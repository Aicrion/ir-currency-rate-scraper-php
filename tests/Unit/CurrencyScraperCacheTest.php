<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Cache\ArrayCache;
use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Providers\ArzdigitalProvider;
use Aicrion\IrCurrencyRateScraper\Providers\TgjuProvider;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class CurrencyScraperCacheTest extends TestCase
{
    public function testWarmupPopulatesCacheAndEliminatesDuplicateHttpCalls(): void
    {
        $currencyHtml = $this->loadFixture('tgju/currency_table.html');
        $arzdigitalJson = $this->loadFixture('arzdigital/coins.json');

        $callCount = 0;
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('get')->willReturnCallback(function (string $url) use (
            $currencyHtml,
            $arzdigitalJson,
            &$callCount
        ) {
            $callCount++;
            if (strpos($url, 'arzdigital.com') !== false) {
                return $arzdigitalJson;
            }
            if (strpos($url, '/currency') !== false) {
                return $currencyHtml;
            }

            return '<html><body><table class="market-table"><tbody></tbody></table></body></html>';
        });

        $cache = new ArrayCache();
        $scraper = new CurrencyScraper([
            new TgjuProvider($mockHttpClient),
            new ArzdigitalProvider($mockHttpClient),
        ], $cache, 300);

        // 1. Warmup
        $allRates = $scraper->warmup([RateCategory::FREE_CURRENCY, RateCategory::CRYPTO]);
        $this->assertNotEmpty($allRates);
        $this->assertTrue($scraper->isCached(RateCategory::FREE_CURRENCY));
        $this->assertTrue($scraper->isCached(RateCategory::CRYPTO));

        $initialCalls = $callCount;
        $this->assertGreaterThan(0, $initialCalls);

        // 2. Subsequent getRate queries should be served 100% from cache without increasing HTTP call count
        $usd = $scraper->getUsd();
        $this->assertSame('USD', $usd->getSymbol());
        $this->assertSame($initialCalls, $callCount, 'HTTP call count should not increase when serving from cache');

        $btc = $scraper->getCrypto('BTC');
        $this->assertSame('BTC', $btc->getSymbol());
        $this->assertSame($initialCalls, $callCount, 'HTTP call count should not increase when serving from cache');

        // 3. Clear cache and verify isCached is false
        $scraper->clearCache();
        $this->assertFalse($scraper->isCached(RateCategory::FREE_CURRENCY));
    }

    public function testForceRefreshBypassesCache(): void
    {
        $currencyHtml = $this->loadFixture('tgju/currency_table.html');
        $callCount = 0;

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('get')->willReturnCallback(function (string $url) use ($currencyHtml, &$callCount) {
            $callCount++;
            return $currencyHtml;
        });

        $scraper = new CurrencyScraper([new TgjuProvider($mockHttpClient)], new ArrayCache(), 300);

        // First call populates cache
        $scraper->getUsd();
        $this->assertSame(1, $callCount);

        // Second call with default cache hits cache (no new HTTP call)
        $scraper->getUsd();
        $this->assertSame(1, $callCount);

        // Third call with forceRefresh=true forces a new HTTP request
        $scraper->getUsd(forceRefresh: true);
        $this->assertSame(2, $callCount);
    }
}
