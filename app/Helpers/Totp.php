<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * RFC 6238 TOTP (Time-Based One-Time Password).
 * Pure PHP — pas de dépendance externe.
 */
final class Totp
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 20): string
    {
        $secret = '';
        $chars  = self::BASE32_CHARS;
        $max    = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Génère l'URL otpauth:// pour les apps authenticator.
     */
    public static function provisioningUri(string $secret, string $email, string $issuer = 'Wilaya Harmonia'): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'digits' => '6',
            'period' => '30',
            'algorithm' => 'SHA1',
        ]);

        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $email) . '?' . $params;
    }

    /**
     * Vérifie un code TOTP (±1 intervalle de 30s pour tolérance).
     */
    public static function verify(string $secret, string $code, int $tolerance = 1): bool
    {
        $time = (int) floor(time() / 30);

        for ($i = -$tolerance; $i <= $tolerance; $i++) {
            $expected = self::generateCode($secret, $time + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Génère le code TOTP actuel pour un secret donné.
     */
    public static function generateCode(string $secret, ?int $time = null): string
    {
        if ($time === null) {
            $time = (int) floor(time() / 30);
        }

        $timeHex = str_pad(dechex($time), 16, '0', STR_PAD_LEFT);
        $key     = self::base32Decode($secret);
        $hash    = hash_hmac('sha1', pack('H*', $timeHex), $key, true);

        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
                | ((ord($hash[$offset + 1]) & 0xff) << 16)
                | ((ord($hash[$offset + 2]) & 0xff) << 8)
                | (ord($hash[$offset + 3]) & 0xff);

        $otp = $binary % 1_000_000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Décode une chaîne Base32 (RFC 4648).
     */
    private static function base32Decode(string $input): string
    {
        $input  = strtoupper(trim($input, '='));
        $chars  = self::BASE32_CHARS;
        $buffer = 0;
        $bits   = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($chars, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bits  += 5;

            if ($bits >= 8) {
                $bits   -= 8;
                $output .= chr(($buffer >> $bits) & 0xff);
            }
        }

        return $output;
    }

    /**
     * Encode Base32 (pour export ou debug).
     */
    public static function base32Encode(string $data): string
    {
        $chars  = self::BASE32_CHARS;
        $output = '';
        $buffer = 0;
        $bits   = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bits  += 8;

            while ($bits >= 5) {
                $bits   -= 5;
                $output .= $chars[($buffer >> $bits) & 0x1f];
            }
        }

        if ($bits > 0) {
            $output .= $chars[($buffer << (5 - $bits)) & 0x1f];
        }

        return $output;
    }
}
