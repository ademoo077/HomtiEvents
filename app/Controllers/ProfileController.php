<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;

/**
 * Espace profil — rôles wilaya / association / epic.
 */
final class ProfileController extends Controller
{
    public function show(): never
    {
        $this->requireAuth();

        $user    = Session::user();
        $role    = Rbac::role($user);
        $userId  = (int) $user['id'];
        $layout  = $this->layoutForRole($role);

        if (! in_array($role, ['wilaya', 'association', 'epic'], true)) {
            redirect(dashboard_path());
        }

        $preferences = Database::one('SELECT * FROM user_preferences WHERE user_id = ?', [$userId])
            ?? ['notif_email' => 1, 'notif_inapp' => 1, 'langue' => null];

        $data = [
            'user'        => $user,
            'role'        => $role,
            'roleLabel'   => Rbac::roleLabel($role),
            'preferences' => $preferences,
            'errors'      => $this->errors(),
            'success'     => flash('success'),
            'widgets'     => $this->widgets($role, $user),
            'association' => $this->association($user),
            'epic'        => $this->epic($user),
            'qrDataUri'   => $this->qrDataUri($role, $user),
            'publicUrl'   => $this->publicUrl($role, $user),
            'page'        => request_path(),
        ];

        $this->view('profile.index', $data, $layout);
    }

    public function updateInfo(): never
    {
        $this->requireAuth();
        $user = Session::user();
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic'], true)) {
            redirect(dashboard_path());
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'nom'       => 'required|string|max:50',
            'prenom'    => 'required|string|max:50',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $userId = (int) $user['id'];
        $old = ['nom' => $user['nom'], 'prenom' => $user['prenom'], 'telephone' => $user['telephone'] ?? ''];

        Database::update('users', [
            'nom'       => trim((string) $data['nom']),
            'prenom'    => trim((string) $data['prenom']),
            'telephone' => trim((string) ($data['telephone'] ?? '')),
        ], 'id = ?', [$userId]);

        Session::refreshUser();

        AuditLog::log('profile.update', 'user', $userId, $old, [
            'nom' => trim((string) $data['nom']), 'prenom' => trim((string) $data['prenom']), 'telephone' => trim((string) ($data['telephone'] ?? '')),
        ]);

        flash('success', 'Profil mis à jour avec succès.');
        redirect($this->profilePath());
    }

    public function updatePassword(): never
    {
        $this->requireAuth();
        $user = Session::user();

        $data = all_input();
        $validator = Validator::make($data, [
            'current_password'    => 'required|string',
            'password'            => 'required|string|min:8',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors());
        }

        if ((string) $data['password'] !== (string) $data['password_confirmation']) {
            $this->backWithErrors(['password_confirmation' => 'La confirmation ne correspond pas au nouveau mot de passe.']);
        }

        if (! password_verify((string) $data['current_password'], (string) $user['password'])) {
            AuditLog::logWithDetails('profile.password.fail', 'user', (int) $user['id'], null, null, 'echec');
            $this->backWithErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        if ((string) $data['password'] === (string) $data['current_password']) {
            $this->backWithErrors(['password' => 'Le nouveau mot de passe doit être différent de l\'actuel.']);
        }

        Database::update('users', [
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
        ], 'id = ?', [(int) $user['id']]);

        Session::refreshUser();

        AuditLog::log('profile.password', 'user', (int) $user['id']);

        flash('success', 'Mot de passe modifié avec succès.');
        redirect($this->profilePath());
    }

    public function uploadAvatar(): never
    {
        $this->requireAuth();
        $user = Session::user();

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->backWithErrors(['avatar' => 'Aucun fichier reçu.']);
        }

        $result = UploadHelper::uploadImage($_FILES['avatar'], (string) config('paths.uploads.avatars'), 2 * 1024 * 1024);

        if (! $result['success']) {
            $this->backWithErrors(['avatar' => $result['error'] ?? 'Erreur d\'upload.']);
        }

        $old = $user['avatar'] ?? null;
        Database::update('users', ['avatar' => $result['path']], 'id = ?', [(int) $user['id']]);
        Session::refreshUser();

        if ($old) {
            UploadHelper::delete($old);
        }

        AuditLog::log('profile.avatar', 'user', (int) $user['id'], ['avatar' => $old], ['avatar' => $result['path']]);

        flash('success', 'Photo de profil mise à jour.');
        redirect($this->profilePath());
    }

