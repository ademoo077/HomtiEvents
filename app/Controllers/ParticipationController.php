<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Session;
use App\Helpers\Validator;

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
            'SELECT q.*, e.statut FROM qr_event q JOIN evenements e ON e.id = q.evenement_id AND e.deleted_at IS NULL WHERE q.token_qr = ?',
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

        if (QrCodeGenerator::estComplet((int) $qr['evenement_id'])) {
            json_response(['success' => false, 'error' => 'Désolé, la capacité maximale de cet événement est atteinte.']);
        }

        $ok = QrCodeGenerator::registerParticipation((int) $qr['evenement_id'], $userId);

        if (! $ok) {
            json_response(['success' => false, 'error' => 'Votre présence a déjà été enregistrée.']);
        }

        json_response(['success' => true, 'message' => 'Check-in enregistré. Bienvenue !']);
    }

    /**
     * Participation à un événement SANS compte (invité).
     *
     * Après le scan du QR, la personne non connectée peut s'inscrire avec un
     * simple formulaire (nom / prénom / téléphone). Les informations sont
     * stockées puis transmises à l'association organisatrice.
     */
    public function invitee(string $token): never
    {
        $qr = Database::one(
            'SELECT q.*, e.statut, e.capacite, e.association_id
             FROM qr_event q
             JOIN evenements e ON e.id = q.evenement_id AND e.deleted_at IS NULL
             WHERE q.token_qr = ?',
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

        $eventId = (int) $qr['evenement_id'];
        $data = [
            'nom'       => (string) ($_POST['nom'] ?? ''),
            'prenom'    => (string) ($_POST['prenom'] ?? ''),
            'telephone' => (string) ($_POST['telephone'] ?? ''),
        ];

        $validator = Validator::make($data, [
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'telephone' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            json_response(['success' => false, 'error' => $validator->firstError()]);
        }

        // Capacité : inscrits (comptes) + invités.
        $placesRestantes = QrCodeGenerator::placesRestantes($eventId);
        if ($placesRestantes !== null && $placesRestantes <= 0) {
            json_response(['success' => false, 'error' => 'Désolé, la capacité maximale de cet événement est atteinte.']);
        }

        $telephone = trim((string) $data['telephone']);

        // Éviter les doublons : même téléphone déjà inscrit (compte ou invité).
        if (QrCodeGenerator::inviteeDejaInscrit($eventId, $telephone)) {
            json_response(['success' => false, 'error' => 'Ce numéro de téléphone est déjà inscrit à cet événement.']);
        }

        if (is_logged()) {
            $user = Session::user();
            if ($user !== null && QrCodeGenerator::hasParticipated($eventId, (int) $user['id'])) {
                json_response(['success' => false, 'error' => 'Vous êtes déjà inscrit à cet événement.']);
            }
        }

        $ok = QrCodeGenerator::registerInvitee($eventId, $data, $token);
        if (! $ok) {
            json_response(['success' => false, 'error' => 'Votre inscription n\'a pas pu être enregistrée.']);
        }

        // Transmission à l'association organisatrice (notification + lien).
        $associationId = (int) ($qr['association_id'] ?? 0);
        if ($associationId > 0) {
            Notification::sendToAssociation(
                $associationId,
                'Nouvelle participation sans compte',
                $data['prenom'] . ' ' . $data['nom'] . ' (' . $telephone . ') souhaite participer.',
                'participation_invite',
                ['evenement_id' => $eventId, 'invite' => true],
                is_logged() ? (int) Session::userId() : null
            );
        }

        json_response(['success' => true, 'message' => 'Inscription enregistrée, à bientôt !']);
    }
}
