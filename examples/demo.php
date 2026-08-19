<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\Enums\AggregationStrategy;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;

echo "========================================================\n";
echo " Iranian Currency, Gold & Crypto Scraper Library Demo\n";
echo "========================================================\n\n";

// 1. Initialize with FileCache enabled
$scraper = CurrencyScraper::create();
$scraper->enableFileCache(__DIR__ . '/../cache', 300);

// Enable User-Agent rotation for resilient scraping
$scraper->enableUserAgentRotation(true);

try {
    // 2. High-Performance Warmup (Preload & Cache All Rates)
    echo "⚡ 1. Warming up cache for all markets...\n";
    $startTime = microtime(true);
    $allRates = $scraper->warmup();
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   Successfully loaded & cached {$allRates->count()} assets in {$duration} ms.\n\n";

    // 3. Instantaneous Cached Retrieval (Zero Network Latency)
    echo "🚀 2. Fetching Rates from Cache (Instant)...\n";
    $cachedStart = microtime(true);

    $usd = $scraper->getUsd();
    $gold18 = $scraper->getGold18k();
    $coin = $scraper->getCoinEmami();
    $cachedDuration = round((microtime(true) - $cachedStart) * 1000, 4);

    echo "   - USD: " . number_format($usd->getPrice()) . " Rial (" . number_format($usd->getPriceInTomans()) . " Toman)\n";
    echo "   - Gold 18K: " . number_format($gold18->getPrice()) . " Rial\n";
    echo "   - Coin Emami: " . number_format($coin->getPrice()) . " Rial\n";
    echo "   -> Retrieved in {$cachedDuration} ms!\n\n";

    // 4. Currency Conversion
    echo "💱 3. Currency Conversion:\n";
    $convertedTomans = $scraper->convert(100, 'USD', 'TOMAN');
    echo "   - 100 USD = " . number_format($convertedTomans) . " Tomans\n";

    $convertedEur = $scraper->convert(50000000, 'IRR', 'EUR');
    echo "   - 50,000,000 Rials = €" . number_format($convertedEur, 2) . "\n\n";

    // 5. Gold Jewelry Calculator (Craftsmanship wage, profit, tax)
    echo "🥇 4. Gold Jewelry Calculation (4.5g 18K Gold, 10% Wage, 7% Profit, 9% VAT):\n";
    $goldCalc = $scraper->calculateGold(4.5, 18, 10.0, 7.0, 9.0);
    echo "   - Gram Price:   " . number_format($goldCalc['gram_price_toman']) . " Toman\n";
    echo "   - Base Value:   " . number_format($goldCalc['base_gold_toman']) . " Toman\n";
    echo "   - Wage (10%):   " . number_format($goldCalc['wage_amount_rial'] / 10) . " Toman\n";
    echo "   - Profit (7%):  " . number_format($goldCalc['profit_amount_rial'] / 10) . " Toman\n";
    echo "   - VAT Tax (9%): " . number_format($goldCalc['tax_amount_rial'] / 10) . " Toman\n";
    echo "   - TOTAL PRICE:  " . number_format($goldCalc['total_price_toman']) . " Toman\n\n";

    // 6. Price Alerts Engine
    echo "🔔 5. Testing Price Alert Engine...\n";
    $scraper->addPriceAlert('USD', 500000.0, '>=', function (Rate $rate, array $alert) {
        echo "   [ALERT TRIGGERED] {$rate->getTitle()} is above " . number_format($alert['target_value']) . " Rials (Current: " . number_format($rate->getPrice()) . ")\n";
    });
    $triggered = $scraper->checkAlerts($allRates);
    echo "   Alerts checked. Total triggered: " . count($triggered) . "\n\n";

    // 7. Multi-Source Aggregation
    echo "📊 6. Aggregating Bitcoin (BTC) Price (Average Strategy)...\n";
    $aggregatedBtc = $scraper->aggregate('BTC', RateCategory::CRYPTO, AggregationStrategy::AVERAGE);
    echo "   Average Price: $" . number_format($aggregatedBtc->getAveragePrice(), 2) . "\n";
    echo "   Min: $" . number_format($aggregatedBtc->getMinPrice(), 2) . " | Max: $" . number_format($aggregatedBtc->getMaxPrice(), 2) . "\n";
    echo "   Sources Queried: {$aggregatedBtc->getSourcesCount()}\n\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
