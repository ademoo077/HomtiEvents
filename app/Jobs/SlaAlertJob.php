<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\SlaHelper;

/**
 * Traite les alertes SLA dues (J-2, J-1, retard).
 */
final class SlaAlertJob extends Job
{
    public function handle(array $payload): void
    {
        SlaHelper::runDue();
        SlaHelper::checkAlbumDelai();
    }
}
