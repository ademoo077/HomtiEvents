<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\LandingService;

final class LandingController extends Controller
{
    public function index(): never
    {
        if (is_logged()) {
            redirect(dashboard_path());
        }

        $this->view('landing.index', LandingService::data(), 'landing');
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
                         (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos_count,
                         (SELECT p.image FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo,
                         a.updated_at, a.date_creation
                    FROM albums a
                    JOIN evenements e ON e.id = a.evenement_id
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE a.statut = ?
                      AND (a.updated_at > ? OR EXISTS (SELECT 1 FROM photos p WHERE p.album_id = a.id AND p.status = ? AND p.uploaded_at > ?))
                    ORDER BY a.updated_at DESC LIMIT 10',
            ['publie', 'active', 'active', $lastTimestamp, 'active', $lastTimestamp]
        );

        // Enrich associations, photos et anomalies
        foreach ($albums as &$al) {
            $al['display_image'] = $al['couverture'] ?: $al['first_photo'];
            $al['nb_photos'] = $al['nb_photos_count'];

            $al['photos'] = Database::all(
                'SELECT id, image, titre, legende, sort_order, uploaded_at FROM photos WHERE album_id = ? AND status = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int) $al['id'], 'active']
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
}
