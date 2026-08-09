<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Internationalisation FR / AR avec support RTL.
 */
final class I18n
{
    private const RTL = ['ar'];

    private static ?string $locale = null;
    private static array $cache = [];

    public static function locale(): string
    {
        if (self::$locale !== null) {
            return self::$locale;
        }

        $supported = config('app.locales', ['fr', 'ar']);
        $locale    = (string) config('app.locale', 'fr');

        // 1. Session
        $session = $_SESSION['_locale'] ?? null;
        if (in_array($session, $supported, true)) {
            $locale = $session;
        } else {
            // 2. Cookie / accept-language
            $cookie = $_COOKIE['WH_LANG'] ?? null;
            if (in_array($cookie, $supported, true)) {
                $locale = $cookie;
            }
        }

        self::$locale = $locale;

        return $locale;
    }

    public static function set(string $locale): void
    {
        $supported = config('app.locales', ['fr', 'ar']);

        if (! in_array($locale, $supported, true)) {
            return;
        }

        self::$locale = $locale;
        $_SESSION['_locale'] = $locale;
        setcookie('WH_LANG', $locale, time() + 60 * 60 * 24 * 365, '/', '', false, true);
    }

    public static function isRtl(?string $locale = null): bool
    {
        return in_array($locale ?? self::locale(), self::RTL, true);
    }

    public static function direction(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    public static function langAttribute(?string $locale = null): string
    {
        $locale ??= self::locale();

        return $locale === 'ar' ? 'ar' : 'fr';
    }

    /**
     * @return array<string, string>
     */
    public static function lines(?string $locale = null): array
    {
        $locale ??= self::locale();

        if (isset(self::$cache[$locale])) {
            return self::$cache[$locale];
        }

        $file = (string) config('paths.lang', storage_path('..') . '/lang') . '/' . $locale . '.json';

        if (! is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return self::$cache[$locale] = is_array($decoded) ? $decoded : [];
    }

    /**
     * Sélectionne la valeur selon la locale : $ar ?? $fr.
     */
    public static function pick(?string $fr, ?string $ar, ?string $locale = null): string
    {
        $locale ??= self::locale();

        return ($locale === 'ar' && $ar !== null && $ar !== '') ? $ar : ($fr ?? '');
    }
}
