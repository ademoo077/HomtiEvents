<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\UploadHelper;
use App\Helpers\Validator;

final class AuthController extends Controller
{
    public function showLogin(): never
    {
        if (Session::isLogged()) {
            redirect('/');
        }

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

        Csrf::rotate();
        Session::login((int) $user['id']);
        Session::set('user', $user);
        Session::set('user_roles', [(string) ($user['role_user'] ?? 'citoyen')]);
        Rbac::loadPermissions((int) $user['id']);

        AuditLog::log('login', 'user', (int) $user['id']);

        $next = trim((string) ($_POST['next'] ?? ''));
        if ($next !== '' && str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! preg_match('#^[a-z]+:#i', $next)) {
            redirect($next);
        }

        redirect(dashboard_path());
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

        AuditLog::log('register', 'user', $userId, null, ['email' => mb_strtolower(trim((string) $data['email']))]);

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

        Database::transaction(function () use ($data, $email, $presidentEmail, $approvalFile, $hashedPassword, $nom, $prenom, &$id) {
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
        $data = all_input();
        $validator = Validator::make($data, ['email' => 'required|email']);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $user = Database::one('SELECT id, email FROM users WHERE email = ?', [mb_strtolower(trim((string) $data['email']))]);

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            Database::run(
                'INSERT INTO password_resets (email, token) VALUES (?, ?)',
                [$user['email'], $token]
            );

            // En production : envoyer un email. En dev, on le journalise.
            AuditLog::log('password_reset_request', 'user', (int) $user['id'], null, ['token' => $token]);
        }

        flash('success', __('auth.forgot_sent'));
        redirect('auth/login');
    }

    public function showReset(string $token): never
    {
        $reset = Database::one('SELECT * FROM password_resets WHERE token = ?', [$token]);

        if ($reset === null || strtotime((string) $reset['created_at']) < time() - 3600) {
            flash('error', __('auth.reset_invalid'));
            redirect('auth/forgot');
        }

        $this->view('auth.reset', ['token' => $token, 'errors' => $this->errors()], 'guest');
    }

    public function reset(string $token): never
    {
        $reset = Database::one('SELECT * FROM password_resets WHERE token = ?', [$token]);

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

        Database::run(
            'UPDATE users SET password = ? WHERE email = ?',
            [password_hash((string) $data['password'], PASSWORD_BCRYPT), $reset['email']]
        );

        Database::delete('password_resets', 'token = ?', [$token]);

        flash('success', __('auth.reset_success'));
        redirect('auth/login');
    }
}
