<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Notifications in-app + push + email.
 *
 * Améliorations :
 *  - Push automatique pour les types prioritaires
 *  - Filtrage par préférences utilisateur
 *  - Sender ID pour traçabilité
 *  - Groupement par type/date
 */
final class Notification
{
    /** Types de notification qui déclenchent un push automatique. */
    private const PUSH_TYPES = [
        'routing_alerte',
        'sla_retard',
        'modification_demandee',
        'evenement_valide',
        'evenement_refuse',
        'association_request',
        'epic_anomalie',
    ];

    /**
     * Types considérés comme prioritaires (reçoivent push + email).
     */
    private const HIGH_PRIORITY_TYPES = [
        'routing_alerte',
        'sla_retard',
        'modification_demandee',
        'association_request',
    ];

    public static function send(
        int $userId,
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?int $senderId = null,
        bool $forcePush = false
    ): int {
        // Vérifier les préférences utilisateur
        if (! self::shouldNotify($userId, $type, 'inapp')) {
            return 0;
        }

        $id = Database::insert('notifications', [
            'user_id'       => $userId,
            'sender_id'     => $senderId,
            'titre'         => $titre,
            'message_notif' => mb_substr($message, 0, 255),
            'type'          => $type,
            'data_json'     => $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'lu'            => 0,
        ]);

        // Push automatique pour les types prioritaires ou si forcé
        if ($id > 0 && ($forcePush || in_array($type, self::PUSH_TYPES, true))) {
            $url = $data['link'] ?? ($data['evenement_id'] !== null ? '/wilaya/evenements/' . (int) $data['evenement_id'] : '/');
            Queue::push(SendPushJob::class, [
                'user_id' => $userId,
                'titre'   => $titre,
                'message' => $message,
                'url'     => $url,
            ]);
        }

        return $id;
    }

