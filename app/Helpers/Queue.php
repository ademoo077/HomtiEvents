<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * File queue — jobs sérialisés sur disque, exécutés par queue/worker.php.
 *
 * @category WilayaHarmonia
 * @package  App\Helpers
 * @author   Wilaya Harmonia
 * @license  Proprietary
 * @link     https://wilaya-harmonia.dz
 */
final class Queue
{
    /**
     * Pousse un job en file.
     *
     * @param array<string, mixed> $payload
     */
    public static function push(
        string $jobClass,
        array $payload = [],
        int $delay = 0
    ): string {
        $id = bin2hex(random_bytes(16));

        $dir = storage_path('queue');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $job = [
            'id'      => $id,
            'job'     => $jobClass,
            'payload' => $payload,
            'delay'   => $delay,
            'attempts' => 0,
            'created' => time(),
        ];

        // Format nom de fichier : timestamp + priorité (delay 0 = immédiat)
        $file = sprintf(
            '%s/%d_%d_%s.job',
            $dir,
            time(),
            $delay > 0 ? 1 : 0,
            $id
        );
        file_put_contents($file, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $id;
    }

    /**
     * Pousse un job différé.
     *
     * @param array<string, mixed> $payload
     */
    public static function later(int $seconds, string $jobClass, array $payload = []): string
    {
        return self::push($jobClass, $payload, $seconds);
    }

    /**
     * Récupère les jobs prêts à être exécutés.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pull(int $limit = 10): array
    {
        $dir = storage_path('queue');
        if (! is_dir($dir)) {
            return [];
        }

        $jobs = [];
        $files = glob($dir . '/*.job');

        if ($files === false) {
            return [];
        }

        usort($files, 'strnatcmp'); // les plus anciens d'abord

        foreach ($files as $file) {
            if (count($jobs) >= $limit) {
                break;
            }

            $content = file_get_contents($file);
            $job = json_decode((string) $content, true);

            if (! is_array($job)) {
                unlink($file);
                continue;
            }

            // Job différé ?
            $due = (int) $job['created'] + (int) $job['delay'] <= time();
            if (($job['delay'] ?? 0) > 0 && ! $due) {
                continue;
            }

            $jobs[] = ['file' => $file, 'job' => $job];
        }

        return $jobs;
    }

    /**
     * Supprime un job traité.
     */
    public static function delete(string $file): void
    {
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Renvoie un job en file avec backoff exponentiel.
     *
     * @param array<string, mixed> $job
     */
    public static function retry(string $file, array $job): void
    {
        $job['attempts'] = ((int) ($job['attempts'] ?? 0)) + 1;
        $job['created']  = time();
        $job['delay']    = min(300, ((int) ($job['delay'] ?? 0) * 2 ?: 10)); // backoff exponentiel
        $json = json_encode($job, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $json === false ? '{}' : $json);
    }

    /**
     * Nombre de jobs en attente.
     */
    public static function size(): int
    {
        return count(glob(storage_path('queue') . '/*.job') ?: []);
    }
}
