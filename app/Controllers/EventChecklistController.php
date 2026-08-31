<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;

class EventChecklistController extends Controller
{
    /**
     * GET /api/events/{id}/checklist
     */
    public function index(string $eventId): void
    {
        $eventId = (int) $eventId;
        $items = Database::all(
            'SELECT ec.*, u.nom AS fait_by_nom, u.prenom AS fait_by_prenom
             FROM event_checklist ec
             LEFT JOIN users u ON u.id = ec.fait_by
             WHERE ec.evenement_id = ?
             ORDER BY ec.fait ASC, ec.id ASC',
            [$eventId]
        );

        json_response(['success' => true, 'items' => $items]);
    }

    /**
     * POST /api/events/{id}/checklist/toggle
     * Body: { item_id: int }
     */
    public function toggle(string $eventId): void
    {
        $eventId = (int) $eventId;
        $itemId = (int) ($_POST['item_id'] ?? 0);

        if ($itemId <= 0) {
            json_response(['success' => false, 'error' => 'item_id requis.'], 422);
            return;
        }

        $item = Database::one(
            'SELECT id, fait FROM event_checklist WHERE id = ? AND evenement_id = ?',
            [$itemId, $eventId]
        );

        if ($item === null) {
            json_response(['success' => false, 'error' => 'Élément introuvable.'], 404);
            return;
        }

        $newState = empty($item['fait']) ? 1 : 0;
        Database::update('event_checklist', [
            'fait'    => $newState,
            'fait_by' => $newState ? Session::userId() : null,
            'fait_at' => $newState ? date('Y-m-d H:i:s') : null,
        ], 'id = ?', [$itemId]);

        json_response(['success' => true, 'fait' => $newState]);
    }

    /**
     * POST /api/events/{id}/checklist/add
     * Body: { libelle: string }
     */
    public function add(string $eventId): void
    {
        $libelle = trim((string) ($_POST['libelle'] ?? ''));
        if ($libelle === '') {
            json_response(['success' => false, 'error' => 'Libellé requis.'], 422);
            return;
        }

        $id = Database::insert('event_checklist', [
            'evenement_id' => (int) $eventId,
            'libelle'      => $libelle,
        ]);

        json_response(['success' => true, 'id' => $id]);
    }

    /**
     * DELETE /api/events/checklist/{itemId}
     */
    public function delete(string $itemId): void
    {
        Database::delete('event_checklist', 'id = ?', [(int) $itemId]);
        json_response(['success' => true]);
    }
}
