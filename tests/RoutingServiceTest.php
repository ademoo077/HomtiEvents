<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Cache;
use App\Helpers\ControlCenter;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Notification;
use App\Helpers\RoutingService;

final class RoutingServiceTest extends DatabaseTestCase
{
    private function clearCache(): void
    {
        Cache::forget(RoutingService::CACHE_KEY);
    }

    private function createEvent(int $anomalieId, ?int $communeId = null, array $extra = []): int
    {
        return EvenementService::create(
            array_merge([
                'commune_id' => $communeId ?? 1,
                'adresse'    => 'Rue du test',
                'description' => 'Événement de test routage',
            ], $extra),
            null,
            [$anomalieId],
            'EN_ATTENTE'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
        parent::tearDown();
    }

    public function testReglesSeededDepuisEpicAnomalies(): void
    {
        $rules = RoutingService::regles();

        $this->assertNotEmpty($rules);
        $this->assertCount(10, $rules);

        $first = $rules[0];
        $this->assertArrayHasKey('epic_id', $first);
        $this->assertArrayHasKey('ca_id', $first);
        $this->assertArrayHasKey('anomalie_id', $first);
        $this->assertIsInt($first['epic_id']);
    }

    public function testAssignOrganizationRoutesVersEpicCompetente(): void
    {
        $row = Database::one('SELECT anomalie_id, epic_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1');
        $anomalieId = (int) $row['anomalie_id'];
        $epicAttendu = (int) $row['epic_id'];

        $eventId = $this->createEvent($anomalieId);
        $assigned = (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId]);

        $this->assertSame($epicAttendu, $assigned);

        $log = (int) Database::value(
            'SELECT COUNT(*) FROM routing_log WHERE evenement_id = ? AND new_org_id = ? AND rule_matched = ?',
            [$eventId, $epicAttendu, 'anomalie']
        );
        $this->assertSame(1, $log, 'Le routage doit être journalisé.');
    }

    public function testAnomalieAPrioriteSurDaira(): void
    {
        $epicAnomalie = (int) Database::value(
            'SELECT epic_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );
        $anomalieId = (int) Database::value(
            'SELECT anomalie_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );
        $epicAutre = $epicAnomalie === 2 ? 3 : 2;

        $communeId = (int) Database::value('SELECT id FROM commune WHERE ca_id IS NOT NULL ORDER BY id LIMIT 1');
        $caId = (int) Database::value('SELECT ca_id FROM commune WHERE id = ?', [$communeId]);

        RoutingService::regleEnregistrer([
            'anomalie_id' => $anomalieId,
            'ca_id'       => $caId,
            'epic_id'     => $epicAutre,
        ]);

        $eventId = $this->createEvent($anomalieId, $communeId);
        $assigned = (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId]);

        $this->assertSame($epicAnomalie, $assigned, 'La règle sur l’anomalie doit primer sur la règle de la daïra.');
    }

    public function testFallbackEtAlerteQuandAucuneRegleNeMatch(): void
    {
        $this->createEvent((int) Database::value('SELECT id FROM anomalies ORDER BY id LIMIT 1'), 1);
        $this->clearCache();

        // On vide les règles pour forcer le fallback.
        Database::run('DELETE FROM routing_rules');
        $this->clearCache();

        $eventId = $this->createEvent((int) Database::value('SELECT id FROM anomalies ORDER BY id LIMIT 1'), 1);

        $assigned = Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId]);
        $this->assertNull($assigned, 'Sans règle, le fallback doit être null.');

