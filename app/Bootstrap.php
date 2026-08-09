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
        error_reporting(E_ALL);
        ini_set('display_errors', env('APP_DEBUG', '0') === '1' ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', storage_path('logs') . '/app-' . date('Y-m-d') . '.log');
        date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Algiers') ?: 'Africa/Algiers');

        if (! is_dir(storage_path('logs'))) {
            mkdir(storage_path('logs'), 0775, true);
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
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => config('security.session_secure', false),
            'samesite' => 'Lax',
        ]);

        if (is_dir(BASE_PATH . '/storage/sessions')) {
            session_save_path(BASE_PATH . '/storage/sessions');
        }

        session_start();
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
