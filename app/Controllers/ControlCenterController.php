<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\BusinessRules;
use App\Helpers\ControlCenter;
use App\Helpers\Database;
use App\Helpers\RoutingService;
use App\Helpers\Rbac;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\Validator;

/**
 * Control Center — supervision et contrôle centralisé de la plateforme.
 *
 * Pilote : modules, utilisateurs, associations/EPIC, règles métier,
 * paramètres SaaS, sécurité (2FA / IP / sessions), audit et supervision.
 */
final class ControlCenterController extends Controller
{
    // ── Vue principale du Control Center ─────────────────────────────
    public function index(): never
    {
        $this->requirePermission('control.view');

        $this->view('control.index', [
            'modules'    => ControlCenter::modules(),
            'regles'     => BusinessRules::liste(),
            'securite'   => [
                'sessions' => Security::sessionsActives(),
                'evenements' => $this->securityEvents(30),
            ],
            'statistiques' => [
                'utilisateurs'     => (int) Database::value('SELECT COUNT(*) FROM users'),
                'suspendus'        => (int) Database::value("SELECT COUNT(*) FROM users WHERE status != 'actif'"),
                'associations'     => (int) Database::value('SELECT COUNT(*) FROM associations'),
                'evenements'       => (int) Database::value('SELECT COUNT(*) FROM evenements'),
                'audit'            => AuditLog::count(),
            ],
            'users'          => $this->allUsers(),
            'roles'          => Database::all('SELECT * FROM roles ORDER BY niveau'),
            'associationsList' => ControlCenter::allAssociations(),
            'epics'          => ControlCenter::allEpics(),
            'communes'       => ControlCenter::allCommunes(),
            'settings'       => $this->settingsMap(),
            'auditLogs'      => AuditLog::all('', 100),
            'pendingContent' => Database::all(
                "SELECT c.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
                 FROM content c
                 LEFT JOIN users u ON u.id = c.user_id
                 WHERE c.statut = 'brouillon'
                 ORDER BY c.created_at DESC LIMIT 50"
            ),
        ], 'dashboard-futur');
    }

    // ── Activation / désactivation des modules ───────────────────────
    public function toggleModule(): never
    {
        $this->requirePermission('control.modules');

        $cle  = (string) input('cle');
        $actif = (int) input('actif', 0) === 1;

        ControlCenter::toggleModule($cle, $actif);

        json_response(['success' => true]);
    }

    // ── Contrôle des utilisateurs ────────────────────────────────────
    public function utilisateurs(): never
    {
        $this->requirePermission('control.users');

        $this->view('control.utilisateurs', [
            'users' => $this->allUsers(),
            'roles' => Database::all('SELECT * FROM roles ORDER BY niveau'),
        ], 'dashboard-futur');
    }

    public function utilisateurAction(): never
    {
        $this->requirePermission('control.users');

        $id     = (int) input('id');
        $action = (string) input('action');
        $valeur = input('valeur');

        match ($action) {
            'statut'      => ControlCenter::modifierStatutUtilisateur($id, (string) $valeur),
            'forcer_logout' => ControlCenter::forcerDeconnexion($id),
            'role'        => ControlCenter::changerRole(
                $id,
                (string) input('role'),
                (int) input('association_id', 0),
                (int) input('epic_id', 0)
            ),
            'reset_password' => $this->resetMotDePasse($id),
            default       => abort(422, 'Action inconnue.'),
        };

        json_response(['success' => true]);
    }

    // ── Associations & EPIC ──────────────────────────────────────────
    public function associations(): never
    {
        $this->requirePermission('control.associations');

        $this->view('control.associations', [
            'associations' => Database::all('SELECT a.*, COUNT(e.id) AS nb_evenements FROM associations a LEFT JOIN evenements e ON e.association_id = a.id GROUP BY a.id ORDER BY a.created_at DESC'),
        ], 'dashboard-futur');
    }

    public function associationAction(): never
    {
        $this->requirePermission('control.associations');

        $id   = (int) input('id');
        $action = (string) input('action');

        if ($action === 'suspendre') {
            ControlCenter::suspendreAssociation($id, true);
        } elseif ($action === 'restaurer') {
            ControlCenter::suspendreAssociation($id, false);
        } else {
            abort(422, 'Action inconnue.');
        }

        json_response(['success' => true]);
    }

