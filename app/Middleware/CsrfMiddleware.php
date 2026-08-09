<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Csrf;

/**
 * Vérification CSRF sur les méthodes POST/PUT/PATCH/DELETE.
 */
final class CsrfMiddleware
{
    public function handle(): void
    {
        if (in_array(request_method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            Csrf::check();
        }
    }
}
