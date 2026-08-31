<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\StatsService;
use App\Helpers\UploadHelper;

/**
 * Espace EPIC — suivi des interventions et événements assignés.
 *
 * Parcours complet (Lot 5) :
 *   - acceptation / refus d'une affectation,
 *   - changement de statut (gated par acceptation),
 *   - preuves avant / après (photos),
 *   - clôture d'intervention (rapport),
 *   - agenda (vue calendrier).
 */
final class EpicController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * @param array<string, mixed> $user
     */
    private function guardEpic(array $user): int
    {
        $epicId = (int) ($user['epic_id'] ?? 0);
        if ($epicId === 0) {
            flash('error', 'Aucun EPIC lié à votre compte.');
            redirect(dashboard_path());
        }

        return $epicId;
    }

    public function index(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = $this->guardEpic($user);

        $epic = Database::one('SELECT * FROM epic WHERE id = ?', [$epicId]);

        $filters = [
            'q'      => trim((string) input('q', '')),
            'statut' => input('statut'),
        ];

        $where = ['ee.epic_id = ?', 'e.deleted_at IS NULL', "ee.accepte <> 'REFUSE'"];
        $params = [$epicId];

        if (! empty($filters['q'])) {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ?)';
            $like = '%' . trim($filters['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (! empty($filters['statut'])) {
            $where[] = 'ee.statut = ?';
            $params[] = (string) $filters['statut'];
        }

        $sql = 'SELECT e.id AS evenement_id, e.adresse AS evenement_adresse, e.description, e.statut AS evenement_statut,
                       e.date_evenement, e.heure, e.updated_at,
                       ee.id AS intervention_id, ee.statut AS intervention_statut,
                       ee.accepte, ee.date_affectation, ee.observation,
                       c.nom AS commune_nom, a.nom AS association_nom
                FROM evenement_epic ee
                JOIN evenements e ON e.id = ee.evenement_id
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.date_evenement DESC, e.updated_at DESC';

        $result = Database::paginate($sql, $params, self::PER_PAGE, (int) input('page', 1));

        $kpis = [
            'total'       => (int) Database::value('SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND accepte <> \'REFUSE\'', [$epicId]),
            'affecte'     => (int) Database::value("SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND statut = 'AFFECTE' AND accepte = 'ACCEPTE'", [$epicId]),
            'en_cours'    => (int) Database::value("SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND statut = 'EN_COURS'", [$epicId]),
            'termine'     => (int) Database::value("SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND statut = 'TERMINE'", [$epicId]),
            'anomalie'    => (int) Database::value("SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND statut = 'ANOMALIE'", [$epicId]),
            'en_attente'  => (int) Database::value("SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ? AND accepte = 'EN_ATTENTE'", [$epicId]),
            'temps_moyen_epic' => StatsService::tempsMoyenEpicForEpic($epicId),
        ];

        $this->view('epic/index', [
            'epic'          => $epic,
            'interventions' => $result['items'],
            'filters'       => $filters,
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'kpis'          => $kpis,
        ]);
    }

    public function show(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = $this->guardEpic($user);

        $intervention = Database::one(
            'SELECT e.id AS evenement_id, e.adresse AS evenement_adresse, e.description,
                    e.statut AS evenement_statut, e.date_evenement, e.heure,
                    e.latitude, e.longitude,
                    ee.id AS intervention_id, ee.statut AS intervention_statut, ee.observation AS intervention_observation,
                    ee.accepte, ee.motif_refus, ee.date_acceptation, ee.rapport,
                    ee.date_debut_reel, ee.date_fin_reel, ee.cloture, ee.date_cloture,
                    c.nom AS commune_nom, a.nom AS association_nom,
                    a.id AS association_id,
                    a.email AS association_email, a.telephone AS association_telephone,
                    a.nom_prenom_president AS association_president, q.token_qr
             FROM evenements e
             JOIN evenement_epic ee ON ee.evenement_id = e.id
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.id = ? AND ee.epic_id = ? AND e.deleted_at IS NULL',
            [(int) $id, $epicId]
        );

        if ($intervention === null) {
            abort(404, 'Intervention introuvable.');
        }

        $participants = Database::all(
            'SELECT p.*, u.nom, u.prenom FROM evenement_participant p
             JOIN users u ON u.id = p.user_id
             WHERE p.evenement_id = ?
             ORDER BY p.heure_scan DESC',
            [(int) $intervention['evenement_id']]
        );

        $preuves = Database::all(
            'SELECT p.*, u.nom, u.prenom
             FROM epic_preuves p
             LEFT JOIN users u ON u.id = p.uploaded_by
             WHERE p.evenement_epic_id = ?
             ORDER BY p.created_at DESC, p.id DESC',
            [(int) $intervention['intervention_id']]
        );

        $preuvesAvant = array_values(array_filter($preuves, static fn (array $p): bool => ($p['type'] ?? '') === 'AVANT'));
        $preuvesApres = array_values(array_filter($preuves, static fn (array $p): bool => ($p['type'] ?? '') === 'APRES'));

        $this->view('epic/show', [
            'intervention'  => $intervention,
            'participants'  => $participants,
            'preuvesAvant'  => $preuvesAvant,
            'preuvesApres'  => $preuvesApres,
        ]);
    }

    public function agenda(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = $this->guardEpic($user);

        $now = getdate();
        $year  = (int) input('year', (string) $now['year']);
        $month = (int) input('month', (string) $now['mon']);
        if ($month < 1 || $month > 12) {
            $month = (int) $now['mon'];
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) $now['year'];
        }

        $first = sprintf('%04d-%02d-01', $year, $month);
        $last  = date('Y-m-t', strtotime($first));

        $interventions = Database::all(
            'SELECT e.id AS evenement_id, e.adresse, e.date_evenement, e.heure,
                    ee.id AS intervention_id, ee.statut AS intervention_statut, ee.accepte,
                    c.nom AS commune_nom
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ee.epic_id = ?
               AND ee.accepte <> \'REFUSE\'
               AND e.deleted_at IS NULL
               AND e.date_evenement BETWEEN ? AND ?
             ORDER BY e.date_evenement ASC, e.heure ASC',
            [$epicId, $first, $last]
        );

        $byDay = [];
        foreach ($interventions as $iv) {
            $day = (int) date('j', strtotime((string) $iv['date_evenement']));
            $byDay[$day][] = $iv;
        }

        $dim = (int) date('N', strtotime($first));
        $nbJours = (int) date('t', strtotime($first));
        $prev = mktime(0, 0, 0, $month - 1, 1, $year);
        $next = mktime(0, 0, 0, $month + 1, 1, $year);

        $this->view('epic/agenda', [
            'interventions' => $byDay,
            'year'          => $year,
            'month'         => $month,
            'dim'           => $dim,
            'nbJours'       => $nbJours,
            'prev'          => ['y' => (int) date('Y', $prev), 'm' => (int) date('n', $prev)],
            'next'          => ['y' => (int) date('Y', $next), 'm' => (int) date('n', $next)],
            'isCurrent'     => $year === (int) $now['year'] && $month === (int) $now['mon'],
        ]);
    }

    public function accept(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);

        $row = Database::one(
            'SELECT ee.id, ee.evenement_id, ee.accepte, e.association_id, e.adresse
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             WHERE ee.id = ? AND ee.epic_id = ?',
            [(int) $id, $epicId]
        );

        if ($row === null) {
            json_response(['success' => false, 'error' => 'Intervention introuvable.']);
        }

        if (($row['accepte'] ?? '') !== 'EN_ATTENTE') {
            json_response(['success' => false, 'error' => 'Affectation déjà traitée.']);
        }

        Database::run(
            'UPDATE evenement_epic SET accepte = \'ACCEPTE\', date_acceptation = CURRENT_TIMESTAMP WHERE id = ?',
            [(int) $id]
        );

        AuditLog::log('epic.intervention_acceptee', 'evenement_epic', (int) $id);

        if (! empty($row['association_id'])) {
            Notification::sendToAssociation(
                (int) $row['association_id'],
                __('epic.accept_title'),
                __('epic.accept_message', ['adresse' => (string) ($row['adresse'] ?? '')]),
                'epic_acceptee',
                ['evenement_id' => (int) ($row['evenement_id'] ?? 0)]
            );
        }

        json_response(['success' => true, 'message' => __('epic.accept_ok')]);
    }

    public function refuser(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        $motif  = trim((string) input('motif', ''));

        if ($motif === '') {
            json_response(['success' => false, 'error' => __('epic.refuse_motif_required')]);
        }

        $row = Database::one(
            'SELECT ee.id, ee.evenement_id, ee.accepte, e.association_id, e.adresse
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             WHERE ee.id = ? AND ee.epic_id = ?',
            [(int) $id, $epicId]
        );

        if ($row === null) {
            json_response(['success' => false, 'error' => 'Intervention introuvable.']);
        }

        if (($row['accepte'] ?? '') !== 'EN_ATTENTE') {
            json_response(['success' => false, 'error' => 'Affectation déjà traitée.']);
        }

        Database::run(
            'UPDATE evenement_epic SET accepte = \'REFUSE\', motif_refus = ?, date_acceptation = CURRENT_TIMESTAMP WHERE id = ?',
            [$motif, (int) $id]
        );

        AuditLog::log('epic.intervention_refusee', 'evenement_epic', (int) $id, ['motif' => $motif]);

        Notification::sendToRole(
            'wilaya',
            __('epic.refuse_title'),
            __('epic.refuse_message', ['adresse' => (string) ($row['adresse'] ?? ''), 'motif' => $motif]),
            'epic_refus',
            ['evenement_id' => (int) ($row['evenement_id'] ?? 0)]
        );

        json_response(['success' => true, 'message' => __('epic.refuse_ok')]);
    }

    public function updateStatut(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        $nouveauStatut = (string) input('statut', '');

        $validStatuts = ['AFFECTE', 'EN_COURS', 'TERMINE', 'ANOMALIE'];
        if (! in_array($nouveauStatut, $validStatuts, true)) {
            json_response(['success' => false, 'error' => 'Statut invalide.']);
        }

        $intervention = Database::one(
            'SELECT ee.id, ee.evenement_id, ee.statut AS ancien_statut, ee.accepte, ee.cloture, e.adresse
             FROM evenement_epic ee
             LEFT JOIN evenements e ON e.id = ee.evenement_id
             WHERE ee.id = ? AND ee.epic_id = ?',
            [(int) $id, $epicId]
        );

        if ($intervention === null) {
            json_response(['success' => false, 'error' => 'Intervention introuvable.']);
        }

        if (($intervention['accepte'] ?? '') !== 'ACCEPTE' || ($intervention['cloture'] ?? 'OUVERTE') === 'CLOTUREE') {
            json_response(['success' => false, 'error' => __('epic.statut_blocked')]);
        }

        if ($nouveauStatut === 'TERMINE') {
            json_response(['success' => false, 'error' => __('epic.use_closure')]);
        }

        if ($nouveauStatut === 'EN_COURS' && empty($intervention['date_debut_reel'])) {
            Database::run(
                "UPDATE evenement_epic SET statut = ?, date_debut_reel = CURRENT_TIMESTAMP WHERE id = ?",
                [$nouveauStatut, (int) $id]
            );
        } else {
            Database::run(
                'UPDATE evenement_epic SET statut = ? WHERE id = ?',
                [$nouveauStatut, (int) $id]
            );
        }

        AuditLog::log(
            'epic.intervention_statut',
            'evenement_epic',
            (int) $id,
            ['statut' => $intervention['ancien_statut'] ?? null],
            ['statut' => $nouveauStatut]
        );

        if ($nouveauStatut === 'ANOMALIE') {
            Notification::sendToRole(
                'wilaya',
                __('epic.anomaly_reported_title'),
                __('epic.anomaly_reported_message', ['adresse' => (string) ($intervention['adresse'] ?? '')]),
                'epic_anomalie',
                ['evenement_id' => (int) ($intervention['evenement_id'] ?? 0)]
            );
        }

        json_response(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function uploadPreuves(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        $type   = (string) input('type', '');
        if (! in_array($type, ['AVANT', 'APRES'], true)) {
            flash('error', 'Type de preuve invalide.', 'danger');
            $this->redirect('epic/' . (int) $id);
        }

        $interventionId = (int) $id;
        $row = Database::one(
            'SELECT id, evenement_id, accepte, cloture FROM evenement_epic WHERE id = ? AND epic_id = ?',
            [$interventionId, $epicId]
        );
        if ($row === null) {
            abort(404, 'Intervention introuvable.');
        }
        if (($row['accepte'] ?? '') !== 'ACCEPTE' || ($row['cloture'] ?? 'OUVERTE') === 'CLOTUREE') {
            flash('error', __('epic.preuves_blocked'), 'danger');
            $this->redirect('epic/' . $interventionId);
        }

        $files = $_FILES['preuves'] ?? null;
        if ($files === null || ($files['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash('error', __('epic.preuve_no_file'), 'danger');
            $this->redirect('epic/' . $interventionId);
        }

        $uploadDir = config('paths.uploads.preuves', public_path('uploads/preuves'));
        $maxSize   = (int) config('security.upload_max', 5242880);
        $result    = UploadHelper::uploadMultiple($files, $uploadDir, $maxSize);

        $inserted = 0;
        foreach ($result['successes'] as $path) {
            $idx = array_search($path, $result['successes'], true);
            Database::insert('epic_preuves', [
                'evenement_epic_id' => $interventionId,
                'type'              => $type,
                'fichier'           => (string) $path,
                'type_mime'         => $files['type'][$idx] ?? null,
                'taille'            => isset($files['size'][$idx]) ? (int) $files['size'][$idx] : null,
                'uploaded_by'       => Session::userId(),
            ]);
            $inserted++;
        }

        AuditLog::log('epic_preuve_uploaded', 'evenement_epic', $interventionId, ['type' => $type], ['count' => $inserted]);

        if ($inserted > 0) {
            flash('success', __('epic.preuves_added', ['count' => $inserted]));
        }
        if (! empty($result['errors'])) {
            flash('error', count($result['errors']) . ' fichier(s) rejeté(s).', 'warning');
        }

        $this->redirect('epic/' . $interventionId);
    }

    public function deletePreuve(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);

        $preuve = Database::one(
            'SELECT p.* FROM epic_preuves p
             JOIN evenement_epic ee ON ee.id = p.evenement_epic_id
             WHERE p.id = ? AND ee.epic_id = ? AND ee.cloture = \'OUVERTE\'',
            [(int) $id, $epicId]
        );
        if ($preuve === null) {
            abort(404, 'Preuve introuvable.');
        }

        if (! empty($preuve['fichier'])) {
            UploadHelper::delete($preuve['fichier']);
        }
        Database::delete('epic_preuves', 'id = ?', [(int) $id]);

        AuditLog::log('epic_preuve_deleted', 'evenement_epic', (int) $preuve['evenement_epic_id'], ['fichier' => $preuve['fichier']]);

        flash('success', __('epic.preuve_deleted'));
        $this->redirect('epic/' . (int) $preuve['evenement_epic_id']);
    }

    public function cloturer(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        $rapport = trim((string) input('rapport', ''));

        if ($rapport === '') {
            json_response(['success' => false, 'error' => __('epic.rapport_required')]);
        }

        $interventionId = (int) $id;
        $row = Database::one(
            'SELECT ee.id, ee.evenement_id, ee.accepte, ee.cloture, e.association_id, e.adresse
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             WHERE ee.id = ? AND ee.epic_id = ?',
            [$interventionId, $epicId]
        );

        if ($row === null) {
            json_response(['success' => false, 'error' => 'Intervention introuvable.']);
        }

        if (($row['accepte'] ?? '') !== 'ACCEPTE' || ($row['cloture'] ?? 'OUVERTE') === 'CLOTUREE') {
            json_response(['success' => false, 'error' => __('epic.preuves_blocked')]);
        }

        Database::run(
            "UPDATE evenement_epic
             SET statut = 'TERMINE', cloture = 'CLOTUREE', date_cloture = CURRENT_TIMESTAMP,
                 date_fin_reel = CURRENT_TIMESTAMP, rapport = ?
             WHERE id = ?",
            [$rapport, $interventionId]
        );

        AuditLog::log('epic.intervention_cloturee', 'evenement_epic', $interventionId, null, ['rapport' => $rapport]);

        if (! empty($row['association_id'])) {
            Notification::sendToAssociation(
                (int) $row['association_id'],
                __('epic.closure_title'),
                __('epic.closure_message', ['adresse' => (string) ($row['adresse'] ?? '')]),
                'epic_cloturee',
                ['evenement_id' => (int) ($row['evenement_id'] ?? 0)]
            );
        }

        Notification::sendToRole(
            'wilaya',
            __('epic.closure_title'),
            __('epic.closure_message', ['adresse' => (string) ($row['adresse'] ?? '')]),
            'epic_cloturee',
            ['evenement_id' => (int) ($row['evenement_id'] ?? 0)]
        );

        json_response(['success' => true, 'message' => __('epic.closure_ok')]);
    }
}
