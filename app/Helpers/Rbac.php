<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * RBAC — 7 niveaux hiérarchiques avec portée de données.
 */
final class Rbac
{
    private const HIERARCHY = [
        'citoyen'     => 1,
        'membre'      => 2,
        'epic'        => 2,
        'association' => 3,
        'chef_section' => 4,
        'chef_unite'  => 5,
        'wilaya'      => 7,
    ];

    /**
     * Rôle effectif de l'utilisateur (users.role_user).
     */
    public static function role(?array $user): ?string
    {
        return $user['role_user'] ?? null;
    }

    public static function level(?array $user): int
    {
        return self::HIERARCHY[self::role($user)] ?? 0;
    }

    public static function hasRole(string $role, ?array $user): bool
    {
        return self::role($user) === $role;
    }

    /**
     * Permissions dynamiques depuis role_permissions.
     */
    public static function permissions(?array $user): array
    {
        static $cache = [];

        $role = self::role($user);
        if ($role === null) {
            return [];
        }

        if (isset($cache[$role])) {
            return $cache[$role];
        }

        // La Wilaya (niveau 7) hérite de toutes les permissions.
        if ($role === 'wilaya') {
            return $cache[$role] = array_column(Database::all('SELECT nom FROM permissions'), 'nom');
        }

        $rows = Database::all(
            'SELECT p.nom FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN roles r ON r.id = rp.role_id
             WHERE r.nom = ?',
            [$role]
        );

        return $cache[$role] = array_column($rows, 'nom');
    }

    public static function can(string $permission, ?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        return in_array($permission, self::permissions($user), true);
    }

    /**
     * Charge les permissions de l'utilisateur dans la session (appelé au login).
     * Pattern Balagh Alger: permissions en session pour éviter les requêtes DB répétées.
     */
    public static function loadPermissions(int $userId): void
    {
        $rows = Database::all(
            'SELECT DISTINCT p.nom FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN user_roles ur ON ur.role_id = rp.role_id
             JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?',
            [$userId]
        );

        Session::set('user_permissions', array_column($rows, 'nom'));
    }

    /**
     * Vérifie une permission depuis la session (rapide, pas de DB).
     */
    public static function hasPermission(string $permission): bool
    {
        $perms = Session::get('user_permissions', []);

        // Wilaya a toutes les permissions (niveau 7)
        if (in_array('wilaya', self::rolesInSession(), true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }

    /**
     * Vérifie qu'une de plusieurs permissions est présente.
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if (self::hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rôles de l'utilisateur depuis la session.
     *
     * @return string[]
     */
    public static function rolesInSession(): array
    {
        $roles = Session::get('user_roles', []);

        return is_array($roles) ? $roles : [];
    }

    /**
     * Portée de données : filtres SQL selon le rôle.
     * Retourne le fragment SQL avec placeholder (vide = aucune restriction).
     * OWASP A03 — évite injection via $column (allowlist) et valeurs bindées.
     */
    public static function scope(?array $user, string $column = 'e.association_id'): string
    {
        $allow = ['e.association_id', 'association_id', 'evenements.association_id', 'e.id'];
        if (!in_array($column, $allow, true)) {
            return '1 = 0';
        }
        $role = self::role($user);
        return match ($role) {
            'wilaya', 'chef_section', 'chef_unite' => '',
            'association', 'membre' => sprintf('%s = ?', $column),
            'epic' => 'e.id IN (SELECT evenement_id FROM evenement_epic WHERE epic_id = ?)',
            default => '1 = 0',
        };
    }

    public static function scopeParams(?array $user): array
    {
        $role = self::role($user);
        return match ($role) {
            'association', 'membre' => [(int) ($user['association_id'] ?? 0)],
            'epic' => [(int) ($user['epic_id'] ?? 0)],
            default => [],
        };
    }

    /**
     * Niveaux de protection des routes par rôle.
     */
    public static function levelAtLeast(int $level, ?array $user): bool
    {
        return self::level($user) >= $level;
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'wilaya'       => __('roles.wilaya'),
            'association'  => __('roles.association'),
            'membre'       => __('roles.membre'),
            'epic'         => __('roles.epic'),
            'citoyen'      => __('roles.citoyen'),
            'chef_section' => __('roles.chef_section'),
            'chef_unite'   => __('roles.chef_unite'),
            default        => $role,
        };
    }
}
