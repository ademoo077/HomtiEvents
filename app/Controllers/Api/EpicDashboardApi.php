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
                return self::eventToArray($e);
            }, $events),
        ]);
    }

    /**
     * Données d'un mois entier (indexées par jour) pour le calendrier dynamique
     * (navigation sans rechargement).
     */
    public function calendarMois(): never
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

        $mois = (string) input('mois', date('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $mois) !== 1) {
            json_response(['success' => false, 'error' => 'Mois invalide.'], 422);
        }

        $filters = [];
        $communeId = (int) input('commune_id', 0);
        if ($communeId > 0) {
            $filters['commune_id'] = $communeId;
        }

        $parJour = EpicDashboardService::evenementsParJour($epicId, $mois, $filters);

        $annee   = (int) substr($mois, 0, 4);
        $moisNum = (int) substr($mois, 5, 2);
        $nbJours = (int) date('t', mktime(0, 0, 0, $moisNum, 1, $annee));
        $nSemaine = (int) date('N', mktime(0, 0, 0, $moisNum, 1, $annee));
        $isRtl   = \App\Helpers\I18n::direction() === 'rtl';
        $decalage = $isRtl ? ($nSemaine + 1) % 7 : ($nSemaine - 1) % 7;

        $jours = [];
        foreach ($parJour as $date => $events) {
            $jours[$date] = array_map(static fn (array $e): array => self::eventToArray($e), $events);
        }

        $total    = 0;
        $parStatut = [];
        foreach ($parJour as $events) {
            $total += count($events);
            foreach ($events as $e) {
                $s = (string) ($e['statut'] ?? 'autre');
                $parStatut[$s] = ($parStatut[$s] ?? 0) + 1;
            }
        }

        json_response([
            'success'   => true,
            'mois'      => $mois,
            'annee'     => $annee,
            'moisNum'   => $moisNum,
            'nbJours'   => $nbJours,
            'decalage'  => $decalage,
            'total'     => $total,
            'parStatut' => $parStatut,
            'jours'     => $jours,
        ]);
    }

    /**
     * Normalise un événement en tableau JSON destiné au calendrier / modal.
     *
     * @param array<string, mixed> $e
     * @return array<string, mixed>
     */
    private static function eventToArray(array $e): array
    {
        return [
            'id'          => (int) $e['id'],
            'adresse'     => $e['adresse'] ?? '',
            'statut'      => $e['statut'] ?? '',
            'statut_lib'  => statut_label((string) ($e['statut'] ?? '')),
            'date'        => $e['date_evenement'] ?? '',
            'heure'       => $e['heure'] ?? '',
            'commune'     => $e['commune_nom'] ?? '',
            'association' => $e['association_nom'] ?? '',
            'motif'       => $e['motif_refus'] ?? '',
            'url_admin'   => url('wilaya/evenements/' . (int) $e['id']),
            'url_epic'    => url('epic/' . (int) $e['id']),
            'url_qr'      => ! empty($e['token_qr']) ? url('event/qr/download/' . (int) $e['id']) : '',
            'token_qr'    => $e['token_qr'] ?? '',
        ];
    }
}
