<?php

/**
 * Chemins critiques de l'application.
 */

declare(strict_types=1);

return [
    'root'      => dirname(__DIR__, 2),
    'app'       => dirname(__DIR__),
    'public'    => dirname(__DIR__, 2) . '/public',
    'storage'   => dirname(__DIR__, 2) . '/storage',
    'lang'      => dirname(__DIR__, 2) . '/lang',
    'views'     => dirname(__DIR__) . '/Views',
    'uploads'   => [
        'agrements' => dirname(__DIR__, 2) . '/public/uploads/agrements',
        'photos'    => dirname(__DIR__, 2) . '/public/uploads/photos',
        'avatars'   => dirname(__DIR__, 2) . '/public/uploads/avatars',
        'landing'   => dirname(__DIR__, 2) . '/public/uploads/landing',
    ],
    'queue'     => dirname(__DIR__, 2) . '/storage/queue',
    'backups'   => dirname(__DIR__, 2) . '/storage/backups',
    'pdfs'      => dirname(__DIR__, 2) . '/storage/pdfs',
];
