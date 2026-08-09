<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Gestion des sessions utilisateur (PDO stocké si disponible).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start();
    }

    public static function id(): string
    {
        return session_id();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Authentification ───────────────────────────────────────────

    public static function login(int $userId): void
    {
        self::regenerate();
        $_SESSION['user_id'] = $userId;

        Database::run(
            'UPDATE users SET last_login = NOW() WHERE id = ?',
            [$userId]
        );
    }

    public static function logout(): void
    {
        $id = self::userId();
        if ($id !== null) {
            Database::run('UPDATE sessions SET user_id = NULL WHERE user_id = ?', [$id]);
        }

        self::forget('user_id');
        self::destroy();
    }

    public static function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;

        return $id !== null ? (int) $id : null;
    }

    public static function isLogged(): bool
    {
        return self::userId() !== null;
    }

    public static function user(bool $refresh = false): ?array
    {
        static $cache = null;
        static $cachedId = null;

        $id = self::userId();
        if ($id === null) {
            $cache = null;
            $cachedId = null;

            return null;
        }

        if ($cachedId === $id && $cache !== null && ! $refresh) {
            return $cache;
        }

        $cache = Database::one(
            'SELECT u.*, a.nom AS association_nom, a.valide AS association_valide, e.nom AS epic_nom
             FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             LEFT JOIN epic e ON e.id = u.epic_id
             WHERE u.id = ? AND u.is_active = 1',
            [$id]
        );

        $cachedId = $id;

        return $cache;
    }

    public static function refreshUser(): void
    {
        if (self::userId() !== null) {
            self::user(true);
        }
    }

    public static function persistToDatabase(): void
    {
        if (! is_logged() || ! Database::connection()) {
            return;
        }

        Database::run(
            'INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), last_activity = VALUES(last_activity)',
            [
                self::id(),
                self::userId(),
                client_ip(),
                mb_substr(client_user_agent(), 0, 255),
                session_encode() ?: '',
                time(),
            ]
        );
    }
}
