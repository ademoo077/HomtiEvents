<?php

declare(strict_types=1);

namespace App;

use App\Helpers\Database;
use App\Helpers\Router;
use App\Helpers\Session;

/**
 * Amorçage de l'application Wilaya Harmonia.
 */
final class Bootstrap
{
    private static array $config = [];

    public static function run(): void
    {
        self::boot();
        self::bootstrapSession();

        // Routage
        $router = new Router();
        require BASE_PATH . '/app/Routes/web.php';
        require BASE_PATH . '/app/Routes/api.php';

        $router->dispatch();

        // Periodic cleanup: expired password reset tokens (once per day)
        self::cleanupExpiredTokens();
    }

    /**
     * Nettoyage périodique des tokens expirés (une fois par jour).
     */
    private static function cleanupExpiredTokens(): void
    {
        $lockFile = storage_path('logs') . '/.token_cleanup_' . date('Y-m-d');
        if (is_file($lockFile)) {
            return;
        }

        try {
            Database::run('DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)');
            file_put_contents($lockFile, (string) time());
        } catch (\Throwable) {
            // Silencieux — table peut ne pas exister encore
        }
    }

    /**
     * Charge l'environnement et la configuration (sans session ni routage).
     */
    public static function boot(): void
    {
        ini_set('pcre.jit', '0');

        self::loadEnv();
        self::setEnvironment();
        self::loadConfig();
    }

    private static function loadEnv(): void
    {
        $file = BASE_PATH . '/.env';
        if (! is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if (preg_match('/^"(.*)"$/', $value, $m)) {
                $value = $m[1];
            } elseif (preg_match('/^\'(.*)\'$/', $value, $m)) {
                $value = $m[1];
            }

            $value = match (strtolower($value)) {
                'true'      => '1',
                'false'     => '0',
                'null', ''  => '',
                default     => $value,
            };

            if ($value !== '' && getenv($key) === false) {
                putenv("{$key}={$value}");
            }
        }
    }

    private static function setEnvironment(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors', env('APP_DEBUG', '0') === '1' ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', storage_path('logs') . '/app-' . date('Y-m-d') . '.log');
        date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Algiers') ?: 'Africa/Algiers');

        if (! is_dir(storage_path('logs'))) {
            mkdir(storage_path('logs'), 0775, true);
        }

        // Output compression for PHP built-in server (Apache has mod_deflate)
        if (PHP_SAPI !== 'cli' && ! headers_sent()) {
            $accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
            if (str_contains($accept, 'gzip') && extension_loaded('zlib')) {
                if (! ini_get('output_buffering')) {
                    ob_start('ob_gzhandler', 16384);
                }
            }
        }
    }

    private static function loadConfig(): void
    {
        foreach (glob(__DIR__ . '/Config/*.php') as $file) {
            $key = basename($file, '.php');
            $config = require $file;

            self::$config[$key] = $config;

            // Flatten top-level scalar array keys from app.php (security, vapid, sla...)
            if ($key === 'app' && is_array($config)) {
                foreach ($config as $subKey => $subValue) {
                    if (! isset(self::$config[$subKey])) {
                        self::$config[$subKey] = $subValue;
                    }
                }
            }
        }
    }

    private static function bootstrapSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('WH_SESSID');
        // Déploiement actuellement servi uniquement en HTTP (le point HTTPS :443 n'est pas
        // joignable). Un cookie marqué « Secure » n'est de toute façon jamais renvoyé par le
        // navigateur en HTTP → session perdue → erreur CSRF « Session expirée » / blocage sur
        // la page de connexion. On n'active donc « Secure » que si c'est explicitement
        // configuré (sesion en HTTPS réel), sans jamais le déduire de $_SERVER['HTTPS'], dont
        // la valeur est incohérente en fonction du réseau/NAT (parfois « on » même en HTTP).
        $isSecure = (bool) config('security.session_secure', false);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $isSecure,
            'samesite' => 'Lax',
        ]);

        if (is_dir(BASE_PATH . '/storage/sessions')) {
            session_save_path(BASE_PATH . '/storage/sessions');
        }

        session_start();

        // Persister la session en base pour le dashboard sécurité
        if (Session::isLogged()) {
            Session::persistToDatabase();
        }
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }

        $value = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::config($key, $default);
    }
}
