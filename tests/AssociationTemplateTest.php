<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;

/**
 * Vérifie le socle des modèles de demande (Lot 4) :
 *   - schéma de la table demande_modeles,
 *   - insertion / lecture / suppression d'un modèle,
 *   - sauvegarde des anomalies au format JSON.
 */
final class AssociationTemplateTest extends DatabaseTestCase
{
    private function associationId(): int
    {
        $id = (int) Database::value('SELECT id FROM associations ORDER BY id LIMIT 1');

        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function testDemandeModelesSchema(): void
    {
        $cols = array_column(Database::all('SHOW COLUMNS FROM demande_modeles'), 'Field');

        foreach (['id', 'association_id', 'nom', 'commune_id', 'adresse', 'capacite', 'informations', 'anomalies'] as $col) {
            $this->assertContains($col, $cols);
        }
    }

    public function testStoreAndReadTemplate(): void
    {
        $associationId = $this->associationId();
        $anomalies     = array_map(
            'intval',
            array_column(Database::all('SELECT anomalie_id FROM epic_anomalies LIMIT 2'), 'anomalie_id')
        );

        $id = Database::insert('demande_modeles', [
            'association_id' => $associationId,
            'nom'            => 'Nettoyage plage — format type',
            'commune_id'     => 1,
            'adresse'        => 'Plage des Sablettes, Oran',
            'capacite'       => 100,
            'informations'   => 'Matériel fourni',
            'anomalies'      => json_encode($anomalies),
        ]);

        $this->assertGreaterThan(0, $id);

        $row = Database::one('SELECT * FROM demande_modeles WHERE id = ?', [$id]);
        $this->assertNotNull($row);
        $this->assertSame($associationId, (int) $row['association_id']);
        $this->assertSame('Plage des Sablettes, Oran', $row['adresse']);
        $this->assertSame($anomalies, array_map('intval', json_decode((string) $row['anomalies'], true)));
    }

    public function testDeleteTemplate(): void
    {
        $associationId = $this->associationId();

        $id = Database::insert('demande_modeles', [
            'association_id' => $associationId,
            'nom'            => 'Modèle temporaire',
        ]);

        Database::run('DELETE FROM demande_modeles WHERE id = ? AND association_id = ?', [$id, $associationId]);

        $this->assertNull(Database::one('SELECT id FROM demande_modeles WHERE id = ?', [$id]));
    }
}