<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Rbac;

/**
 * Présence « en temps réel » pendant l'événement — côté association.
 *
 * Page (polling) + endpoint JSON protégé par le contrôle d'appartenance :
 * l'association connectée ne voit que ses propres événements.
 */
final class AssociationPresenceController extends Controller
{
    private function eventForAssociation(int $eventId): array
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association rattachée.');
        }

        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.id = ? AND e.association_id = ? AND e.deleted_at IS NULL',
            [$eventId, $associationId]
        );

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        return $event;
    }

    /**
     * Données de présence (JSON) pour le polling.
     */
    public function presenceJson(string $eventId): never
    {
        $event = $this->eventForAssociation((int) $eventId);

        $participants = Database::all(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, ep.heure_scan
             FROM evenement_participant ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.evenement_id = ?
             ORDER BY ep.heure_scan ASC',
            [(int) $eventId]
        );

        $invitees = \App\Helpers\QrCodeGenerator::inviteesPourEvenement((int) $eventId);

        json_response([
            'success'        => true,
            'evenement_id'   => (int) $event['id'],
            'statut'         => (string) $event['statut'],
            'adresse'        => (string) ($event['adresse'] ?? ''),
            'date_evenement' => (string) ($event['date_evenement'] ?? ''),
            'capacite'       => ! empty($event['capacite']) ? (int) $event['capacite'] : null,
            'count'          => count($participants),
            'participants'   => $participants,
            'invitees'       => $invitees,
            'invitees_count' => count($invitees),
        ]);
    }

    /**
     * Page de suivi en direct (rafraîchie toutes les 10 s).
     */
    public function presence(string $eventId): never
    {
        $event = $this->eventForAssociation((int) $eventId);

        $this->view('association/presence', [
            'event' => $event,
        ], 'association');
    }
}
