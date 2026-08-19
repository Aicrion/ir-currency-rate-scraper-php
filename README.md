# Iranian Currency, Gold, Coin & Crypto Rate Scraper (PHP 7.4+)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A modern, resilient, and extensible PHP 7.4+ library for scraping and aggregating real-time exchange rates, gold prices, coins, and cryptocurrencies from multiple Iranian and global financial sources (TGJU, ArzDigital, etc.).

---

## 🚀 Features

- **Strict PHP 7.4+ Compatibility**: Works seamlessly across PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4 with `declare(strict_types=1);`.
- **⚡ High-Performance Caching & Warmup**:
  - Built-in drivers: **`FileCache`** (persistent on disk with TTL and file locking) and **`ArrayCache`** (in-memory).
  - **`Psr16CacheAdapter`** for plugging in Redis, Memcached, Laravel, or Symfony Cache.
  - **`warmup()` method**: Preloads, refreshes, and caches all market rates upfront so subsequent calls are served with 0ms network latency.
- **💱 Real-Time Currency Converter & Gold Calculator**:
  - Live conversions: `USD <-> IRR`, `USD <-> TOMAN`, `EUR <-> AED`, `BTC <-> TOMAN`.
  - Gold jewelry breakdown: calculates raw gold base value, craftsmanship wage, seller profit, and VAT tax.
- **🔔 Price Alerts & Fluctuation Watcher**:
  - Register target price and 24h percentage fluctuation alerts with custom callbacks (SMS, Telegram, Webhooks).
- **💻 Standalone CLI Tool**:
  - Executable command at `bin/currency` for retrieving rates, converting, and cache warming in terminal.
- **🛡️ Proxy & User-Agent Rotation**:
  - Built-in HTTP proxy authentication and rotating User-Agent pools for heavy-duty scraping.
- **Multi-Source Aggregation Engine**:
  - **Fallback / First Available**: Query primary source; seamlessly fall back to secondary sources or profile pages if unavailable.
  - **Average Calculation**: Automatically query multiple sources (e.g. TGJU + ArzDigital for crypto) and calculate average, minimum, and maximum prices.
- **Resilient Multi-Layer Scraping**:
  - Semantic table header scanning, data attribute matchers (`data-market-row`, `data-price`), and dedicated profile fallbacks (e.g. `/profile/price_dollar_rl`, `/profile/geram18`, `/profile/geram24`).
- **Comprehensive Market Coverage**:
  - 💵 Free Market, 🏛️ Central Bank, 🏦 SANA, 📑 NIMA, 🔄 Remittance, 🥇 Gold, 🪙 Coins, ⚡ Crypto Top 100.
- **Global Search Capability**: Search by Persian title (e.g. `دلار`, `طلای ۱۸ عیار`, `سکه امامی`) or international symbol (`USD`, `BTC`, `EUR`).

---

## 📦 Installation

```bash
composer require aicrion/ir-currency-rate-scraper
```

---

## 💡 Quick Start

```php
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Enums\AggregationStrategy;

// 1. Initialize with 5-minute File Cache
$scraper = CurrencyScraper::create();
$scraper->enableFileCache(__DIR__ . '/cache', 300);

// 2. High-Speed Preload / Warmup (0ms latency for subsequent requests)
$scraper->warmup();

// 3. Fetch Rates
$usd = $scraper->getUsd();
echo "USD: " . number_format($usd->getPrice()) . " Rial (" . number_format($usd->getPriceInTomans()) . " Toman)\n";

$gold18k = $scraper->getGold18k();
$coinEmami = $scraper->getCoinEmami();

// 4. Convert Currency
$tomans = $scraper->convert(100, 'USD', 'TOMAN');
echo "100 USD = " . number_format($tomans) . " Tomans\n";

// 5. Calculate Gold Jewelry Price (4.5g 18K with 10% wage, 7% profit, 9% VAT)
$goldCalc = $scraper->calculateGold(4.5, 18, 10.0, 7.0, 9.0);
echo "Gold Total: " . number_format($goldCalc['total_price_toman']) . " Tomans\n";

// 6. Multi-Source Aggregation (Bitcoin Average)
$btc = $scraper->aggregate('BTC', RateCategory::CRYPTO, AggregationStrategy::AVERAGE);
echo "Average BTC: $" . number_format($btc->getAveragePrice(), 2) . "\n";
```

---

## 💻 CLI Commands

```bash
# View live rate
php bin/currency rate USD

# Display market table
php bin/currency table gold

# Currency conversion
php bin/currency convert 100 USD TOMAN

# Gold breakdown
php bin/currency gold 5 18 10

# Search
php bin/currency search دلار

# Cache warmup
php bin/currency warmup
```

---

## 🧪 Testing & Quality Assurance

```bash
# Run unit tests
composer test

# Run static analysis (PHPStan Level 8)
composer analyse

# Run code style fixer (PSR-12)
composer cs:fix
```

---

## 📄 License

This library is open-sourced software licensed under the [MIT license](LICENSE).
