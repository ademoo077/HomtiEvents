<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Sécurité renforcée : 2FA, limitation de tentatives, blocage IP,
 * détection d'anomalies, gestion avancée des sessions.
 */
final class Security
{
    /**
     * Enregistre un événement de sécurité.
     */
    public static function evenement(string $type, string $message = '', int $severity = 1, array $payload = [], ?int $userId = null): void
    {
        try {
            Database::run(
                'INSERT INTO security_events (type, severity, user_id, ip_address, user_agent, message, payload, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $type, $severity, $userId ?? Session::userId(), client_ip(),
                    mb_substr(client_user_agent(), 0, 500), $message,
                    json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}', 'open',
                ]
            );
        } catch (\Throwable) {
            // Ne jamais casser le flux sur un enregistrement de log
        }
    }

    /**
     * Vérifie si l'IP courante est bloquée.
     */
    public static function ipBloquee(): bool
    {
        $ip = client_ip();

        try {
            $blocked = Database::one(
                'SELECT id FROM blocked_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())',
                [$ip]
            );

            return $blocked !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Bloque une IP (automatique ou manuelle).
     */
    public static function bloquerIp(string $ip, string $raison, string $trigger = 'auto', ?int $dureeMin = null): void
    {
        $expires = $dureeMin !== null ? date('Y-m-d H:i:s', time() + $dureeMin * 60) : null;

        Database::run(
            'INSERT INTO blocked_ips (ip_address, raison, trigger_type, expires_at, blocked_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE raison = VALUES(raison), expires_at = VALUES(expires_at)',
            [$ip, $raison, $trigger, $expires, Session::userId()]
        );

        self::evenement('ip_blocked', 'IP bloquée : ' . $raison, 3, ['ip' => $ip, 'raison' => $raison]);
    }

    /**
     * Limite les tentatives de connexion et bloque l'IP au-delà du seuil.
     *
     * @return bool true = autorisé, false = bloqué
     */
    public static function limiteTentatives(string $key, int $max = 5, int $windowMinutes = 10): bool
    {
        $ip = client_ip();
        $cacheKey = "login_attempts:{$key}:{$ip}";
        $count = (int) ($_SESSION[$cacheKey] ?? 0);
        $start = (int) ($_SESSION[$cacheKey . ':start'] ?? time());

        if (time() - $start > $windowMinutes * 60) {
            $count = 0;
            $start = time();
        }

        $count++;
        $_SESSION[$cacheKey] = $count;
        $_SESSION[$cacheKey . ':start'] = $start;

        if ($count > $max) {
            $duree = (int) self::param('securite.blocage_duree_min', 10);
            self::bloquerIp($ip, 'Trop de tentatives de connexion', 'auto', $duree);
            self::evenement('login_fail', 'IP bloquée après ' . $count . ' tentatives', 3, ['ip' => $ip]);

            return false;
        }

        return true;
    }

    /**
     * Génère un code 2FA temporaire (méthode email).
     */
    public static function genererCode2fa(int $userId): string
    {
        $code = (string) random_int(100000, 999999);

        Database::run(
            'INSERT INTO two_factor (user_id, method, enabled, confirmed, code, code_expires_at)
             VALUES (?, ?, 1, 0, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
             ON DUPLICATE KEY UPDATE code = VALUES(code), code_expires_at = VALUES(code_expires_at)',
            [$userId, 'email', $code]
        );

        self::evenement('tfa_code', 'Code de vérification 2FA généré', 1, ['user_id' => $userId]);

        return $code;
    }

    /**
     * Valide un code 2FA.
     */
    public static function validerCode2fa(int $userId, string $code): bool
    {
        $row = Database::one(
            'SELECT id FROM two_factor WHERE user_id = ? AND enabled = 1 AND code = ? AND code_expires_at > NOW()',
            [$userId, $code]
        );

        if ($row === null) {
            self::evenement('tfa_fail', 'Code 2FA invalide', 2, ['user_id' => $userId]);

            return false;
        }

        Database::run('UPDATE two_factor SET confirmed = 1 WHERE id = ?', [(int) $row['id']]);
        self::evenement('tfa_success', '2FA validé', 1, ['user_id' => $userId]);

        return true;
    }

    /**
     * Sessions actives d'un utilisateur.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function sessionsActives(int $userId = 0): array
    {
        $sql = 'SELECT * FROM sessions WHERE 1 = 1';
        $params = [];

        if ($userId > 0) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }

        return Database::all($sql . ' ORDER BY last_activity DESC', $params);
    }

    /**
     * Tente de récupérer un paramètre système.
     */
    public static function param(string $cle, mixed $defaut = null): mixed
    {
        static $settings = null;

        if ($settings === null) {
            try {
                $settings = [];
                foreach (Database::all('SELECT cle, valeur, type FROM system_settings') as $row) {
                    $settings[$row['cle']] = self::cast($row['valeur'], $row['type']);
                }
            } catch (\Throwable) {
                $settings = [];
            }
        }

        return $settings[$cle] ?? $defaut;
    }

    private static function cast(mixed $valeur, string $type): mixed
    {
        return match ($type) {
            'int'  => (int) $valeur,
            'bool' => (int) $valeur === 1 || $valeur === '1',
            'json' => json_decode((string) $valeur, true) ?? $valeur,
            default => (string) $valeur,
        };
    }
}
