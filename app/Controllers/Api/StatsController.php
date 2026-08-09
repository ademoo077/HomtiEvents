<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Database;

final class StatsController
{
    public function global(): never
    {
        json_response([
            'success' => true,
            'data'    => [
                'evenements' => [
                    'total'         => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL'),
                    'aujourdhui'    => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE date_evenement = ? AND deleted_at IS NULL', [date('Y-m-d')]),
                    'cette_semaine' => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE date_evenement BETWEEN ? AND ? AND deleted_at IS NULL', [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))]),
                    'ce_mois'       => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE DATE_FORMAT(date_evenement, '%Y-%m') = ? AND deleted_at IS NULL", [date('Y-m')]),
                    'en_attente'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'EN_ATTENTE' AND deleted_at IS NULL"),
                    'programmes'    => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'PROGRAMME' AND deleted_at IS NULL"),
                    'termines'      => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'TERMINE' AND deleted_at IS NULL"),
                    'en_cours'      => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'PROGRAMME' AND date_evenement = ? AND deleted_at IS NULL", [date('Y-m-d')]),
                    'refuses'       => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'REFUSE' AND deleted_at IS NULL"),
                ],
                'associations'  => [
                    'total'         => (int) Database::value('SELECT COUNT(*) FROM associations'),
                    'validees'      => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1'),
                    'en_attente'    => (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 0'),
                ],
                'epic'            => (int) Database::value('SELECT COUNT(*) FROM epic'),
                'epics_actifs'    => (int) Database::value("SELECT COUNT(DISTINCT epic_id) FROM evenement_epic ee JOIN evenements e ON e.id = ee.evenement_id WHERE e.statut IN ('EN_ATTENTE','PROGRAMME') AND e.deleted_at IS NULL"),
                'participants'    => (int) Database::value('SELECT COUNT(*) FROM evenement_participant'),
                'citoyens'        => (int) Database::value('SELECT COUNT(*) FROM users WHERE role_user = ?', ['citoyen']),
                'citoyens_actifs' => (int) Database::value('SELECT COUNT(DISTINCT user_id) FROM evenement_participant'),
                'participation_moyenne' => Database::value('SELECT ROUND(COUNT(*) * 100.0 / NULLIF((SELECT COUNT(*) FROM users WHERE role_user = ?), 0), 1) FROM evenement_participant', ['citoyen']) ?? 0.0,
                'anomalies_traitees' => (int) Database::value("SELECT COUNT(*) FROM anomalies_evenement ae JOIN evenements e ON e.id = ae.evenement_id WHERE e.statut = 'TERMINE'"),
                'photos'          => (int) Database::value('SELECT COUNT(*) FROM photos'),
                'note_moyenne'    => Database::value('SELECT ROUND(AVG(note), 2) FROM evaluation') ?? null,
            ],
        ]);
    }
}
