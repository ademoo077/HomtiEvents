<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Service de génération / gestion du QR code d'un événement.
 *
 * Le QR est identifié de façon unique par le token UUID v4 stocké dans
 * `qr_event.token_qr` (déjà présent) — c'est l'identifiant partageable &
 * vérifiable sans énumération. Ce service ajoute la persistance du fichier
 * PNG sur disque (spec "Option 1 : chemin stocké, pas l'image en BDD") dans
 * `public/qr/event_{id}.png` et la colonne `evenements.qr_code_path`.
 */
final class QrCodeService
{
    /**
     * Génère (ou régénère) le QR code d'un événement programmé et persiste le PNG.
     *
     * @return array{token: string, url: string, path: string, file_url: string}
     */
    public static function generate(int $eventId, ?string $date = null, ?string $heure = null): array
    {
        $event = Database::one(
            'SELECT date_evenement, heure, association_id, adresse, description
             FROM evenements WHERE id = ? AND deleted_at IS NULL',
            [$eventId]
        );
        if ($event === null) {
            abort(404, 'Événement introuvable.');
        }

        $qr = QrCodeGenerator::createForEvenement(
            $eventId,
            $date ?? (string) $event['date_evenement'],
            $heure ?? (string) ($event['heure'] ?? '00:00:00')
        );

        $path = self::filepath($eventId);
        QrCodeGenerator::pngToFile($qr['url'], $path, 300);

        Database::update(
            'evenements',
            ['qr_code_path' => $path],
            'id = ?',
            [$eventId]
        );

        AuditLog::log('qrcode.generate', 'evenement', $eventId, null, ['path' => $path]);

        self::notifierQrDisponible($eventId, $event);

        return [
            'token'    => (string) $qr['token'],
            'url'      => (string) $qr['url'],
            'path'     => $path,
            'file_url' => self::fileUrl($eventId),
        ];
    }

    /** Chemin absolu du fichier PNG sur disque. */
    public static function filepath(int $eventId): string
    {
        return public_path('qr/event_' . $eventId . '.png');
    }

    /** URL publique du fichier PNG (pour un <img src>). */
    public static function fileUrl(int $eventId): string
    {
        return asset('qr/event_' . $eventId . '.png');
    }

    /** URL courte shareable (checkin/{uuid}) — token de sécurité. */
    public static function getQrCodeUrl(int $eventId): ?string
    {
        $token = QrCodeGenerator::tokenForEvent($eventId);

        return $token === null ? null : url('checkin/' . $token);
    }

    /**
     * Notifie le destinataire concerné (association porteuse, sinon la Wilaya)
     * que le QR code de l'événement est disponible.
     *
     * @param array<string, mixed> $event
     */
    private static function notifierQrDisponible(int $eventId, array $event): void
    {
        $titre = (string) ($event['description'] ?? $event['adresse'] ?? 'Événement n°' . $eventId);
        $url   = self::getQrCodeUrl($eventId);

        if ((int) ($event['association_id'] ?? 0) > 0) {
            Notification::sendToAssociation(
                (int) $event['association_id'],
                'QR code disponible',
                "Le QR code de votre événement '{$titre}' est disponible : {$url}",
                'qr_disponible',
                ['evenement_id' => $eventId, 'url' => $url]
            );

            return;
        }

        Notification::sendToRole(
            'wilaya',
            'QR code disponible',
            "Le QR code de l'événement '{$titre}' est disponible : {$url}",
            'qr_disponible',
            ['evenement_id' => $eventId, 'url' => $url]
        );
    }

    public static function path(int $eventId): ?string
    {
        $val = Database::value('SELECT qr_code_path FROM evenements WHERE id = ?', [$eventId]);

        return $val !== null && $val !== '' ? (string) $val : null;
    }

    public static function has(int $eventId): bool
    {
        return QrCodeGenerator::tokenForEvent($eventId) !== null;
    }

    /** Supprime le token, le fichier et l'ancien chemin. */
    public static function deleteQrCode(int $eventId): void
    {
        Database::delete('qr_event', 'evenement_id = ?', [$eventId]);

        $file = self::filepath($eventId);
        if (is_file($file)) {
            @unlink($file);
        }

        Database::update('evenements', ['qr_code_path' => null], 'id = ?', [$eventId]);
    }
}
