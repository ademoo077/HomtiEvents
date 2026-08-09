<?php

declare(strict_types=1);

use App\Bootstrap;
use App\Helpers\Rbac;

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return match ($value) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        return Bootstrap::config($key, $default);
    }
}

if (! function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return config('paths.app') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return config('paths.root') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return config('paths.public') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return config('paths.storage') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('normalizePath')) {
    function normalizePath(string $path): string
    {
        // Les URL absolues (avec schéma) ne doivent pas être mangées.
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $path)) {
            $scheme = substr($path, 0, strpos($path, '://') + 3);
            $rest   = substr($path, strlen($scheme));

            return $scheme . ltrim(normalizePath($rest), '/');
        }

        $parts = explode('/', $path);
        $clean = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..' && ! empty($clean)) {
                array_pop($clean);

                continue;
            }

            if ($part !== '..') {
                $clean[] = $part;
            }
        }

        $result = '/' . implode('/', $clean);

        if (str_ends_with($path, '/') && $result !== '/') {
            $result .= '/';
        }

        return $result;
    }
}

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if (PHP_SAPI !== 'cli') {
            $host = $_SERVER['HTTP_HOST'] ?? null;
            if ($host !== null && $host !== '') {
                $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $base   = $scheme . '://' . $host;
            }
        }

        $full = $base . '/' . ltrim($path, '/');

        return normalizePath($full);
    }
}

if (! function_exists('asset')) {
    function asset(string $path): string
    {
        $full = url($path);
        $file = public_path($path);
        if (is_file($file)) {
            $mtime = @filemtime($file);
            if ($mtime !== false) {
                $sep = str_contains($full, '?') ? '&' : '?';
                $full .= $sep . 'v=' . $mtime;
            }
        }
        return $full;
    }
}

