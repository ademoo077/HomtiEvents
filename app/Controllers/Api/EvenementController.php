<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;

final class EvenementController
{
    public function index(): never
    {
        $statut = input('statut', 'PROGRAMME');
        $limit = min(100, max(1, (int) input('limit', 50)));

        $sql = 'SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure,
                       e.informations_complementaires, c.nom AS commune, c.latitude, c.longitude,
                       a.nom AS association
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                WHERE e.statut = ?
                ORDER BY e.date_evenement ASC
                LIMIT ' . $limit;
        $params = [$statut];

        $q = trim((string) input('q', ''));
        if ($q !== '') {
            $sql = str_replace('WHERE e.statut = ?', 'WHERE e.statut = ? AND (e.adresse LIKE ? OR e.description LIKE ?)', $sql);
            $like = '%' . $q . '%';
            array_unshift($params, $like, $like);
        }

        json_response([
            'success' => true,
            'count'   => count($rows = Database::all($sql, $params)),
            'data'    => $rows,
        ]);
    }

    public function show(string $id): never
    {
        $event = Database::one(
            'SELECT e.*, c.nom AS commune, c.latitude, c.longitude, a.nom AS association,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants,
                    (SELECT GROUP_CONCAT(an.nom SEPARATOR ", ") FROM anomalies_evenement ae
                     JOIN anomalies an ON an.id = ae.anomalie_id WHERE ae.evenement_id = e.id) AS anomalies
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.id = ?',
            [(int) $id]
        );

        if ($event === null) {
            json_response(['success' => false, 'message' => 'Événement introuvable'], 404);
        }

        json_response(['success' => true, 'data' => $event]);
    }

    public function nearby(): never
    {
        $lat = input('lat');
        $lon = input('lon');
        $rayon = min(100, max(1, (int) input('rayon', 20)));

        if ($lat === null || $lon === null) {
            json_response(['success' => false, 'message' => 'Paramètres lat/lon requis.'], 400);
        }

        $events = \App\Helpers\GeoHelper::evenementsProches(
            (float) $lat,
            (float) $lon,
            $rayon,
            'PROGRAMME',
            50
        );

        json_response([
            'success' => true,
            'count'   => count($events),
            'data'    => $events,
        ]);
    }
}
