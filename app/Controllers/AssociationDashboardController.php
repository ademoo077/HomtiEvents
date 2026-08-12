<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\Notification;
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

        // Événements déjà envoyés à la Wilaya (toutes demandes soumises), les 5 plus récents.
        $envoyes = Database::all(
            "SELECT e.*, c.nom AS commune_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE e.association_id = ? AND e.deleted_at IS NULL
               AND e.statut NOT IN ('ANNULE')
             ORDER BY e.created_at DESC LIMIT 5",
            [$associationId]
        );

        // Historique récent (transitions des événements)
        $historique = Database::all(
            'SELECT th.*, e.adresse,
                    CONCAT(u.prenom, " ", u.nom) AS acteur_nom
             FROM transition_history th
             JOIN evenements e ON e.id = th.evenement_id
             LEFT JOIN users u ON u.id = th.user_id
             WHERE e.association_id = ?
             ORDER BY th.created_at DESC LIMIT 10',
            [$associationId]
        );

        // Libellé lisible de chaque action pour la vue.
        $historique = array_map(static function (array $h): array {
            $avant = (string) ($h['statut_avant'] ?? '');
            $apres = (string) ($h['statut_apres'] ?? '');

            $h['nouveau_statut'] = $apres !== '' ? $apres : 'EN_ATTENTE';
            $h['action'] = match (true) {
                $avant === $apres && $apres !== '' => 'Changement d\'état (' . statut_label($apres) . ')',
                $apres === 'VALIDÉ'                => 'Validation par la Wilaya',
                $apres === 'PROGRAMME'             => 'Programmation par la Wilaya',
                $apres === 'QR_GENERE'             => 'QR code généré',
                $apres === 'EN_COURS'              => 'Démarrage de l\'événement',
                $apres === 'TERMINE'               => 'Clôture de l\'événement',
                $apres === 'REFUSE'                => 'Demande refusée',
                $apres === 'MODIFICATION_DEMANDEE' => 'Modifications demandées',
                $apres === 'ANNULE'                => 'Demande annulée',
                $apres === 'EN_ATTENTE'            => 'Re-soumission / retour en attente',
                default                            => 'Changement de statut',
            };
            if (! empty($h['acteur_nom']) && str_contains($h['action'], 'Wilaya')) {
                $h['action'] .= ' (' . $h['acteur_nom'] . ')';
            }

            return $h;
        }, $historique);

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
            'envoyes'       => $envoyes,
            'historique'    => $historique,
            'evaluations'   => $evaluations,
        ], 'association');
    }

    /**
     * Liste filtrable des événements de l'association (vues cartes).
     * Onglet "Tous / En attente / Validés / Programmés / Terminés".
     */
    public function events(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);

        $onglet = (string) input('tab', 'envoyes');
        $onglets = [
            'all'       => null,
            'envoyes'   => ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE', 'VALIDÉ', 'PROGRAMME', 'QR_GENERE', 'EN_COURS', 'TERMINE'],
            'pending'   => ['EN_ATTENTE', 'MODIFICATION_DEMANDEE', 'REFUSE'],
            'validated' => ['VALIDÉ'],
            'programme' => ['PROGRAMME', 'QR_GENERE'],
            'termine'   => ['EN_COURS', 'TERMINE'],
        ];
        $statuts = $onglets[$onglet] ?? $onglets['envoyes'];

        $q = trim((string) input('q', ''));
        $where = ['e.association_id = ?'];
        $params = [$associationId];
        if ($statuts !== null) {
            $in = str_repeat('?,', count($statuts) - 1) . '?';
            $where[] = 'e.statut IN (' . $in . ')';
            $params = array_merge($params, $statuts);
        }
        if ($q !== '') {
            $where[] = '(e.adresse LIKE ? OR e.description LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT e.*, c.nom AS commune_nom
                FROM evenements e
                LEFT JOIN commune c ON c.id = e.commune_id
                WHERE ' . implode(' AND ', $where) . ' AND e.deleted_at IS NULL
                ORDER BY e.created_at DESC';

        $page = (int) input('page', 1);
        $result = Database::paginate($sql, $params, 15, $page);

        $statutsCounts = EvenementService::statutsCounts($associationId);
        $tabCounts = [];
        $tabCounts['all'] = (int) Database::value('SELECT COUNT(*) FROM evenements WHERE association_id = ? AND deleted_at IS NULL', [$associationId]);
        foreach ($onglets as $tab => $liste) {
            if ($liste === null) {
                continue;
            }
            $tabCounts[$tab] = (int) array_sum(array_intersect_key($statutsCounts, array_flip($liste)));
        }

        $this->view('association/events', [
            'evenements'    => $result['items'],
            'filters'       => ['q' => $q],
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'onglet'        => $onglet,
            'tabCounts'     => $tabCounts,
        ], 'association');
    }

    /**
     * Espace notifications de l'association : historique complet des
     * notifications (non lues + lues), avec pagination.
     */
    public function notifications(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $page   = (int) input('page', 1);
        $result = Notification::all((int) $user['id'], 20, $page);

        $this->view('association/notifications', [
            'notifications' => $result['items'],
            'page'          => $result['page'],
            'lastPage'      => $result['last_page'],
            'total'         => $result['total'],
            'unread'        => Notification::unreadCount((int) $user['id']),
        ], 'association');
    }

    /**
     * Programmation express depuis la liste association (statut VALIDÉ → PROGRAMME).
     * Réutilise EvenementService::programmer avec les EPICs déjà affectées.
     */
    public function programmer(string $id): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        $event = Database::one(
            'SELECT id, association_id FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );
        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $epics = array_column(
            Database::all('SELECT epic_id FROM evenement_epic WHERE evenement_id = ?', [(int) $id]),
            'epic_id'
        );

        $date = (string) input('date_evenement');
        $heure = (string) input('heure');

        try {
            EvenementService::programmer((int) $id, $date, $heure ?: '09:00:00', $epics, $associationId);
            flash('success', 'Événement programmé.');
        } catch (\Throwable $e) {
            flash('error', 'Impossible de programmer : ' . $e->getMessage());
        }

        redirect('association/events');
    }

    /**
     * Annulation d'une demande par l'association (EN_ATTENTE / MODIFICATION_DEMANDEE).
     */
    public function annuler(string $id): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'association') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);
        $event = Database::one(
            'SELECT id, statut FROM evenements WHERE id = ? AND association_id = ? AND deleted_at IS NULL',
            [(int) $id, $associationId]
        );
        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $statut = (string) ($event['statut'] ?? 'EN_ATTENTE');
        if (! in_array($statut, ['EN_ATTENTE', 'MODIFICATION_DEMANDEE'], true)) {
            flash('error', 'Annulation impossible : l\'événement n\'est plus annulable.');
            redirect('association/events');
        }

        $motif = trim((string) input('motif', ''));
        if ($motif === '') {
            flash('error', 'Le motif d\'annulation est obligatoire.');
            redirect('association/events');
        }

        EvenementService::changerStatutAnnule((int) $id, $motif);

        flash('success', 'Demande annulée avec succès.');
        redirect('association/events');
    }
}
