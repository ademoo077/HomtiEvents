<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\EpicDashboardService;
use App\Helpers\Rbac;
use App\Helpers\Session;

/**
 * Alimentation du calendrier EPIC (JSON).
 */
final class EpicDashboardApi
{
    /**
     * Événements actifs d'une journée précise (clic sur un jour du calendrier).
     */
    public function eventsDuJour(): never
    {
        if (! Session::isLogged()) {
            json_response(['success' => false, 'error' => 'Non authentifié.'], 401);
        }

        $user = Session::user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            json_response(['success' => false, 'error' => 'Accès refusé.'], 403);
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        if ($epicId === 0) {
            json_response(['success' => false, 'error' => 'Aucun EPIC lié.'], 404);
        }

        $date = (string) input('date', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            json_response(['success' => false, 'error' => 'Date invalide.'], 422);
        }

        $events = EpicDashboardService::evenementsPeriode($epicId, $date, $date);

        json_response([
            'success' => true,
            'date'    => $date,
            'count'   => count($events),
            'events'  => array_map(static function (array $e): array {
                return [
                    'id'         => (int) $e['id'],
                    'adresse'    => $e['adresse'] ?? '',
                    'statut'     => $e['statut'] ?? '',
                    'statut_lib' => statut_label((string) ($e['statut'] ?? '')),
                    'date'       => $e['date_evenement'] ?? '',
                    'heure'      => $e['heure'] ?? '',
                    'commune'    => $e['commune_nom'] ?? '',
                    'association'=> $e['association_nom'] ?? '',
                    'motif'      => $e['motif_refus'] ?? '',
                    'url_admin'  => url('wilaya/evenements/' . (int) $e['id']),
                ];
            }, $events),
        ]);
    }
}