    /**
     * Envoie à tous les utilisateurs d'un rôle donné.
     */
    public static function sendToRole(
        string $role,
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?int $senderId = null
    ): int {
        $users = Database::all('SELECT id FROM users WHERE role_user = ? AND is_active = 1', [$role]);
        $sent = 0;

        foreach ($users as $user) {
            $result = self::send((int) $user['id'], $titre, $message, $type, $data, $senderId);
            if ($result > 0) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function sendToAssociation(
        int $associationId,
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?int $senderId = null
    ): int {
        $users = Database::all(
            'SELECT id FROM users WHERE association_id = ? AND is_active = 1',
            [$associationId]
        );
        $sent = 0;

        foreach ($users as $user) {
            $result = self::send((int) $user['id'], $titre, $message, $type, $data, $senderId);
            if ($result > 0) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Envoie aux utilisateurs actifs d'une EPIC donnée.
     */
    public static function sendToEpic(
        int $epicId,
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?int $senderId = null
    ): int {
        $users = Database::all(
            'SELECT id FROM users WHERE epic_id = ? AND is_active = 1',
            [$epicId]
        );
        $sent = 0;

        foreach ($users as $user) {
            $result = self::send((int) $user['id'], $titre, $message, $type, $data, $senderId);
            if ($result > 0) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Broadcast à tous les utilisateurs actifs (annonces).
     */
    public static function broadcast(
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?int $senderId = null,
        ?string $targetRole = null
    ): int {
        $sql = 'SELECT id FROM users WHERE is_active = 1';
        $params = [];

        if ($targetRole !== null) {
            $sql .= ' AND role_user = ?';
            $params[] = $targetRole;
        }

        $users = Database::all($sql, $params);
        $sent = 0;

        foreach ($users as $user) {
            $result = self::send((int) $user['id'], $titre, $message, $type, $data, $senderId);
            if ($result > 0) {
                $sent++;
            }
        }

        return $sent;
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
            'SELECT n.*, u.prenom AS sender_prenom, u.nom AS sender_nom
             FROM notifications n
             LEFT JOIN users u ON u.id = n.sender_id
             WHERE n.user_id = ? ORDER BY n.date_creation DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }

    /**
     * Notifications non lues d'un utilisateur.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getUnread(int $userId): array
    {
        return Database::all(
            'SELECT n.id, n.titre, n.message_notif, n.type, n.data_json, n.date_creation,
                    u.prenom AS sender_prenom, u.nom AS sender_nom
             FROM notifications n
             LEFT JOIN users u ON u.id = n.sender_id
             WHERE n.user_id = ? AND n.lu = 0
             ORDER BY n.date_creation DESC',
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
     * Notifications groupées par type.
     */
    public static function grouped(int $userId, int $limit = 50): array
    {
        $all = self::all($userId, $limit);

        $grouped = [];
        foreach ($all['items'] as $n) {
            $type = $n['type'] ?? 'general';
            $grouped[$type][] = $n;
        }

        return ['groups' => $grouped, 'total' => $all['total']];
    }

    /**
     * Liste paginée de toutes les notifications d'un utilisateur.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, last_page: int}
     */
    public static function all(int $userId, int $perPage = 20, int $page = 1): array
    {
        $sql = 'SELECT n.*, u.prenom AS sender_prenom, u.nom AS sender_nom
                FROM notifications n
                LEFT JOIN users u ON u.id = n.sender_id
                WHERE n.user_id = ? ORDER BY n.date_creation DESC';

        return Database::paginate($sql, [$userId], $perPage, $page);
    }

    /**
     * Liste paginée du centre de notifications, ordonnée par type puis date.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, last_page: int}
     */
    public static function center(int $userId, int $perPage = 20, int $page = 1): array
    {
        $sql = 'SELECT n.*, u.prenom AS sender_prenom, u.nom AS sender_nom
                FROM notifications n
                LEFT JOIN users u ON u.id = n.sender_id
                WHERE n.user_id = ? ORDER BY n.type ASC, n.date_creation DESC';

        return Database::paginate($sql, [$userId], $perPage, $page);
    }

    /**
     * Nettoyage des anciennes notifications (> 90 jours).
     */
    public static function cleanup(int $days = 90): int
    {
        Database::run(
            'DELETE FROM notifications WHERE date_creation < DATE_SUB(NOW(), INTERVAL ? DAY) AND lu = 1',
            [$days]
        );

        return Database::value('SELECT ROW_COUNT()', []) ?? 0;
    }

    /**
     * Libellé lisible d'un type de notification.
     */
    public static function typeLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return __('notifications.type_general');
        }

        return __('notifications.type_' . $type);
    }

    /**
     * Icône MDI par type de notification.
     */
    public static function typeIcon(?string $type): string
    {
        return match ($type) {
            'evenement_valide'       => 'mdi-check-decagram',
            'evenement_refuse'       => 'mdi-close-octagon',
            'modification_demandee'  => 'mdi-pencil-outline',
            'qr_genere'              => 'mdi-qrcode',
            'evenement_create'       => 'mdi-calendar-plus',
            'evenement_annule'       => 'mdi-calendar-remove',
            'evenement_resoumis'     => 'mdi-sync',
            'routing_alerte'         => 'mdi-sitemap',
            'sla_retard'             => 'mdi-timer-alert',
            'album_publie'           => 'mdi-image-multiple',
            'epic_anomalie'          => 'mdi-alert-octagon',
            'association_request'    => 'mdi-account-clock',
            'membre_invite'          => 'mdi-account-plus',
            'membre_accepte'         => 'mdi-account-check',
            'membre_retire'          => 'mdi-account-minus',
            'membre_deja_inscrit'    => 'mdi-account-alert',
            'rappel'                 => 'mdi-bell-ring',
            'comment'                => 'mdi-comment-text-outline',
            'note'                   => 'mdi-lock-outline',
            'announcement'           => 'mdi-bullhorn-outline',
            default                  => 'mdi-bell-outline',
        };
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
                Database::delete('push_subscriptions', 'id = ?', [$sub['id']]);
            }
        }

        return $sent;
    }

    /**
     * Vérifie si l'utilisateur souhaite recevoir ce type de notification.
     */
    private static function shouldNotify(int $userId, ?string $type, string $channel): bool
    {
        $prefs = Database::one(
            'SELECT * FROM user_preferences WHERE user_id = ?',
            [$userId]
        );

        if ($prefs === null) {
            return true;
        }

        $key = 'notif_' . $channel;
        if (isset($prefs[$key]) && (int) $prefs[$key] === 0) {
            return false;
        }

        return true;
    }

    /**
     * Envoie notification + email pour un type donné.
     */
    public static function sendWithEmail(
        int $userId,
        string $titre,
        string $message,
        ?string $type = null,
        ?array $data = null,
        ?string $emailSubject = null,
        ?string $emailBody = null,
        ?int $senderId = null
    ): int {
        $id = self::send($userId, $titre, $message, $type, $data, $senderId);

        if ($id > 0 && in_array($type, self::HIGH_PRIORITY_TYPES, true)) {
            $user = Database::one('SELECT email FROM users WHERE id = ?', [$userId]);
            if ($user !== null && ! empty($user['email'])) {
                Queue::push(SendNotificationJob::class, [
                    'to'      => $user['email'],
                    'subject' => $emailSubject ?? $titre,
                    'body'    => $emailBody ?? $message,
                ]);
            }
        }

        return $id;
    }
}