    public function removeAvatar(): never
    {
        $this->requireAuth();
        $user = Session::user();
        $old  = $user['avatar'] ?? null;

        Database::update('users', ['avatar' => null], 'id = ?', [(int) $user['id']]);
        Session::refreshUser();

        if ($old) {
            UploadHelper::delete($old);
        }

        AuditLog::log('profile.avatar.remove', 'user', (int) $user['id'], ['avatar' => $old], ['avatar' => null]);

        flash('success', 'Photo de profil supprimée.');
        redirect($this->profilePath());
    }

    public function updatePreferences(): never
    {
        $this->requireAuth();
        $user = Session::user();
        $userId = (int) $user['id'];

        $data = all_input();
        $notifEmail = isset($data['notif_email']) && (int) $data['notif_email'] === 1;
        $notifInapp = isset($data['notif_inapp']) && (int) $data['notif_inapp'] === 1;
        $langue = in_array((string) ($data['langue'] ?? ''), ['fr', 'ar'], true) ? (string) $data['langue'] : null;

        if (Database::exists('SELECT 1 FROM user_preferences WHERE user_id = ?', [$userId])) {
            Database::update('user_preferences', [
                'notif_email' => $notifEmail ? 1 : 0,
                'notif_inapp' => $notifInapp ? 1 : 0,
                'langue'      => $langue,
            ], 'user_id = ?', [$userId]);
        } else {
            Database::insert('user_preferences', [
                'user_id'     => $userId,
                'notif_email' => $notifEmail ? 1 : 0,
                'notif_inapp' => $notifInapp ? 1 : 0,
                'langue'      => $langue,
            ]);
        }

        AuditLog::log('profile.preferences', 'user', $userId);

        flash('success', 'Préférences enregistrées.');
        redirect($this->profilePath());
    }

