<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;

final class QrCodeController extends Controller
{
    public function scan(): never
    {
        $this->requireAuth();

        $evenements = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut,
                    q.token_qr, q.date_expiration
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.deleted_at IS NULL AND q.token_qr IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 10'
        );

        $layout = \App\Helpers\Rbac::role($this->user()) === 'citoyen' ? 'citoyen' : 'main';
        $view = \App\Helpers\Rbac::role($this->user()) === 'citoyen' ? 'qrcode/scan_citoyen' : 'qrcode/scan';

        $this->view($view, [
            'evenements' => $evenements,
        ], $layout);
    }

    public function show(string $id): never
    {
        $this->requireAuth();

        $qr = Database::one('SELECT * FROM qr_event WHERE evenement_id = ?', [(int) $id]);

        if ($qr === null) {
            abort(404, 'QR Code introuvable.');
        }

        $this->view('qrcode/show', [
            'qr' => $qr,
        ], 'main');
    }
}
