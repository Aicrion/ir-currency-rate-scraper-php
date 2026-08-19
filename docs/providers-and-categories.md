# Providers & Market Categories

This document provides a detailed breakdown of all supported financial categories, provider endpoints, and instructions for creating custom providers.

---

## 📊 Supported Market Categories (`RateCategory`)

| Constant | Description | Primary TGJU Endpoint | Dedicated Fallback Profile URL |
|---|---|---|---|
| `RateCategory::FREE_CURRENCY` | Free market exchange rates (USD, EUR, GBP, AED, etc.) | `https://www.tgju.org/currency` | `https://www.tgju.org/profile/price_dollar_rl` |
| `RateCategory::BANK_GOVERNMENT` | Central bank / official government currency rates | `https://www.tgju.org/bank` | Direct table lookup |
| `RateCategory::SANA` | SANA currency system rates (Buy & Sell) | `https://www.tgju.org/sana` | Direct table lookup |
| `RateCategory::NIMA` | NIMA currency system rates (Buy & Sell) | `https://www.tgju.org/%D9%86%D8%B1%D8%AE-%D8%A7%D8%B1%D8%B2-%D9%86%DB%8C%D9%85%D8%A7%DB%8C%DB%8C` | Direct table lookup |
| `RateCategory::TRANSFER` | Foreign exchange transfer / remittance rates | `https://www.tgju.org/transfer` | Direct table lookup |
| `RateCategory::GOLD` | Gold (18K, 24K, Mesghal, Melted, Global Ounce) | `https://www.tgju.org/gold-chart` | `https://www.tgju.org/profile/geram18`, `https://www.tgju.org/profile/geram24` |
| `RateCategory::COIN` | Coins (Emami, Bahar Azadi, Half, Quarter, Grami) | `https://www.tgju.org/coin` | Direct table lookup |
| `RateCategory::CRYPTO` | Cryptocurrencies (Top 100 & details) | `https://www.tgju.org/crypto` + `https://arzdigital.com/coins/` | Direct symbol lookup |

---

## 🛠️ Built-in Providers

### 1. `TgjuProvider` (`tgju`)
- Scrapes all 8 categories from `tgju.org`.
- Includes automated profile fallbacks for:
  - US Dollar (`price_dollar_rl` / `usd`)
  - Euro (`price_eur` / `eur`)
  - British Pound (`price_gbp` / `gbp`)
  - UAE Dirham (`price_aed` / `aed`)
  - 18 Karat Gold (`geram18` / `ir18k`)
  - 24 Karat Gold (`geram24` / `ir24k`)
  - Gold Mesghal (`mesghal`)
  - Coins (`sekee`, `sekeb`, `nim`, `rob`, `gerami`)

### 2. `ArzdigitalProvider` (`arzdigital`)
- Scrapes and parses cryptocurrencies from `arzdigital.com`.
- Supports Top 100 coins list and JSON payload extraction.

---

## 🔌 Adding a Custom Provider

Creating and registering a new provider is straightforward:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;

final class BonbastProvider implements ProviderInterface
{
    public const NAME = 'bonbast';

    public function getName(): string
    {
        return self::NAME;
    }

    public function supportsCategory(string $category): bool
    {
        return $category === RateCategory::FREE_CURRENCY;
    }

    public function fetchCategory(string $category): RateCollection
    {
        // 1. Fetch data via HTTP
        // 2. Parse into RateCollection
        // 3. Return RateCollection
        return new RateCollection();
    }

    public function fetchRate(string $symbolOrId, ?string $category = null): ?Rate
    {
        $collection = $this->fetchCategory($category ?? RateCategory::FREE_CURRENCY);
        return $collection->get($symbolOrId);
    }

    public function search(string $query, ?string $category = null): RateCollection
    {
        $collection = $this->fetchCategory($category ?? RateCategory::FREE_CURRENCY);
        return $collection->search($query);
    }
}
```

### Registering with CurrencyScraper
```php
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$scraper = CurrencyScraper::create();
$scraper->registerProvider(new BonbastProvider());
```
