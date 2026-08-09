<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Abac;
use App\Helpers\BusinessRules;
use App\Helpers\Rbac;
use App\Helpers\Security;
use App\Helpers\Session;

/**
 * Couche de contrôle centralisée (Control Layer).
 *
 * Applique à chaue requête :
 *  - contrôle du statut utilisateur (suspendu/banni -> blocage)
 *  - contrôle ABAC global
 *  - blocage IP suspectes
 */
final class SystemControlMiddleware
{
    public function handle(): void
    {
        // 1. Blocage IP suspecte
        if (Security::ipBloquee()) {
            abort(403, 'Accès restreint. Votre adresse IP a été bloquée.');
        }

        // 2. Mode maintenance global
        if ((int) Security::param('maintenance.mode', 0) === 1) {
            $user = Session::user();
            if ($user === null || ! Rbac::hasPermission('control.view')) {
                abort(503, Security::param('maintenance.message', 'Maintenance en cours.'));
            }
        }

        // 3. Statut utilisateur : suspendu / banni -> aucun accès
        BusinessRules::bloquerSiSuspendu(Session::user(false));

        // 4. Contrôle ABAC global de l'action courante
        $user = Session::user(false);
        if ($user !== null && ! Abac::permet($user, 'general', ['path' => request_path()])) {
            abort(403, 'Accès refusé par la politique de contrôle.');
        }
    }
}
