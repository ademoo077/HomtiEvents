<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Envoi Web Push conforme RFC 8292 (VAPID).
 */
final class WebPush
{
    public static function send(array $subscription, string $payload): bool
    {
        $endpoint = $subscription['endpoint'];
        $p256dh   = $subscription['p256dh'];
        $auth     = $subscription['auth'];
        $public   = config('vapid.public');
        $private  = config('vapid.private');
        $subject  = config('vapid.subject');

        if ($endpoint === '' || $p256dh === '' || $auth === '' || $public === '' || $private === '') {
            return false;
        }

        try {
            $payloadBytes = (string) $payload;
            $salt         = random_bytes(16);

            $clientKey   = base64_decode(strtr($p256dh, '-_', '+/'), true);
            $clientAuth  = base64_decode(strtr($auth, '-_', '+/'), true);

            if ($clientKey === false || $clientAuth === false) {
                return false;
            }

            $localKeyPair = sodium_crypto_box_keypair();
            $localPublic  = sodium_crypto_box_publickey($localKeyPair);
            $localPrivate = sodium_crypto_box_secretkey($localKeyPair);

            $ikm = sodium_crypto_pwhash_scryptsalsa208sha256_ll($clientAuth, $salt, 16, 1, 1, 32);

            $shared = sodium_crypto_scalarmult($localPrivate, $clientKey);
            $prk    = hash_hkdf('sha256', $shared, 32, "Content-Encoding: auth\x00", $salt);

            $cekInfo = "Content-Encoding: aes128gcm\x00" . pack('J', strlen($clientKey)) . $clientKey;
            $cek     = hash_hkdf('sha256', $prk, 16, $cekInfo, $ikm);

            $nonceInfo = "Content-Encoding: nonce\x00" . pack('J', strlen($clientKey)) . $clientKey;
            $nonce     = substr(hash_hkdf('sha256', $prk, 12, $nonceInfo, $ikm), 0, 12);

            $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($payloadBytes, '', $nonce, $cek);

            $header  = pack('N', strlen($salt)) . $salt;
            $header .= pack('N', 4096);
            $header .= pack('C', 65) . $localPublic;

            $body = $header . $ciphertext;

            $headers = [
                'TTL: 86400',
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Authorization: vapid t=' . self::vapidToken($endpoint, $public, $private, $subject),
                'Content-Length: ' . strlen($body),
            ];

            return self::httpPost($endpoint, $headers, $body);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function vapidToken(string $endpoint, string $publicKey, string $privateKey, string $subject): string
    {
        $url = parse_url($endpoint);
        $aud = ($url['scheme'] ?? 'https') . '://' . ($url['host'] ?? '') . ($url['port'] ?? '' !== '' ? ':' . ($url['port'] ?? '') : '');

        $headers = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $aud,
            'exp' => time() + 12 * 3600,
            'sub' => $subject,
        ];

        $signer = sodium_crypto_sign_detached(
            base64UrlEncode(json_encode($headers)) . '.' . base64UrlEncode(json_encode($payload)),
            sodium_crypto_sign_seed_keypair(base64_decode(strtr($privateKey, '-_', '+/'), true))
        );

        return base64UrlEncode(json_encode($headers))
            . '.' . base64UrlEncode(json_encode($payload))
            . '.' . base64UrlEncode($signer);
    }

    private static function httpPost(string $url, array $headers, string $body): bool
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers) . "\r\n",
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return false;
        }

        $status = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s(\d+)#', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }

        return $status >= 200 && $status < 300;
    }
}
