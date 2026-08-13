<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;

/**
 * Vérifie le socle utilisé par l'espace EPIC :
 *   - schéma evenement_epic (id AUTO_INCREMENT + statut d'intervention),
 *   - lecture intervention avec coordonnées association (Phase 6 §4),
 *   - journalisation AuditLog + notification Wilaya sur ANOMALIE (O2).
 */
final class EpicFlowTest extends DatabaseTestCase
{
    public function testEvenementEpicSchemaExposesIdAndStatut(): void
    {
        $columns = Database::all('SHOW COLUMNS FROM evenement_epic');

        $cols = array_column($columns, 'Field');

        $this->assertContains('id', $cols);
        $this->assertContains('statut', $cols);
        $this->assertContains('date_affectation', $cols);
        $this->assertContains('observation', $cols);

        $statut = array_values(array_filter(
            $columns,
            static fn (array $c): bool => $c['Field'] === 'statut'
        ));
        $this->assertStringContainsString('ANOMALIE', (string) ($statut[0]['Type'] ?? ''));
    }

    public function testInterventionQueryReturnsAssociationContact(): void
    {
        $row = Database::one(
            'SELECT e.id AS evenement_id, e.adresse AS evenement_adresse,
                    ee.id AS intervention_id, ee.statut AS intervention_statut,
                    a.nom AS association_nom, a.email AS association_email,
                    a.telephone AS association_telephone,
                    a.nom_prenom_president AS association_president
             FROM evenements e
             JOIN evenement_epic ee ON ee.evenement_id = e.id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.deleted_at IS NULL
             LIMIT 1'
        );

        $this->assertNotNull($row, 'Une intervention EPIC doit exister (seed).');
        $this->assertArrayHasKey('intervention_id', $row);
        $this->assertArrayHasKey('intervention_statut', $row);
        $this->assertArrayHasKey('association_email', $row);
        $this->assertArrayHasKey('association_telephone', $row);
        $this->assertArrayHasKey('association_president', $row);
    }

    public function testUpdateStatutWritesAuditLog(): void
    {
        $intervention = Database::one(
            'SELECT id FROM evenement_epic ORDER BY id LIMIT 1'
        );
        $this->assertNotNull($intervention);

        $id = (int) $intervention['id'];

        $ancien = (string) (Database::value('SELECT statut FROM evenement_epic WHERE id = ?', [$id]) ?? 'AFFECTE');

        Database::run('UPDATE evenement_epic SET statut = ? WHERE id = ?', ['EN_COURS', $id]);

        AuditLog::log(
            'epic.intervention_statut',
            'evenement_epic',
            $id,
            ['statut' => $ancien],
            ['statut' => 'EN_COURS']
        );

        $audit = Database::value(
            'SELECT COUNT(*) FROM audit_logs WHERE action = ? AND modele = ? AND modele_id = ?',
            ['epic.intervention_statut', 'evenement_epic', $id]
        );
        $this->assertSame(1, (int) $audit);

        $this->assertSame('EN_COURS', Database::value('SELECT statut FROM evenement_epic WHERE id = ?', [$id]));
    }

    public function testAnomalieNotifiesWilaya(): void
    {
        $wilayaCount = (int) Database::value(
            "SELECT COUNT(*) FROM users WHERE role_user = 'wilaya' AND is_active = 1"
        );
        $this->assertGreaterThan(0, $wilayaCount);

        $sent = Notification::sendToRole(
            'wilaya',
            'Anomalie signalée',
            "L'EPIC a signalé une anomalie.",
            'epic_anomalie',
            ['evenement_id' => 1]
        );

        $this->assertSame($wilayaCount, $sent);

        $notifs = (int) Database::value(
            "SELECT COUNT(*) FROM notifications WHERE type = 'epic_anomalie' AND lu = 0"
        );
        $this->assertSame($wilayaCount, (int) $notifs);
    }
}
