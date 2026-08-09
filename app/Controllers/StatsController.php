<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\StatsService;

/**
 * Statistiques de la plateforme (panel Wilaya).
 */
final class StatsController extends Controller
{
    /**
     * Page de statistiques détaillées (mise en cache fichier).
     */
    public function index(): never
    {
        $this->requirePermission('evenement.view_all');

        $this->view('admin.stats.index', [
            'stats' => StatsService::data(),
        ]);
    }

    /**
     * Export CSV du rapport statistique.
     */
    public function export(): never
    {
        $this->requirePermission('evenement.view_all');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport-statistiques-' . date('Ymd-Hi') . '.csv"');
        header('Cache-Control: no-cache');

        echo StatsService::csv();
        exit;
    }
}
