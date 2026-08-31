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
            'url'        => public_url('checkin/' . $token),
            'expiration' => $expiration,
        ];
    }

    public static function findByToken(string $token): ?array
    {
        return Database::one(
            'SELECT q.*, e.statut, e.date_evenement, e.heure, e.adresse, e.description
             FROM qr_event q
             JOIN evenements e ON e.id = q.evenement_id AND e.deleted_at IS NULL
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

        if (! in_array(($qr['statut'] ?? ''), ['PROGRAMME', 'QR_GENERE', 'EN_COURS'], true)) {
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

    /**
     * Capacité restante (quota de passages) : null si aucune capacité fixée.
     *
     * @return int|null Nombre de places encore disponibles, null = illimité.
     */
    public static function placesRestantes(int $evenementId): ?int
    {
        $capacite = Database::value(
            'SELECT capacite FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$evenementId]
        );

        if ($capacite === null) {
            return null;
        }

        $inscrits = (int) Database::value(
            'SELECT COUNT(*) FROM evenement_participant WHERE evenement_id = ?',
            [$evenementId]
        );

        return max(0, (int) $capacite - $inscrits);
    }

    /**
     * L'événement a-t-il atteint sa capacité maximale ?
     */
    public static function estComplet(int $evenementId): bool
    {
        return self::placesRestantes($evenementId) === 0;
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
     * Un invité (même numéro de téléphone) est-il déjà inscrit à l'événement ?
     */
    public static function inviteeDejaInscrit(int $evenementId, string $telephone): bool
    {
        return Database::exists(
            'SELECT 1 FROM participations_invitees WHERE evenement_id = ? AND telephone = ?',
            [$evenementId, trim($telephone)]
        );
    }

    /**
     * Enregistre une participation « invité » (sans compte) à un événement.
     */
    public static function registerInvitee(int $evenementId, array $data, string $qrToken): bool
    {
        try {
            Database::insert('participations_invitees', [
                'evenement_id' => $evenementId,
                'qr_token'     => $qrToken,
                'nom'          => mb_substr(trim((string) ($data['nom'] ?? '')), 0, 100),
                'prenom'       => mb_substr(trim((string) ($data['prenom'] ?? '')), 0, 100),
                'telephone'    => mb_substr(trim((string) ($data['telephone'] ?? '')), 0, 30),
                'ip_address'   => client_ip(),
                'user_agent'   => mb_substr(client_user_agent(), 0, 255),
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }

        AuditLog::log(
            'participation_invite',
            'evenement',
            $evenementId,
            null,
            ['telephone' => (string) ($data['telephone'] ?? ''), 'qr' => $qrToken]
        );

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function inviteesPourEvenement(int $evenementId): array
    {
        return Database::all(
            'SELECT id, nom, prenom, telephone, ip_address, created_at
             FROM participations_invitees
             WHERE evenement_id = ?
             ORDER BY created_at DESC',
            [$evenementId]
        );
    }
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
