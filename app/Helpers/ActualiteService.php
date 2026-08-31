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
     * @param array<string, mixed> $filters Filtres optionnels (q, du, au, commune_id, type)
     * @return array<string, mixed>
     */
    public static function data(array $filters = []): array
    {
        $q          = trim((string) ($filters['q'] ?? ''));
        $du         = (string) ($filters['du'] ?? '');
        $au         = (string) ($filters['au'] ?? '');
        $communeId  = (int) ($filters['commune_id'] ?? 0);
        $typeFilter = (string) ($filters['type'] ?? '');

        // 1. Actualités publiées (les plus récentes d'abord)
        $actSql  = "SELECT * FROM landing_news
                    WHERE type = 'actualite' AND statut = 'publie' AND deleted_at IS NULL";
        $actParams = [];

        if ($q !== '') {
            $actSql .= ' AND (titre_fr LIKE ? OR titre_ar LIKE ? OR description_fr LIKE ? OR lieu LIKE ?)';
            $like = '%' . $q . '%';
            $actParams = array_merge($actParams, [$like, $like, $like, $like]);
        }
        if ($du !== '') {
            $actSql .= ' AND date_event >= ?';
            $actParams[] = $du;
        }
        if ($au !== '') {
            $actSql .= ' AND date_event <= ?';
            $actParams[] = $au;
        }
        $actSql .= ' ORDER BY date_event DESC, sort_order ASC, id DESC';
        $actualites = Database::all($actSql, $actParams);

        // 2. Événements « saisie libre » du CMS (non liés à un événement structuré)
        $manSql  = "SELECT * FROM landing_news
                    WHERE type = 'evenement' AND evenement_id IS NULL
                      AND statut = 'publie' AND deleted_at IS NULL";
        $manParams = [];

        if ($q !== '') {
            $manSql .= ' AND (titre_fr LIKE ? OR titre_ar LIKE ? OR description_fr LIKE ? OR lieu LIKE ?)';
            $like = '%' . $q . '%';
            $manParams = array_merge($manParams, [$like, $like, $like, $like]);
        }
        if ($du !== '') {
            $manSql .= ' AND date_event >= ?';
            $manParams[] = $du;
        }
        if ($au !== '') {
            $manSql .= ' AND date_event <= ?';
            $manParams[] = $au;
        }
        $manSql .= ' ORDER BY date_event ASC, sort_order ASC, id ASC';
        $manuels = Database::all($manSql, $manParams);

        // 3. Événements « liés » (curation) : masquent leur événement structuré
        $liesSql  = "SELECT * FROM landing_news
                     WHERE type = 'evenement' AND evenement_id IS NOT NULL
                       AND statut = 'publie' AND deleted_at IS NULL";
        $liesParams = [];

        if ($q !== '') {
            $liesSql .= ' AND (titre_fr LIKE ? OR titre_ar LIKE ? OR description_fr LIKE ? OR lieu LIKE ?)';
            $like = '%' . $q . '%';
            $liesParams = array_merge($liesParams, [$like, $like, $like, $like]);
        }
        if ($du !== '') {
            $liesSql .= ' AND date_event >= ?';
            $liesParams[] = $du;
        }
        if ($au !== '') {
            $liesSql .= ' AND date_event <= ?';
            $liesParams[] = $au;
        }
        $lies = Database::all($liesSql, $liesParams);

        $exclus = [];
        foreach ($lies as $lie) {
            if ($lie['evenement_id'] !== null) {
                $exclus[] = (int) $lie['evenement_id'];
            }
        }

        // 4. Événements structurés auto-synchronisés (à venir, non archivés)
        $in      = implode(',', array_map(static fn (string $s): string => "'{$s}'", self::STATUTS_SYNC));
        $exclude = '';
        $syncParams = [];
        if ($exclus !== []) {
            $exclude = ' AND e.id NOT IN (' . implode(',', array_fill(0, count($exclus), '?')) . ')';
            $syncParams = $exclus;
        }

        $syncSql = "SELECT e.id, e.adresse, e.date_evenement, e.heure,
                           c.nom AS commune_nom, c.latitude, c.longitude
                    FROM evenements e
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE e.statut IN ({$in})
                      AND e.deleted_at IS NULL
                      AND e.date_evenement >= CURDATE()
                      {$exclude}";

        if ($q !== '') {
            $syncSql .= ' AND (e.adresse LIKE ? OR e.description LIKE ? OR c.nom LIKE ?)';
            $like = '%' . $q . '%';
            $syncParams = array_merge($syncParams, [$like, $like, $like]);
        }
        if ($du !== '') {
            $syncSql .= ' AND e.date_evenement >= ?';
            $syncParams[] = $du;
        }
        if ($au !== '') {
            $syncSql .= ' AND e.date_evenement <= ?';
            $syncParams[] = $au;
        }
        if ($communeId > 0) {
            $syncSql .= ' AND e.commune_id = ?';
            $syncParams[] = $communeId;
        }

        $syncSql .= ' ORDER BY e.date_evenement ASC, e.heure ASC, e.id ASC LIMIT 30';
        $synced = Database::all($syncSql, $syncParams);

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
            $row['latitude']     = $row['latitude'] ?? null;
            $row['longitude']    = $row['longitude'] ?? null;

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

        $allActualites = array_map(static fn (array $row): array => self::card($row, 'actualite', 'cms'), $actualites);
        $allEvenements = $evenements;

        // Appliquer le filtre type
        $items = [...$allActualites, ...$allEvenements];
        if ($typeFilter !== '' && in_array($typeFilter, ['actualite', 'evenement'], true)) {
            $items = array_values(array_filter($items, static fn (array $i): bool => $i['type'] === $typeFilter));
        }

        // Communes disponibles (pour le dropdown filtre)
        $communes = Database::all('SELECT id, nom FROM commune WHERE is_active = 1 ORDER BY nom');

        return [
            'actualites' => $allActualites,
            'evenements' => $allEvenements,
            'items'      => $items,
            'prochains'  => $prochains,
            'theme'      => LandingService::theme(),
            'communes'   => $communes,
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
            'latitude'      => isset($row['latitude']) ? (float) $row['latitude'] : null,
            'longitude'     => isset($row['longitude']) ? (float) $row['longitude'] : null,
        ];
    }
}
