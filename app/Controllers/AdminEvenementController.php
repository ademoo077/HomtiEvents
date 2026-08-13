<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Notification;
use App\Helpers\QrCodeService;
use App\Helpers\RoutingService;
use App\Helpers\Session;
use App\Helpers\Validator;

/**
 * Gestion administrative des événements (centre de commandement Wilaya).
 *
 * Liste filtrée, création directe, édition complète, workflow, EPIC, QR,
 * archivage et actions groupées.
 */
final class AdminEvenementController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): never
    {
        $this->requirePermission('evenement.view_all');
        $filters = [
            'q'            => trim((string) input('q', '')),
            'statut'        => input('statut'),
            'commune_id'    => input('commune_id'),
            'association_id' => input('association_id'),
            'epic_id'       => input('epic_id'),
            'anomalie_id'   => input('anomalie_id'),
            'du'            => input('du'),
            'au'            => input('au'),
            'deleted'       => input('deleted') !== null,
        ];

        [$sql, $params] = EvenementService::queryFiltres($filters);
        $page = (int) input('page', 1);
        $result = Database::paginate($sql, $params, self::PER_PAGE, $page);

        $this->view('wilaya.evenements.index', [
            'evenements'  => $result['items'],
            'filters'     => $filters,
            'page'        => $result['page'],
            'lastPage'    => $result['last_page'],
            'total'       => $result['total'],
            'communes'    => Database::all('SELECT id, nom FROM commune ORDER BY nom'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'anomalies'   => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
        ]);
    }

    public function dashboard(): never
    {
        $this->requirePermission('evenement.view_all');

        $kpis = [
            'total'       => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL'),
            'en_attente'  => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_ATTENTE' AND deleted_at IS NULL"),
            'programmes'  => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut IN ('PROGRAMME', 'QR_GENERE') AND deleted_at IS NULL"),
            'en_cours'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_COURS' AND deleted_at IS NULL"),
            'termines'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'TERMINE' AND deleted_at IS NULL"),
            'associations' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'),
            'epics'       => (int) Database::value('SELECT COUNT(*) FROM epic'),
            'communes'    => (int) Database::value('SELECT COUNT(*) FROM commune WHERE is_active = 1'),
            // Galerie KPIs
            'total_photos' => (int) Database::value('SELECT COUNT(*) FROM photos'),
            'total_albums' => (int) Database::value('SELECT COUNT(*) FROM albums'),
            // Association requests KPIs
            'total_requests'  => (int) Database::value('SELECT COUNT(*) FROM association_requests'),
            'pending_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'pending'"),
            'approved_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'approved'"),
            'rejected_requests' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'rejected'"),
        ];

        $parStatut = Database::all("SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL GROUP BY statut");
        $parMois = Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mois ORDER BY mois");
        $prochains = Database::all("SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure, c.nom AS commune_nom
            FROM evenements e
            LEFT JOIN commune c ON c.id = e.commune_id
            WHERE e.deleted_at IS NULL AND e.statut NOT IN ('TERMINE', 'REFUSE')
            ORDER BY e.date_evenement ASC LIMIT 6");

        // Activité récente
        $recentActivity = Database::all(
            "SELECT a.action, a.created_at, a.modele, u.nom, u.prenom
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT 8"
        );

        // Photos récentes
        $recentPhotos = Database::all(
            "SELECT p.image, p.uploaded_at, a.titre AS album_titre, e.adresse
             FROM photos p
             JOIN albums a ON a.id = p.album_id
             JOIN evenements e ON e.id = a.evenement_id
             ORDER BY p.uploaded_at DESC LIMIT 6"
        );

        // Taux de complétion
        $tauxComplet = $kpis['total'] > 0
            ? round(($kpis['termines'] / $kpis['total']) * 100)
            : 0;

        // ── Idées & conseils contextuels (actions recommandées) ──
        $validesSansDate = Database::all(
            "SELECT e.id, e.adresse, e.description
             FROM evenements e
             WHERE e.statut = 'VALIDÉ' AND e.deleted_at IS NULL AND e.date_evenement IS NULL
             ORDER BY e.created_at ASC LIMIT 5"
        );
        $programmesSansQr = Database::all(
            "SELECT e.id, e.adresse, e.description
             FROM evenements e
             WHERE e.statut IN ('PROGRAMME', 'QR_GENERE') AND e.deleted_at IS NULL
               AND NOT EXISTS (SELECT 1 FROM qr_event q WHERE q.evenement_id = e.id)
             ORDER BY e.created_at ASC LIMIT 5"
        );
        $slaRetard = (int) Database::value(
            "SELECT COUNT(*) FROM sla_alertes WHERE envoyee = 1 AND type = 'retard'"
        );
        $evenementsSansEpic = (int) Database::value(
            "SELECT COUNT(*) FROM evenements e
             WHERE e.deleted_at IS NULL AND e.statut NOT IN ('TERMINE', 'REFUSE', 'ANNULE')
               AND NOT EXISTS (SELECT 1 FROM evenement_epic ee WHERE ee.evenement_id = e.id)"
        );
        $qrsActifs = (int) Database::value(
            'SELECT COUNT(*) FROM qr_event WHERE date_expiration >= NOW() OR date_expiration IS NULL'
        );

        $suggestions = [];
        if ($kpis['en_attente'] > 0) {
            $suggestions[] = ['icon' => 'mdi-clock-outline', 'color' => 'amber',
                'titre' => $kpis['en_attente'] . ' demande(s) en attente',
                'texte' => 'Des demandes d\'événements attendent une décision. Validez, demandez des modifications ou refusez-les pour débloquer le circuit.',
                'lien'  => url('wilaya/evenements?statut=EN_ATTENTE')];
        }
        if ($validesSansDate !== []) {
            $suggestions[] = ['icon' => 'mdi-calendar-blank-outline', 'color' => 'violet',
                'titre' => count($validesSansDate) . ' événement(s) validé(s) sans date',
                'texte' => 'Programmez-les pour fixer une date/heure et générer le QR code automatiquement.',
                'lien'  => url('wilaya/evenements?statut=VALIDÉ')];
        }
        if ($programmesSansQr !== []) {
            $suggestions[] = ['icon' => 'mdi-qrcode-remove', 'color' => 'red',
                'titre' => count($programmesSansQr) . ' événement(s) programmé(s) sans QR',
                'texte' => 'Régénérez le QR code de ces événements pour permettre le contrôle d\'accès.',
                'lien'  => url('wilaya/evenements?statut=PROGRAMME')];
        }
        if ($slaRetard > 0) {
            $suggestions[] = ['icon' => 'mdi-image-off-outline', 'color' => 'red',
                'titre' => $slaRetard . ' alerte(s) album en retard',
                'texte' => 'Des événements terminés n\'ont toujours pas d\'album officiel. Relancez les associations concernées.',
                'lien'  => url('wilaya/gallery')];
        }
        if ($evenementsSansEpic > 0) {
            $suggestions[] = ['icon' => 'mdi-folder-account-outline', 'color' => 'info',
                'titre' => $evenementsSansEpic . ' événement(s) sans EPIC',
                'texte' => 'Affectez une EPIC compétente pour assurer le suivi des travaux sur le terrain.',
                'lien'  => url('wilaya/evenements')];
        }
        if ($kpis['pending_requests'] > 0) {
            $suggestions[] = ['icon' => 'mdi-account-check-outline', 'color' => 'green',
                'titre' => $kpis['pending_requests'] . ' demande(s) d\'inscription en attente',
                'texte' => 'Examinez les nouvelles demandes d\'associations pour agrandir le réseau.',
                'lien'  => url('admin/association-requests?status=pending')];
        }
        if ($suggestions === []) {
            $suggestions[] = ['icon' => 'mdi-lightbulb-on-outline', 'color' => 'primary',
                'titre' => 'Tout est à jour',
                'texte' => 'Aucune action urgente. Le circuit des demandes est fluide. Consultez les statistiques pour piloter votre territoire.',
                'lien'  => null];
        }

        // Demandes d'inscription récentes + ancienneté
        $latestRequests = Database::all(
            "SELECT r.*, DATEDIFF(CURDATE(), DATE(r.created_at)) AS age_jours
             FROM association_requests r
             ORDER BY r.id DESC LIMIT 5"
        );
        $agingPending = (int) Database::value(
            "SELECT COUNT(*) FROM association_requests
             WHERE status = 'pending' AND DATEDIFF(CURDATE(), DATE(created_at)) >= 7"
        );

        // Notifications de l'utilisateur connecté
        $notifFeed = [];
        $unreadNotifs = 0;
        $currentUserId = Session::userId();
        if ($currentUserId !== null) {
            $unreadNotifs = Notification::unreadCount($currentUserId);
            $notifFeed = Notification::recent($currentUserId, 6);
        }

        $this->view('wilaya.dashboard', [
            'kpis'             => $kpis,
            'parStatut'        => $parStatut,
            'parMois'          => $parMois,
            'prochains'        => $prochains,
            'recentActivity'   => $recentActivity,
            'recentPhotos'     => $recentPhotos,
            'tauxComplet'      => $tauxComplet,
            'latestRequests'   => $latestRequests,
            'agingPending'     => $agingPending,
            'notifFeed'        => $notifFeed,
            'unreadNotifs'     => $unreadNotifs,
            'repartitionOrg'   => RoutingService::repartition(),
            'routing_alertes_non_traitees' => RoutingService::alertesNonTraitees(),
            'suggestions'      => $suggestions,
            'qrsActifs'        => $qrsActifs,
            'slaRetard'        => $slaRetard,
        ]);
    }

    /**
     * Espace notifications Wilaya : historique complet, paginé,
     * avec lien contextuel vers l'événement / la demande concernée.
     */
    public function notifications(): never
    {
        $this->requirePermission('evenement.view_all');

        $currentUserId = Session::userId();
        $page   = (int) input('page', 1);
        $result = Notification::center((int) $currentUserId, 20, $page);

        $this->view('wilaya/notifications', [
            'notifications' => $result['items'],
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'unread'        => Notification::unreadCount((int) $currentUserId),
        ]);
    }

    public function show(string $id): never
    {
        $this->requirePermission('evenement.view_all');
        $event = $this->find($id);

        $commune = Database::one('SELECT nom, nom_ar FROM commune WHERE id = ?', [(int) $event['commune_id']]);
        $association = Database::one('SELECT id, nom, email, telephone FROM associations WHERE id = ?', [(int) ($event['association_id'] ?? 0)]);
        $anomalies = Database::all('SELECT an.id, an.nom FROM anomalies_evenement ae JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = ? ORDER BY an.nom', [(int) $id]);
        $epics = Database::all('SELECT ep.id, ep.nom, ep.description, ee.date_affectation, ee.observation FROM evenement_epic ee JOIN epic ep ON ep.id = ee.epic_id WHERE ee.evenement_id = ? ORDER BY ee.date_affectation DESC', [(int) $id]);
        $participants = (int) Database::value('SELECT COUNT(*) FROM evenement_participant WHERE evenement_id = ?', [(int) $id]);
        $qr = Database::one('SELECT * FROM qr_event WHERE evenement_id = ? ORDER BY id DESC LIMIT 1', [(int) $id]);
        $historique = AuditLog::historiqueEvenement((int) $id);
        $transitions = Database::all('SELECT t.*, u.nom AS user_nom, u.prenom AS user_prenom
            FROM transition_history t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE t.evenement_id = ? ORDER BY t.id DESC', [(int) $id]);

        // Galerie photos
        $album = Database::one('SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1', [(int) $id]);
        $photos = [];
        if ($album !== null) {
            $photos = Database::all('SELECT * FROM photos WHERE album_id = ? ORDER BY uploaded_at DESC', [(int) $album['id']]);
        }

         $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        $this->view('wilaya.evenements.show', [
            'event'       => $event,
            'commune'     => $commune,
            'association' => $association,
            'anomalies'   => $anomalies,
            'epics'       => $epics,
            'participants' => $participants,
            'qr'          => $qr,
            'qrUrl'       => QrCodeService::getQrCodeUrl((int) $id),
            'qrStreamUrl' => QrCodeService::has((int) $id) ? url('event/qr/stream/' . (int) $id) : null,
            'qrDownloadUrl'=> QrCodeService::has((int) $id) ? url('event/qr/download/' . (int) $id) : null,
            'historique'  => $historique,
            'transitions' => $transitions,
            'statuts'     => EvenementService::STATUTS,
            'statutActuel' => $statutActuel,
            'epicsListe'  => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'album'       => $album,
            'photos'      => $photos,
            'errors'      => $this->errors(),
        ]);
    }

    public function create(): never
    {
        $this->view('wilaya.evenements.create', [
            'communes'    => Database::all('SELECT id, nom, ca_id FROM commune WHERE is_active = 1 ORDER BY nom'),
            'dairas'      => Database::all('SELECT id, nom, nom_ar FROM ca WHERE is_active = 1 ORDER BY id'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'anomalies'   => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'selectedAnomalies' => [],
            'assignedEpics' => [],
            'event'       => null,
            'errors'      => $this->errors(),
            'old'         => $_SESSION['_old'] ?? [],
        ]);
    }

    public function store(): never
    {
        $this->requirePermission('evenement.create');
        $data = all_input();
        $validator = Validator::make($data, [
            'commune_id'   => 'required|integer',
            'adresse'      => 'required|string|min:5|max:255',
            'description'  => 'required|string|min:10',
            'anomalies'    => 'required|array|distinct',
        ], ['anomalies.required' => 'Sélectionnez au moins une anomalie.']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $statut = in_array($data['statut'] ?? '', ['EN_ATTENTE', 'VALIDÉ', 'PROGRAMME'], true) ? (string) $data['statut'] : 'EN_ATTENTE';
        $eventId = EvenementService::create(
            $data,
            ! empty($data['association_id']) ? (int) $data['association_id'] : null,
            (array) ($data['anomalies'] ?? []),
            $statut
        );

        // Création directe programmée : date + EPIC + QR immédiats
        if ($statut === 'PROGRAMME' && ! empty($data['date_evenement']) && ! empty($data['epics'])) {
            EvenementService::programmer(
                $eventId,
                (string) $data['date_evenement'],
                (string) ($data['heure'] ?? '00:00:00'),
                (array) $data['epics'],
                (int) ($data['association_id'] ?? 0)
            );
        }

        flash('success', 'Événement créé par la Wilaya.');
        redirect('wilaya/evenements/' . $eventId);
    }

    public function edit(string $id): never
    {
        $this->requirePermission('evenement.edit');
        $event = $this->find($id);

        $this->view('wilaya.evenements.edit', [
            'event'       => $event,
            'communes'    => Database::all('SELECT id, nom, ca_id FROM commune WHERE is_active = 1 ORDER BY nom'),
            'dairas'      => Database::all('SELECT id, nom, nom_ar FROM ca WHERE is_active = 1 ORDER BY id'),
            'associations' => Database::all('SELECT id, nom FROM associations WHERE valide = 1 ORDER BY nom'),
            'anomalies'   => Database::all('SELECT * FROM anomalies ORDER BY nom'),
            'selectedAnomalies' => array_column(Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [(int) $id]), 'anomalie_id'),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'assignedEpics' => array_column(Database::all('SELECT epic_id FROM evenement_epic WHERE evenement_id = ?', [(int) $id]), 'epic_id'),
            'errors'      => $this->errors(),
        ]);
    }

    public function update(string $id): never
    {
        $this->requirePermission('evenement.edit');
        $event = $this->find($id);
        $data = all_input();

        $validator = Validator::make($data, [
            'commune_id'   => 'required|integer',
            'adresse'      => 'required|string|min:5|max:255',
            'description'  => 'required|string|min:10',
            'anomalies'    => 'required|array|distinct',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        EvenementService::update((int) $id, $data, $event);

        if (isset($data['epics']) && is_array($data['epics'])) {
            EvenementService::syncEpics((int) $id, (array) $data['epics']);
        }

        flash('success', 'Événement mis à jour.');
        redirect('wilaya/evenements/' . $id);
    }

    public function statut(string $id): never
    {
        $this->requirePermission('evenement.validate');
        $this->find($id);

        $statut = (string) input('statut', '');
        $motif = trim((string) input('motif', ''));

        EvenementService::changeStatut((int) $id, $statut, $motif ?: null);

        flash('success', 'Statut mis à jour : ' . $statut);
        redirect('wilaya/evenements/' . $id);
    }

    public function epics(string $id): never
    {
        $this->requirePermission('epic.assign');
        $this->find($id);

        $epics = (array) (input('epics', []) ?: []);
        EvenementService::syncEpics((int) $id, $epics);

        // Traçabilité de l'organisation assignée (première EPIC ou désassignée).
        RoutingService::reaffecter((int) $id, $epics, 'Affectation manuelle Wilaya');

        flash('success', 'EPIC affectées mises à jour.');
        redirect('wilaya/evenements/' . $id);
    }

    /**
     * Validation Wilaya + affectation multi-EPIC (bouton « Valider et affecter »).
     * Passe l'événement en VALIDÉ, lie les EPIC sélectionnées, notifie l'association
     * et chaque EPIC.
     */
    public function valider(string $id): never
    {
        $this->requirePermission('evenement.validate');
        $this->find($id);

        $epics = array_values(array_filter(array_map('intval', (array) input('epics', []))));
        $date  = input('date_evenement') !== null ? (string) input('date_evenement') : null;
        $heure = input('heure') !== null ? (string) input('heure') : null;

        EvenementService::validateEvent((int) $id, $date, $heure, $epics);

        flash('success', 'Événement validé et EPIC affectées.');
        redirect('wilaya/evenements/' . $id);
    }

    /**
     * Réaffectation manuelle d'une organisation (EPIC) — bouton "Réaffecter".
     */
    public function reaffecter(string $id): never
    {
        $this->requirePermission('epic.assign');
        $this->find($id);

        $epicIds = array_values(array_filter(array_map('intval', (array) input('epic_id', []))));
        $motif  = trim((string) input('motif', ''));

        RoutingService::reaffecter((int) $id, $epicIds, $motif);

        flash('success', $epicIds !== [] ? 'Organisation réaffectée.' : 'Organisation désassignée.');
        redirect('wilaya/evenements/' . $id);
    }

    public function regenQr(string $id): never
    {
        $this->requirePermission('qrcode.generate');
        $this->find($id);
        EvenementService::regenQr((int) $id);

        flash('success', 'QR code régénéré.');
        redirect('wilaya/evenements/' . $id);
    }

    public function archive(string $id): never
    {
        $this->requirePermission('evenement.delete');
        $this->find($id);
        EvenementService::softDelete((int) $id);

        flash('success', 'Événement archivé.');
        redirect('wilaya/evenements');
    }

    public function restore(string $id): never
    {
        $this->requirePermission('evenement.delete');
        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NOT NULL', [(int) $id]);

        if ($event === null) {
            abort(404, 'Événement introuvable');
        }

        EvenementService::restore((int) $id);
        flash('success', 'Événement restauré.');
        redirect('wilaya/evenements?deleted=1');
    }

    public function bulk(): never
    {
        $this->requirePermission('evenement.view_all');
        $action = (string) input('action', '');
        $ids = (array) (input('ids', []) ?: []);

        if (! in_array($action, ['valider', 'terminer', 'archiver', 'restaurer'], true) || $ids === []) {
            flash('error', 'Sélection invalide.');
            redirect('wilaya/evenements');
        }

        $count = EvenementService::bulk($action, $ids);
        flash('success', $count . ' événement(s) traité(s).');
        redirect('wilaya/evenements');
    }

    public function export(): never
    {
        [$sql, $params] = EvenementService::queryFiltres([
            'q'          => trim((string) input('q', '')),
            'statut'     => input('statut'),
            'commune_id' => input('commune_id'),
            'epic_id'    => input('epic_id'),
            'anomalie_id' => input('anomalie_id'),
        ]);
        $sql = preg_replace(
            '/^SELECT.*? FROM evenements e/s',
            'SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure, c.nom AS commune, a.nom AS association FROM evenements e',
            $sql,
            1
        );

        $rows = Database::all($sql, $params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evenements.csv"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Adresse', 'Description', 'Statut', 'Date', 'Heure', 'Commune', 'Association'], ',', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($out, array_map(
                static fn ($v) => is_string($v) ? str_replace(["\r", "\n"], ' ', $v) : $v,
                array_values($row)
            ), ',', '"', '\\');
        }

        fclose($out);
        exit;
    }

    private function find(string $id): array
    {
        $event = Database::one('SELECT * FROM evenements WHERE id = ?', [(int) $id]);

        if ($event === null) {
            abort(404, 'Événement introuvable');
        }

        return $event;
    }
}
