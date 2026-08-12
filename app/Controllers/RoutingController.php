<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\RoutingService;

/**
 * Gestion des règles de routage automatique (organisation_rules).
 *
 * Portail minimal : liste paginée + création/édition en ligne (modal) +
 * activation/désactivation + suppression. Toute écriture invalide le cache
 * des règles.
 */
final class RoutingController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): never
    {
        $this->requirePermission('control.rules');

        $q = trim((string) input('q', ''));
        $sql = 'SELECT r.*, a.nom AS anomalie_nom, ca.nom AS ca_nom, ep.nom AS epic_nom
                FROM routing_rules r
                LEFT JOIN anomalies a ON a.id = r.anomalie_id
                LEFT JOIN ca ON ca.id = r.ca_id
                LEFT JOIN epic ep ON ep.id = r.epic_id';
        $params = [];

        if ($q !== '') {
            $sql .= ' WHERE (a.nom LIKE ? OR ep.nom LIKE ? OR ca.nom LIKE ?)';
            $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
        }
        $sql .= ' ORDER BY r.priorite DESC, r.id DESC';

        $result = Database::paginate($sql, $params, self::PER_PAGE, (int) input('page', 1));

        $this->view('admin.routing.index', [
            'rules'     => $result['items'],
            'q'         => $q,
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'anomalies' => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'ca'        => Database::all('SELECT id, nom FROM ca WHERE is_active = 1 ORDER BY nom'),
            'epics'     => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'editing'   => null,
            'errors'    => $this->errors(),
            'success'   => flash('success'),
        ]);
    }

    public function store(): never
    {
        $this->requirePermission('control.rules');

        $data = all_input();
        $data['priorite'] = (int) ($data['priorite'] ?? 0);
        $data['actif']    = (int) (input('actif', 1));
        $data['anomalie_id'] = (int) ($data['anomalie_id'] ?? 0);
        $data['ca_id']       = (int) ($data['ca_id'] ?? 0);

        if ((int) ($data['epic_id'] ?? 0) <= 0) {
            flash('error', 'Sélectionnez une organisation.');
            redirect('admin/routing');
        }

        RoutingService::regleEnregistrer($data);

        flash('success', 'Règle de routage créée.');
        redirect('admin/routing');
    }

    public function edit(string $id): never
    {
        $this->requirePermission('control.rules');

        $rule = Database::one('SELECT * FROM routing_rules WHERE id = ?', [(int) $id]);
        if ($rule === null) {
            abort(404, 'Règle introuvable.');
        }

        $this->renderForm($rule);
    }

    public function update(string $id): never
    {
        $this->requirePermission('control.rules');

        $rule = Database::one('SELECT * FROM routing_rules WHERE id = ?', [(int) $id]);
        if ($rule === null) {
            abort(404, 'Règle introuvable.');
        }

        $data = all_input();
        $data['anomalie_id'] = (int) ($data['anomalie_id'] ?? 0);
        $data['ca_id']       = (int) ($data['ca_id'] ?? 0);
        $data['priorite']    = (int) ($data['priorite'] ?? 0);
        $data['actif']       = (int) (input('actif', 1));

        if ((int) ($data['epic_id'] ?? 0) <= 0) {
            flash('error', 'Sélectionnez une organisation.');
            redirect('admin/routing');
        }

        RoutingService::regleEnregistrer($data, (int) $id);

        flash('success', 'Règle de routage mise à jour.');
        redirect('admin/routing');
    }

    public function toggle(string $id): never
    {
        $this->requirePermission('control.rules');

        $rule = Database::one('SELECT id, actif FROM routing_rules WHERE id = ?', [(int) $id]);
        if ($rule === null) {
            json_response(['success' => false, 'error' => 'Règle introuvable.']);
        }

        RoutingService::regleBasculer((int) $id, ! (int) $rule['actif']);

        json_response(['success' => true]);
    }

    public function delete(string $id): never
    {
        $this->requirePermission('control.rules');

        $rule = Database::one('SELECT id FROM routing_rules WHERE id = ?', [(int) $id]);
        if ($rule === null) {
            abort(404, 'Règle introuvable.');
        }

        RoutingService::regleSupprimer((int) $id);

        flash('success', 'Règle supprimée.');
        redirect('admin/routing');
    }

    private function renderForm(?array $rule = null): never
    {
        $result = Database::paginate(
            'SELECT r.* FROM routing_rules r ORDER BY r.priorite DESC, r.id DESC',
            [],
            self::PER_PAGE,
            (int) input('page', 1)
        );

        $this->view('admin.routing.index', [
            'rules'     => $result['items'],
            'q'         => (string) input('q', ''),
            'page'      => $result['page'],
            'lastPage'  => $result['last_page'],
            'total'     => $result['total'],
            'anomalies' => Database::all('SELECT id, nom FROM anomalies ORDER BY nom'),
            'ca'        => Database::all('SELECT id, nom FROM ca WHERE is_active = 1 ORDER BY nom'),
            'epics'     => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'editing'   => $rule,
            'errors'    => $this->errors(),
            'success'   => flash('success'),
        ]);
    }
}
