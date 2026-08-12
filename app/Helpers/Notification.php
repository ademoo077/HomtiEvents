<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Notifications in-app + push.
 */
final class Notification
{
    public static function send(int $userId, string $titre, string $message, ?string $type = null, ?array $data = null): int
    {
        return Database::insert('notifications', [
            'user_id'      => $userId,
            'titre'        => $titre,
            'message_notif' => mb_substr($message, 0, 255),
            'type'         => $type,
            'data_json'    => $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'lu'           => 0,
        ]);
    }

    /**
     * Envoie à tous les utilisateurs d'un rôle donné.
     */
    public static function sendToRole(string $role, string $titre, string $message, ?string $type = null, ?array $data = null): int
    {
        $users = Database::all('SELECT id FROM users WHERE role_user = ? AND is_active = 1', [$role]);

        foreach ($users as $user) {
            self::send((int) $user['id'], $titre, $message, $type, $data);
        }

        return count($users);
    }

    public static function sendToAssociation(int $associationId, string $titre, string $message, ?string $type = null, ?array $data = null): int
    {
        $users = Database::all(
            'SELECT id FROM users WHERE association_id = ? AND is_active = 1',
            [$associationId]
        );

        foreach ($users as $user) {
            self::send((int) $user['id'], $titre, $message, $type, $data);
        }

        return count($users);
    }

    /**
     * Envoie aux utilisateurs actifs d'une EPIC donnée.
     */
    public static function sendToEpic(int $epicId, string $titre, string $message, ?string $type = null, ?array $data = null): int
    {
        $users = Database::all(
            'SELECT id FROM users WHERE epic_id = ? AND is_active = 1',
            [$epicId]
        );

        foreach ($users as $user) {
            self::send((int) $user['id'], $titre, $message, $type, $data);
        }

        return count($users);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0',
            [$userId]
        );
    }

    public static function recent(int $userId, int $limit = 10): array
    {
        return Database::all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY date_creation DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    /**
     * Notifications non lues d'un utilisateur (spec §6 : getUnread).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUnread(int $userId): array
    {
        return Database::all(
            'SELECT id, titre, message_notif, type, data_json, date_creation
             FROM notifications
             WHERE user_id = ? AND lu = 0
             ORDER BY date_creation DESC',
            [$userId]
        );
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::run('UPDATE notifications SET lu = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        Database::run('UPDATE notifications SET lu = 1 WHERE user_id = ?', [$userId]);
    }

    /**
     * Liste paginée de toutes les notifications d'un utilisateur.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, last_page: int}
     */
    public static function all(int $userId, int $perPage = 20, int $page = 1): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ? ORDER BY date_creation DESC';

        return Database::paginate($sql, [$userId], $perPage, $page);
    }

    /**
     * Push web (VAPID). Retourne le nombre de notifications envoyées.
     */
    public static function push(int $userId, string $titre, string $message, ?string $url = null): int
    {
        $subscriptions = Database::all(
            'SELECT * FROM push_subscriptions WHERE user_id = ?',
            [$userId]
        );

        $public  = config('vapid.public');
        $private = config('vapid.private');
        if ($public === '' || $private === '' || $subscriptions === []) {
            return 0;
        }

        $sent = 0;
        foreach ($subscriptions as $sub) {
            $payload = json_encode([
                'title'   => $titre,
                'body'    => $message,
                'icon'    => asset('/assets/img/icon-192.png'),
                'url'     => $url,
                'badge'   => asset('/assets/img/icon-192.png'),
            ], JSON_UNESCAPED_UNICODE);

            $res = WebPush::send($sub, $payload);
            if ($res) {
                $sent++;
            } else {
                // Abonnement invalide → nettoyage
                Database::delete('push_subscriptions', 'id = ?', [$sub['id']]);
            }
        }

        return $sent;
    }
}
