<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Gamification;
use App\Helpers\GeoHelper;
use App\Helpers\Session;

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
                     (SELECT COALESCE(NULLIF(a.couverture, \'\'),
                             (SELECT p.image FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at DESC LIMIT 1))
                      ) AS couverture,
                     (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos
              FROM albums a
              JOIN evenements e ON e.id = a.evenement_id
              WHERE a.statut = ? AND e.deleted_at IS NULL
              ORDER BY a.date_creation DESC LIMIT 12',
            ['active', 'active', 'publie']
        );

        $pastCount = (int) Database::value(
            'SELECT COUNT(*) FROM evenements WHERE statut = ? AND date_evenement < CURDATE() AND deleted_at IS NULL',
            ['TERMINE']
        );
        $albumsCount = (int) Database::value(
            'SELECT COUNT(*) FROM albums a JOIN evenements e ON e.id = a.evenement_id
             WHERE a.statut = ? AND e.deleted_at IS NULL',
            ['publie']
        );

        $stats = [
            'evenements_à_venir' => count($upcoming),
            'evenements_passés'  => $pastCount,
            'albums'             => $albumsCount,
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

        $gamification = [
            'points' => (int) ($user['points'] ?? 0),
            'rank'   => (int) Session::userId() > 0 ? Gamification::rank((int) Session::userId()) : 0,
            'badges' => (int) Session::userId() > 0 ? count(Gamification::badgesOf((int) Session::userId())) : 0,
        ];

        $userId = Session::userId();
        $recommandations = EvenementService::recommandationsPourCitoyen($userId, 6);
        $favorisCount = (int) Database::value(
            'SELECT COUNT(*) FROM citoyen_favoris WHERE user_id = ?',
            [$userId]
        );
        $stats['favoris'] = $favorisCount;

        $this->view('citoyen.index', [
            'user'           => $user,
            'role'           => $role,
            'upcoming'       => $upcoming,
            'past'           => $past,
            'albums'         => $albums,
            'stats'          => $stats,
            'gamification'   => $gamification,
            'recommandations' => $recommandations,
        ], 'citoyen');
    }

    public function notifications(): never
    {
        $this->requireAuth();
        $userId = Session::userId();

        $notifications = Database::all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY date_creation DESC LIMIT 50',
            [$userId]
        );

        $unreadCount = (int) Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0',
            [$userId]
        );

        $this->view('citoyen.notifications', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ], 'citoyen');
    }

    public function markAllRead(): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        Database::run(
            'UPDATE notifications SET lu = 1 WHERE user_id = ? AND lu = 0',
            [Session::userId()]
        );

        redirect('citoyen/notifications');
    }

    /**
     * Liste des événements mis en favori par le citoyen.
     */
    public function favoris(): never
    {
        $this->requireAuth();

        $user = $this->user();
        $role = \App\Helpers\Rbac::role($user);
        $userId = Session::userId();

        $favoris = Database::all(
            'SELECT e.*, c.nom AS commune_nom,
                    a.nom AS association_nom,
                    f.created_at AS fav_date,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants_count
             FROM citoyen_favoris f
             JOIN evenements e ON e.id = f.evenement_id AND e.deleted_at IS NULL
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC',
            [$userId]
        );

        $this->view('citoyen/favoris', [
            'favoris' => $favoris,
            'role'    => $role,
        ], 'citoyen');
    }

    /**
     * Ajoute ou retire un événement des favoris du citoyen.
     */
    public function toggleFavori(string $id): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $eventId = (int) $id;
        $exists = Database::exists(
            'SELECT 1 FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$eventId]
        );
        if (! $exists) {
            abort(404, 'Événement introuvable.');
        }

        $userId = Session::userId();
        $saved = Database::exists(
            'SELECT 1 FROM citoyen_favoris WHERE user_id = ? AND evenement_id = ?',
            [$userId, $eventId]
        );

        if ($saved) {
            Database::delete('citoyen_favoris', 'user_id = ? AND evenement_id = ?', [$userId, $eventId]);
            $saved = false;
        } else {
            Database::insert('citoyen_favoris', ['user_id' => $userId, 'evenement_id' => $eventId]);
            $saved = true;
        }

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || (($_GET['ajax'] ?? null) === '1');
        if ($isAjax) {
            json_response(['success' => true, 'saved' => $saved, 'event_id' => $eventId]);
        }

        redirect('citoyen/evenement/' . $eventId);
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
             JOIN evenements e ON e.id = a.evenement_id AND e.deleted_at IS NULL
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
