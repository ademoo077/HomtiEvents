<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\EvenementService;

/**
 * Vérifie le parcours citoyen (Lot 6) :
 *   - schéma : table citoyen_favoris (PK user_id / evenement_id),
 *   - ajout / suppression d'un favori (idempotence par contrainte PK),
 *   - recommandations simples d'événements.
 */
final class CitoyenFavorisTest extends DatabaseTestCase
{
    private function makeUser(string $email): int
    {
        return Database::insert('users', [
            'nom'       => 'Test',
            'prenom'    => 'Citoyen',
            'email'     => $email,
            'password'  => password_hash('secret', PASSWORD_DEFAULT),
            'role_user' => 'citoyen',
        ]);
    }

    private function makeEvent(?int $communeId = null, string $adresse = '', string $daysAhead = '30'): int
    {
        return Database::insert('evenements', [
            'adresse'       => $adresse,
            'commune_id'    => $communeId,
            'statut'        => 'PROGRAMME',
            'date_evenement' => date('Y-m-d', strtotime("+{$daysAhead} days")),
        ]);
    }

    public function testFavorisSchema(): void
    {
        $cols = array_column(Database::all('SHOW COLUMNS FROM citoyen_favoris'), 'Field');

        $this->assertContains('user_id', $cols);
        $this->assertContains('evenement_id', $cols);
        $this->assertContains('created_at', $cols);
    }

    public function testAjoutEtSuppressionFavori(): void
    {
        $userId = $this->makeUser('fav-add@example.com');
        $eventId = $this->makeEvent(null, 'Favori test');

        Database::insert('citoyen_favoris', [
            'user_id'       => $userId,
            'evenement_id'  => $eventId,
        ]);

        $count = (int) Database::value(
            'SELECT COUNT(*) FROM citoyen_favoris WHERE user_id = ? AND evenement_id = ?',
            [$userId, $eventId]
        );
        $this->assertSame(1, $count, 'Le favori doit être enregistré.');

        Database::delete('citoyen_favoris', 'user_id = ? AND evenement_id = ?', [$userId, $eventId]);

        $count = (int) Database::value(
            'SELECT COUNT(*) FROM citoyen_favoris WHERE user_id = ? AND evenement_id = ?',
            [$userId, $eventId]
        );
        $this->assertSame(0, $count, 'Le favori doit être retiré.');
    }

    public function testFavoriImpossibleEnDoublon(): void
    {
        $userId = $this->makeUser('fav-dup@example.com');
        $eventId = $this->makeEvent(null, 'Doublon test');

        Database::insert('citoyen_favoris', ['user_id' => $userId, 'evenement_id' => $eventId]);

        $this->expectException(\Throwable::class);
        Database::insert('citoyen_favoris', ['user_id' => $userId, 'evenement_id' => $eventId]);
    }

    public function testRecommandationsExcluentEvenementsParticipes(): void
    {
        $userId = $this->makeUser('reco-1@example.com');

        $c1 = Database::insert('commune', ['nom' => 'Commune A']);

        $participated = $this->makeEvent($c1, 'Événement déjà rejoint', '1');
        $favCommune   = $this->makeEvent($c1, 'Événement dans ma commune', '2');

        // Le citoyen a déjà participé à un événement de la commune A.
        Database::insert('evenement_participant', [
            'evenement_id' => $participated,
            'user_id'      => $userId,
        ]);

        $recos = EvenementService::recommandationsPourCitoyen($userId, 6);

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $recos);
        $this->assertNotContains($participated, $ids, 'L\'événement déjà rejoint ne doit pas être recommandé.');

        // Grâce à l'affinité de commune, l'événement de la commune fréquentée
        // doit être recommandé et classé en tête avec la raison 'commune'.
        $this->assertNotEmpty($recos);
        $this->assertContains($favCommune, $ids);
        $this->assertSame('commune', $recos[0]['raison']);
        $this->assertSame($favCommune, $ids[0] ?? null);
    }

    public function testRecommandationsSansHistoriqueRetournentProchains(): void
    {
        $userId = $this->makeUser('reco-2@example.com');

        $upcoming = $this->makeEvent(null, 'Prochain événement', '15');
        // Événement passé : ne doit jamais être recommandé.
        Database::insert('evenements', [
            'adresse'       => 'Événement passé',
            'statut'        => 'TERMINE',
            'date_evenement' => date('Y-m-d', strtotime('-10 days')),
        ]);

        $recos = EvenementService::recommandationsPourCitoyen($userId, 6);

        $ids = array_map(static fn (array $r): int => (int) $r['id'], $recos);
        $this->assertContains($upcoming, $ids);
        $this->assertNotContains('Événement passé', array_column($recos, 'adresse'));
    }
}
