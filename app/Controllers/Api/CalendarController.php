<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;

final class CalendarController
{
    /**
     * Carte interactive : marqueurs colorés par statut + popup détaillée.
     */
    public function carto(): never
    {
        $statuts = input('statuts');
        $communeId = (int) input('commune_id', 0);
        $anomalieId = (int) input('anomalie_id', 0);

        $sql = "SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure,
                       c.nom AS commune_nom, c.latitude, c.longitude,
                       a.nom AS association_nom,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants,
                       (SELECT COUNT(*) FROM anomalies_evenement ae WHERE ae.evenement_id = e.id) AS nb_anomalies
                FROM evenements e
                JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                WHERE e.deleted_at IS NULL AND c.latitude IS NOT NULL";

        $params = [];

        if (! empty($statuts)) {
            $statutsArr = is_array($statuts) ? $statuts : [$statuts];
            $placeholders = implode(',', array_fill(0, count($statutsArr), '?'));
            $sql .= ' AND e.statut IN (' . $placeholders . ')';
            $params = array_merge($params, $statutsArr);
        }

        if ($communeId > 0) {
            $sql .= ' AND e.commune_id = ?';
            $params[] = $communeId;
        }

        if ($anomalieId > 0) {
            $sql .= ' AND e.id IN (SELECT evenement_id FROM anomalies_evenement WHERE anomalie_id = ?)';
            $params[] = $anomalieId;
        }

        $sql .= ' ORDER BY e.date_evenement ASC LIMIT 500';

        $events = Database::all($sql, $params);

        $statutsColors = [
            'EN_ATTENTE'            => '#f59e0b',
            'MODIFICATION_DEMANDEE' => '#f59e0b',
            'VALIDÉ'                => '#3b82f6',
            'PROGRAMME'             => '#6366f1',
            'QR_GENERE'             => '#a78bfa',
            'EN_COURS'              => '#06b6d4',
            'TERMINE'               => '#22c55e',
            'REFUSE'                => '#ef4444',
        ];

        $markers = array_map(static function (array $e) use ($statutsColors): array {
            $statut = $e['statut'] ?? 'EN_ATTENTE';
            $lat = (float) ($e['latitude'] ?? 0);
            $lng = (float) ($e['longitude'] ?? 0);

            return [
                'id'         => (int) $e['id'],
                'lat'        => $lat,
                'lng'        => $lng,
                'adresse'    => $e['adresse'] ?? '',
                'commune'    => $e['commune_nom'] ?? '',
                'statut'     => $statut,
                'color'      => $statutsColors[$statut] ?? '#6366f1',
                'date'       => $e['date_evenement'] ?? '',
                'heure'      => $e['heure'] ?? '',
                'participants' => (int) ($e['participants'] ?? 0),
                'nb_anomalies' => (int) ($e['nb_anomalies'] ?? 0),
                'association'  => $e['association_nom'] ?? '',
                'url'        => url('wilaya/evenements/' . $e['id']),
            ];
        }, $events);

        json_response([
            'success' => true,
            'count'   => count($markers),
            'markers' => $markers,
        ]);
    }

    /**
     * Moteur de recherche avancée — résultats paginés.
     */
    public function search(): never
    {
        $q = trim((string) input('q', ''));
        $statut = input('statut');
        $communeId = (int) input('commune_id', 0);
        $associationId = (int) input('association_id', 0);
        $epicId = (int) input('epic_id', 0);
        $du = input('du');
        $au = input('au');
        $caId = (int) input('ca_id', 0);
        $anomalieId = (int) input('anomalie_id', 0);
        $president = input('president');
        $agrement = input('agrement');

        $sql = "SELECT DISTINCT e.id, e.adresse, e.description, e.statut, e.date_evenement, e.heure, e.created_at,
                       c.nom AS commune_nom, c.ca_id,
                       a.nom AS association_nom, a.numero_agrement, a.nom_prenom_president,
                       COUNT(ep.user_id) AS participants,
                       (SELECT COUNT(*) FROM anomalies_evenement ae WHERE ae.evenement_id = e.id) AS nb_anomalies,
                       (SELECT GROUP_CONCAT(epp.nom SEPARATOR ', ')
                        FROM epic epp JOIN evenement_epic ee ON ee.epic_id = epp.id
                        WHERE ee.evenement_id = e.id) AS epics_noms
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                LEFT JOIN evenement_participant ep ON ep.evenement_id = e.id
                WHERE e.deleted_at IS NULL";

        $params = [];

        if ($q !== '') {
            $sql .= ' AND (e.adresse LIKE ? OR e.description LIKE ? OR a.nom LIKE ? OR c.nom LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if (! empty($statut)) {
            $sql .= ' AND e.statut = ?';
            $params[] = $statut;
        }

        if ($communeId > 0) {
            $sql .= ' AND e.commune_id = ?';
            $params[] = $communeId;
        }

        if ($associationId > 0) {
            $sql .= ' AND e.association_id = ?';
            $params[] = $associationId;
        }

        if ($epicId > 0) {
            $sql .= ' AND e.id IN (SELECT evenement_id FROM evenement_epic WHERE epic_id = ?)';
            $params[] = $epicId;
        }

        if ($anomalieId > 0) {
            $sql .= ' AND e.id IN (SELECT evenement_id FROM anomalies_evenement WHERE anomalie_id = ?)';
            $params[] = $anomalieId;
        }

        if (! empty($du)) {
            $sql .= ' AND e.date_evenement >= ?';
            $params[] = $du;
        }

        if (! empty($au)) {
            $sql .= ' AND e.date_evenement <= ?';
            $params[] = $au;
        }

        if ($caId > 0) {
            $sql .= ' AND c.ca_id = ?';
            $params[] = $caId;
        }

        if ($president !== '') {
            $sql .= ' AND a.nom_prenom_president LIKE ?';
            $params[] = '%' . $president . '%';
        }

        if ($agrement !== '') {
            $sql .= ' AND a.numero_agrement LIKE ?';
            $params[] = '%' . $agrement . '%';
        }

        $sql .= ' GROUP BY e.id ORDER BY e.created_at DESC';

        $perPage = (int) input('per_page', 20);
        $page = (int) input('page', 1);
        $result = Database::paginate($sql, $params, $perPage, $page);

        json_response([
            'success'  => true,
            'total'    => $result['total'],
            'page'     => $result['page'],
            'lastPage' => $result['last_page'],
            'perPage'  => $result['per_page'],
            'data'     => $result['items'],
        ]);
    }
}
