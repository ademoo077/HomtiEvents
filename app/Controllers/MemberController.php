<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\Validator;

/**
 * Membres d'association (Phase 7) :
 *   - gestion des membres et invitations côté association,
 *   - acceptation publique d'une invitation (création de compte membre
 *     ou rattachement d'un compte existant),
 *   - tableau de bord membre (événements de l'association).
 */
final class MemberController extends Controller
{
    private const INVITE_TTL = 7 * 24 * 3600;

    public function index(): never
    {
        $this->requirePermission('association.members');

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association liée à votre compte.');
        }

        $association = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);
        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $membres = Database::all(
            'SELECT id, nom, prenom, email, telephone, avatar, is_active, last_login
             FROM users
             WHERE association_id = ? AND role_user = ?
             ORDER BY prenom, nom',
            [$associationId, 'membre']
        );

        $invitations = Database::all(
            'SELECT i.*, u.prenom AS inviteur_prenom, u.nom AS inviteur_nom
             FROM association_invitations i
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.association_id = ?
             ORDER BY i.created_at DESC',
            [$associationId]
        );

        $this->view('association/members', [
            'association' => $association,
            'membres'     => $membres,
            'invitations' => $invitations,
        ], 'association');
    }

    public function invite(): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association liée à votre compte.');
        }

        $email = mb_strtolower(trim((string) input('email', '')));

        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|max:100',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), ['email' => $email]);
        }

        // Déjà membre ?
        $alreadyMember = Database::value(
            'SELECT COUNT(*) FROM users WHERE email = ? AND association_id = ?',
            [$email, $associationId]
        );
        if ((int) $alreadyMember > 0) {
            flash('error', __('members.already_member'));
            $this->redirect('association/membres');
        }

        // Invitation pending en double ?
        $pending = Database::value(
            "SELECT COUNT(*) FROM association_invitations
             WHERE association_id = ? AND LOWER(email) = ? AND statut = 'pending' AND (expires_at IS NULL OR expires_at > NOW())",
            [$associationId, $email]
        );
        if ((int) $pending > 0) {
            flash('error', __('members.invite_pending'));
            $this->redirect('association/membres');
        }

        $token = bin2hex(random_bytes(32));
        Database::insert('association_invitations', [
            'association_id' => $associationId,
            'email'          => $email,
            'token'          => $token,
            'statut'         => 'pending',
            'created_by'     => (int) ($user['id'] ?? 0),
            'expires_at'     => date('Y-m-d H:i:s', time() + self::INVITE_TTL),
        ]);

        AuditLog::log('association.invite', 'association_invitations', 0, null, ['email' => $email, 'association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.invite_sent_title'),
            __('members.invite_sent_message', ['email' => $email]),
            'membre_invite',
            ['email' => $email]
        );

        flash('success', __('members.invite_created', ['email' => $email]));
        $this->redirect('association/membres');
    }

    public function revoke(string $id): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        Database::run(
            "UPDATE association_invitations SET statut = 'revoked'
             WHERE id = ? AND association_id = ? AND statut = 'pending'",
            [(int) $id, $associationId]
        );

        AuditLog::log('association.invite_revoke', 'association_invitations', (int) $id);

        flash('success', __('members.invite_revoked'));
        $this->redirect('association/membres');
    }

    public function remove(string $id): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        $membre = Database::one(
            'SELECT id, nom, prenom, email FROM users
             WHERE id = ? AND association_id = ? AND role_user = ?',
            [(int) $id, $associationId, 'membre']
        );
        if ($membre === null) {
            abort(404, 'Membre introuvable.');
        }

        // Démotion en citoyen (conservation du compte et de son historique).
        Database::update('users', [
            'role_user'      => 'citoyen',
            'association_id' => null,
        ], 'id = ?', [(int) $id]);

        AuditLog::log('association.member_remove', 'users', (int) $id, null, ['association_id' => $associationId]);
        Notification::send(
            (int) $id,
            __('members.removed_title'),
            __('members.removed_message', ['association' => (string) ($membre['prenom'] ?? '')]),
            'membre_retire'
        );

        flash('success', __('members.member_removed', ['nom' => trim((string) ($membre['prenom'] . ' ' . $membre['nom']))]));
        $this->redirect('association/membres');
    }

    public function acceptShow(string $token): never
    {
        $invitation = $this->findValidInvitation($token);
        if ($invitation === null) {
            abort(404, __('members.invite_invalid'));
        }

        $existing = Database::one('SELECT id, prenom, nom FROM users WHERE email = ?', [$invitation['email']]);

        $association = Database::one(
            'SELECT nom FROM associations WHERE id = ?',
            [(int) $invitation['association_id']]
        );

        $this->view('member/accept', [
            'invitation'  => $invitation,
            'association' => $association,
            'existing'    => $existing,
        ], 'guest');
    }

    public function accept(string $token): never
    {
        $this->csrfCheck();

        $invitation = $this->findValidInvitation($token);
        if ($invitation === null) {
            abort(404, __('members.invite_invalid'));
        }

        $associationId = (int) $invitation['association_id'];
        $email         = (string) $invitation['email'];
        $existingUser  = Database::one('SELECT id, role_user FROM users WHERE email = ?', [$email]);

        if ($existingUser !== null) {
            $this->attachExistingUser($invitation, (int) $existingUser['id'], (string) $existingUser['role_user']);

            flash('success', __('members.invite_accepted'));
            $this->redirect(dashboard_path());
        }

        $data = [
            'prenom'    => trim((string) input('prenom', '')),
            'nom'       => trim((string) input('nom', '')),
            'telephone' => trim((string) input('telephone', '')),
            'password'  => (string) input('password', ''),
        ];

        $validator = Validator::make($data, [
            'prenom'    => 'required|string|max:50',
            'nom'       => 'required|string|max:50',
            'telephone' => 'nullable|phone',
            'password'  => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), array_merge($data, ['token' => $token]));
        }

        $userId = Database::insert('users', [
            'nom'            => $data['nom'],
            'prenom'         => $data['prenom'],
            'email'          => $email,
            'password'       => password_hash($data['password'], PASSWORD_BCRYPT),
            'role_user'      => 'membre',
            'telephone'      => $data['telephone'] !== '' ? $data['telephone'] : null,
            'association_id' => $associationId,
            'is_active'      => 1,
        ]);

        Database::update('association_invitations', [
            'statut'      => 'accepted',
            'accepted_by' => $userId,
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $invitation['id']]);

        AuditLog::log('association.invite_accepted', 'users', $userId, null, ['association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.accepted_title'),
            __('members.accepted_message', ['email' => $email]),
            'membre_accepte',
            ['email' => $email]
        );

        Session::login($userId);
        Session::set('user', Database::one('SELECT * FROM users WHERE id = ?', [$userId]));
        Session::set('user_roles', ['membre']);
        Rbac::loadPermissions($userId);

        flash('success', __('members.welcome_member'));
        $this->redirect(dashboard_path());
    }

    public function dashboard(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'membre') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);

        $events = $associationId > 0
            ? Database::all(
                'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom,
                        (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
                 FROM evenements e
                 LEFT JOIN commune c ON c.id = e.commune_id
                 LEFT JOIN associations a ON a.id = e.association_id
                 WHERE e.association_id = ? AND e.deleted_at IS NULL
                 ORDER BY e.date_evenement DESC, e.created_at DESC',
                [$associationId]
            )
            : [];

        $association = $associationId > 0
            ? Database::one('SELECT * FROM associations WHERE id = ?', [$associationId])
            : null;

        $this->view('member/dashboard', [
            'association' => $association,
            'events'      => $events,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findValidInvitation(string $token): ?array
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        $invitation = Database::one(
            'SELECT * FROM association_invitations WHERE token = ?',
            [$token]
        );
        if ($invitation === null || $invitation['statut'] !== 'pending') {
            return null;
        }

        if (($invitation['expires_at'] ?? null) !== null && strtotime((string) $invitation['expires_at']) < time()) {
            return null;
        }

        return $invitation;
    }

    /**
     * @param array<string, mixed> $invitation
     */
    private function attachExistingUser(array $invitation, int $userId, string $currentRole): void
    {
        $associationId = (int) $invitation['association_id'];

        // Un compte non-citoyen déjà rattaché à une autre structure : refus.
        $holder = Database::value('SELECT association_id FROM users WHERE id = ?', [$userId]);
        if (! in_array($currentRole, ['citoyen', 'membre'], true) || (int) ($holder ?? 0) > 0) {
            abort(409, __('members.invite_conflict'));
        }

        Database::update('users', [
            'role_user'      => 'membre',
            'association_id' => $associationId,
        ], 'id = ?', [$userId]);

        Database::update('association_invitations', [
            'statut'      => 'accepted',
            'accepted_by' => $userId,
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $invitation['id']]);

        AuditLog::log('association.invite_accepted', 'users', $userId, null, ['association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.accepted_title'),
            __('members.accepted_message', ['email' => (string) $invitation['email']]),
            'membre_accepte',
            ['email' => $invitation['email']]
        );

        Session::refreshUser();
        Session::set('user_roles', ['membre']);
        Rbac::loadPermissions($userId);
    }
}
