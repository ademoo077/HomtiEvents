<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\LandingService;

final class LandingController extends Controller
{
    public function index(): never
    {
        if (is_logged()) {
            redirect(dashboard_path());
        }

        $this->view('landing.index', LandingService::data(), 'landing');
    }

    /**
     * Manifest PWA dynamique : lang/dir suivent la locale (FR/AR, RTL).
     * Délivré via le routeur pour éviter le fichier statique figé.
     */
    public function manifest(): never
    {
        header('Content-Type: application/manifest+json; charset=utf-8');

        $locale = \App\Helpers\I18n::locale();
        $isAr   = \App\Helpers\I18n::isRtl($locale);

        $manifest = [
            'name'             => 'حومتي ايفانت',
            'short_name'       => 'حومتي',
            'description'      => $isAr
                ? 'منصة التنسيق المواطني: الإبلاغ والمشاركة في فعاليات الولاية.'
                : 'Plateforme de coordination citoyenne : signalement et participation aux événements de la wilaya.',
            'start_url'        => url('/'),
            'scope'            => url('/'),
            'id'               => url('/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#FAF6EC',
            'theme_color'      => '#0F2B22',
            'lang'             => $locale,
            'dir'              => $isAr ? 'rtl' : 'ltr',
            'categories'       => ['civic', 'events', 'government'],
            'prefer_related_applications' => false,
            'icons'            => [
                ['src' => url('/assets/img/icon-144.png'), 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('/assets/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('/assets/img/icon-256.png'), 'sizes' => '256x256', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('/assets/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('/assets/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                [
                    'name'  => $isAr ? 'مسح رمز QR' : 'Scanner un QR',
                    'url'   => url('/qrcode/scan'),
                    'icons' => [['src' => url('/assets/img/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name'  => $isAr ? 'الفعاليات' : 'Événements',
                    'url'   => url('/evenements'),
                    'icons' => [['src' => url('/assets/img/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name'  => $isAr ? 'لوحة القيادة' : 'Dashboard',
                    'url'   => url('/wilaya/dashboard'),
                    'icons' => [['src' => url('/assets/img/icon-192.png'), 'sizes' => '192x192']],
                ],
            ],
            'share_target'     => [
                'action'  => url('/share'),
                'method'  => 'GET',
                'params'  => ['title' => 'title', 'text' => 'text', 'url' => 'url'],
            ],
            'screenshots'      => [
                [
                    'src'     => url('/assets/img/icon-512.png'),
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'form_factor' => 'narrow',
                    'label'   => $isAr ? 'تطبيق حومتي ايفانت' : 'Application حومتي ايفانت',
                ],
            ],
        ];

        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Web Share Target handler — receives shared URLs/titles/text.
     * Redirects to the shared URL if on the same origin, otherwise to landing.
     */
    public function shareTarget(): never
    {
        $sharedUrl = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL) ?? '';
        $title     = filter_input(INPUT_GET, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
        $text      = filter_input(INPUT_GET, 'text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

        $baseHost = parse_url(url(''), PHP_URL_HOST) ?? '';
        $isValid  = false;

        if ($sharedUrl) {
            $parsed = parse_url($sharedUrl);
            if (isset($parsed['host']) && $parsed['host'] === $baseHost) {
                $isValid = true;
            }
        }

        if ($isValid) {
            header('Location: ' . $sharedUrl, true, 302);
        } else {
            $params = [];
            if ($title) $params['title'] = $title;
            if ($text)  $params['text']  = $text;
            $dest = url('/') . ($params ? '?' . http_build_query($params) : '');
            header('Location: ' . $dest, true, 302);
        }
        exit;
    }

    /**
     * API endpoint for polling new/updated albums.
     * Returns published albums modified after the given timestamp
     * (comparison sur `updated_at` : les modifications du titre, du récit
     * ou le dépôt de nouvelles photos mettent à jour ce champ).
     */
    public function galleryUpdates(): never
    {
        header('Content-Type: application/json');

        $lastTimestamp = (string) input('since', date('Y-m-d H:i:s', strtotime('-1 hour')));

        $albums = Database::all(
            'SELECT a.id, a.titre, a.recit, a.statut, a.couverture,
                         e.adresse, e.date_evenement, e.association_id, e.id AS evenement_id,
                         c.nom AS commune_nom,
                         (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos_count,
                         (SELECT p.image FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo,
                         (SELECT COALESCE(p.thumbnail, p.image) FROM photos p WHERE p.album_id = a.id AND p.status = ? ORDER BY p.uploaded_at ASC LIMIT 1) AS first_photo_src,
                         a.updated_at, a.date_creation
                    FROM albums a
                    JOIN evenements e ON e.id = a.evenement_id
                    LEFT JOIN commune c ON c.id = e.commune_id
                    WHERE a.statut = ?
                      AND (a.updated_at > ? OR EXISTS (SELECT 1 FROM photos p WHERE p.album_id = a.id AND p.status = ? AND p.uploaded_at > ?))
                    ORDER BY a.updated_at DESC LIMIT 10',
            ['publie', 'active', 'active', 'active', $lastTimestamp, 'active', $lastTimestamp]
        );

        // Enrich associations, photos et anomalies (batch — avoids N+1)
        $albumIds = array_column($albums, 'id');
        $eventIds = array_unique(array_filter(array_column($albums, 'evenement_id')));
        $assocIds = array_unique(array_filter(array_column($albums, 'association_id')));

        $allPhotos = $anomalyMap = $assocMap = [];
        if ($albumIds !== []) {
            $ph = Database::all(
                'SELECT id, image, titre, legende, sort_order, uploaded_at, album_id FROM photos WHERE album_id IN (' . implode(',', array_fill(0, count($albumIds), '?')) . ') AND status = ? ORDER BY sort_order ASC, uploaded_at DESC',
                array_merge($albumIds, ['active'])
            );
            foreach ($ph as $row) { $allPhotos[(int) $row['album_id']][] = $row; }
        }
        if ($eventIds !== []) {
            $an = Database::all(
                'SELECT ae.evenement_id, a.id, a.nom, a.icone, a.couleur FROM anomalies a JOIN anomalies_evenement ae ON ae.anomalie_id = a.id WHERE ae.evenement_id IN (' . implode(',', array_fill(0, count($eventIds), '?')) . ')',
                $eventIds
            );
            foreach ($an as $row) { $anomalyMap[(int) $row['evenement_id']][] = $row; }
        }
        if ($assocIds !== []) {
            $as = Database::all(
                'SELECT id, nom, numero_agrement, valide FROM associations WHERE id IN (' . implode(',', array_fill(0, count($assocIds), '?')) . ')',
                $assocIds
            );
            foreach ($as as $row) { $assocMap[(int) $row['id']] = $row; }
        }

        foreach ($albums as &$al) {
            $al['display_image'] = $al['couverture'] ?: ($al['first_photo_src'] ?: $al['first_photo']);
            $al['nb_photos'] = $al['nb_photos_count'];
            $al['photos'] = $allPhotos[(int) $al['id']] ?? [];
            $al['anomalies'] = $anomalyMap[(int) $al['evenement_id']] ?? [];
            $al['association'] = (! empty($al['association_id'])) ? ($assocMap[(int) $al['association_id']] ?? null) : null;
        }
        unset($al);

        json_response([
            'timestamp' => date('Y-m-d H:i:s'),
            'albums' => $albums,
        ]);
    }

    public function privacy(): never
    {
        $this->view('public.privacy', [], 'landing');
    }
}
