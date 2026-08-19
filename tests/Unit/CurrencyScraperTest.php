<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Enums\AggregationStrategy;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Exceptions\RateNotFoundException;
use Aicrion\IrCurrencyRateScraper\Providers\ArzdigitalProvider;
use Aicrion\IrCurrencyRateScraper\Providers\TgjuProvider;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class CurrencyScraperTest extends TestCase
{
    private CurrencyScraper $scraper;
    private HttpClientInterface $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $currencyHtml = $this->loadFixture('tgju/currency_table.html');
        $profileDollarHtml = $this->loadFixture('tgju/profile_dollar.html');
        $goldHtml = $this->loadFixture('tgju/gold_table.html');
        $coinHtml = $this->loadFixture('tgju/coin_table.html');
        $arzdigitalJson = $this->loadFixture('arzdigital/coins.json');

        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockHttpClient->method('get')->willReturnCallback(function (string $url) use (
            $currencyHtml,
            $profileDollarHtml,
            $goldHtml,
            $coinHtml,
            $arzdigitalJson
        ) {
            if (strpos($url, 'arzdigital.com') !== false) {
                return $arzdigitalJson;
            }
            if (strpos($url, '/currency') !== false) {
                return $currencyHtml;
            }
            if (strpos($url, '/profile/price_dollar_rl') !== false) {
                return $profileDollarHtml;
            }
            if (strpos($url, '/gold-chart') !== false) {
                return $goldHtml;
            }
            if (strpos($url, '/coin') !== false) {
                return $coinHtml;
            }

            return '<html><body></body></html>';
        });

        $this->scraper = new CurrencyScraper([
            new TgjuProvider($this->mockHttpClient),
            new ArzdigitalProvider($this->mockHttpClient),
        ]);
    }

    public function testGetUsdRate(): void
    {
        $usd = $this->scraper->getUsd();
        $this->assertSame('USD', $usd->getSymbol());
        $this->assertSame(885500.0, $usd->getPrice());
        $this->assertSame(88550.0, $usd->getPriceInTomans());
    }

    public function testGetGoldAndCoin(): void
    {
        $gold18 = $this->scraper->getGold18k();
        $this->assertSame(48500000.0, $gold18->getPrice());

        $coinEmami = $this->scraper->getCoinEmami();
        $this->assertSame(550000000.0, $coinEmami->getPrice());
    }

    public function testSearchAcrossAllCategories(): void
    {
        $results = $this->scraper->search('دلار');
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->assertSame('USD', $results->first()->getSymbol());
    }

    public function testAggregateAverageStrategy(): void
    {
        // For crypto BTC in Arzdigital (96550.0)
        $aggregated = $this->scraper->aggregate('BTC', RateCategory::CRYPTO, AggregationStrategy::AVERAGE);

        $this->assertSame(AggregationStrategy::AVERAGE, $aggregated->getStrategy());
        $this->assertSame(96550.0, $aggregated->getAveragePrice());
        $this->assertCount(1, $aggregated->getRates());
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(RateNotFoundException::class);
        $this->scraper->getRate('NON_EXISTENT_CURRENCY_XYZ');
    }
}
