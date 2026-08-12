<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\Rbac;
use App\Helpers\RoutingService;
use App\Helpers\Session;

/**
 * Endpoint de diagnostic du routage (développement).
 *
 * GET api/routing/debug?event_id=X
 * → renvoie la résolution de règle pour l'événement, sans aucun effet de
 *   bord (lecture). Nécessite le rôle wilaya.
 */
final class RoutingDebugApi
{
    public function debug(): never
    {
        if (! Session::isLogged() || ! Rbac::hasPermission('evenement.view_all')) {
            json_response(['success' => false, 'error' => 'Accès refusé.'], 403);
        }

        $eventId = (int) input('event_id', 0);
        if ($eventId <= 0) {
            json_response(['success' => false, 'error' => 'Paramètre event_id requis.'], 422);
        }

        $event = Database::one('SELECT e.*, c.ca_id FROM evenements e LEFT JOIN commune c ON c.id = e.commune_id WHERE e.id = ?', [$eventId]);
        if ($event === null) {
            json_response(['success' => false, 'error' => 'Événement introuvable.'], 404);
        }

        $anomalieIds = array_map(
            static fn (array $r): int => (int) $r['anomalie_id'],
            Database::all('SELECT anomalie_id FROM anomalies_evenement WHERE evenement_id = ?', [$eventId])
        );
        $anomalyNames = $anomalieIds === []
            ? []
            : array_column(Database::all('SELECT id, nom FROM anomalies WHERE id IN (' . implode(',', $anomalieIds) . ')'), 'nom', 'id');

        $resolution = RoutingService::resoudre($event);

        $rules = array_values(array_filter(RoutingService::regles(), static fn (array $r): bool => (int) $r['anomalie_id'] > 0));

        json_response([
            'success'      => true,
            'event_id'     => $eventId,
            'anomalies'    => array_map(static fn (int $id): array => ['id' => $id, 'nom' => $anomalyNames[$id] ?? '—'], $anomalieIds),
            'ca_id'        => $event['ca_id'] ?? null,
            'assigned_org_id' => (int) ($event['assigned_org_id'] ?? 0),
            'resolution'   => $resolution,
            'rules'        => $rules,
        ]);
    }
}
