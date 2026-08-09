<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\I18n;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    public function testPickFrench(): void
    {
        $this->assertSame('Bonjour', I18n::pick('Bonjour', 'مرحبا', 'fr'));
    }

    public function testPickArabic(): void
    {
        $this->assertSame('مرحبا', I18n::pick('Bonjour', 'مرحبا', 'ar'));
    }

    public function testPickArabicFallsBackToFrench(): void
    {
        $this->assertSame('Bonjour', I18n::pick('Bonjour', '', 'ar'));
        $this->assertSame('Bonjour', I18n::pick('Bonjour', null, 'ar'));
    }

    public function testPickWithoutArguments(): void
    {
        $this->assertSame('', I18n::pick(null, null, 'fr'));
    }

    public function testDirection(): void
    {
        $this->assertTrue(I18n::isRtl('ar'));
        $this->assertFalse(I18n::isRtl('fr'));
        $this->assertSame('rtl', I18n::direction('ar'));
        $this->assertSame('ltr', I18n::direction('fr'));
    }

    public function testLangAttribute(): void
    {
        $this->assertSame('ar', I18n::langAttribute('ar'));
        $this->assertSame('fr', I18n::langAttribute('fr'));
    }

    public function testSetRejectsUnsupportedLocale(): void
    {
        $before = I18n::locale();
        I18n::set('de');
        $this->assertSame($before, I18n::locale(), 'Un locale non supporté doit être ignoré.');
    }
}