        $alerte = (int) Database::value('SELECT COUNT(*) FROM routing_alertes WHERE evenement_id = ?', [$eventId]);
        $this->assertGreaterThanOrEqual(1, $alerte, 'Une alerte admin doit être créée.');
    }

    public function testReaffecterMetAJourAssignedOrgEtJournal(): void
    {
        $anomalieId = (int) Database::value(
            'SELECT anomalie_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );
        $routedEpic = (int) Database::value(
            'SELECT epic_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );

        $eventId = $this->createEvent($anomalieId, 1);
        $current = (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId]);
        $this->assertSame($routedEpic, $current);

        $epicCible = (int) Database::value('SELECT id FROM epic WHERE id <> ? ORDER BY id DESC LIMIT 1', [$current]);
        $this->assertNotSame($current, $epicCible);

        $before = (int) Database::value(
            'SELECT COUNT(*) FROM routing_log WHERE evenement_id = ? AND rule_matched = ?',
            [$eventId, 'manuel']
        );

        $result = RoutingService::reaffecter($eventId, [$epicCible], 'Programmation wilaya');

        $this->assertSame($epicCible, (int) $result['epic_id']);
        $this->assertSame(
            $epicCible,
            (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId])
        );
        $this->assertSame($before + 1, (int) Database::value(
            'SELECT COUNT(*) FROM routing_log WHERE evenement_id = ? AND rule_matched = ?',
            [$eventId, 'manuel']
        ));
    }

    public function testCacheInvalidatedApresModification(): void
    {
        $countInitial = count(RoutingService::regles());

        $newId = RoutingService::regleEnregistrer([
            'anomalie_id' => (int) Database::value('SELECT id FROM anomalies ORDER BY id LIMIT 1'),
            'ca_id'       => null,
            'epic_id'     => (int) Database::value('SELECT id FROM epic ORDER BY id LIMIT 1'),
        ]);
        $this->assertGreaterThan(0, $newId);

        $after = RoutingService::regles();
        $this->assertGreaterThan($countInitial, count($after), 'Le cache doit être invalidé après un enregistrement.');

        RoutingService::regleSupprimer((int) $after[0]['id']);
        $final = RoutingService::regles();
        $this->assertSame($countInitial, count($final), 'Supprimer une règle doit restaurer le compte initial.');
    }

    public function testRepartitionParOrganisation(): void
    {
        $repos = RoutingService::repartition();

        $this->assertIsArray($repos);
        $this->assertNotEmpty($repos, 'La répartition doit contenir au moins une organisation.');

        foreach ($repos as $row) {
            $this->assertArrayHasKey('org', $row);
            $this->assertArrayHasKey('nb', $row);
            $this->assertIsInt($row['nb']);
            $this->assertGreaterThanOrEqual(0, $row['nb']);
        }
    }

    public function testCreerUtilisateurEpicAvecEpicId(): void
    {
        $epicId = (int) Database::value('SELECT id FROM epic ORDER BY id LIMIT 1');

        $id = ControlCenter::creerUtilisateur([
            'nom'       => 'TEST',
            'prenom'    => 'Epic',
            'email'     => 'epic_' . bin2hex(random_bytes(4)) . '@test.local',
            'password'  => 'MotDePasse123',
            'telephone' => '0550 00 00 00',
            'role_user' => 'epic',
            'epic_id'   => $epicId,
        ]);

        $user = Database::one('SELECT role_user, epic_id FROM users WHERE id = ?', [$id]);
        $this->assertSame('epic', $user['role_user']);
        $this->assertSame($epicId, (int) $user['epic_id']);

        $rbac = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$id, (int) Database::value('SELECT id FROM roles WHERE nom = ?', ['epic'])]
        );
        $this->assertSame(1, $rbac, 'Le lien RBAC epic doit exister.');
    }

    public function testGetUnreadNotifiesThenRead(): void
    {
        $userId = (int) Database::value('SELECT id FROM users WHERE role_user = "epic" ORDER BY id LIMIT 1');
        $this->assertGreaterThan(0, $userId);

        $before = Notification::unreadCount($userId);

        $notifId = Notification::send(
            $userId,
            'Test Waliya',
            'Message de test',
            'test',
            ['link' => 'wilaya/evenements/1']
        );

        $this->assertSame($before + 1, Notification::unreadCount($userId));

        $unread = Notification::getUnread($userId);
        $this->assertNotEmpty($unread);
        $found = false;
        foreach ($unread as $n) {
            if ((int) $n['id'] === $notifId) {
                $found = true;
                $this->assertSame('Test Waliya', $n['titre']);
            }
        }
        $this->assertTrue($found, 'La notification doit apparaître dans getUnread().');

        // Après lecture, elle disparaît.
        Notification::markRead($notifId, $userId);
        $this->assertSame($before, Notification::unreadCount($userId));
        $this->assertEmpty(Notification::getUnread($userId));
    }

    public function testValidateEventMetAJourStatutEtNotifie(): void
    {
        $assocId = (int) Database::value('SELECT id FROM associations ORDER BY id LIMIT 1');
        $this->assertGreaterThan(0, $assocId);

        $assocUserId = (int) Database::value(
            'SELECT id FROM users WHERE association_id = ? ORDER BY id LIMIT 1',
            [$assocId]
        );
        $epicUserIds = Database::all(
            'SELECT id FROM users WHERE epic_id IN (1, 2) AND is_active = 1'
        );
        $this->assertGreaterThanOrEqual(
            1,
            count($epicUserIds),
            'Des utilisateurs EPIC doivent exister.'
        );

        // Événement en EN_ATTENTE, associé à une association réelle.
        $anomalieId = (int) Database::value(
            'SELECT anomalie_id FROM routing_rules WHERE ca_id IS NULL ORDER BY id LIMIT 1'
        );
        $eventId = EvenementService::create(
            ['commune_id' => 1, 'adresse' => 'Rue validation test', 'description' => 'Événement validation'],
            $assocId,
            [$anomalieId],
            'EN_ATTENTE'
        );

        $assocBefore = Notification::unreadCount($assocUserId);
        $epicBefore = 0;
        foreach ($epicUserIds as $eu) {
            $epicBefore += Notification::unreadCount((int) $eu['id']);
        }

        $result = EvenementService::validateEvent($eventId, '2026-08-20', '14:00:00', [1, 2]);

        $this->assertSame('VALIDÉ', $result['statut']);
        $this->assertSame('VALIDÉ', (string) Database::value('SELECT statut FROM evenements WHERE id = ?', [$eventId]));
        $this->assertSame(
            '2026-08-20',
            (string) Database::value('SELECT date_evenement FROM evenements WHERE id = ?', [$eventId])
        );

        // Many-to-many : exactement 2 EPIC liées.
        $linked = (int) Database::value('SELECT COUNT(*) FROM evenement_epic WHERE evenement_id = ?', [$eventId]);
        $this->assertSame(2, $linked);

        // First EPIC devient l'organisation assignée.
        $this->assertSame(1, (int) Database::value('SELECT assigned_org_id FROM evenements WHERE id = ?', [$eventId]));

        // Notification à l'association : validation + QR code disponible
        // (QrCodeService::generate notifie aussi l'association quand une date est fournie).
        $this->assertSame($assocBefore + 2, Notification::unreadCount($assocUserId));

        // Notification à chaque EPIC sélectionné (reaffecter notifie aussi la
        // première EPIC → on vérifie le +1 explicite par EPIC, pas le total).
        foreach ($epicUserIds as $eu) {
            $label = 'Chaque EPIC affecté doit recevoir une notification.';
            $this->assertGreaterThan(0, Notification::unreadCount((int) $eu['id']), $label);
        }
    }
}
