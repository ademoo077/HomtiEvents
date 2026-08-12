<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Routage automatique des réclamations (événements) vers les organisations.
 *
 * Ordre de priorité (adapté au schéma réel du projet) :
 *   1. anomalie — une règle de routage existe pour un type d'anomalie
 *                 lié à l'événement (routing_rules.anomalie_id, sans daira).
 *   2. daira    — une règle combine type d'anomalie + daira de la commune
 *                 de l'événement (routing_rules.ca_id = commune.ca_id).
 *   3. fallback — aucune règle : assigned_org_id = NULL + alerte admin
 *                 (routing_alertes + notification rôle wilaya).
 *
 * Les règles sont chargées en cache fichier (TTL 1 h) pour éviter les
 * requêtes à chaque création ; toute modification invalide le cache.
 */
final class RoutingService
{
    public const CACHE_KEY = 'routing.rules.v1';
    public const CACHE_TTL = 3600;

    public const RULE_ANOMALIE = 'anomalie';
    public const RULE_DAIRA    = 'daira';
    public const RULE_MANUEL   = 'manuel';
    public const RULE_AUCUNE   = 'aucune';

    /**
     * Règles de routage actives, depuis le cache (1 h).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function regles(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function (): array {
            return Database::all(
                'SELECT r.*, a.nom AS anomalie_nom, ca.nom AS ca_nom, ep.nom AS epic_nom
                 FROM routing_rules r
                 LEFT JOIN anomalies a ON a.id = r.anomalie_id
                 LEFT JOIN ca ON ca.id = r.ca_id
                 LEFT JOIN epic ep ON ep.id = r.epic_id
                 WHERE r.actif = 1
                 ORDER BY r.priorite DESC, r.anomalie_id ASC, r.id ASC'
            );
        });
    }

    /**
     * Invalide le cache des règles (après un CRUD).
     */
    public static function invaliderCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Aperçu du routage (prévision) pour un formulaire — sans créer l'événement.
     *
     * Reproduit l'ordre de priorité de resoudre() à partir de la commune et des
     * anomalies sélectionnées par l'association.
     *
     * @param int[] $anomalieIds
     * @return array{epic_id: int|null, epic_nom: string|null, rule_matched: string, detail: string}
     */
    public static function preview(int $communeId, array $anomalieIds): array
    {
        $anomalieIds = array_values(array_map('intval', array_filter($anomalieIds, static fn ($id) => $id > 0)));
        $anomalieIds = array_unique($anomalieIds);

        if ($anomalieIds === []) {
            return ['epic_id' => null, 'epic_nom' => null, 'rule_matched' => self::RULE_AUCUNE, 'detail' => 'Aucune anomalie sélectionnée.'];
        }

        $rules = array_values(array_filter(self::regles(), static fn (array $r): bool => (int) $r['anomalie_id'] > 0));

        // Priorité 1 : règle sur le type d'anomalie (sans daira)
        foreach ($rules as $rule) {
            if ($rule['ca_id'] !== null) {
                continue;
            }
            if (in_array((int) $rule['anomalie_id'], $anomalieIds, true)) {
                return self::previewResult($rule, self::RULE_ANOMALIE);
            }
        }

        // Priorité 2 : règle type d'anomalie + daira de la commune
        $caId = $communeId > 0 ? Database::value('SELECT ca_id FROM commune WHERE id = ?', [$communeId]) : null;
        if ($caId !== null) {
            foreach ($rules as $rule) {
                if ((int) $rule['ca_id'] !== (int) $caId) {
                    continue;
                }
                if (in_array((int) $rule['anomalie_id'], $anomalieIds, true)) {
                    return self::previewResult($rule, self::RULE_DAIRA);
                }
            }
        }

        return ['epic_id' => null, 'epic_nom' => null, 'rule_matched' => self::RULE_AUCUNE, 'detail' => 'Aucune règle ne couvre les anomalies sélectionnées.'];
    }

    /**
     * @param array<string, mixed> $rule
     * @return array{epic_id: int|null, epic_nom: string|null, rule_matched: string, detail: string}
     */
    private static function previewResult(array $rule, string $ruleMatched): array
    {
        $epicId = (int) $rule['epic_id'];
        $epicNom = $epicId > 0 ? (string) Database::value('SELECT nom FROM epic WHERE id = ?', [$epicId]) : null;

        return [
            'epic_id'      => $epicId > 0 ? $epicId : null,
            'epic_nom'     => $epicNom,
            'rule_matched' => $ruleMatched,
            'detail'       => 'Règle #' . (int) $rule['id'] . ' (anomalie #' . (int) $rule['anomalie_id'] . ')',
        ];
    }

