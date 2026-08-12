<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Session;

final class ParticipationController extends Controller
{
    public function checkin(string $token): never
    {
        $qr = Database::one(
            'SELECT q.*, e.statut, e.date_evenement, e.heure, e.adresse, e.description,
                    e.informations_complementaires, a.nom AS association_nom
             FROM qr_event q
             JOIN evenements e ON e.id = q.evenement_id AND e.deleted_at IS NULL
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE q.token_qr = ?',
            [$token]
        );

        if ($qr === null) {
            abort(404, 'QR Code introuvable.');
        }

        $expired = $qr['date_expiration'] !== null && strtotime((string) $qr['date_expiration']) < time();
        $notProgramme = ! in_array(($qr['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true);
        $already = is_logged() && QrCodeGenerator::hasParticipated((int) $qr['evenement_id'], (int) Session::userId());

        echo view('qrcode/checkin', [
            'qr'          => $qr,
            'event'       => $qr,
            'expired'     => $expired,
            'notProgramme'=> $notProgramme,
            'already'     => $already,
        ]);
        exit;
    }

    public function register(string $token): never
    {
        $this->requireAuth();

        $qr = Database::one(
            'SELECT q.*, e.statut FROM qr_event q JOIN evenements e ON e.id = q.evenement_id WHERE q.token_qr = ?',
            [$token]
        );

        if ($qr === null) {
            json_response(['success' => false, 'error' => 'QR Code introuvable.']);
        }

        if (! in_array(($qr['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true)) {
            json_response(['success' => false, 'error' => 'Ce QR code est expiré ou l\'événement n\'est plus disponible.']);
        }

        if ($qr['date_expiration'] !== null && strtotime((string) $qr['date_expiration']) < time()) {
            json_response(['success' => false, 'error' => 'Ce QR code a expiré.']);
        }

        $userId = (int) Session::userId();

        if (QrCodeGenerator::hasParticipated((int) $qr['evenement_id'], $userId)) {
            json_response(['success' => false, 'error' => 'Vous êtes déjà inscrit à cet événement.']);
        }

        $ok = QrCodeGenerator::registerParticipation((int) $qr['evenement_id'], $userId);

        if (! $ok) {
            json_response(['success' => false, 'error' => 'Votre présence a déjà été enregistrée.']);
        }

        json_response(['success' => true, 'message' => 'Check-in enregistré. Bienvenue !']);
    }
}
