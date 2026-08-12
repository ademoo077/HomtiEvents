<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\QrCodeService;

final class QrCodeServiceTest extends DatabaseTestCase
{
    private function eventProgramme(): int
    {
        $anomalieId = (int) Database::value(
            'SELECT anomalie_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );
        $epicId = (int) Database::value('SELECT id FROM epic ORDER BY id LIMIT 1');

        $eventId = EvenementService::create(
            ['commune_id' => 1, 'adresse' => 'Rue QR test', 'description' => 'QR événement test'],
            null,
            [$anomalieId],
            'EN_ATTENTE'
        );

        // Passe l'événement en PROGRAMME → déclenche QrCodeService::generate().
        EvenementService::programmer($eventId, '2026-08-25', '10:00:00', [$epicId], 0);

        return $eventId;
    }

    public function testGeneratePersistsTokenFileAndPath(): void
    {
        $eventId = $this->eventProgramme();

        $this->assertTrue(QrCodeService::has($eventId), 'Un token QR doit exister.');

        $path = QrCodeService::path($eventId);
        $this->assertNotNull($path, 'qr_code_path doit être renseigné.');
        $this->assertNotEmpty($path);
        $this->assertTrue(is_file($path), 'Le fichier PNG doit exister sur disque.');

        $url = QrCodeService::getQrCodeUrl($eventId);
        $this->assertNotNull($url);
        $this->assertStringContainsString('checkin/', $url);
        $this->assertStringContainsString('/', $url);
    }

    public function testStreamOrDownloadReturnsPng(): void
    {
        $eventId = $this->eventProgramme();
        $png = file_get_contents(QrCodeService::filepath($eventId));

        $this->assertNotFalse($png);
        $this->assertGreaterThan(100, strlen($png), 'Le PNG doit être un vrai fichier image.');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8), 'Signature PNG valide.');
    }

    public function testDeleteQrCodeRemovesTokenFileAndPath(): void
    {
        $eventId = $this->eventProgramme();
        $this->assertTrue(QrCodeService::has($eventId));

        QrCodeService::deleteQrCode($eventId);

        $this->assertFalse(QrCodeService::has($eventId));
        $this->assertNull(QrCodeService::path($eventId));
    }
}
