<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\EvenementService;
use App\Helpers\I18n;
use App\Helpers\QrCodeGenerator;
use App\Helpers\Rbac;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\Totp;
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

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

        $preferences = Database::one('SELECT * FROM user_preferences WHERE user_id = ?', [$userId])
            ?? ['notif_email' => 1, 'notif_inapp' => 1, 'langue' => null];

        $association = $this->association($user);

        // Suggestions contextuelles pour membre
        $suggestions = [];
        if ($role === 'membre' && $association) {
            $isAr = \App\Helpers\I18n::direction() === 'rtl';
            $today = date('Y-m-d');
            $events = Database::all(
                'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom,
                        (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
                 FROM evenements e
                 LEFT JOIN commune c ON c.id = e.commune_id
                 LEFT JOIN associations a ON a.id = e.association_id
                 WHERE e.association_id = ? AND e.deleted_at IS NULL
                 ORDER BY e.date_evenement ASC',
                [(int) $association['id']]
            );
            $prochains = array_values(array_filter($events, fn($e) => (string)($e['date_evenement'] ?? '') >= $today));
            $participantsTotal = array_sum(array_map(fn($e) => (int)($e['participants'] ?? 0), $events));
            $mesParticipations = (int) Database::value('SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?', [$userId]);

            // Prochain événement
            if ($prochains !== []) {
                $ev = $prochains[0];
                $j = (int) floor((strtotime((string)$ev['date_evenement']) - time()) / 86400);
                $suggestions[] = [
                    'icon'  => 'mdi-calendar-clock-outline',
                    'color' => 'primary',
                    'titre' => $isAr ? 'حدث قادم' : 'Événement à venir',
                    'texte' => $isAr
                        ? 'في ' . date('d/m/Y', strtotime((string)$ev['date_evenement']))
                          . (empty($ev['heure']) ? '' : ' على الساعة ' . substr((string)$ev['heure'], 0, 5))
                        : 'Le ' . date('d/m/Y', strtotime((string)$ev['date_evenement']))
                          . (empty($ev['heure']) ? '' : ' à ' . substr((string)$ev['heure'], 0, 5)),
                    'lien' => url('dashboard') . '#evenements',
                    'cta'  => $isAr ? 'عرض الفعالية' : 'Voir l\'événement',
                ];
                if ($j <= 1) {
                    $suggestions[] = [
                        'icon'  => 'mdi-alarm',
                        'color' => 'amber',
                        'titre' => $isAr ? 'تنبيه' : 'Rappel',
                        'texte' => $isAr
                            ? 'الحدث قريب جداً، لا تنسوا الترويج والمشاركة!'
                            : 'L\'événement approche, pensez à le relayer et à y participer !',
                    ];
                }
            }

            // Participations
            if ($mesParticipations === 0) {
                $suggestions[] = [
                    'icon'  => 'mdi-qrcode-scan',
                    'color' => 'blue',
                    'titre' => $isAr ? 'مشاركتكم الأولى' : 'Votre première participation',
                    'texte' => $isAr
                        ? 'احضروا الفعالية القادمة وسجّلوا حضوركم.'
                        : 'Présentez-vous au prochain événement pour comptabiliser votre participation.',
                ];
            }

            // Profil
            if (empty($user['telephone']) || empty($user['avatar'])) {
                $suggestions[] = [
                    'icon'  => 'mdi-account-edit-outline',
                    'color' => 'amber',
                    'titre' => $isAr ? 'أكملوا ملفكم' : 'Complétez votre profil',
                    'texte' => $isAr
                        ? 'أضيفوا رقم الهاتف أو صورة.'
                        : 'Ajoutez votre téléphone ou une photo.',
                    'lien' => url('profile'),
                    'cta'  => $isAr ? 'تعديل الملف' : 'Modifier mon profil',
                ];
            }

            // Association status
            if (! $association['valide']) {
                $suggestions[] = [
                    'icon'  => 'mdi-shield-alert-outline',
                    'color' => 'amber',
                    'titre' => $isAr ? 'جمعية قيد المراجعة' : 'Association en attente de validation',
                    'texte' => $isAr
                        ? 'بعض الخدمات قد تكون محدودة لحين المصادقة.'
                        : 'Certaines fonctions peuvent être limitées jusqu\'à la validation.',
                ];
            }
        }

        $data = [
            'user'        => $user,
            'role'        => $role,
            'roleLabel'   => Rbac::roleLabel($role),
            'preferences' => $preferences,
            'errors'      => $this->errors(),
            'success'     => flash('success'),
            'widgets'     => $this->widgets($role, $user),
            'association' => $association,
            'epic'        => $this->epic($user),
            'qrDataUri'   => $this->qrDataUri($role, $user),
            'publicUrl'   => $this->publicUrl($role, $user),
            'page'        => request_path(),
            'suggestions' => $suggestions,
        ];

        $this->view('profile.index', $data, $layout);
    }

    public function updateInfo(): never
    {
        $this->requireAuth();
        $this->csrfCheck();
        $user = Session::user();
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
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

    public function updateEmail(): never
    {
        $this->requireAuth();
        $this->csrfCheck();
        $user = Session::user();
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

        $data = all_input();
        $newEmail = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['current_password'] ?? '');

        if ($newEmail === '' || ! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->backWithErrors(['email' => 'Adresse email invalide.']);
        }

        if ($password === '') {
            $this->backWithErrors(['current_password' => 'Mot de passe requis pour changer l\'email.']);
        }

        $userId = (int) $user['id'];
        $dbUser = Database::one('SELECT password FROM users WHERE id = ?', [$userId]);

        if ($dbUser === null || ! password_verify($password, $dbUser['password'])) {
            $this->backWithErrors(['current_password' => 'Mot de passe incorrect.']);
        }

        $existing = Database::one('SELECT id FROM users WHERE email = ? AND id != ?', [$newEmail, $userId]);
        if ($existing !== null) {
            $this->backWithErrors(['email' => 'Cet email est déjà utilisé par un autre compte.']);
        }

        $oldEmail = (string) $user['email'];
        Database::run('UPDATE users SET email = ? WHERE id = ?', [$newEmail, $userId]);

        Session::refreshUser();

        AuditLog::log('profile.email_change', 'user', $userId, ['email' => $oldEmail], ['email' => $newEmail]);
        Security::evenement('email_change', 'Email changé de ' . $oldEmail . ' à ' . $newEmail, 1, ['user_id' => $userId]);

        flash('success', 'Email modifié avec succès.');
        redirect($this->profilePath());
    }

    public function updatePassword(): never
    {
        $this->requireAuth();
        $this->csrfCheck();
        $user = Session::user();
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

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
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

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
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

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
        $role = Rbac::role($user);

        if (! in_array($role, ['wilaya', 'association', 'epic', 'membre'], true)) {
            redirect(dashboard_path());
        }

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
        return match ($role) {
            'association' => 'association',
            'membre'      => 'member',
            default       => 'main',
        };
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
            'membre' => [
                ['label' => 'Événements de l\'association', 'icon' => 'mdi-calendar-star', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE association_id = ? AND deleted_at IS NULL', [(int) ($user['association_id'] ?? 0)])],
                ['label' => 'Prochains événements', 'icon' => 'mdi-calendar-clock', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenements WHERE association_id = ? AND date_evenement >= CURDATE() AND deleted_at IS NULL', [(int) ($user['association_id'] ?? 0)])],
                ['label' => 'Participants cumulés', 'icon' => 'mdi-account-group', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenement_participant ep JOIN evenements e ON e.id = ep.evenement_id WHERE e.association_id = ?', [(int) ($user['association_id'] ?? 0)])],
                ['label' => 'Mes participations', 'icon' => 'mdi-account-check', 'value' => (int) Database::value('SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?', [$userId])],
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
            'association' => ! empty($user['association_id']) ? public_url('citoyen/association/' . (int) $user['association_id']) : null,
            'epic'        => ! empty($user['epic_id']) ? public_url('citoyen/epic/' . (int) $user['epic_id']) : null,
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

    // ── 2FA (Double authentification) ─────────────────────────

    public function show2fa(): never
    {
        $userId = Session::userId();
        $twoFactor = Database::one('SELECT * FROM two_factor WHERE user_id = ?', [$userId]);
        $recoveryCount = (int) Database::value(
            'SELECT COUNT(*) FROM two_factor_recovery_codes WHERE user_id = ? AND used_at IS NULL',
            [$userId]
        );

        $this->view('profile.2fa', [
            'twoFactor'     => $twoFactor,
            'recoveryCount' => $recoveryCount,
            'codeRequested' => Session::get('2fa_enable_pending'),
            'flashSuccess'  => Session::get('flash_success'),
            'errors'        => $this->errors(),
        ], $this->layoutForRole(Session::user()['role_user'] ?? 'wilaya'));
    }

    public function enable2fa(): never
    {
        $userId = Session::userId();
        $method = (string) input('method', 'email');

        $code = Security::genererCode2fa($userId);
        $this->envoyerCode2fa($userId, $code);

        Session::set('2fa_enable_pending', true);

        flash('success', __('auth.2fa_sent'));
        redirect('profile/2fa');
    }

    public function confirm2fa(): never
    {
        $userId = Session::userId();
        $code = trim((string) input('code', ''));

        if (! preg_match('/^\d{6}$/', $code)) {
            $this->backWithErrors(['code' => __('auth.2fa_invalid')]);
        }

        if (! Security::validerCode2fa($userId, $code)) {
            $this->backWithErrors(['code' => __('auth.2fa_invalid')]);
        }

        // Activer la 2FA
        Database::run(
            'UPDATE two_factor SET enabled = 1, confirmed = 1 WHERE user_id = ?',
            [$userId]
        );

        // Générer les codes de secours
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = strtoupper(bin2hex(random_bytes(4)));
            $recoveryCodes[] = $plain;
            Database::run(
                'INSERT INTO two_factor_recovery_codes (user_id, code_hash) VALUES (?, ?)',
                [$userId, password_hash($plain, PASSWORD_BCRYPT)]
            );
        }

        Security::evenement('tfa_enabled', '2FA activée (email)', 1, ['user_id' => $userId]);

        Session::forget('2fa_enable_pending');

        // Afficher les codes de secours une seule fois
        $this->view('profile.2fa-recovery', [
            'recoveryCodes' => $recoveryCodes,
        ], $this->layoutForRole(Session::user()['role_user'] ?? 'wilaya'));
        exit;
    }

    public function disable2fa(): never
    {
        $userId = Session::userId();
        $password = (string) input('password', '');

        if ($password === '') {
            $this->backWithErrors(['password' => 'Mot de passe requis.']);
        }

        $user = Database::one('SELECT password FROM users WHERE id = ?', [$userId]);
        if ($user === null || ! password_verify($password, $user['password'])) {
            $this->backWithErrors(['password' => 'Mot de passe incorrect.']);
        }

        Database::run('UPDATE two_factor SET enabled = 0, confirmed = 0 WHERE user_id = ?', [$userId]);
        Database::run('DELETE FROM two_factor_recovery_codes WHERE user_id = ?', [$userId]);

        Security::evenement('tfa_disabled', '2FA désactivée', 2, ['user_id' => $userId]);

        flash('success', 'Double authentification désactivée.');
        redirect('profile/2fa');
    }

    public function regenerateRecoveryCodes(): never
    {
        $userId = Session::userId();

        Database::run('DELETE FROM two_factor_recovery_codes WHERE user_id = ?', [$userId]);

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = strtoupper(bin2hex(random_bytes(4)));
            $recoveryCodes[] = $plain;
            Database::run(
                'INSERT INTO two_factor_recovery_codes (user_id, code_hash) VALUES (?, ?)',
                [$userId, password_hash($plain, PASSWORD_BCRYPT)]
            );
        }

        Security::evenement('tfa_recovery_regenerated', 'Codes de secours régénérés', 1, ['user_id' => $userId]);

        $this->view('profile.2fa-recovery', [
            'recoveryCodes' => $recoveryCodes,
            'regenerated'   => true,
        ], $this->layoutForRole(Session::user()['role_user'] ?? 'wilaya'));
        exit;
    }

    private function envoyerCode2fa(int $userId, string $code): void
    {
        $user = Database::one('SELECT email, prenom FROM users WHERE id = ?', [$userId]);
        if ($user === null || empty($user['email'])) {
            return;
        }

        \App\Helpers\Mailer::send2faCode((string) $user['email'], (string) ($user['prenom'] ?? ''), $code);
    }

    // ── 2FA TOTP (Authenticator) ─────────────────────────────

    public function totpSetup(): never
    {
        $userId = Session::userId();
        $user   = Database::one('SELECT email FROM users WHERE id = ?', [$userId]);

        $twoFactor = Database::one('SELECT * FROM two_factor WHERE user_id = ?', [$userId]);
        $isTotp = ($twoFactor['method'] ?? '') === 'authenticator' && !empty($twoFactor['confirmed']);

        if ($isTotp) {
            redirect('profile/2fa');
        }

        // Reuse existing secret if user refreshed the page (avoids invalidating scanned QR)
        $secret = Session::get('totp_pending_secret');
        if ($secret === null || ! is_string($secret) || strlen($secret) < 16) {
            $secret = Totp::generateSecret();
            Session::set('totp_pending_secret', $secret);
        }

        $email   = (string) ($user['email'] ?? '');
        $uri     = Totp::provisioningUri($secret, $email);
        $qrCode  = QrCodeGenerator::pngDataUri($uri, 200);

        $this->view('profile.2fa-totp-setup', [
            'secret'  => $secret,
            'qrCode'  => $qrCode,
            'errors'  => $this->errors(),
        ], $this->layoutForRole(Session::user()['role_user'] ?? 'wilaya'));
        exit;
    }

    public function totpEnable(): never
    {
        $userId = Session::userId();

        $secret = Session::get('totp_pending_secret');
        if ($secret === null) {
            flash('error', 'Session expirée. Réessayez.');
            redirect('profile/2fa');
        }

        $code = trim((string) input('code', ''));

        if (! preg_match('/^\d{6}$/', $code)) {
            $this->backWithErrors(['code' => 'Code invalide (6 chiffres requis).']);
        }

        if (! Totp::verify($secret, $code)) {
            $this->backWithErrors(['code' => 'Code incorrect. Vérifiez l\'heure de votre appareil.']);
        }

        // Activer
        Database::run(
            'INSERT INTO two_factor (user_id, method, enabled, confirmed, secret)
             VALUES (?, "authenticator", 1, 1, ?)
             ON DUPLICATE KEY UPDATE method = VALUES(method), enabled = 1, confirmed = 1, secret = VALUES(secret)',
            [$userId, $secret]
        );

        // Générer codes de secours
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = strtoupper(bin2hex(random_bytes(4)));
            $recoveryCodes[] = $plain;
            Database::run(
                'INSERT INTO two_factor_recovery_codes (user_id, code_hash) VALUES (?, ?)',
                [$userId, password_hash($plain, PASSWORD_BCRYPT)]
            );
        }

        Session::forget('totp_pending_secret');
        Security::evenement('tfa_totp_enabled', '2FA Authenticator activée', 1, ['user_id' => $userId]);

        $this->view('profile.2fa-recovery', [
            'recoveryCodes' => $recoveryCodes,
        ], $this->layoutForRole(Session::user()['role_user'] ?? 'wilaya'));
        exit;
    }

    public function totpConfirm(): never
    {
        $userId = Session::userId();

        $twoFactor = Database::one('SELECT secret, method FROM two_factor WHERE user_id = ? AND method = "authenticator"', [$userId]);
        if ($twoFactor === null) {
            $this->backWithErrors(['code' => 'Authenticator non configuré.']);
        }

        $code = trim((string) input('code', ''));

        if (! Totp::verify((string) $twoFactor['secret'], $code)) {
            $this->backWithErrors(['code' => 'Code incorrect.']);
        }

        Session::forget('auth_totp');
        Session::set('user_id', $userId);
        Session::set('logged_in', true);
        Session::set('role_user', Database::one('SELECT role_user FROM users WHERE id = ?', [$userId])['role_user'] ?? 'wilaya');
        Session::persistToDatabase();

        Security::evenement('tfa_success', '2FA Authenticator validé', 1, ['user_id' => $userId]);
        AuditLog::log('login', 'user', $userId, null, ['2fa' => 'authenticator']);

        $next = Session::get('login_next', '/');
        Session::forget('login_next');
        redirect($next);
    }
}
