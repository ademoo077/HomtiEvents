<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\Validator;

/**
 * Contrôleur de base.
 */
abstract class Controller
{
    protected function view(string $name, array $data = [], string $layout = 'main'): never
    {
        render($name, $data, $layout);
        exit;
    }

    protected function renderContent(string $name, array $data = []): string
    {
        return view($name, $data);
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        return $validator->errors() === [] ? $data : $data;
    }

    protected function backWithErrors(array $errors, array $oldInput = []): never
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old']    = $oldInput;

        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');

        redirect($referer);
    }

    protected function errors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        return $errors;
    }

    protected function requireAuth(): void
    {
        if (! Session::isLogged()) {
            redirect('auth/login');
        }
    }

    /**
     * Vérifie qu'une permission est présente dans la session.
     *
     * @param string|string[] $permissions
     */
    protected function requirePermission(string|array $permissions): void
    {
        $this->requireAuth();

        $perms = is_array($permissions) ? $permissions : [$permissions];

        if (! Rbac::hasAnyPermission($perms)) {
            abort(403, 'Accès non autorisé.');
        }
    }

    protected function user(): ?array
    {
        return Session::user();
    }

    protected function csrfCheck(): void
    {
        if (! Csrf::validate()) {
            abort(419, 'Session expirée.');
        }
    }

    /**
     * Rate limiting simple par IP (fenêtre glissante en base).
     */
    protected function rateLimit(string $key, int $max = 10, int $windowSeconds = 60): bool
    {
        $ip = client_ip();
        $cacheKey = "rl:{$key}:{$ip}";

        $count = (int) ($_SESSION[$cacheKey . ':count'] ?? 0);
        $start = (int) ($_SESSION[$cacheKey . ':start'] ?? time());

        if (time() - $start > $windowSeconds) {
            $count = 0;
            $start = time();
        }

        $count++;

        $_SESSION[$cacheKey . ':count'] = $count;
        $_SESSION[$cacheKey . ':start'] = $start;

        return $count <= $max;
    }
}
