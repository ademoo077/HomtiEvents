<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Business Rule Engine — moteur de règles métier dynamique.
 *
 * Règles configurables, activables/désactivables et versionnées depuis
 * le dashboard. Une règle peut bloquer, valider ou autoriser une action.
 */
final class BusinessRules
{
    private const ACTIONS_BLOQUANTES = ['blocage', 'validation', 'autorisation'];

    /**
     * Évalue une règle pour une action donnée.
     *
     * @return array{bloque: bool, regle: array|null}
     */
    public static function evaluer(string $cle, ?array $user = null, array $contexte = []): array
    {
        $regle = self::trouver($cle);

        if ($regle === null || (int) $regle['actif'] !== 1) {
            return ['bloque' => false, 'regle' => $regle];
        }

        // Règle globale "utilisateur suspendu n'accède à aucune ressource"
        if ($cle === 'utilisateur_suspendu_aucun_acces' && $user !== null) {
            $bloque = in_array($user['status'] ?? 'actif', ['suspendu', 'banni'], true);
            self::tracer($regle, $bloque, $user, $contexte);

            return ['bloque' => $bloque, 'regle' => $regle];
        }

        return ['bloque' => false, 'regle' => $regle];
    }

    /**
     * Vérifie le blocage global (utilisateur suspendu/banni) sur toute action.
     */
    public static function bloquerSiSuspendu(?array $user): void
    {
        if ($user === null) {
            return;
        }

        $statut = $user['status'] ?? 'actif';
        if (in_array($statut, ['suspendu', 'banni'], true)) {
            AuditLog::log('blocked_action', 'user', (int) $user['id'], [
                'motif' => 'Utilisateur ' . $statut,
            ]);
            abort(403, 'Votre compte est ' . ($statut === 'banni' ? 'banni' : 'suspendu') . '.');
        }
    }

    /**
     * Liste des règles (filtrées).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function liste(string $activite = ''): array
    {
        if ($activite !== '') {
            return Database::all('SELECT * FROM system_rules WHERE activite = ? ORDER BY id', [$activite]);
        }

        return Database::all('SELECT * FROM system_rules ORDER BY id');
    }

    /**
     * Crée / met à jour une règle (versionnée).
     */
    public static function enregistrer(array $data, ?int $id = null): void
    {
        $activite = $data['activite'] ?? 'blocage';

        if ($id === null) {
            Database::run(
                'INSERT INTO system_rules (cle, nom, description, activite, portee, cible, condition_sql, actif, version, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)',
                [
                    $data['cle'], $data['nom'], $data['description'] ?? null, $activite,
                    $data['portee'] ?? 'global', $data['cible'] ?? null, $data['condition_sql'] ?? null,
                    (int) ($data['actif'] ?? 1), Session::userId(),
                ]
            );
        } else {
            Database::run(
                'UPDATE system_rules SET nom = ?, description = ?, activite = ?, portee = ?, cible = ?,
                        condition_sql = ?, actif = ?, version = version + 1, updated_by = ? WHERE id = ?',
                [
                    $data['nom'], $data['description'] ?? null, $activite, $data['portee'] ?? 'global',
                    $data['cible'] ?? null, $data['condition_sql'] ?? null, (int) ($data['actif'] ?? 1),
                    Session::userId(), $id,
                ]
            );
        }

        AuditLog::log($id === null ? 'rule.create' : 'rule.update', 'system_rules', $id ?? 0, $data);
    }

    /**
     * Active / désactive une règle.
     */
    public static function basculer(int $id, bool $actif): void
    {
        Database::run('UPDATE system_rules SET actif = ? WHERE id = ?', [(int) $actif, $id]);
        AuditLog::log('rule.toggle', 'system_rules', $id, ['actif' => $actif]);
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function trouver(string $cle): ?array
    {
        return Database::one('SELECT * FROM system_rules WHERE cle = ?', [$cle]);
    }

    private static function tracer(array $regle, bool $bloque, ?array $user, array $contexte): void
    {
        if ($bloque) {
            AuditLog::log('rule.blocked', 'system_rules', (int) $regle['id'], [
                'cle'     => $regle['cle'],
                'contexte'=> $contexte,
            ]);
        }
    }
}
