<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Control Center — Supervision globale de la plateforme.
 *
 * Centre de contrôle centralisé : activation/désactivation des modules,
 * contrôle des accès, validation des actions sensibles, blocage utilisateurs,
 * suspension associations, gestion des permissions en temps réel.
 */
final class ControlCenter
{
    /** @var array<string,bool>|null cache des modules */
    private static ?array $cache = null;

    /**
     * Vérifie qu'un module est actif.
     */
    public static function moduleActif(string $cle): bool
    {
        self::warm();

        if (! isset(self::$cache['modules'])) {
            return true;
        }

        return self::$cache['modules'][$cle] ?? true;
    }

    /**
     * Bloque une action si le module est désactivé.
     */
    public static function requireModule(string $cle): void
    {
        if (! self::moduleActif($cle)) {
            AuditLog::log('blocked_action', 'module', 0, [
                'module' => $cle,
                'motif'  => 'Module désactivé par le Control Center',
            ]);
            abort(403, 'Module désactivé par le Control Center.');
        }
    }

    /**
     * Liste tous les modules avec leur état.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function modules(): array
    {
        return Database::all('SELECT * FROM control_modules ORDER BY ordre ASC');
    }

    /**
     * Active / désactive un module (action sensible -> auditée).
     */
    public static function toggleModule(string $cle, bool $actif): void
    {
        $module = Database::one('SELECT * FROM control_modules WHERE cle = ?', [$cle]);

        if ($module === null) {
            abort(404, 'Module introuvable.');
        }

        if ((int) $module['verrouille'] === 1) {
            abort(403, 'Ce module est verrouillé.');
        }

        Database::run(
            'UPDATE control_modules SET actif = ?, updated_by = ? WHERE id = ?',
            [(int) $actif, Session::userId(), (int) $module['id']]
        );

        AuditLog::log('module.toggle', 'control_modules', (int) $module['id'], [
            'module' => $cle,
            'avant'  => (int) $module['actif'],
            'apres'  => (int) $actif,
        ]);

        self::$cache = null;
    }

    /**
     * Bloque (suspend/bannit) un utilisateur.
     */
    public static function modifierStatutUtilisateur(int $userId, string $statut, ?string $jusquA = null): void
    {
        $statutsValides = ['actif', 'suspendu', 'banni'];
        if (! in_array($statut, $statutsValides, true)) {
            abort(422, 'Statut utilisateur invalide.');
        }

        $avant = Database::one('SELECT status FROM users WHERE id = ?', [$userId]);

        Database::run(
            'UPDATE users SET status = ?, suspendu_jusqu_a = ? WHERE id = ?',
            [$statut, $jusquA ?? null, $userId]
        );

        if ($statut !== 'actif') {
            Database::run('DELETE FROM sessions WHERE user_id = ?', [$userId]);
        }

        AuditLog::log('user.statut', 'user', $userId, [
            'avant' => $avant['status'] ?? 'actif',
            'apres' => $statut,
        ]);
    }

    /**
     * Force la déconnexion d'un utilisateur (toutes sessions).
     */
    public static function forcerDeconnexion(int $userId): void
    {
        Database::run('DELETE FROM sessions WHERE user_id = ?', [$userId]);
        AuditLog::log('user.force_logout', 'user', $userId);
    }

    /**
     * Change le rôle d'un utilisateur.
     */
    public static function changerRole(int $userId, string $role, int $associationId = 0, int $epicId = 0): void
    {
        $rpc = Database::one(
            'SELECT COUNT(*) AS c FROM roles WHERE nom = ?',
            [$role]
        );
        if ((int) ($rpc['c'] ?? 0) === 0) {
            abort(422, 'Rôle invalide.');
        }

        $avant = Database::one('SELECT role_user, association_id, epic_id FROM users WHERE id = ?', [$userId]);

        Database::run(
            'UPDATE users SET role_user = ?, association_id = NULLIF(?,0), epic_id = NULLIF(?,0) WHERE id = ?',
            [$role, $associationId, $epicId, $userId]
        );

        AuditLog::log('user.role', 'user', $userId, [
            'avant' => $avant,
            'apres' => ['role_user' => $role, 'association_id' => $associationId, 'epic_id' => $epicId],
        ]);
    }

    /**
     * Suspend / restaure une association.
     */
    public static function suspendreAssociation(int $associationId, bool $suspendu): void
    {
        $avant = Database::one('SELECT valide FROM associations WHERE id = ?', [$associationId]);

        Database::run(
            'UPDATE associations SET valide = ? WHERE id = ?',
            [$suspendu ? 0 : 1, $associationId]
        );

        AuditLog::log('association.suspendre', 'association', $associationId, [
            'avant' => (int) ($avant['valide'] ?? 0),
            'apres' => $suspendu ? 0 : 1,
        ]);
    }

