<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use PHPUnit\Framework\TestCase;

/**
 * Base isolée : chaque test s'exécute dans une transaction rollbackée.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Database::connection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (Database::connection()->inTransaction()) {
            Database::connection()->rollBack();
        }
        parent::tearDown();
    }

    protected function userByEmail(string $email): array
    {
        $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
        $this->assertNotNull($user, "Utilisateur introuvable : {$email}");

        return $user;
    }

    protected function eventByStatus(string $statut): array
    {
        $event = Database::one('SELECT * FROM evenements WHERE statut = ? ORDER BY id ASC LIMIT 1', [$statut]);
        $this->assertNotNull($event, "Aucun événement avec le statut {$statut}");

        return $event;
    }
}
