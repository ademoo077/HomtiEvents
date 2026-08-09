<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Journal d'audit immuable (les lignes ne sont jamais modifiées).
 */
final class AuditLog
{
    public static function log(
        string $action,
        string $modele,
        ?int $modeleId = null,
        ?array $anciennesValeurs = null,
        ?array $nouvellesValeurs = null,
        ?int $userId = null,
    ): int {
        return Database::insert('audit_logs', [
            'user_id'          => $userId ?? Session::userId(),
            'action'           => $action,
            'modele'           => $modele,
            'modele_id'        => $modeleId,
            'anciennes_valeurs' => $anciennesValeurs !== null ? json_encode($anciennesValeurs, JSON_UNESCAPED_UNICODE) : null,
            'nouvelles_valeurs' => $nouvellesValeurs !== null ? json_encode($nouvellesValeurs, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'       => client_ip(),
            'user_agent'       => mb_substr(client_user_agent(), 0, 255),
        ]);
    }

    public static function logWithDetails(
        string $action,
        string $modele,
        ?int $modeleId,
        ?array $anciennesValeurs,
        ?array $nouvellesValeurs,
        ?string $statut = 'succes',
        ?int $userId = null,
    ): int {
        return Database::insert('audit_logs', [
            'user_id'          => $userId ?? Session::userId(),
            'action'           => $action,
            'modele'           => $modele,
            'modele_id'        => $modeleId,
            'anciennes_valeurs' => $anciennesValeurs !== null ? json_encode($anciennesValeurs, JSON_UNESCAPED_UNICODE) : null,
            'nouvelles_valeurs' => $nouvellesValeurs !== null ? json_encode($nouvellesValeurs, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'       => client_ip(),
            'user_agent'       => mb_substr(client_user_agent(), 0, 255),
            'statut'           => $statut,
        ]);
    }

    public static function historique(int $evenementId, string $action, ?string $observation = null, ?int $userId = null): int
    {
        return Database::insert('historique_evenement', [
            'evenement_id' => $evenementId,
            'user_id'      => $userId ?? Session::userId(),
            'action'       => $action,
            'observation'  => $observation,
            'ip_address'   => client_ip(),
            'user_agent'   => mb_substr(client_user_agent(), 0, 255),
        ]);
    }

    public static function historiqueEvenement(int $evenementId): array
    {
        return Database::all(
            'SELECT h.*, u.nom, u.prenom
             FROM historique_evenement h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.evenement_id = ?
             ORDER BY h.date_action DESC',
            [$evenementId]
        );
    }

    public static function all(string $search = '', int $limit = 100, int $offset = 0, string $statut = '', string $action = ''): array
    {
        $sql = 'SELECT a.*, u.nom, u.prenom, u.email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id';
        $params = [];
        $wheres = [];

        if ($search !== '') {
            $wheres[] = '(a.action LIKE ? OR a.modele LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($statut !== '') {
            $wheres[] = 'a.statut = ?';
            $params[] = $statut;
        }

        if ($action !== '') {
            $wheres[] = 'a.action = ?';
            $params[] = $action;
        }

        if (! empty($wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return Database::all($sql, $params);
    }

    public static function count(string $search = '', string $statut = '', string $action = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM audit_logs a';
        $params = [];
        $wheres = [];

        if ($search !== '') {
            $wheres[] = '(a.action LIKE ? OR a.modele LIKE ? OR a.ip_address LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($statut !== '') {
            $wheres[] = 'a.statut = ?';
            $params[] = $statut;
        }

        if ($action !== '') {
            $wheres[] = 'a.action = ?';
            $params[] = $action;
        }

        if (! empty($wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        return (int) Database::value($sql, $params);
    }

    public static function export(string $search = '', string $statut = '', string $action = '', int $limit = 10000): array
    {
        return self::all($search, $limit, 0, $statut, $action);
    }

    public static function stats(int $days = 30): array
    {
        $sql = 'SELECT action, COUNT(*) AS total,
                       SUM(CASE WHEN statut = \'succes\' THEN 1 ELSE 0 END) AS succes,
                       SUM(CASE WHEN statut = \'echec\' THEN 1 ELSE 0 END) AS echec
                FROM audit_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action
                ORDER BY total DESC';

        return Database::all($sql, [$days]);
    }

    public static function recent(int $limit = 20): array
    {
        return Database::all(
            'SELECT a.*, u.nom, u.prenom
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT ' . (int) $limit
        );
    }
}
