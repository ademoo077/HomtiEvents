<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\StatsService;

/**
 * Détail d'une association côté Wilaya (lecture seule).
 */
final class WilayaAssociationController extends Controller
{
    public function show(string $id): never
    {
        $this->requireAuth();

        $association = Database::one(
            'SELECT a.*,
                    c.nom AS commune_nom,
                    (SELECT COUNT(*) FROM users u WHERE u.association_id = a.id AND u.role_user = ? AND u.is_active = 1) AS membres_actifs,
                    (SELECT COUNT(*) FROM evenements e WHERE e.association_id = a.id AND e.deleted_at IS NULL) AS total_evenements,
                    (SELECT COUNT(*) FROM evenement_participant ep
                     JOIN evenements ev ON ev.id = ep.evenement_id
                     WHERE ev.association_id = a.id) AS total_participants,
                    (SELECT ROUND(AVG(ev2.note), 2) FROM evaluation ev2
                     JOIN evenements ev3 ON ev3.id = ev2.evenement_id
                     WHERE ev3.association_id = a.id) AS note_moyenne
             FROM associations a
             LEFT JOIN commune c ON c.id = a.commune_id
             WHERE a.id = ?',
            ['membre', (int) $id]
        );

        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $score = StatsService::associationScore((int) $id);

        $derniersEvenements = Database::all(
            'SELECT e.id, e.adresse, e.statut, e.date_evenement, e.heure, c.nom AS commune_nom,
                    (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.association_id = ? AND e.deleted_at IS NULL
             ORDER BY e.date_evenement DESC LIMIT 10',
            [(int) $id]
        );

        $membres = Database::all(
            'SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.is_active, u.last_login, u.points,
                    (SELECT COUNT(*) FROM evenement_participant ep
                     JOIN evenements ev ON ev.id = ep.evenement_id
                     WHERE ep.user_id = u.id AND ev.association_id = ?) AS participations
             FROM users u
             WHERE u.association_id = ? AND u.role_user = ? AND u.deleted_at IS NULL
             ORDER BY u.nom, u.prenom',
            [(int) $id, (int) $id, 'membre']
        );

        $this->view('wilaya/associations/show', [
            'association'       => $association,
            'score'             => $score,
            'derniersEvenements'=> $derniersEvenements,
            'membres'           => $membres,
        ]);
    }
}
