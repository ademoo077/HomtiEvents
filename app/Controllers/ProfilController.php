<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\UploadHelper;
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
            'SELECT b.nom, b.icone, b.points_recompense, b.couleur FROM badges b
             JOIN user_badges ub ON ub.badge_id = b.id
             WHERE ub.user_id = ?
             ORDER BY b.nom ASC',
            [$userId]
        );

        $recent = Database::all(
            'SELECT e.id AS evenement_id, e.adresse, e.date_evenement, ep.heure_scan, e.statut
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id AND e.deleted_at IS NULL
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC LIMIT 8',
            [$userId]
        );

        $prefs = Database::one(
            'SELECT notif_email, notif_inapp, langue FROM user_preferences WHERE user_id = ?',
            [$userId]
        );

        $this->view('citoyen.profile', [
            'user'    => $user,
            'stats'   => $stats,
            'badges'  => $badges,
            'recent'  => $recent,
            'prefs'   => $prefs,
            'errors'  => $this->errors(),
            'success' => flash('success'),
        ], 'citoyen');
    }

    public function update(): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $data = all_input();
        $user = Session::user();
        $userId = (int) $user['id'];

        // Avatar upload (form with action=avatar)
        if (($data['action'] ?? '') === 'avatar') {
            if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
                flash('error', 'Aucun fichier reçu.');
                redirect('citoyen/profile');
            }

            $result = UploadHelper::uploadImage($_FILES['avatar'], (string) config('paths.uploads.avatars'), 2 * 1024 * 1024);

            if (! $result['success']) {
                flash('error', $result['error'] ?? 'Erreur d\'upload.');
                redirect('citoyen/profile');
            }

            $old = $user['avatar'] ?? null;
            Database::update('users', ['avatar' => $result['path']], 'id = ?', [$userId]);

            $fresh = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
            if ($fresh !== null) {
                Session::set('user', $fresh);
            }

            if ($old) {
                UploadHelper::delete($old);
            }

            AuditLog::log('profile.avatar', 'user', $userId, ['avatar' => $old], ['avatar' => $result['path']]);

            flash('success', $this->isAr() ? 'تم تحديث الصورة بنجاح.' : 'Photo de profil mise à jour.');
            redirect('citoyen/profile');
        }

        $validator = Validator::make($data, [
            'nom'      => 'required|string|max:50',
            'prenom'   => 'required|string|max:50',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

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

    /**
     * Sauvegarde les préférences de notification (toggle AJAX).
     */
    public function updatePreferences(): never
    {
        $this->requireAuth();
        $this->csrfCheck();

        $user   = Session::user();
        $userId = (int) $user['id'];

        $data       = all_input();
        $notifEmail = isset($data['notif_email']) ? (int) $data['notif_email'] : null;
        $notifInapp = isset($data['notif_inapp']) ? (int) $data['notif_inapp'] : null;
        $langue     = isset($data['langue']) ? (string) $data['langue'] : null;

        $exists = Database::exists('SELECT 1 FROM user_preferences WHERE user_id = ?', [$userId]);

        $updates = [];
        if ($notifEmail !== null) {
            $updates['notif_email'] = $notifEmail ? 1 : 0;
        }
        if ($notifInapp !== null) {
            $updates['notif_inapp'] = $notifInapp ? 1 : 0;
        }
        if ($langue !== null && in_array($langue, ['fr', 'ar'], true)) {
            $updates['langue'] = $langue;
            // Appliquer immédiatement la préférence (session + cookie).
            \App\Helpers\I18n::set($langue);
        }

        if ($updates !== []) {
            if ($exists) {
                Database::update('user_preferences', $updates, 'user_id = ?', [$userId]);
            } else {
                $updates['user_id'] = $userId;
                if (! isset($updates['notif_email'])) {
                    $updates['notif_email'] = 1;
                }
                if (! isset($updates['notif_inapp'])) {
                    $updates['notif_inapp'] = 1;
                }
                Database::insert('user_preferences', $updates);
            }
        }

        AuditLog::log('profile.preferences', 'user', $userId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'          => true,
                'notif_email' => $notifEmail ?? 1,
                'notif_inapp' => $notifInapp ?? 1,
                'langue'      => $langue ?? '',
            ]);
            exit;
        }

        flash('success', 'Préférences enregistrées.');
        redirect('citoyen/profile');
    }

    private function isAr(): bool
    {
        return \App\Helpers\I18n::direction() === 'rtl';
    }
}
