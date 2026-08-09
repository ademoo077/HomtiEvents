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
}
