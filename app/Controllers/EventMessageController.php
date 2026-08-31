<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;

class EventMessageController extends Controller
{
    /**
     * Liste les messages d'un événement (GET /api/events/{id}/messages).
     */
    public function index(string $eventId): void
    {
        $eventId = (int) $eventId;
        $event = Database::one(
            'SELECT id, association_id FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$eventId]
        );

        if ($event === null) {
            json_response(['success' => false, 'error' => 'Événement introuvable.'], 404);
            return;
        }

        $this->assertAccess($eventId, (int) ($event['association_id'] ?? 0));

        $isWilaya = user_role() === 'wilaya' ? 1 : 0;
        $messages = Database::all(
            'SELECT em.*, u.nom AS sender_nom, u.prenom AS sender_prenom
             FROM event_messages em
             LEFT JOIN users u ON u.id = em.sender_id
             WHERE em.evenement_id = ?
               AND (em.is_internal = 0 OR ? = 1)
             ORDER BY em.created_at ASC',
            [$eventId, $isWilaya]
        );

        json_response(['success' => true, 'messages' => $messages]);
    }

    /**
     * Envoie un message (POST /api/events/{id}/messages).
     */
    public function store(string $eventId): void
    {
        $eventId = (int) $eventId;
        $event = Database::one(
            'SELECT id, association_id FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$eventId]
        );

        if ($event === null) {
            json_response(['success' => false, 'error' => 'Événement introuvable.'], 404);
            return;
        }

        $this->assertAccess($eventId, (int) ($event['association_id'] ?? 0));

        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '') {
            json_response(['success' => false, 'error' => 'Message vide.'], 422);
            return;
        }

        $role = user_role();
        $isInternal = $role === 'wilaya' && !empty($_POST['is_internal']);

        $senderRole = match (true) {
            $role === 'wilaya'      => 'wilaya',
            $role === 'association' => 'association',
            $role === 'epic'        => 'epic',
            default                 => 'unknown',
        };

        $msgId = Database::insert('event_messages', [
            'evenement_id' => $eventId,
            'sender_id'    => Session::userId(),
            'sender_role'  => $senderRole,
            'message'      => $message,
            'is_internal'  => $isInternal ? 1 : 0,
        ]);

        // Notify the other side
        if ($senderRole === 'wilaya' && (int) $event['association_id'] > 0 && !$isInternal) {
            \App\Helpers\Notification::sendToAssociation(
                (int) $event['association_id'],
                'Nouveau message — Événement #' . $eventId,
                mb_strimwidth($message, 0, 120, '…'),
                'evenement_create',
                ['evenement_id' => $eventId]
            );
        } elseif ($senderRole === 'association') {
            \App\Helpers\Notification::sendToRole(
                'wilaya',
                'Message association — Événement #' . $eventId,
                mb_strimwidth($message, 0, 120, '…'),
                'evenement_create',
                ['evenement_id' => $eventId]
            );
        }

        json_response(['success' => true, 'id' => $msgId]);
    }

    private function assertAccess(int $eventId, int $associationId): void
    {
        $role = user_role();
        if ($role === 'wilaya') {
            return;
        }
        $user = current_user();
        if ($role === 'association' && (int) ($user['association_id'] ?? 0) === $associationId) {
            return;
        }
        if ($role === 'epic') {
            return;
        }
        json_response(['success' => false, 'error' => 'Accès refusé.'], 403);
        exit;
    }
}
