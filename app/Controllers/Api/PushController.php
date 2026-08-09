<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\Session;

final class PushController
{
    public function subscribe(): never
    {
        if (! Session::isLogged()) {
            json_response(['success' => false, 'message' => 'Authentification requise.'], 401);
        }

        $body = json_decode((string) file_get_contents('php://input'), true);

        if (! is_array($body) || empty($body['endpoint']) || empty($body['p256dh']) || empty($body['auth'])) {
            json_response(['success' => false, 'message' => 'Subscription invalide.'], 400);
        }

        // Évite les doublons par endpoint
        Database::delete('push_subscriptions', 'endpoint = ?', [$body['endpoint']]);

        Database::insert('push_subscriptions', [
            'user_id'   => (int) Session::userId(),
            'endpoint'  => (string) $body['endpoint'],
            'p256dh'    => (string) $body['p256dh'],
            'auth'      => (string) $body['auth'],
            'user_agent' => mb_substr(client_user_agent(), 0, 255),
        ]);

        json_response(['success' => true], 201);
    }

    public function unsubscribe(): never
    {
        $body = json_decode((string) file_get_contents('php://input'), true);

        if (is_array($body) && ! empty($body['endpoint'])) {
            Database::delete('push_subscriptions', 'endpoint = ?', [$body['endpoint']]);
        }

        json_response(['success' => true]);
    }
}
