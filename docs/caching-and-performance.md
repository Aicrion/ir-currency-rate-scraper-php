# Caching & Performance Optimization Guide

This guide details the caching architecture, drivers, TTL configurations, warmup mechanisms, and background synchronization workflows for `aicrion/ir-currency-rate-scraper`.

---

## ⚡ Why Caching Matters

Scraping real-time rates on every user HTTP request causes:
1. High response latency (100ms - 2000ms per provider network call).
2. Risk of IP rate limits or Cloudflare challenges from target websites.
3. Unnecessary network bandwidth consumption.

By utilizing the built-in **Caching & Warmup Engine**, all rate queries (`getUsd()`, `getGold18k()`, `search()`, `getCategory()`) are served directly from disk or memory in **< 1 millisecond**.

---

## 🗄️ Supported Cache Drivers

| Driver | Class | Persistence | Ideal Use Case |
|---|---|---|---|
| **File Cache** | `Aicrion\IrCurrencyRateScraper\Cache\FileCache` | Persistent on disk | Production CLI/Web without Redis. Survives request lifecycles. |
| **Array Cache** | `Aicrion\IrCurrencyRateScraper\Cache\ArrayCache` | In-memory | Single request lifecycle / Unit tests. |
| **PSR-16 Adapter** | `Aicrion\IrCurrencyRateScraper\Cache\Psr16CacheAdapter` | External (Redis, Memcached) | High-traffic distributed applications (Laravel, Symfony). |
| **Null Cache** | `Aicrion\IrCurrencyRateScraper\Cache\NullCache` | None (Disabled) | Default mode when caching is not needed. |

---

## 🚀 Activation & Configuration

### 1. File Cache (Built-in)
```php
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$scraper = CurrencyScraper::create();

// Enable file cache stored in a custom folder with a 5-minute (300s) TTL
$scraper->enableFileCache(__DIR__ . '/storage/cache', 300);
```

### 2. Array Cache (In-Memory)
```php
$scraper->enableArrayCache(300);
```

### 3. PSR-16 Adapter (Redis, Laravel, Symfony)
```php
use Aicrion\IrCurrencyRateScraper\Cache\Psr16CacheAdapter;

// Inject any Psr\SimpleCache\CacheInterface implementation
$psr16Adapter = new Psr16CacheAdapter($redisCacheInstance);
$scraper->setCache($psr16Adapter, 300);
```

---

## 🔄 Warmup & Background Cron Workflow

### The Recommended Architecture

Instead of having visitors wait for pages to scrape live data:
1. Run a background Cron job every 1 to 5 minutes to call `warmup()`.
2. Let user-facing web requests read instantaneously from cache.

### Example Cron Job Script (`cron/update_rates.php`)
```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$scraper = CurrencyScraper::create();
$scraper->enableFileCache(__DIR__ . '/../storage/cache', 600); // 10-minute TTL

echo "[" . date('Y-m-d H:i:s') . "] Starting rate warmup...\n";
$rates = $scraper->warmup();
echo "[" . date('Y-m-d H:i:s') . "] Done. Cached {$rates->count()} assets.\n";
```

Add to crontab:
```cron
*/2 * * * * php /var/www/html/cron/update_rates.php >> /var/log/rates_cron.log 2>&1
```

---

## ⚡ Cache Control Operations

### Bypassing Cache (`forceRefresh`)
All query methods accept `forceRefresh: true` to bypass the cache and force a live network scrape:

```php
// Live query bypassing cache:
$liveUsd = $scraper->getUsd(forceRefresh: true);
$liveSana = $scraper->getCategory(RateCategory::SANA, null, forceRefresh: true);
```

### Clearing Cache
```php
$scraper->clearCache();
```

### Checking Cache Status
```php
if ($scraper->isCached(RateCategory::FREE_CURRENCY)) {
    echo "Free currencies are ready in cache!";
}
```
