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

        $where = ['e.statut = ?', 'e.deleted_at IS NULL'];
        $params = [$statut];

        $q = trim((string) input('q', ''));
        if ($q !== '') {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure,
                       e.informations_complementaires, c.nom AS commune, c.latitude, c.longitude,
                       a.nom AS association,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.date_evenement ASC
                LIMIT ' . $limit;

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
             WHERE e.id = ? AND e.deleted_at IS NULL',
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
            \App\Helpers\EvenementService::STATUTS_A_VENIR,
            50
        );

        json_response([
            'success' => true,
            'count'   => count($events),
            'data'    => $events,
        ]);
    }

    /**
     * Données temps réel pour la carte de suivi EN_COURS (page /wilaya/suivi).
     *
     * Retourne les événements en cours enrichis (capacité, places, taux de
     * remplissage), des indicateurs globaux et le fil des derniers scans.
     */
    public function suivi(): never
    {
        $events = Database::all(
            "SELECT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure,
                    e.informations_complementaires, e.capacite,
                    c.nom AS commune, c.latitude, c.longitude,
                    a.nom AS association,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.statut = 'EN_COURS' AND e.deleted_at IS NULL
             ORDER BY e.date_evenement ASC, e.heure ASC"
        );

        $totalParticipants = 0;
        foreach ($events as &$ev) {
            $p = (int) ($ev['participants'] ?? 0);
            $totalParticipants += $p;
            $cap = ($ev['capacite'] ?? null) !== null ? (int) $ev['capacite'] : null;
            $ev['participants'] = $p;
            $ev['places_restantes'] = $cap !== null ? max(0, $cap - $p) : null;
            $ev['taux_remplissage'] = $cap !== null ? min(100, (int) round($p / max(1, $cap) * 100)) : null;
        }
        unset($ev);

        $recentScans = Database::all(
            'SELECT ep.heure_scan, e.adresse, e.id AS evenement_id, u.nom, u.prenom
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id AND e.deleted_at IS NULL
             LEFT JOIN users u ON u.id = ep.user_id
             ORDER BY ep.heure_scan DESC LIMIT 12'
        );

        $scansHeure = (int) Database::value(
            'SELECT COUNT(*) FROM evenement_participant
             WHERE heure_scan >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );

        // ── Anomalies ouvertes liées aux événements EN_COURS (pour la carte) ──
        $anomalies = Database::all(
            "SELECT ae.anomalie_id, ae.evenement_id, ae.latitude, ae.longitude,
                    ae.titre, ae.priorite, ae.statut,
                    a.nom AS anomalie_nom,
                    e.adresse AS evenement_adresse, e.id AS evenement_id_suivi,
                    e.date_evenement, e.heure,
                    c.nom AS commune
             FROM anomalies_evenement ae
             JOIN evenements e ON e.id = ae.evenement_id AND e.deleted_at IS NULL AND e.statut = 'EN_COURS'
             JOIN anomalies a ON a.id = ae.anomalie_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ae.latitude IS NOT NULL AND ae.longitude IS NOT NULL
               AND (
                     ae.statut IS NULL
                     OR UPPER(TRIM(ae.statut)) NOT IN ('RESOLUE', 'CLOTUREE', 'TERMINE', 'REJETEE')
                   )
             ORDER BY FIELD(UPPER(COALESCE(ae.priorite,'moyenne')), 'CRITIQUE','HAUTE','MOYENNE','BASSE'), ae.anomalie_id"
        );

        json_response([
            'success'          => true,
            'timestamp'        => date('Y-m-d H:i:s'),
            'events'           => $events,
            'anomalies'        => $anomalies,
            'kpis'             => [
                'en_cours'      => count($events),
                'participants'  => $totalParticipants,
                'scans_heure'   => $scansHeure,
                'taux_moyen'    => count($events) > 0
                    ? (int) round(array_sum(array_column($events, 'taux_remplissage')) / count($events))
                    : null,
            ],
            'recent_scans'     => $recentScans,
        ]);
    }
}
