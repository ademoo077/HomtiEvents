<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Rbac;
use App\Helpers\Validator;

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

        $this->view('association/create', [
            'communes'    => Database::all(
                'SELECT c.id, c.nom, c.ca_id, ca.nom AS daira_nom
                 FROM commune c
                 LEFT JOIN ca ca ON ca.id = c.ca_id
                 WHERE c.is_active = 1
                 ORDER BY ca.nom, c.nom'
            ),
            'anomalies'       => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'anomaliesParEpic'=> $this->anomaliesParEpic(),
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
                'SELECT c.id, c.nom, c.ca_id, ca.nom AS daira_nom
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

        EvenementService::update((int) $id, $data, $event);

        flash('success', 'Événement mis à jour.');
        redirect('association');
    }

    /**
     * Regroupe les anomalies par EPIC (une anomalie n'apparaît qu'une fois,
     * dans son premier EPIC rencontré ; les anomalies sans EPIC sont isolées).
     *
     * @return array<int, array{epic_nom: ?string, items: array<int, array{id:int,nom:string,icone:string,couleur:string}>}>
     */
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
     * Compteurs d'événements par statut pour une association donnée.
     *
     * @return array<string, int>
     */
    private function statutsCounts(int $associationId): array
    {
        return EvenementService::statutsCounts($associationId);
    }
}
