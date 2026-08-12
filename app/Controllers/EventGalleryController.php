<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\UploadHelper;

/**
 * Gestion de la galerie photos des événements.
 *
 * Permet d'ajouter, modifier, supprimer et publier les photos
 * de chaque événement.
 */
final class EventGalleryController extends Controller
{
    /**
     * Liste tous les événements avec leurs albums photos.
     */
    public function list(): never
    {
        $this->requirePermission('gallery.create');

        $events = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut, c.nom AS commune_nom,
                     a.id AS album_id, a.titre AS album_titre, a.statut AS album_statut,
                     (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) AS nb_photos
              FROM evenements e
              LEFT JOIN commune c ON c.id = e.commune_id
              LEFT JOIN albums a ON a.evenement_id = e.id
              WHERE e.deleted_at IS NULL
              ORDER BY e.date_evenement DESC'
        );

        $this->view('wilaya.gallery.list', [
            'events' => $events,
        ]);
    }

    /**
     * Liste des photos de l'événement + album associé.
     */
    public function index(string $eventId): never
    {
        $this->requirePermission('gallery.create');
        $event = $this->findEvent($eventId);

        $album = Database::one(
            'SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $eventId]
        );

        $photos = [];
        if ($album !== null) {
            $photos = Database::all(
                'SELECT p.id, p.image, p.legende, p.status, p.motif_rejet, p.uploaded_at,
                        u.nom AS uploader_nom, u.prenom AS uploader_prenom
                 FROM photos p
                 LEFT JOIN users u ON u.id = p.uploaded_by
                 WHERE p.album_id = ? ORDER BY p.sort_order ASC, p.uploaded_at DESC',
                [(int) $album['id']]
            );
        }

        $this->view('wilaya.gallery.index', [
            'event' => $event,
            'album' => $album,
            'photos' => $photos,
        ]);
    }

    /**
     * Formulaire d'ajout de photos.
     */
    public function create(string $eventId): never
    {
        $this->requirePermission('gallery.upload');
        $event = $this->findEvent($eventId);

        $album = Database::one(
            'SELECT * FROM albums WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $eventId]
        );

        $this->view('wilaya.gallery.create', [
            'event' => $event,
            'album' => $album,
        ]);
    }

    /**
     * Upload et insertion de photos.
     */
    public function store(string $eventId): never
    {
        $this->requirePermission('gallery.upload');
        $this->csrfCheck();
        $event = $this->findEvent($eventId);

        // Trouver ou créer l'album
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

        // Upload des photos
        $files = $_FILES['photos'] ?? null;
        if ($files === null || $files['error'][0] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Aucun fichier sélectionné.', 'danger');
            $this->redirect('wilaya/evenements/' . $eventId . '/photos/create');
        }

        $uploadDir = config('paths.uploads.photos', public_path('uploads/photos'));
        $maxSize = (int) config('security.upload_max', 5242880);

        $result = UploadHelper::uploadMultiple($files, $uploadDir, $maxSize);

        // Insérer les photos en base
        $inserted = 0;
        foreach ($result['successes'] as $path) {
            Database::insert('photos', [
                'album_id' => (int) $album['id'],
                'image'    => $path,
                'legende'  => null,
            ]);
            $inserted++;
        }

        AuditLog::log('photo_uploaded', 'albums', (int) $album['id'], null, [
            'event_id' => (int) $eventId,
            'count'    => $inserted,
        ]);

        if ($inserted > 0) {
            flash('success', $inserted . ' photo(s) ajoutée(s) avec succès.');
        }

        if (! empty($result['errors'])) {
            flash('error', count($result['errors']) . ' fichier(s) rejeté(s).', 'warning');
        }

        $this->redirect('wilaya/evenements/' . $eventId . '/photos');
    }

    /**
     * Formulaire de modification d'une photo.
     */
    public function edit(string $photoId): never
    {
        $this->requirePermission('gallery.edit');

        $photo = Database::one('SELECT * FROM photos WHERE id = ?', [(int) $photoId]);
        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $photo['album_id']]);
        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        $event = Database::one('SELECT * FROM evenements WHERE id = ?', [(int) $album['evenement_id']]);

        $this->view('wilaya.gallery.edit', [
            'photo' => $photo,
            'album' => $album,
            'event' => $event,
        ]);
    }

    /**
     * Mise à jour d'une photo.
     */
    public function update(string $photoId): never
    {
        $this->requirePermission('gallery.edit');
        $this->csrfCheck();

        $photo = Database::one('SELECT * FROM photos WHERE id = ?', [(int) $photoId]);
        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        $data = all_input();

        Database::update('photos', [
            'legende' => $data['legende'] ?? null,
        ], 'id = ?', [(int) $photoId]);

        AuditLog::log('photo_updated', 'photos', (int) $photoId, null, $data);

        flash('success', 'Photo modifiée avec succès.');

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $photo['album_id']]);
        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Suppression d'une photo (fichier + BDD).
     */
    public function delete(string $photoId): never
    {
        $this->requirePermission('gallery.delete');
        $this->csrfCheck();

        $photo = Database::one('SELECT * FROM photos WHERE id = ?', [(int) $photoId]);
        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $photo['album_id']]);

        // Supprimer le fichier
        if (! empty($photo['image'])) {
            UploadHelper::delete($photo['image']);
        }

        // Supprimer l'enregistrement
        Database::delete('photos', 'id = ?', [(int) $photoId]);

        AuditLog::log('photo_deleted', 'photos', (int) $photoId, ['image' => $photo['image']], null);

        flash('success', 'Photo supprimée.');

        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Publie un album.
     */
    public function publish(string $albumId): never
    {
        $this->requirePermission('gallery.publish');
        $this->csrfCheck();

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $albumId]);
        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        Database::update('albums', [
            'statut'           => 'publie',
            'date_publication' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $albumId]);

        AuditLog::log('album_published', 'albums', (int) $albumId, null, ['statut' => 'publie']);
        $this->notifierAlbumPublie((int) $albumId);
        flash('success', 'Album publié.');
        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Notifie l'association porteuse (sinon la Wilaya) de la publication de l'album.
     */
    private function notifierAlbumPublie(int $albumId): void
    {
        $album = Database::one(
            'SELECT a.titre, a.evenement_id, e.association_id, e.adresse, e.description
             FROM albums a
             JOIN evenements e ON e.id = a.evenement_id
             WHERE a.id = ?',
            [$albumId]
        );
        if ($album === null) {
            return;
        }

        $titreEvenement = (string) ($album['description'] ?? $album['adresse'] ?? 'Événement n°' . (int) $album['evenement_id']);
        $albumTitre     = (string) ($album['titre'] ?? 'album');
        $message        = "L'album '{$albumTitre}' de votre événement '{$titreEvenement}' a été publié.";

        if ((int) ($album['association_id'] ?? 0) > 0) {
            Notification::sendToAssociation(
                (int) $album['association_id'],
                'Album publié',
                $message,
                'album_publie',
                ['album_id' => $albumId]
            );

            return;
        }

        Notification::sendToRole(
            'wilaya',
            'Album publié',
            $message,
            'album_publie',
            ['album_id' => $albumId]
        );
    }

    /**
     * Masque un album.
     */
    public function unpublish(string $albumId): never
    {
        $this->requirePermission('gallery.publish');
        $this->csrfCheck();

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $albumId]);
        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        Database::update('albums', [
            'statut'           => 'masque',
            'date_publication' => null,
        ], 'id = ?', [(int) $albumId]);

        AuditLog::log('album_unpublished', 'albums', (int) $albumId, null, ['statut' => 'masque']);
        flash('success', 'Album masqué.');
        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Définit la photo de couverture d'un album.
     */
    public function setCover(string $albumId): never
    {
        $this->requirePermission('gallery.edit');
        $this->csrfCheck();

        $data = all_input();
        $photoId = (int) ($data['photo_id'] ?? 0);

        $photo = Database::one(
            'SELECT * FROM photos WHERE id = ? AND album_id = ?',
            [$photoId, (int) $albumId]
        );
        if ($photo === null) {
            flash('error', 'Photo introuvable dans cet album.');
            $this->redirect('wilaya/evenements/' . $albumId . '/photos');
        }

        Database::update('albums', ['couverture' => $photo['image']], 'id = ?', [(int) $albumId]);

        AuditLog::log('album_cover_set', 'albums', (int) $albumId, null, ['photo_id' => $photoId]);
        flash('success', 'Couverture mise à jour.');
        $this->redirect('wilaya/evenements/' . $albumId . '/photos');
    }

    /**
     * Met à jour le titre et le récit d'un album.
     */
    public function updateAlbum(string $albumId): never
    {
        $this->requirePermission('gallery.edit');
        $this->csrfCheck();

        $album = Database::one('SELECT * FROM albums WHERE id = ?', [(int) $albumId]);
        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'titre'  => 'required|string|max:255',
            'recit'  => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->errors()['titre'][0] ?? 'Erreur de validation.');
            $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
        }

        Database::update('albums', [
            'titre'  => trim((string) $data['titre']),
            'recit'  => trim((string) ($data['recit'] ?? '')),
        ], 'id = ?', [(int) $albumId]);

        AuditLog::log('album_updated', 'albums', (int) $albumId, null, ['titre' => $data['titre']]);
        flash('success', 'Album mis à jour.');
        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Réorganise l'ordre des photos dans un album.
     */
    public function reorder(string $albumId): never
    {
        $this->requirePermission('gallery.edit');
        $this->csrfCheck();

        $data = all_input();
        $order = $data['order'] ?? [];

        if (! is_array($order) || $order === []) {
            flash('error', 'Aucun ordre fourni.');
            $this->redirect('wilaya/evenements/' . $albumId . '/photos');
        }

        $album = Database::one('SELECT evenement_id FROM albums WHERE id = ?', [(int) $albumId]);
        if ($album === null) {
            abort(404, 'Album introuvable.');
        }

        foreach ($order as $index => $photoId) {
            Database::update('photos', ['sort_order' => $index], 'id = ? AND album_id = ?', [(int) $photoId, (int) $albumId]);
        }

        AuditLog::log('album_reordered', 'albums', (int) $albumId, null, ['order' => $order]);
        flash('success', 'Ordre des photos mis à jour.');
        $this->redirect('wilaya/evenements/' . $album['evenement_id'] . '/photos');
    }

    /**
     * Valide une photo soumise par une association (pending → active).
     */
    public function approvePhoto(string $photoId): never
    {
        $this->requirePermission('gallery.validate');
        $this->csrfCheck();

        $photo = Database::one(
            'SELECT p.*, a.evenement_id, e.association_id, e.adresse
             FROM photos p
             JOIN albums a ON a.id = p.album_id
             JOIN evenements e ON e.id = a.evenement_id
             WHERE p.id = ?',
            [(int) $photoId]
        );
        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        Database::update('photos', ['status' => 'active', 'motif_rejet' => null], 'id = ?', [(int) $photoId]);

        AuditLog::log('photo_approved', 'photos', (int) $photoId, ['status' => 'pending'], ['status' => 'active']);

        if ((int) ($photo['association_id'] ?? 0) > 0) {
            Notification::sendToAssociation(
                (int) $photo['association_id'],
                'Photo publiée',
                "Votre photo pour l'événement '" . ($photo['adresse'] ?? '') . "' a été publiée.",
                'gallery_photo_approved',
                ['evenement_id' => (int) $photo['evenement_id']]
            );
        }

        flash('success', 'Photo validée et publiée.');
        $this->redirect('wilaya/evenements/' . $photo['evenement_id'] . '/photos');
    }

    /**
     * Rejette une photo soumise par une association (pending → rejected + motif).
     */
    public function rejectPhoto(string $photoId): never
    {
        $this->requirePermission('gallery.validate');
        $this->csrfCheck();

        $photo = Database::one(
            'SELECT p.*, a.evenement_id, e.association_id, e.adresse
             FROM photos p
             JOIN albums a ON a.id = p.album_id
             JOIN evenements e ON e.id = a.evenement_id
             WHERE p.id = ?',
            [(int) $photoId]
        );
        if ($photo === null) {
            abort(404, 'Photo introuvable.');
        }

        $motif = trim((string) input('motif', ''));

        Database::update('photos', [
            'status'      => 'rejected',
            'motif_rejet' => $motif !== '' ? mb_substr($motif, 0, 255) : null,
        ], 'id = ?', [(int) $photoId]);

        AuditLog::log('photo_rejected', 'photos', (int) $photoId, ['status' => 'pending'], ['status' => 'rejected', 'motif' => $motif]);

        if ((int) ($photo['association_id'] ?? 0) > 0) {
            Notification::sendToAssociation(
                (int) $photo['association_id'],
                'Photo rejetée',
                "Votre photo pour l'événement '" . ($photo['adresse'] ?? '') . "' a été rejetée."
                . ($motif !== '' ? ' Motif : ' . $motif : ''),
                'gallery_photo_rejected',
                ['evenement_id' => (int) $photo['evenement_id']]
            );
        }

        flash('success', 'Photo rejetée.');
        $this->redirect('wilaya/evenements/' . $photo['evenement_id'] . '/photos');
    }

    /**
     * Trouve un événement ou abort 404.
     */
    private function findEvent(string $id): array
    {
        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.id = ?',
            [(int) $id]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        return $event;
    }
}
