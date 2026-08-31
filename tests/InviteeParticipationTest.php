<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;

/**
 * Lot 6b — Participation par scan QR sans compte (invités).
 *
 * Vérifie :
 *   - schéma : table participations_invitees,
 *   - enregistrement d'un invité (nom/prénom/téléphone),
 *   - détection d'un doublon (même téléphone),
 *   - récupération des invités d'un événement.
 */
final class InviteeParticipationTest extends DatabaseTestCase
{
    private function makeEvent(): int
    {
        return Database::insert('evenements', [
            'adresse'       => 'Événement invité test',
            'statut'        => 'PROGRAMME',
            'date_evenement' => date('Y-m-d', strtotime('+10 days')),
        ]);
    }

    public function testSchema(): void
    {
        $cols = array_column(Database::all('SHOW COLUMNS FROM participations_invitees'), 'Field');

        $this->assertContains('evenement_id', $cols);
        $this->assertContains('nom', $cols);
        $this->assertContains('prenom', $cols);
        $this->assertContains('telephone', $cols);
        $this->assertContains('created_at', $cols);
    }

    public function testEnregistrementInvite(): void
    {
        $eventId = $this->makeEvent();

        $ok = QrCodeGenerator::registerInvitee($eventId, [
            'nom'       => 'Ben',
            'prenom'    => 'Karim',
            'telephone' => '0550 12 34 56',
        ], 'token' . bin2hex(random_bytes(4)));

        $this->assertTrue($ok, 'L\'invité doit être enregistré.');

        $row = Database::one(
            'SELECT nom, prenom, telephone FROM participations_invitees WHERE evenement_id = ?',
            [$eventId]
        );
        $this->assertNotNull($row);
        $this->assertSame('Karim', $row['prenom'] ?? null);
        $this->assertSame('Ben', $row['nom'] ?? null);
        $this->assertSame('0550 12 34 56', $row['telephone'] ?? null);
    }

    public function testDoublonMemeTelephoneDetecte(): void
    {
        $eventId = $this->makeEvent();

        QrCodeGenerator::registerInvitee($eventId, [
            'nom'       => 'Ben',
            'prenom'    => 'Karim',
            'telephone' => '0550123456',
        ], 'token-a');

        $this->assertTrue(
            QrCodeGenerator::inviteeDejaInscrit($eventId, '0550123456'),
            'Un téléphone déjà inscrit doit être détecté.'
        );
        $this->assertFalse(
            QrCodeGenerator::inviteeDejaInscrit($eventId, '0666000000'),
            'Un téléphone inconnu ne doit pas être considéré inscrit.'
        );
    }

    public function testListeInvitesDunEvenement(): void
    {
        $eventId = $this->makeEvent();

        QrCodeGenerator::registerInvitee($eventId, ['nom' => 'A', 'prenom' => 'X', 'telephone' => '111'], 't1');
        QrCodeGenerator::registerInvitee($eventId, ['nom' => 'B', 'prenom' => 'Y', 'telephone' => '222'], 't2');

        $invites = QrCodeGenerator::inviteesPourEvenement($eventId);

        $this->assertCount(2, $invites);
        $this->assertSame('X', $invites[0]['prenom'] ?? null);
    }
}
