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
    /**
     * Rassemble toutes les données nécessaires au rendu de la page landing.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        // Événements à venir
        $upcoming = Database::all(
            'SELECT e.*, c.nom AS commune_nom FROM evenements e
              LEFT JOIN commune c ON c.id = e.commune_id
              WHERE e.statut = ? AND e.date_evenement >= CURDATE()
              ORDER BY e.date_evenement ASC LIMIT 3',
            ['PROGRAMME']
        );

        $stats = [
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'), 'libelle' => __('landing.stat_associations'), 'icone' => 'mdi-account-group-outline', 'teinte' => 'violet'],
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM users WHERE role_user = ?', ['citoyen']), 'libelle' => __('landing.stat_citoyens'), 'icone' => 'mdi-account-heart-outline', 'teinte' => 'cyan'],
            ['valeur' => (int) Database::value('SELECT COUNT(*) FROM anomalies_evenement'), 'libelle' => __('landing.stat_signalements'), 'icone' => 'mdi-alert-octgon-outline', 'teinte' => 'amber'],
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
                         (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS nb_photos_count,
                         (SELECT p.image FROM photos p WHERE p.album_id = a.id ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo
                    FROM albums a
                    JOIN evenements e ON e.id = a.evenement_id
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE a.statut = ?
                    ORDER BY a.date_creation DESC LIMIT 12',
            ['publie']
        );

        foreach ($albums as &$al) {
            // Couverture explicite de l'album, sinon première photo
            $al['display_image'] = $al['couverture'] ?: $al['first_photo'];

            // Liste complète des photos pour la lightbox
            $al['photos'] = Database::all(
                'SELECT * FROM photos WHERE album_id = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int) $al['id']]
            );

            // Badge association
            if (! empty($al['association_id'])) {
                $al['association'] = Database::one(
                    'SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?',
                    [(int) $al['association_id']]
                );
            } else {
                $al['association'] = null;
            }
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

        return [
            'upcoming'         => $upcoming,
            'stats'            => $stats,
            'totalParticipants'=> $totalParticipants,
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
        ];
    }
}
