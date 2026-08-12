<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\UploadHelper;

/**
 * Galerie associative — soumission de photos par l'association.
 *
 * Les photos sont soumises en statut 'pending' puis validées / rejetées
 * par la Wilaya (permission gallery.validate).
 */
final class AssociationGalleryController extends Controller
{
    private function associationId(): int
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            flash('error', __('auth.association_pending'));
            redirect(dashboard_path());
        }

        return $associationId;
    }

    /**
     * Liste les événements de l'association avec leur album et compteurs de photos.
     */
    public function index(): never
    {
        $associationId = $this->associationId();

        $events = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut,
                    a.id AS album_id, a.titre AS album_titre, a.statut AS album_statut,
                    (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS nb_photos,
                    (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_pending,
                    (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_rejected
             FROM evenements e
             LEFT JOIN albums a ON a.evenement_id = e.id
             WHERE e.association_id = ? AND e.deleted_at IS NULL
             ORDER BY e.date_evenement DESC',
            ['pending', 'rejected', $associationId]
        );

        $this->view('association/gallery', [
            'events' => $events,
        ], 'association');
    }

    /**
     * Photos de l'événement + formulaire de soumission.
     */
    public function show(string $eventId): never
    {
        $associationId = $this->associationId();
        $event = $this->findEvent((int) $eventId, $associationId);

        $album = Database::one(
            'SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $eventId]
        );

        $photos = [];
        if ($album !== null) {
            $photos = Database::all(
                'SELECT * FROM photos WHERE album_id = ? ORDER BY sort_order ASC, uploaded_at DESC',
                [(int) $album['id']]
            );
        }

        $this->view('association/gallery-photos', [
            'event'  => $event,
            'album'  => $album,
            'photos' => $photos,
        ], 'association');
    }

    /**
     * Soumet de nouvelles photos (statut 'pending') pour validation Wilaya.
     */
    public function submit(string $eventId): never
    {
        $associationId = $this->associationId();
        $this->csrfCheck();
        $event = $this->findEvent((int) $eventId, $associationId);

        $album = Database::one(
            'SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $eventId]
        );

        if ($album === null) {
            $albumId = Database::insert('albums', [
                'evenement_id' => (int) $eventId,
                'titre'        => 'Photos — ' . $event['adresse'],
                'recit'        => null,
            ]);
            $album = Database::one('SELECT * FROM albums WHERE id = ?', [$albumId]);
        }

        $files = $_FILES['photos'] ?? null;
        if ($files === null || $files['error'][0] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Aucun fichier sélectionné.', 'danger');
            $this->redirect('association/evenements/' . $eventId . '/photos');
        }

        $uploadDir = config('paths.uploads.photos', public_path('uploads/photos'));
        $maxSize   = (int) config('security.upload_max', 5242880);

        $result = UploadHelper::uploadMultiple($files, $uploadDir, $maxSize);

        $inserted = 0;
        foreach ($result['successes'] as $path) {
            Database::insert('photos', [
                'album_id'    => (int) $album['id'],
                'image'       => $path,
                'legende'     => null,
                'status'      => 'pending',
                'uploaded_by' => (int) $this->user()['id'],
            ]);
            $inserted++;
        }

        if ($inserted > 0) {
            AuditLog::log('photo_submitted', 'albums', (int) $album['id'], null, [
                'event_id'  => (int) $eventId,
                'count'     => $inserted,
            ]);

            Notification::sendToRole(
                'wilaya',
                'Nouvelles photos à valider',
                "L'association a soumis {$inserted} photo(s) pour l'événement '" . $event['adresse'] . "'.",
                'gallery_photo_pending',
                ['evenement_id' => (int) $eventId]
            );

            flash('success', $inserted . ' photo(s) soumise(s) pour validation.');
        }

        if (! empty($result['errors'])) {
            flash('error', count($result['errors']) . ' fichier(s) rejeté(s).', 'warning');
        }

        $this->redirect('association/evenements/' . $eventId . '/photos');
    }

    /**
     * Supprime une photo soumise par l'association (en attente ou rejetée).
     */
    public function deletePhoto(string $photoId): never
    {
        $associationId = $this->associationId();
        $this->csrfCheck();

        $photo = Database::one(
            'SELECT p.*, e.id AS evenement_id FROM photos p
             JOIN albums a ON a.id = p.album_id
             JOIN evenements e ON e.id = a.evenement_id
             WHERE p.id = ? AND e.association_id = ? AND p.status IN (?, ?)',
            [(int) $photoId, $associationId, 'pending', 'rejected']
        );

        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        if (! empty($photo['image'])) {
            UploadHelper::delete($photo['image']);
        }

        Database::delete('photos', 'id = ?', [(int) $photoId]);

        AuditLog::log('photo_deleted_association', 'photos', (int) $photoId, ['image' => $photo['image']], null);

        flash('success', 'Photo supprimée.');
        $this->redirect('association/evenements/' . (int) $photo['evenement_id'] . '/photos');
    }

    /**
     * Vérifie que l'événement appartient bien à l'association connectée.
     */
    private function findEvent(int $eventId, int $associationId): array
    {
        $event = Database::one(
            'SELECT * FROM evenements
             WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [$eventId, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        return $event;
    }
}
