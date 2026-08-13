<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\Gamification;
use App\Helpers\StatsService;

/**
 * Phase 5 — compléments dashboard (M5) :
 *   - StatsService::tempsMoyenEpic() : durée moyenne affectation→clôture,
 *   - Gamification::rank() : classement citoyen (badge gamification),
 *   - clés i18n citoyen.stats_points présentes (FR/AR).
 */
final class DashboardsDeltaTest extends DatabaseTestCase
{
    public function testTempsMoyenEpicCalculeLaDuree(): void
    {
        Database::run("UPDATE evenements SET statut = 'ANNULE' WHERE statut = 'TERMINE'");

        $intervention = Database::one(
            'SELECT evenement_id, date_affectation FROM evenement_epic
             WHERE date_affectation IS NOT NULL ORDER BY id LIMIT 1'
        );
        $this->assertNotNull($intervention, 'Une intervention EPIC seedée est requise.');

        $evenementId = (int) $intervention['evenement_id'];

        Database::run(
            "UPDATE evenements e
             SET statut = 'TERMINE',
                 updated_at = DATE_ADD((SELECT date_affectation FROM evenement_epic WHERE evenement_id = ?), INTERVAL 15 DAY)
             WHERE e.id = ?",
            [$evenementId, $evenementId]
        );

        $moyenne = StatsService::tempsMoyenEpic();

        $this->assertIsFloat($moyenne, 'Une moyenne doit être calculée.');
        $this->assertEqualsWithDelta(15.0, $moyenne, 0.01, '15 jours affectation→clôture attendus.');
    }

    public function testTempsMoyenEpicRetourneNullSansEvenementsTermines(): void
    {
        Database::run("UPDATE evenements SET statut = 'ANNULE' WHERE statut = 'TERMINE'");

        $this->assertNull(StatsService::tempsMoyenEpic());
    }

    public function testRankRetourneUnEntierSuperieurOuEgalAUn(): void
    {
        $citoyen = Database::one("SELECT id FROM users WHERE role_user = 'citoyen' ORDER BY points DESC LIMIT 1");
        $this->assertNotNull($citoyen, 'Un citoyen seedé est requis.');

        $rang = Gamification::rank((int) $citoyen['id']);

        $this->assertIsInt($rang);
        $this->assertGreaterThanOrEqual(1, $rang);
    }

    public function testCleCitoyenStatsPointsPresenteDansLesLangues(): void
    {
        foreach (['fr', 'ar'] as $locale) {
            $json = (string) file_get_contents(dirname(__DIR__) . "/lang/{$locale}.json");
            $data = json_decode($json, true);
            $this->assertIsArray($data, "lang/{$locale}.json doit être un JSON valide.");
            $this->assertArrayHasKey('citoyen.stats_points', $data, "Clé citoyen.stats_points manquante ({$locale}).");
        }
    }
}
