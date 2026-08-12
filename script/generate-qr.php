<?php

declare(strict_types=1);

/**
 * Rétro-génération des QR codes pour les événements déjà PROGRAMMÉs.
 *
 * Usage :
 *   php script/generate-qr.php            # simulation (dry-run)
 *   php script/generate-qr.php --apply     # écrit les PNG + qr_code_path + qr_event
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

\App\Bootstrap::boot();

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;
use App\Helpers\QrCodeService;

$apply = in_array('--apply', $argv, true);

$rows = Database::all(
    "SELECT e.id, e.qr_code_path
     FROM evenements e
     WHERE e.statut IN ('PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE')
       AND e.deleted_at IS NULL
     ORDER BY e.id"
);

$total = count($rows);
$genere = 0;
$existant = 0;

echo ($apply ? '▶' : 'ℹ') . " QR codes : {$total} événement(s) programmé(s)." . ($apply ? ' (écriture)' : ' (dry-run)') . "\n";
echo str_repeat('─', 60) . "\n";

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $aToken = QrCodeGenerator::tokenForEvent($id) !== null;
    $aFile = $row['qr_code_path'] !== null && $row['qr_code_path'] !== '';

    if ($aToken && $aFile) {
        $existant++;
        echo "  #{$id} : déjà généré.\n";
        continue;
    }

    if ($apply) {
        QrCodeService::generate($id, null, null);
    }
    $genere++;
    echo "  #{$id} : " . ($apply ? 'généré' : 'à générer') . ".\n";
}

echo str_repeat('─', 60) . "\n";
echo 'Terminé : ' . ($apply ? $genere . ' généré(s)' : $genere . ' à générer') . ", {$existant} déjà présents.\n";
echo "Passe en écriture avec : php script/generate-qr.php --apply\n";
