<?php

/**
 * Configuration globale de l'application.
 */

declare(strict_types=1);

return [
    'name'    => env('APP_NAME', 'Wilaya Harmonia'),
    'env'     => env('APP_ENV', 'local'),
    'debug'   => (bool) env('APP_DEBUG', false),
    'url'     => env('APP_URL', 'http://localhost:8080'),
    'locale'  => env('APP_LOCALE', 'fr'),
    'locales' => ['fr', 'ar'],
    'timezone' => 'Africa/Algiers',

    'key'     => env('APP_KEY', ''),
    'cipher'  => 'AES-256-CBC',

    'pwa'     => (bool) env('PWA_ENABLED', true),

    'vapid'   => [
        'public'  => env('VAPID_PUBLIC_KEY', ''),
        'private' => env('VAPID_PRIVATE_KEY', ''),
        'subject' => env('VAPID_SUBJECT', 'mailto:contact@wilaya-harmonia.dz'),
    ],

    'security' => [
        'session_secure' => (bool) env('SESSION_SECURE', false),
        'csp_enabled'    => (bool) env('CSP_ENABLED', true),
        'upload_max'     => (int) env('UPLOAD_MAX_SIZE', 5242880),
    ],

    'sla' => [
        'rappel_j2'   => 48 * 3600,  // J-2 en secondes
        'rappel_j1'   => 24 * 3600,  // J-1
        'album_delai' => 48 * 3600,  // Délai création album après événement
    ],
];
