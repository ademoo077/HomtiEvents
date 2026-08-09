<?php

/**
 * Configuration Redis (utilisée par le worker de queue si activée).
 */

declare(strict_types=1);

return [
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'port'     => (int) env('REDIS_PORT', 6379),
    'password' => env('REDIS_PASSWORD', null),
    'timeout'  => 2.0,
    'database' => 0,
];
