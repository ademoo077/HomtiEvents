<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Agrégation des données de la page publique « Actualités & événements à venir ».
 *
 * Approche hybride :
 *  - les actualités viennent exclusivement du CMS landing_news (statut = publie,
 *    non supprimées) ;
 *  - les événements sont auto-synchronisés depuis la table structurée `evenements`
 *    (statuts PROGRAMME / QR_GENERE, non archivés, à venir) ;
 *  - le CMS peut ajouter un événement « saisie libre » (landing_news.type =
 *    'evenement' sans evenement_id) ou « lier » un événement structuré
 *    (evenement_id renseigné) pour en forcer la mise en avant avec un contenu
 *    éditorial (image, titre, description) sans le dupliquer dans la grille.
 */
final class ActualiteService
{
    /**
     * Statuts des événements structurés affichés sur la page publique.
     */
    public const STATUTS_SYNC = ['PROGRAMME', 'QR_GENERE'];

    /**
     * Rassemble les données nécessaires au rendu de la page /actualites.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        // 1. Actualités publiées (les plus récentes d'abord)
        $actualites = Database::all(
            "SELECT * FROM landing_news
             WHERE type = 'actualite' AND statut = 'publie' AND deleted_at IS NULL
             ORDER BY date_event DESC, sort_order ASC, id DESC"
        );

        // 2. Événements « saisie libre » du CMS (non liés à un événement structuré)
        $manuels = Database::all(
            "SELECT * FROM landing_news
             WHERE type = 'evenement' AND evenement_id IS NULL
               AND statut = 'publie' AND deleted_at IS NULL
             ORDER BY date_event ASC, sort_order ASC, id ASC"
        );

        // 3. Événements « liés » (curation) : masquent leur événement structuré
        $lies = Database::all(
            "SELECT * FROM landing_news
             WHERE type = 'evenement' AND evenement_id IS NOT NULL
               AND statut = 'publie' AND deleted_at IS NULL"
        );

        $exclus = [];
        foreach ($lies as $lie) {
            if ($lie['evenement_id'] !== null) {
                $exclus[] = (int) $lie['evenement_id'];
            }
        }

        // 4. Événements structurés auto-synchronisés (à venir, non archivés)
        $in      = implode(',', array_map(static fn (string $s): string => "'{$s}'", self::STATUTS_SYNC));
        $exclude = '';
        $params  = [];
        if ($exclus !== []) {
            $exclude = ' AND e.id NOT IN (' . implode(',', array_fill(0, count($exclus), '?')) . ')';
            $params  = $exclus;
        }

        $synced = Database::all(
            "SELECT e.id, e.adresse, e.date_evenement, e.heure, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.statut IN ({$in})
               AND e.deleted_at IS NULL
               AND e.date_evenement >= CURDATE()
               {$exclude}
             ORDER BY e.date_evenement ASC, e.heure ASC, e.id ASC
             LIMIT 30",
            $params
        );

        $evenements = [];

        foreach ($manuels as $row) {
            $evenements[] = self::card($row, 'evenement', 'cms');
        }

        foreach ($lies as $row) {
            // Les métadonnées (date / lieu) manquantes sont reprises de l'événement lié.
            $evenement = Database::one(
                'SELECT e.date_evenement, e.heure, c.nom AS commune_nom
                 FROM evenements e
                 LEFT JOIN commune c ON c.id = e.commune_id
                 WHERE e.id = ?',
                [(int) $row['evenement_id']]
            );

            if ($evenement !== null) {
                $row['date_event'] = $row['date_event'] ?? $evenement['date_evenement'];
                $row['heure']      = $row['heure'] ?? $evenement['heure'] ?? null;
                $row['lieu']       = $row['lieu'] ?? $evenement['commune_nom'] ?? null;
            }

            $evenements[] = self::card($row, 'evenement', 'cms');
        }

        foreach ($synced as $row) {
            // Normalisation : la table evenements expose adresse / date_evenement,
            // la carte d'affichage attend titre_fr / date_event.
            $row['titre_fr']     = (string) ($row['adresse'] ?? '');
            $row['date_event']   = $row['date_evenement'] ?? null;
            $row['evenement_id'] = (int) $row['id'];
            $row['titre_ar']     = null;
            $row['lieu']         = $row['commune_nom'] ?? null;
            $row['lieu_ar']      = null;

            $evenements[] = self::card($row, 'evenement', 'evenement');
        }

        // Tri chronologique des événements (sans date en fin de liste)
        usort($evenements, static function (array $a, array $b): int {
            $da = $a['date_event'] ?? null;
            $db = $b['date_event'] ?? null;
            if ($da === null && $db === null) {
                return 0;
            }
            if ($da === null) {
                return 1;
            }
            if ($db === null) {
                return -1;
            }

            return strcmp((string) $da, (string) $db);
        });

        $prochains = array_slice(array_values(array_filter($evenements, static fn (array $c): bool => $c['date_event'] !== null)), 0, 6);

        return [
            'actualites' => array_map(static fn (array $row): array => self::card($row, 'actualite', 'cms'), $actualites),
            'evenements' => $evenements,
            'items'      => [...array_map(static fn (array $row): array => self::card($row, 'actualite', 'cms'), $actualites), ...$evenements],
            'prochains'  => $prochains,
            'theme'      => LandingService::theme(),
        ];
    }

    /**
     * Normalise une ligne landing_news en carte d'affichage.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private static function card(array $row, string $type, string $source): array
    {
        return [
            'id'            => (int) $row['id'],
            'type'          => $type,
            'source'        => $source,
            'evenement_id'  => isset($row['evenement_id']) ? (int) $row['evenement_id'] : null,
            'titre_fr'      => (string) ($row['titre_fr'] ?? ''),
            'titre_ar'      => $row['titre_ar'] ?? null,
            'description_fr' => $row['description_fr'] ?? null,
            'description_ar' => $row['description_ar'] ?? null,
            'image'         => $row['image'] ?? null,
            'date_event'    => $row['date_event'] ?? null,
            'heure'         => $row['heure'] ?? null,
            'lieu'          => $row['lieu'] ?? null,
            'lieu_ar'       => $row['lieu_ar'] ?? null,
            'url_externe'   => $row['url_externe'] ?? null,
        ];
    }
}
