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
        'EN_ATTENTE'            => ['MODIFICATION_DEMANDEE', 'VALIDÉ', 'PROGRAMME', 'REFUSE'],
        'MODIFICATION_DEMANDEE' => ['EN_ATTENTE', 'REFUSE'],
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
        QrCodeGenerator::createForEvenement($id, $date, $heure);
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
     */
    public static function regenQr(int $evenementId): array
    {
        $event = Database::one('SELECT date_evenement, heure FROM evenements WHERE id = ?', [$evenementId]);

        return QrCodeGenerator::createForEvenement(
            $evenementId,
            (string) ($event['date_evenement'] ?? ''),
            (string) ($event['heure'] ?? '00:00:00')
        );
    }
}
