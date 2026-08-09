<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Notification;

/**
 * Envoie une notification in-app.
 */
final class SendNotificationJob extends Job
{
    public function handle(array $payload): void
    {
        Notification::send(
            (int) ($payload['user_id'] ?? 0),
            (string) ($payload['titre'] ?? ''),
            (string) ($payload['message'] ?? ''),
            $payload['type'] ?? null,
            $payload['data'] ?? null
        );
    }
}
