<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\EpicDashboardService;

final class EpicDashboardServiceTest extends DatabaseTestCase
{
    public function testKpisAgregeParStatut(): void
    {
        // EPIC 1 (ADE) : un événement MODIFICATION_DEMANDEE (seed 020) affecté.
        $kpis = EpicDashboardService::kpis(1);

        $this->assertArrayHasKey('total', $kpis);
        $this->assertArrayHasKey('VALIDÉ', $kpis);
        $this->assertArrayHasKey('PROGRAMME', $kpis);
        $this->assertArrayHasKey('EN_COURS', $kpis);
        $this->assertArrayHasKey('TERMINE', $kpis);
        $this->assertArrayHasKey('REFUSE', $kpis);

        $this->assertSame(1, $kpis['MODIFICATION_DEMANDEE'], 'L\'anomalie seedée de l\'EPIC 1 doit être comptée.');
        $this->assertSame(
            $kpis['VALIDÉ'] + $kpis['PROGRAMME'] + $kpis['EN_COURS'] + $kpis['TERMINE']
            + $kpis['REFUSE'] + $kpis['EN_ATTENTE'] + $kpis['MODIFICATION_DEMANDEE'],
            $kpis['total']
        );
    }

    public function testKpisRespectentLeFiltreCommune(): void
    {
        $tous = EpicDashboardService::kpis(1);
        $centre = EpicDashboardService::kpis(1, ['commune_id' => 1]); // Alger-Centre

        $this->assertLessThanOrEqual($tous['total'], $centre['total']);
    }

    public function testEvenementsParJourNestIndexeQueParJourAvecEvenements(): void
    {
        $aujourdhui = date('Y-m-d');

        Database::insert('evenements', [
            'adresse'        => 'Événement de test calendrier EPIC',
            'description'    => 'Événement de test pour la grille calendrier.',
            'statut'         => 'PROGRAMME',
            'assigned_org_id' => 1,
            'date_evenement' => $aujourdhui,
            'heure'          => '09:00:00',
        ]);

        $mois = date('Y-m');
        $parJour = EpicDashboardService::evenementsParJour(1, $mois);

        $this->assertNotEmpty($parJour, 'Le calendrier doit contenir l\'événement de test du jour.');
        $this->assertArrayHasKey($aujourdhui, $parJour);

        foreach ($parJour as $jour => $events) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $jour);
            $this->assertNotEmpty($events);
            foreach ($events as $event) {
                $this->assertSame($jour, (string) $event['date_evenement']);
                $this->assertContains($event['statut'], EpicDashboardService::CALENDRIER_STATUTS);
            }
        }
    }

    public function testAVenirLimiteA5Dans3Jours(): void
    {
        $avenir = EpicDashboardService::aVenir(1);

        $this->assertLessThanOrEqual(5, count($avenir));

        $demain = date('Y-m-d');
        $dans3 = date('Y-m-d', strtotime('+3 days'));
        foreach ($avenir as $event) {
            $this->assertGreaterThanOrEqual($demain, (string) $event['date_evenement']);
            $this->assertLessThanOrEqual($dans3, (string) $event['date_evenement']);
        }
    }

    public function testAnomaliesParMotifGroupeEtNormalise(): void
    {
        $anomalies = EpicDashboardService::anomaliesParMotif(1);

        $this->assertNotEmpty($anomalies);
        foreach ($anomalies as $a) {
            $this->assertArrayHasKey('motif', $a);
            $this->assertArrayHasKey('nb', $a);
            $this->assertGreaterThan(0, $a['nb']);
        }
    }

    public function testNormaliseMotifClassifieLesCategories(): void
    {
        $this->assertSame('Date invalide', EpicDashboardService::normaliseMotif('Date invalide : conflit'));
        $this->assertSame('Lieu inexact', EpicDashboardService::normaliseMotif('Lieu inexact : adresse incomplète'));
        $this->assertSame('Pièce manquante', EpicDashboardService::normaliseMotif('Pièce manquante : copie de l\'agrément'));
        $this->assertSame('Dossier incomplet', EpicDashboardService::normaliseMotif('Dossier incomplet : statut juridique'));
        $this->assertSame('Autre motif', EpicDashboardService::normaliseMotif('Raisons diverses'));
    }

    public function testAnomaliesNonTraiteesCompteLesAnomaliesDeLaSemaine(): void
    {
        $nb = EpicDashboardService::anomaliesNonTraitees(1);

        $attendues = (int) Database::value(
            "SELECT COUNT(*) FROM evenements e
             JOIN evenement_epic ee ON ee.evenement_id = e.id
             WHERE ee.epic_id = 1 AND e.deleted_at IS NULL
               AND e.statut IN ('MODIFICATION_DEMANDEE', 'REFUSE')
               AND e.updated_at >= NOW() - INTERVAL 7 DAY"
        );

        $this->assertSame($attendues, $nb);
    }

    public function testPerimetreRestreintAuxEvenementsDeLEpic(): void
    {
        $epic1 = EpicDashboardService::kpis(1);
        $epic3 = EpicDashboardService::kpis(3);

        // L'anomalie REFUSE seedée est affectée à l'EPIC 3 (ASROUT).
        $this->assertSame(1, $epic3['REFUSE']);
        $this->assertSame(0, $epic1['REFUSE'], 'L\'EPIC 1 ne voit pas les événements des autres EPIC.');
    }
}