if (! function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        $viewPath = app_path('Views') . '/' . str_replace('.', '/', $name) . '.php';

        if (! is_file($viewPath)) {
            throw new RuntimeException("Vue introuvable : {$name}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;

        return (string) ob_get_clean();
    }
}

if (! function_exists('render')) {
    function render(string $name, array $data = [], string $layout = 'main'): void
    {
        $content = view($name, $data);

        echo view('layouts.' . $layout, array_merge($data, ['content' => $content]));
    }
}

if (! function_exists('redirect')) {
    function redirect(string $path): never
    {
        if (! str_starts_with($path, 'http')) {
            $path = url($path);
        }

        header('Location: ' . $path);
        exit;
    }
}

if (! function_exists('abort')) {
    function abort(int $code = 404, string $message = 'Page introuvable'): never
    {
        http_response_code($code);
        echo view('layouts.' . ($code === 403 ? '403' : ($code === 500 ? '500' : '404')), ['message' => $message]);
        exit;
    }
}

if (! function_exists('request_method')) {
    function request_method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}

if (! function_exists('request_path')) {
    function request_path(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return '/' . trim((string) $path, '/');
    }
}

if (! function_exists('input')) {
    function input(string $key, mixed $default = null): mixed
    {
        $from = request_method() === 'GET' ? $_GET : $_POST;

        return $from[$key] ?? $default;
    }
}

if (! function_exists('all_input')) {
    function all_input(): array
    {
        if (request_method() === 'GET') {
            return $_GET;
        }

        $data = $_POST;
        $raw  = file_get_contents('php://input');

        if ($raw !== '' && $raw !== false && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        }

        return $data;
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (! function_exists('flash')) {
    function flash(string $key, ?string $message = null, string $type = 'success'): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = ['message' => $message, 'type' => $type];

            return null;
        }

        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $value ? ($value['message'] ?? null) : null;
    }
}

if (! function_exists('flash_type')) {
    function flash_type(string $key): string
    {
        $value = $_SESSION['_flash'][$key] ?? null;

        return $value ? ($value['type'] ?? 'success') : 'success';
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return App\Helpers\Csrf::token();
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?array
    {
        return App\Helpers\Session::user();
    }
}

if (! function_exists('is_logged')) {
    function is_logged(): bool
    {
        return App\Helpers\Session::isLogged();
    }
}

if (! function_exists('can')) {
    function can(string $permission, ?array $user = null): bool
    {
        return Rbac::can($permission, $user ?? current_user());
    }
}

if (! function_exists('has_role')) {
    function has_role(string $role, ?array $user = null): bool
    {
        return Rbac::hasRole($role, $user ?? current_user());
    }
}

if (! function_exists('user_role')) {
    function user_role(?array $user = null): ?string
    {
        return Rbac::role($user ?? current_user());
    }
}

if (! function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $routes = \App\Helpers\Router::$routes;

        foreach ($routes as $route) {
            if (($route['name'] ?? '') === $name) {
                $path = $route['path'];
                foreach ($params as $k => $v) {
                    $path = str_replace('{' . $k . '}', (string) $v, $path);
                }

                return url($path);
            }
        }

        return url('/');
    }
}

if (! function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (! function_exists('client_ip')) {
    function client_ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}

if (! function_exists('client_user_agent')) {
    function client_user_agent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

if (! function_exists('statut_key')) {
    /**
     * Clé normalisée (minuscules, sans accent) d'un statut d'événement.
     *
     * strtolower() ne gère pas l'UTF-8 (ex. 'VALIDÉ' → 'validÉ') : on passe
     * par mb_strtolower pour générer des clés de traduction et des classes
     * CSS stables (statut_en_cours, statut_valide, …).
     */
    function statut_key(string $statut): string
    {
        $lower = function_exists('mb_strtolower')
            ? mb_strtolower($statut, 'UTF-8')
            : strtolower($statut);

        return str_replace(['é', 'è', 'ê', 'à', 'ù', 'ç'], ['e', 'e', 'e', 'a', 'u', 'c'], $lower);
    }
}

if (! function_exists('statut_label')) {
    /**
     * Libellé traduit d'un statut d'événement.
     */
    function statut_label(string $statut): string
    {
        return __('evenements.statut_' . statut_key($statut));
    }
}

if (! function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        $lines = App\Helpers\I18n::lines($locale);
        $text  = $lines[$key] ?? ($lines['common.' . $key] ?? $key);

        foreach ($replace as $search => $value) {
            $text = str_replace(':' . $search, (string) $value, $text);
        }

        return $text;
    }
}

if (! function_exists('lang')) {
    function lang(string $locale): void
    {
        App\Helpers\I18n::set($locale);
    }
}

if (! function_exists('settings')) {
    function settings(string $key, mixed $default = null): mixed
    {
        static $cache = null;

        if ($cache === null) {
            $rows = \App\Helpers\Database::all('SELECT cle, valeur, type FROM landing_settings');
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['cle']] = $row['type'] === 'json' && $row['valeur']
                    ? json_decode($row['valeur'], true)
                    : $row['valeur'];
            }
        }

        return $cache[$key] ?? $default;
    }
}

if (! function_exists('dashboard_path')) {
    function dashboard_path(): string
    {
        $user = App\Helpers\Session::user();
        if ($user === null) {
            return url('auth/login');
        }

        $role = App\Helpers\Rbac::role($user);
        return match ($role) {
            'wilaya'      => url('wilaya/dashboard'),
            'association' => ! empty($user['association_id']) ? url('association') : url('association/demande'),
            'epic'        => url('epic'),
            'membre'      => url('dashboard'),
            'citoyen'     => url('citoyen'),
            default       => url('/'),
        };
    }
}

if (! function_exists('association_badge')) {
    /**
     * Badge virtuel d'une association agréée par la Wilaya.
     *
     * Affiche un chip « Association agréée » avec le numéro d'agrément
     * quand l'association est validée (valide = 1).
     *
     * @param array<string, mixed>|null $association
     */
    function association_badge(?array $association, string $extraClass = ''): string
    {
        if ($association === null || (int) ($association['valide'] ?? 0) !== 1) {
            return '';
        }

        $nom = trim((string) ($association['nom'] ?? ''));
        $agrement = trim((string) ($association['numero_agrement'] ?? ''));

        $label = __('common.association_agreer');
        $html  = '<span class="badge-association-agreer ' . e($extraClass) . '" title="' . e($nom) . '">';
        $html .= '<i class="mdi mdi-shield-check" aria-hidden="true"></i>';
        $html .= '<span>' . e($label) . '</span>';
        if ($agrement !== '') {
            $html .= '<span class="badge-association-agreer-num">N° ' . e($agrement) . '</span>';
        }
        $html .= '</span>';

        return $html;
    }
}
