<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\StatsService;
use App\Helpers\I18n;
use App\Helpers\Notification;
use App\Helpers\QrCodeService;
use App\Helpers\Rbac;
use App\Helpers\RoutingService;
use App\Helpers\Validator;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Helpers\UploadHelper;

/**
 * Espace association — création et suivi des événements par une association.
 */
final class AssociationController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * Statuts modifiables par l'association (avant validation / programmation).
     */
    private const STATUTS_EDITABLES = ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'];

    public function index(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            flash('error', __('auth.association_pending'));
            redirect(dashboard_path());
        }

        $association = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);

        $filters = [
            'q'      => trim((string) input('q', '')),
            'statut' => input('statut'),
            'du'     => input('du'),
            'au'     => input('au'),
        ];

        [$sql, $params] = EvenementService::queryFiltres(array_merge($filters, [
            'association_id' => $associationId,
        ]));

        $page   = (int) input('page', 1);
        $result = Database::paginate($sql, $params, self::PER_PAGE, $page);

        $this->view('association/index', [
            'association'   => $association,
            'evenements'    => $result['items'],
            'filters'       => $filters,
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'statutsCounts' => $this->statutsCounts($associationId),
            'communes'      => Database::all(
                'SELECT c.id, c.nom, c.ca_id, ca.nom AS daira_nom
                 FROM commune c
                 LEFT JOIN ca ca ON ca.id = c.ca_id
                 WHERE c.is_active = 1
                 ORDER BY ca.nom, c.nom'
            ),
            'anomalies'     => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'epics'         => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'score'         => StatsService::associationScore($associationId),
        ], 'association'); // Association layout
    }

    /**
     * Suivi de la demande d'inscription (statut, agrément joint, motif de refus).
     */
    public function demande(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $request = Database::one(
            'SELECT * FROM association_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $user['id']]
        );

        $association = null;
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId > 0) {
            $association = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);
        }

        $this->view('association/demande', [
            'request'     => $request,
            'association' => $association,
        ], 'association'); // Association layout
    }

    public function create(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);

        $this->view('association/create', [
            'association'     => $associationId > 0 ? Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]) : null,
            'communes'    => Database::all(
                'SELECT c.id, c.nom, c.ca_id, c.latitude, c.longitude, ca.nom AS daira_nom
                 FROM commune c
                 LEFT JOIN ca ca ON ca.id = c.ca_id
                 WHERE c.is_active = 1
                 ORDER BY ca.nom, c.nom'
            ),
            'anomalies'       => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'anomaliesParEpic' => $this->anomaliesParEpic(),
            'epics'           => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'errors'          => $this->errors(),
            'old'             => $_SESSION['_old'] ?? [],
        ], 'association'); // Association layout
    }

    public function store(): never
    {
        $this->requireAuth();
        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        if ($associationId === 0) {
            flash('error', __('auth.association_pending'));
            redirect('association');
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'commune_id'   => 'required|integer',
            'adresse'      => 'required|string|min:5|max:255',
            'description'  => 'required|string|min:10',
            'anomalies'    => 'required|array|distinct',
            'capacite'     => 'nullable|integer|between:1,100000',
        ], ['anomalies.required' => 'Sélectionnez au moins une anomalie.']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        // Une association ne peut jamais fixer la date / l'heure de son événement :
        // elles sont uniquement déterminées par la Wilaya lors de la programmation.
        unset($data['date_evenement'], $data['heure'], $data['epics']);

        EvenementService::create($data, $associationId, (array) ($data['anomalies'] ?? []));

        flash('success', __('evenements.create_success'));
        redirect('association');
    }

    /**
     * Cloner un événement existant : pré-remplit le formulaire de création
     * avec les données de l'événement source (commune, adresse, description, anomalies).
     */
    public function clone(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        $source = Database::one(
            'SELECT * FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($source === null) {
            abort(404, 'Événement source introuvable.');
        }

        $selectedAnomalies = array_column(
            Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [(int) $id]),
            'anomalie_id'
        );

        $this->view('association/create', [
            'association'     => Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]),
            'communes'        => Database::all(
                'SELECT c.id, c.nom, c.ca_id, c.latitude, c.longitude, ca.nom AS daira_nom
                 FROM commune c LEFT JOIN ca ca ON ca.id = c.ca_id
                 WHERE c.is_active = 1 ORDER BY ca.nom, c.nom'
            ),
            'anomalies'       => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'anomaliesParEpic' => $this->anomaliesParEpic(),
            'epics'           => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'errors'          => [],
            'old'             => [
                'commune_id'  => $source['commune_id'],
                'adresse'     => $source['adresse'],
                'description' => $source['description'],
                'capacite'    => $source['capacite'] ?? '',
                'anomalies'   => $selectedAnomalies,
            ],
            'clonedFrom'      => (int) $id,
        ], 'association');
    }

    /**
     * Export iCal (.ics) d'un événement.
     */
    public function ical(string $id): void
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.id = ? AND e.association_id = ? AND e.deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $this->sendIcal($event);
    }

    /**
     * Export iCal depuis la page publique (sans auth).
     */
    public function icalPublic(string $id): void
    {
        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.id = ? AND e.deleted_at IS NULL',
            [(int) $id]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $this->sendIcal($event);
    }

    /**
     * Génère et envoie le fichier .ics pour un événement.
     */
    private function sendIcal(array $event): void
    {
        $dtStart = ! empty($event['date_evenement'])
            ? date('Ymd\THis', strtotime((string) $event['date_evenement'] . ' ' . substr((string) ($event['heure'] ?? '00:00'), 0, 5)))
            : date('Ymd\THis');
        $dtEnd = $dtStart;
        if (! empty($event['date_evenement']) && ! empty($event['heure'])) {
            $dtEnd = date('Ymd\THis', strtotime((string) $event['date_evenement'] . ' ' . substr((string) $event['heure'], 0, 5) . ' +2 hours'));
        }

        $summary = ($event['adresse'] ?? 'Événement') . ' — ' . ($event['association_nom'] ?? '');
        $location = ($event['adresse'] ?? '') . ', ' . ($event['commune_nom'] ?? '') . ', Alger';
        $description = $event['description'] ?? '';
        $uid = 'event-' . (int) $event['id'] . '@wilaya-harmonia.dz';
        $now = date('Ymd\THis');

        $ical = "BEGIN:VCALENDAR\r\n"
            . "VERSION:2.0\r\n"
            . "PRODID:-//Wilaya Harmonia//Event//FR\r\n"
            . "CALSCALE:GREGORIAN\r\n"
            . "METHOD:PUBLISH\r\n"
            . "BEGIN:VEVENT\r\n"
            . "UID:{$uid}\r\n"
            . "DTSTAMP:{$now}\r\n"
            . "DTSTART:{$dtStart}\r\n"
            . "DTEND:{$dtEnd}\r\n"
            . "SUMMARY:" . $this->icalEscape($summary) . "\r\n"
            . "LOCATION:" . $this->icalEscape($location) . "\r\n"
            . "DESCRIPTION:" . $this->icalEscape($description) . "\r\n"
            . "STATUS:" . strtoupper((string) ($event['statut'] ?? 'TENTATIVE')) . "\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR";

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="evenement-' . (int) $event['id'] . '.ics"');
        header('Cache-Control: no-cache, must-revalidate');
        echo $ical;
        exit;
    }

    private function icalEscape(string $text): string
    {
        $text = str_replace(["\\", ";", ","], ["\\\\", "\\;", "\\,"], $text);
        return str_replace("\n", "\\n", $text);
    }

    public function show(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.id = ? AND e.association_id = ? AND e.deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $eventId = (int) $event['id'];

        $this->view('association/show', [
            'event'        => $event,
            'qrStreamUrl'  => QrCodeService::has($eventId) ? url('event/qr/stream/' . $eventId) : null,
            'qrDownloadUrl' => QrCodeService::has($eventId) ? url('event/qr/download/' . $eventId) : null,
            'qrShareUrl'   => QrCodeService::getQrCodeUrl($eventId),
            'participants' => Database::all(
                'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, ep.heure_scan
                 FROM evenement_participant ep
                 JOIN users u ON u.id = ep.user_id
                 WHERE ep.evenement_id = ?
                 ORDER BY ep.heure_scan ASC',
                [$eventId]
            ),
            'album' => Database::one(
                'SELECT a.*,
                        (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS nb_photos
                 FROM albums a
                 WHERE a.evenement_id = ? ORDER BY a.id DESC LIMIT 1',
                [$eventId]
            ),
            'evaluation' => Database::one(
                'SELECT * FROM evaluation WHERE evenement_id = ? AND association_id = ?',
                [$eventId, $associationId]
            ),
            'historique' => Database::all(
                'SELECT * FROM transition_history
                 WHERE evenement_id = ?
                 ORDER BY created_at DESC, id DESC',
                [$eventId]
            ),
            'errors'     => $this->errors(),
            'old'        => $_SESSION['_old'] ?? [],
            'score'      => StatsService::associationScore($associationId),
        ], 'association'); // Explicit association layout
    }

    /**
     * Évaluation (note 1–5 + commentaire) par l'association après l'événement.
     * Possible uniquement quand l'événement est TERMINE (une seule évaluation).
     */
    public function evaluate(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $event = Database::one(
            'SELECT * FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        if (($event['statut'] ?? '') !== 'TERMINE') {
            flash('error', __('associations.evaluate_not_termine'));
            redirect('association/' . (int) $id);
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'note'        => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $existing = Database::one(
            'SELECT id FROM evaluation WHERE evenement_id = ? AND association_id = ?',
            [(int) $id, $associationId]
        );

        if ($existing !== null) {
            flash('error', __('associations.evaluate_already'));
            redirect('association/' . (int) $id);
        }

        Database::insert('evaluation', [
            'evenement_id'   => (int) $id,
            'association_id' => $associationId,
            'note'           => (int) $data['note'],
            'description'    => trim((string) ($data['description'] ?? '')),
        ]);

        \App\Helpers\AuditLog::log('evenement_evaluation', 'evenement', (int) $id, null, [
            'note' => (int) $data['note'],
        ]);

        flash('success', __('associations.evaluate_success'));
        redirect('association/' . (int) $id);
    }

    public function edit(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $event = Database::one(
            'SELECT * FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $this->view('association/edit', [
            'event'       => $event,
            'communes'    => Database::all(
                'SELECT c.id, c.nom, c.ca_id, c.latitude, c.longitude, ca.nom AS daira_nom
                 FROM commune c
                 LEFT JOIN ca ca ON ca.id = c.ca_id
                 WHERE c.is_active = 1
                 ORDER BY ca.nom, c.nom'
            ),
            'anomalies'   => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'anomaliesParEpic' => $this->anomaliesParEpic(),
            'epics'       => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'selectedAnomalies' => array_column(
                Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [(int) $id]),
                'anomalie_id'
            ),
            'assignedEpics' => array_column(
                Database::all('SELECT epic_id FROM evenement_epic WHERE evenement_id = ?', [(int) $id]),
                'epic_id'
            ),
            'anomalyDetails' => Database::all(
                'SELECT ae.*, an.nom AS anomalie_nom FROM anomalies_evenement ae JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = ?',
                [(int) $id]
            ),
            'assignments' => Database::all(
                'SELECT aa.*, an.nom AS anomalie_nom, ep.nom AS epic_nom FROM anomaly_assignments aa JOIN anomalies an ON an.id = aa.anomalie_id JOIN epic ep ON ep.id = aa.epic_id WHERE aa.evenement_id = ?',
                [(int) $id]
            ),
            'errors'      => $this->errors(),
        ], 'association'); // Association layout
    }

    public function update(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $event = Database::one(
            'SELECT * FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        // L'association ne peut modifier un événement qu'avant sa validation
        // (EN_ATTENTE / MODIFICATION_DEMANDEE / REFUSE). Une fois programmé
        // (date fixée, EPIC affectées), l'événement appartient au circuit Wilaya.
        if (! in_array((string) ($event['statut'] ?? 'EN_ATTENTE'), self::STATUTS_EDITABLES, true)) {
            flash('error', __('associations.event_locked'));
            redirect('association/' . (int) $id);
        }

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

        // Idem création : jamais de date/heure/EPIC par l'association.
        unset($data['date_evenement'], $data['heure'], $data['epics']);

        $statutAvant = (string) ($event['statut'] ?? 'EN_ATTENTE');

        EvenementService::update((int) $id, $data, $event);

        // Re-soumission : transition vers EN_ATTENTE + notifie la Wilaya.
        if (in_array($statutAvant, ['REFUSE', 'MODIFICATION_DEMANDEE'], true)) {
            EvenementService::resoumettre((int) $id);
        }

        flash('success', 'Événement mis à jour.');
        redirect('association');
    }

    /**
     * Regroupe les anomalies par EPIC (une anomalie n'apparaît qu'une fois,
     * dans son premier EPIC rencontré ; les anomalies sans EPIC sont isolées).
     *
     * @return array<int, array{epic_nom: ?string, items: array<int, array{id:int,nom:string,icone:string,couleur:string}>}>
     */
    public function routingPreview(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $communeId = (int) (all_input()['commune_id'] ?? 0);
        $anomalies = all_input()['anomalies'] ?? [];
        $anomalies = is_array($anomalies) ? array_map('intval', $anomalies) : [];

        $result = RoutingService::preview($communeId, $anomalies);

        $epic = null;
        if ($result['epic_id'] !== null) {
            $epic = Database::one('SELECT id, nom FROM epic WHERE id = ?', [$result['epic_id']]);
        }

        json_response([
            'success' => true,
            'epic'    => $epic,
            'matched' => $result['rule_matched'],
            'detail'  => $result['detail'],
        ]);
    }

    private function anomaliesParEpic(): array
    {
        $rows = Database::all(
            'SELECT a.id, a.nom, a.icone, a.couleur, e.id AS epic_id, e.nom AS epic_nom
             FROM anomalies a
             JOIN epic_anomalies ea ON ea.anomalie_id = a.id
             JOIN epic e ON e.id = ea.epic_id
             ORDER BY e.nom, a.nom'
        );

        $groups = [];
        $seen = [];
        foreach ($rows as $row) {
            $anomalieId = (int) $row['id'];
            if (isset($seen[$anomalieId])) {
                continue;
            }
            $seen[$anomalieId] = true;

            $epicId = (int) $row['epic_id'];
            if (! isset($groups[$epicId])) {
                $groups[$epicId] = ['epic_nom' => (string) $row['epic_nom'], 'items' => []];
            }
            $groups[$epicId]['items'][] = [
                'id'      => $anomalieId,
                'nom'     => (string) $row['nom'],
                'icone'   => (string) ($row['icone'] ?? ''),
                'couleur' => (string) ($row['couleur'] ?? ''),
            ];
        }

        $orphans = Database::all(
            'SELECT id, nom, icone, couleur FROM anomalies a
             WHERE NOT EXISTS (SELECT 1 FROM epic_anomalies ea WHERE ea.anomalie_id = a.id)
             ORDER BY nom'
        );
        if ($orphans !== []) {
            $groups[0] = [
                'epic_nom' => null,
                'items'    => array_map(
                    static fn (array $o): array => [
                        'id'      => (int) $o['id'],
                        'nom'     => (string) $o['nom'],
                        'icone'   => (string) ($o['icone'] ?? ''),
                        'couleur' => (string) ($o['couleur'] ?? ''),
                    ],
                    $orphans
                ),
            ];
        }

        return array_values($groups);
    }

    /**
     * Page de scan QR pour les membres de l'association.
     * Affiche les événements de l'association avec QR code actif.
     */
    public function scan(): never
    {
        $this->requireAuth();
        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $evenements = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut, e.heure,
                    q.token_qr, q.date_expiration
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.deleted_at IS NULL
               AND e.association_id = ?
               AND e.statut IN (\'PROGRAMME\', \'QR_GENERE\', \'EN_COURS\')
               AND q.token_qr IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 20',
            [$associationId]
        );

        $this->view('qrcode/scan_optimized', [
            'evenements' => $evenements,
            'associationScan' => true,
        ], 'association');
    }

    /**
     * Compteurs d'événements par statut pour une association donnée.
     *
     * @return array<string, int>
     */
    private function statutsCounts(int $associationId): array
    {
        return EvenementService::statutsCounts($associationId);
    }

    /**
     * Formulaire d'édition de la demande d'inscription (après refus ou demande de modification).
     */
    public function editDemande(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $request = Database::one(
            'SELECT * FROM association_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $user['id']]
        );

        if ($request === null) {
            abort(404, 'Aucune demande d\'inscription trouvée.');
        }

        $status = (string) ($request['status'] ?? '');
        if (! in_array($status, ['rejected', 'modification_requested'], true)) {
            flash('error', 'Vous ne pouvez modifier que les demandes refusées ou en attente de modification.');
            redirect('association/demande');
        }

        $this->view('association/edit-demande', [
            'request' => $request,
            'errors'  => $this->errors(),
            'old'     => $_SESSION['_old'] ?? [],
        ], 'association');
    }

    /**
     * Enregistre les modifications de la demande d'inscription et la resoumet.
     */
    public function updateDemande(): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $request = Database::one(
            'SELECT * FROM association_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $user['id']]
        );

        if ($request === null) {
            abort(404, 'Aucune demande d\'inscription trouvée.');
        }

        $status = (string) ($request['status'] ?? '');
        if (! in_array($status, ['rejected', 'modification_requested'], true)) {
            flash('error', 'Vous ne pouvez modifier que les demandes refusées ou en attente de modification.');
            redirect('association/demande');
        }

        $data = all_input();

        // Les champs nullable vides doivent être null
        foreach (['approval_number', 'activity_domain', 'description', 'address', 'commune', 'wilaya', 'website', 'president_birthdate', 'president_phone', 'president_email', 'president_address', 'president_id_type', 'president_id_number'] as $champ) {
            $data[$champ] = trim((string) ($data[$champ] ?? '')) ?: null;
        }
        $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? ''))) ?: null;
        $data['phone'] = trim((string) ($data['phone'] ?? '')) ?: null;

        $validator = Validator::make($data, [
            'association_name'    => 'required|string|max:150',
            'approval_number'     => 'nullable|string|max:50',
            'activity_domain'     => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:2000',
            'address'             => 'nullable|string|max:255',
            'commune'             => 'nullable|string|max:100',
            'wilaya'              => 'nullable|string|max:100',
            'phone'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:100',
            'website'             => 'nullable|string|max:255',
            'president_lastname'  => 'required|string|max:100',
            'president_firstname' => 'required|string|max:100',
            'president_birthdate' => 'nullable|date',
            'president_phone'     => 'nullable|string|max:20',
            'president_email'     => 'nullable|email|max:100',
            'president_address'   => 'nullable|string|max:255',
            'president_id_type'   => 'nullable|string|max:50',
            'president_id_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $approvalFile = $request['approval_file'];
        if (! empty($_FILES['approval_file']['name']) && $_FILES['approval_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = config('paths.uploads.agrements', public_path('uploads/agrements'));
            $result    = UploadHelper::uploadDocument($_FILES['approval_file'], $uploadDir, (int) config('security.upload_max', 5242880));
            if (! $result['success']) {
                $this->backWithErrors(['approval_file' => $result['error']], $data);
            }
            $approvalFile = $result['path'];
        }

        Database::update('association_requests', [
            'association_name'       => trim((string) $data['association_name']),
            'approval_number'        => $data['approval_number'],
            'activity_domain'        => $data['activity_domain'],
            'description'            => $data['description'],
            'address'                => $data['address'],
            'commune'                => $data['commune'],
            'wilaya'                 => $data['wilaya'],
            'phone'                  => $data['phone'],
            'email'                  => $data['email'],
            'website'                => $data['website'],
            'president_lastname'     => trim((string) $data['president_lastname']),
            'president_firstname'    => trim((string) $data['president_firstname']),
            'president_birthdate'    => $data['president_birthdate'],
            'president_phone'        => $data['president_phone'],
            'president_email'        => $data['president_email'],
            'president_address'      => $data['president_address'],
            'president_id_type'      => $data['president_id_type'],
            'president_id_number'    => $data['president_id_number'],
            'approval_file'          => $approvalFile,
            'status'                 => 'pending',
            'rejection_reason'       => null,
            'modification_reason'    => null,
            'modification_requested_at' => null,
        ], 'id = ?', [(int) $request['id']]);

        // Supprime l'ancien fichier agrément si remplacé
        if ($approvalFile !== $request['approval_file'] && str_starts_with((string) ($request['approval_file'] ?? ''), '/uploads/')) {
            UploadHelper::delete((string) $request['approval_file']);
        }

        AuditLog::log('association_request_resubmitted', 'association_requests', (int) $request['id'], null, [
            'association_name' => trim((string) $data['association_name']),
        ]);

        // Notifier la Wilaya
        Notification::sendToRole('wilaya', 'Demande resoumise', 'L\'association « ' . trim((string) $data['association_name']) . ' » a resoumis sa demande d\'inscription après correction.', 'association_request_resubmitted', [
            'request_id' => (int) $request['id'],
        ]);

        flash('success', 'Votre demande a été resoumise et est en attente de traitement.');
        redirect('association/demande');
    }
}
