<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Cache;
use App\Helpers\StatsService;

final class StatsTest extends DatabaseTestCase
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

    public function testDataContientToutesLesSections(): void
    {
        $data = StatsService::data();

        foreach (['kpis', 'parStatut', 'evolutionMensuelle', 'topAssociations', 'repartitionCommunes', 'topEpics', 'participationsJour', 'demandesParStatut', 'tauxParticipation'] as $cle) {
            $this->assertArrayHasKey($cle, $data, "Clé manquante : {$cle}");
        }

        $this->assertCount(6, $data['evolutionMensuelle'], '6 mois attendus.');
        $this->assertIsFloat($data['tauxParticipation']);

        foreach (['evenements', 'participants', 'citoyens', 'associations', 'epics', 'demandes', 'photos', 'albums', 'anomalies'] as $cle) {
            $this->assertArrayHasKey($cle, $data['kpis'], "KPI manquant : {$cle}");
        }
    }

    public function testKpisMisEnCacheFichier(): void
    {
        $fichier = BASE_PATH . '/storage/cache/' . md5('stats:kpis') . '.json';
        $this->assertFileDoesNotExist($fichier, 'Le cache doit être vide avant calcul.');

        StatsService::kpis();

        $this->assertFileExists($fichier, 'Le fichier de cache doit être créé.');
    }

    public function testFlushInvalidateLeCache(): void
    {
        StatsService::kpis();
        StatsService::data();

        $this->assertGreaterThan(0, count(glob(BASE_PATH . '/storage/cache/*.json') ?: []));

        StatsService::flush();

        $this->assertCount(0, glob(BASE_PATH . '/storage/cache/*.json') ?: [], 'flush() doit vider le cache.');
    }

    public function testCsvContientLesSections(): void
    {
        $csv = StatsService::csv();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'Le CSV doit commencer par le BOM UTF-8.');
        $this->assertStringContainsString('Wilaya Harmonia', $csv);
        $this->assertStringContainsString('Indicateur;Valeur', $csv);
        $this->assertStringContainsString('Répartition par statut', $csv);
        $this->assertStringContainsString('Évolution mensuelle', $csv);
        $this->assertStringContainsString('Top associations', $csv);
        $this->assertStringContainsString('Demandes d\'inscription par statut', $csv);
    }
}
