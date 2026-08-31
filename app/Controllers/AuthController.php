<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\Mailer;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\Totp;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;

final class AuthController extends Controller
{
    public function showLogin(): never
    {
        if (Session::isLogged()) {
            redirect('/');
        }

        // Ne jamais servir une page de connexion en cache (fichier statique / proxy /
        // navigateur) : un formulaire périmé porterait un jeton CSRF incohérent avec la
        // session → erreur « Session expirée » à la soumission.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $this->view('auth.login', [
            'errors' => $this->errors(),
            'next'   => $this->safeNext(),
        ], 'guest');
    }

    /**
     * Chemin de retour sûr (interne uniquement) pour reprendre un flux
     * interrompu par la connexion (ex. scan de QR Code).
     */
    private function safeNext(): string
    {
        $next = (string) ($_GET['next'] ?? '');

        if ($next === '') {
            return '';
        }

        // Anti open-redirect : uniquement des chemins internes.
        if (! str_starts_with($next, '/') || str_starts_with($next, '//') || preg_match('#^[a-z]+:#i', $next)) {
            return '';
        }

        return $next;
    }

    public function login(): never
    {
        if (! $this->rateLimit('login', 10, 300)) {
            $this->backWithErrors(['global' => __('auth.too_many')]);
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $user = Database::one(
            'SELECT u.*, a.valide AS association_valide FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             WHERE u.email = ? AND u.is_active = 1 AND u.deleted_at IS NULL',
            [mb_strtolower(trim((string) $data['email']))]
        );

        if ($user === null || ! password_verify((string) $data['password'], (string) $user['password'])) {
            Security::evenement('login_fail', 'Échec de connexion : ' . mb_strtolower(trim((string) ($data['email'] ?? ''))), 2);
            Security::limiteTentatives('login:' . mb_strtolower(trim((string) ($data['email'] ?? ''))), (int) Security::param('securite.tentatives_max', 5), (int) Security::param('securite.blocage_duree_min', 10));
            $this->backWithErrors(['email' => __('auth.invalid')], $data);
        }

        // Compte association : autoriser la connexion si une demande est en cours (ou refusée, pour consulter le motif)
        if (
            ($user['role_user'] ?? '') === 'association'
            && (int) ($user['association_valide'] ?? 0) === 0
            && (int) ($user['association_id'] ?? 0) === 0
        ) {
            $hasRequest = Database::exists(
                "SELECT 1 FROM association_requests WHERE user_id = ? AND status IN ('pending', 'rejected')",
                [(int) $user['id']]
            );

            if (! $hasRequest) {
                $this->backWithErrors(['email' => __('auth.association_pending')], $data);
            }
        }

        // Défi 2FA : si le paramètre global est actif et que l'utilisateur
        // a activé la double authentification, on ne connecte pas encore —
        // un code à usage unique est généré puis envoyé par email (ou TOTP).
        if ($this->requires2fa((int) $user['id'])) {
            $method = $this->get2faMethod((int) $user['id']);

            Session::set('auth_2fa', [
                'user_id' => (int) $user['id'],
                'next'    => trim((string) ($_POST['next'] ?? '')),
                'expires' => time() + 300,
                'method'  => $method,
            ]);

            if ($method === 'authenticator') {
                redirect('auth/verify-2fa');
            }

            $code = Security::genererCode2fa((int) $user['id']);
            $this->envoyerCode2fa((int) $user['id'], $code);
            redirect('auth/verify-2fa');
        }

        Csrf::rotate();
        Session::login((int) $user['id']);
        Session::set('user', $user);
        Session::set('user_roles', [(string) ($user['role_user'] ?? 'citoyen')]);
        Rbac::loadPermissions((int) $user['id']);

        Session::persistToDatabase();
        Security::evenement('login_success', 'Connexion réussie', 1, ['role' => $user['role_user'] ?? 'citoyen']);
        AuditLog::log('login', 'user', (int) $user['id']);

        $next = trim((string) ($_POST['next'] ?? ''));
        if ($next !== '' && str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! preg_match('#^[a-z]+:#i', $next)) {
            // Les rôles back-office ne doivent pas atterrir sur des pages citoyen.
            $role = Rbac::role($user);
            $isCitoyenOnly = str_starts_with($next, '/citoyen') || str_starts_with($next, '/qrcode');
            $isBackOffice = in_array($role, ['wilaya', 'chef_section', 'chef_unite', 'association', 'membre', 'epic'], true);

            if (! ($isCitoyenOnly && $isBackOffice)) {
                redirect($next);
            }
        }

        redirect(dashboard_path());
    }

    public function showVerify2fa(): never
    {
        $pending = Session::get('auth_2fa');

        if (! is_array($pending) || Session::isLogged() || (int) ($pending['expires'] ?? 0) < time()) {
            Session::forget('auth_2fa');
            redirect('auth/login');
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $this->view('auth.verify-2fa', [
            'errors' => $this->errors(),
            'method' => $pending['method'] ?? 'email',
        ], 'guest');
    }

    public function verify2fa(): never
    {
        if (! $this->rateLimit('verify_2fa', 10, 300)) {
            flash('error', __('auth.too_many'));
            redirect('auth/verify-2fa');
        }

        $pending = Session::get('auth_2fa');

        if (! is_array($pending) || (int) ($pending['expires'] ?? 0) < time()) {
            Session::forget('auth_2fa');
            flash('error', __('auth.2fa_expired'));
            redirect('auth/login');
        }

        $data = all_input();
        $validator = Validator::make($data, ['code' => 'required|numeric']);

        $code = trim((string) ($data['code'] ?? ''));
        if ($validator->fails() || ! preg_match('/^\d{6}$/', $code)) {
            $this->backWithErrors(['code' => __('auth.2fa_invalid')]);
        }

        $userId = (int) ($pending['user_id'] ?? 0);
        $method = $pending['method'] ?? 'email';

        if ($method === 'authenticator') {
            $twoFactor = Database::one('SELECT secret FROM two_factor WHERE user_id = ? AND method = "authenticator" AND enabled = 1', [$userId]);
            if ($twoFactor === null || ! Totp::verify((string) $twoFactor['secret'], $code)) {
                Security::evenement('tfa_fail', 'Code TOTP invalide', 2, ['user_id' => $userId]);
                $this->backWithErrors(['code' => __('auth.2fa_invalid')]);
            }
        } else {
            if (! Security::validerCode2fa($userId, $code)) {
                $this->backWithErrors(['code' => __('auth.2fa_invalid')]);
            }
        }

        // Code valide : compléter la connexion.
        Session::forget('auth_2fa');
        Csrf::rotate();
        Session::login($userId);

        $user = Database::one(
            'SELECT u.*, a.valide AS association_valide FROM users u
             LEFT JOIN associations a ON a.id = u.association_id
             WHERE u.id = ?',
            [$userId]
        );
        Session::set('user', $user);
        Session::set('user_roles', [(string) ($user['role_user'] ?? 'citoyen')]);
        Rbac::loadPermissions($userId);

        Session::persistToDatabase();
        Security::evenement('tfa_success', '2FA validé, connexion complétée', 1, ['user_id' => $userId]);
        AuditLog::log('login', 'user', $userId, null, ['2fa' => true]);

        $next = trim((string) ($pending['next'] ?? ''));
        if ($next !== '' && str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! preg_match('#^[a-z]+:#i', $next)) {
            $role = Rbac::role($user);
            $isCitoyenOnly = str_starts_with($next, '/citoyen') || str_starts_with($next, '/qrcode');
            $isBackOffice = in_array($role, ['wilaya', 'chef_section', 'chef_unite', 'association', 'membre', 'epic'], true);

            if (! ($isCitoyenOnly && $isBackOffice)) {
                redirect($next);
            }
        }

        redirect(dashboard_path());
    }

    /**
     * Le défi 2FA s'applique uniquement si le paramètre global est actif
     * ET que l'utilisateur a activé la double authentification.
     */
    private function requires2fa(int $userId): bool
    {
        if (! Security::param('securite.tfa_obligatoire', false)) {
            return false;
        }

        return Database::exists(
            'SELECT id FROM two_factor WHERE user_id = ? AND enabled = 1',
            [$userId]
        );
    }

    /**
     * Retourne la méthode 2FA de l'utilisateur (email|authenticator).
     */
    private function get2faMethod(int $userId): string
    {
        $row = Database::one(
            'SELECT method FROM two_factor WHERE user_id = ? AND enabled = 1',
            [$userId]
        );

        return ($row !== null && ($row['method'] ?? '') === 'authenticator') ? 'authenticator' : 'email';
    }

    /**
     * Envoie le code 2FA par email (+ notification in-app).
     */
    private function envoyerCode2fa(int $userId, string $code): void
    {
        $user = Database::one('SELECT email, prenom FROM users WHERE id = ?', [$userId]);

        if ($user === null || empty($user['email'])) {
            return;
        }

        Mailer::send2faCode((string) $user['email'], (string) ($user['prenom'] ?? ''), $code);

        Notification::send($userId, __('auth.2fa_code'), __('auth.2fa_sent') . ' Code : ' . $code, 'tfa_code');

        if (Mailer::lastFailed()) {
            Security::evenement('tfa_mail_fail', 'Échec d\'envoi du code 2FA', 2, ['user_id' => $userId], $userId);
        }
    }

    public function showRegister(): never
    {
        if (Session::isLogged()) {
            redirect('/');
        }

        $this->view('auth.register', [
            'errors' => $this->errors(),
            'next'   => $this->safeNext(),
        ], 'guest');
    }

    public function register(): never
    {
        $data = all_input();
        $validator = Validator::make($data, [
            'nom'      => 'required|string|max:50',
            'prenom'   => 'required|string|max:50',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'telephone' => 'required|phone',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $userId = Database::insert('users', [
            'nom'      => trim((string) $data['nom']),
            'prenom'   => trim((string) $data['prenom']),
            'email'    => mb_strtolower(trim((string) $data['email'])),
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role_user' => 'citoyen',
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'is_active' => 1,
        ]);

        Database::insert('user_roles', [
            'user_id' => $userId,
            'role_id' => (int) Database::value('SELECT id FROM roles WHERE nom = ?', ['citoyen']),
        ]);

        // Préférences par défaut (notifications email activées)
        Database::insert('user_preferences', [
            'user_id'     => $userId,
            'notif_email' => 1,
            'notif_inapp' => 1,
        ]);

        AuditLog::log('register', 'user', $userId, null, ['email' => mb_strtolower(trim((string) $data['email']))]);

        // Email de bienvenue (asynchrone — on ne bloque pas l'inscription)
        $prenom = trim((string) $data['prenom']);
        $emailAddr = mb_strtolower(trim((string) $data['email']));
        Mailer::sendWelcomeEmail($emailAddr, $prenom, 'citoyen');

        // Après inscription, on envoie le nouveau citoyen sur la page de
        // connexion en conservant la destination d'origine (scan, etc.).
        $next = trim((string) ($data['next'] ?? ''));
        flash('success', __('auth.register_success'));

        if ($next !== '' && str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! preg_match('#^[a-z]+:#i', $next)) {
            redirect('auth/login?next=' . urlencode($next));
        }

        redirect('auth/login');
    }

    public function showAssociationRegister(): never
    {
        if (Session::isLogged()) {
            redirect('/');
        }

        $dairas = Database::all(
            'SELECT ca.id, ca.nom, ca.nom_ar, ca.code
             FROM ca ca
             WHERE ca.is_active = 1
             ORDER BY ca.nom'
        );
        $communesByDaira = [];
        foreach ($dairas as $d) {
            $communesByDaira[$d['id']] = Database::all(
                'SELECT id, nom, nom_ar FROM commune WHERE ca_id = ? AND is_active = 1 ORDER BY nom',
                [(int) $d['id']]
            );
        }

        $this->view('auth.register-association', [
            'errors'  => $this->errors(),
            'old'     => $_SESSION['_old'] ?? [],
            'dairas'  => $dairas,
            'communesByDaira' => $communesByDaira,
        ], 'guest');
    }

    /**
     * Soumission du formulaire public — crée une DEMANDE d'inscription
     * (statut « pending ») que la Wilaya traite depuis /admin/association-requests.
     */
    public function associationRegister(): never
    {
        $data = all_input();

        $validator = Validator::make($data, [
            'association_name'     => 'required|string|max:150',
            'approval_number'      => 'required|string|max:50',
            'activity_domain'      => 'nullable|string|max:100',
            'description'          => 'nullable|string|max:2000',
            'address'              => 'nullable|string|max:255',
            'daira_id'             => 'required|integer',
            'commune'              => 'required|string|max:100',
            'wilaya'               => 'nullable|string|max:100',
            'phone'                => 'required|string|max:20',
            'email'                => 'required|email|max:100',
            'president_lastname'   => 'required|string|max:100',
            'president_firstname'  => 'required|string|max:100',
            'president_phone'      => 'required|string|max:20',
            'president_email'      => 'required|email|max:100',
            'password'             => 'required|min:8|confirmed',
        ], [
            'association_name.required' => 'Le nom de l\'association est obligatoire.',
            'approval_number.required'  => 'Le numéro d\'agrément est obligatoire.',
            'phone.required'            => 'Le téléphone est obligatoire.',
            'email.required'            => 'L\'email est obligatoire.',
            'email.email'               => 'L\'email est invalide.',
            'president_lastname.required'  => 'Le nom du président est obligatoire.',
            'president_firstname.required' => 'Le prénom du président est obligatoire.',
            'president_phone.required'     => 'Le téléphone du président est obligatoire.',
            'president_email.required'     => 'L\'email du président est obligatoire.',
            'president_email.email'        => 'L\'email du président est invalide.',
            'password.required'            => 'Le mot de passe est obligatoire.',
            'password.min'                 => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'           => 'Les mots de passe ne correspondent pas.',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $email          = mb_strtolower(trim((string) $data['email']));
        $presidentEmail = mb_strtolower(trim((string) $data['president_email']));

        if ($this->emailTaken($email)) {
            $this->backWithErrors(['email' => 'Cette adresse email est déjà utilisée.'], $data);
        }
        if ($this->emailTaken($presidentEmail)) {
            $this->backWithErrors(['president_email' => 'Cette adresse email est déjà utilisée.'], $data);
        }

        // Upload de l'agrément (optionnel — image ou PDF)
        $approvalFile = null;
        if (! empty($_FILES['approval_file']['name']) && $_FILES['approval_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = config('paths.uploads.agrements', public_path('uploads/agrements'));
            $maxSize   = (int) config('security.upload_max', 5242880);
            $result    = UploadHelper::uploadDocument($_FILES['approval_file'], $uploadDir, $maxSize);
            if ($result['success']) {
                $approvalFile = $result['path'];
            } else {
                $this->backWithErrors(['approval_file' => $result['error']], $data);
            }
        }

        $hashedPassword = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        $nom            = trim((string) $data['president_lastname']);
        $prenom         = trim((string) $data['president_firstname']);

        Database::transaction(function () use ($data, $email, $presidentEmail, $approvalFile, $hashedPassword, $nom, $prenom, &$id, &$userId) {
            // 1. Créer le compte président dès l'inscription (association_id NULL tant que non validée)
            $userId = Database::insert('users', [
                'nom'            => $nom,
                'prenom'         => $prenom,
                'email'          => $presidentEmail,
                'password'       => $hashedPassword,
                'role_user'      => 'association',
                'telephone'      => trim((string) $data['president_phone']),
                'association_id' => null,
                'is_active'      => 1,
            ]);

            // Lier le rôle RBAC association
            $associationRoleId = (int) Database::value('SELECT id FROM roles WHERE nom = ?', ['association']);
            if ($associationRoleId > 0) {
                Database::insert('user_roles', [
                    'user_id' => $userId,
                    'role_id' => $associationRoleId,
                ]);
            }

            // 2. Créer la demande d'inscription liée au compte
            $id = Database::insert('association_requests', [
                'user_id'             => $userId,
                'association_name'    => trim((string) $data['association_name']),
                'approval_number'     => trim((string) $data['approval_number']),
                'activity_domain'     => trim((string) ($data['activity_domain'] ?? '')),
                'description'         => trim((string) ($data['description'] ?? '')),
                'address'             => trim((string) ($data['address'] ?? '')),
                'commune'             => trim((string) ($data['commune'] ?? '')),
                'wilaya'              => trim((string) ($data['wilaya'] ?? '')),
                'phone'               => trim((string) $data['phone']),
                'email'               => $email,
                'president_lastname'  => $nom,
                'president_firstname' => $prenom,
                'president_phone'     => trim((string) $data['president_phone']),
                'president_email'     => $presidentEmail,
                'approval_file'       => $approvalFile,
                'status'              => 'pending',
            ]);
        });

        AuditLog::log('association_request_created', 'association_requests', $id, null, [
            'association_name' => trim((string) $data['association_name']),
            'email'            => $email,
        ]);

        // Préférences par défaut
        Database::insert('user_preferences', [
            'user_id'     => $userId,
            'notif_email' => 1,
            'notif_inapp' => 1,
        ]);

        // Email de bienvenue
        Mailer::sendWelcomeEmail($presidentEmail, $prenom, 'association');

        // Notifier la Wilaya : nouvelle demande à traiter
        Notification::sendToRole('wilaya', 'Nouvelle demande d\'inscription', 'Demande de « ' . trim((string) $data['association_name']) . ' » en attente de validation.', 'association_request', [
            'request_id' => $id,
            'nom'        => trim((string) $data['association_name']),
        ]);

        flash('success', __('associations.inscription_success'));
        redirect('auth/login');
    }

    /**
     * Vérifie qu'une adresse email n'est pas déjà utilisée
     * (comptes, associations ou demandes en attente/approuvées).
     */
    private function emailTaken(string $email): bool
    {
        return Database::exists('SELECT id FROM users WHERE email = ?', [$email])
            || Database::exists('SELECT id FROM associations WHERE email = ?', [$email])
            || Database::exists(
                "SELECT id FROM association_requests WHERE email = ? AND status <> 'rejected'",
                [$email]
            );
    }

    public function logout(): never
    {
        AuditLog::log('logout', 'user', Session::userId());
        Session::logout();
        redirect('/');
    }

    public function showForgot(): never
    {
        $this->view('auth.forgot', ['errors' => $this->errors()], 'guest');
    }

    public function forgot(): never
    {
        $this->rateLimit('forgot', 5, 300);

        $data = all_input();
        $validator = Validator::make($data, ['email' => 'required|email']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $email = mb_strtolower(trim((string) $data['email']));
        $user = Database::one('SELECT id, email FROM users WHERE email = ?', [$email]);

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            Database::run(
                'INSERT INTO password_resets (email, token) VALUES (?, ?)',
                [$user['email'], $tokenHash]
            );

            AuditLog::log('password_reset_request', 'user', (int) $user['id'], null, ['email' => $email]);
            Mailer::sendResetLink($user['email'], $token);
        }

        flash('success', __('auth.forgot_sent'));
        redirect('auth/login');
    }

    public function showReset(string $token): never
    {
        $tokenHash = hash('sha256', $token);
        $reset = Database::one('SELECT * FROM password_resets WHERE token = ?', [$tokenHash]);

        if ($reset === null || strtotime((string) $reset['created_at']) < time() - 3600) {
            flash('error', __('auth.reset_invalid'));
            redirect('auth/forgot');
        }

        $this->view('auth.reset', ['token' => $token, 'errors' => $this->errors()], 'guest');
    }

    public function reset(string $token): never
    {
        $tokenHash = hash('sha256', $token);
        $reset = Database::one('SELECT * FROM password_resets WHERE token = ?', [$tokenHash]);

        if ($reset === null || strtotime((string) $reset['created_at']) < time() - 3600) {
            flash('error', __('auth.reset_invalid'));
            redirect('auth/forgot');
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $newHash = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        Database::run(
            'UPDATE users SET password = ? WHERE email = ?',
            [$newHash, $reset['email']]
        );

        Database::delete('password_resets', 'token = ?', [$tokenHash]);

        $user = Database::one('SELECT id FROM users WHERE email = ?', [$reset['email']]);
        if ($user !== null) {
            Database::delete('sessions', 'user_id = ?', [(int) $user['id']]);
        }

        AuditLog::log('password_reset_success', 'user', (int) ($user['id'] ?? 0), null, ['email' => $reset['email']]);

        flash('success', __('auth.reset_success'));
        redirect('auth/login');
    }
}
