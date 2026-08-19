<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Support\PriceNormalizer;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class PriceNormalizerTest extends TestCase
{
    public function testConvertsPersianAndArabicDigitsToEnglish(): void
    {
        $this->assertSame('1234567890', PriceNormalizer::toEnglishDigits('۱۲۳۴۵۶۷۸۹۰'));
        $this->assertSame('1234567890', PriceNormalizer::toEnglishDigits('١٢٣٤٥٦٧٨٩٠'));
    }

    public function testParsesComplexPricesCorrectly(): void
    {
        $this->assertSame(885500.0, PriceNormalizer::parsePrice('۸۸۵,۵۰۰'));
        $this->assertSame(885500.0, PriceNormalizer::parsePrice('885,500 ریال'));
        $this->assertSame(96500.5, PriceNormalizer::parsePrice('$ 96,500.50'));
        $this->assertSame(-1500.0, PriceNormalizer::parsePrice('-۱,۵۰۰'));
        $this->assertSame(-2500.0, PriceNormalizer::parsePrice('(۲,۵۰۰-)'));
        $this->assertNull(PriceNormalizer::parsePrice('---'));
        $this->assertNull(PriceNormalizer::parsePrice(null));
    }

    public function testParsesPercentage(): void
    {
        $this->assertSame(2.45, PriceNormalizer::parsePercentage('+ ۲.۴۵ %'));
        $this->assertSame(-0.85, PriceNormalizer::parsePercentage('-۰.۸۵ %'));
        $this->assertSame(-0.85, PriceNormalizer::parsePercentage('(% ۰.۸۵-)'));
    }

    public function testCleansWhitespaceAndSpecialCharacters(): void
    {
        $input = "  دلار \u{200c} آمریکا \t \n ";
        $this->assertSame('دلار آمریکا', PriceNormalizer::cleanText($input));
    }
}