    /**
     * Crée un compte (citoyen ou président d'association).
     *
     * Attend les clés : nom, prenom, email, password, telephone, role_user,
     * association_id. Enchaîne le lien RBAC (user_roles) et l'audit.
     *
     * @param array<string, mixed> $d
     */
    public static function creerUtilisateur(array $d): int
    {
        $role          = (string) ($d['role_user'] ?? 'citoyen');
        $associationId = $role === 'association' ? (int) ($d['association_id'] ?? 0) : 0;
        $epicId        = $role === 'epic' ? (int) ($d['epic_id'] ?? 0) : 0;
        $email         = mb_strtolower(trim((string) ($d['email'] ?? '')));

        $userId = Database::insert('users', [
            'nom'            => trim((string) ($d['nom'] ?? '')),
            'prenom'         => trim((string) ($d['prenom'] ?? '')),
            'email'          => $email,
            'password'       => password_hash((string) ($d['password'] ?? ''), PASSWORD_BCRYPT),
            'telephone'      => trim((string) ($d['telephone'] ?? '')),
            'role_user'      => $role,
            'association_id' => $associationId > 0 ? $associationId : null,
            'epic_id'        => $epicId > 0 ? $epicId : null,
            'is_active'      => 1,
        ]);

        self::syncRoleRbac($userId, $role);

        AuditLog::log('user.create', 'user', $userId, null, [
            'email'          => $email,
            'role_user'      => $role,
            'association_id' => $associationId > 0 ? $associationId : null,
            'epic_id'        => $epicId > 0 ? $epicId : null,
        ]);

        return $userId;
    }

    /**
     * Modifie un compte existant (informations, rôle, rattachement,
     * éventuellement mot de passe). Lève 404 si l'utilisateur n'existe pas.
     *
     * @param array<string, mixed> $d
     * @return array<string, mixed> anciennes valeurs (utiles pour l'audit)
     */
    public static function modifierUtilisateur(int $userId, array $d, ?string $nouveauPassword = null): array
    {
        $avant = Database::one('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($avant === null) {
            abort(404, 'Utilisateur introuvable.');
        }

        $role          = (string) ($d['role_user'] ?? ($avant['role_user'] ?? 'citoyen'));
        $associationId = $role === 'association' ? (int) ($d['association_id'] ?? 0) : 0;
        $epicId        = $role === 'epic' ? (int) ($d['epic_id'] ?? 0) : 0;
        $email         = mb_strtolower(trim((string) ($d['email'] ?? '')));

        $params = [
            trim((string) ($d['nom'] ?? '')),
            trim((string) ($d['prenom'] ?? '')),
            $email,
            trim((string) ($d['telephone'] ?? '')),
            $role,
            $associationId > 0 ? $associationId : null,
            $epicId > 0 ? $epicId : null,
        ];
        $sql = 'UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, role_user = ?, association_id = ?, epic_id = ?';
        if ($nouveauPassword !== null && $nouveauPassword !== '') {
            $sql .= ', password = ?';
            $params[] = $nouveauPassword;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $userId;

        Database::run($sql, $params);

        if ((string) ($avant['role_user'] ?? '') !== $role) {
            self::syncRoleRbac($userId, $role);
        }

        AuditLog::log('user.update', 'user', $userId, [
            'nom'            => $avant['nom'] ?? null,
            'prenom'         => $avant['prenom'] ?? null,
            'email'          => $avant['email'] ?? null,
            'telephone'      => $avant['telephone'] ?? null,
            'role_user'      => $avant['role_user'] ?? null,
            'association_id' => $avant['association_id'] ?? null,
            'epic_id'        => $avant['epic_id'] ?? null,
        ], [
            'nom'            => trim((string) ($d['nom'] ?? '')),
            'prenom'         => trim((string) ($d['prenom'] ?? '')),
            'email'          => $email,
            'telephone'      => trim((string) ($d['telephone'] ?? '')),
            'role_user'      => $role,
            'association_id' => $associationId > 0 ? $associationId : null,
            'epic_id'        => $epicId > 0 ? $epicId : null,
            'password'       => ($nouveauPassword !== null && $nouveauPassword !== '') ? 'modifié' : null,
        ]);

        return $avant;
    }

    /**
     * Synchronise le lien RBAC (user_roles) d'un utilisateur sur un rôle.
     */
    private static function syncRoleRbac(int $userId, string $role): void
    {
        $roleId = (int) Database::value('SELECT id FROM roles WHERE nom = ?', [$role]);
        if ($roleId === 0) {
            return;
        }

        Database::run(
            'DELETE FROM user_roles WHERE user_id = ? AND role_id NOT IN (SELECT id FROM roles WHERE nom = ?)',
            [$userId, $role]
        );

        $existe = (int) Database::value(
            'SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?',
            [$userId, $roleId]
        );
        if ($existe === 0) {
            Database::insert('user_roles', ['user_id' => $userId, 'role_id' => $roleId]);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allAssociations(): array
    {
        return Database::all('SELECT * FROM associations ORDER BY nom');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allEpics(): array
    {
        return Database::all('SELECT * FROM epic ORDER BY nom');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allCommunes(): array
    {
        return Database::all('SELECT * FROM communes ORDER BY wilaya, daira, nom');
    }

    private static function warm(): void
    {
        if (self::$cache !== null) {
            return;
        }

        try {
            $rows = Database::all('SELECT cle, actif FROM control_modules');
        } catch (\Throwable) {
            self::$cache['modules'] = [];

            return;
        }

        $map = [];
        foreach ($rows as $row) {
            $map[$row['cle']] = (int) $row['actif'] === 1;
        }

        self::$cache['modules'] = $map;
    }
}
