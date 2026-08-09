<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\UploadHelper;

final class UploadHelperTest extends DatabaseTestCase
{
    public function testDeleteSupprimeLeFichierSousPublic(): void
    {
        $dir = public_path('uploads/landing');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nom = bin2hex(random_bytes(8)) . '.png';
        $fichier = $dir . '/' . $nom;
        file_put_contents($fichier, 'contenu-test');

        $this->assertFileExists($fichier);

        $supprime = UploadHelper::delete('/uploads/landing/' . $nom);

        $this->assertTrue($supprime, 'delete() doit résoudre le chemin sous public/.');
        $this->assertFileDoesNotExist($fichier);
    }

    public function testDeleteRetourneFalseSiAbsent(): void
    {
        $this->assertFalse(UploadHelper::delete('/uploads/landing/absente-' . bin2hex(random_bytes(4)) . '.png'));
    }
}
