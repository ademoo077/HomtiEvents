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
     * Endpoint de détection réseau : retourne l'URL courante accessible
     * depuis le même réseau. Utile au scanner pour construire les
     * URLs de checkin dynamiquement.
     */
    public function networkInfo(): never
    {
        $scheme = 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? config('app.url', 'localhost');
        // Supprimer le port de l'APP_URL si présent
        $host = preg_replace('#^https?://#', '', $host);

        $httpsHeaders = ['HTTP_X_FORWARDED_PROTO', 'HTTP_CF_VISITOR', 'HTTP_X_FORWARDED_SCHEME'];
        if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        } else {
            foreach ($httpsHeaders as $h) {
                $val = $_SERVER[$h] ?? '';
                if (stripos($val, 'https') !== false) {
                    $scheme = 'https';
                    break;
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'base_url'  => $scheme . '://' . $host,
            'scheme'    => $scheme,
            'host'      => $host,
            'scan_url'  => $scheme . '://' . $host . '/qrcode/scan-optimise',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

        $uri = QrCodeGenerator::pngDataUri(network_url('checkin/' . $token), 300);
        $base64 = explode(';base64,', $uri, 2)[1] ?? '';
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            abort(500, 'QR non disponible.');
        }

        return $bytes;
    }
}
