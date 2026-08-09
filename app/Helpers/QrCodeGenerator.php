<?php

declare(strict_types=1);

namespace App\Helpers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Générateur de QR Codes (endroid/qr-code) avec token UUID v4.
 */
final class QrCodeGenerator
{
    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Crée la ligne qr_event pour un événement programmé.
     */
    public static function createForEvenement(int $evenementId, ?string $dateEvenement = null, ?string $heure = null): array
    {
        $token = self::uuid();

        $expiration = null;
        if ($dateEvenement !== null && $dateEvenement !== '') {
            $expiration = date('Y-m-d H:i:s', strtotime($dateEvenement . ' +1 day'));
        }

        $debut = $dateEvenement !== null && $heure !== null
            ? date('Y-m-d H:i:s', strtotime($dateEvenement . ' ' . $heure))
            : date('Y-m-d H:i:s');

        Database::run(
            'DELETE FROM qr_event WHERE evenement_id = ?',
            [$evenementId]
        );

        Database::insert('qr_event', [
            'evenement_id'    => $evenementId,
            'token_qr'        => $token,
            'date_debut'      => $debut,
            'date_expiration' => $expiration,
        ]);

        return [
            'token'      => $token,
            'url'        => url('checkin/' . $token),
            'expiration' => $expiration,
        ];
    }

    public static function findByToken(string $token): ?array
    {
        return Database::one(
            'SELECT q.*, e.statut, e.date_evenement, e.heure, e.adresse, e.description
             FROM qr_event q
             JOIN evenements e ON e.id = q.evenement_id
             WHERE q.token_qr = ?',
            [$token]
        );
    }

    public static function tokenForEvent(int $evenementId): ?string
    {
        $qr = Database::one(
            'SELECT token_qr FROM qr_event WHERE evenement_id = ?',
            [$evenementId]
        );

        return $qr !== null ? (string) $qr['token_qr'] : null;
    }

    public static function isValid(?array $qr, bool $checkExpiration = true): bool
    {
        if ($qr === null) {
            return false;
        }

        if (($qr['statut'] ?? '') !== 'PROGRAMME') {
            return false;
        }

        if ($checkExpiration && $qr['date_expiration'] !== null && strtotime((string) $qr['date_expiration']) < time()) {
            return false;
        }

        return true;
    }

    public static function hasParticipated(int $evenementId, int $userId): bool
    {
        return Database::exists(
            'SELECT 1 FROM evenement_participant WHERE evenement_id = ? AND user_id = ?',
            [$evenementId, $userId]
        );
    }

    public static function registerParticipation(int $evenementId, int $userId, bool $already = false): bool
    {
        if ($already || self::hasParticipated($evenementId, $userId)) {
            return false;
        }

        try {
            Database::insert('evenement_participant', [
                'evenement_id' => $evenementId,
                'user_id'      => $userId,
                'heure_scan'   => date('Y-m-d H:i:s'),
                'ip_address'   => client_ip(),
                'user_agent'   => mb_substr(client_user_agent(), 0, 255),
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }

        Gamification::participation($userId, $evenementId);

        AuditLog::log('participation', 'evenement', $evenementId, null, ['user_id' => $userId], $userId);

        return true;
    }

    /**
     * Génère une image PNG en base64 ou sauvegarde sur disque.
     */
    public static function pngDataUri(string $content, int $size = 300, ?string $label = null): string
    {
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10);

        if ($label !== null) {
            $builder->label(new Label($label, null, null, new Margin(0)));
        }

        $result = $builder->build();

        return 'data:' . $result->getMimeType() . ';base64,' . base64_encode($result->getString());
    }

    public static function pngToFile(string $content, string $path, int $size = 300): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->build();

        file_put_contents($path, $result->getString());
    }
}
