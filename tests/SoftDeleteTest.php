<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;

/**
 * Phase 6 (M6) — suppression logique des comptes (soft delete) :
 *   - schéma users.deleted_at (migration 030),
 *   - le compte archivé reste en base mais ne peut plus se connecter
 *     (garde is_active = 0 + deleted_at IS NULL dans AuthController::login()),
 *   - l'historique lié (participations) est conservé.
 */
final class SoftDeleteTest extends DatabaseTestCase
{
    public function testSchemaUsersExposeDeletedAt(): void
    {
        $columns = array_column(Database::all('SHOW COLUMNS FROM users'), 'Field');

        $this->assertContains('deleted_at', $columns, 'La colonne deleted_at doit exister (migration 030).');
    }

    public function testCompteArchiveConserveSaLigneEtDesactive(): void
    {
        $citoyen = Database::one("SELECT id, is_active FROM users WHERE role_user = 'citoyen' ORDER BY id LIMIT 1");
        $this->assertNotNull($citoyen);

        $id = (int) $citoyen['id'];

        Database::update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'is_active'  => 0,
        ], 'id = ?', [$id]);

        $archive = Database::one('SELECT id, deleted_at, is_active FROM users WHERE id = ?', [$id]);

        $this->assertNotNull($archive, 'Le compte doit rester en base (soft delete).');
        $this->assertNotNull($archive['deleted_at'], 'deleted_at doit être renseigné.');
        $this->assertSame(0, (int) $archive['is_active'], 'Le compte archivé doit être désactivé.');
    }

    public function testLoginRefuseUnCompteArchive(): void
    {
        $citoyen = Database::one("SELECT id, email FROM users WHERE role_user = 'citoyen' ORDER BY id LIMIT 1");
        $this->assertNotNull($citoyen);

        Database::update('users', ['deleted_at' => date('Y-m-d H:i:s'), 'is_active' => 0], 'id = ?', [(int) $citoyen['id']]);

        $user = Database::one(
            'SELECT u.*, a.valide AS association_valide FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             WHERE u.email = ? AND u.is_active = 1 AND u.deleted_at IS NULL',
            [(string) $citoyen['email']]
        );

        $this->assertNull($user, 'Un compte archivé ne doit pas pouvoir se connecter.');
    }

    public function testHistoriqueParticipationsConserveApresArchivage(): void
    {
        $participant = Database::one('SELECT user_id, evenement_id FROM evenement_participant LIMIT 1');
        if ($participant === null) {
            $this->markTestSkipped('Aucune participation seedée.');
        }

        Database::update('users', ['deleted_at' => date('Y-m-d H:i:s'), 'is_active' => 0], 'id = ?', [(int) $participant['user_id']]);

        $still = Database::one(
            'SELECT evenement_id FROM evenement_participant WHERE user_id = ? AND evenement_id = ?',
            [(int) $participant['user_id'], (int) $participant['evenement_id']]
        );

        $this->assertNotNull($still, 'L\'historique des participations doit être conservé (soft delete).');
    }
}
