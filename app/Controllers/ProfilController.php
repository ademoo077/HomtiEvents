<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Validator;

final class ProfilController extends Controller
{
    public function show(): never
    {
        $this->requireAuth();

        $user = Session::user();
        $userId = (int) $user['id'];

        $stats = [
            'participations' => (int) Database::value('SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?', [$userId]),
            'points'         => (int) ($user['points'] ?? 0),
        ];

        $badges = Database::all(
            'SELECT b.nom, b.icone, b.points_recompense FROM badges b
             JOIN user_badges ub ON ub.badge_id = b.id
             WHERE ub.user_id = ?
             ORDER BY b.nom ASC',
            [$userId]
        );

        $recent = Database::all(
            'SELECT e.id AS evenement_id, e.adresse, e.date_evenement, ep.heure_scan, e.statut
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC LIMIT 8',
            [$userId]
        );

        $this->view('citoyen.profile', [
            'user'    => $user,
            'stats'   => $stats,
            'badges'  => $badges,
            'recent'  => $recent,
            'errors'  => $this->errors(),
            'success' => flash('success'),
        ], 'citoyen');
    }

    public function update(): never
    {
        $this->requireAuth();

        $data = all_input();
        $validator = Validator::make($data, [
            'nom'      => 'required|string|max:50',
            'prenom'   => 'required|string|max:50',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $user = Session::user();
        $userId = (int) $user['id'];

        // L'email peut être modifié si unique.
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '' && $email !== mb_strtolower((string) $user['email'])) {
            if (
                filter_var($email, FILTER_VALIDATE_EMAIL) === false || Database::exists(
                    'SELECT 1 FROM users WHERE email = ? AND id <> ?',
                    [$email, $userId]
                )
            ) {
                $this->backWithErrors(['email' => 'Cette adresse email est déjà utilisée.'], $data);
            }
        } else {
            $email = (string) $user['email'];
        }

        Database::update('users', [
            'nom'       => trim((string) $data['nom']),
            'prenom'    => trim((string) $data['prenom']),
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'email'     => $email,
        ], 'id = ?', [$userId]);

        // Rafraîchir la session pour les données affichées.
        $fresh = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($fresh !== null) {
            Session::set('user', $fresh);
        }

        AuditLog::log('profile.update', 'user', $userId);

        flash('success', __('profil.updated'));
        redirect('citoyen/profile');
    }
}
