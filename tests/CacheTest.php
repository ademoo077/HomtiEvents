<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Cache;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function testRememberCachesResult(): void
    {
        $calls = 0;
        $fn = static function () use (&$calls): int {
            $calls++;

            return 42;
        };

        $this->assertSame(42, Cache::remember('clé.1', 60, $fn));
        $this->assertSame(42, Cache::remember('clé.1', 60, $fn));
        $this->assertSame(1, $calls, 'Le callback ne doit être exécuté qu\'une seule fois.');
    }

    public function testForgetInvalidates(): void
    {
        $calls = 0;
        $fn = static function () use (&$calls): string {
            $calls++;

            return 'valeur';
        };

        Cache::remember('clé.2', 60, $fn);
        Cache::forget('clé.2');

        $this->assertSame('valeur', Cache::remember('clé.2', 60, $fn));
        $this->assertSame(2, $calls);
    }

    public function testExpiredEntryIsRefreshed(): void
    {
        $key = 'clé.expiree';
        $file = BASE_PATH . '/storage/cache/' . md5($key) . '.json';
        @file_put_contents($file, json_encode(['expires_at' => time() - 10, 'value' => 'périmé'], JSON_UNESCAPED_UNICODE));

        $calls = 0;
        $fn = static function () use (&$calls): string {
            $calls++;

            return 'frais';
        };

        $this->assertSame('frais', Cache::remember($key, 60, $fn));
        $this->assertSame(1, $calls);
    }

    public function testArrayRoundTrip(): void
    {
        $payload = ['a' => 1, 'noms' => ['École', 'Mosquée'], 'actif' => true];

        Cache::remember('clé.tableau', 60, static fn () => $payload);

        $this->assertSame($payload, Cache::remember('clé.tableau', 60, static fn () => []));
    }

    public function testFlushRemovesAll(): void
    {
        Cache::remember('clé.3', 60, static fn () => 1);
        Cache::remember('clé.4', 60, static fn () => 2);

        Cache::flush();

        $this->assertCount(0, glob(BASE_PATH . '/storage/cache/*.json') ?: []);
    }
}
