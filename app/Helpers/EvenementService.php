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

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY e.created_at DESC';

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
     * Crée un événement (association ou opération directe Wilaya).
     *
     * @param array<string, mixed> $d
     * @param array<int>           $anomalieIds
     */
    public static function create(array $d, ?int $associationId, array $anomalieIds = [], string $statut = 'EN_ATTENTE'): int
    {
        $eventId = Database::insert('evenements', [
            'commune_id'                  => (int) ($d['commune_id'] ?? 0) ?: null,
            'adresse'                     => trim((string) ($d['adresse'] ?? '')),
            'association_id'              => $associationId,
            'description'                 => trim((string) ($d['description'] ?? '')),
            'informations_complementaires' => trim((string) ($d['informations'] ?? '')),
            'statut'                      => $statut,
            'date_evenement'              => ! empty($d['date_evenement']) ? (string) $d['date_evenement'] : null,
            'heure'                       => ! empty($d['heure']) ? (string) $d['heure'] : null,
            'motif_refus'                 => null,
            'deadline_at'                 => date('Y-m-d H:i:s', time() + 5 * 86400),
        ]);

        if ($anomalieIds !== []) {
            self::syncAnomalies($eventId, $anomalieIds);
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

        Database::update('evenements', $data, 'id = ?', [$id]);

        if (isset($d['anomalies']) && is_array($d['anomalies'])) {
            self::syncAnomalies($id, array_map('intval', $d['anomalies']));
        }

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

        $event    = Database::one('SELECT description, adresse FROM evenements WHERE id = ?', [$id]);
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
    }
}
