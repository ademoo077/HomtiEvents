<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validation pipeline for sensitive actions.
 *
 * Every critical action must pass through this pipeline:
 * 1. Permission check
 * 2. Business rule evaluation
 * 3. Control Center validation
 * 4. Audit logging
 * 5. Notification of administrators
 */
final class ControlAction
{
    /**
     * Execute a sensitive action through the validation pipeline.
     *
     * @param string $action   The action being performed (e.g. 'association.create')
     * @param string $module   The module being affected
     * @param int|null $modelId The ID of the affected entity
     * @param array  $context  Additional context for evaluation
     * @param callable $execute The actual action to execute if validation passes
     *
     * @return mixed The result of the executed action
     */
    public static function execute(
        string $action,
        string $module,
        ?int $modelId,
        array $context,
        callable $execute
    ): mixed {
        $user = Session::user();

        // Step 1: Permission check
        if (! Rbac::hasPermission($action)) {
            AuditLog::log('blocked_permission', $module, $modelId ?? 0, [
                'action'   => $action,
                'user_id'  => $user['id'] ?? null,
                'context'  => $context,
            ]);
            abort(403, 'Permission refusée pour cette action.');
        }

        // Step 2: Business rule evaluation
        foreach (BusinessRules::liste('blocage') as $rule) {
            if ($rule['cible'] !== null && $rule['cible'] !== $module) {
                continue;
            }

            $result = BusinessRules::evaluer($rule['cle'], $user, $context);

            if ($result['bloque']) {
                AuditLog::log('rule.blocked', 'system_rules', (int) $rule['id'], [
                    'action'  => $action,
                    'rule'    => $rule['cle'],
                    'context' => $context,
                ]);
                abort(403, 'Action bloquée par une règle métier : ' . ($rule['nom'] ?? 'Règle inconnue'));
            }
        }

        // Step 3: Control Center module check
        ControlCenter::requireModule($module);

        // Step 4: ABAC check
        if (! Abac::permet($user, $action, $context)) {
            AuditLog::log('abac.blocked', $module, $modelId ?? 0, [
                'action'  => $action,
                'user_id' => $user['id'] ?? null,
                'context' => $context,
            ]);
            abort(403, 'Accès refusé par la politique de contrôle.');
        }

        // Step 5: Execute the action
        $result = $execute();

        // Step 6: Audit logging
        AuditLog::log($action, $module, $modelId ?? 0, $context);

        // Step 7: Notify administrators of sensitive actions
        self::notifyAdmins($action, $module, $modelId, $user);

        return $result;
    }

    /**
     * Validate content before publication (brouillon → en_attente → publie).
     */
    public static function validateContent(
        string $model,
        int $modelId,
        string $statut,
        ?int $proposerPar = null
    ): bool {
        $user = Session::user();
        $proposerPar = $proposerPar ?? Session::userId();

        $existing = Database::one(
            'SELECT id, statut FROM content_validations WHERE modele = ? AND modele_id = ?',
            [$model, $modelId]
        );

        if ($existing !== null) {
            Database::run(
                'UPDATE content_validations SET statut = ?, proposer_par = ?, valide_par = ?, updated_at = NOW() WHERE id = ?',
                [$statut, $proposerPar, null, (int) $existing['id']]
            );
        } else {
            Database::run(
                'INSERT INTO content_validations (modele, modele_id, statut, proposer_par) VALUES (?, ?, ?, ?)',
                [$model, $modelId, $statut, $proposerPar]
            );
        }

        AuditLog::log('content.validation', $model, $modelId, [
            'statut'         => $statut,
            'proposer_par'   => $proposerPar,
        ]);

        if ($statut === 'PUBLIE' && Rbac::hasPermission('content.approve')) {
            self::notifyAdmins('content.publish', $model, $modelId, $user);
        }

        return true;
    }

    /**
     * Approve content for publication (admin only).
     */
    public static function approveContent(
        string $model,
        int $modelId,
        string $motif = ''
    ): bool {
        $user = Session::user();

        if (! Rbac::hasPermission('content.approve')) {
            abort(403, 'Permission de validation requise.');
        }

        Database::run(
            'UPDATE content_validations SET statut = ?, valide_par = ?, motif = ?, updated_at = NOW() WHERE modele = ? AND modele_id = ? AND statut = ?',
            ['PUBLIE', Session::userId(), $motif, $model, $modelId, 'EN_ATTENTE']
        );

        AuditLog::log('content.approve', $model, $modelId, [
            'valide_par' => Session::userId(),
            'motif'      => $motif,
        ]);

        return true;
    }

    /**
     * Reject content (admin only).
     */
    public static function rejectContent(
        string $model,
        int $modelId,
        string $motif = ''
    ): bool {
        $user = Session::user();

        if (! Rbac::hasPermission('content.approve')) {
            abort(403, 'Permission de validation requise.');
        }

        Database::run(
            'UPDATE content_validations SET statut = ?, valide_par = ?, motif = ?, updated_at = NOW() WHERE modele = ? AND modele_id = ? AND statut = ?',
            ['REJETE', Session::userId(), $motif, $model, $modelId, 'EN_ATTENTE']
        );

        AuditLog::log('content.reject', $model, $modelId, [
            'valide_par' => Session::userId(),
            'motif'      => $motif,
        ]);

        return true;
    }

    /**
     * Get content validation status.
     */
    public static function contentStatus(string $model, int $modelId): ?array
    {
        return Database::one(
            'SELECT * FROM content_validations WHERE modele = ? AND modele_id = ?',
            [$model, $modelId]
        );
    }

    /**
     * Notify administrators of sensitive actions.
     */
    private static function notifyAdmins(string $action, string $module, ?int $modelId, ?array $user): void
    {
        $sensitiveActions = [
            'association.create',
            'association.update',
            'association.delete',
            'evenement.publish',
            'evenement.delete',
            'user.role',
            'user.statut',
            'module.toggle',
            'rule.create',
            'rule.update',
            'settings.update',
        ];

        if (! in_array($action, $sensitiveActions, true)) {
            return;
        }

        try {
            $admins = Database::all(
                "SELECT id FROM users WHERE role_user = 'wilaya' AND status = 'actif'"
            );

            foreach ($admins as $admin) {
                Database::run(
                    'INSERT INTO notifications (user_id, titre, message_notif, type, url) VALUES (?, ?, ?, ?, ?)',
                    [
                        (int) $admin['id'],
                        'Action sensible détectée',
                        ($user['prenom'] ?? 'Utilisateur') . ' a effectué : ' . $action . ' sur ' . $module . ($modelId ? ' (ID: ' . $modelId . ')' : ''),
                        'alert',
                        url('control/audit'),
                    ]
                );
            }
        } catch (\Throwable) {
            // Never break the main flow for notifications
        }
    }
}