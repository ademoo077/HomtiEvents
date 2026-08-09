<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Rbac;

/**
 * Espace EPIC — suivi des interventions et événements assignés.
 */
final class EpicController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        if ($epicId === 0) {
            flash('error', 'Aucun EPIC lié à votre compte.');
            redirect(dashboard_path());
        }

        $epic = Database::one('SELECT * FROM epic WHERE id = ?', [$epicId]);

        $filters = [
            'q'      => trim((string) input('q', '')),
            'statut' => input('statut'),
        ];

        $where = ['ee.epic_id = ?'];
        $params = [$epicId];

        if (! empty($filters['q'])) {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ?)';
            $like = '%' . trim($filters['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT ee.*, e.adresse AS evenement_adresse, e.date_evenement AS evenement_date,
                       e.statut AS evenement_statut, c.nom AS commune_nom, a.nom AS association_nom
                FROM evenement_epic ee
                JOIN evenements e ON e.id = ee.evenement_id
                LEFT JOIN commune c ON c.id = e.commune_id
                LEFT JOIN associations a ON a.id = e.association_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ee.date_affectation DESC';

        $result = Database::paginate($sql, $params, self::PER_PAGE, (int) input('page', 1));

        $this->view('epic/index', [
            'epic'        => $epic,
            'interventions' => $result['items'],
            'filters'     => $filters,
            'page'        => $result['page'],
            'lastPage'    => $result['last_page'],
            'total'       => $result['total'],
        ]);
    }

    public function show(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);

        $intervention = Database::one(
            'SELECT ee.*, ee.evenement_id AS id, e.adresse AS evenement_adresse, e.description,
                    e.statut AS evenement_statut, e.date_evenement, e.heure,
                    c.nom AS commune_nom, a.nom AS association_nom, q.token_qr
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE ee.evenement_id = ? AND ee.epic_id = ?',
            [(int) $id, $epicId]
        );

        if ($intervention === null) {
            abort(404, 'Intervention introuvable.');
        }

        $participants = Database::all(
            'SELECT p.*, u.nom, u.prenom FROM evenement_participant p
             JOIN users u ON u.id = p.user_id
             WHERE p.evenement_id = ?
             ORDER BY p.heure_scan DESC',
            [(int) $intervention['evenement_id']]
        );

        $this->view('epic/show', [
            'intervention'  => $intervention,
            'participants'  => $participants,
        ]);
    }

    public function updateStatut(string $id): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        $nouveauStatut = (string) input('statut', '');

        $validStatuts = ['AFFECTE', 'EN_COURS', 'TERMINE', 'ANOMALIE'];
        if (! in_array($nouveauStatut, $validStatuts, true)) {
            json_response(['success' => false, 'error' => 'Statut invalide.']);
        }

        $intervention = Database::one(
            'SELECT id FROM evenement_epic WHERE id = ? AND epic_id = ?',
            [(int) $id, $epicId]
        );

        if ($intervention === null) {
            json_response(['success' => false, 'error' => 'Intervention introuvable.']);
        }

        Database::run(
            'UPDATE evenement_epic SET statut = ? WHERE id = ?',
            [$nouveauStatut, (int) $id]
        );

        json_response(['success' => true, 'message' => 'Statut mis à jour.']);
    }
}
