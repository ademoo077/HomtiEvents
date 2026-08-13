<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\EvenementService;

/**
 * Phase 1 (bugs de données) — listing citoyen :
 *   - un événement EN_COURS reste listé comme « à venir » (STATUTS_A_VENIR),
 *   - les événements archivés (deleted_at) ne remontent jamais.
 */
final class CitoyenEvenementsTest extends DatabaseTestCase
{
    /** @param array<string, mixed> $cols */
    private function insertEvenement(array $cols): int
    {
        return Database::insert('evenements', array_merge([
            'adresse'        => 'Adresse test ' . bin2hex(random_bytes(4)),
            'statut'         => 'PROGRAMME',
            'date_evenement' => date('Y-m-d', strtotime('+5 days')),
            'association_id' => null,
            'commune_id'     => null,
        ], $cols));
    }

    public function testEvenementsAVenirInclutEnCours(): void
    {
        $this->insertEvenement(['statut' => 'EN_COURS', 'date_evenement' => date('Y-m-d')]);

        $rows    = EvenementService::evenementsAVenirPourCitoyen();
        $statuts = array_column($rows, 'statut');

        $this->assertContains('EN_COURS', $statuts, 'Un événement EN_COURS doit rester listé comme « à venir ».');
    }

    public function testEvenementsAVenirExclutArchives(): void
    {
        $this->insertEvenement(['statut' => 'PROGRAMME', 'deleted_at' => date('Y-m-d H:i:s')]);

        $rows = EvenementService::evenementsAVenirPourCitoyen();

        $this->assertNotEmpty($rows, 'Des événements non archivés doivent exister (seed).');
        foreach ($rows as $row) {
            $this->assertNull($row['deleted_at'], 'Aucun événement archivé ne doit apparaître.');
        }
    }

    public function testEvenementsPassesExclutArchives(): void
    {
        $this->insertEvenement([
            'statut'         => 'TERMINE',
            'date_evenement' => date('Y-m-d', strtotime('-3 days')),
            'deleted_at'     => date('Y-m-d H:i:s'),
        ]);

        $rows = EvenementService::evenementsPassesPourCitoyen();

        foreach ($rows as $row) {
            $this->assertNull($row['deleted_at'], 'Aucun événement archivé passé ne doit apparaître.');
        }
    }
}
