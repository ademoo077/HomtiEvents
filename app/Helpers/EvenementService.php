<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Service métier centralisé des événements.
 *
 * Centralise toute la logique métier (création, workflow, EPIC, anomalies,
 * suppression) afin que les contrôleurs restent fins et sans duplication.
 */
final class EvenementService
{
    public const STATUTS = [
        'EN_ATTENTE',
        'MODIFICATION_DEMANDEE',
        'VALIDÉ',
        'PROGRAMME',
        'QR_GENERE',
        'EN_COURS',
        'TERMINE',
        'REFUSE',
        'ANNULE',
    ];

    /** Statuts considérés comme « validés » (pour les compteurs dashboard). */
    public const STATUTS_VALIDES = ['VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'];

    /** Statuts considérés comme « en attente » (pour les compteurs dashboard). */
    public const STATUTS_EN_ATTENTE = ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'];

    /** Statuts pour les événements « à venir » ou « en cours » (côté citoyen). */
    public const STATUTS_A_VENIR = ['PROGRAMME', 'QR_GENERE', 'EN_COURS'];

    /** Délai (jours) après la date de l'événement avant la clôture automatique (TERMINE). */
    public const DELAI_CLOTURE_JOURS = 1;

    /**
     * Transitions autorisées dans la machine à états stricte.
     *
     * Cycle de vie complet :
     *   EN_ATTENTE ──→ VALIDÉ ──→ PROGRAMME ──→ QR_GENERE ──→ EN_COURS ──→ TERMINE
     *       │  │  └→ MODIFICATION_DEMANDEE ──→ EN_ATTENTE (re-soumission association)
     *       │  └────→ REFUSE ──→ EN_ATTENTE / MODIFICATION_DEMANDEE (re-soumission)
     *       └───────→ PROGRAMME (programmation directe)
     *   VALIDÉ / PROGRAMME / QR_GENERE peuvent être rejoués ou re-programmés.
     */
    private const TRANSITIONS_AUTORISEES = [
        'EN_ATTENTE'            => ['MODIFICATION_DEMANDEE', 'VALIDÉ', 'PROGRAMME', 'REFUSE', 'ANNULE'],
        'MODIFICATION_DEMANDEE' => ['EN_ATTENTE', 'REFUSE', 'ANNULE'],
        'VALIDÉ'                => ['PROGRAMME', 'MODIFICATION_DEMANDEE', 'REFUSE'],
        'PROGRAMME'             => ['QR_GENERE', 'EN_COURS', 'TERMINE'],
        'QR_GENERE'             => ['EN_COURS', 'PROGRAMME', 'TERMINE'],
        'EN_COURS'              => ['TERMINE', 'EN_ATTENTE'],
        'TERMINE'               => ['EN_ATTENTE'],
        'REFUSE'                => ['EN_ATTENTE', 'MODIFICATION_DEMANDEE'],
    ];

