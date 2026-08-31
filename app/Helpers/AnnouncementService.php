<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Service d'annonces / bulletin board.
 */
final class AnnouncementService
{
    /**
     * Récupère les annonces actives pour un utilisateur donné.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getActive(?string $role = null, ?int $associationId = null): array
    {
        $sql = 'SELECT a.*, u.prenom AS author_prenom, u.nom AS author_nom
                FROM announcements a
                LEFT JOIN users u ON u.id = a.created_by
                WHERE a.is_active = 1
                  AND (a.published_at IS NULL OR a.published_at <= NOW())
                  AND (a.expires_at IS NULL OR a.expires_at > NOW())';
        $params = [];

        if ($role !== null) {
            $sql .= ' AND (a.target_role IS NULL OR a.target_role = ?)';
            $params[] = $role;
        }

        if ($associationId !== null) {
            $sql .= ' AND (a.target_association_id IS NULL OR a.target_association_id = ?)';
            $params[] = $associationId;
        }

        $sql .= ' ORDER BY a.published_at DESC, a.created_at DESC';

        return Database::all($sql, $params);
    }

    /**
     * Crée une annonce.
     */
    public static function create(array $data, int $createdBy): int
    {
        $id = Database::insert('announcements', [
            'titre'                   => $data['titre'],
            'body'                    => $data['body'],
            'target_role'             => $data['target_role'] ?? null,
            'target_association_id'   => $data['target_association_id'] ?? null,
            'is_active'               => $data['is_active'] ?? 1,
            'published_at'            => $data['published_at'] ?? date('Y-m-d H:i:s'),
            'expires_at'              => $data['expires_at'] ?? null,
            'created_by'              => $createdBy,
        ]);

        if ($id > 0 && ! empty($data['notify'])) {
            $senderName = trim((current_user()['prenom'] ?? '') . ' ' . (current_user()['nom'] ?? ''));
            Notification::broadcast(
                'Nouvelle annonce : ' . $data['titre'],
                $data['body'],
                'announcement',
                null,
                $createdBy,
                $data['target_role'] ?? null
            );
        }

        return $id;
    }

    /**
     * Met à jour une annonce.
     */
    public static function update(int $id, array $data): bool
    {
        Database::update('announcements', $id, $data);
        return true;
    }

    /**
     * Supprime une annonce.
     */
    public static function delete(int $id): bool
    {
        Database::delete('announcements', 'id = ?', [$id]);
        return true;
    }

    /**
     * Basculer l'état actif/inactif.
     */
    public static function toggle(int $id): bool
    {
        Database::run(
            'UPDATE announcements SET is_active = NOT is_active WHERE id = ?',
            [$id]
        );
        return true;
    }
}
