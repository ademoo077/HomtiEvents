<?php

declare(strict_types=1);

/**
 * Worker de file — traitement continu des jobs + alertes SLA.
 *
 * Usage :
 *   php queue/worker.php            # une passe (semblable à queue:work)
 *   php queue/worker.php --watch    # boucle infinie (daemon)
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

Bootstrap::boot();

use App\Helpers\Queue;
use App\Helpers\SlaHelper;

function processJob(array $item): bool
{
    [$file, $job] = [$item['file'], $item['job']];

    try {
        $instance = new $job['job']();
        $instance->handle($job['payload'] ?? []);
        Queue::delete($file);

        return true;
    } catch (Throwable $e) {
        $attempts = (int) ($job['attempts'] ?? 0) + 1;

        if ($attempts >= 3) {
            Queue::delete($file);
            $instance = new $job['job']();
            $instance->failed($job['payload'] ?? [], $e);
        } else {
            $job['attempts'] = $attempts;
            $job['delay']    = min(300, 10 * 2 ** ($attempts - 1)); // backoff : 10s, 20s, 40s…
            Queue::delete($file);
            file_put_contents(
                $file,
                json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        return false;
    }
}

$watch = in_array('--watch', $argv, true);
$tick  = 0;

while (true) {
    $jobs = Queue::pull(10);
    foreach ($jobs as $item) {
        processJob($item);
    }

    if ($tick % 30 === 0) {
        SlaHelper::runDue();
        SlaHelper::checkAlbumDelai();
    }

    if (! $watch) {
        break;
    }

    $tick++;
    usleep(1000000); // 1s
}

exit(0);
