<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\EpicDashboardService;
use App\Helpers\I18n;
use App\Helpers\Rbac;
use App\Helpers\RoutingService;

/**
 * Tableau de bord EPIC — événements attribués, calendrier, anomalies.
 */
final class EpicDashboardController extends Controller
{
    /**
     * Page principale du tableau de bord EPIC.
     */
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

        $epic = \App\Helpers\Database::one('SELECT * FROM epic WHERE id = ?', [$epicId]);

        // Filtres (appliqués aux KPIs et aux anomalies)
        $filters = [
            'du'         => (string) input('du', ''),
            'au'         => (string) input('au', ''),
            'commune_id' => (int) input('commune_id', 0),
        ];
        $f = array_filter($filters, static fn ($v) => $v !== '' && $v !== 0);

        // Mois du calendrier (par défaut : mois courant)
        $mois = (string) preg_replace('/[^0-9-]/', '', (string) input('mois', date('Y-m')));
        if (preg_match('/^\d{4}-\d{2}$/', $mois) !== 1) {
            $mois = date('Y-m');
        }

        $kpis    = EpicDashboardService::kpis($epicId, $f);
        $parJour = EpicDashboardService::evenementsParJour($epicId, $mois);
        $avenir  = EpicDashboardService::aVenir($epicId);
        $anomalies = EpicDashboardService::anomaliesParMotif($epicId, $f);
        $badgeAnomalies = EpicDashboardService::anomaliesNonTraitees($epicId);
        $communes = EpicDashboardService::communes();
        $nouveauxRoutages = RoutingService::nouveauxRoutages($epicId);

        // ── Idées & conseils contextuels (actions recommandées) ──
        $suggestions = [];
        if ($badgeAnomalies > 0) {
            $suggestions[] = ['icon' => 'mdi-alert-octagon-outline', 'color' => 'danger',
                'titre' => $badgeAnomalies . ' anomalie(s) à traiter',
                'texte' => 'Des événements sont en attente de correction (modification demandée ou refus). Consultez-les pour relancer les associations.',
                'lien'  => url('epic')];
        }
        if ($avenir !== []) {
            $suggestions[] = ['icon' => 'mdi-calendar-clock-outline', 'color' => 'primary',
                'titre' => count($avenir) . ' événement(s) dans les 3 prochains jours',
                'texte' => 'Préparez vos équipes : ' . implode(', ', array_map(
                    static fn ($e) => (string) ($e['commune_nom'] ?? '-'),
                    array_slice($avenir, 0, 3)
                )) . '.',
                'lien'  => url('epic/dashboard')];
        }
        if ($kpis['EN_COURS'] > 0) {
            $suggestions[] = ['icon' => 'mdi-progress-wrench', 'color' => 'amber',
                'titre' => $kpis['EN_COURS'] . ' événement(s) en cours',
                'texte' => 'Pensez à renseigner les interventions et à clôturer les événements terminés.',
                'lien'  => url('epic')];
        }
        if (($kpis['total'] ?? 0) === 0) {
            $suggestions[] = ['icon' => 'mdi-lightbulb-on-outline', 'color' => 'success',
                'titre' => 'Aucun événement attribué',
                'texte' => 'Votre EPIC n\'a pas encore d\'événements. Les nouvelles demandes de la Wilaya vous seront affectées automatiquement selon les anomalies.',
                'lien'  => null];
        }
        if ($nouveauxRoutages !== [] && $suggestions === []) {
            $suggestions[] = ['icon' => 'mdi-router-wireless-outline', 'color' => 'violet',
                'titre' => count($nouveauxRoutages) . ' nouveau(x) routage(s)',
                'texte' => 'Des événements viennent de vous être affectés. Vérifiez leur programme.',
                'lien'  => url('epic')];
        }

        $this->view('epic/dashboard', [
            'epic'            => $epic,
            'epicId'          => $epicId,
            'kpis'            => $kpis,
            'mois'            => $mois,
            'parJour'         => $parJour,
            'avenir'          => $avenir,
            'anomalies'       => $anomalies,
            'badgeAnomalies'  => $badgeAnomalies,
            'communes'        => $communes,
            'nouveauxRoutages'=> $nouveauxRoutages,
            'suggestions'     => $suggestions,
            'filters'         => $filters,
            'isRtl'           => I18n::direction() === 'rtl',
        ]);
    }

    /**
     * Export CSV : KPIs, anomalies, prochains événements.
     */
    public function export(): never
    {
        $this->requireAuth();
        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'epic') {
            abort(403, 'Accès refusé.');
        }

        $epicId = (int) ($user['epic_id'] ?? 0);
        if ($epicId === 0) {
            abort(404, 'Aucun EPIC lié.');
        }

        $epic = \App\Helpers\Database::one('SELECT nom FROM epic WHERE id = ?', [$epicId]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dashboard-epic-' . (int) $epicId . '-' . date('Ymd-Hi') . '.csv"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Dashboard EPIC — ' . ($epic['nom'] ?? ''), date('d/m/Y H:i')], ';', '"', '\\');
        fputcsv($out, [], ';', '"', '\\');

        $kpis = EpicDashboardService::kpis($epicId);

        fputcsv($out, ['Événements attribués', 'Valeur'], ';', '"', '\\');
        $labels = [
            'total'                 => 'Total',
            'VALIDÉ'                => 'Validés',
            'PROGRAMME'             => 'Programmés',
            'EN_COURS'              => 'En cours',
            'TERMINE'               => 'Terminés',
            'REFUSE'                => 'Refusés',
            'EN_ATTENTE'            => 'En attente',
            'MODIFICATION_DEMANDEE' => 'Modification demandée',
        ];
        foreach ($labels as $cle => $lib) {
            fputcsv($out, [$lib, $kpis[$cle] ?? 0], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Répartition des anomalies par motif', 'Nombre'], ';', '"', '\\');
        foreach (EpicDashboardService::anomaliesParMotif($epicId) as $a) {
            fputcsv($out, [$a['motif'], (int) $a['nb']], ';', '"', '\\');
        }

        fputcsv($out, [], ';', '"', '\\');
        fputcsv($out, ['Prochains événements', 'Date', 'Heure', 'Commune'], ';', '"', '\\');
        foreach (EpicDashboardService::aVenir($epicId) as $e) {
            fputcsv($out, [
                $e['adresse'] ?? '',
                $e['date_evenement'] ?? '',
                $e['heure'] ?? '',
                $e['commune_nom'] ?? '',
            ], ';', '"', '\\');
        }

        fclose($out);
        exit;
    }
}
