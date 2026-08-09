<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\I18n;

final class LangController
{
    public function translations(string $locale): never
    {
        if (! in_array($locale, ['fr', 'ar'], true)) {
            json_response(['success' => false, 'message' => 'Locale inconnue.'], 400);
        }

        json_response([
            'success'   => true,
            'locale'    => $locale,
            'direction' => I18n::direction($locale),
            'data'      => I18n::lines($locale),
        ]);
    }
}