    public function associationCreate(): never
    {
        $this->requirePermission('control.associations');

        $this->view('control.association-form', [
            'mode'       => 'create',
            'association' => null,
            'errors'     => $this->errors(),
        ], 'dashboard-futur');
    }

    public function associationStore(): never
    {
        $this->requirePermission('control.associations');

        $data = input()->all();

        $validator = Validator::make($data, [
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email|unique:associations,email',
            'telephone' => 'nullable|phone',
            'wilaya'    => 'nullable|string|max:50',
            'adresse'   => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('associations', [
            'nom'       => trim((string) ($data['nom'] ?? '')),
            'email'     => mb_strtolower(trim((string) ($data['email'] ?? ''))),
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'wilaya'    => trim((string) ($data['wilaya'] ?? '')),
            'adresse'   => trim((string) ($data['adresse'] ?? '')),
            'valide'    => 0,
        ]);

        flash('success', 'Association créée avec succès.');
        redirect('control?tab=associations');
    }

    public function associationEdit(string $id): never
    {
        $this->requirePermission('control.associations');

        $association = Database::one('SELECT * FROM associations WHERE id = ?', [(int) $id]);
        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $this->view('control.association-form', [
            'mode'        => 'edit',
            'association' => $association,
            'errors'      => $this->errors(),
        ], 'dashboard-futur');
    }

    public function associationUpdate(string $id): never
    {
        $this->requirePermission('control.associations');

        $associationId = (int) $id;
        $association   = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);
        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $data = input()->all();

        $validator = Validator::make($data, [
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email|unique:associations,email,' . $associationId,
            'telephone' => 'nullable|phone',
            'wilaya'    => 'nullable|string|max:50',
            'adresse'   => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::run(
            'UPDATE associations SET nom = ?, email = ?, telephone = ?, wilaya = ?, adresse = ? WHERE id = ?',
            [
                trim((string) ($data['nom'] ?? '')),
                mb_strtolower(trim((string) ($data['email'] ?? ''))),
                trim((string) ($data['telephone'] ?? '')),
                trim((string) ($data['wilaya'] ?? '')),
                trim((string) ($data['adresse'] ?? '')),
                $associationId,
            ]
        );

        flash('success', 'Association mise à jour.');
        redirect('control?tab=associations');
    }

    public function associationDelete(string $id): never
    {
        $this->requirePermission('control.associations');

        $associationId = (int) $id;
        $association   = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);
        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        Database::run('DELETE FROM associations WHERE id = ?', [$associationId]);

        AuditLog::log('association.delete', 'association', $associationId, ['nom' => $association['nom'] ?? '']);

        flash('success', 'Association supprimée.');
        redirect('control?tab=associations');
    }

    // ── Moteur de règles métier ──────────────────────────────────────
    public function regles(): never
    {
        $this->requirePermission('control.rules');

        $this->view('control.regles', [
            'regles' => BusinessRules::liste(),
        ], 'dashboard-futur');
    }

    public function regleEnregistrer(): never
    {
        $this->requirePermission('control.rules');

        $data = [
            'cle'        => (string) input('cle'),
            'nom'        => (string) input('nom'),
            'description'=> input('description'),
            'activite'   => (string) input('activite', 'blocage'),
            'portee'     => (string) input('portee', 'global'),
            'cible'      => input('cible'),
            'condition_sql' => input('condition_sql'),
            'actif'      => (int) input('actif', 1),
        ];

        BusinessRules::enregistrer($data, input('id') ? (int) input('id') : null);

        json_response(['success' => true]);
    }

    public function regleBasculer(): never
    {
        $this->requirePermission('control.rules');

        BusinessRules::basculer((int) input('id'), (int) input('actif', 0) === 1);

        json_response(['success' => true]);
    }

    // ── Paramètres système (SaaS) ────────────────────────────────────
    public function parametres(): never
    {
        $this->requirePermission('control.settings');

        $this->view('control.parametres', [
            'parametres' => $this->allSettings(),
            'groupes'    => $this->settingsGroupes(),
        ], 'dashboard-futur');
    }

    public function parametreEnregistrer(): never
    {
        $this->requirePermission('control.settings');

        $groupe = (string) input('groupe');
        $apa    = input('cle');   // array
        $vap    = input('valeur');// array

        $cles = is_array($apa) ? $apa : [];
        $vals = is_array($vap) ? $vap : [];

        foreach ($cles as $i => $cle) {
            $val = $vals[$i] ?? '';
            $this->saveSetting($groupe, (string) $cle, (string) $val);
        }

        json_response(['success' => true]);
    }

    // ── Audit & supervision ──────────────────────────────────────────
    public function audit(): never
    {
        $this->requirePermission('control.audit');

        $this->view('control.audit', [
            'logs' => AuditLog::all((string) input('q', ''), 200),
        ], 'dashboard-futur');
    }

    public function auditExport(): never
    {
        $this->requirePermission('control.audit');

        $logs = AuditLog::all((string) input('q', ''), 10000);

        $this->csv('audit_export.csv', ['ID', 'Date', 'Utilisateur', 'Role', 'Action', 'Module', 'ID Module', 'IP', 'Statut'], array_map(static function (array $l): array {
            return [
                (int) $l['id'],
                $l['created_at'],
                ($l['nom'] ?? '') . ' ' . ($l['prenom'] ?? ''),
                $l['role'] ?? '',
                $l['action'],
                $l['modele'],
                $l['modele_id'],
                $l['ip_address'],
                'succes',
            ];
        }, $logs));
    }

    // ── Supervision temps réel (API JSON) ────────────────────────────
    public function supervision(): never
    {
        $this->requirePermission('control.view');

        json_response([
            'success'  => true,
            'sessions' => count(Security::sessionsActives()),
            'suspects' => (int) Database::value("SELECT COUNT(*) FROM security_events WHERE type IN ('login_fail','ip_blocked','blocked_action') AND status = 'open'"),
            'evenements' => $this->securityEventsJson(),
            'modules'  => ControlCenter::modules(),
            'regles_actives' => (int) Database::value('SELECT COUNT(*) FROM system_rules WHERE actif = 1'),
        ]);
    }

    // ── Content Validation Workflow ──────────────────────
public function contentList(): never
{
    $this->requirePermission('control.content');

    $statut = input('statut', 'EN_ATTENTE');
    $modele = input('modele', '');

    $sql = 'SELECT cv.*, m.nom AS modele_nom, u.nom AS propose_par_nom
            FROM content_validations cv
            LEFT JOIN users u ON u.id = cv.proposer_par
            LEFT JOIN (SELECT "evenement" AS modele, id, adresse AS nom FROM evenements WHERE deleted_at IS NULL
                       UNION ALL SELECT "album" AS modele, id, titre AS nom FROM albums
                       UNION ALL SELECT "association" AS modele, id, nom AS nom FROM associations) m
                    ON m.modele = cv.modele AND m.id = cv.modele_id
            WHERE 1 = 1';
    $params = [];

    if ($statut !== 'tous') {
        $sql .= ' AND cv.statut = ?';
        $params[] = $statut;
    }

    if ($modele !== '') {
        $sql .= ' AND cv.modele = ?';
        $params[] = $modele;
    }

    $sql .= ' ORDER BY cv.created_at DESC LIMIT 100';

    $this->view('control.content', [
        'items'  => Database::all($sql, $params),
        'statuts'=> ['EN_ATTENTE', 'PUBLIE', 'REJETE', 'BROUILLON'],
        'modeles'=> ['evenement', 'album', 'association'],
        'currentStatut' => $statut,
        'currentModele' => $modele,
    ], 'dashboard-futur');
}

public function contentApprove(): never
{
    $this->requirePermission('control.content');

    $id = (int) input('id');
    $motif = trim((string) input('motif', ''));

    $item = Database::one('SELECT * FROM content_validations WHERE id = ?', [$id]);

    if ($item === null) {
        json_response(['success' => false, 'error' => 'Contenu introuvable.']);
    }

    ControlAction::approveContent($item['modele'], (int) $item['modele_id'], $motif);

    json_response(['success' => true, 'message' => 'Contenu approuvé et publié.']);
}

public function contentReject(): never
{
    $this->requirePermission('control.content');

    $id = (int) input('id');
    $motif = trim((string) input('motif', 'Contenu non conforme'));

    $item = Database::one('SELECT * FROM content_validations WHERE id = ?', [$id]);

    if ($item === null) {
        json_response(['success' => false, 'error' => 'Contenu introuvable.']);
    }

    ControlAction::rejectContent($item['modele'], (int) $item['modele_id'], $motif);

    json_response(['success' => true, 'message' => 'Contenu rejeté.']);
}

public function contentPublish(): never
{
    $this->requirePermission('control.content');

    $id = (int) input('id');

    $item = Database::one('SELECT * FROM content_validations WHERE id = ?', [$id]);

    if ($item === null) {
        json_response(['success' => false, 'error' => 'Contenu introuvable.']);
    }

    ControlAction::validateContent($item['modele'], (int) $item['modele_id'], 'PUBLIE');

    json_response(['success' => true, 'message' => 'Contenu publié.']);
}

// ── Security ────────────────────────────────────────────────
public function securityRevoke(): never
{
    $this->requirePermission('control.security');

    $sessionId = (int) input('session_id');

    Database::run('DELETE FROM sessions WHERE id = ?', [$sessionId]);

    AuditLog::log('security.revoke', 'session', $sessionId);

    flash('success', 'Session révoquée.');
    redirect('control?tab=security');
}

// ── User Control ──────────────────────────────────────
public function users(): never
{
    $this->requirePermission('control.users');

    $this->view('control.utilisateurs', [
        'users' => $this->allUsers(),
        'roles' => Database::all('SELECT * FROM roles ORDER BY niveau'),
    ], 'dashboard-futur');
}

public function userAction(): never
{
    $this->requirePermission('control.users');

    $id     = (int) input('id');
    $action = (string) input('action');
    $valeur = input('valeur');

    match ($action) {
        'statut'         => ControlCenter::modifierStatutUtilisateur($id, (string) $valeur),
        'forcer_logout'  => ControlCenter::forcerDeconnexion($id),
        'role'           => ControlCenter::changerRole(
            $id,
            (string) input('role'),
            (int) input('association_id', 0),
            (int) input('epic_id', 0)
        ),
        'reset_password' => $this->resetMotDePasse($id),
        'limit_access'   => $this->userLimitAccess($id, (string) ($valeur ?? '')),
        default          => abort(422, 'Action inconnue.'),
    };

    json_response(['success' => true]);
}

    // ── Création / édition de comptes (citoyen · président · EPIC) ─────────
    public function userCreateForm(): never
    {
        $this->requirePermission('control.users');

        $this->view('control.user-form', [
            'mode'         => 'create',
            'user'         => null,
            'roles'        => Database::all("SELECT * FROM roles WHERE nom IN ('citoyen', 'association', 'epic') ORDER BY niveau"),
            'associations' => Database::all('SELECT id, nom FROM associations ORDER BY nom'),
            'epics'        => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'errors'       => $this->errors(),
            'old'          => $_SESSION['_old'] ?? [],
        ], 'dashboard-futur');
    }

    public function userStore(): never
    {
        $this->requirePermission('control.users');

        $data = all_input();

        // Les champs nullable vides doivent être null (le validateur gère null, pas '')
        $data['telephone']      = (($data['telephone'] ?? '') === '') ? null : $data['telephone'];
        $data['association_id'] = ((($data['association_id'] ?? '') === '') || (int) ($data['association_id'] ?? 0) === 0)
            ? null
            : $data['association_id'];
        $data['epic_id']        = ((($data['epic_id'] ?? '') === '') || (int) ($data['epic_id'] ?? 0) === 0)
            ? null
            : $data['epic_id'];

        $validator = Validator::make($data, [
            'nom'            => 'required|string|max:50',
            'prenom'         => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email',
            'telephone'      => 'nullable|phone',
            'password'       => 'required|min:8|confirmed',
            'role_user'      => 'required|in:citoyen,association,epic',
            'association_id' => 'nullable|integer',
            'epic_id'        => 'nullable|integer',
        ], [
        'nom.required'      => 'Le nom est obligatoire.',
        'prenom.required'   => 'Le prénom est obligatoire.',
        'email.required'    => 'L\'email est obligatoire.',
        'email.email'       => 'L\'email est invalide.',
        'email.unique'      => 'Cette adresse email est déjà utilisée.',
        'password.required' => 'Le mot de passe est obligatoire.',
        'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
        'password.confirmed'=> 'Les mots de passe ne correspondent pas.',
        'role_user.required'=> 'Le rôle est obligatoire.',
        'role_user.in'      => 'Le rôle sélectionné est invalide.',
    ]);

    if ($validator->fails()) {
        $this->backWithErrors($validator->errors(), $data);
    }

    ControlCenter::creerUtilisateur($data);

    flash('success', 'Compte créé avec succès.');
    redirect('control/utilisateurs');
}

    public function userEditForm(string $id): never
    {
        $this->requirePermission('control.users');

        $user = Database::one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if ($user === null) {
            abort(404, 'Utilisateur introuvable.');
        }

        $this->view('control.user-form', [
            'mode'         => 'edit',
            'user'         => $user,
            'roles'        => Database::all("SELECT * FROM roles WHERE nom IN ('citoyen', 'association', 'epic') ORDER BY niveau"),
            'associations' => Database::all('SELECT id, nom FROM associations ORDER BY nom'),
            'epics'        => Database::all('SELECT id, nom FROM epic ORDER BY nom'),
            'errors'       => $this->errors(),
            'old'          => $_SESSION['_old'] ?? [],
        ], 'dashboard-futur');
    }

    public function userUpdate(string $id): never
    {
        $this->requirePermission('control.users');

        $userId = (int) $id;
        $user   = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            abort(404, 'Utilisateur introuvable.');
        }

        $data = all_input();

        // Les champs nullable vides doivent être null (le validateur gère null, pas '')
        $data['telephone']      = (($data['telephone'] ?? '') === '') ? null : $data['telephone'];
        $data['association_id'] = ((($data['association_id'] ?? '') === '') || (int) ($data['association_id'] ?? 0) === 0)
            ? null
            : $data['association_id'];
        $data['epic_id']        = ((($data['epic_id'] ?? '') === '') || (int) ($data['epic_id'] ?? 0) === 0)
            ? null
            : $data['epic_id'];

        $validator = Validator::make($data, [
            'nom'            => 'required|string|max:50',
            'prenom'         => 'required|string|max:50',
            'email'          => 'required|email|unique:users,email,' . $userId,
            'telephone'      => 'nullable|phone',
            'password'       => 'nullable|min:8',
            'role_user'      => 'required|in:citoyen,association,epic',
            'association_id' => 'nullable|integer',
            'epic_id'        => 'nullable|integer',
        ], [
        'nom.required'      => 'Le nom est obligatoire.',
        'prenom.required'   => 'Le prénom est obligatoire.',
        'email.required'    => 'L\'email est obligatoire.',
        'email.email'       => 'L\'email est invalide.',
        'email.unique'      => 'Cette adresse email est déjà utilisée.',
        'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
        'role_user.required'=> 'Le rôle est obligatoire.',
        'role_user.in'      => 'Le rôle sélectionné est invalide.',
    ]);

    if ($validator->fails()) {
        $this->backWithErrors($validator->errors(), $data);
    }

    ControlCenter::modifierUtilisateur(
        $userId,
        $data,
        ! empty($data['password']) ? password_hash((string) $data['password'], PASSWORD_BCRYPT) : null
    );

    flash('success', 'Compte mis à jour.');
    redirect('control/utilisateurs');
}

public function resetPassword(): never
{
    $this->requirePermission('control.users');

    $id = (int) input('id');
    $this->resetMotDePasse($id);
}

public function forceLogout(): never
{
    $this->requirePermission('control.users');

    $id = (int) input('id');
    ControlCenter::forcerDeconnexion($id);

    json_response(['success' => true]);
}

public function limitAccess(): never
{
    $this->requirePermission('control.users');

    $id    = (int) input('id');
    $limit = (string) input('limit');

    $this->limitAccess($id, $limit);

    json_response(['success' => true]);
}

private function userLimitAccess(int $userId, string $limit): void
{
    Database::run('UPDATE users SET access_limit = ? WHERE id = ?', [$limit, $userId]);
    AuditLog::log('user.limit_access', 'user', $userId, null, ['access_limit' => $limit]);
}

// ── Association & EPIC Control ────────────────────────
public function epic(): never
{
    $this->requirePermission('control.epic');

    $this->view('control.epic', [
        'epics'       => Database::all('SELECT * FROM epic ORDER BY nom'),
        'interventions' => Database::all(
            'SELECT ee.*, e.adresse AS evenement_adresse, e.statut AS evenement_statut
             FROM evenement_epic ee
             JOIN evenements e ON e.id = ee.evenement_id
             ORDER BY ee.date_affectation DESC LIMIT 50'
        ),
    ], 'dashboard-futur');
}

public function epicAssign(): never
{
    $this->requirePermission('control.epic');

    $epicId    = (int) input('epic_id');
    $eventId   = (int) input('evenement_id');
    $observation = trim((string) input('observation', ''));

        Database::run(
            'INSERT INTO evenement_epic (evenement_id, epic_id, date_affectation, observation)
             VALUES (?, ?, NOW(), ?)',
            [$eventId, $epicId, $observation]
        );

        AuditLog::log('epic.assign', 'evenement_epic', 0, [
            'epic_id' => $epicId,
            'evenement_id' => $eventId,
        ]);

        // Traçabilité de l'organisation assignée (réaffectation manuelle).
        RoutingService::reaffecter($eventId, $epicId, $observation);

        json_response(['success' => true]);
    }

public function epicValidate(): never
{
    $this->requirePermission('control.epic');

    $id     = (int) input('id');
    $statut = (string) input('statut');

    $validStatuts = ['AFFECTE', 'EN_COURS', 'TERMINE', 'ANOMALIE'];
    if (! in_array($statut, $validStatuts, true)) {
        json_response(['success' => false, 'error' => 'Statut invalide.']);
    }

    Database::run('UPDATE evenement_epic SET statut = ? WHERE id = ?', [$statut, $id]);

    AuditLog::log('epic.validate', 'evenement_epic', $id, null, ['statut' => $statut]);

    json_response(['success' => true]);
}

public function epicCreate(): never
{
    $this->requirePermission('control.epic');

    $this->view('control.epic-form', [
        'mode' => 'create',
        'epic' => null,
        'errors' => $this->errors(),
    ], 'dashboard-futur');
}

public function epicStore(): never
{
    $this->requirePermission('control.epic');

    $data = input()->all();

    $validator = Validator::make($data, [
        'nom'        => 'required|string|max:100',
        'wilaya'     => 'nullable|string|max:50',
        'daira'      => 'nullable|string|max:50',
        'description' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
        $this->backWithErrors($validator->errors(), $data);
    }

    Database::insert('epic', [
        'nom'        => trim((string) ($data['nom'] ?? '')),
        'wilaya'     => trim((string) ($data['wilaya'] ?? '')),
        'daira'      => trim((string) ($data['daira'] ?? '')),
        'description' => trim((string) ($data['description'] ?? '')),
        'actif'      => 1,
    ]);

    flash('success', 'EPIC créée avec succès.');
    redirect('control?tab=epics');
}

public function epicEdit(string $id): never
{
    $this->requirePermission('control.epic');

    $epic = Database::one('SELECT * FROM epic WHERE id = ?', [(int) $id]);
    if ($epic === null) {
        abort(404, 'EPIC introuvable.');
    }

    $this->view('control.epic-form', [
        'mode'   => 'edit',
        'epic'   => $epic,
        'errors' => $this->errors(),
    ], 'dashboard-futur');
}

public function epicUpdate(string $id): never
{
    $this->requirePermission('control.epic');

    $epicId = (int) $id;
    $epic   = Database::one('SELECT * FROM epic WHERE id = ?', [$epicId]);
    if ($epic === null) {
        abort(404, 'EPIC introuvable.');
    }

    $data = input()->all();

    $validator = Validator::make($data, [
        'nom'        => 'required|string|max:100',
        'wilaya'     => 'nullable|string|max:50',
        'daira'      => 'nullable|string|max:50',
        'description' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
        $this->backWithErrors($validator->errors(), $data);
    }

    Database::run(
        'UPDATE epic SET nom = ?, wilaya = ?, daira = ?, description = ? WHERE id = ?',
        [
            trim((string) ($data['nom'] ?? '')),
            trim((string) ($data['wilaya'] ?? '')),
            trim((string) ($data['daira'] ?? '')),
            trim((string) ($data['description'] ?? '')),
            $epicId,
        ]
    );

    flash('success', 'EPIC mise à jour.');
    redirect('control?tab=epics');
}

public function epicDelete(string $id): never
{
    $this->requirePermission('control.epic');

    $epicId = (int) $id;
    $epic   = Database::one('SELECT * FROM epic WHERE id = ?', [$epicId]);
    if ($epic === null) {
        abort(404, 'EPIC introuvable.');
    }

    Database::run('DELETE FROM epic WHERE id = ?', [$epicId]);

    AuditLog::log('epic.delete', 'epic', $epicId, ['nom' => $epic['nom'] ?? '']);

    flash('success', 'EPIC supprimée.');
    redirect('control?tab=epics');
}

    /**
     * @return array<int,array<string,mixed>>
     */
    private function allUsers(): array
    {
        return Database::all(
            'SELECT u.*, r.nom AS role_nom, a.nom AS association_nom, e.nom AS epic_nom
             FROM users u
             LEFT JOIN roles r ON r.nom = u.role_user
             LEFT JOIN associations a ON a.id = u.association_id
             LEFT JOIN epic e ON e.id = u.epic_id
             ORDER BY u.id DESC LIMIT 500'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function securityEvents(int $limit): array
    {
        return Database::all(
            'SELECT * FROM security_events ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function securityEventsJson(): array
    {
        return Database::all(
            "SELECT type, COUNT(*) AS total FROM security_events
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY type ORDER BY total DESC"
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function allSettings(): array
    {
        return Database::all('SELECT * FROM system_settings ORDER BY groupe, cle');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function settingsGroupes(): array
    {
        return Database::all('SELECT DISTINCT groupe FROM system_settings ORDER BY groupe');
    }

    /**
     * Retourne les paramètres en tant que tableau clé => valeur.
     */
    private function settingsMap(): array
    {
        $rows = Database::all('SELECT cle, valeur FROM system_settings');
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['cle']] = $r['valeur'];
        }

        return $map;
    }

    // ── Communes CRUD ─────────────────────────────────────────────────
    public function communes(): never
    {
        $this->requirePermission('control.communes');

        $q = trim((string) input('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (nom LIKE ? OR code_postal LIKE ? OR code_insee LIKE ?)';
            $like = "%{$q}%";
            $params = [$like, $like, $like];
        }

        $page = (int) input('page', 1);
        $result = Database::paginate(
            "SELECT * FROM commune WHERE {$where} ORDER BY nom",
            $params,
            25,
            $page
        );

        $this->view('control.communes', [
            'communes' => $result['items'],
            'page'     => $result['page'],
            'lastPage' => $result['last_page'],
            'total'    => $result['total'],
            'filters'  => ['q' => $q],
        ], 'dashboard-futur');
    }

    public function communeCreate(): never
    {
        $this->requirePermission('control.communes');

        $this->view('control.commune_form', [
            'mode'  => 'create',
            'commune' => null,
            'errors' => $this->errors(),
            'old' => $_SESSION['_old'] ?? [],
        ], 'dashboard-futur');
    }

    public function communeStore(): never
    {
        $this->requirePermission('control.communes');

        $data = all_input();
        $validator = Validator::make($data, [
            'nom'          => 'required|string|max:100',
            'code_postal'  => 'required|string|max:10',
            'code_insee'   => 'required|string|max:10|unique:commune,code_insee',
            'latitude'     => 'nullable|numeric|between:-90,90',
            'longitude'    => 'nullable|numeric|between:-180,180',
        ], [
            'nom.required'         => 'Le nom est obligatoire.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'code_insee.required'  => 'Le code INSEE est obligatoire.',
            'code_insee.unique'    => 'Ce code INSEE existe déjà.',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::insert('commune', [
            'nom'          => trim($data['nom']),
            'code_postal'  => trim($data['code_postal']),
            'code_insee'   => trim($data['code_insee']),
            'latitude'     => $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude'    => $data['longitude'] !== '' ? (float) $data['longitude'] : null,
        ]);

        AuditLog::log($this->user()['id'], 'commune_created', 'commune', (int) Database::lastInsertId(), ['nom' => $data['nom']]);
        flash('success', 'Commune créée.');
        redirect('control/communes');
    }

    public function communeEdit(string $id): never
    {
        $this->requirePermission('control.communes');

        $commune = Database::one('SELECT * FROM commune WHERE id = ?', [(int) $id]);
        if ($commune === null) {
            abort(404, 'Commune introuvable.');
        }

        $this->view('control.commune_form', [
            'mode'    => 'edit',
            'commune' => $commune,
            'errors'  => $this->errors(),
            'old'     => $_SESSION['_old'] ?? [],
        ], 'dashboard-futur');
    }

    public function communeUpdate(string $id): never
    {
        $this->requirePermission('control.communes');

        $commune = Database::one('SELECT * FROM commune WHERE id = ?', [(int) $id]);
        if ($commune === null) {
            abort(404, 'Commune introuvable.');
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'nom'          => 'required|string|max:100',
            'code_postal'  => 'required|string|max:10',
            'code_insee'   => 'required|string|max:10|unique:commune,code_insee,' . $id,
            'latitude'     => 'nullable|numeric|between:-90,90',
            'longitude'    => 'nullable|numeric|between:-180,180',
        ], [
            'nom.required'         => 'Le nom est obligatoire.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'code_insee.required'  => 'Le code INSEE est obligatoire.',
            'code_insee.unique'    => 'Ce code INSEE existe déjà.',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        Database::update('commune', [
            'nom'          => trim($data['nom']),
            'code_postal'  => trim($data['code_postal']),
            'code_insee'   => trim($data['code_insee']),
            'latitude'     => $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            'longitude'    => $data['longitude'] !== '' ? (float) $data['longitude'] : null,
        ], 'id = ?', [(int) $id]);

        AuditLog::log($this->user()['id'], 'commune_updated', 'commune', (int) $id);
        flash('success', 'Commune mise à jour.');
        redirect('control/communes');
    }

    public function communeDelete(string $id): never
    {
        $this->requirePermission('control.communes');

        $commune = Database::one('SELECT * FROM commune WHERE id = ?', [(int) $id]);
        if ($commune === null) {
            abort(404, 'Commune introuvable.');
        }

        // Vérifier s'il y a des événements liés
        $hasEvents = Database::value('SELECT 1 FROM evenements WHERE commune_id = ?', [(int) $id]);
        if ($hasEvents) {
            flash('error', 'Impossible de supprimer : des événements sont liés à cette commune.');
            redirect('control/communes');
        }

        Database::run('DELETE FROM commune WHERE id = ?', [(int) $id]);
        AuditLog::log($this->user()['id'], 'commune_deleted', 'commune', (int) $id, ['nom' => $commune['nom']]);
        flash('success', 'Commune supprimée.');
        redirect('control/communes');
    }

    private function resetMotDePasse(int $userId): void
    {
        $nouveau = bin2hex(random_bytes(6));
        Database::run('UPDATE users SET password = ? WHERE id = ?', [password_hash($nouveau, PASSWORD_BCRYPT), $userId]);
        Database::run('DELETE FROM sessions WHERE user_id = ?', [$userId]);

        AuditLog::log('user.reset_password', 'user', $userId, null, ['mot_de_passe_regeneré' => true]);

        json_response(['success' => true, 'nouveau' => $nouveau]);
    }

    private function saveSetting(string $groupe, string $cle, string $valeur): void
    {
        $avant = Database::one('SELECT valeur FROM system_settings WHERE cle = ?', [$cle]);

        Database::run(
            'INSERT INTO system_settings (groupe, cle, valeur, updated_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur), updated_by = VALUES(updated_by)',
            [$groupe, $cle, $valeur, Session::userId()]
        );

        AuditLog::log('settings.update', 'system_settings', 0, [
            'cle'   => $cle,
            'avant' => $avant['valeur'] ?? null,
            'apres' => $valeur,
        ]);
    }

    /**
     * @param array<int|string, string> $headers
     * @param array<int, array<int|string, mixed>> $rows
     */
    private function csv(string $filename, array $headers, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn ($v) => is_string($v) ? str_replace(["\r", "\n"], ' ', $v) : $v, array_values($row)));
        }

        fclose($out);
        exit;
    }
}
