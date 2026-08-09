<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Protection CSRF — double-submit pattern.
 */
final class Csrf
{
    public static function token(): string
    {
        if (! isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function validate(?string $token = null): bool
    {
        $provided = $token ?? ($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));
        $expected = $_SESSION['_csrf'] ?? null;

        if ($expected === null || $provided === null) {
            return false;
        }

        return hash_equals($expected, (string) $provided);
    }

    public static function check(): void
    {
        if (! self::validate()) {
            abort(419, 'Session expirée. Merci de recharger la page.');
        }
    }
}
