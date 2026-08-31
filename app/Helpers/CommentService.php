<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Service de commentaires et notes internes pour les événements.
 */
final class CommentService
{
    /**
     * Récupère les commentaires d'un événement (avec réponses en thread).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getComments(int $evenementId, int $page = 1, int $perPage = 20): array
    {
        $sql = 'SELECT c.*, u.prenom, u.nom, u.avatar, u.role_user AS role
                FROM event_comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.evenement_id = ? AND c.deleted_at IS NULL
                ORDER BY c.parent_id ASC, c.created_at ASC';

        return Database::paginate($sql, [$evenementId], $perPage, $page);
    }

    /**
     * Nombre de commentaires d'un événement.
     */
    public static function countComments(int $evenementId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM event_comments WHERE evenement_id = ? AND deleted_at IS NULL',
            [$evenementId]
        );
    }

    /**
     * Ajoute un commentaire.
     */
    public static function addComment(int $evenementId, int $userId, string $body, ?int $parentId = null): int
    {
        $body = trim($body);
        if ($body === '') {
            return 0;
        }

        $id = Database::insert('event_comments', [
            'evenement_id' => $evenementId,
            'user_id'      => $userId,
            'body'         => $body,
            'parent_id'    => $parentId,
        ]);

        if ($id > 0) {
            AuditLog::log('comment.add', 'evenement', $evenementId, null, [
                'comment_id' => $id,
                'parent_id'  => $parentId,
            ], $userId);
        }

        return $id;
    }

    /**
     * Modifie un commentaire (auteur uniquement).
     */
    public static function editComment(int $commentId, int $userId, string $body): bool
    {
        $body = trim($body);
        if ($body === '') {
            return false;
        }

        $existing = Database::one(
            'SELECT id FROM event_comments WHERE id = ? AND user_id = ? AND deleted_at IS NULL',
            [$commentId, $userId]
        );

        if ($existing === null) {
            return false;
        }

        Database::update('event_comments', $commentId, [
            'body'      => $body,
            'edited_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Suppression logique d'un commentaire.
     */
    public static function deleteComment(int $commentId, int $userId, bool $isOwner = true): bool
    {
        if ($isOwner) {
            Database::run(
                'UPDATE event_comments SET deleted_at = NOW() WHERE id = ? AND user_id = ?',
                [$commentId, $userId]
            );
        } else {
            Database::run('UPDATE event_comments SET deleted_at = NOW() WHERE id = ?', [$commentId]);
        }

        return true;
    }

    // ─── Notes internes Wilaya ──────────────────────────────────

    /**
     * Récupère les notes internes d'un événement.
     */
    public static function getNotes(int $evenementId, int $page = 1, int $perPage = 20): array
    {
        $sql = 'SELECT n.*, u.prenom, u.nom, u.avatar
                FROM event_notes n
                JOIN users u ON u.id = n.user_id
                WHERE n.evenement_id = ? AND n.deleted_at IS NULL
                ORDER BY n.created_at DESC';

        return Database::paginate($sql, [$evenementId], $perPage, $page);
    }

    /**
     * Nombre de notes internes.
     */
    public static function countNotes(int $evenementId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM event_notes WHERE evenement_id = ? AND deleted_at IS NULL',
            [$evenementId]
        );
    }

    /**
     * Ajoute une note interne.
     */
    public static function addNote(int $evenementId, int $userId, string $body): int
    {
        $body = trim($body);
        if ($body === '') {
            return 0;
        }

        $id = Database::insert('event_notes', [
            'evenement_id' => $evenementId,
            'user_id'      => $userId,
            'body'         => $body,
            'is_internal'  => 1,
        ]);

        if ($id > 0) {
            AuditLog::log('note.add', 'evenement', $evenementId, null, ['note_id' => $id], $userId);
        }

        return $id;
    }

    /**
     * Suppression logique d'une note.
     */
    public static function deleteNote(int $noteId, int $userId): bool
    {
        Database::run(
            'UPDATE event_notes SET deleted_at = NOW() WHERE id = ? AND user_id = ?',
            [$noteId, $userId]
        );

        return true;
    }
}
