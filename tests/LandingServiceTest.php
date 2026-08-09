<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Database;
use App\Helpers\LandingService;

final class LandingServiceTest extends DatabaseTestCase
{
    public function testDataContientToutesLesSectionsDeLaLanding(): void
    {
        $data = LandingService::data();

        $this->assertIsArray($data);
        foreach (['upcoming', 'stats', 'totalParticipants', 'faq', 'testimonials', 'partners', 'gallery', 'beforeAfter', 'albums', 'mapEvents', 'anomalies', 'lastUpdate'] as $cle) {
            $this->assertArrayHasKey($cle, $data, "Clé manquante : {$cle}");
        }
        $this->assertCount(4, $data['stats'], 'La bande de statistiques doit contenir 4 éléments.');
        foreach ($data['stats'] as $stat) {
            $this->assertArrayHasKey('valeur', $stat);
            $this->assertArrayHasKey('libelle', $stat);
            $this->assertArrayHasKey('icone', $stat);
            $this->assertArrayHasKey('teinte', $stat);
        }
    }

    public function testFaqTrieParOrdreEtActifsUniquement(): void
    {
        Database::run('UPDATE landing_faq SET actif = 0');
        Database::run('INSERT INTO landing_faq (question_fr, reponse_fr, ordre, actif) VALUES (?, ?, ?, ?)', ['Q1', 'R1', 1, 1]);
        Database::run('INSERT INTO landing_faq (question_fr, reponse_fr, ordre, actif) VALUES (?, ?, ?, ?)', ['Q0', 'R0', 0, 1]);

        $faq = LandingService::data()['faq'];

        $this->assertCount(2, $faq, 'Seules les FAQ actives doivent être renvoyées.');
        $this->assertSame(['Q0', 'Q1'], array_column($faq, 'question_fr'), 'Les FAQ doivent être triées par ordre croissant.');
    }

    public function testPartenairesActifsTriesParOrdre(): void
    {
        Database::run('UPDATE landing_partners SET actif = 0');
        Database::run('INSERT INTO landing_partners (nom, ordre, actif) VALUES (?, ?, ?)', ['B', 5, 1]);
        Database::run('INSERT INTO landing_partners (nom, ordre, actif) VALUES (?, ?, ?)', ['A', 1, 1]);

        $partners = LandingService::data()['partners'];

        $this->assertCount(2, $partners);
        $this->assertSame(['A', 'B'], array_column($partners, 'nom'));
    }

    public function testGalerieManuelleActiveTrieeParSortOrder(): void
    {
        Database::run('UPDATE landing_gallery SET actif = 0');
        Database::run('INSERT INTO landing_gallery (titre_fr, image, sort_order, actif) VALUES (?, ?, ?, ?)', ['Second', '/img/b.jpg', 2, 1]);
        Database::run('INSERT INTO landing_gallery (titre_fr, image, sort_order, actif) VALUES (?, ?, ?, ?)', ['Premier', '/img/a.jpg', 1, 1]);

        $gallery = LandingService::data()['gallery'];

        $this->assertCount(2, $gallery);
        $this->assertSame(['Premier', 'Second'], array_column($gallery, 'titre_fr'));
    }

    public function testAvantApresActifEtPublieUniquement(): void
    {
        Database::run('UPDATE landing_before_after SET actif = 0');
        Database::run('INSERT INTO landing_before_after (titre_fr, image_before, image_after, statut, sort_order, actif) VALUES (?, ?, ?, ?, ?, ?)', ['Publie', '/a.jpg', '/b.jpg', 'publie', 1, 1]);
        Database::run('INSERT INTO landing_before_after (titre_fr, image_before, image_after, statut, sort_order, actif) VALUES (?, ?, ?, ?, ?, ?)', ['Brouillon', '/a.jpg', '/b.jpg', 'brouillon', 2, 1]);

        $beforeAfter = LandingService::data()['beforeAfter'];

        $this->assertCount(1, $beforeAfter);
        $this->assertSame('Publie', $beforeAfter[0]['titre_fr']);
    }

    public function testPhotosAlbumTrieesParSortOrderPuisUpload(): void
    {
        $event = $this->eventByStatus('PROGRAMME');
        $albumId = Database::insert('albums', [
            'evenement_id' => (int) $event['id'],
            'titre'        => 'Album test',
        ]);

        Database::insert('photos', ['album_id' => $albumId, 'image' => '/uploads/photos/c.jpg', 'sort_order' => 2]);
        Database::insert('photos', ['album_id' => $albumId, 'image' => '/uploads/photos/a.jpg', 'sort_order' => 0]);
        Database::insert('photos', ['album_id' => $albumId, 'image' => '/uploads/photos/b.jpg', 'sort_order' => 1]);

        $photos = Database::all(
            'SELECT image FROM photos WHERE album_id = ? ORDER BY sort_order ASC, uploaded_at DESC',
            [$albumId]
        );

        $this->assertSame(
            ['/uploads/photos/a.jpg', '/uploads/photos/b.jpg', '/uploads/photos/c.jpg'],
            array_column($photos, 'image'),
            'Les photos doivent être ordonnées par sort_order croissant.'
        );
    }
}
