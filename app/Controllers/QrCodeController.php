<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\QrCodeGenerator;
use App\Helpers\QrCodeService;

final class QrCodeController extends Controller
{
    public function scan(): never
    {
        $this->requireAuth();

        $evenements = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut,
                    q.token_qr, q.date_expiration
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.deleted_at IS NULL AND q.token_qr IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 10'
        );

        $layout = \App\Helpers\Rbac::role($this->user()) === 'citoyen' ? 'citoyen' : 'main';
        $view = \App\Helpers\Rbac::role($this->user()) === 'citoyen' ? 'qrcode/scan_citoyen' : 'qrcode/scan';

        $this->view($view, [
            'evenements' => $evenements,
        ], $layout);
    }

    public function show(string $id): never
    {
        $this->requireAuth();

        $qr = Database::one('SELECT * FROM qr_event WHERE evenement_id = ?', [(int) $id]);

        if ($qr === null) {
            abort(404, 'QR Code introuvable.');
        }

        $this->view('qrcode/show', [
            'qr' => $qr,
        ], 'main');
    }

    /**
     * Téléchargement PNG du QR code d'un événement (route publique /event/qr/download/{id}).
     */
    public function download(string $id): never
    {
        $evenementId = (int) $id;
        if ($evenementId <= 0) {
            abort(404, 'Identifiant invalide.');
        }

        $png = $this->pngBytes($evenementId);

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="qr-' . $evenementId . '.png"');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    /**
     * Diffusion en ligne du PNG (pour les <img src>) — route publique.
     */
    public function stream(string $id): never
    {
        $evenementId = (int) $id;
        if ($evenementId <= 0) {
            abort(404, 'Identifiant invalide.');
        }

        $png = $this->pngBytes($evenementId);

        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($png));
        echo $png;
        exit;
    }

    /**
     * Retourne les octets PNG d'un événement : fichier sur disque ou régénéré.
     */
    private function pngBytes(int $evenementId): string
    {
        $file = QrCodeService::filepath($evenementId);
        if (is_file($file)) {
            $bytes = @file_get_contents($file);
            if ($bytes !== false && $bytes !== '') {
                return $bytes;
            }
        }

        $token = QrCodeGenerator::tokenForEvent($evenementId);
        if ($token === null) {
            abort(404, 'QR code introuvable pour cet événement.');
        }

        $uri = QrCodeGenerator::pngDataUri(url('checkin/' . $token), 300);
        $base64 = explode(';base64,', $uri, 2)[1] ?? '';
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            abort(500, 'QR non disponible.');
        }

        return $bytes;
    }
}
