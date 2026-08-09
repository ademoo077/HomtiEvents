<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Rbac;

final class RbacTest extends DatabaseTestCase
{
    public function testRoleAndLevel(): void
    {
        $this->assertSame('wilaya', Rbac::role($this->userByEmail('wilaya@wilaya-harmonia.dz')));
        $this->assertSame('citoyen', Rbac::role($this->userByEmail('sami@citoyen.dz')));

        $this->assertSame(7, Rbac::level($this->userByEmail('wilaya@wilaya-harmonia.dz')));
        $this->assertSame(3, Rbac::level($this->userByEmail('president@elamel.dz')));
        $this->assertSame(1, Rbac::level($this->userByEmail('sami@citoyen.dz')));
    }

    public function testHasRole(): void
    {
        $asso = $this->userByEmail('president@elamel.dz');

        $this->assertTrue(Rbac::hasRole('association', $asso));
        $this->assertFalse(Rbac::hasRole('wilaya', $asso));
    }

    public function testLevelAtLeast(): void
    {
        $wilaya = $this->userByEmail('wilaya@wilaya-harmonia.dz');
        $asso   = $this->userByEmail('president@elamel.dz');
        $citoyen= $this->userByEmail('sami@citoyen.dz');

        $this->assertTrue(Rbac::levelAtLeast(7, $wilaya));
        $this->assertTrue(Rbac::levelAtLeast(3, $asso));
        $this->assertFalse(Rbac::levelAtLeast(4, $asso));
        $this->assertFalse(Rbac::levelAtLeast(2, $citoyen));
    }

    public function testPermissions(): void
    {
        $wilaya = $this->userByEmail('wilaya@wilaya-harmonia.dz');
        $citoyen = $this->userByEmail('sami@citoyen.dz');

        $perms = Rbac::permissions($wilaya);
        $this->assertNotEmpty($perms);
        $this->assertContains('evenement.validate', $perms);

        $this->assertTrue(Rbac::can('evenement.validate', $wilaya));
        $this->assertFalse(Rbac::can('evenement.validate', $citoyen));
    }

    public function testScope(): void
    {
        $asso = $this->userByEmail('president@elamel.dz');
        $wilaya = $this->userByEmail('wilaya@wilaya-harmonia.dz');

        $this->assertSame('', Rbac::scope($wilaya));
        $this->assertSame('e.association_id = ' . (int) $asso['association_id'], Rbac::scope($asso));

        $count = (int) \App\Helpers\Database::value(
            'SELECT COUNT(*) FROM evenements e WHERE 1 ' . (Rbac::scope($asso) !== '' ? 'AND ' . Rbac::scope($asso) : '')
        );
        $this->assertGreaterThanOrEqual(1, $count);
    }
}
