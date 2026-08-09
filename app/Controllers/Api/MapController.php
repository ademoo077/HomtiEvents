<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\GeoHelper;

final class MapController
{
    public function index(): never
    {
        $events = Database::all(
            'SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure,
                    c.nom AS commune_nom, c.latitude, c.longitude
             FROM evenements e
             JOIN commune c ON c.id = e.commune_id
             WHERE e.statut IN (?, ?) AND c.latitude IS NOT NULL
             ORDER BY e.date_evenement ASC',
            ['PROGRAMME', 'TERMINE']
        );

        json_response([
            'success' => true,
            'count'   => count($events),
            'markers' => GeoHelper::markers($events),
        ]);
    }
}
