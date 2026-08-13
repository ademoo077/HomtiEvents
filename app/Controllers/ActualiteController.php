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
        $this->view('actualites.index', ActualiteService::data(), 'landing');
    }
}
