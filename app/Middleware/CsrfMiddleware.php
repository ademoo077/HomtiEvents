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
            if (Csrf::validate()) {
                return;
            }

            // Sur la page de connexion, un échec CSRF correspond le plus souvent à une
            // session/cookie périmée (ex. accès en HTTP après une session Secure).
            // Au lieu d'un 419 brut (« Session expirée »), on régénère le jeton et on
            // renvoie proprement vers le formulaire : le rechargement corrige le souci.
            if (self::isLoginRoute()) {
                Csrf::rotate();
                flash('error', 'Session expirée. Veuillez réessayer.');
                redirect('auth/login');
            }

            Csrf::check();
        }
    }

    private static function isLoginRoute(): bool
    {
        $path = '/' . trim((string) ($_SERVER['REQUEST_URI'] ?? ''), '/');
        $path = strtok($path, '?') ?: $path;

        return $path === '/auth/login' || $path === '/auth/verify-2fa';
    }
}
