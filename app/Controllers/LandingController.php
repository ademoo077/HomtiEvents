<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;

final class LandingController extends Controller
{
    public function index(): never
    {
        if (is_logged()) {
            redirect(dashboard_path());
        }

        $this->renderLandingPage();
    }

    /**
     * API endpoint for polling new/updated albums.
     * Returns published albums modified after the given timestamp
     * (comparison sur `updated_at` : les modifications du titre, du récit
     * ou le dépôt de nouvelles photos mettent à jour ce champ).
     */
    public function galleryUpdates(): never
    {
        header('Content-Type: application/json');

        $lastTimestamp = (string) input('since', date('Y-m-d H:i:s', strtotime('-1 hour')));

        $albums = Database::all(
            'SELECT a.id, a.titre, a.recit, a.statut, a.couverture,
                         e.adresse, e.date_evenement, e.association_id, e.id AS evenement_id,
                         c.nom AS commune_nom,
                         (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS nb_photos_count,
                         (SELECT p.image FROM photos p WHERE p.album_id = a.id ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo,
                         a.updated_at, a.date_creation
                    FROM albums a
                    JOIN evenements e ON e.id = a.evenement_id
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE a.statut = ?
                      AND (a.updated_at > ? OR EXISTS (SELECT 1 FROM photos p WHERE p.album_id = a.id AND p.uploaded_at > ?))
                    ORDER BY a.updated_at DESC LIMIT 10',
            ['publie', $lastTimestamp, $lastTimestamp]
        );

        // Enrich associations, photos et anomalies
        foreach ($albums as &$al) {
            $al['display_image'] = $al['couverture'] ?: $al['first_photo'];
            $al['nb_photos'] = $al['nb_photos_count'];

            $al['photos'] = Database::all(
                'SELECT id, image, title, legende, sort_order, uploaded_at FROM photos WHERE album_id = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int) $al['id']]
            );

            $al['anomalies'] = Database::all(
                'SELECT a.id, a.nom, a.icone, a.couleur FROM anomalies a
                 JOIN anomalies_evenement ae ON ae.anomalie_id = a.id
                 WHERE ae.evenement_id = ?',
                [(int) $al['evenement_id']]
            );

            if (! empty($al['association_id'])) {
                $assoc = Database::one(
                    'SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?',
                    [(int) $al['association_id']]
                );
                $al['association'] = $assoc;
            } else {
                $al['association'] = null;
            }
        }

        json_response([
            'timestamp' => date('Y-m-d H:i:s'),
            'albums' => $albums,
        ]);
    }

    /**
     * Render the complete landing page with enriched album/gallery data.
     */
    private function renderLandingPage(): void
    {
        // Upcoming events
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

        // Albums with enriched data including photos and association info
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

        // Enrich albums with additional data
        foreach ($albums as &$al) {
            // Prefer album's explicit cover, fallback to first photo
            $al['display_image'] = $al['couverture'] ?: $al['first_photo'];
            
            // Get full photo list for lightbox
            $al['photos'] = Database::all(
                'SELECT * FROM photos WHERE album_id = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int)$al['id']]
            );

            // Get association badge
            if (!empty($al['association_id'])) {
                $al['association'] = Database::one(
                    'SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?',
                    [(int)$al['association_id']]
                );
            } else {
                $al['association'] = null;
            }
        }

        // Gallery (separate from albums, for manual curation)
        $gallery = Database::all(
            'SELECT * FROM landing_gallery WHERE actif = 1 ORDER BY sort_order ASC'
        );

        // Before/After comparisons
        $beforeAfter = Database::all(
            'SELECT * FROM landing_before_after WHERE actif = 1 AND statut = "publie" ORDER BY sort_order ASC'
        );

        // Map data: all events with commune coordinates
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

        // Get current timestamp for polling
        $currentTime = Database::value('SELECT NOW()');

        $this->view('landing.index', [
            'upcoming'      => $upcoming,
            'stats'         => $stats,
            'totalParticipants' => $totalParticipants,
            'faq'           => $faq,
            'testimonials'  => $testimonials,
            'partners'      => $partners,
            'gallery'       => $gallery,
            'beforeAfter'   => $beforeAfter,
            'albums'        => $albums,
            'mapEvents'     => $mapEvents,
            'anomalies'     => $anomalies,
            'lang'          => null,
            'lastUpdate'    => $currentTime,
        ], 'landing');
    }
}
