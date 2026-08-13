<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Statistiques de la plateforme, mises en cache fichier (Cache::remember).
 */
final class StatsService
{
    /** Durée de vie du cache (secondes). */
    private const TTL = 60;

    /** Préfixe des clés de cache. */
    private const PREFIX = 'stats:';

    public static function flush(): void
    {
        Cache::flush();
    }

    /**
     * Agrégat complet des statistiques (KPIs + répartitions + tendances).
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        return [
            'kpis'                  => self::kpis(),
            'parStatut'             => self::parStatut(),
            'evolutionMensuelle'    => self::evolutionMensuelle(),
            'topAssociations'       => self::topAssociations(),
            'repartitionCommunes'   => self::repartitionCommunes(),
            'repartitionAnomalies'  => self::repartitionAnomalies(),
            'topEpics'              => self::topEpics(),
            'participationsJour'    => self::participationsQuotidiennes(),
            'demandesParStatut'     => self::demandesParStatut(),
            'tauxParticipation'     => self::tauxParticipation(),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    public static function kpis(): array
    {
        return Cache::remember(self::PREFIX . 'kpis', self::TTL, static function (): array {
            return [
                'evenements'        => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL'),
                'en_attente'        => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_ATTENTE' AND deleted_at IS NULL"),
                'programmes'        => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut IN ('PROGRAMME', 'QR_GENERE') AND deleted_at IS NULL"),
                'en_cours'          => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_COURS' AND deleted_at IS NULL"),
                'termines'          => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'TERMINE' AND deleted_at IS NULL"),
                'refuses'           => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'REFUSE' AND deleted_at IS NULL"),
                'participants'      => (int) Database::value('SELECT COUNT(*) FROM evenement_participant'),
                'citoyens'          => (int) Database::value("SELECT COUNT(*) FROM users WHERE role_user = 'citoyen'"),
                'citoyens_actifs'   => (int) Database::value('SELECT COUNT(DISTINCT user_id) FROM evenement_participant'),
                'associations'      => (int) Database::value('SELECT COUNT(*) FROM associations'),
                'associations_validees' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'),
                'epics'             => (int) Database::value('SELECT COUNT(*) FROM epic'),
                'epics_actifs'      => (int) Database::value("SELECT COUNT(DISTINCT ee.epic_id) FROM evenement_epic ee JOIN evenements e ON e.id = ee.evenement_id WHERE e.statut IN ('EN_ATTENTE', 'PROGRAMME') AND e.deleted_at IS NULL"),
                'demandes'          => (int) Database::value('SELECT COUNT(*) FROM association_requests'),
                'demandes_pending'  => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'pending'"),
                'demandes_approved' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'approved'"),
                'demandes_rejected' => (int) Database::value("SELECT COUNT(*) FROM association_requests WHERE status = 'rejected'"),
                'photos'            => (int) Database::value('SELECT COUNT(*) FROM photos'),
                'albums'            => (int) Database::value('SELECT COUNT(*) FROM albums'),
                'anomalies'         => (int) Database::value('SELECT COUNT(*) FROM anomalies'),
                'communes'          => (int) Database::value('SELECT COUNT(*) FROM commune WHERE is_active = 1'),
                'note_moyenne'      => Database::value('SELECT ROUND(AVG(note), 2) FROM evaluation') ?? null,
                'temps_moyen_epic'  => self::tempsMoyenEpic(),
            ];
        });
    }

    /**
     * Temps moyen entre l'affectation EPIC et la clôture d'un événement (en jours).
     * Aucun cache : calcul à chaque appel sur les données actuelles.
     */
    public static function tempsMoyenEpic(): ?float
    {
        $rows = Database::all(
            "SELECT ee.date_affectation, e.updated_at
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             WHERE e.statut = 'TERMINE' AND ee.date_affectation IS NOT NULL AND e.updated_at IS NOT NULL"
        );

        if ($rows === []) {
            return null;
        }

        $totalDays = 0;
        $count = 0;
        foreach ($rows as $row) {
            $affectation = strtotime($row['date_affectation']);
            $updated = strtotime($row['updated_at']);
            if ($affectation && $updated) {
                $diff = floor(($updated - $affectation) / 86400); // secondes en jour
                $totalDays += $diff;
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : null;
    }

    /**
     * @return array<int, array{statut: string, nb: int}>
     */
    public static function parStatut(): array
    {
        return Cache::remember(self::PREFIX . 'parStatut', self::TTL, static function (): array {
            $rows = Database::all('SELECT statut, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL GROUP BY statut');

            return array_map(
                static fn (array $r): array => ['statut' => (string) $r['statut'], 'nb' => (int) $r['nb']],
                $rows
            );
        });
    }

    /**
     * Évolution mensuelle des événements et des participations (6 derniers mois).
     *
     * @return array<int, array{mois: string, evenements: int, participants: int}>
     */
    public static function evolutionMensuelle(): array
    {
        return Cache::remember(self::PREFIX . 'evolutionMensuelle', self::TTL, static function (): array {
            $mois = [];
            for ($i = 5; $i >= 0; $i--) {
                $mois[date('Y-m', strtotime("first day of -{$i} month"))] = ['mois' => date('Y-m', strtotime("first day of -{$i} month")), 'evenements' => 0, 'participants' => 0];
            }

            foreach (Database::all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb FROM evenements WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mois") as $r) {
                if (isset($mois[$r['mois']])) {
                    $mois[$r['mois']]['evenements'] = (int) $r['nb'];
                }
            }

            foreach (Database::all("SELECT DATE_FORMAT(ep.heure_scan, '%Y-%m') AS mois, COUNT(*) AS nb FROM evenement_participant ep WHERE ep.heure_scan >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY mois") as $r) {
                if (isset($mois[$r['mois']])) {
                    $mois[$r['mois']]['participants'] = (int) $r['nb'];
                }
            }

            return array_values($mois);
        });
    }

    /**
     * Top associations par nombre d'événements.
     *
     * @return array<int, array{nom: string, nb: int}>
     */
    public static function topAssociations(int $limit = 5): array
    {
        return Cache::remember(self::PREFIX . 'topAssociations:' . $limit, self::TTL, static function () use ($limit): array {
            return array_map(
                static fn (array $r): array => ['nom' => (string) $r['nom'], 'nb' => (int) $r['nb']],
                Database::all(
                    'SELECT a.nom, COUNT(e.id) AS nb
                     FROM evenements e
                     JOIN associations a ON a.id = e.association_id
                     WHERE e.deleted_at IS NULL
                     GROUP BY a.id
                     ORDER BY nb DESC LIMIT ?',
                    [$limit]
                )
            );
        });
    }

    /**
     * Répartition des événements par commune.
     *
     * @return array<int, array{nom: string, nb: int}>
     */
    public static function repartitionCommunes(int $limit = 8): array
    {
        return Cache::remember(self::PREFIX . 'repartitionCommunes:' . $limit, self::TTL, static function () use ($limit): array {
            return array_map(
                static fn (array $r): array => ['nom' => (string) ($r['nom'] ?? 'Non renseignée'), 'nb' => (int) $r['nb']],
                Database::all(
                    'SELECT c.nom, COUNT(e.id) AS nb
                     FROM evenements e
                     LEFT JOIN commune c ON c.id = e.commune_id
                     WHERE e.deleted_at IS NULL
                     GROUP BY c.id
                     ORDER BY nb DESC LIMIT ?',
                    [$limit]
                )
            );
        });
    }

    /**
     * Répartition des événements par anomalie signalée.
     *
     * @return array<int, array{nom: string, nb: int}>
     */
    public static function repartitionAnomalies(int $limit = 8): array
    {
        return Cache::remember(self::PREFIX . 'repartitionAnomalies:' . $limit, self::TTL, static function () use ($limit): array {
            return array_map(
                static fn (array $r): array => ['nom' => (string) $r['nom'], 'nb' => (int) $r['nb']],
                Database::all(
                    'SELECT an.nom, COUNT(ae.evenement_id) AS nb
                     FROM anomalies_evenement ae
                     JOIN anomalies an ON an.id = ae.anomalie_id
                     JOIN evenements e ON e.id = ae.evenement_id
                     WHERE e.deleted_at IS NULL
                     GROUP BY an.id
                     ORDER BY nb DESC LIMIT ?',
                    [$limit]
                )
            );
        });
    }

    /**
     * Top EPIC par nombre d'événements affectés.
     *
     * @return array<int, array{nom: string, nb: int}>
     */
    public static function topEpics(int $limit = 5): array
    {
        return Cache::remember(self::PREFIX . 'topEpics:' . $limit, self::TTL, static function () use ($limit): array {
            return array_map(
                static fn (array $r): array => ['nom' => (string) $r['nom'], 'nb' => (int) $r['nb']],
                Database::all(
                    'SELECT ep.nom, COUNT(ee.evenement_id) AS nb
                     FROM evenement_epic ee
                     JOIN epic ep ON ep.id = ee.epic_id
                     JOIN evenements e ON e.id = ee.evenement_id
                     WHERE e.deleted_at IS NULL
                     GROUP BY ep.id
                     ORDER BY nb DESC LIMIT ?',
                    [$limit]
                )
            );
        });
    }

    /**
     * Participations enregistrées par jour (14 derniers jours).
     *
     * @return array<int, array{jour: string, nb: int}>
     */
    public static function participationsQuotidiennes(int $jours = 14): array
    {
        return Cache::remember(self::PREFIX . 'participationsQuotidiennes:' . $jours, self::TTL, static function () use ($jours): array {
            $rows = Database::all(
                "SELECT DATE(ep.heure_scan) AS jour, COUNT(*) AS nb
                 FROM evenement_participant ep
                 WHERE ep.heure_scan >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY jour ORDER BY jour",
                [$jours]
            );

            return array_map(
                static fn (array $r): array => ['jour' => (string) $r['jour'], 'nb' => (int) $r['nb']],
                $rows
            );
        });
    }

    /**
     * Demandes d'inscription par statut.
     *
     * @return array<int, array{status: string, nb: int}>
     */
    public static function demandesParStatut(): array
    {
        return Cache::remember(self::PREFIX . 'demandesParStatut', self::TTL, static function (): array {
            return array_map(
                static fn (array $r): array => ['status' => (string) $r['status'], 'nb' => (int) $r['nb']],
                Database::all('SELECT status, COUNT(*) AS nb FROM association_requests GROUP BY status')
            );
        });
    }

    /**
     * Taux de participation global (participants / citoyens inscrits).
     */
    public static function tauxParticipation(): float
    {
        return Cache::remember(self::PREFIX . 'tauxParticipation', self::TTL, static function (): float {
            $participants = (int) Database::value('SELECT COUNT(*) FROM evenement_participant');
            $citoyens     = (int) Database::value("SELECT COUNT(*) FROM users WHERE role_user = 'citoyen'");

            return $citoyens > 0 ? round(($participants / $citoyens) * 100, 1) : 0.0;
        });
    }

    /**
     * Rapport CSV complet des statistiques (BOM UTF-8).
     */
    public static function csv(): string
    {
        $out = fopen('php://temp', 'r+');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Wilaya Harmonia — Rapport statistique', date('d/m/Y H:i')], ';', '"', '\\');
        fputcsv($out, [], ';', '"', '\\');

        $kpis = self::kpis();
        $labels = [
            'evenements' => 'Événements (total)', 'en_attente' => '  En attente',
            'programmes' => '  Programmés', 'en_cours' => '  En cours', 'termines' => '  Terminés',
            'refuses' => '  Refusés', 'participants' => 'Participations', 'citoyens' => 'Citoyens inscrits',
            'citoyens_actifs' => 'Citoyens actifs', 'associations' => 'Associations (total)',
            'associations_validees' => '  Validées', 'epics' => 'EPIC', 'epics_actifs' => 'EPIC actifs',
            'demandes' => 'Demandes inscription', 'demandes_pending' => '  En attente',
            'demandes_approved' => '  Approuvées', 'demandes_rejected' => '  Refusées',
            'photos' => 'Photos', 'albums' => 'Albums', 'anomalies' => 'Anomalies', 'communes' => 'Communes',
            'note_moyenne' => 'Note moyenne',
        ];

        fputcsv($out, ['Indicateur', 'Valeur'], ';', '"', '\\');
        foreach ($labels as $cle => $lib) {
            fputcsv($out, [$lib, $kpis[$cle] ?? ''], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Répartition par statut'], ';', '"', '\\');
        fputcsv($out, ['Statut', 'Nombre'], ';', '"', '\\');
        foreach (self::parStatut() as $r) {
            fputcsv($out, [statut_label((string) $r['statut']), $r['nb']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Évolution mensuelle'], ';', '"', '\\');
        fputcsv($out, ['Mois', 'Événements', 'Participations'], ';', '"', '\\');
        foreach (self::evolutionMensuelle() as $r) {
            fputcsv($out, [$r['mois'], $r['evenements'], $r['participants']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Top associations'], ';', '"', '\\');
        fputcsv($out, ['Association', 'Événements'], ';', '"', '\\');
        foreach (self::topAssociations() as $r) {
            fputcsv($out, [$r['nom'], $r['nb']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Répartition par commune'], ';', '"', '\\');
        fputcsv($out, ['Commune', 'Événements'], ';', '"', '\\');
        foreach (self::repartitionCommunes() as $r) {
            fputcsv($out, [$r['nom'], $r['nb']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Répartition par anomalie'], ';', '"', '\\');
        fputcsv($out, ['Anomalie', 'Événements'], ';', '"', '\\');
        foreach (self::repartitionAnomalies() as $r) {
            fputcsv($out, [$r['nom'], $r['nb']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Demandes d\'inscription par statut'], ';', '"', '\\');
        fputcsv($out, ['Statut', 'Nombre'], ';', '"', '\\');
        $statusLabels = ['pending' => 'En attente', 'approved' => 'Approuvée', 'rejected' => 'Refusée'];
        foreach (self::demandesParStatut() as $r) {
            fputcsv($out, [$statusLabels[$r['status']] ?? $r['status'], $r['nb']], ';', '"', '\\');
        }

        rewind($out);

        return (string) stream_get_contents($out);
    }
}
