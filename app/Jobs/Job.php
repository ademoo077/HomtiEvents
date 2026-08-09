<?php

declare(strict_types=1);

namespace App\Jobs;

/**
 * Job de base — contrat des tâches de la file.
 */
abstract class Job
{
    abstract public function handle(array $payload): void;

    public function failed(array $payload, \Throwable $e): void
    {
        error_log(sprintf(
            "[JOB %s] Échec : %s — %s",
            static::class,
            $e->getMessage(),
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        ));
    }
}
