<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Statistiques du tableau de bord EPIC.
 *
 * Périmètre : événements affectés à l'EPIC connectée (evenement_epic.epic_id),
 * soit la « zone » de l'EPIC dans cette plateforme (pas de colonne zone_id).
 *
 * Contrainte : 2 requêtes GROUP BY au maximum (KPIs par statut, anomalies par motif).
 */
final class EpicDashboardService
{
    /** Statuts comptés comme « programmés » (programmé + QR généré). */
    public const PROGRAMMES = ['PROGRAMME', 'QR_GENERE'];

    /** Statuts actifs affichés au calendrier. */
    public const CALENDRIER_STATUTS = ['PROGRAMME', 'QR_GENERE', 'EN_COURS'];

    /** Statuts considérés comme des anomalies à traiter. */
    public const ANOMALIES = ['MODIFICATION_DEMANDEE', 'REFUSE'];

    /** Largeur de la fenêtre « À venir » (jours). */
    public const AVENIR_JOURS = 3;

    /** Nombre maximum d'événements « À venir ». */
    public const AVENIR_LIMITE = 5;

    /** Seuil du badge d'alerte anomalies non traitées. */
    public const ALERTE_SEUIL = 3;

    /**
     * Filtres combinables appliqués au périmètre EPIC.
     *
     * @param array<string, mixed> $f clés possibles : du, au, commune_id
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function scope(int $epicId, array $f = []): array
    {
        $where = [
            'e.deleted_at IS NULL',
            'e.assigned_org_id = ?',
        ];
        $params = [$epicId];

        if (! empty($f['du'])) {
            $where[] = 'e.date_evenement >= ?';
            $params[] = (string) $f['du'];
        }
        if (! empty($f['au'])) {
            $where[] = 'e.date_evenement <= ?';
            $params[] = (string) $f['au'];
        }
        if (! empty($f['commune_id'])) {
            $where[] = 'e.commune_id = ?';
            $params[] = (int) $f['commune_id'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * KPIs « Événements attribués » par statut + total (1 requête GROUP BY).
     *
     * @param array<string, mixed> $f
     * @return array<string, int>
     */
    public static function kpis(int $epicId, array $f = []): array
    {
        [$where, $params] = self::scope($epicId, $f);

        $rows = Database::all(
            'SELECT e.statut, COUNT(*) AS nb FROM evenements e
             WHERE ' . $where . '
             GROUP BY e.statut',
            $params
        );

        $counts = [
            'total'       => 0,
            'VALIDÉ'      => 0,
            'PROGRAMME'   => 0,
            'EN_COURS'    => 0,
            'TERMINE'     => 0,
            'REFUSE'      => 0,
            'EN_ATTENTE'  => 0,
            'MODIFICATION_DEMANDEE' => 0,
        ];

        foreach ($rows as $row) {
            $statut = (string) $row['statut'];
            $nb = (int) $row['nb'];
            $counts['total'] += $nb;

            if ($statut === 'PROGRAMME' || $statut === 'QR_GENERE') {
                $counts['PROGRAMME'] += $nb;
            } elseif (isset($counts[$statut])) {
                $counts[$statut] += $nb;
            }
        }

        return $counts;
    }

    /**
     * Événements actifs (PROGRAMME / QR_GENERE / EN_COURS) d'une période.
     * Une seule requête, exploitée par le calendrier et l'API.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function evenementsPeriode(int $epicId, string $du, string $au, array $f = []): array
    {
        [$where, $params] = self::scope($epicId, $f);

        $statuts = implode(',', array_fill(0, count(self::CALENDRIER_STATUTS), '?'));
        $params = array_merge($params, [$du, $au], self::CALENDRIER_STATUTS);

        $sql = 'SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure, e.motif_refus,
                       c.nom AS commune_nom, a.nom AS association_nom,
                       q.token_qr, q.date_expiration
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                LEFT JOIN qr_event q ON q.evenement_id = e.id
                WHERE ' . $where . '
                  AND e.date_evenement BETWEEN ? AND ?
                  AND e.statut IN (' . $statuts . ')
                ORDER BY e.date_evenement ASC, e.heure ASC';

        return Database::all($sql, $params);
    }

    /**
     * Événements actifs d'un mois donné, indexés par jour (pour la grille).
     *
     * @return array<string, array<int, array<string, mixed>>> clé = jour 'Y-m-d'
     */
    public static function evenementsParJour(int $epicId, string $mois, array $f = []): array
    {
        $du = $mois . '-01';
        $au = date('Y-m-t', strtotime($du . ' +0 days'));

        $parJour = [];
        foreach (self::evenementsPeriode($epicId, $du, $au, $f) as $event) {
            $jour = (string) ($event['date_evenement'] ?? '');
            $parJour[$jour][] = $event;
        }

        return $parJour;
    }

