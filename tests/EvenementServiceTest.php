<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\QrCodeGenerator;

final class EvenementServiceTest extends DatabaseTestCase
{
    public function testCreatePersistsEvent(): void
    {
        $id = EvenementService::create(
            ['commune_id' => 1, 'adresse' => 'Rue des Frères Boudiaf', 'description' => 'Nettoyage du quartier'],
            null,
            [1, 2],
            'EN_ATTENTE'
        );

        $event = Database::one('SELECT * FROM evenements WHERE id = ?', [$id]);
        $this->assertNotNull($event);
        $this->assertSame('Rue des Frères Boudiaf', $event['adresse']);
        $this->assertSame('EN_ATTENTE', $event['statut']);

        $linked = (int) Database::value('SELECT COUNT(*) FROM anomalies_evenement WHERE evenement_id = ?', [$id]);
        $this->assertSame(2, $linked);
    }

    public function testSoftDeleteAndRestore(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');

        EvenementService::softDelete((int) $event['id']);
        $this->assertNotNull(
            Database::value('SELECT deleted_at FROM evenements WHERE id = ?', [(int) $event['id']])
        );

        EvenementService::restore((int) $event['id']);
        $this->assertNull(
            Database::value('SELECT deleted_at FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testBulkTerminer(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $epic = (int) Database::value('SELECT epic_id FROM epic_anomalies ORDER BY epic_id, anomalie_id LIMIT 1');

        EvenementService::programmer((int) $event['id'], '2026-08-20', '09:30:00', [$epic], (int) ($event['association_id'] ?? 0));

        $count = EvenementService::bulk('terminer', [(int) $event['id']]);

        $this->assertSame(1, $count);
        $this->assertSame(
            'TERMINE',
            Database::value('SELECT statut FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testBulkArchiverRestaurer(): void
    {
        $event = $this->eventByStatus('PROGRAMME');

        EvenementService::bulk('archiver', [(int) $event['id']]);
        $this->assertNotNull(
            Database::value('SELECT deleted_at FROM evenements WHERE id = ?', [(int) $event['id']])
        );

        EvenementService::bulk('restaurer', [(int) $event['id']]);
        $this->assertNull(
            Database::value('SELECT deleted_at FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testBulkDeduplicatesAndSkipsUnknown(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $epic = (int) Database::value('SELECT epic_id FROM epic_anomalies ORDER BY epic_id, anomalie_id LIMIT 1');

        EvenementService::programmer((int) $event['id'], '2026-08-20', '09:30:00', [$epic], (int) ($event['association_id'] ?? 0));

        $count = EvenementService::bulk('terminer', [(int) $event['id'], (int) $event['id'], 999999]);

        $this->assertSame(1, $count);
        $this->assertSame(
            'TERMINE',
            Database::value('SELECT statut FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testBulkUnknownActionDoesNotChange(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $before = $event['statut'];

        EvenementService::bulk('foo', [(int) $event['id']]);

        $this->assertSame(
            $before,
            Database::value('SELECT statut FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testProgrammerCreatesDependencies(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $epic = (int) Database::value('SELECT epic_id FROM epic_anomalies ORDER BY epic_id, anomalie_id LIMIT 1');

        EvenementService::programmer((int) $event['id'], '2026-08-20', '09:30:00', [$epic], (int) ($event['association_id'] ?? 0));

        $updated = Database::one('SELECT * FROM evenements WHERE id = ?', [(int) $event['id']]);
        $this->assertSame('PROGRAMME', $updated['statut']);
        $this->assertNotNull($updated['deadline_at']);

        $qr = (int) Database::value('SELECT COUNT(*) FROM qr_event WHERE evenement_id = ?', [(int) $event['id']]);
        $this->assertGreaterThanOrEqual(1, $qr);

        $linked = (int) Database::value('SELECT COUNT(*) FROM evenement_epic WHERE evenement_id = ?', [(int) $event['id']]);
        $this->assertGreaterThanOrEqual(1, $linked);

        $qrRow = Database::one('SELECT * FROM qr_event WHERE evenement_id = ? ORDER BY id DESC LIMIT 1', [(int) $event['id']]);
        $this->assertNotNull($qrRow);
        $this->assertTrue(QrCodeGenerator::isValid(QrCodeGenerator::findByToken((string) $qrRow['token_qr'])));
    }

    public function testChangerStatutAnnulePersisteLeStatut(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');

        $result = EvenementService::changerStatutAnnule((int) $event['id'], 'Plus disponible');

        $this->assertSame('ANNULE', $result['statut']);
        $this->assertSame('Plus disponible', $result['motif_refus']);

        $transition = Database::one(
            'SELECT * FROM transition_history WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $event['id']]
        );
        $this->assertSame('ANNULE', $transition['statut_apres']);
        $this->assertSame($event['statut'], $transition['statut_avant']);
    }

    public function testChangerStatutAnnuleRefuseLesTransitionsInterdites(): void
    {
        $this->assertFalse(EvenementService::transitionAutorisee('PROGRAMME', 'ANNULE'));
        $this->assertTrue(EvenementService::transitionAutorisee('EN_ATTENTE', 'ANNULE'));
        $this->assertTrue(EvenementService::transitionAutorisee('MODIFICATION_DEMANDEE', 'ANNULE'));
    }

    public function testQueryFiltresAppliesConditions(): void
    {
        [$sql, $params] = EvenementService::queryFiltres(['statut' => 'PROGRAMME', 'q' => 'école', 'deleted' => false]);

        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('e.statut = ?', $sql);
        $this->assertStringContainsString('e.deleted_at IS NULL', $sql);
        $this->assertContains('PROGRAMME', $params);

        $rows = Database::all($sql, $params);
        foreach ($rows as $row) {
            $this->assertSame('PROGRAMME', $row['statut']);
        }
    }

    public function testAutoCloturerClotureLesEvenementsDepasses(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $epic  = (int) Database::value('SELECT epic_id FROM epic_anomalies ORDER BY epic_id, anomalie_id LIMIT 1');

        EvenementService::programmer(
            (int) $event['id'],
            date('Y-m-d', strtotime('-5 days')),
            '09:30:00',
            [$epic],
            (int) ($event['association_id'] ?? 0)
        );

        $count = EvenementService::autoCloturer();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(
            'TERMINE',
            Database::value('SELECT statut FROM evenements WHERE id = ?', [(int) $event['id']])
        );

        $transition = Database::one(
            'SELECT * FROM transition_history WHERE evenement_id = ? ORDER BY id DESC LIMIT 1',
            [(int) $event['id']]
        );
        $this->assertSame('TERMINE', $transition['statut_apres']);
        $this->assertSame('PROGRAMME', $transition['statut_avant']);
    }

    public function testAutoCloturerEpargneLesEvenementsFuturs(): void
    {
        $event = $this->eventByStatus('EN_ATTENTE');
        $epic  = (int) Database::value('SELECT epic_id FROM epic_anomalies ORDER BY epic_id, anomalie_id LIMIT 1');

        EvenementService::programmer(
            (int) $event['id'],
            date('Y-m-d', strtotime('+30 days')),
            '09:30:00',
            [$epic],
            (int) ($event['association_id'] ?? 0)
        );

        EvenementService::autoCloturer();

        $this->assertSame(
            'PROGRAMME',
            Database::value('SELECT statut FROM evenements WHERE id = ?', [(int) $event['id']])
        );
    }

    public function testNextActionPourEnAttente(): void
    {
        $na = EvenementService::nextAction(
            ['id' => 5, 'statut' => 'EN_ATTENTE', 'deadline_at' => null, 'capacite' => 0],
            []
        );
        $this->assertSame('EN_ATTENTE', $na['statut']);
        $this->assertSame('Wilaya', $na['responsable']);
        $this->assertSame('haute', $na['priorite']);
        $this->assertNotSame('', $na['titre']);
    }

    public function testNextActionTermineSansAlbum(): void
    {
        $na = EvenementService::nextAction(
            ['id' => 9, 'statut' => 'TERMINE', 'deadline_at' => null, 'capacite' => 50],
            ['album_existe' => false]
        );
        $this->assertSame('TERMINE', $na['statut']);
        $this->assertStringContainsString('album', strtolower($na['titre']));
    }

    public function testCompletudeDetecteDossierIncomplet(): void
    {
        $event = ['adresse' => '', 'commune_id' => 0, 'association_id' => 0, 'description' => '', 'date_evenement' => '', 'heure' => '', 'capacite' => 0, 'latitude' => '', 'statut' => 'EN_ATTENTE'];
        $comp = EvenementService::completudeEvent($event, []);
        $this->assertLessThan(100, $comp['score']);
        $this->assertNotEmpty($comp['manque']);
    }

    public function testCompletudeDossierComplet(): void
    {
        $event = ['adresse' => 'A', 'commune_id' => 1, 'association_id' => 1, 'description' => 'D', 'date_evenement' => '2026-12-01', 'heure' => '09:00:00', 'capacite' => 100, 'latitude' => 36.7, 'longitude' => 3.0, 'statut' => 'PROGRAMME'];
        $comp = EvenementService::completudeEvent($event, ['epics_count' => 1]);
        $this->assertSame(100, $comp['score']);
        $this->assertSame([], $comp['manque']);
    }

    public function testPrioriteDossierUrgent(): void
    {
        $event = ['statut' => 'EN_ATTENTE', 'deadline_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')), 'id' => 7];
        $prio = EvenementService::prioriteDossier($event, []);
        $this->assertSame('urgent', $prio['niveau']);
        $this->assertNotEmpty($prio['raisons']);
    }

    public function testSuggestionsAdminRemplies(): void
    {
        $event = ['statut' => 'EN_ATTENTE', 'adresse' => 'A', 'commune_id' => 1, 'association_id' => 1, 'description' => 'D', 'date_evenement' => '', 'heure' => '09:00:00', 'capacite' => 10, 'latitude' => 36.7, 'longitude' => 3.0, 'deadline_at' => null, 'id' => 8, 'created_at' => date('Y-m-d H:i:s')];
        $sugs = EvenementService::suggestionsAdmin($event, ['epics_count' => 0]);
        $this->assertNotEmpty($sugs);
    }

    public function testEstimationDelaiTermine(): void
    {
        $est = EvenementService::estimDelaiTraitement(['statut' => 'TERMINE'], []);
        $this->assertSame(0, $est['jours']);
        $this->assertSame('haute', $est['confiance']);
    }

    public function testEstimationDelaiAvantEvenement(): void
    {
        $est = EvenementService::estimDelaiTraitement([
            'statut'        => 'PROGRAMME',
            'date_evenement'=> date('Y-m-d', strtotime('+5 days')),
        ], []);
        $this->assertSame(5, $est['jours']);
    }

    public function testEstimationDelaiSlaDepasse(): void
    {
        $est = EvenementService::estimDelaiTraitement([
            'statut'     => 'EN_ATTENTE',
            'deadline_at'=> date('Y-m-d H:i:s', strtotime('-1 day')),
        ], []);
        $this->assertStringContainsString('dépassé', strtolower($est['label']));
    }

    public function testRelancesEscaladesStructure(): void
    {
        $escs = EvenementService::relancesEscalades(10);
        $this->assertIsArray($escs);
        foreach (array_slice($escs, 0, 5) as $e) {
            $this->assertArrayHasKey('type', $e);
            $this->assertArrayHasKey('gravite', $e);
            $this->assertArrayHasKey('evenement_id', $e);
            $this->assertContains($e['gravite'], ['haute', 'moyenne', 'normale']);
        }
    }
}
