<?php

declare(strict_types=1);

/**
 * Front controller de Wilaya Harmonia.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ((bool) config('security.csp_enabled', true)) {
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https://unpkg.com https://cdn.jsdelivr.net https://*.basemaps.cartocdn.com; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; font-src 'self' data:; connect-src 'self';");
}

// Serveur de dev (php -S) : servir les fichiers statiques directement
if (PHP_SAPI === 'cli-server') {
    $staticPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $staticFile = __DIR__ . $staticPath;
    if (is_file($staticFile) && $staticPath !== '/index.php') {
        return false;
    }
}

try {
    \App\Bootstrap::run();
} catch (Throwable $e) {
    if (config('app.debug')) {
        http_response_code(500);
        echo '<h1>Erreur interne</h1><pre>' . e((string) $e) . '</pre>';
    } else {
        error_log($e->getMessage());
        abort(500, 'Une erreur interne est survenue.');
    }
}
