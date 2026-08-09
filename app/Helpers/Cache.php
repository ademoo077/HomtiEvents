<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Cache fichier léger avec expiration (TTL).
 */
final class Cache
{
    private static string $dir = '';

    private static function dir(): string
    {
        if (self::$dir === '') {
            $dir = BASE_PATH . '/storage/cache';
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            self::$dir = $dir;
        }

        return self::$dir;
    }

    public static function remember(string $key, int $ttlSeconds, callable $fn): mixed
    {
        $file = self::dir() . '/' . md5($key) . '.json';

        if (is_file($file)) {
            $payload = json_decode((string) file_get_contents($file), true);
            if (is_array($payload) && ($payload['expires_at'] ?? 0) > time()) {
                return $payload['value'];
            }
        }

        $value = $fn();
        $data = ['expires_at' => time() + $ttlSeconds, 'value' => $value];
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::dir() . '/' . md5($key) . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }
}
