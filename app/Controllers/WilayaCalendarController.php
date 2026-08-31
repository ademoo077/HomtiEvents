<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\I18n;

/**
 * Calendrier FullCalendar pour l'espace Wilaya.
 * Vue globale de tous les événements organisés par association.
 */
final class WilayaCalendarController extends Controller
{
    public function index(): never
    {
        $this->requireAuth();

        $associations = Database::all(
            'SELECT id, nom FROM associations ORDER BY nom'
        );

        $this->view('wilaya/calendrier', [
            'associations' => $associations,
        ]);
    }

    /**
     * API JSON pour FullCalendar — retourne tous les événements formatés.
     */
    public function events(): never
    {
        $this->requireAuth();

        $start = (string) input('start', '');
        $end   = (string) input('end', '');
        $assoc = input('association_id');

        $sql = '                SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure, e.capacite,
                       e.description, e.association_id,
                       a.nom AS association_nom,
                       c.nom AS commune_nom,
                       (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
                FROM evenements e
                LEFT JOIN associations a ON a.id = e.association_id
                LEFT JOIN commune c ON c.id = e.commune_id
                WHERE e.deleted_at IS NULL';
        $params = [];

        if ($start !== '') {
            $sql .= ' AND e.date_evenement >= ?';
            $params[] = $start;
        }
        if ($end !== '') {
            $sql .= ' AND e.date_evenement <= ?';
            $params[] = $end;
        }
        if ($assoc !== null && $assoc !== '') {
            $sql .= ' AND e.association_id = ?';
            $params[] = (int) $assoc;
        }

        $sql .= ' ORDER BY e.date_evenement ASC, e.heure ASC';

        $rows = Database::all($sql, $params);

        $events = array_map(static function (array $r): array {
            $dateEvenement = (string) ($r['date_evenement'] ?? '');
            $heure = substr((string) ($r['heure'] ?? '00:00'), 0, 5);
            $start = $dateEvenement !== '' ? $dateEvenement . ($heure !== '' ? 'T' . $heure : 'T09:00') : null;

            $statut = (string) ($r['statut'] ?? 'EN_ATTENTE');
            $color = match ($statut) {
                'EN_ATTENTE'            => '#f59e0b',
                'MODIFICATION_DEMANDEE' => '#f97316',
                'VALIDÉ'                => '#3b82f6',
                'PROGRAMME'             => '#06b6d4',
                'QR_GENERE'             => '#8b5cf6',
                'EN_COURS'              => '#2563eb',
                'TERMINE'               => '#10b981',
                'REFUSE'                => '#ef4444',
                'ANNULE'                => '#6b7280',
                default                 => '#6b7280',
            };

            $title = ($r['adresse'] ?: 'Événement')
                . ' — ' . ($r['association_nom'] ?? 'N/A')
                . ' (' . ($r['commune_nom'] ?? '') . ')';

            $participants = (int) ($r['participants'] ?? 0);
            $capacite = (int) ($r['capacite'] ?? 0);

            return [
                'id'       => (int) $r['id'],
                'title'    => $title,
                'start'    => $start,
                'color'    => $color,
                'borderColor' => $color,
                'textColor'   => '#fff',
                'classNames'  => ['wh-cal-event'],
                'extendedProps' => [
                    'statut'           => $statut,
                    'association_nom'  => $r['association_nom'] ?? '',
                    'commune_nom'      => $r['commune_nom'] ?? '',
                    'participants'     => $participants,
                    'capacite'         => $capacite,
                    'description'      => mb_substr((string) ($r['description'] ?? ''), 0, 200),
                    'association_id'   => (int) ($r['association_id'] ?? 0),
                ],
            ];
        }, $rows);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
