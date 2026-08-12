<?php

declare(strict_types=1);

/**
 * Réaffectation de tous les événements historiques selon les règles de routage.
 *
 * Usage :
 *   php script/reroute-all.php            # simulation (dry-run)
 *   php script/reroute-all.php --apply    # exécution réelle avec journalisation
 *
 * Parcourt tous les événements non supprimés, résout la règle et met à jour
 * assigned_org_id (journalisation + notification) uniquement si l'organisation
 * diffère de l'actuelle. Respecte l'API existante RoutingService::assignOrganization.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/Bootstrap.php';

\App\Bootstrap::boot();

use App\Helpers\Database;
use App\Helpers\RoutingService;

$apply = in_array('--apply', $argv, true);

$pdo = Database::connection();
$pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

$rows = Database::all(
    'SELECT e.id, e.assigned_org_id, e.commune_id FROM evenements e
     WHERE e.deleted_at IS NULL ORDER BY e.id'
);

$total = count($rows);
$change = 0;
$noChange = 0;
$unassigned = 0;

echo ($apply ? '▶' : 'ℹ') . " Routage de {$total} événement(s)" . ($apply ? ' (écriture activée)' : ' (dry-run)') . ".\n";
echo str_repeat('─', 60) . "\n";

foreach ($rows as $row) {
    $eventId = (int) $row['id'];
    $event = Database::one(
        'SELECT e.*, c.ca_id FROM evenements e LEFT JOIN commune c ON c.id = e.commune_id WHERE e.id = ?',
        [$eventId]
    );

    if ($event === null) {
        echo "  #{$eventId} : ignoré (introuvable).\n";
        continue;
    }

    $resolution = RoutingService::resoudre($event);
    $newOrg = $resolution['epic_id'];
    $oldOrg = (int) ($event['assigned_org_id'] ?? 0);

    if ($oldOrg === (int) ($newOrg ?? 0)) {
        $noChange++;
        echo "  #{$eventId} : inchangé (org #{$oldOrg} / règle {$resolution['rule_matched']}).\n";
        continue;
    }

    if ($newOrg === null) {
        $unassigned++;
    }

    if ($apply) {
        $result = RoutingService::assignOrganization($eventId);
        echo "  #{$eventId} : " . ($result['change'] ? 'mis à jour' : 'identique')
            . " → org " . ($newOrg ? (string) $newOrg : 'NULL')
            . " (règle : {$resolution['rule_matched']}).\n";
        if ($result['change']) {
            $change++;
        } else {
            $noChange++;
        }
    } else {
        $change++;
        echo "  #{$eventId} : #{$oldOrg} → " . ($newOrg ? (string) $newOrg : 'NULL')
            . " (règle : {$resolution['rule_matched']}).\n";
    }
}

echo str_repeat('─', 60) . "\n";
echo ($apply ? '✔' : 'ℹ') . " Terminé : {$change} modification(s), {$noChange} inchangé(s), {$unassigned} non assigné(s).\n";
echo "Passe en écriture avec : php script/reroute-all.php --apply\n";
