<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\QrCodeGenerator;

/**
 * Génère le QR code d'un événement après programmation.
 */
final class GenerateQrJob extends Job
{
    public function handle(array $payload): void
    {
        $evenementId = (int) ($payload['evenement_id'] ?? 0);
        $date        = $payload['date_evenement'] ?? null;
        $heure       = $payload['heure'] ?? null;

        if ($evenementId === 0) {
            return;
        }

        QrCodeGenerator::createForEvenement($evenementId, (string) $date, (string) $heure);
    }
}
