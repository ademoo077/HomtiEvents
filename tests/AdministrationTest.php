<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;

final class AdministrationTest extends DatabaseTestCase
{
    public function testEpicCompetenceLinkAndGuard(): void
    {
        $epicId = (int) Database::insert('epic', ['nom' => 'TEST ADM EPIC', 'description' => 'temp', 'couleur' => '#123456']);
        $anomalieId = (int) Database::value('SELECT id FROM anomalies ORDER BY id LIMIT 1');

        Database::run('INSERT IGNORE INTO epic_anomalies (epic_id, anomalie_id) VALUES (?, ?)', [$epicId, $anomalieId]);

        $linked = (int) Database::value('SELECT COUNT(*) FROM epic_anomalies WHERE epic_id = ?', [$epicId]);
        $this->assertSame(1, $linked);

        $byAnomalie = (int) Database::value('SELECT COUNT(*) FROM epic_anomalies WHERE anomalie_id = ?', [$anomalieId]);
        $this->assertGreaterThanOrEqual(1, $byAnomalie);
    }

    public function testEpicDeleteGuardQueries(): void
    {
        $withInterventions = (int) Database::value(
            'SELECT COUNT(*) FROM evenement_epic WHERE epic_id = 1'
        );
        $this->assertGreaterThan(0, $withInterventions, 'L\'EPIC 1 a des interventions : la suppression doit être bloquée.');

        $epicId = (int) Database::insert('epic', ['nom' => 'SANS INTERVENTION', 'couleur' => '#ffffff']);
        $interventions = (int) Database::value(
            'SELECT COUNT(*) FROM evenement_epic WHERE epic_id = ?',
            [$epicId]
        );
        $this->assertSame(0, $interventions, 'Aucune intervention : la suppression est autorisée.');

        Database::run('DELETE FROM epic WHERE id = ?', [$epicId]);
        $this->assertNull(Database::value('SELECT id FROM epic WHERE id = ?', [$epicId]));
    }

    public function testAnomalieDeleteGuardQueries(): void
    {
        $signalements = (int) Database::value(
            'SELECT COUNT(*) FROM anomalies_evenement WHERE anomalie_id = 1'
        );
        $this->assertGreaterThan(0, $signalements, 'L\'anomalie 1 est référencée : suppression bloquée.');

        $anomalieId = (int) Database::insert('anomalies', ['nom' => 'SANS SIGNALEMENT', 'icone' => 'test']);
        $refs = (int) Database::value(
            'SELECT COUNT(*) FROM anomalies_evenement WHERE anomalie_id = ?',
            [$anomalieId]
        );
        $this->assertSame(0, $refs, 'Non référencée : suppression autorisée.');

        Database::run('DELETE FROM epic_anomalies WHERE anomalie_id = ?', [$anomalieId]);
        Database::run('DELETE FROM anomalies WHERE id = ?', [$anomalieId]);
        $this->assertNull(Database::value('SELECT id FROM anomalies WHERE id = ?', [$anomalieId]));
    }

    public function testCitoyenToggleLogic(): void
    {
        $citoyen = Database::one('SELECT id, is_active FROM users WHERE role_user = ? ORDER BY id LIMIT 1', ['citoyen']);
        $this->assertNotNull($citoyen);

        $next = (int) $citoyen['is_active'] === 1 ? 0 : 1;
        Database::update('users', ['is_active' => $next], 'id = ?', [(int) $citoyen['id']]);

        $this->assertSame($next, (int) Database::value('SELECT is_active FROM users WHERE id = ?', [(int) $citoyen['id']]));
    }

    public function testEpicCompetenceMatching(): void
    {
        $eventId = (int) Database::value(
            'SELECT ae.evenement_id FROM anomalies_evenement ae
             JOIN epic_anomalies ea ON ea.anomalie_id = ae.anomalie_id
             LIMIT 1'
        );
        $this->assertGreaterThan(0, $eventId);

        $competentes = Database::all(
            'SELECT DISTINCT e.* FROM epic e
             WHERE e.id IN (
                SELECT ea.epic_id FROM epic_anomalies ea
                JOIN anomalies_evenement ae ON ae.anomalie_id = ea.anomalie_id
                WHERE ae.evenement_id = ?
             ) ORDER BY e.nom',
            [$eventId]
        );

        $this->assertNotEmpty($competentes);
        $this->assertArrayHasKey('nom', $competentes[0]);
    }
}
