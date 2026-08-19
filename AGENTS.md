# AGENTS.md - Iranian Currency & Asset Scraper Library

Welcome to the **`aicrion/ir-currency-rate-scraper`** repository. This document outlines the architectural principles, conventions, guidelines, and workflows to follow when interacting with, maintaining, or extending this codebase.

---

## 📌 Project Overview

This library is a high-performance, resilient, and extensible PHP library for scraping and aggregating real-time exchange rates, gold prices, coins, and cryptocurrencies from Iranian and global financial sources (e.g. TGJU, ArzDigital, etc.).

### Primary Goals:
1. **Rock-Solid Compatibility**: Fully compatible with **PHP 7.4 through PHP 8.4+**.
2. **Strict Typing**: `declare(strict_types=1);` on all PHP files.
3. **Resilient Scraping**: Multi-tier extraction (DOM header detection, data attributes, dedicated profile URL fallbacks, regex fallback).
4. **Flexible Aggregation**: First Available (Fallback), Multi-Source Average, All Sources.
5. **High-Performance Caching & Warmup**: Built-in `FileCache` and `ArrayCache` with PSR-16 adapter.
6. **Financial Calculators**: Built-in currency converter and gold jewelry value breakdown.
7. **Price Alerts**: Market watcher and threshold notification callbacks.
8. **CLI Executable**: Command-line tool at `bin/currency`.

---

## 🏗️ Architectural Guidelines

### 1. Separation of Concerns
- **Contracts (`src/Contracts/`)**: Define interfaces for Providers, Parsers, HTTP Clients, Cache, and Marker Exceptions.
- **DTOs & Domain Models (`src/DTO/`)**: All models (`Rate`, `RateCollection`, `AggregatedRate`) are **immutable** and return copies on mutation.
- **Services & Tools (`src/Services/`, `src/Alerts/`)**: Decoupled financial calculation engines (`CurrencyConverter`) and alert managers (`PriceAlertManager`).
- **Providers (`src/Providers/`)**: Encapsulate source-specific logic (e.g., `TgjuProvider`, `ArzdigitalProvider`).
- **Parsers (`src/Parsers/`)**: Pure HTML/JSON parsing logic completely decoupled from HTTP communication.
- **Support & Normalizers (`src/Support/`)**: Utilities for cleaning text, converting Persian/Arabic numerals, parsing currency values and table headers.

### 2. Provider Pattern (Strategy)
Every provider must implement `Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface`:
```php
interface ProviderInterface
{
    public function getName(): string;
    public function supportsCategory(string $category): bool;
    public function fetchCategory(string $category): RateCollection;
    public function fetchRate(string $symbolOrId, ?string $category = null): ?Rate;
    public function search(string $query, ?string $category = null): RateCollection;
}
```

---

## 🛑 PHP 7.4 Compatibility Rules

Agents must adhere strictly to PHP 7.4 compatibility:

| Allowed in PHP 7.4 (Use Freely) | Prohibited in PHP 7.4 (Do NOT Use) |
|---|---|
| Typed Properties (`public string $x;`) | Constructor Property Promotion (`__construct(public string $x)`) |
| Arrow Functions (`fn($x) => $x * 2`) | `match` expressions (use `switch` or array maps) |
| Null Coalescing Assignment (`$a ??= $b`) | Native Union Types (`int\|string` - use `@param int\|string`) |
| Array Spread Operator (`[...$a, ...$b]`) | Native Enums (`enum X` - use Class Constants) |
| Type Hints for Parameters & Returns | Nullsafe Operator (`$obj?->method()`) |
| Strict Types (`declare(strict_types=1);`) | `readonly` properties / classes |
| Covariant returns / Contravariant parameters | Attributes (`#[Attribute]`) |

---

## 🧪 Testing & Quality Assurance Standards

1. **Deterministic Unit Tests**: Always test parsers using static HTML/JSON fixtures stored in `tests/Fixtures/`. Never make real external HTTP requests inside unit tests.
2. **Static Analysis**: Maintain PHPStan Level 8 clean status (`vendor/bin/phpstan analyse --memory-limit=1G`).
3. **Coding Standards**: Adhere to PSR-12 formatting rules via PHP-CS-Fixer (`vendor/bin/php-cs-fixer fix`).

### Essential CLI Commands
```bash
# Run unit tests
vendor/bin/phpunit

# Run static analysis (Level 8)
vendor/bin/phpstan analyse --memory-limit=1G

# Run code style fixer
vendor/bin/php-cs-fixer fix

# Run CLI console tool
php bin/currency help

# Run live demo
php examples/demo.php
```

---

## 📂 Project Directory Structure

```text
├── .agents/
│   └── skills/
│       ├── php-library-dev/         # PHP library development skill & reference
│       └── scraper-provider-dev/    # Skill for adding new providers & parsers
├── .github/
│   └── workflows/
│       ├── ci.yml                   # Matrix tests for PHP 7.4 - 8.4
│       └── docs.yml                 # GitHub Pages auto deployment
├── bin/
│   └── currency                     # Standalone CLI executable
├── docs/                            # Docsify documentation portal
│   ├── _coverpage.md
│   ├── _navbar.md
│   ├── _sidebar.md
│   ├── architecture.md
│   ├── caching-and-performance.md
│   ├── cli-tool.md
│   ├── converter-and-gold.md
│   ├── getting-started.md
│   ├── index.html
│   ├── price-alerts.md
│   ├── providers-and-categories.md
│   ├── resilient-scraping.md
│   └── testing-and-qa.md
├── examples/
│   └── demo.php                     # Live demo script
├── src/
│   ├── Alerts/                      # PriceAlertManager
│   ├── Cache/                       # FileCache, ArrayCache, Psr16CacheAdapter, NullCache
│   ├── Contracts/                   # Interfaces
│   ├── DTO/                         # Immutable Data Transfer Objects
│   ├── Enums/                       # Constants / Enums
│   ├── Exceptions/                  # Domain Exceptions
│   ├── Http/                        # HTTP Drivers (Curl with Proxy & PSR-18 Adapter)
│   ├── Parsers/                     # HTML/JSON Extractors
│   ├── Providers/                   # Strategy Providers (TGJU, ArzDigital)
│   ├── Services/                    # CurrencyConverter & Gold Calculator
│   ├── Support/                     # DomHelper, PriceNormalizer
│   └── CurrencyScraper.php          # Main Facade
├── tests/
│   ├── Fixtures/                    # Offline HTML/JSON sample files
│   └── Unit/                        # Unit test classes (40 tests, 149 assertions)
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon.dist
└── README.md
```
