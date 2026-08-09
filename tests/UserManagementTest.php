<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\ControlCenter;
use App\Helpers\Database;

final class UserManagementTest extends DatabaseTestCase
{
    private function roleId(string $role): int
    {
        return (int) Database::value('SELECT id FROM roles WHERE nom = ?', [$role]);
    }

    public function testCreerCitoyenPersisteUserRoleEtAudit(): void
    {
        $email = 'citoyen_' . bin2hex(random_bytes(4)) . '@test.local';

        $id = ControlCenter::creerUtilisateur([
            'nom'       => 'BENALI',
            'prenom'    => 'Karim',
            'email'     => $email,
            'password'  => 'MotDePasse123',
            'telephone' => '0550 00 00 00',
            'role_user' => 'citoyen',
        ]);

        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        $this->assertNotNull($user);
        $this->assertSame('citoyen', $user['role_user']);
        $this->assertSame($email, $user['email']);
        $this->assertSame(1, (int) $user['is_active']);
        $this->assertNotSame('MotDePasse123', $user['password']);
        $this->assertTrue(password_verify('MotDePasse123', (string) $user['password']));

        $rbac = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$id, $this->roleId('citoyen')]
        );
        $this->assertSame(1, $rbac, 'Le lien RBAC citoyen doit exister.');

        $audit = (int) Database::value(
            "SELECT COUNT(*) FROM audit_logs WHERE action = 'user.create' AND modele_id = ?",
            [$id]
        );
        $this->assertSame(1, $audit, 'La création doit être auditée.');
    }

    public function testCreerPresidentAvecAssociationLiee(): void
    {
        $associationId = (int) Database::value('SELECT id FROM associations ORDER BY id LIMIT 1');
        $this->assertGreaterThan(0, $associationId);

        $id = ControlCenter::creerUtilisateur([
            'nom'            => 'HADJ',
            'prenom'         => 'Ahmed',
            'email'          => 'pres_' . bin2hex(random_bytes(4)) . '@test.local',
            'password'       => 'MotDePasse456',
            'telephone'      => '0661 22 33 44',
            'role_user'      => 'association',
            'association_id' => $associationId,
        ]);

        $user = Database::one('SELECT role_user, association_id FROM users WHERE id = ?', [$id]);
        $this->assertSame('association', $user['role_user']);
        $this->assertSame($associationId, (int) $user['association_id']);

        $rbac = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$id, $this->roleId('association')]
        );
        $this->assertSame(1, $rbac, 'Le lien RBAC association doit exister.');
    }

    public function testModifierUtilisateurChangeRoleEtSynchroniseRbac(): void
    {
        $id = ControlCenter::creerUtilisateur([
            'nom'       => 'MERZOUG',
            'prenom'    => 'Salima',
            'email'     => 'switch_' . bin2hex(random_bytes(4)) . '@test.local',
            'password'  => 'MotDePasse789',
            'telephone' => '0770 11 22 33',
            'role_user' => 'citoyen',
        ]);

        $associationId = (int) Database::value('SELECT id FROM associations ORDER BY id LIMIT 1');
        ControlCenter::modifierUtilisateur($id, [
            'nom'            => 'MERZOUGUI',
            'prenom'         => 'Salima',
            'email'          => 'switch_' . bin2hex(random_bytes(3)) . 'b@test.local',
            'telephone'      => '0770 11 22 33',
            'role_user'      => 'association',
            'association_id' => $associationId,
        ]);

        $user = Database::one('SELECT nom, role_user, association_id FROM users WHERE id = ?', [$id]);
        $this->assertSame('MERZOUGUI', $user['nom']);
        $this->assertSame('association', $user['role_user']);
        $this->assertSame($associationId, (int) $user['association_id']);

        $ancienLien = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$id, $this->roleId('citoyen')]
        );
        $this->assertSame(0, $ancienLien, 'L\'ancien lien citoyen doit être retiré.');

        $nouveauLien = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$id, $this->roleId('association')]
        );
        $this->assertSame(1, $nouveauLien, 'Le nouveau lien association doit exister.');

        $audit = (int) Database::value(
            "SELECT COUNT(*) FROM audit_logs WHERE action = 'user.update' AND modele_id = ?",
            [$id]
        );
        $this->assertSame(1, $audit);
    }

    public function testModifierMotDePasseSansRoleChanged(): void
    {
        $id = ControlCenter::creerUtilisateur([
            'nom'       => 'KACI',
            'prenom'    => 'Lina',
            'email'     => 'mdp_' . bin2hex(random_bytes(4)) . '@test.local',
            'password'  => 'AncienMotDePasse',
            'telephone' => '0555 66 77 88',
            'role_user' => 'citoyen',
        ]);

        ControlCenter::modifierUtilisateur($id, [
            'nom'       => 'KACI',
            'prenom'    => 'Lina',
            'email'     => 'mdp_' . bin2hex(random_bytes(3)) . 'b@test.local',
            'telephone' => '0555 66 77 88',
            'role_user' => 'citoyen',
        ], password_hash('NouveauMotDePasse1', PASSWORD_BCRYPT));

        $user = Database::one('SELECT password, role_user FROM users WHERE id = ?', [$id]);
        $this->assertTrue(password_verify('NouveauMotDePasse1', (string) $user['password']));
        $this->assertSame('citoyen', $user['role_user']);

        $audit = Database::value(
            "SELECT nouvelles_valeurs FROM audit_logs WHERE action = 'user.update' AND modele_id = ?",
            [$id]
        );
        $this->assertNotNull($audit);
        $this->assertStringContainsString('modifié', (string) $audit);
    }
}
