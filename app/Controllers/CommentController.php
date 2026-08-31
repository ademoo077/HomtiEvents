<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\CommentService;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Session;

/**
 * Contrôleur de commentaires et notes internes pour les événements.
 */
final class CommentController extends Controller
{
    /**
     * Liste des commentaires d'un événement (AJAX).
     */
    public function index(string $eventId): never
    {
        $this->requireAuth();

        $evenementId = (int) $eventId;
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = CommentService::getComments($evenementId, $page);

        json_response([
            'success' => true,
            'comments' => $result['items'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'last_page' => $result['last_page'],
        ]);
    }

    /**
     * Ajoute un commentaire (AJAX POST).
     */
    public function store(string $eventId): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = Session::user();
        $userId = (int) $user['id'];
        $evenementId = (int) $eventId;
        $body = trim($_POST['body'] ?? '');
        $parentId = ! empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

        if ($body === '') {
            json_response(['success' => false, 'error' => 'Commentaire vide.'], 422);
        }

        if (mb_strlen($body) > 2000) {
            json_response(['success' => false, 'error' => 'Commentaire trop long (2000 caractères max).'], 422);
        }

        $id = CommentService::addComment($evenementId, $userId, $body, $parentId);

        if ($id === 0) {
            json_response(['success' => false, 'error' => 'Erreur lors de l\'ajout.'], 500);
        }

        // Notifier l'association si le commentaire est d'un wilaya/epic
        $role = user_role();
        if (in_array($role, ['wilaya', 'epic'], true)) {
            $event = Database::one('SELECT association_id FROM evenements WHERE id = ?', [$evenementId]);
            if ($event !== null && ! empty($event['association_id'])) {
                $senderName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
                Notification::sendToAssociation(
                    (int) $event['association_id'],
                    'Nouveau commentaire',
                    $senderName . ' a commenté l\'événement #' . $evenementId,
                    'evenement_create',
                    ['evenement_id' => $evenementId, 'link' => 'wilaya/evenements/' . $evenementId]
                );
            }
        }

        $comment = Database::one(
            'SELECT c.*, u.prenom, u.nom, u.avatar, u.role_user AS role
             FROM event_comments c JOIN users u ON u.id = c.user_id
             WHERE c.id = ?',
            [$id]
        );

        json_response(['success' => true, 'comment' => $comment]);
    }

    /**
     * Modifie un commentaire (AJAX POST).
     */
    public function update(string $id): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = Session::user();
        $userId = (int) $user['id'];
        $body = trim($_POST['body'] ?? '');

        if ($body === '') {
            json_response(['success' => false, 'error' => 'Commentaire vide.'], 422);
        }

        $ok = CommentService::editComment((int) $id, $userId, $body);
        json_response(['success' => $ok]);
    }

    /**
     * Supprime un commentaire (AJAX POST).
     */
    public function destroy(string $id): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = Session::user();
        $userId = (int) $user['id'];
        $role = user_role();

        // Wilaya peut supprimer n'importe quel commentaire
        $isOwner = $role !== 'wilaya';
        CommentService::deleteComment((int) $id, $userId, $isOwner);

        json_response(['success' => true]);
    }

    // ─── Notes internes Wilaya ──────────────────────────────────

    /**
     * Liste des notes internes d'un événement (AJAX).
     */
    public function notes(string $eventId): never
    {
        $this->requireAuth();
        $this->requirePermission('wilaya');

        $result = CommentService::getNotes((int) $eventId);

        json_response([
            'success' => true,
            'notes'   => $result['items'],
            'total'   => $result['total'],
        ]);
    }

    /**
     * Ajoute une note interne (AJAX POST).
     */
    public function storeNote(string $eventId): never
    {
        $this->requireAuth();
        $this->requirePermission('wilaya');
        $this->csrfCheck();

        $user = Session::user();
        $body = trim($_POST['body'] ?? '');

        if ($body === '') {
            json_response(['success' => false, 'error' => 'Note vide.'], 422);
        }

        $id = CommentService::addNote((int) $eventId, (int) $user['id'], $body);

        if ($id === 0) {
            json_response(['success' => false, 'error' => 'Erreur lors de l\'ajout.'], 500);
        }

        $note = Database::one(
            'SELECT n.*, u.prenom, u.nom, u.avatar
             FROM event_notes n JOIN users u ON u.id = n.user_id
             WHERE n.id = ?',
            [$id]
        );

        json_response(['success' => true, 'note' => $note]);
    }

    /**
     * Supprime une note interne (AJAX POST).
     */
    public function destroyNote(string $id): never
    {
        $this->requireAuth();
        $this->requirePermission('wilaya');
        $this->csrfCheck();

        $user = Session::user();
        CommentService::deleteNote((int) $id, (int) $user['id']);

        json_response(['success' => true]);
    }
}
