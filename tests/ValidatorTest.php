<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequired(): void
    {
        $this->assertFalse(Validator::make(['titre' => ''], ['titre' => 'required'])->validate());
        $this->assertFalse(Validator::make(['titre' => null], ['titre' => 'required'])->validate());
        $this->assertTrue(Validator::make(['titre' => 'Nettoyage'], ['titre' => 'required'])->validate());
    }

    public function testEmail(): void
    {
        $this->assertTrue(Validator::make(['mail' => 'user@exemple.dz'], ['mail' => 'email'])->validate());
        $this->assertFalse(Validator::make(['mail' => 'pas-un-email'], ['mail' => 'email'])->validate());
    }

    public function testMinMax(): void
    {
        $v = Validator::make(['champ' => 'abc'], ['champ' => 'min:5|max:3']);
        $this->assertFalse($v->validate());
        $this->assertNotEmpty($v->errors());
    }

    public function testIn(): void
    {
        $this->assertTrue(Validator::make(['s' => 'PROGRAMME'], ['s' => 'in:EN_ATTENTE,PROGRAMME,TERMINE'])->validate());
        $this->assertFalse(Validator::make(['s' => 'SUPPRIME'], ['s' => 'in:EN_ATTENTE,PROGRAMME'])->validate());
    }

    public function testConfirmed(): void
    {
        $this->assertTrue(Validator::make(['mdp' => 'secret', 'mdp_confirmation' => 'secret'], ['mdp' => 'confirmed'])->validate());
        $this->assertFalse(Validator::make(['mdp' => 'secret', 'mdp_confirmation' => 'autre'], ['mdp' => 'confirmed'])->validate());
    }

    public function testUnique(): void
    {
        $this->assertFalse(Validator::make(['email' => 'wilaya@wilaya-harmonia.dz'], ['email' => 'unique:users,email'])->validate());
        $this->assertTrue(Validator::make(['email' => 'nouveau@exemple.dz'], ['email' => 'unique:users,email'])->validate());
    }

    public function testDateAfter(): void
    {
        $this->assertTrue(Validator::make(['d' => '2026-08-10'], ['d' => 'date_after:2026-08-01'])->validate());
        $this->assertFalse(Validator::make(['d' => '2026-07-01'], ['d' => 'date_after:2026-08-01'])->validate());
    }

    public function testHexColor(): void
    {
        $this->assertTrue(Validator::make(['c' => '#00ff99'], ['c' => 'hex_color'])->validate());
        $this->assertFalse(Validator::make(['c' => 'red'], ['c' => 'hex_color'])->validate());
    }

    public function testNullableSkipsAbsentField(): void
    {
        $this->assertTrue(Validator::make([], ['description' => 'nullable|string|max:100'])->validate());
        $this->assertTrue(Validator::make(['description' => 'abc'], ['description' => 'nullable|string|max:100'])->validate());
        $this->assertFalse(Validator::make([], ['description' => 'string'])->validate());
    }

    public function testBoolean(): void
    {
        $this->assertTrue(Validator::make(['actif' => 'on'], ['actif' => 'boolean'])->validate());
        $this->assertTrue(Validator::make(['actif' => 0], ['actif' => 'boolean'])->validate());
        $this->assertTrue(Validator::make(['actif' => '1'], ['actif' => 'boolean'])->validate());
        $this->assertFalse(Validator::make(['actif' => 'oui'], ['actif' => 'boolean'])->validate());
    }

    public function testPhone(): void
    {
        $this->assertTrue(Validator::make(['tel' => '0550 12 34 56'], ['tel' => 'phone'])->validate());
        $this->assertTrue(Validator::make(['tel' => '+213 550123456'], ['tel' => 'phone'])->validate());
        $this->assertFalse(Validator::make(['tel' => 'abc'], ['tel' => 'phone'])->validate());
    }

    public function testUuid(): void
    {
        $this->assertTrue(Validator::make(['u' => '9a8c7b6d-1234-4abc-8def-0123456789ab'], ['u' => 'uuid'])->validate());
        $this->assertFalse(Validator::make(['u' => 'pas-un-uuid'], ['u' => 'uuid'])->validate());
    }

    public function testDistinct(): void
    {
        $this->assertTrue(Validator::make(['ids' => [1, 2, 3]], ['ids' => 'array|distinct'])->validate());
        $this->assertFalse(Validator::make(['ids' => [1, 1, 2]], ['ids' => 'array|distinct'])->validate());
    }

    public function testArrayRule(): void
    {
        $this->assertTrue(Validator::make(['ids' => [1]], ['ids' => 'array'])->validate());
        $this->assertFalse(Validator::make(['ids' => '1'], ['ids' => 'array'])->validate());
    }
}
