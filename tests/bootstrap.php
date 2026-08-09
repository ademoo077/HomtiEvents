<?php

declare(strict_types=1);

/**
 * Bootstrap des tests — utilise une base dédiée wilaya_harmonia_test.
 */

define('BASE_PATH', dirname(__DIR__));
define('TESTS_BASE_PATH', __DIR__);

// Redirige l'application vers la base de test AVANT le boot.
putenv('DB_DATABASE=wilaya_harmonia_test');

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

\App\Bootstrap::boot();

/**
 * Exécute les fichiers SQL (schéma + références + seed + landing).
 */
function test_migrate(): void
{
    $pdo = \App\Helpers\Database::connection();

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach (glob(BASE_PATH . '/sql/*.sql') as $file) {
        $sql = preg_replace('/^--.*$/m', '', (string) file_get_contents($file));
        foreach (array_filter(explode(';', $sql)) as $query) {
            $query = trim($query);
            if ($query === '') {
                continue;
            }
            if (preg_match('/^(CREATE DATABASE|USE)\b/i', $query)) {
                continue;
            }
            $pdo->exec($query);
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

test_migrate();