    /**
     * Résout l'organisation cible pour un événement.
     *
     * @param array<string, mixed> $event événement complet (id, commune_id requis)
     * @return array{epic_id: int|null, rule_matched: string, detail: string}
     */
    public static function resoudre(array $event): array
    {
        $anomalies = array_map(
            static fn (array $r): int => (int) $r['anomalie_id'],
            Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [(int) $event['id']])
        );

        if ($anomalies === []) {
            return ['epic_id' => null, 'rule_matched' => self::RULE_AUCUNE, 'detail' => 'Aucune anomalie liée à l\'événement.'];
        }

        $rules = array_values(array_filter(self::regles(), static fn (array $r): bool => (int) $r['anomalie_id'] > 0));

        // ── Priorité 1 : règle sur le type d'anomalie (sans daira) ──
        foreach ($rules as $rule) {
            if ($rule['ca_id'] !== null) {
                continue;
            }
            if (in_array((int) $rule['anomalie_id'], $anomalies, true)) {
                return [
                    'epic_id'      => (int) $rule['epic_id'],
                    'rule_matched' => self::RULE_ANOMALIE,
                    'detail'       => 'Règle #' . (int) $rule['id'] . ' (anomalie #' . (int) $rule['anomalie_id'] . ')',
                ];
            }
        }

        // ── Priorité 2 : règle type d'anomalie + daira de la commune ──
        $caId = null;
        if (! empty($event['commune_id'])) {
            $caId = Database::value('SELECT ca_id FROM commune WHERE id = ?', [(int) $event['commune_id']]);
        }
        if ($caId !== null) {
            foreach ($rules as $rule) {
                if ((int) $rule['ca_id'] !== (int) $caId) {
                    continue;
                }
                if (in_array((int) $rule['anomalie_id'], $anomalies, true)) {
                    return [
                        'epic_id'      => (int) $rule['epic_id'],
                        'rule_matched' => self::RULE_DAIRA,
                        'detail'       => 'Règle #' . (int) $rule['id'] . ' (anomalie #' . (int) $rule['anomalie_id'] . ' · daira #' . (int) $caId . ')',
                    ];
                }
            }
        }

