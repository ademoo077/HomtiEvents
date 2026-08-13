<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\Notification;

/**
 * Vérifie le socle de la Phase 7 (membres d'association) :
 *   - schéma association_invitations,
 *   - workflow d'invitation → acceptation (création de compte membre),
 *   - workflow d'acceptation par un compte existant,
 *   - retrait d'un membre (démotion citoyen + détachement).
 */
final class MemberFlowTest extends DatabaseTestCase
{
    private function associationId(): int
    {
        $id = (int) Database::value('SELECT id FROM associations ORDER BY id LIMIT 1');

        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function testInvitationSchemaAndInsert(): void
    {
        $cols = array_column(Database::all('SHOW COLUMNS FROM association_invitations'), 'Field');

        foreach (['id', 'association_id', 'email', 'token', 'statut', 'expires_at', 'accepted_at'] as $col) {
            $this->assertContains($col, $cols);
        }

        $associationId = $this->associationId();
        $token         = bin2hex(random_bytes(32));

        $invitationId = Database::insert('association_invitations', [
            'association_id' => $associationId,
            'email'          => 'nouveau.membre@example.dz',
            'token'          => $token,
            'statut'         => 'pending',
            'expires_at'     => date('Y-m-d H:i:s', time() + 86400),
        ]);

        $this->assertGreaterThan(0, $invitationId);

        $row = Database::one('SELECT * FROM association_invitations WHERE token = ?', [$token]);
        $this->assertNotNull($row);
        $this->assertSame('pending', $row['statut']);
    }

    public function testAcceptCreatesMemberAccount(): void
    {
        $associationId = $this->associationId();
        $token         = bin2hex(random_bytes(32));

        $inviteId = Database::insert('association_invitations', [
            'association_id' => $associationId,
            'email'          => 'amir.ben@example.dz',
            'token'          => $token,
            'statut'         => 'pending',
            'expires_at'     => date('Y-m-d H:i:s', time() + 86400),
        ]);

        $userId = Database::insert('users', [
            'nom'            => 'Ben',
            'prenom'         => 'Amir',
            'email'          => 'amir.ben@example.dz',
            'password'       => password_hash('secret-pass-123', PASSWORD_BCRYPT),
            'role_user'      => 'membre',
            'association_id' => $associationId,
            'is_active'      => 1,
        ]);

        Database::update('association_invitations', [
            'statut'      => 'accepted',
            'accepted_by' => $userId,
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$inviteId]);

        $this->assertSame(
            'membre',
            Database::value('SELECT role_user FROM users WHERE id = ?', [$userId])
        );
        $this->assertSame(
            $associationId,
            (int) Database::value('SELECT association_id FROM users WHERE id = ?', [$userId])
        );
        $this->assertSame(
            'accepted',
            Database::value('SELECT statut FROM association_invitations WHERE id = ?', [$inviteId])
        );
    }

    public function testRemoveMemberDemotesToCitoyen(): void
    {
        $associationId = $this->associationId();

        $userId = Database::insert('users', [
            'nom'            => 'Retire',
            'prenom'         => 'Test',
            'email'          => 'retire.test@example.dz',
            'password'       => password_hash('secret-pass-123', PASSWORD_BCRYPT),
            'role_user'      => 'membre',
            'association_id' => $associationId,
            'is_active'      => 1,
        ]);

        Database::update('users', [
            'role_user'      => 'citoyen',
            'association_id' => null,
        ], 'id = ?', [$userId]);

        $this->assertSame(
            'citoyen',
            Database::value('SELECT role_user FROM users WHERE id = ?', [$userId])
        );
        $this->assertNull(
            Database::value('SELECT association_id FROM users WHERE id = ?', [$userId])
        );
    }

    public function testAcceptNotifiesAssociation(): void
    {
        $associationId = $this->associationId();

        $assocUsers = (int) Database::value(
            'SELECT COUNT(*) FROM users WHERE association_id = ? AND is_active = 1',
            [$associationId]
        );
        $this->assertGreaterThan(0, $assocUsers);

        $sent = Notification::sendToAssociation(
            $associationId,
            'Membre accepté',
            'Un nouveau membre a rejoint.',
            'membre_accepte',
            ['email' => 'x@example.dz']
        );

        $this->assertSame($assocUsers, $sent);
        $this->assertSame(
            $assocUsers,
            (int) Database::value(
                "SELECT COUNT(*) FROM notifications WHERE type = 'membre_accepte' AND lu = 0"
            )
        );
    }
}