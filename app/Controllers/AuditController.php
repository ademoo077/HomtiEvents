<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;

final class AuditController extends Controller
{
    public function index(): never
    {
        $search = trim((string) input('q', ''));
        $limit = 50;
        $page = max(1, (int) input('page', 1));

        $logs = AuditLog::all($search, $limit, ($page - 1) * $limit);
        $total = AuditLog::count();

        $this->view('control.audit', [
            'logs'   => $logs,
            'search' => $search,
            'page'   => $page,
            'total'  => $total,
        ], 'dashboard-futur');
    }
}
