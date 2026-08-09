<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Rbac;
use App\Helpers\Session;

/**
 * Exige un ou plusieurs rôles (portée RBAC).
 *
 * Usage : ['role:wilaya'] ou ['role:association,epic'] ou ['role:>=3']
 */
final class RoleMiddleware
{
    public function handle(string $roles): void
    {
        $user = Session::user();

        if ($user === null) {
            redirect('auth/login');
        }

        if (str_starts_with($roles, '>=')) {
            if (! Rbac::levelAtLeast((int) substr($roles, 2), $user)) {
                abort(403, 'Accès refusé');
            }

            return;
        }

        $allowed = array_map('trim', explode(',', $roles));

        if (! in_array(Rbac::role($user), $allowed, true)) {
            abort(403, 'Accès refusé');
        }
    }
}