        // ── Priorité 3 : aucune règle → non assigné ──
        return [
            'epic_id'      => null,
            'rule_matched' => self::RULE_AUCUNE,
            'detail'       => 'Aucune règle pour les anomalies [' . implode(',', $anomalies) . ']',
        ];
    }

    /**
     * Assignation automatique (règles) d'un événement, avec traçabilité.
     * Se déclenche à la création et à la modification des anomalies.
     *
     * @return array<string, mixed> résultat du routage
     */
    public static function assignOrganization(int $evenementId): array
    {
        $event = Database::one(
            'SELECT e.*, c.ca_id FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.id = ?',
            [$evenementId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $resolution = self::resoudre($event);

        return self::appliquer($evenementId, $resolution['epic_id'], $resolution['rule_matched'], $resolution['detail'], (int) ($event['assigned_org_id'] ?? 0));
    }

    /**
     * Réaffectation manuelle par un administrateur (bouton « Réaffecter »).
     *
     * @param array<int> $newEpicIds EPICs sélectionnés (users.epic_id)
     * @return array<string, mixed> résultat du routage
     */
    public static function reaffecter(int $evenementId, array $newEpicIds, string $motif = ''): array
    {
        $old = (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$evenementId]);
        $detail = trim($motif) !== '' ? 'Manuel : ' . $motif : 'Manuel par administrateur';

        return self::appliquer($evenementId, $newEpicIds !== [] ? (int) $newEpicIds[0] : null, self::RULE_MANUEL, $detail, $old);
    }

    /**
     * Applique (ou non) un changement d'organisation avec journalisation,
     * notification et alerte admin en cas de fallback.
     *
     * @return array<string, mixed>
     */
    private static function appliquer(int $evenementId, ?int $newEpicId, string $rule, string $detail, int $oldOrgId): array
    {
        $old = $oldOrgId > 0 ? $oldOrgId : null;
        $new = ($newEpicId ?? 0) > 0 ? $newEpicId : null;

        $log = [
            'evenement_id'  => $evenementId,
            'old_org_id'    => $old,
            'new_org_id'    => $new,
            'rule_matched'  => $rule,
            'detail'        => mb_substr($detail, 0, 250),
        ];

        // Changement effectif d'organisation.
        if (($old ?? 0) !== ($new ?? 0)) {
            Database::update('evenements', ['assigned_org_id' => $new], 'id = ?', [$evenementId]);

            Database::insert('routing_log', $log);

            AuditLog::log('evenement_routage', 'evenement', $evenementId, [
                'assigned_org_id' => $old,
                'rule_matched'    => $rule,
            ], [
                'assigned_org_id' => $new,
                'rule_matched'    => $rule,
                'detail'          => $log['detail'],
            ]);
        }

        // Notification de l'organisation assignée.
        if ($new !== null) {
            $epic = Database::one('SELECT id, nom FROM epic WHERE id = ?', [$new]);
            if ($epic !== null) {
                Notification::sendToEpic(
                    $new,
                    'Nouvel événement routé',
                    'Un événement vous a été routé (règle : ' . $rule . ').',
                    'evenement_routage',
                    ['evenement_id' => $evenementId]
                );
            }
        }

        // Fallback : aucune organisation trouvée → alerte admin.
        if ($new === null) {
            Database::insert('routing_alertes', [
                'evenement_id' => $evenementId,
                'motif'        => $log['detail'],
                'traite'       => 0,
            ]);
            Notification::sendToRole(
                'wilaya',
                'Événement non routé',
                'Aucune règle de routage pour l\'événement #' . $evenementId . '.',
                'routing_alerte',
                ['evenement_id' => $evenementId]
            );
        }

        return [
            'evenement_id'  => $evenementId,
            'epic_id'       => $new,
            'rule_matched'  => $rule,
            'detail'        => $log['detail'],
            'change'        => ($old ?? 0) !== ($new ?? 0),
        ];
    }

    /**
     * Répartition des événements par organisation (admin).
     *
     * @return array<int, array{org: string, nb: int}>
     */
    public static function repartition(): array
    {
        $rows = Database::all(
            'SELECT e.assigned_org_id, ep.nom AS org, COUNT(*) AS nb
             FROM evenements e
             LEFT JOIN epic ep ON ep.id = e.assigned_org_id
             WHERE e.deleted_at IS NULL
             GROUP BY e.assigned_org_id, ep.nom
             ORDER BY nb DESC'
        );

        return array_map(static function (array $r): array {
            return [
                'org' => $r['org'] !== null ? (string) $r['org'] : 'Non assigné',
                'nb'  => (int) $r['nb'],
            ];
        }, $rows);
    }

    /**
     * « Nouveaux événements routés » (dernières 48 h) pour une EPIC.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function nouveauxRoutages(int $epicId, int $heures = 48): array
    {
        return Database::all(
            'SELECT rl.evenement_id, rl.rule_matched, rl.created_at,
                    e.adresse, e.statut, c.nom AS commune_nom
             FROM routing_log rl
             JOIN evenements e ON e.id = rl.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE rl.new_org_id = ?
               AND rl.created_at >= NOW() - INTERVAL ? HOUR
             ORDER BY rl.created_at DESC
             LIMIT 10',
            [$epicId, (int) $heures]
        );
    }

    /**
     * Alertes admin non traitées (fallback de routage).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function alertesNonTraitees(int $limit = 50): array
    {
        return Database::all(
            'SELECT ra.*, e.adresse, c.nom AS commune_nom
             FROM routing_alertes ra
             LEFT JOIN evenements e ON e.id = ra.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ra.traite = 0
             ORDER BY ra.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    /**
     * Marque une alerte comme traitée.
     */
    public static function alerteTraiter(int $alerteId): void
    {
        Database::update('routing_alertes', [
            'traite'     => 1,
            'traite_par' => Session::userId(),
            'traite_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$alerteId]);
    }

    // ── CRUD règles de routage (back-office) ─────────────────────────

    /**
     * Enregistre ou met à jour une règle de routage.
     *
     * @param array<string, mixed> $d
     */
    public static function regleEnregistrer(array $d, ?int $id = null): int
    {
        $data = [
            'anomalie_id' => (int) ($d['anomalie_id'] ?? 0) ?: null,
            'ca_id'       => (int) ($d['ca_id'] ?? 0) ?: null,
            'epic_id'     => (int) ($d['epic_id'] ?? 0),
            'priorite'    => (int) ($d['priorite'] ?? 0),
            'actif'       => (int) ($d['actif'] ?? 1),
        ];

        if ($data['epic_id'] <= 0) {
            abort(422, 'Organisation cible obligatoire.');
        }

        if ($id !== null) {
            Database::update('routing_rules', $data, 'id = ?', [$id]);
            AuditLog::log('routing.rule_update', 'routing_rules', $id, null, $data);
            self::invaliderCache();

            return $id;
        }

        $newId = Database::insert('routing_rules', $data);
        AuditLog::log('routing.rule_create', 'routing_rules', $newId, null, $data);
        self::invaliderCache();

        return $newId;
    }

    public static function regleSupprimer(int $id): void
    {
        Database::delete('routing_rules', 'id = ?', [$id]);
        AuditLog::log('routing.rule_delete', 'routing_rules', $id, null, null);
        self::invaliderCache();
    }

    public static function regleBasculer(int $id, bool $actif): void
    {
        Database::update('routing_rules', ['actif' => $actif ? 1 : 0], 'id = ?', [$id]);
        AuditLog::log('routing.rule_toggle', 'routing_rules', $id, null, ['actif' => $actif ? 1 : 0]);
        self::invaliderCache();
    }
}
