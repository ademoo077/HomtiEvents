<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ActualiteService;

/**
 * Page publique « Actualités & événements à venir » (/actualites).
 */
final class ActualiteController extends Controller
{
    public function index(): never
    {
        $filters = [
            'q'          => trim((string) input('q', '')),
            'du'         => input('du'),
            'au'         => input('au'),
            'commune_id' => input('commune_id'),
            'type'       => input('type'),
        ];

        $data = ActualiteService::data($filters);
        $data['filters'] = $filters;

        $this->view('actualites.index', $data, 'landing');
    }
}
