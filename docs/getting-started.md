# Getting Started with Iranian Currency Rate Scraper

This guide covers installation, configuration, fundamental usage patterns, and high-performance caching for the `aicrion/ir-currency-rate-scraper` PHP library.

---

## 📥 Requirements

- **PHP**: `^7.4 || ^8.0 || ^8.1 || ^8.2 || ^8.3 || ^8.4`
- **Extensions**: `ext-curl`, `ext-json`, `ext-mbstring`
- **Composer** package manager

---

## ⚡ Installation

Install the library via Composer:

```bash
composer require aicrion/ir-currency-rate-scraper
```

---

## 🚀 Quick Usage

### 1. Basic Initialization
By default, `CurrencyScraper::create()` sets up the standard providers (`TgjuProvider` and `ArzdigitalProvider`) with a built-in cURL HTTP client:

```php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$scraper = CurrencyScraper::create();
```

---

### 2. High-Performance Caching & Warmup (0ms Latency)
To avoid making external network calls on user requests, enable caching and warm up the cache:

```php
// 1. Enable disk file cache for 5 minutes (300 seconds)
$scraper->enableFileCache(__DIR__ . '/cache', 300);

// 2. Warm up and cache all markets (Currencies, Gold, Coins, Crypto)
$allRates = $scraper->warmup();
echo "Preloaded {$allRates->count()} assets into cache!\n";

// 3. Subsequent rate lookups are served instantaneously from cache
$usd = $scraper->getUsd();
$gold18 = $scraper->getGold18k();
$coin = $scraper->getCoinEmami();

// Force fresh network query by passing forceRefresh: true
$liveUsd = $scraper->getUsd(forceRefresh: true);
```

---

### 3. Fetching Popular Currency & Gold Rates
Convenience helper methods provide direct access to commonly requested market rates:

```php
// US Dollar (Rial & Toman)
$usd = $scraper->getUsd();
echo "USD: " . number_format($usd->getPrice()) . " Rial (" . number_format($usd->getPriceInTomans()) . " Toman)\n";
echo "Change 24h: {$usd->getChangePercent()}%\n";

// Euro, British Pound, UAE Dirham
$eur = $scraper->getEur();
$gbp = $scraper->getGbp();
$aed = $scraper->getAed();

// Gold 18K & 24K
$gold18 = $scraper->getGold18k();
$gold24 = $scraper->getGold24k();

// Coins (Emami & Bahar Azadi)
$coinEmami = $scraper->getCoinEmami();
$coinBahar = $scraper->getCoinBahar();
```

---

### 4. Searching for Specific Currencies / Assets
Search by Persian title or English symbol:

```php
// Search for dollar across all markets
$results = $scraper->search('دلار');

foreach ($results as $rate) {
    echo sprintf(
        "%-20s | %-10s | %15s %s\n",
        $rate->getTitle(),
        $rate->getCategory(),
        number_format($rate->getPrice()),
        $rate->getUnit()
    );
}
```

---

### 5. Fetching Specific Market Categories
Fetch all available rates in a specific category:

```php
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;

// Fetch all SANA exchange rates
$sanaRates = $scraper->getCategory(RateCategory::SANA);
foreach ($sanaRates as $rate) {
    echo "{$rate->getTitle()}: Buy={$rate->getBuyPrice()}, Sell={$rate->getSellPrice()}\n";
}

// Fetch all Government / Bank rates
$bankRates = $scraper->getCategory(RateCategory::BANK_GOVERNMENT);
```

---

### 6. Multi-Source Aggregation
Calculate average rates or fallbacks across multiple providers:

```php
use Aicrion\IrCurrencyRateScraper\Enums\AggregationStrategy;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;

// Calculate average Bitcoin price across TGJU & ArzDigital
$btc = $scraper->aggregate('BTC', RateCategory::CRYPTO, AggregationStrategy::AVERAGE);

echo "Average Price: $" . number_format($btc->getAveragePrice(), 2) . "\n";
echo "Lowest Source: $" . number_format($btc->getMinPrice(), 2) . "\n";
echo "Highest Source: $" . number_format($btc->getMaxPrice(), 2) . "\n";
echo "Sources Count: {$btc->getSourcesCount()}\n";
```
