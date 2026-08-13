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
        $epicId = (int) input('epic_id', 0);
        $sql = 'SELECT a.*,
                       (SELECT COUNT(*) FROM anomalies_evenement ae WHERE ae.anomalie_id = a.id) AS signalements,
                       (SELECT COUNT(*) FROM epic_anomalies ea WHERE ea.anomalie_id = a.id) AS epics,
                       (SELECT COUNT(*) FROM routing_rules rr WHERE rr.anomalie_id = a.id AND rr.ca_id IS NULL AND rr.actif = 1) AS regles_routage,
                       (SELECT GROUP_CONCAT(ep.nom SEPARATOR ", ") FROM epic_anomalies ea
                        JOIN epic ep ON ep.id = ea.epic_id WHERE ea.anomalie_id = a.id ORDER BY ep.nom) AS epics_competents
                FROM anomalies a';
        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = 'a.nom LIKE ?';
            $params[] = '%' . $q . '%';
        }

        if ($epicId > 0) {
            $where[] = 'a.id IN (SELECT anomalie_id FROM epic_anomalies WHERE epic_id = ?)';
            $params[] = $epicId;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY a.nom';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.anomalies.index', [
            'anomalies' => $result['items'],
            'q'         => $q,
            'epic_id'   => $epicId,
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'epics'     => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
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

    // ──────────────────────── USERS (TOUS RÔLES) ─────────────────

    public function users(): never
    {
        $q = trim((string) input('q', ''));
        $role = (string) input('role', '');

        $sql = 'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.role_user, u.is_active, u.created_at,
                       a.nom AS association_nom, e.nom AS epic_nom
                FROM users u
                LEFT JOIN associations a ON a.id = u.association_id
                LEFT JOIN epic e ON e.id = u.epic_id
                WHERE u.deleted_at IS NULL';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if ($role !== '') {
            $sql .= ' AND u.role_user = ?';
            $params[] = $role;
        }

        $sql .= ' ORDER BY u.created_at DESC';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.users.index', [
            'users'    => $result['items'],
            'q'        => $q,
            'role'     => $role,
            'page'     => $result['page'],
            'lastPage' => $result['last_page'],
            'total'    => $result['total'],
            'errors'   => $this->errors(),
        ]);
    }

    public function userShow(string $id): never
    {
        $user = Database::one(
            'SELECT u.*, a.nom AS association_nom, e.nom AS epic_nom
             FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             LEFT JOIN epic e ON e.id = u.epic_id
             WHERE u.id = ?',
            [(int) $id]
        );
        if ($user === null) {
            abort(404, 'Utilisateur introuvable');
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

        $this->view('admin.users.show', [
            'user'           => $user,
            'participations' => $participations,
            'errors'         => $this->errors(),
        ]);
    }

    public function userToggle(string $id): never
    {
        $user = Database::one('SELECT id, is_active, deleted_at FROM users WHERE id = ?', [(int) $id]);
        if ($user === null) {
            abort(404, 'Utilisateur introuvable');
        }
        if ($user['deleted_at'] !== null) {
            flash('error', 'Ce compte est archivé : restaurez-le avant de modifier son statut.');
            redirect('admin/users/' . $id);
        }

        $newStatus = (int) $user['is_active'] === 1 ? 0 : 1;
        Database::update('users', ['is_active' => $newStatus], 'id = ?', [(int) $id]);

        AuditLog::log('user_toggle', 'user', (int) $id, ['is_active' => (int) $user['is_active']], ['is_active' => $newStatus]);
        flash('success', 'Statut du compte mis à jour.');
        redirect('admin/users/' . $id);
    }

    public function userRole(string $id): never
    {
        $user = Database::one('SELECT id, role_user FROM users WHERE id = ?', [(int) $id]);
        if ($user === null) {
            abort(404, 'Utilisateur introuvable');
        }

        $newRole = (string) input('role');
        $validRoles = ['citoyen', 'association', 'epic', 'wilaya'];
        if (!in_array($newRole, $validRoles, true)) {
            flash('error', 'Rôle invalide.');
            redirect('admin/users/' . $id);
        }

        Database::update('users', ['role_user' => $newRole], 'id = ?', [(int) $id]);

        AuditLog::log('user_role_change', 'user', (int) $id, ['role_user' => $user['role_user']], ['role_user' => $newRole]);
        flash('success', 'Rôle mis à jour.');
        redirect('admin/users/' . $id);
    }

    public function userDelete(string $id): never
    {
        $user = Database::one('SELECT id, nom, prenom, role_user, deleted_at FROM users WHERE id = ?', [(int) $id]);
        if ($user === null) {
            abort(404, 'Utilisateur introuvable');
        }
        if ($user['deleted_at'] !== null) {
            flash('error', 'Ce compte est déjà archivé.');
            redirect('admin/users/' . $id);
        }

        // Suppression logique : on archive le compte (l'historique —
        // participations, évaluations, notifications — reste intègre).
        Database::update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'is_active'  => 0,
        ], 'id = ?', [(int) $id]);
        Database::run('DELETE FROM sessions WHERE user_id = ?', [(int) $id]);

        AuditLog::log('user_deleted', 'user', (int) $id, ['nom' => $user['nom'], 'prenom' => $user['prenom'], 'role_user' => $user['role_user'], 'soft' => true], null);
        flash('success', 'Compte archivé (suppression logique).');
        redirect('admin/users');
    }

    // ──────────────────────── PRÉSIDENTS ────────────────────────

    public function presidents(): never
    {
        $q = trim((string) input('q', ''));

        $sql = 'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.is_active, u.created_at,
                       a.id AS association_id, a.nom AS association_nom, a.valide AS association_valide
                FROM users u
                JOIN associations a ON a.id = u.association_id
                WHERE u.role_user = ?';
        $params = ['association'];

        if ($q !== '') {
            $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR a.nom LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY u.created_at DESC';

        $result = Database::paginate($sql, $params, 15, (int) input('page', 1));

        $this->view('admin.presidents.index', [
            'presidents' => $result['items'],
            'q'          => $q,
            'page'       => $result['page'],
            'lastPage'   => $result['last_page'],
            'total'      => $result['total'],
            'errors'     => $this->errors(),
        ]);
    }

    public function presidentShow(string $id): never
    {
        $president = Database::one(
            'SELECT u.*, a.nom AS association_nom, a.valide AS association_valide, a.email AS association_email
             FROM users u
             JOIN associations a ON a.id = u.association_id
             WHERE u.id = ? AND u.role_user = ?',
            [(int) $id, 'association']
        );
        if ($president === null) {
            abort(404, 'Président introuvable');
        }

        $this->view('admin.users.show', [
            'user'           => $president,
            'participations' => [],
            'errors'         => $this->errors(),
        ]);
    }

    public function presidentToggle(string $id): never
    {
        $president = Database::one('SELECT id, is_active FROM users WHERE id = ? AND role_user = ?', [(int) $id, 'association']);
        if ($president === null) {
            abort(404, 'Président introuvable');
        }

        $newStatus = (int) $president['is_active'] === 1 ? 0 : 1;
        Database::update('users', ['is_active' => $newStatus], 'id = ?', [(int) $id]);

        AuditLog::log('president_toggle', 'user', (int) $id, ['is_active' => (int) $president['is_active']], ['is_active' => $newStatus]);
        flash('success', 'Statut du président mis à jour.');
        redirect('admin/presidents/' . $id);
    }
}