    public function exportData(): never
    {
        $this->requireAuth();
        $user = Session::user();
        $userId = (int) $user['id'];

        $data = [
            'utilisateur'  => [
                'id'         => $userId,
                'nom'        => $user['nom'],
                'prenom'     => $user['prenom'],
                'email'      => $user['email'],
                'telephone'  => $user['telephone'] ?? null,
                'role'       => $user['role_user'] ?? null,
                'cree_le'    => $user['created_at'] ?? null,
            ],
            'preferences' => Database::one('SELECT * FROM user_preferences WHERE user_id = ?', [$userId]),
            'audit'       => Database::all('SELECT action, modele, modele_id, statut, ip_address, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC', [$userId]),
        ];

        if ($user['association_id'] ?? null) {
            $data['association'] = Database::one('SELECT * FROM associations WHERE id = ?', [(int) $user['association_id']]);
            $data['evenements'] = Database::all(
                'SELECT id, titre, adresse, date_evenement, statut, created_at FROM evenements WHERE association_id = ? ORDER BY created_at DESC',
                [(int) $user['association_id']]
            );
        }

        if ($user['epic_id'] ?? null) {
            $data['epic'] = Database::one('SELECT * FROM epic WHERE id = ?', [(int) $user['epic_id']]);
        }

        AuditLog::log('profile.export', 'user', $userId);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mes-donnees-' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function deactivateRequest(): never
    {
        $this->requireAuth();
        $user = Session::user();

        $data = all_input();
        $validator = Validator::make($data, [
            'motif'              => 'required|string|min:10|max:500',
            'current_password'   => 'required|string',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        if (! password_verify((string) $data['current_password'], (string) $user['password'])) {
            AuditLog::logWithDetails('profile.deactivate.fail', 'user', (int) $user['id'], null, null, 'echec');
            $this->backWithErrors(['current_password' => 'Le mot de passe actuel est incorrect.'], $data);
        }

        Database::insert('notifications', [
            'user_id'      => $user['id'],
            'type'         => 'demande_desactivation',
            'titre'        => 'Demande de désactivation du compte',
            'message_notif' => 'L\'utilisateur ' . trim((string) ($user['prenom'] . ' ' . $user['nom'])) . ' (' . $user['email'] . ') demande la désactivation de son compte. Motif : ' . trim((string) $data['motif']),
            'data_json'    => json_encode(['motif' => $data['motif'], 'user_id' => $user['id']], JSON_UNESCAPED_UNICODE),
            'lu'           => 0,
        ]);

        AuditLog::logWithDetails('profile.deactivate.request', 'user', (int) $user['id'], null, ['motif' => $data['motif']], 'succes');

        flash('success', 'Votre demande de désactivation a été transmise à l\'administration.');
        redirect($this->profilePath());
    }

    private function profilePath(): string
    {
        return 'profile';
    }

    private function layoutForRole(?string $role): string
    {
        return $role === 'association' ? 'association' : 'main';
    }

    private function widgets(string $role, array $user): array
    {
        $userId = (int) $user['id'];

        return match ($role) {
            'wilaya' => [
                ['label' => 'Événements validés', 'icon' => 'mdi-check-decagram', 'value' => (int) Database::value(
                    'SELECT COUNT(*) FROM evenements WHERE statut IN (' . implode(',', array_map(fn($s) => "'$s'", EvenementService::STATUTS_VALIDES)) . ')'
                )],
                ['label' => 'Événements programmés', 'icon' => 'mdi-calendar-check', 'value' => (int) Database::value("SELECT COUNT(*) FROM evenements WHERE statut = 'PROGRAMME'")],
                ['label' => 'Associations', 'icon' => 'mdi-handshake', 'value' => (int) Database::value('SELECT COUNT(*) FROM associations')],
                ['label' => 'EPICs', 'icon' => 'mdi-satellite-variant', 'value' => (int) Database::value('SELECT COUNT(*) FROM epic')],
            ],
            'association' => [
                ['label' => 'Mes événements', 'icon' => 'mdi-calendar-star', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE association_id = ?', [(int) ($user['association_id'] ?? 0)])],
                ['label' => 'En attente', 'icon' => 'mdi-clock-outline', 'value' => (int) Database::value(
                    'SELECT COUNT(*) FROM evenements WHERE association_id = ? AND statut IN (' . implode(',', array_map(fn($s) => "'$s'", EvenementService::STATUTS_EN_ATTENTE)) . ')',
                    [(int) ($user['association_id'] ?? 0)]
                )],
                ['label' => 'Validés', 'icon' => 'mdi-check-decagram', 'value' => (int) Database::value(
                    'SELECT COUNT(*) FROM evenements WHERE association_id = ? AND statut IN (' . implode(',', array_map(fn($s) => "'$s'", EvenementService::STATUTS_VALIDES)) . ')',
                    [(int) ($user['association_id'] ?? 0)]
                )],
                ['label' => 'Participants', 'icon' => 'mdi-account-group', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenement_participant ep JOIN evenements e ON e.id = ep.evenement_id WHERE e.association_id = ?', [(int) ($user['association_id'] ?? 0)])],
            ],
            'epic' => [
                ['label' => 'Événements attribués', 'icon' => 'mdi-calendar-check', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE assigned_org_id = ?', [(int) ($user['epic_id'] ?? 0)])],
                ['label' => 'Anomalies traitées', 'icon' => 'mdi-alert-octagon', 'value' => (int) Database::value('SELECT COUNT(DISTINCT ea.evenement_id) FROM evenements e JOIN anomalies_evenement ea ON ea.evenement_id = e.id WHERE e.assigned_org_id = ?', [(int) ($user['epic_id'] ?? 0)])],
                ['label' => 'Routages récents', 'icon' => 'mdi-route', 'value' => (int) Database::value('SELECT COUNT(*) FROM routing_log WHERE new_org_id = ? OR old_org_id = ?', [(int) ($user['epic_id'] ?? 0), (int) ($user['epic_id'] ?? 0)])],
            ],
            default => [],
        };
    }

    private function association(array $user): ?array
    {
        if (empty($user['association_id'])) {
            return null;
        }

        return Database::one('SELECT * FROM associations WHERE id = ?', [(int) $user['association_id']]);
    }

    private function epic(array $user): ?array
    {
        if (empty($user['epic_id'])) {
            return null;
        }

        return Database::one('SELECT * FROM epic WHERE id = ?', [(int) $user['epic_id']]);
    }

    private function publicUrl(string $role, array $user): ?string
    {
        return match ($role) {
            'association' => ! empty($user['association_id']) ? url('association/' . (int) $user['association_id']) : null,
            'epic'        => ! empty($user['epic_id']) ? url('epic/' . (int) $user['epic_id']) : null,
            default       => null,
        };
    }

    private function qrDataUri(string $role, array $user): ?string
    {
        $publicUrl = $this->publicUrl($role, $user);

        if ($publicUrl === null) {
            return null;
        }

        return QrCodeGenerator::pngDataUri($publicUrl, 160);
    }
}
