<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Rbac;

/**
 * Dashboard dédié aux associations.
 *
 * Affiche uniquement les indicateurs pertinents pour les associations :
 * - Nombre d'événements créés
 * - Nombre d'événements validés
 * - Nombre total de participants à leurs événements
 * - Historique des actions
 * - Évaluations reçues
 */
final class AssociationDashboardController extends Controller
{
    public function index(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            flash('error', __('auth.association_pending'));
            redirect(dashboard_path());
        }

        $association = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);

        // Statistiques propres à l'association
        $stats = [
            'created'    => (int) Database::value(
                'SELECT COUNT(*) FROM evenements WHERE association_id = ?',
                [$associationId]
            ),
            'validated'  => (int) Database::value(
                "SELECT COUNT(*) FROM evenements WHERE association_id = ? AND statut IN ('VALIDÉ', 'PROGRAMME', 'EN_COURS', 'TERMINE')",
                [$associationId]
            ),
            'pending'    => (int) Database::value(
                "SELECT COUNT(*) FROM evenements WHERE association_id = ? AND statut IN ('EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE')",
                [$associationId]
            ),
            'participants' => (int) Database::value(
                'SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id IN (SELECT id FROM evenements WHERE association_id = ?)',
                [$associationId]
            ),
        ];

        // Compteurs par statut (KPI cliquables) et événements exigeant une action
        $statutsCounts = EvenementService::statutsCounts($associationId);
        $attention = Database::all(
            "SELECT e.id, e.adresse, e.motif_refus, th.created_at AS action_demandee_le
             FROM evenements e
             JOIN transition_history th ON th.evenement_id = e.id
             WHERE e.association_id = ? AND e.statut = 'MODIFICATION_DEMANDEE' AND e.deleted_at IS NULL
               AND th.statut_apres = 'MODIFICATION_DEMANDEE'
             GROUP BY e.id
             ORDER BY action_demandee_le DESC",
            [$associationId]
        );

        // Historique récent (transitions des événements)
        $historique = Database::all(
            'SELECT th.*, e.adresse FROM transition_history th
             JOIN evenements e ON e.id = th.evenement_id
             WHERE e.association_id = ?
             ORDER BY th.created_at DESC LIMIT 10',
            [$associationId]
        );

        // Évaluations (self-évaluations par les associations)
        $evaluations = Database::all(
            "SELECT ev.*, e.adresse
             FROM evaluation ev
             JOIN evenements e ON e.id = ev.evenement_id
             WHERE ev.association_id = ?
             ORDER BY ev.created_at DESC LIMIT 5",
            [$associationId]
        );

        $this->view('association/dashboard', [
            'association'   => $association,
            'stats'         => $stats,
            'statutsCounts' => $statutsCounts,
            'attention'     => $attention,
            'historique'    => $historique,
            'evaluations'   => $evaluations,
        ], 'association');
    }
}
