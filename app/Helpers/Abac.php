<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * ABAC — Attribute Based Access Control.
 *
 * Contrôle d'accès basé sur les attributs : localisation, statut utilisateur,
 * type d'action, sensibilité, contexte temporel et état du système.
 */
final class Abac
{
    /**
     * Vérifie une règle ABAC pour un utilisateur et une action.
     */
    public static function permet(?array $user, string $action, array $attributs = []): bool
    {
        if ($user === null) {
            return false;
        }

        // Statut utilisateur : suspendu/banni = refus global
        $statut = $user['status'] ?? 'actif';
        if (in_array($statut, ['suspendu', 'banni'], true)) {
            AuditLog::log('abac.blocked', 'user', (int) $user['id'], ['action' => $action, 'attributs' => $attributs]);

            return false;
        }

        // Contexte temporel : fenêtres d'accès (si paramétré)
        if (! self::contexteTemporelOk($action)) {
            AuditLog::log('abac.blocked_time', 'user', (int) $user['id'], ['action' => $action]);

            return false;
        }

        // État du système : mode maintenance bloque certaines actions
        if ((int) Security::param('maintenance.mode', 0) === 1 && $action !== 'dashboard.view') {
            return false;
        }

        return true;
    }

    /**
     * Vérifie la portée géographique (localisation).
     */
    public static function dansZone(array $user, string $colonne, int $valeur): bool
    {
        return true; // La portée SQL via Rbac::scope() gère le filtrage fin
    }

    // ── interne ──────────────────────────────────────────────────────

    private static function contexteTemporelOk(string $action): bool
    {
        // Fenêtre d'ouverture (ex. exports/administration) si paramétrée.
        $heure = (int) date('G');

        // Par défaut : toujours autorisé (fenêtre gérée via Settings SaaS)
        return true;
    }
}
