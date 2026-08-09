<?php

declare(strict_types=1);

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Génération de rapports PDF (DomPDF).
 */
final class PdfHelper
{
    public static function render(string $html, array $options = []): string
    {
        $defaults = [
            'isHtml5ParserEnabled'    => true,
            'isRemoteEnabled'         => true,
            'defaultFont'             => 'DejaVu Sans',
            'isPhpEnabled'            => false,
            'chroot'                  => base_path(),
        ];

        $dompdf = new Dompdf(new Options(array_merge($defaults, $options)));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($options['paper'] ?? 'A4', $options['orientation'] ?? 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public static function download(string $html, string $filename): never
    {
        $pdf = self::render($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    public static function save(string $html, string $filename): string
    {
        $dir = storage_path('pdfs');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . '/' . $filename;
        file_put_contents($path, self::render($html));

        return $path;
    }

    public static function badge(int $evenementId): string
    {
        $event = Database::one(
            'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom
             FROM evenements e
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN associations a ON a.id = e.association_id
             WHERE e.id = ?',
            [$evenementId]
        );

        if ($event === null) {
            return '';
        }

        $html = view('pdfs.evenement', ['event' => $event]);

        return self::render($html);
    }
}
