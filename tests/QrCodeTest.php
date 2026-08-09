<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;

final class QrCodeTest extends DatabaseTestCase
{
    public function testUuidFormat(): void
    {
        $uuid = QrCodeGenerator::uuid();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
            'UUID v4 attendu'
        );
    }

    public function testCreateForEvenement(): void
    {
        $event = $this->eventByStatus('PROGRAMME');

        $qr = QrCodeGenerator::createForEvenement((int) $event['id'], $event['date_evenement'], $event['heure']);

        $this->assertNotEmpty($qr['token']);
        $this->assertNotEmpty($qr['url']);
        $this->assertStringContainsString('/checkin/', $qr['url']);
        $this->assertNotNull($qr['expiration']);

        $found = QrCodeGenerator::findByToken($qr['token']);
        $this->assertNotNull($found);
        $this->assertSame((int) $event['id'], (int) $found['evenement_id']);

        $this->assertTrue(QrCodeGenerator::isValid($found));
    }

    public function testInvalidTokenRejected(): void
    {
        $this->assertNull(QrCodeGenerator::findByToken('token-inexistant'));
        $this->assertFalse(QrCodeGenerator::isValid(null));
    }

    public function testParticipationFlow(): void
    {
        $event = $this->eventByStatus('PROGRAMME');
        $citoyen = $this->userByEmail('sami@citoyen.dz');

        $this->assertFalse(QrCodeGenerator::hasParticipated((int) $event['id'], (int) $citoyen['id']));

        $ok = QrCodeGenerator::registerParticipation((int) $event['id'], (int) $citoyen['id']);
        $this->assertTrue($ok);

        $this->assertTrue(QrCodeGenerator::hasParticipated((int) $event['id'], (int) $citoyen['id']));

        $points = (int) Database::value('SELECT points FROM users WHERE id = ?', [(int) $citoyen['id']]);
        $this->assertSame((int) $citoyen['points'] + 50, $points);

        $ok2 = QrCodeGenerator::registerParticipation((int) $event['id'], (int) $citoyen['id']);
        $this->assertFalse($ok2, 'Double check-in refusé');
    }

    public function testPngDataUri(): void
    {
        $uri = QrCodeGenerator::pngDataUri('https://wilaya-harmonia.dz/checkin/demo', 200);

        $this->assertStringStartsWith('data:image/png;base64,', $uri);

        $png = base64_decode(substr($uri, 22));
        $this->assertSame("\x89PNG", substr($png, 0, 4));
    }
}
