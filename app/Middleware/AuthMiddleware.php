<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Session;

/**
 * Exige une authentification.
 *
 * Si l'utilisateur n'est pas connecté, il est redirigé vers la page de
 * connexion avec un paramètre `next` qui permet de reprendre le flux
 * d'origine après authentification (ex. : le scan de QR Code).
 */
final class AuthMiddleware
{
    public function handle(): void
    {
        if (! Session::isLogged()) {
            redirect('auth/login?next=' . urlencode(request_path() . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'])));
        }

        // Vérifier si la session a été révoquée par un administrateur
        try {
            $revoked = \App\Helpers\Database::exists(
                'SELECT 1 FROM revoked_sessions WHERE session_id = ?',
                [Session::id()]
            );
            if ($revoked) {
                Session::logout();
                redirect('auth/login');
            }
        } catch (\Throwable) {
            // Table n'existe pas encore — ignorer
        }
    }
}
