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
