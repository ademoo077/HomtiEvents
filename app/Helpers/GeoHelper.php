<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Géographie : distance Haversine, recherche de proximité.
 */
final class GeoHelper
{
    private const EARTH_RADIUS_KM = 6371.0;

    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Événements triés par proximité depuis (lat, lon).
     */
    public static function evenementsProches(float $lat, float $lon, int $rayonKm = 20, string $statut = 'PROGRAMME', int $limit = 50): array
    {
        return Database::all(
            'SELECT e.*, c.nom AS commune_nom, c.latitude, c.longitude,
                    (6371 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(? - c.latitude) / 2), 2)
                        + COS(RADIANS(?)) * COS(RADIANS(c.latitude)) * POWER(SIN(RADIANS(? - c.longitude) / 2), 2)))) AS distance_km
             FROM evenements e
             JOIN commune c ON c.id = e.commune_id
             WHERE e.statut = ? AND c.latitude IS NOT NULL
             HAVING distance_km <= ?
             ORDER BY distance_km ASC
             LIMIT ' . (int) $limit,
            [$lat, $lat, $lon, $statut, $rayonKm]
        );
    }

    /**
     * Marqueurs pour la carte Leaflet.
     */
    public static function markers(array $events): array
    {
        return array_map(static function (array $e) {
            return [
                'id'       => (int) $e['id'],
                'lat'      => (float) ($e['latitude'] ?? 0),
                'lng'      => (float) ($e['longitude'] ?? 0),
                'nom'      => $e['adresse'],
                'commune'  => $e['commune_nom'] ?? '',
                'statut'   => $e['statut'],
                'date'     => $e['date_evenement'],
                'url'      => url('/'),
            ];
        }, $events);
    }

    public static function distanceLabel(float $km): string
    {
        if ($km < 1) {
            return sprintf('%d m', (int) round($km * 1000));
        }

        return sprintf('%.1f km', $km);
    }
}
