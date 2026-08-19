<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Providers\ArzdigitalProvider;
use Aicrion\IrCurrencyRateScraper\Providers\TgjuProvider;
use Aicrion\IrCurrencyRateScraper\Services\CurrencyConverter;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class CurrencyConverterTest extends TestCase
{
    private CurrencyScraper $scraper;
    private CurrencyConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $currencyHtml = $this->loadFixture('tgju/currency_table.html');
        $goldHtml = $this->loadFixture('tgju/gold_table.html');
        $arzdigitalJson = $this->loadFixture('arzdigital/coins.json');

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('get')->willReturnCallback(function (string $url) use (
            $currencyHtml,
            $goldHtml,
            $arzdigitalJson
        ) {
            if (strpos($url, 'arzdigital.com') !== false) {
                return $arzdigitalJson;
            }
            if (strpos($url, '/currency') !== false) {
                return $currencyHtml;
            }
            if (strpos($url, '/gold-chart') !== false) {
                return $goldHtml;
            }

            return '<html><body></body></html>';
        });

        $this->scraper = new CurrencyScraper([
            new TgjuProvider($mockHttpClient),
            new ArzdigitalProvider($mockHttpClient),
        ]);

        $this->converter = new CurrencyConverter($this->scraper);
    }

    public function testConvertsUsdToRialsAndTomans(): void
    {
        // USD is 885,500 Rials in fixture
        $inRials = $this->converter->convert(10.0, 'USD', 'IRR');
        $this->assertSame(8855000.0, $inRials);

        $inTomans = $this->converter->convert(10.0, 'USD', 'TOMAN');
        $this->assertSame(885500.0, $inTomans);
    }

    public function testConvertsTomansToUsd(): void
    {
        // 885,500 Tomans = 8,855,000 Rials = 10 USD
        $usd = $this->converter->convert(885500.0, 'TOMAN', 'USD');
        $this->assertEqualsWithDelta(10.0, $usd, 0.001);
    }

    public function testConvertsDirectTomanToRial(): void
    {
        $this->assertSame(500000.0, $this->converter->convert(50000.0, 'TOMAN', 'IRR'));
        $this->assertSame(50000.0, $this->converter->convert(500000.0, 'IRR', 'TOMAN'));
    }

    public function testConvertsCryptoToToman(): void
    {
        // BTC is $96,550.00, USD is 885,500 IRR (= 88,550 Toman)
        // 1 BTC = 96,550 * 88,550 Toman
        $toman = $this->converter->convert(1.0, 'BTC', 'TOMAN');
        $expected = 96550.0 * 88550.0;
        $this->assertEqualsWithDelta($expected, $toman, 1.0);
    }

    public function testCalculatesGoldJewelryBreakdown(): void
    {
        // Gold 18K in fixture is 48,500,000 Rial per gram
        // 10 grams, 10% wage, 7% profit, 9% VAT
        $calc = $this->converter->calculateGold(10.0, 18, 10.0, 7.0, 9.0);

        $this->assertSame(10.0, $calc['grams']);
        $this->assertSame(18, $calc['karat']);
        $this->assertSame(48500000.0, $calc['gram_price_rial']);
        $this->assertSame(4850000.0, $calc['gram_price_toman']);

        $baseRial = 485000000.0; // 10 * 48.5m
        $this->assertSame($baseRial, $calc['base_gold_rial']);

        $wageRial = 48500000.0; // 10% of base
        $this->assertSame($wageRial, $calc['wage_amount_rial']);

        $profitRial = (485000000.0 + 48500000.0) * 0.07; // 37,345,000
        $this->assertSame($profitRial, $calc['profit_amount_rial']);

        $taxRial = ($wageRial + $profitRial) * 0.09; // 9% on (wage + profit) = 7,726,050
        $this->assertSame($taxRial, $calc['tax_amount_rial']);

        $totalRial = $baseRial + $wageRial + $profitRial + $taxRial;
        $this->assertSame($totalRial, $calc['total_price_rial']);
        $this->assertSame($totalRial / 10.0, $calc['total_price_toman']);
    }

    public function testScraperFacadeHelperMethods(): void
    {
        $this->assertSame(8855000.0, $this->scraper->convert(10.0, 'USD', 'IRR'));
        $calc = $this->scraper->calculateGold(5.0, 18);
        $this->assertSame(5.0, $calc['grams']);
    }
}
