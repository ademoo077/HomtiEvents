<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Notification;
use App\Helpers\Session;

/**
 * Notifications in-app : compteur non lues (AJAX), lecture, tout marquer lu.
 */
final class NotificationController extends Controller
{
    public function unreadCount(): never
    {
        $this->requireAuth();

        $user = Session::user();
        $userId = $user['id'] ?? null;
        $count = $userId !== null ? Notification::unreadCount((int) $userId) : 0;

        json_response(['success' => true, 'count' => $count]);
    }

    /**
     * Liste des notifications non lues (polled via AJAX pour la popup).
     */
    public function unread(): never
    {
        $this->requireAuth();

        $user = Session::user();
        $userId = $user['id'] ?? null;

        if ($userId === null) {
            json_response(['success' => true, 'notifications' => []]);
        }

        $items = Notification::getUnread((int) $userId);
        $role = user_role();
        $formatted = array_map(static function (array $n) use ($role): array {
            $data = $n['data_json'] !== null ? json_decode((string) $n['data_json'], true) : [];

            $link = null;
            if (is_array($data)) {
                if (isset($data['link'])) {
                    $link = $data['link'];
                } elseif (isset($data['evenement_id'])) {
                    $link = $role === 'association'
                        ? 'association/' . (int) $data['evenement_id']
                        : 'wilaya/evenements/' . (int) $data['evenement_id'];
                }
            }

            return [
                'id'         => (int) $n['id'],
                'titre'      => $n['titre'],
                'message'    => $n['message_notif'],
                'type'       => $n['type'],
                'date'       => $n['date_creation'],
                'link'       => $link,
            ];
        }, $items);

        json_response(['success' => true, 'notifications' => $formatted]);
    }

    public function read(string $id): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = Session::user();
        $userId = $user['id'] ?? null;

        if ($userId !== null && ctype_digit($id)) {
            Notification::markRead((int) $id, (int) $userId);
        }

        json_response(['success' => true]);
    }

    public function readAll(): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user = Session::user();
        $userId = $user['id'] ?? null;

        if ($userId !== null) {
            Notification::markAllRead((int) $userId);
        }

        json_response(['success' => true]);
    }

    public function stream(): never
    {
        $this->requireAuth();
        $user = Session::user();
        $userId = (int) ($user['id'] ?? 0);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        $count = Notification::unreadCount($userId);
        echo "data: " . json_encode(['count' => $count]) . "\n\n";
        @ob_flush(); @flush();
        // keep-alive single shot — client will fallback to polling
        exit;
    }
}
