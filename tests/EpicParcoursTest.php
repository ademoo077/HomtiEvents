<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;

/**
 * Vérifie le parcours EPIC complet (Lot 5) :
 *   - schéma : colonnes acceptation/clôture sur evenement_epic + table epic_preuves,
 *   - flux d'acceptation et de refus,
 *   - changement de statut gated par l'acceptation,
 *   - clôture d'intervention (rapport),
 *   - preuves avant / après (insertion / suppression).
 */
final class EpicParcoursTest extends DatabaseTestCase
{
    private function epicId(): int
    {
        $id = (int) Database::value('SELECT id FROM epic ORDER BY id LIMIT 1');
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    private function createIntervention(string $statut = 'AFFECTE', string $accepte = 'EN_ATTENTE'): array
    {
        $epicId = $this->epicId();

        $evenementId = Database::insert('evenements', [
            'adresse'    => 'Intervention test, rue des Arbres',
            'statut'     => 'VALIDE',
            'latitude'   => 36.7538,
            'longitude'  => 3.0588,
        ]);

        $eeId = Database::insert('evenement_epic', [
            'evenement_id' => $evenementId,
            'epic_id'      => $epicId,
            'statut'       => $statut,
            'accepte'      => $accepte,
        ]);

        return ['evenement_id' => $evenementId, 'ee_id' => $eeId, 'epic_id' => $epicId];
    }

    public function testEpicParcoursSchema(): void
    {
        $cols = array_column(Database::all('SHOW COLUMNS FROM evenement_epic'), 'Field');

        foreach (['accepte', 'date_acceptation', 'motif_refus', 'date_debut_reel', 'date_fin_reel', 'cloture', 'date_cloture', 'rapport'] as $col) {
            $this->assertContains($col, $cols);
        }

        $preuves = array_column(Database::all('SHOW COLUMNS FROM epic_preuves'), 'Field');
        $this->assertContains('type', $preuves);
        $this->assertContains('fichier', $preuves);
        $this->assertContains('evenement_epic_id', $preuves);
    }

    public function testAcceptFlow(): void
    {
        $data = $this->createIntervention();

        Database::run(
            "UPDATE evenement_epic SET accepte = 'ACCEPTE', date_acceptation = CURRENT_TIMESTAMP WHERE id = ?",
            [$data['ee_id']]
        );

        $row = Database::one('SELECT accepte, date_acceptation FROM evenement_epic WHERE id = ?', [$data['ee_id']]);
        $this->assertSame('ACCEPTE', $row['accepte']);
        $this->assertNotNull($row['date_acceptation']);
    }

    public function testRefuseFlow(): void
    {
        $data = $this->createIntervention();

        Database::run(
            "UPDATE evenement_epic SET accepte = 'REFUSE', motif_refus = ?, date_acceptation = CURRENT_TIMESTAMP WHERE id = ?",
            ['Capacité insuffisante', $data['ee_id']]
        );

        $row = Database::one('SELECT accepte, motif_refus FROM evenement_epic WHERE id = ?', [$data['ee_id']]);
        $this->assertSame('REFUSE', $row['accepte']);
        $this->assertSame('Capacité insuffisante', $row['motif_refus']);
    }

    public function testStatutAndDateReelGatedByAcceptance(): void
    {
        $accepted = $this->createIntervention('AFFECTE', 'ACCEPTE');

        Database::run(
            "UPDATE evenement_epic SET statut = 'EN_COURS', date_debut_reel = CURRENT_TIMESTAMP WHERE id = ? AND accepte = 'ACCEPTE'",
            [$accepted['ee_id']]
        );

        $row = Database::one('SELECT statut, date_debut_reel FROM evenement_epic WHERE id = ?', [$accepted['ee_id']]);
        $this->assertSame('EN_COURS', $row['statut']);
        $this->assertNotNull($row['date_debut_reel']);

        $pending = $this->createIntervention('AFFECTE', 'EN_ATTENTE');
        Database::run(
            "UPDATE evenement_epic SET statut = 'EN_COURS' WHERE id = ? AND accepte = 'ACCEPTE'",
            [$pending['ee_id']]
        );
        $row2 = Database::one('SELECT statut FROM evenement_epic WHERE id = ?', [$pending['ee_id']]);
        $this->assertSame('AFFECTE', $row2['statut'], 'Une intervention non acceptée ne doit pas voir son statut changé.');
    }

    public function testCloture(): void
    {
        $data = $this->createIntervention('EN_COURS', 'ACCEPTE');

        Database::run(
            "UPDATE evenement_epic
             SET statut = 'TERMINE', cloture = 'CLOTUREE', date_cloture = CURRENT_TIMESTAMP,
                 date_fin_reel = CURRENT_TIMESTAMP, rapport = ?
             WHERE id = ?",
            ['Intervention réalisée avec succès', $data['ee_id']]
        );

        $row = Database::one('SELECT statut, cloture, rapport, date_cloture, date_fin_reel FROM evenement_epic WHERE id = ?', [$data['ee_id']]);
        $this->assertSame('TERMINE', $row['statut']);
        $this->assertSame('CLOTUREE', $row['cloture']);
        $this->assertSame('Intervention réalisée avec succès', $row['rapport']);
        $this->assertNotNull($row['date_cloture']);
        $this->assertNotNull($row['date_fin_reel']);
    }

    public function testPreuvesInsertAndDelete(): void
    {
        $data = $this->createIntervention('EN_COURS', 'ACCEPTE');

        $avant = Database::insert('epic_preuves', [
            'evenement_epic_id' => $data['ee_id'],
            'type'              => 'AVANT',
            'fichier'           => '/uploads/preuves/avant.jpg',
            'type_mime'         => 'image/jpeg',
            'taille'            => 2048,
        ]);
        $apres = Database::insert('epic_preuves', [
            'evenement_epic_id' => $data['ee_id'],
            'type'              => 'APRES',
            'fichier'           => '/uploads/preuves/apres.jpg',
            'type_mime'         => 'image/jpeg',
            'taille'            => 4096,
        ]);

        $this->assertGreaterThan(0, $avant);
        $this->assertGreaterThan(0, $apres);

        $rows = Database::all('SELECT type, fichier FROM epic_preuves WHERE evenement_epic_id = ? ORDER BY id', [$data['ee_id']]);
        $this->assertCount(2, $rows);
        $this->assertSame('AVANT', $rows[0]['type']);
        $this->assertSame('/uploads/preuves/apres.jpg', $rows[1]['fichier']);

        Database::run('DELETE FROM epic_preuves WHERE id = ?', [$avant]);
        $this->assertNull(Database::one('SELECT id FROM epic_preuves WHERE id = ?', [$avant]));
    }
}
