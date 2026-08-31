<?php

declare(strict_types=1);

/**
 * Front controller de Wilaya Harmonia.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

// Headers de sécurité — OWASP ASVS 14.4, RNSI §6 (défense en profondeur)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=(self), payment=()');
header('X-Permitted-Cross-Domain-Policies: none');
// HSTS pour https://homtievents.work.gd — HTTPS forcé
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if ((bool) config('security.csp_enabled', true)) {
    // CSP sans unsafe-eval (OWASP A05) — unsafe-inline conservé pour compat inline scripts existants, à migrer vers nonce/hash P1
    $csp = "default-src 'self'; "
         . "img-src 'self' data: blob: https://unpkg.com https://cdn.jsdelivr.net https://*.basemaps.cartocdn.com https://*.tile.openstreetmap.org https://api.qrserver.com; "
         . "script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; "
         . "style-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; "
         . "font-src 'self' data: https://unpkg.com https://cdn.jsdelivr.net; "
         . "connect-src 'self' https://*.basemaps.cartocdn.com https://*.tile.openstreetmap.org https://api.qrserver.com;";
    header("Content-Security-Policy: {$csp}");
}

// Serveur de dev (php -S) : servir les fichiers statiques directement
// (le manifest.json est généré dynamiquement selon la locale → routé vers PHP)
if (PHP_SAPI === 'cli-server') {
    $staticPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $staticFile = __DIR__ . $staticPath;
    if ($staticPath !== '/manifest.json' && is_file($staticFile) && $staticPath !== '/index.php') {
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
