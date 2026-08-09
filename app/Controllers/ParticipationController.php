<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Session;

final class ParticipationController extends Controller
{
    public function checkin(string $token): never
    {
        $qr = Database::one('SELECT * FROM qr_event WHERE token_qr = ?', [$token]);

        if ($qr === null) {
            abort(404, 'QR Code introuvable.');
        }

        $event = Database::one('SELECT * FROM evenements WHERE id = ? AND deleted_at IS NULL', [(int) $qr['evenement_id']]);

        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        echo view('qrcode/checkin', [
            'qr'    => $qr,
            'event' => $event,
        ]);
        exit;
    }

    public function register(string $token): never
    {
        $this->requireAuth();

        $qr = Database::one('SELECT * FROM qr_event WHERE token_qr = ?', [$token]);

        if ($qr === null) {
            json_response(['success' => false, 'error' => 'QR Code introuvable.']);
        }

        $existing = Database::one(
            'SELECT evenement_id FROM evenement_participant WHERE evenement_id = ? AND user_id = ?',
            [(int) $qr['evenement_id'], Session::userId()]
        );

        if ($existing !== null) {
            json_response(['success' => false, 'error' => 'Vous êtes déjà inscrit.']);
        }

        Database::run(
            'INSERT INTO evenement_participant (evenement_id, user_id, heure_scan, ip_address) VALUES (?, ?, NOW(), ?)',
            [(int) $qr['evenement_id'], Session::userId(), client_ip()]
        );

        AuditLog::log('participation.register', 'evenement_participant', 0, [
            'evenement_id' => (int) $qr['evenement_id'],
            'user_id'     => Session::userId(),
        ]);

        json_response(['success' => true, 'message' => 'Check-in enregistré.']);
    }
}