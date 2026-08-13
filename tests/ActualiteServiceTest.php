<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\ActualiteService;
use App\Helpers\Database;

/**
 * Page publique « Actualités & événements à venir » (/actualites).
 */
final class ActualiteServiceTest extends DatabaseTestCase
{
    public function testDataContientLesClefsDeLaPage(): void
    {
        $data = ActualiteService::data();

        $this->assertIsArray($data);
        foreach (['actualites', 'evenements', 'items', 'prochains', 'theme'] as $cle) {
            $this->assertArrayHasKey($cle, $data, "Clé manquante : {$cle}");
        }
        $this->assertIsArray($data['theme']);
        $this->assertArrayHasKey('primary', $data['theme']);
    }

    public function testSeulsLesElementsPubliesEtNonSupprimesSontRenvoyes(): void
    {
        $idPublie = Database::insert('landing_news', [
            'titre_fr' => 'Actualité publiée',
            'type'     => 'actualite',
            'statut'   => 'publie',
            'actif'    => 1,
        ]);

        Database::insert('landing_news', [
            'titre_fr' => 'Actualité brouillon',
            'type'     => 'actualite',
            'statut'   => 'brouillon',
            'actif'    => 0,
        ]);

        $idSupprime = Database::insert('landing_news', [
            'titre_fr' => 'Actualité supprimée',
            'type'     => 'actualite',
            'statut'   => 'publie',
            'actif'    => 1,
        ]);
        Database::update('landing_news', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$idSupprime]);

        $actualites = ActualiteService::data()['actualites'];
        $titres = array_column($actualites, 'titre_fr');

        $this->assertContains('Actualité publiée', $titres, 'Les éléments publiés doivent apparaître.');
        $this->assertNotContains('Actualité brouillon', $titres, 'Les brouillons ne doivent pas apparaître.');
        $this->assertNotContains('Actualité supprimée', $titres, 'Les éléments supprimés ne doivent pas apparaître.');

        $this->assertSame((int) $idPublie, (int) $actualites[0]['id'], 'L\'actualité publiée est identifiée.');
    }

    public function testEvenementsManuelsSaisieLibreEtLies(): void
    {
        // Saisie libre (sans lien)
        Database::insert('landing_news', [
            'titre_fr' => 'Atelier libre',
            'type'     => 'evenement',
            'statut'   => 'publie',
            'actif'    => 1,
            'date_event' => '2026-09-10',
        ]);

        // Lié à un événement structuré PROGRAMME à venir
        $evenement = Database::one(
            "SELECT id FROM evenements WHERE statut = 'PROGRAMME' AND deleted_at IS NULL AND date_evenement >= CURDATE() ORDER BY id LIMIT 1"
        );
        $this->assertNotNull($evenement, 'Un événement PROGRAMME à venir doit exister en base de test.');

        Database::insert('landing_news', [
            'titre_fr'     => 'Vernissage curation',
            'type'         => 'evenement',
            'evenement_id' => (int) $evenement['id'],
            'statut'       => 'publie',
            'actif'        => 1,
        ]);

        $data  = ActualiteService::data();
        $titres = array_column($data['evenements'], 'titre_fr');
        $lieIds = array_column($data['evenements'], 'evenement_id');

        $this->assertContains('Atelier libre', $titres, 'La saisie libre doit apparaître.');
        $this->assertContains('Vernissage curation', $titres, 'L\'élément lié doit apparaître.');

        // L'événement lié remplace l'événement structuré dans la grille (pas de doublon).
        $adresse = Database::value('SELECT adresse FROM evenements WHERE id = ?', [(int) $evenement['id']]);
        $this->assertNotContains($adresse, $titres, 'L\'événement structuré lié ne doit pas être dupliqué.');

        // L'événement lié ne réapparaît pas comme « saisie libre » :
        // seul l'élément CMS lié porte cet evenement_id.
        $cmsLies = array_values(array_filter(
            $data['evenements'],
            static fn (array $e): bool => $e['source'] === 'cms' && $e['evenement_id'] !== null
        ));
        $this->assertSame(
            [(int) $evenement['id']],
            array_map(static fn (array $e): int => $e['evenement_id'], $cmsLies)
        );
    }

    public function testEvenementsSynchronisesExclusLesArchives(): void
    {
        $evenement = Database::one(
            "SELECT id FROM evenements WHERE statut = 'PROGRAMME' AND deleted_at IS NULL AND date_evenement >= CURDATE() ORDER BY id LIMIT 1"
        );
        $this->assertNotNull($evenement);

        Database::update('evenements', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $evenement['id']]);

        $ids = array_column(ActualiteService::data()['evenements'], 'evenement_id');
        $this->assertNotContains((int) $evenement['id'], $ids, 'Un événement archivé ne doit pas être synchronisé.');
    }

    public function testProchainsTriesParDateCroissanteEtLimites(): void
    {
        $data = ActualiteService::data();

        $this->assertLessThanOrEqual(6, count($data['prochains']), 'La liste latérale est plafonnée.');

        $dates = array_column($data['prochains'], 'date_event');
        $sorted = $dates;
        sort($sorted);

        $this->assertSame($sorted, $dates, 'Les prochains événements sont triés par date croissante.');
        foreach ($data['prochains'] as $item) {
            $this->assertNotNull($item['date_event'], 'Seuls les événements datés figurent dans la liste latérale.');
        }
    }
}