    /**
     * Construit la requête de listing avec filtres combinables.
     *
     * Filtres possibles : q (recherche), statut, commune_id, association_id,
     * epic_id, anomalie_id, du, au, supprimés.
     *
     * @param array<string, mixed> $f
     * @return array{0: string, 1: array<int, mixed>}
     */
    public static function queryFiltres(array $f = []): array
    {
        $sql = 'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom,
                       a.email AS association_email, a.telephone AS association_telephone,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants,
                       (SELECT COUNT(*) FROM anomalies_evenement ae WHERE ae.evenement_id = e.id) AS nb_anomalies,
                       (SELECT GROUP_CONCAT(ep.nom SEPARATOR ", ") FROM epic ep
                         JOIN evenement_epic ee ON ee.epic_id = ep.id WHERE ee.evenement_id = e.id) AS epics_noms
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id';
        $where = [];
        $params = [];

        if (! empty($f['deleted'])) {
            $where[] = 'e.deleted_at IS NOT NULL';
        } else {
            $where[] = 'e.deleted_at IS NULL';
        }

        if (! empty($f['statut']) && in_array($f['statut'], self::STATUTS, true)) {
            $where[] = 'e.statut = ?';
            $params[] = $f['statut'];
        }

        if (! empty($f['q'])) {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ? OR e.informations_complementaires LIKE ? OR a.nom LIKE ?)';
            $like = '%' . trim((string) $f['q']) . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if (! empty($f['commune_id'])) {
            $where[] = 'e.commune_id = ?';
            $params[] = (int) $f['commune_id'];
        }

        if (! empty($f['association_id'])) {
            $where[] = 'e.association_id = ?';
            $params[] = (int) $f['association_id'];
        }

        if (! empty($f['epic_id'])) {
            $where[] = 'e.id IN (SELECT evenement_id FROM evenement_epic WHERE epic_id = ?)';
            $params[] = (int) $f['epic_id'];
        }

        if (! empty($f['assigned_org_id'])) {
            $where[] = 'e.assigned_org_id = ?';
            $params[] = (int) $f['assigned_org_id'];
        }

        if (! empty($f['anomalie_id'])) {
            $where[] = 'e.id IN (SELECT evenement_id FROM anomalies_evenement WHERE anomalie_id = ?)';
            $params[] = (int) $f['anomalie_id'];
        }

        if (! empty($f['du'])) {
            $where[] = 'e.date_evenement >= ?';
            $params[] = (string) $f['du'];
        }

        if (! empty($f['au'])) {
            $where[] = 'e.date_evenement <= ?';
            $params[] = (string) $f['au'];
        }

        if (! empty($f['sans_epic'])) {
            $where[] = 'NOT EXISTS (SELECT 1 FROM evenement_epic ee WHERE ee.evenement_id = e.id)';
        }

        if (! empty($f['retard'])) {
            $where[] = "e.statut = 'EN_ATTENTE' AND e.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Tri serveur sécurisé : colonnes en liste blanche uniquement.
        $allow = [
            'created_at'    => 'e.created_at',
            'date_evenement' => 'e.date_evenement',
            'adresse'       => 'e.adresse',
            'statut'        => 'e.statut',
            'commune'       => 'c.nom',
            'association'   => 'a.nom',
            'id'            => 'e.id',
        ];
        $col = $allow[$f['sort'] ?? 'created_at'] ?? 'e.created_at';
        $dir = (($f['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . $col . ' ' . $dir . ', e.id DESC';

        return [$sql, $params];
    }

    /**
     * Compteurs d'événements par statut (tous, ou restreints à une association).
     *
     * @return array<string, int>
     */
    public static function statutsCounts(int $associationId = 0): array
    {
        $sql = 'SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL';
        $params = [];
        if ($associationId > 0) {
            $sql .= ' AND association_id = ?';
            $params[] = $associationId;
        }
        $sql .= ' GROUP BY statut';

        $counts = array_fill_keys(self::STATUTS, 0);
        foreach (Database::all($sql, $params) as $row) {
            $statut = (string) $row['statut'];
            if (isset($counts[$statut])) {
                $counts[$statut] = (int) $row['nb'];
            }
        }

        return $counts;
    }

    /**
     * Événements « à venir » ou « en cours » affichés au citoyen.
     *
     * Filtre sur STATUTS_A_VENIR et exclut les événements archivés
     * (deleted_at IS NULL). Les statuts proviennent de constantes internes.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function evenementsAVenirPourCitoyen(): array
    {
        $in = implode(',', array_map(static fn (string $s): string => "'{$s}'", self::STATUTS_A_VENIR));

        return Database::all(
            "SELECT e.*, c.nom AS commune_nom FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.statut IN ({$in}) AND e.date_evenement >= CURDATE() AND e.deleted_at IS NULL
             ORDER BY e.date_evenement ASC"
        );
    }

    /**
     * Derniers événements terminés affichés au citoyen (exclut les archivés).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function evenementsPassesPourCitoyen(int $limit = 20): array
    {
        return Database::all(
            'SELECT e.*, c.nom AS commune_nom,
                    a.id AS assoc_id, a.nom AS assoc_nom, a.numero_agrement AS assoc_agrement, a.valide AS assoc_valide
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.statut = ? AND e.date_evenement < CURDATE() AND e.deleted_at IS NULL
             ORDER BY e.date_evenement DESC LIMIT ' . max(1, (int) $limit),
            ['TERMINE']
        );
    }

    /**
     * Recommandations d'événements simples pour un citoyen.
     *
     * S'appuie sur l'historique de participation (communes, associations et
     * types d'anomalies fréquentés) pour suggérer des événements à venir que le
     * citoyen n'a pas encore rejoints. Chaque suggestion porte une raison
     * courte ('commune', 'association', 'theme', 'populaire') affichable.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recommandationsPourCitoyen(int $userId, int $limit = 6): array
    {
        $limit = max(1, min(12, (int) $limit));

        // Communes les plus fréquentées par le citoyen (au moins 1 participation).
        $communes = Database::all(
            'SELECT e.commune_id, COUNT(*) AS nb
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id
             WHERE ep.user_id = ? AND e.commune_id IS NOT NULL
             GROUP BY e.commune_id ORDER BY nb DESC LIMIT 5',
            [$userId]
        );
        // Associations les plus fréquentées.
        $associations = Database::all(
            'SELECT e.association_id, COUNT(*) AS nb
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id
             WHERE ep.user_id = ? AND e.association_id IS NOT NULL
             GROUP BY e.association_id ORDER BY nb DESC LIMIT 5',
            [$userId]
        );
        // Types d'anomalies (thèmes) les plus fréquentés.
        $themes = Database::all(
            'SELECT ae.anomalie_id, COUNT(*) AS nb
             FROM evenement_participant ep
             JOIN anomalies_evenement ae ON ae.evenement_id = ep.evenement_id
             WHERE ep.user_id = ?
             GROUP BY ae.anomalie_id ORDER BY nb DESC LIMIT 5',
            [$userId]
        );

        $idsCommune     = array_map(static fn (array $r): int => (int) $r['commune_id'], $communes);
        $idsAssociation = array_map(static fn (array $r): int => (int) $r['association_id'], $associations);
        $idsTheme       = array_map(static fn (array $r): int => (int) $r['anomalie_id'], $themes);

        // Événements déjà rejoints (à exclure).
        $participated = array_map(
            static fn (array $r): int => (int) $r['evenement_id'],
            Database::all('SELECT evenement_id FROM evenement_participant WHERE user_id = ?', [$userId])
        );

        $in = implode(',', array_map(static fn (string $s): string => "'{$s}'", self::STATUTS_A_VENIR));
        $where = ["e.statut IN ({$in})", 'e.date_evenement >= CURDATE()', 'e.deleted_at IS NULL'];
        $params = [];

        if ($participated !== []) {
            $where[] = 'e.id NOT IN (' . implode(',', array_fill(0, count($participated), '?')) . ')';
            foreach ($participated as $id) {
                $params[] = $id;
            }
        }

        $rows = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.heure, e.commune_id, e.association_id,
                    c.nom AS commune_nom,
                    (SELECT GROUP_CONCAT(an.nom SEPARATOR ", ") FROM anomalies_evenement ae
                     JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = e.id) AS anomalies
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY e.date_evenement ASC
             LIMIT 60',
            $params
        );

        if ($rows === []) {
            return [];
        }

        $setCommune     = array_flip($idsCommune);
        $setAssociation = array_flip($idsAssociation);
        $setTheme       = array_flip($idsTheme);
        $themeOfEvent   = [];

        // Associer les anomalies de chaque événement candidat à ses id (une requête).
        $candidateIds = array_map(static fn (array $ev): int => (int) $ev['id'], $rows);
        $themeRows = Database::all(
            'SELECT ae.evenement_id, ae.anomalie_id
             FROM anomalies_evenement ae
             WHERE ae.evenement_id IN (' . implode(',', array_fill(0, count($candidateIds), '?')) . ')',
            $candidateIds
        );
        foreach ($themeRows as $t) {
            $eid = (int) $t['evenement_id'];
            $themeOfEvent[$eid][(int) $t['anomalie_id']] = true;
        }

        $scored = [];
        foreach ($rows as $ev) {
            $id = (int) $ev['id'];
            $score = 0;
            $reason = 'populaire';

            if ($ev['commune_id'] !== null && isset($setCommune[(int) $ev['commune_id']])) {
                $score += 30;
                $reason = 'commune';
            }
            if ($ev['association_id'] !== null && isset($setAssociation[(int) $ev['association_id']])) {
                $score += 25;
                if ($reason === 'populaire') {
                    $reason = 'association';
                }
            }
            $evThemes = $themeOfEvent[$id] ?? [];
            foreach ($evThemes as $tid => $_) {
                if (isset($setTheme[$tid])) {
                    $score += 20;
                    if ($reason === 'populaire' || $reason === 'commune') {
                        $reason = 'theme';
                    }
                }
            }

            $scored[] = [
                'id'             => $id,
                'adresse'        => (string) ($ev['adresse'] ?? ''),
                'date_evenement' => (string) ($ev['date_evenement'] ?? ''),
                'heure'          => (string) ($ev['heure'] ?? ''),
                'commune_nom'    => (string) ($ev['commune_nom'] ?? ''),
                'anomalies'      => (string) ($ev['anomalies'] ?? ''),
                'raison'         => $reason,
                'score'          => $score,
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    /**
     * Crée un événement (association ou opération directe Wilaya).
     *
     * @param array<string, mixed> $d
     * @param array<int>           $anomalieIds
     */
    public static function create(array $d, ?int $associationId, array $anomalieIds = [], string $statut = 'EN_ATTENTE'): int
    {
        $data = [
            'commune_id'                  => (int) ($d['commune_id'] ?? 0) ?: null,
            'adresse'                     => trim((string) ($d['adresse'] ?? '')),
            'association_id'              => $associationId,
            'description'                 => trim((string) ($d['description'] ?? '')),
            'informations_complementaires' => trim((string) ($d['informations'] ?? '')),
            'statut'                      => $statut,
            'date_evenement'              => ! empty($d['date_evenement']) ? (string) $d['date_evenement'] : null,
            'heure'                       => ! empty($d['heure']) ? (string) $d['heure'] : null,
            'capacite'                    => isset($d['capacite']) && $d['capacite'] !== '' ? max(1, (int) $d['capacite']) : null,
            'motif_refus'                 => null,
            'deadline_at'                 => date('Y-m-d H:i:s', time() + 5 * 86400),
        ];

        if (isset($d['start_at']) && $d['start_at'] !== '') {
            $data['start_at'] = (string) $d['start_at'];
        } elseif (! empty($d['date_evenement'])) {
            $data['start_at'] = (string) $d['date_evenement'] . ' ' . (! empty($d['heure']) ? (string) $d['heure'] : '09:00:00');
        }
        if (isset($d['end_at']) && $d['end_at'] !== '') {
            $data['end_at'] = (string) $d['end_at'];
        }

        if (isset($d['latitude']) && $d['latitude'] !== '') {
            $data['latitude'] = (float) $d['latitude'];
        }
        if (isset($d['longitude']) && $d['longitude'] !== '') {
            $data['longitude'] = (float) $d['longitude'];
        }

        // Auto-fill GPS from commune if not set (association may not have map)
        if (($data['latitude'] ?? null) === null && ($data['commune_id'] ?? null) !== null) {
            $communeGps = Database::one('SELECT latitude, longitude FROM commune WHERE id = ?', [$data['commune_id']]);
            if ($communeGps && $communeGps['latitude'] !== null) {
                $data['latitude'] = (float) $communeGps['latitude'];
                $data['longitude'] = (float) $communeGps['longitude'];
            }
        }

        $eventId = Database::insert('evenements', $data);

        if ($anomalieIds !== []) {
            self::syncAnomalies($eventId, $anomalieIds);
            self::syncAnomalyAssignments($eventId, $anomalieIds, (int) ($data['commune_id'] ?? 0));
        }

        RoutingService::assignOrganization($eventId);

        if ($associationId > 0) {
            $assoc = Database::one('SELECT nom FROM associations WHERE id = ?', [$associationId]);
            $commune = Database::one('SELECT nom FROM commune WHERE id = ?', [(int) ($d['commune_id'] ?? 0)]);
            $titre = $assoc ? $assoc['nom'] : 'Association inconnue';
            $lieu = ($commune['nom'] ?? '') . (! empty($d['adresse']) ? ' — ' . mb_strimwidth((string) $d['adresse'], 0, 60, '…') : '');
            Notification::sendToRole(
                'wilaya',
                'Nouvelle demande d\'événement',
                "Nouvelle demande de {$titre} : " . ($d['description'] ?? 'Événement sans titre') . ($lieu !== '' ? " ({$lieu})" : ''),
                'evenement_create',
                ['evenement_id' => $eventId]
            );
        }

        return $eventId;
    }

    /**
     * Met à jour les champs éditables d'un événement.
     *
     * @param array<string, mixed> $d
     * @param array<string, mixed> $ancien
     */
    public static function update(int $id, array $d, array $ancien): void
    {
        $data = [
            'commune_id'                  => (int) ($d['commune_id'] ?? 0) ?: null,
            'adresse'                     => trim((string) ($d['adresse'] ?? '')),
            'description'                 => trim((string) ($d['description'] ?? '')),
            'informations_complementaires' => trim((string) ($d['informations'] ?? '')),
        ];

        if (array_key_exists('date_evenement', $d) && $d['date_evenement'] !== '') {
            $data['date_evenement'] = (string) $d['date_evenement'];
        }
        if (array_key_exists('heure', $d) && $d['heure'] !== '') {
            $data['heure'] = (string) $d['heure'];
        }
        if (array_key_exists('capacite', $d)) {
            $data['capacite'] = $d['capacite'] !== '' ? max(1, (int) $d['capacite']) : null;
        }

        if (isset($d['start_at']) && $d['start_at'] !== '') {
            $data['start_at'] = (string) $d['start_at'];
        }
        if (isset($d['end_at']) && $d['end_at'] !== '') {
            $data['end_at'] = (string) $d['end_at'];
        }
        if (isset($d['latitude']) && $d['latitude'] !== '') {
            $data['latitude'] = (float) $d['latitude'];
        } else {
            $data['latitude'] = null;
        }
        if (isset($d['longitude']) && $d['longitude'] !== '') {
            $data['longitude'] = (float) $d['longitude'];
        } else {
            $data['longitude'] = null;
        }

        Database::update('evenements', $data, 'id = ?', [$id]);

        RoutingService::assignOrganization((int) $id);

        AuditLog::log('evenement_modification', 'evenement', $id, $ancien, $data);
    }

    /**
     * Remplace les anomalies liées à un événement.
     *
     * @param array<int> $ids
     */
    public static function syncAnomalies(int $evenementId, array $ids): void
    {
        Database::delete('anomalies_evenement', 'evenement_id = ?', [$evenementId]);

        foreach (array_unique(array_map('intval', $ids)) as $anomalieId) {
            Database::insert('anomalies_evenement', [
                'anomalie_id'  => $anomalieId,
                'evenement_id' => $evenementId,
            ]);
        }
    }

    /**
     * Remplace les anomalies + GPS + statuts pour un événement.
     *
     * @param array<int, array{anomalie_id: int, latitude?: float|null, longitude?: float|null, statut?: string}> $anomalies
     */
    public static function syncAnomaliesWithGps(int $evenementId, array $anomalies): void
    {
        Database::delete('anomalies_evenement', 'evenement_id = ?', [$evenementId]);

        foreach ($anomalies as $a) {
            $anomalieId = (int) ($a['anomalie_id'] ?? 0);
            if ($anomalieId <= 0) {
                continue;
            }
            Database::insert('anomalies_evenement', [
                'anomalie_id'  => $anomalieId,
                'evenement_id' => $evenementId,
                'latitude'     => isset($a['latitude']) && $a['latitude'] !== '' ? (float) $a['latitude'] : null,
                'longitude'    => isset($a['longitude']) && $a['longitude'] !== '' ? (float) $a['longitude'] : null,
                'statut'       => (string) ($a['statut'] ?? 'DETECTEE'),
                'titre'        => $a['titre'] ?? null,
            ]);
        }
    }

    /**
     * Crée les anomaly_assignments (affectation EPIC par anomalie) pour un événement.
     *
     * @param array<int> $anomalieIds
     */
    public static function syncAnomalyAssignments(int $evenementId, array $anomalieIds, int $communeId = 0): void
    {
        Database::delete('anomaly_assignments', 'evenement_id = ?', [$evenementId]);

        foreach (array_unique(array_map('intval', $anomalieIds)) as $anomalieId) {
            $rule = Database::one(
                'SELECT epic_id FROM routing_rules WHERE anomalie_id = ? AND actif = 1 ORDER BY priorite DESC LIMIT 1',
                [$anomalieId]
            );
            $epicId = $rule ? (int) $rule['epic_id'] : 0;
            if ($epicId <= 0) {
                $event = Database::one('SELECT assigned_org_id FROM evenements WHERE id = ?', [$evenementId]);
                $epicId = $event ? (int) $event['assigned_org_id'] : 0;
            }
            if ($epicId > 0) {
                Database::insert('anomaly_assignments', [
                    'evenement_id' => $evenementId,
                    'anomalie_id'  => $anomalieId,
                    'epic_id'      => $epicId,
                    'auto_routed'  => 1,
                ]);
            }
        }
    }

    /**
     * Remplace les EPIC affectées à un événement.
     *
     * @param array<int> $ids
     */
    public static function syncEpics(int $evenementId, array $ids): void
    {
        Database::delete('evenement_epic', 'evenement_id = ?', [$evenementId]);

        foreach (array_unique(array_map('intval', $ids)) as $epicId) {
            Database::insert('evenement_epic', [
                'evenement_id'    => $evenementId,
                'epic_id'         => $epicId,
                'date_affectation' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Validation Wilaya d'un événement en attent — étape "Valider et affecter".
     *
     * Passe l'événement en VALIDÉ, affecte plusieurs EPICs (many-to-many),
     * notifie l'association et chaque EPIC sélectionné, puis journalise.
     *
     * @param array<int> $epicIds EPICs sélectionnées (users.epic_id)
     * @return array<string, mixed>
     */
    public static function validateEvent(int $eventId, ?string $date, ?string $heure, array $epicIds): array
    {
        $event = Database::one(
            'SELECT id, association_id, description, adresse, date_evenement, heure, statut
             FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$eventId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! self::transitionAutorisee($statutActuel, 'VALIDÉ')) {
            abort(409, "Transition interdite : {$statutActuel} → VALIDÉ.");
        }

        $update = [
            'statut'        => 'VALIDÉ',
            'date_evenement' => $date,
            'heure'         => $heure ?? '00:00:00',
            'motif_refus'   => null,
        ];
        Database::update('evenements', $update, 'id = ?', [$eventId]);

        // Many-to-many : affecte toutes les EPICs sélectionnées.
        self::syncEpics((int) $event['id'], $epicIds);

        // Organisation assignée (Wilaya→EPIC) : première EPIC ou désassignée.
        RoutingService::reaffecter(
            (int) $event['id'],
            $epicIds !== [] ? $epicIds : null,
            'Validation Wilaya + affectation EPIC'
        );

        // Date/heure déjà renseignées par la Wilaya → QR code généré directement
        // (l'association n'a plus qu'à programmer ; le QR est disponible tout de suite).
        if (! empty($date)) {
            QrCodeService::generate($eventId, $date, $heure ?? '00:00:00');
        }

        $titre = (string) ($event['description'] ?? $event['adresse'] ?? 'Événement n°' . (int) $event['id']);

        // Notification à l'association.
        if ((int) ($event['association_id'] ?? 0) > 0) {
            Notification::sendToAssociation(
                (int) $event['association_id'],
                'Événement validé',
                "Votre événement '{$titre}' a été validé par la Waliya. Sélectionnez une date/heure.",
                'evenement_valide',
                ['evenement_id' => (int) $event['id']]
            );
        }

        // Notification à chaque EPIC affectée.
        foreach ($epicIds as $epicId) {
            Notification::sendToEpic(
                (int) $epicId,
                'Nouvel événement affecté',
                "Vous avez été affecté à l'événement '{$titre}'.",
                'epic_affectation',
                ['evenement_id' => (int) $event['id']]
            );
        }

        AuditLog::log('evenement_valide', 'evenement', $eventId, ['statut' => $statutActuel], $update);
        $historiqueMsg = 'Statut : ' . $statutActuel . ' → VALIDÉ';
        AuditLog::historique((int) $event['id'], 'statut_valide', $historiqueMsg);

        Database::insert('transition_history', [
            'evenement_id' => $eventId,
            'statut_avant' => $statutActuel,
            'statut_apres' => 'VALIDÉ',
            'user_id'      => Session::userId(),
            'motif'        => null,
        ]);

        return [
            'statut' => 'VALIDÉ',
            'epics'  => $epicIds,
            'date'   => $date,
            'heure'  => $heure,
        ];
    }

    /**
     * Change le statut d'un événement avec traçabilité et validation de transition.
     *
     * @throws \RuntimeException si la transition n'est pas autorisée
     */
    public static function changeStatut(int $id, string $statut, ?string $motif = null): bool
    {
        if (! in_array($statut, self::STATUTS, true)) {
            abort(422, 'Statut invalide.');
        }

        $ancien = Database::one(
            'SELECT statut, date_evenement, heure, association_id FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );

        if ($ancien === null) {
            abort(404, 'Événement introuvable.');
        }

        $statutActuel = (string) ($ancien['statut'] ?? 'EN_ATTENTE');

        if (! self::transitionAutorisee($statutActuel, $statut)) {
            abort(409, "Transition interdite : {$statutActuel} → {$statut}.");
        }

        $data = ['statut' => $statut, 'motif_refus' => $motif];

        Database::update('evenements', $data, 'id = ?', [$id]);

        // Passage direct en PROGRAMME / QR_GENERE depuis le tableau de bord Wilaya :
        // si une date/heure existe, génère (ou régénère) le QR code immédiatement.
        if (in_array($statut, ['PROGRAMME', 'QR_GENERE'], true)
            && ! empty($ancien['date_evenement'])) {
            QrCodeService::generate(
                $id,
                (string) $ancien['date_evenement'],
                (string) ($ancien['heure'] ?? '00:00:00')
            );
        }

        self::notifierDecision((int) $id, (int) ($ancien['association_id'] ?? 0), $statut, $motif, $statutActuel);

        AuditLog::log('evenement_statut', 'evenement', $id, $ancien, $data);
        AuditLog::historique($id, 'statut_' . statut_key($statut), 'Statut : ' . $statutActuel . ' → ' . $statut . ($motif ? ' (' . $motif . ')' : ''));

        Database::insert('transition_history', [
            'evenement_id' => $id,
            'statut_avant' => $statutActuel,
            'statut_apres'   => $statut,
            'user_id'        => Session::userId(),
            'motif'          => $motif,
        ]);

        return true;
    }

    /**
     * Clôture automatique : passe en TERMINE les événements « à venir » dont la
     * date est dépassée de plus de DELAI_CLOTURE_JOURS jours.
     *
     * Conservatoire — n'appelle jamais abort()/exit() (utilisable depuis un
     * worker, un cron ou un point d'entrée HTTP). Traçabilité, historique et
     * notification association conservés.
     *
     * @return int nombre d'événements clôturés
     */
    public static function autoCloturer(): int
    {
        $statuts = array_map(static fn(string $s): string => "'{$s}'", self::STATUTS_A_VENIR);
        $rows    = Database::all(
            'SELECT id, statut, association_id FROM evenements
              WHERE statut IN (' . implode(',', $statuts) . ')
                AND date_evenement < DATE_SUB(CURDATE(), INTERVAL ' . (int) self::DELAI_CLOTURE_JOURS . ' DAY)
                AND deleted_at IS NULL
              ORDER BY date_evenement ASC
              LIMIT 200'
        );

        $count = 0;
        $motif = 'Clôture automatique : événement terminé depuis plus de ' . (int) self::DELAI_CLOTURE_JOURS . ' jour.';

        foreach ($rows as $row) {
            $id           = (int) $row['id'];
            $statutActuel = (string) $row['statut'];

            // Re-lecture fraîche : un autre processus a pu modifier l'événement.
            $actuel = Database::one(
                'SELECT statut, association_id FROM evenements WHERE id = ? AND deleted_at IS NULL',
                [$id]
            );
            if ($actuel === null
                || (string) $actuel['statut'] !== $statutActuel
                || ! self::transitionAutorisee($statutActuel, 'TERMINE')) {
                continue;
            }

            $data = ['statut' => 'TERMINE', 'motif_refus' => $motif];

            Database::update('evenements', $data, 'id = ?', [$id]);

            self::notifierDecision($id, (int) ($actuel['association_id'] ?? 0), 'TERMINE', $motif, $statutActuel);
            AuditLog::log('evenement_statut', 'evenement', $id, $actuel, $data);
            AuditLog::historique($id, 'statut_' . statut_key('TERMINE'), 'Statut : ' . $statutActuel . ' → TERMINE (' . $motif . ')');
            Database::insert('transition_history', [
                'evenement_id' => $id,
                'statut_avant' => $statutActuel,
                'statut_apres'   => 'TERMINE',
                'user_id'        => null,
                'motif'          => $motif,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Rappels programmés : notifie les participants et l'association des
     * événements « à venir » qui se déroulent demain, puis marque
     * rappel_envoye_at pour ne jamais re-notifier (idempotent).
     *
     * Conservatoire — aucun abort()/exit() (utilisable depuis le worker ou un cron).
     *
     * @return int nombre d'événements pour lesquels un rappel a été envoyé
     */
    public static function envoyerRappels(): int
    {
        $statuts = array_map(static fn(string $s): string => "'{$s}'", self::STATUTS_A_VENIR);
        $rows    = Database::all(
            'SELECT id, date_evenement, heure, adresse, association_id
             FROM evenements
             WHERE statut IN (' . implode(',', $statuts) . ')
               AND date_evenement = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND rappel_envoye_at IS NULL
               AND deleted_at IS NULL
             ORDER BY date_evenement ASC
             LIMIT 200'
        );

        $count = 0;

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            // Re-lecture fraîche : éviter la double notification concurrente.
            $nb = (int) Database::value(
                'SELECT COUNT(*) FROM evenements WHERE id = ? AND rappel_envoye_at IS NULL AND deleted_at IS NULL',
                [$id]
            );
            if ($nb === 0) {
                continue;
            }

            $jour   = $row['date_evenement'] ? date('d/m/Y', strtotime((string) $row['date_evenement'])) : 'demain';
            $heure  = ! empty($row['heure']) ? substr((string) $row['heure'], 0, 5) : '';
            $lieu   = (string) ($row['adresse'] ?? '');
            $quand  = $jour . ($heure !== '' ? " à {$heure}" : '') . ($lieu !== '' ? " — {$lieu}" : '');

            // Rappel aux participants déjà inscrits.
            foreach (Database::all(
                'SELECT DISTINCT user_id FROM evenement_participant WHERE evenement_id = ?',
                [$id]
            ) as $p) {
                Notification::send(
                    (int) $p['user_id'],
                    'Rappel : événement demain',
                    "Votre participation est confirmée pour l'événement du {$quand}. On vous attend !",
                    'rappel_evenement',
                    ['evenement_id' => $id]
                );
            }

            // Rappel à l'association organisatrice.
            if ((int) ($row['association_id'] ?? 0) > 0) {
                Notification::sendToAssociation(
                    (int) $row['association_id'],
                    'Rappel : votre événement a lieu demain',
                    "L'événement du {$quand} se déroule demain. Pensez à activer la présence en direct pendant l'événement.",
                    'rappel_evenement',
                    ['evenement_id' => $id]
                );
            }

            Database::update('evenements', ['rappel_envoye_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);

            // ── Envoi email rappel J-1 ──────────────────────────────
            $eventUrl = public_url('association/events/' . $id);
            $eventTitle = $row['adresse'] ?? ('Événement #' . $id);
            $dateFmt = $jour . ($heure !== '' ? " à {$heure}" : '');

            // Email aux participants
            $participantEmails = Mailer::getParticipantEmails($id);
            Mailer::sendToUsers($participantEmails, static fn(string $email) => Mailer::sendEventReminder(
                $email, $eventTitle, $dateFmt, $lieu, $eventUrl, false
            ));

            // Email à l'association
            if ((int) ($row['association_id'] ?? 0) > 0) {
                $assocEmails = Mailer::getAssociationEmails((int) $row['association_id']);
                Mailer::sendToUsers($assocEmails, static fn(string $email) => Mailer::sendEventReminder(
                    $email, $eventTitle, $dateFmt, $lieu, $eventUrl, true
                ));
            }
            // ─────────────────────────────────────────────────────────

            $count++;
        }

        return $count;
    }

    /**
     * Annulation d'une demande par l'association (EN_ATTENTE / MODIFICATION_DEMANDEE → ANNULE).
     *
     * @return array<string, mixed> l'événement concerné
     */
    public static function changerStatutAnnule(int $id, string $motif): array
    {
        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NULL', [$id]);

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! self::transitionAutorisee($statutActuel, 'ANNULE')) {
            abort(409, "Transition interdite : {$statutActuel} → ANNULE.");
        }

        Database::update('evenements', ['statut' => 'ANNULE', 'motif_refus' => $motif], 'id = ?', [$id]);

        // Notification à la Wilaya : une demande a été annulée par l'association.
        if ((int) ($event['association_id'] ?? 0) > 0) {
            $assoc = Database::one('SELECT nom FROM associations WHERE id = ?', [(int) $event['association_id']]);
            Notification::sendToRole(
                'wilaya',
                'Demande annulée',
                ($assoc ? $assoc['nom'] : 'L\'association') . " a annulé sa demande (#{$id}) : {$motif}",
                'evenement_annule',
                ['evenement_id' => $id]
            );
        }

        AuditLog::log('evenement_statut', 'evenement', $id, ['statut' => $statutActuel], ['statut' => 'ANNULE', 'motif' => $motif]);
        AuditLog::historique($id, 'statut_annule', 'Statut : ' . $statutActuel . ' → ANNULE (' . $motif . ')');

        Database::insert('transition_history', [
            'evenement_id' => $id,
            'statut_avant' => $statutActuel,
            'statut_apres' => 'ANNULE',
            'user_id'      => Session::userId(),
            'motif'        => $motif,
        ]);

        return Database::one('SELECT * FROM evenements WHERE id = ?', [$id]);
    }

    /**
     * Vérifie qu'une transition de statut est autorisée.
     */
    public static function transitionAutorisee(string $avant, string $apres): bool
    {
        $allowed = self::TRANSITIONS_AUTORISEES[$avant] ?? [];

        return in_array($apres, $allowed, true);
    }

    /**
     * Détermine la "prochaine action" à réaliser pour un événement (vision
     * dossier) : action prioritaire, responsable, délai restant (SLA).
     *
     * Méthode en lecture seule — aucune mutation ni accès réseau.
     *
     * @param array<string, mixed>  $event   lignes de la table evenements
     * @param array<string, mixed>  $context informations complémentaires
     *                                        (epics_count, nb_participants, album_existe, ...)
     *
     * @return array{statut: string, titre: string, responsable: string,
     *               priorite: string, lien: ?string, sla_sec: ?int,
     *               sla_label: string, overdue: bool}
     */
    public static function nextAction(array $event, array $context = []): array
    {
        $statut = (string) ($event['statut'] ?? 'EN_ATTENTE');
        $id     = (int) ($event['id'] ?? 0);
        $deadline = $event['deadline_at'] ?? null;
        $slaSec = null;
        $slaLabel = '';
        $overdue = false;
        $assigned = $event['assigned_org_id'] ?? null;

        if ($deadline !== null && ! empty($deadline)) {
            $slaSec = strtotime((string) $deadline) - time();
            $overdue = $slaSec < 0;
            $abs = abs($slaSec);
            $slaLabel = $overdue
                ? 'Délai dépassé de ' . ceil($abs / 86400) . 'j'
                : ($slaSec < 86400
                    ? ceil($slaSec / 3600) . 'h restantes'
                    : ceil($slaSec / 86400) . 'j restantes');
        }

        $meta = [
            'epics_count'     => (int) ($context['epics_count'] ?? 0),
            'nb_participants' => (int) ($context['nb_participants'] ?? 0),
            'capacite'        => (int) ($event['capacite'] ?? 0),
            'album_existe'    => (bool) ($context['album_existe'] ?? false),
            'anomalies_ouvertes' => (int) ($context['anomalies_ouvertes'] ?? 0),
            'qrcode_existe'   => (bool) ($context['qrcode_existe'] ?? false),
        ];

        switch ($statut) {
            case 'EN_ATTENTE':
                return self::action('Valider et affecter les EPIC', 'Wilaya', 'haute',
                    $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);

            case 'MODIFICATION_DEMANDEE':
                return self::action('Attendre la re-soumission de l\'association', 'Association', 'moyenne',
                    null, $statut, $slaSec, $slaLabel, $overdue);

            case 'REFUSE':
                return self::action('Dossier refusé — créer une nouvelle demande', 'Association', 'basse',
                    null, $statut, $slaSec, $slaLabel, $overdue);

            case 'VALIDÉ':
                return self::action('Programmer l\'événement (générer le QR et le SLA)', 'Wilaya', 'haute',
                    $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);

            case 'PROGRAMME':
                if ($meta['epics_count'] === 0) {
                    return self::action('Affecter au moins un EPIC', 'Wilaya', 'haute',
                        $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);
                }
                if ($meta['qrcode_existe'] === false) {
                    return self::action('Générer le QR code', 'Wilaya', 'moyenne',
                        $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);
                }
                return self::action('Préparer l\'arrivée (EPIC programmé)', 'EPIC', 'moyenne',
                    null, $statut, $slaSec, $slaLabel, $overdue);

            case 'QR_GENERE':
            case 'EN_COURS':
                if ($meta['anomalies_ouvertes'] > 0) {
                    return self::action($meta['anomalies_ouvertes'] . ' anomalie(s) à traiter', 'Wilaya', 'haute',
                        $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);
                }
                if ($statut === 'EN_COURS') {
                    return self::action('Suivre et clôturer l\'opération', 'EPIC', 'moyenne',
                        null, $statut, $slaSec, $slaLabel, $overdue);
                }
                return self::action('Passer l\'événement en cours', 'Wilaya', 'moyenne',
                    $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);

            case 'TERMINE':
                if ($meta['album_existe'] === false) {
                    return self::action('Créer l\'album officiel (48 h max)', 'Wilaya', 'moyenne',
                        $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);
                }
                return self::action('Dossier clôturé', '—', 'basse', null, $statut, $slaSec, $slaLabel, $overdue);

            case 'ANNULE':
                return self::action('Dossier annulé', '—', 'basse', null, $statut, $slaSec, $slaLabel, $overdue);

            default:
                return self::action('Vérifier le dossier', 'Wilaya', 'moyenne',
                    $id ? url('wilaya/evenements/' . $id) : null, $statut, $slaSec, $slaLabel, $overdue);
        }
    }

    /**
     * Assemble le tableau "prochaine action".
     *
     * @return array<string, mixed>
     */
    private static function action(
        string $titre,
        string $responsable,
        string $priorite,
        ?string $lien,
        string $statut,
        ?int $slaSec,
        string $slaLabel,
        bool $overdue
    ): array {
        return [
            'statut'      => $statut,
            'titre'       => $titre,
            'responsable' => $responsable,
            'priorite'    => $priorite,
            'lien'        => $lien,
            'sla_sec'     => $slaSec,
            'sla_label'   => $slaLabel,
            'overdue'     => $overdue,
        ];
    }

    /**
     * Détecte les dossiers incomplets : retourne un score de complétude (0-100)
     * et la liste des éléments manquants (champs obligatoires d'un dossier).
     *
     * En lecture seule — aucune mutation.
     *
     * @param array<string, mixed> $event  ligne evenements
     * @param array<string, mixed> $ctx    contexte : epics_count, anomalies_ouvertes,
     *                                     album_existe, qrcode_existe
     *
     * @return array{score: int, manque: array<int, array{cle: string, libelle: string, important: bool}>}
     */
    public static function completudeEvent(array $event, array $ctx = []): array
    {
        $checks = [];

        $obligatoires = [
            'adresse'        => ['adresse', 'Adresse', true],
            'commune'        => ['commune_id', 'Commune', true],
            'association'    => ['association_id', 'Association porteuse', true],
            'description'    => ['description', 'Description', true],
            'date'           => ['date_evenement', 'Date de l\'événement', true],
            'heure'          => ['heure', 'Heure', true],
            'capacite'       => ['capacite', 'Capacité (places)', false],
            'gps'            => ['latitude', 'Coordonnées GPS', false],
        ];

        foreach ($obligatoires as $cle => [$sqlCol, $libelle, $important]) {
            $val = $event[$sqlCol] ?? null;
            $present = $val !== null && $val !== '';
            $checks[$cle] = [
                'present'   => $present,
                'libelle'   => $libelle,
                'important' => $important,
            ];
        }

        $epicsCount = (int) ($ctx['epics_count'] ?? 0);
        $statut = (string) ($event['statut'] ?? 'EN_ATTENTE');
        $checks['epics'] = [
            'present'   => $epicsCount > 0,
            'libelle'   => 'EPIC affecté(s)',
            'important' => in_array($statut, ['VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'], true),
        ];
        $checks['gps' ]['present'] = $checks['gps']['present']
            || (! empty($event['latitude']) && ! empty($event['longitude']));

        $manque = [];
        $poidsTotal = 0;
        $poidsOk = 0;

        foreach ($checks as $cle => $c) {
            if (! $c['present']) {
                $manque[] = ['cle' => $cle, 'libelle' => $c['libelle'], 'important' => $c['important']];
            }
            $poidsImportant = $c['important'] ? 2 : 1;
            $poidsTotal += $poidsImportant;
            if ($c['present']) {
                $poidsOk += $poidsImportant;
            }
        }

        $score = (int) round(($poidsOk / max(1, $poidsTotal)) * 100);

        return ['score' => $score, 'manque' => $manque];
    }

    /**
     * Priorisation automatique d'un dossier pour l'administrateur.
     * Combine complétude, SLA, anomalies critiques et ancienneté.
     *
     * En lecture seule.
     *
     * @param array<string, mixed> $event
     * @param array<string, mixed> $ctx
     *
     * @return array{niveau: string, score: int, raisons: array<int, string>}
     */
    public static function prioriteDossier(array $event, array $ctx = []): array
    {
        $completude = self::completudeEvent($event, $ctx);
        $manqueImportants = count(array_filter($completude['manque'], static fn($m) => $m['important']));

        $raisons = [];
        $base = 0;

        if ($manqueImportants > 0) {
            $base += 15;
            $raisons[] = 'Dossier incomplet (' . $manqueImportants . ' champ(s) obligatoire(s) manquant(s))';
        }

        $deadline = $event['deadline_at'] ?? null;
        if ($deadline !== null && ! empty($deadline)) {
            $slaSec = strtotime((string) $deadline) - time();
            if ($slaSec < 0) {
                $base += 40;
                $raisons[] = 'SLA dépassé de ' . ceil(abs($slaSec) / 86400) . 'j';
            } elseif ($slaSec < 86400) {
                $base += 25;
                $raisons[] = 'SLA sous 24 h';
            } elseif ($slaSec < 3 * 86400) {
                $base += 10;
            }
        }

        $anomalies = (int) ($ctx['anomalies_ouvertes'] ?? 0);
        if ($anomalies > 0) {
            $base += 20;
            $raisons[] = $anomalies . ' anomalie(s) ouverte(s)';
        }

        // Ancienneté de la demande.
        $created = $event['created_at'] ?? null;
        if ($created !== null && ! empty($created)) {
            $ageJours = (int) floor((time() - strtotime((string) $created)) / 86400);
            if ($ageJours >= 7) {
                $base += 15;
                $raisons[] = 'Demande ancienne (> 7 j)';
            } elseif ($ageJours >= 3) {
                $base += 8;
            }
        }

        if ((string) ($event['statut'] ?? '') === 'TERMINE' && empty($ctx['album_existe'])) {
            $base += 20;
            $raisons[] = 'Album officiel non créé (48 h max)';
        }

        $score = min(100, $base);
        $niveau = $score >= 50 ? 'urgent' : ($score >= 25 ? 'important' : 'normal');

        return ['niveau' => $niveau, 'score' => $score, 'raisons' => array_slice($raisons, 0, 4)];
    }

    /**
     * Suggestions automatiques à destination de l'administrateur, dérivées de
     * la complétude, de la priorité et de la prochaine action.
     *
     * @param array<string, mixed> $event
     * @param array<string, mixed> $ctx
     *
     * @return array<int, string>
     */
    public static function suggestionsAdmin(array $event, array $ctx = []): array
    {
        $suggestions = [];

        $completude = self::completudeEvent($event, $ctx);
        foreach ($completude['manque'] as $m) {
            if ($m['important']) {
                $suggestions[] = 'Renseigner : ' . $m['libelle'];
            }
        }

        $priorite = self::prioriteDossier($event, $ctx);
        if ($priorite['niveau'] === 'urgent') {
            $suggestions[] = 'Dossier prioritaire — traiter en premier';
        }

        $next = self::nextAction($event, $ctx);
        if ($next['titre'] !== '') {
            $suggestions[] = 'Prochaine action : ' . $next['titre'] . ' (' . $next['responsable'] . ')';
        }

        if ((string) ($event['statut'] ?? '') === 'EN_ATTENTE' && (int) ($ctx['epics_count'] ?? 0) === 0) {
            $suggestions[] = 'Aucun EPIC compétent détecté — vérifier le routage manuel';
        }

        if (! empty($event['date_evenement']) && ! empty($event['heure']) && empty($event['deadline_at'])) {
            $suggestions[] = 'Calculer le délai SLA / rappels automatiques';
        }

        return array_slice($suggestions, 0, 6);
    }

    /**
     * Estimation du délai de traitement restant d'un dossier (lecture seule).
     *
     * @param array<string, mixed> $event
     * @param array<string, mixed> $ctx
     * @return array{jours: int, label: string, confiance: string}
     */
    public static function estimDelaiTraitement(array $event, array $ctx = []): array
    {
        $statut        = (string) ($event['statut'] ?? '');
        $now           = time();
        $dateEvenement = ! empty($event['date_evenement']) ? strtotime((string) $event['date_evenement']) : null;
        $deadline      = ! empty($event['deadline_at'])   ? strtotime((string) $event['deadline_at'])   : null;

        if ($statut === 'TERMINE') {
            return ['jours' => 0, 'label' => 'Terminé', 'confiance' => 'haute'];
        }
        if (in_array($statut, ['ANNULE', 'REFUSE'], true)) {
            return ['jours' => 0, 'label' => 'Clôturé', 'confiance' => 'haute'];
        }

        // Délai physique avant l'événement (lead-time restant).
        if ($dateEvenement !== null) {
            $joursAvant = (int) ceil(($dateEvenement - $now) / 86400);
            if ($joursAvant > 0) {
                return [
                    'jours'     => $joursAvant,
                    'label'     => $joursAvant . ' j avant l\'événement',
                    'confiance' => 'haute',
                ];
            }
        }

        // Sinon fenêtre de traitement SLA (donne la date limite de traitement).
        if ($deadline !== null) {
            $joursRestants = (int) ceil(($deadline - $now) / 86400);
            if ($joursRestants > 0) {
                return [
                    'jours'     => $joursRestants,
                    'label'     => $joursRestants . ' j avant échéance SLA',
                    'confiance' => 'moyenne',
                ];
            }
            return [
                'jours'     => 0,
                'label'     => 'Échéance SLA dépassée',
                'confiance' => 'haute',
            ];
        }

        // Heuristique par statut quand aucune date n'est renseignée.
        $parDefaut = [
            'EN_ATTENTE'          => ['jours' => 7, 'confiance' => 'basse'],
            'MODIFICATION_DEMANDEE' => ['jours' => 7, 'confiance' => 'basse'],
            'VALIDÉ'              => ['jours' => 3, 'confiance' => 'basse'],
            'PROGRAMME'           => ['jours' => 2, 'confiance' => 'basse'],
            'QR_GENERE'           => ['jours' => 1, 'confiance' => 'basse'],
            'EN_COURS'            => ['jours' => 1, 'confiance' => 'basse'],
        ];
        $d = $parDefaut[$statut] ?? ['jours' => 3, 'confiance' => 'basse'];

        return ['jours' => $d['jours'], 'label' => '~' . $d['jours'] . ' j (estimation)', 'confiance' => $d['confiance']];
    }

    /**
     * Détecte les relances / escalades nécessaires à la volée (lecture seule),
     * sur tous les événements ouverts, pour piloter le traitement par la wilaya.
     *
     * @return array<int, array{type: string, label: string, gravite: string,
     *                             evenement_id: int, adresse: string, epics: array<int, string>}>
     */
    public static function relancesEscalades(int $limit = 30): array
    {
        $limit = max(1, (int) $limit);
        $sql   = 'SELECT e.id, e.statut, e.adresse, e.date_evenement, e.deadline_at, e.assigned_org_id, e.date_evenement AS de
                    FROM evenements e
                   WHERE e.deleted_at IS NULL
                     AND e.statut IN '
                . "('EN_ATTENTE','MODIFICATION_DEMANDEE','VALIDÉ','PROGRAMME','QR_GENERE','EN_COURS') "
                . 'ORDER BY e.created_at DESC LIMIT ' . $limit;
        $rows  = Database::all($sql);

        $pochesEpic = [];
        $pochesEpicIds = array_column($rows, 'id');
        if (! empty($pochesEpicIds)) {
            $in   = implode(',', array_fill(0, count($pochesEpicIds), '?'));
            $ints = array_map('intval', $pochesEpicIds);
            $sqlEpic = 'SELECT ee.evenement_id, ep.nom, ee.statut AS ee_statut, ee.date_affectation
                          FROM evenement_epic ee JOIN epic ep ON ep.id = ee.epic_id
                         WHERE ee.evenement_id IN (' . $in . ')';
            $ep   = Database::all($sqlEpic, $ints);
            foreach ($ep as $row) {
                $pochesEpic[(int) $row['evenement_id']][] = $row;
            }
        }

        $albums = [];
        foreach ($pochesEpicIds as $eid) {
            $has = (int) Database::value('SELECT COUNT(*) FROM albums WHERE evenement_id = ?', [(int) $eid]);
            $albums[(int) $eid] = $has > 0;
        }

        $now = time();
        $out = [];
        foreach ($rows as $e) {
            $escs  = [];
            $eid   = (int) $e['id'];
            $epics = $pochesEpic[$eid] ?? [];
            $noms  = array_map(static fn($x) => (string) $x['nom'], $epics);

            // EPIC affecté mais jamais passé en EN_COURS depuis 48 h.
            if (! empty($epics)) {
                $bloque = false;
                foreach ($epics as $x) {
                    if ($x['ee_statut'] === 'AFFECTE') {
                        $aff = ! empty($x['date_affectation']) ? strtotime((string) $x['date_affectation']) : $now;
                        if (($now - $aff) / 3600 >= 48) {
                            $bloque = true;
                        }
                    }
                }
                if ($bloque) {
                    $escs[] = [
                        'type' => 'epic_bloque',
                        'label' => 'EPIC affecté sans prise en charge (48 h+)',
                        'gravite' => 'haute',
                        'evenement_id' => $eid,
                        'adresse' => (string) $e['adresse'],
                        'epics' => $noms,
                    ];
                }
            } elseif ($e['statut'] === 'EN_ATTENTE') {
                $escs[] = [
                    'type' => 'non_routé',
                    'label' => 'Non affecté à un EPIC',
                    'gravite' => 'haute',
                    'evenement_id' => $eid,
                    'adresse' => (string) $e['adresse'],
                    'epics' => [],
                ];
            }

            // Événement dont la date est passée mais statut jamais démarré.
            $nonDemarre = ! empty($e['de']) && strtotime((string) $e['de']) < $now - 86400
                && in_array($e['statut'], ['PROGRAMME', 'QR_GENERE', 'VALIDÉ'], true);
            if ($nonDemarre) {
                $escs[] = [
                    'type' => 'non_démarré',
                    'label' => 'Événement non démarré (date passée)',
                    'gravite' => 'haute',
                    'evenement_id' => $eid,
                    'adresse' => (string) $e['adresse'],
                    'epics' => $noms,
                ];
            }

            // Terminé (ou EN_COURS) sans album.
            if (in_array($e['statut'], ['EN_COURS', 'PROGRAMME', 'QR_GENERE'], true) && empty($albums[$eid])) {
                $escs[] = [
                    'type' => 'album_manquant',
                    'label' => 'Album preuves non créé',
                    'gravite' => 'moyenne',
                    'evenement_id' => $eid,
                    'adresse' => (string) $e['adresse'],
                    'epics' => $noms,
                ];
            }

            // Échéance SLA dépassée ou J-1, dossier pas encore validé.
            $slaAVerifier = in_array($e['statut'], ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'VALIDÉ'], true)
                && ! empty($e['deadline_at']);
            if ($slaAVerifier) {
                $rest = strtotime((string) $e['deadline_at']) - $now;
                if ($rest < 0) {
                    $escs[] = [
                        'type' => 'sla_depassé',
                        'label' => 'Validation en retard (SLA dépassé)',
                        'gravite' => 'haute',
                        'evenement_id' => $eid,
                        'adresse' => (string) $e['adresse'],
                        'epics' => $noms,
                    ];
                }
            }

            foreach ($escs as $es) {
                $out[] = $es;
            }
        }

        return $out;
    }

    /**
     * Demande des modifications à l'association : passe l'événement en
     * MODIFICATION_DEMANDEE (traçabilité + motif), à re-soumettre ensuite.
     *
     * @return array<string, mixed> l'événement concerné
     */
    public static function demanderModifications(int $id, string $motif): array
    {
        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NULL', [$id]);

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! self::transitionAutorisee($statutActuel, 'MODIFICATION_DEMANDEE')) {
            abort(409, "Transition interdite : {$statutActuel} → MODIFICATION_DEMANDEE.");
        }

        Database::update('evenements', ['statut' => 'MODIFICATION_DEMANDEE', 'motif_refus' => $motif], 'id = ?', [$id]);

        self::notifierDecision((int) $id, (int) ($event['association_id'] ?? 0), 'MODIFICATION_DEMANDEE', $motif, $statutActuel);

        AuditLog::log('evenement_statut', 'evenement', $id, ['statut' => $statutActuel], ['statut' => 'MODIFICATION_DEMANDEE', 'motif' => $motif]);
        AuditLog::historique($id, 'statut_modification_demandee', 'Statut : ' . $statutActuel . ' → MODIFICATION_DEMANDEE (' . $motif . ')');

        Database::insert('transition_history', [
            'evenement_id' => $id,
            'statut_avant' => $statutActuel,
            'statut_apres' => 'MODIFICATION_DEMANDEE',
            'user_id'      => Session::userId(),
            'motif'        => $motif,
        ]);

        return $event;
    }

    /**
     * Re-soumission par l'association après correction (MODIFICATION_DEMANDEE / REFUSE → EN_ATTENTE).
     * Efface le motif de refus, notifie la Wilaya, trace l'historique.
     */
    public static function resoumettre(int $id): void
    {
        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NULL', [$id]);

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $statutActuel = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! self::transitionAutorisee($statutActuel, 'EN_ATTENTE')) {
            abort(409, "Transition interdite : {$statutActuel} → EN_ATTENTE.");
        }

        Database::update('evenements', ['statut' => 'EN_ATTENTE', 'motif_refus' => null], 'id = ?', [$id]);

        if ((int) ($event['association_id'] ?? 0) > 0) {
            Notification::sendToRole(
                'wilaya',
                'Demande re-soumise',
                "L'association a re-soumis l'événement #{$id} après correction.",
                'evenement_resoumis',
                ['evenement_id' => $id]
            );
        }

        AuditLog::log('evenement_resoumission', 'evenement', $id, ['statut' => $statutActuel], ['statut' => 'EN_ATTENTE']);
        AuditLog::historique($id, 'resoumission', 'Statut : ' . $statutActuel . ' → EN_ATTENTE (re-soumission après correction)');

        Database::insert('transition_history', [
            'evenement_id' => $id,
            'statut_avant' => $statutActuel,
            'statut_apres' => 'EN_ATTENTE',
            'user_id'      => Session::userId(),
            'motif'        => 'Re-soumission après correction par l\'association',
        ]);
    }

    /**
     * Programme (valide) un événement : statut PROGRAMME, date, EPIC, QR, SLA, notifications.
     * Transition autorisée : EN_ATTENTE / VALIDÉ → PROGRAMME (ou via re-soumission).
     *
     * @param array<int> $epicIds
     */
    public static function programmer(int $id, string $date, string $heure, array $epicIds, int $associationId): void
    {
        $event = Database::one(
            'SELECT statut FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$id]
        ) ?: [];

        $currentStatut = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! self::transitionAutorisee($currentStatut, 'PROGRAMME')) {
            abort(409, "Transition interdite : {$currentStatut} → PROGRAMME.");
        }

        Database::update('evenements', [
            'statut'        => 'PROGRAMME',
            'date_evenement' => $date,
            'heure'         => $heure,
            'motif_refus'   => null,
            'deadline_at'   => date('Y-m-d H:i:s', strtotime($date . ' ' . $heure)),
        ], 'id = ?', [$id]);

        self::syncEpics($id, $epicIds);

        // Événement programmé : affecte les EPIC sélectionnés et trace la première EPIC comme organisation assignée (règle manuelle implicite).
        if ($epicIds !== []) {
            RoutingService::reaffecter($id, $epicIds, 'Programmation wilaya');
        }

        QrCodeService::generate($id, $date, $heure);
        SlaHelper::scheduleForEvenement($id, $date, $heure);

        if ($associationId > 0) {
            Notification::sendToAssociation($associationId, __('evenements.programmed_title'), date('d/m/Y', strtotime($date)) . ' à ' . $heure, 'evenement_valide', ['evenement_id' => $id]);
        }

        foreach ($epicIds as $epicId) {
            Notification::sendToRole('epic', __('evenements.assigned_title'), $date . ' à ' . $heure, 'epic_affectation', ['evenement_id' => $id]);
        }

        AuditLog::log('evenement_programme', 'evenement', $id, ['statut' => $currentStatut], [
            'date_evenement' => $date,
            'heure'          => $heure,
            'epics'          => $epicIds,
        ]);
        AuditLog::historique($id, 'programme', 'Événement programmé et publié : ' . $date . ' à ' . $heure);

        Database::insert('transition_history', [
            'evenement_id'  => $id,
            'statut_avant'  => $currentStatut,
            'statut_apres'  => 'PROGRAMME',
            'user_id'       => Session::userId(),
            'motif'         => null,
        ]);
    }

    /**
     * Suppression douce (archivage) d'un événement.
     */
    public static function softDelete(int $id): void
    {
        Database::run('UPDATE evenements SET deleted_at = NOW() WHERE id = ?', [$id]);
        AuditLog::log('evenement_archive', 'evenement', $id, null, ['deleted_at' => date('Y-m-d H:i:s')]);
        AuditLog::historique($id, 'archive', 'Événement archivé par la Wilaya');
    }

    /**
     * Restaure un événement archivé.
     */
    public static function restore(int $id): void
    {
        Database::run('UPDATE evenements SET deleted_at = NULL WHERE id = ?', [$id]);
        AuditLog::log('evenement_restaure', 'evenement', $id, null, ['deleted_at' => null]);
    }

    /**
     * Action groupée sur plusieurs événements.
     *
     * @param array<int> $ids
     */
    public static function bulk(string $action, array $ids): int
    {
        $ids = array_unique(array_filter(array_map('intval', $ids)));
        $count = 0;

        foreach ($ids as $id) {
            $event = Database::one('SELECT statut, deleted_at FROM evenements WHERE id = ?', [$id]);

            if ($event === null) {
                continue;
            }

            if ($action === 'archiver') {
                self::softDelete($id);
                $count++;
                continue;
            }

            if ($action === 'restaurer') {
                self::restore($id);
                $count++;
                continue;
            }

            if ($event['deleted_at'] !== null) {
                continue;
            }

            $cible = match ($action) {
                'valider'  => 'PROGRAMME',
                'terminer' => 'TERMINE',
                'refuser'  => 'REFUSE',
                'relancer' => 'EN_ATTENTE',
                default    => null,
            };

            if ($cible === null || ! self::transitionAutorisee((string) $event['statut'], $cible)) {
                continue;
            }

            self::changeStatut($id, $cible);
            $count++;
        }

        return $count;
    }

    /**
     * EPIC compétentes pour les anomalies d'un événement.
     */
    public static function epicsCompetentes(int $evenementId): array
    {
        return Database::all(
            'SELECT DISTINCT e.* FROM epic e
             WHERE e.id IN (
                SELECT ea.epic_id FROM epic_anomalies ea
                JOIN anomalies_evenement ae ON ae.anomalie_id = ea.anomalie_id
                WHERE ae.evenement_id = ?
             ) ORDER BY e.nom',
            [$evenementId]
        );
    }

    /**
     * Régénère le QR code d'un événement (nouveau token, nouvelle expiration).
     *
     * Délègue à QrCodeService::generate : régénère le token, régénère le fichier
     * PNG, met à jour `qr_code_path` et notifie la disponibilité du QR.
     */
    public static function regenQr(int $evenementId): array
    {
        return QrCodeService::generate($evenementId);
    }

    /**
     * Notifie l'association de la décision Wilaya (refus, modifications,
     * clôture, etc.) et la Wilaya des annulations/re-soumissions.
     *
     * @param int    $associationId identifiant de l'association (0 si aucun)
     * @param string $nouveauStatut statut décidé par la Wilaya
     * @param string $ancienStatut  statut avant la décision
     */
    private static function notifierDecision(
        int $id,
        int $associationId,
        string $nouveauStatut,
        ?string $motif,
        string $ancienStatut = ''
    ): void {
        if ($associationId <= 0) {
            return;
        }

        $event    = Database::one('SELECT description, adresse, date_evenement, heure FROM evenements WHERE id = ?', [$id]);
        $titre    = $event ? (string) ($event['description'] ?? $event['adresse'] ?? 'Événement n°' . $id) : 'Événement n°' . $id;
        $motifTxt = ! empty($motif) ? ' — Motif : ' . $motif : '';

        match ($nouveauStatut) {
            'REFUSE'                 => Notification::sendToAssociation($associationId, 'Demande refusée', "Votre événement '{$titre}' a été refusé par la Wilaya.{$motifTxt}", 'evenement_refuse', ['evenement_id' => $id]),
            'MODIFICATION_DEMANDEE'  => Notification::sendToAssociation($associationId, 'Modifications demandées', "Des modifications sont demandées pour '{$titre}'.{$motifTxt}", 'evenement_modification', ['evenement_id' => $id]),
            'VALIDÉ'                 => Notification::sendToAssociation($associationId, 'Événement validé', "Votre événement '{$titre}' a été validé par la Wilaya.", 'evenement_valide', ['evenement_id' => $id]),
            'EN_COURS'               => Notification::sendToAssociation($associationId, 'Événement en cours', "Votre événement '{$titre}' est en cours de déroulement.", 'evenement_en_cours', ['evenement_id' => $id]),
            'TERMINE'                => Notification::sendToAssociation($associationId, 'Événement terminé', "Votre événement '{$titre}' est terminé.{$motifTxt}", 'evenement_termine', ['evenement_id' => $id]),
            default                  => null,
        };

        // ── Email notifications ──────────────────────────────────────
        if ($nouveauStatut === 'TERMINE') {
            $dateFmt = $event && $event['date_evenement']
                ? date('d/m/Y', strtotime((string) $event['date_evenement']))
                    . (! empty($event['heure']) ? ' à ' . substr((string) $event['heure'], 0, 5) : '')
                : '';
            $lieu = $event ? (string) ($event['adresse'] ?? '') : '';
            $eventUrl = public_url('association/events/' . $id);

            // Email à l'association
            $assocEmails = Mailer::getAssociationEmails($associationId);
            Mailer::sendToUsers($assocEmails, static fn(string $email) => Mailer::sendEventCompleted(
                $email, $titre, $dateFmt, $lieu, $eventUrl, true
            ));

            // Email aux participants
            $participantEmails = Mailer::getParticipantEmails($id);
            Mailer::sendToUsers($participantEmails, static fn(string $email) => Mailer::sendEventCompleted(
                $email, $titre, $dateFmt, $lieu, $eventUrl, false
            ));
        }
        // ──────────────────────────────────────────────────────────────
    }
}
