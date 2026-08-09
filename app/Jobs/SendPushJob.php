<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Notification;

/**
 * Envoie une notification push web (PWA).
 */
final class SendPushJob extends Job
{
    public function handle(array $payload): void
    {
        Notification::push(
            (int) ($payload['user_id'] ?? 0),
            (string) ($payload['titre'] ?? ''),
            (string) ($payload['message'] ?? ''),
            $payload['url'] ?? null
        );
    }
}