    /**
     * Widget « À venir » : les N prochains événements dans les X jours.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function aVenir(int $epicId, array $f = []): array
    {
        $du = date('Y-m-d');
        $au = date('Y-m-d', strtotime('+' . self::AVENIR_JOURS . ' days'));

        $events = self::evenementsPeriode($epicId, $du, $au, $f);

        return array_slice($events, 0, self::AVENIR_LIMITE);
    }

    /**
     * Répartition des anomalies (MODIFICATION_DEMANDEE / REFUSE) par motif.
     * 1 requête GROUP BY, motifs normalisés en catégories lisibles.
     *
     * @param array<string, mixed> $f
     * @return array<int, array{motif: string, nb: int}> trié par nombre décroissant
     */
    public static function anomaliesParMotif(int $epicId, array $f = []): array
    {
        [$where, $params] = self::scope($epicId, $f);

        $statuts = implode(',', array_fill(0, count(self::ANOMALIES), '?'));
        $params = array_merge($params, self::ANOMALIES);

        $rows = Database::all(
            'SELECT e.motif_refus, COUNT(*) AS nb
             FROM evenements e
             WHERE ' . $where . '
               AND e.statut IN (' . $statuts . ')
             GROUP BY e.motif_refus',
            $params
        );

        $groupes = [];
        foreach ($rows as $row) {
            $motif = (string) ($row['motif_refus'] ?? '');
            $categorie = self::normaliseMotif($motif);
            $groupes[$categorie] = ($groupes[$categorie] ?? 0) + (int) $row['nb'];
        }

        arsort($groupes);

        $result = [];
        foreach ($groupes as $motif => $nb) {
            $result[] = ['motif' => $motif, 'nb' => $nb];
        }

        return $result;
    }

    /**
     * Badge d'alerte : nombre d'anomalies non traitées (statut anomalie)
     * rencontrées / signalées durant les N derniers jours.
     */
    public static function anomaliesNonTraitees(int $epicId, int $jours = 7): int
    {
        [$where, $params] = self::scope($epicId);

        $statuts = implode(',', array_fill(0, count(self::ANOMALIES), '?'));
        $params = array_merge($params, self::ANOMALIES);

        return (int) Database::value(
            'SELECT COUNT(*) FROM evenements e
             WHERE ' . $where . '
               AND e.statut IN (' . $statuts . ')
               AND e.updated_at >= NOW() - INTERVAL ? DAY',
            array_merge($params, [$jours])
        );
    }

    /**
     * Nombre d'événements programmés sans QR généré (à générer).
     */
    public static function aGenererQr(int $epicId): int
    {
        [$where, $params] = self::scope($epicId);

        return (int) Database::value(
            'SELECT COUNT(*)
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE ' . $where . '
               AND e.statut = ?
               AND (q.token_qr IS NULL OR q.token_qr = \'\')',
            array_merge($params, ['PROGRAMME'])
        );
    }

    /**
     * Liste des communes (pour le filtre de la vue).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function communes(): array
    {
        return Database::all('SELECT id, nom FROM commune WHERE is_active = 1 ORDER BY nom');
    }

    /**
     * Normalise un motif libre en catégorie lisible.
     */
    public static function normaliseMotif(string $motif): string
    {
        $m = mb_strtolower($motif, 'UTF-8');

        return match (true) {
            str_contains($m, 'date')          => 'Date invalide',
            str_contains($m, 'lieu'), str_contains($m, 'adresse') => 'Lieu inexact',
            str_contains($m, 'pièce'), str_contains($m, 'piece'), str_contains($m, 'document'), str_contains($m, 'agrément'), str_contains($m, 'agrement') => 'Pièce manquante',
            str_contains($m, 'dossier'), str_contains($m, 'juridique') => 'Dossier incomplet',
            default                           => 'Autre motif',
        };
    }
}
