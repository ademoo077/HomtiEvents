<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Gamification;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Session;

/**
 * API de check-in par scan QR Code.
 */
final class CheckinController
{
    public function verify(string $token): never
    {
        $qr = QrCodeGenerator::findByToken($token);

        if ($qr === null) {
            json_response(['success' => false, 'code' => 'INCONNU', 'message' => 'QR code inconnu.'], 404);
        }

        if (! in_array(($qr['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE'], true)) {
            json_response(['success' => false, 'code' => 'INACTIF', 'message' => 'L\'événement n\'est plus actif.']);
        }

        if ($qr['date_expiration'] !== null && strtotime((string) $qr['date_expiration']) < time()) {
            json_response(['success' => false, 'code' => 'EXPIRE', 'message' => 'Ce QR code a expiré.']);
        }

        json_response([
            'success' => true,
            'data'    => [
                'evenement_id'   => (int) $qr['evenement_id'],
                'adresse'        => $qr['adresse'],
                'date_evenement' => $qr['date_evenement'],
                'heure'          => $qr['heure'],
                'description'    => $qr['description'],
            ],
        ]);
    }

    /**
     * Enregistre la participation au check-in.
     *
     * Sécurité : l'utilisateur est identifié par sa SESSION, jamais par un
     * `user_id` envoyé dans le corps de la requête (usurpation impossible).
     */
    public function register(string $token): never
    {
        $userId = Session::userId();
        if ($userId === null) {
            json_response(['success' => false, 'message' => 'Authentification requise.'], 401);
        }

        $user = Database::one('SELECT id FROM users WHERE id = ? AND is_active = 1', [$userId]);
        if ($user === null) {
            json_response(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        $qr = QrCodeGenerator::findByToken($token);

        if ($qr === null || ! QrCodeGenerator::isValid($qr)) {
            json_response(['success' => false, 'code' => 'INVALIDE', 'message' => 'QR code invalide ou expiré.'], 400);
        }

        $already = QrCodeGenerator::hasParticipated((int) $qr['evenement_id'], $userId);

        if ($already) {
            json_response(['success' => false, 'code' => 'DEJA', 'message' => 'Participation déjà enregistrée.'], 409);
        }

        QrCodeGenerator::registerParticipation((int) $qr['evenement_id'], $userId, $already);

        AuditLog::historique((int) $qr['evenement_id'], 'participation', 'Participation via API check-in');

        json_response([
            'success' => true,
            'message' => 'Participation enregistrée ! +' . Gamification::POINTS_PARTICIPATION . ' points.',
            'points_gagnes' => Gamification::POINTS_PARTICIPATION,
        ], 201);
    }
}
