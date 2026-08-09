<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use PDOStatement;

/**
 * Accès à la base de données via PDO (prepared statements obligatoires).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = config('database');
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

        return self::$pdo;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * @return PDOStatement
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $row = self::one($sql, $params);

        if ($row === null) {
            return null;
        }

        return reset($row);
    }

    public static function insert(string $table, array $data): int
    {
        $cols   = array_keys($data);
        $marks  = implode(', ', array_fill(0, count($cols), '?'));
        $sql    = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $cols), $marks);

        self::run($sql, array_values($data));

        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(static fn (string $c) => $c . ' = ?', array_keys($data)));

        return self::run(sprintf('UPDATE %s SET %s WHERE %s', $table, $set, $where), [...array_values($data), ...$params])
            ->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run(sprintf('DELETE FROM %s WHERE %s', $table, $where), $params)->rowCount();
    }

    public static function exists(string $sql, array $params = []): bool
    {
        return self::value($sql, $params) !== null;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }

    /**
     * Pagination simple.
     */
    public static function paginate(string $sql, array $params = [], int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $countSql = 'SELECT COUNT(*) AS total FROM (' . $sql . ') AS __count';
        $total    = (int) self::value($countSql, $params);
        $offset   = ($page - 1) * $perPage;

        $rows = self::all($sql . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset, $params);

        return [
            'items'     => $rows,
            'total'     => $total,
            'per_page'  => $perPage,
            'page'      => $page,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }
}
