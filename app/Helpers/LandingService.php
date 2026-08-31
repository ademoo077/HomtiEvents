<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Agrégation des données de la page d'accueil publique (landing).
 *
 * Ce service centralise l'assemblage des données afin que le rendu public
 * (LandingController) et l'aperçu admin (LandingAdminController::preview)
 * affichent exactement le même contenu CMS.
 */
final class LandingService
{
    /** Garde anti-doublon (au plus une clôture automatique par requête). */
    private static bool $autoClotureTente = false;

    /**
     * Rassemble toutes les données nécessaires au rendu de la page landing.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        // Auto-clôture : passe en TERMINE les événements dont la date est dépassée.
        if (! self::$autoClotureTente) {
            self::$autoClotureTente = true;
            EvenementService::autoCloturer();
        }

        // Événements à venir
        $upcomingMax = max(1, min(12, (int) settings('general_upcoming_max', 3)));
        $upcoming = Database::all(
            'SELECT e.*, c.nom AS commune_nom FROM evenements e
              LEFT JOIN commune c ON c.id = e.commune_id
              WHERE e.statut IN (' . implode(',', array_map(fn($s) => "'$s'", EvenementService::STATUTS_A_VENIR)) . ')
                AND e.date_evenement >= CURDATE()
                AND e.deleted_at IS NULL
              ORDER BY e.date_evenement ASC LIMIT ' . $upcomingMax
        );

        $stats = [
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'), 'libelle' => __('landing.stat_associations'), 'icone' => 'mdi-account-group-outline', 'teinte' => 'violet'],
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM users WHERE role_user = ?', ['citoyen']), 'libelle' => __('landing.stat_citoyens'), 'icone' => 'mdi-account-heart-outline', 'teinte' => 'cyan'],
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM anomalies_evenement'), 'libelle' => __('landing.stat_signalements'), 'icone' => 'mdi-bullhorn-outline', 'teinte' => 'amber'],
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM evenements'), 'libelle' => __('landing.stat_interventions'), 'icone' => 'mdi-map-marker-radius-outline', 'teinte' => 'green'],
        ];

        $totalParticipants = (int) Database::value('SELECT COUNT(*) FROM evenement_participant');

        $faq = Database::all('SELECT * FROM landing_faq WHERE actif = 1 ORDER BY ordre ASC');

        $testimonials = Database::all('SELECT * FROM landing_testimonials WHERE actif = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 3');

        $partners = Database::all('SELECT * FROM landing_partners WHERE actif = 1 ORDER BY ordre ASC');

        // Albums publiés avec données enrichies (photos + association)
        $albums = Database::all(
            'SELECT a.id, a.titre, a.recit, a.date_creation, a.statut, a.couverture,
                         e.id AS evenement_id, e.adresse, e.date_evenement, e.association_id,
                         c.nom AS commune_nom,
                         (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos_count,
                         (SELECT p.image FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo,
                         (SELECT COALESCE(p.thumbnail, p.image) FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo_src
                    FROM albums a
                    JOIN evenements e ON e.id = a.evenement_id
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE a.statut = ?
                    ORDER BY a.date_creation DESC LIMIT 12',
            ['active', 'active', 'active', 'publie']
        );

        foreach ($albums as &$al) {
            // Couverture explicite de l'album, sinon première photo (vignette si dispo)
            $al['display_image'] = $al['couverture'] ?: ($al['first_photo_src'] ?: $al['first_photo']);
        }
        unset($al);

        // Batch-fetch photos for all albums (avoids N+1)
        $albumIds = array_values(array_column($albums, 'id'));
        $allPhotos = [];
        if ($albumIds !== []) {
            $placeholders = implode(',', array_fill(0, count($albumIds), '?'));
            $photoRows = Database::all(
                'SELECT * FROM photos WHERE album_id IN (' . $placeholders . ') AND status = ? ORDER BY sort_order ASC, uploaded_at DESC',
                array_merge($albumIds, ['active'])
            );
            foreach ($photoRows as $ph) {
                $allPhotos[(int) $ph['album_id']][] = $ph;
            }
        }
        foreach ($albums as &$al) {
            $al['photos'] = $allPhotos[(int) $al['id']] ?? [];
        }
        unset($al);

        // Batch-fetch associations (avoids N+1)
        $assocIds = array_values(array_unique(array_filter(array_column($albums, 'association_id'))));
        $assocMap = [];
        if ($assocIds !== []) {
            $placeholders = implode(',', array_fill(0, count($assocIds), '?'));
            $assocRows = Database::all(
                'SELECT id, nom, numero_agrement, valide FROM associations WHERE id IN (' . $placeholders . ')',
                $assocIds
            );
            foreach ($assocRows as $ar) {
                $assocMap[(int) $ar['id']] = $ar;
            }
        }
        foreach ($albums as &$al) {
            $al['association'] = (! empty($al['association_id'])) ? ($assocMap[(int) $al['association_id']] ?? null) : null;
        }
        unset($al);

        // Batch-fetch anomalies per album event (avoids N+1 in view)
        $eventIds = array_values(array_unique(array_filter(array_column($albums, 'evenement_id'))));
        $anomalyMap = [];
        if ($eventIds !== []) {
            $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
            $anomalyRows = Database::all(
                'SELECT ae.evenement_id, a.id, a.nom, a.icone, a.couleur
                 FROM anomalies a
                 JOIN anomalies_evenement ae ON ae.anomalie_id = a.id
                 WHERE ae.evenement_id IN (' . $placeholders . ')',
                $eventIds
            );
            foreach ($anomalyRows as $ar) {
                $anomalyMap[(int) $ar['evenement_id']][] = $ar;
            }
        }
        foreach ($albums as &$al) {
            $al['anomalies'] = $anomalyMap[(int) $al['evenement_id']] ?? [];
        }
        unset($al);

        // Galerie manuelle (indépendante des albums événements)
        $gallery = Database::all(
            'SELECT * FROM landing_gallery WHERE actif = 1 ORDER BY sort_order ASC'
        );

        // Comparaisons avant / après
        $beforeAfter = Database::all(
            'SELECT * FROM landing_before_after WHERE actif = 1 AND statut = "publie" ORDER BY sort_order ASC'
        );

        // Carte : événements avec coordonnées de commune
        $mapEvents = Database::all(
            'SELECT e.id, e.adresse, e.statut, e.date_evenement,
                    c.nom AS commune_nom, c.latitude, c.longitude
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE c.latitude IS NOT NULL AND c.longitude IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 30'
        );

        $anomalies = Database::all(
            'SELECT a.id, a.nom, a.icone, a.couleur, COUNT(ae.evenement_id) AS total
             FROM anomalies a
             LEFT JOIN anomalies_evenement ae ON ae.anomalie_id = a.id
             GROUP BY a.id
             ORDER BY total DESC LIMIT 6'
        );

        // Horodatage courant pour le polling des albums
        $currentTime = Database::value('SELECT NOW()');

        // Actualités & événements à venir (depuis landing_news)
        $news = Database::all(
            'SELECT * FROM landing_news WHERE actif = 1 AND deleted_at IS NULL ORDER BY date_event DESC, sort_order ASC LIMIT 10'
        );

        // ── Thème couleur (dynamique depuis le CMS) ──
        $theme = self::theme();

        return [
            'upcoming'         => $upcoming,
            'news'             => $news,
            'stats'            => $stats,
            'totalParticipants' => $totalParticipants,
            'faq'              => $faq,
            'testimonials'     => $testimonials,
            'partners'         => $partners,
            'gallery'          => $gallery,
            'beforeAfter'      => $beforeAfter,
            'albums'           => $albums,
            'mapEvents'        => $mapEvents,
            'anomalies'        => $anomalies,
            'lang'             => null,
            'lastUpdate'       => $currentTime,
            'theme'            => $theme,
        ];
    }

    /**
     * Thème couleur du CMS (réutilisé par les pages publiques sur le layout landing).
     *
     * @return array<string, string>
     */
    public static function theme(): array
    {
        return [
            'name'              => settings('theme_name', 'vert'),
            'primary'           => settings('theme_primary', '#16a34a'),
            'primary_hover'     => settings('theme_primary_hover', '#15803d'),
            'secondary'         => settings('theme_secondary', '#22c55e'),
            'tertiary'          => settings('theme_tertiary', '#0ea5e9'),
            'accent_glow'       => settings('theme_accent_glow', '#22c55e'),
            'hero_gradient_1'   => settings('theme_hero_gradient_1', 'rgba(22,163,74,0.16)'),
            'hero_gradient_2'   => settings('theme_hero_gradient_2', 'rgba(34,197,94,0.08)'),
            'hero_gradient_3'   => settings('theme_hero_gradient_3', 'rgba(16,185,129,0.10)'),
            'navbar_bg'         => settings('theme_navbar_bg', 'rgba(255,255,255,0.65)'),
            'navbar_bg_scrolled' => settings('theme_navbar_bg_scrolled', 'rgba(255,255,255,0.85)'),
            'footer_bg'         => settings('theme_footer_bg', '#ffffff'),
            'footer_text'       => settings('theme_footer_text', '#475569'),
            'border_radius'     => settings('theme_border_radius', '18px'),
            'font_sans'         => settings('theme_font_sans', 'Inter, system-ui, -apple-system, sans-serif'),
            'font_heading'      => settings('theme_font_heading', 'Inter, system-ui, -apple-system, sans-serif'),
        ];
    }
}
