<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\GeoHelper;

final class CitoyenController extends Controller
{
    public function index(): never
    {
        $this->requireAuth();

        $user = $this->user();
        $role = \App\Helpers\Rbac::role($user);

        $upcoming = EvenementService::evenementsAVenirPourCitoyen();
        $past     = EvenementService::evenementsPassesPourCitoyen();

        $albums = Database::all(
            'SELECT a.id, a.titre, a.recit, a.date_creation, a.statut,
                     e.adresse, e.date_evenement, e.association_id,
                     (SELECT p.image FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at DESC LIMIT 1) AS couverture,
                     (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos
              FROM albums a
              JOIN evenements e ON e.id = a.evenement_id
              WHERE a.statut = ? AND e.deleted_at IS NULL
              ORDER BY a.date_creation DESC LIMIT 12',
            ['publie', 'active', 'active']
        );

        $stats = [
            'evenements_à_venir' => count($upcoming),
            'evenements_passés'  => count($past),
            'albums'             => count($albums),
        ];

        $participationsCount = 0;
        if (\App\Helpers\Session::isLogged()) {
            $userId = \App\Helpers\Session::userId();
            $participationsCount = (int) Database::value(
                'SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?',
                [$userId]
            );
        }
        $stats['participations'] = $participationsCount;

        $this->view('citoyen.index', [
            'user'                 => $user,
            'role'                 => $role,
            'upcoming'             => $upcoming,
            'past'                 => $past,
            'albums'               => $albums,
            'stats'                => $stats,
        ], 'citoyen');
    }

    /**
     * Détail public d'un album publié.
     */
    public function album(string $id): never
    {
        $this->requireAuth();

        $album = Database::one(
            'SELECT a.*, e.adresse, e.date_evenement, e.association_id,
                    a2.nom AS association_nom, a2.numero_agrement, a2.valide
             FROM albums a
             JOIN evenements e ON e.id = a.evenement_id
             LEFT JOIN associations a2 ON a2.id = e.association_id
             WHERE a.id = ? AND a.statut = ?',
            [(int) $id, 'publie']
        );

        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        $photos = Database::all(
            'SELECT * FROM photos WHERE album_id = ? AND status = ? ORDER BY sort_order ASC, uploaded_at DESC',
            [(int) $id, 'active']
        );

        $association = null;
        if ((int) ($album['association_id'] ?? 0) > 0) {
            $association = Database::one(
                'SELECT id, nom, numero_agrement, valide FROM associations WHERE id = ?',
                [(int) $album['association_id']]
            );
        }

        $participantsCount = 0;
        if (! empty($album['evenement_id'])) {
            $participantsCount = (int) Database::value(
                'SELECT COUNT(*) FROM evenement_participant WHERE evenement_id = ?',
                [(int) $album['evenement_id']]
            );
        }

        $this->view('citoyen.album', [
            'album'             => $album,
            'photos'            => $photos,
            'association'       => $association,
            'participantsCount' => $participantsCount,
        ], 'citoyen');
    }
}
