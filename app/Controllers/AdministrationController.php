<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Validator;

/**
 * Administration du référentiel : EPIC, anomalies, citoyens (niveau Wilaya).
 */
final class AdministrationController extends Controller
{
    // ─────────────────────────── EPIC ───────────────────────────

    public function epics(): never
    {
        $q = trim((string) input('q', ''));
        $sql = 'SELECT e.*,
                       (SELECT COUNT(*) FROM evenement_epic ee WHERE ee.epic_id = e.id) AS interventions,
                       (SELECT COUNT(*) FROM epic_anomalies ea WHERE ea.epic_id = e.id) AS competences,
                       (SELECT COUNT(*) FROM users u WHERE u.epic_id = e.id AND u.is_active = 1) AS comptes_actifs
                FROM epic e';
        $params = [];

        if ($q !== '') {
            $sql .= ' WHERE e.nom LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY e.nom';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.epics.index', [
            'epics'     => $result['items'],
            'q'         => $q,
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'errors'    => $this->errors(),
        ]);
    }

    public function epicCreate(): never
    {
        $this->view('admin.epics.create', [
            'anomalies' => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'errors'    => $this->errors(),
        ]);
    }

    public function epicStore(): never
    {
        $data = all_input();
        $this->validate($data, [
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'couleur' => 'nullable|string|max:7',
        ]);

        $id = (int) Database::insert('epic', [
            'nom'         => trim((string) $data['nom']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'couleur'     => trim((string) ($data['couleur'] ?? '')) ?: null,
        ]);

        $this->syncCompetences($id, (array) ($data['anomalies'] ?? []));

        AuditLog::log('epic_created', 'epic', $id, null, ['nom' => trim((string) $data['nom'])]);
        flash('success', 'EPIC créée.');
        redirect('admin/epics');
    }

    public function epicEdit(string $id): never
    {
        $epic = Database::one('SELECT * FROM epic WHERE id = ?', [(int) $id]);
        if ($epic === null) {
            abort(404, 'EPIC introuvable');
        }

        $assigned = Database::all('SELECT anomalie_id FROM epic_anomalies WHERE epic_id = ?', [(int) $id]);

        $this->view('admin.epics.edit', [
            'epic'       => $epic,
            'anomalies'  => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'assigned'   => array_map(static fn(array $a): int => (int) $a['anomalie_id'], $assigned),
            'errors'     => $this->errors(),
        ]);
    }

    public function epicUpdate(string $id): never
    {
        $epic = Database::one('SELECT * FROM epic WHERE id = ?', [(int) $id]);
        if ($epic === null) {
            abort(404, 'EPIC introuvable');
        }

        $data = all_input();
        $this->validate($data, [
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'couleur' => 'nullable|string|max:7',
        ]);

        Database::update('epic', [
            'nom'         => trim((string) $data['nom']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'couleur'     => trim((string) ($data['couleur'] ?? '')) ?: null,
        ], 'id = ?', [(int) $id]);

        $this->syncCompetences((int) $id, (array) ($data['anomalies'] ?? []));

        AuditLog::log('epic_updated', 'epic', (int) $id, ['nom' => $epic['nom']], ['nom' => trim((string) $data['nom'])]);
        flash('success', 'EPIC mise à jour.');
        redirect('admin/epics');
    }

    public function epicDelete(string $id): never
    {
        $epic = Database::one('SELECT * FROM epic WHERE id = ?', [(int) $id]);
        if ($epic === null) {
            abort(404, 'EPIC introuvable');
        }

        $interventions = (int) Database::value('SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ?', [(int) $id]);
        if ($interventions > 0) {
            flash('error', "Impossible de supprimer : {$interventions} intervention(s) sont liées à cette EPIC.");
            redirect('admin/epics');
        }

        Database::run('DELETE FROM epic_anomalies WHERE epic_id = ?', [(int) $id]);
        Database::run('UPDATE users SET epic_id = NULL WHERE epic_id = ?', [(int) $id]);
        Database::run('DELETE FROM epic WHERE id = ?', [(int) $id]);

        AuditLog::log('epic_deleted', 'epic', (int) $id, ['nom' => $epic['nom']], null);
        flash('success', 'EPIC supprimée.');
        redirect('admin/epics');
    }

    private function syncCompetences(int $epicId, array $anomalieIds): void
    {
        Database::run('DELETE FROM epic_anomalies WHERE epic_id = ?', [$epicId]);

        foreach (array_map('intval', $anomalieIds) as $anomalieId) {
            if ($anomalieId > 0) {
                Database::run('INSERT IGNORE INTO epic_anomalies (epic_id, anomalie_id) VALUES (?, ?)', [$epicId, $anomalieId]);
            }
        }
    }

    // ─────────────────────── ANOMALIES ──────────────────────────

    public function anomalies(): never
    {
        $q = trim((string) input('q', ''));
        $sql = 'SELECT a.*,
                       (SELECT COUNT(*) FROM anomalies_evenement ae WHERE ae.anomalie_id = a.id) AS signalements,
                       (SELECT COUNT(*) FROM epic_anomalies ea WHERE ea.anomalie_id = a.id) AS epics
                FROM anomalies a';
        $params = [];

        if ($q !== '') {
            $sql .= ' WHERE a.nom LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY a.nom';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.anomalies.index', [
            'anomalies' => $result['items'],
            'q'         => $q,
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'errors'    => $this->errors(),
        ]);
    }

    public function anomalyCreate(): never
    {
        $this->view('admin.anomalies.create', ['errors' => $this->errors()]);
    }

    public function anomalyStore(): never
    {
        $data = all_input();
        $this->validate($data, [
            'nom'         => 'required|string|max:100',
            'description' => 'nullable|string',
            'icone'       => 'nullable|string|max:60',
            'couleur'     => 'nullable|string|max:7',
        ]);

        $id = (int) Database::insert('anomalies', [
            'nom'         => trim((string) $data['nom']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'icone'       => trim((string) ($data['icone'] ?? '')) ?: null,
            'couleur'     => trim((string) ($data['couleur'] ?? '')) ?: null,
        ]);

        AuditLog::log('anomalie_created', 'anomalie', $id, null, ['nom' => trim((string) $data['nom'])]);
        flash('success', 'Anomalie créée.');
        redirect('admin/anomalies');
    }

    public function anomalyEdit(string $id): never
    {
        $anomalie = Database::one('SELECT * FROM anomalies WHERE id = ?', [(int) $id]);
        if ($anomalie === null) {
            abort(404, 'Anomalie introuvable');
        }

        $this->view('admin.anomalies.edit', ['anomalie' => $anomalie, 'errors' => $this->errors()]);
    }

    public function anomalyUpdate(string $id): never
    {
        $anomalie = Database::one('SELECT * FROM anomalies WHERE id = ?', [(int) $id]);
        if ($anomalie === null) {
            abort(404, 'Anomalie introuvable');
        }

        $data = all_input();
        $this->validate($data, [
            'nom'         => 'required|string|max:100',
            'description' => 'nullable|string',
            'icone'       => 'nullable|string|max:60',
            'couleur'     => 'nullable|string|max:7',
        ]);

        Database::update('anomalies', [
            'nom'         => trim((string) $data['nom']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'icone'       => trim((string) ($data['icone'] ?? '')) ?: null,
            'couleur'     => trim((string) ($data['couleur'] ?? '')) ?: null,
        ], 'id = ?', [(int) $id]);

        AuditLog::log('anomalie_updated', 'anomalie', (int) $id, ['nom' => $anomalie['nom']], ['nom' => trim((string) $data['nom'])]);
        flash('success', 'Anomalie mise à jour.');
        redirect('admin/anomalies');
    }

    public function anomalyDelete(string $id): never
    {
        $anomalie = Database::one('SELECT * FROM anomalies WHERE id = ?', [(int) $id]);
        if ($anomalie === null) {
            abort(404, 'Anomalie introuvable');
        }

        $signalements = (int) Database::value('SELECT COUNT(*) FROM anomalies_evenement WHERE anomalie_id = ?', [(int) $id]);
        if ($signalements > 0) {
            flash('error', "Impossible de supprimer : {$signalements} événement(s) référencent cette anomalie.");
            redirect('admin/anomalies');
        }

        Database::run('DELETE FROM epic_anomalies WHERE anomalie_id = ?', [(int) $id]);
        Database::run('DELETE FROM anomalies WHERE id = ?', [(int) $id]);

        AuditLog::log('anomalie_deleted', 'anomalie', (int) $id, ['nom' => $anomalie['nom']], null);
        flash('success', 'Anomalie supprimée.');
        redirect('admin/anomalies');
    }

    // ──────────────────────── CITOYENS ──────────────────────────

    public function citoyens(): never
    {
        $q = trim((string) input('q', ''));
        $sql = 'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.points, u.is_active, u.created_at,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.user_id = u.id) AS participations
                FROM users u
                WHERE u.role_user = ?';
        $params = ['citoyen'];

        if ($q !== '') {
            $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY u.created_at DESC';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.citoyens.index', [
            'citoyens'  => $result['items'],
            'q'         => $q,
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'errors'    => $this->errors(),
        ]);
    }

    public function citoyenShow(string $id): never
    {
        $citoyen = Database::one(
            'SELECT u.*,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.user_id = u.id) AS participations
             FROM users u WHERE u.id = ? AND u.role_user = ?',
            [(int) $id, 'citoyen']
        );
        if ($citoyen === null) {
            abort(404, 'Citoyen introuvable');
        }

        $participations = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut, c.nom AS commune_nom, ep.heure_scan
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC LIMIT 20',
            [(int) $id]
        );

        $badges = Database::all(
            'SELECT b.nom, b.icone FROM badges b
             JOIN user_badges ub ON ub.badge_id = b.id
             WHERE ub.user_id = ?',
            [(int) $id]
        );

        $this->view('admin.citoyens.show', [
            'citoyen'        => $citoyen,
            'participations' => $participations,
            'badges'         => $badges,
            'errors'         => $this->errors(),
        ]);
    }

    public function citoyenToggle(string $id): never
    {
        $citoyen = Database::one('SELECT id, is_active FROM users WHERE id = ? AND role_user = ?', [(int) $id, 'citoyen']);
        if ($citoyen === null) {
            abort(404, 'Citoyen introuvable');
        }

        Database::update('users', ['is_active' => (int) $citoyen['is_active'] === 1 ? 0 : 1], 'id = ?', [(int) $id]);

        AuditLog::log('citoyen_activation', 'citoyen', (int) $id, ['is_active' => (int) $citoyen['is_active']], ['is_active' => (int) $citoyen['is_active'] === 1 ? 0 : 1]);
        flash('success', 'Statut du compte mis à jour.');
        redirect('admin/citoyens/' . $id);
    }
}
