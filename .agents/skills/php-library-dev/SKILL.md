---
name: php-library-dev
description: >-
  Expert guide and workflow for architecting, developing, and testing modern PHP 7.4+ libraries
  with strict typing, clean architecture, design patterns (Strategy, Factory, Pipeline, DTO),
  PSR standards, PHPUnit testing with fixtures, and static analysis (PHPStan).
---

# PHP 7.4+ Modern Library Development Skill

This skill provides comprehensive architectural guidelines, design patterns, coding standards, and testing workflows for developing a production-grade, highly maintainable, and extensible PHP library compatible with **PHP 7.4 and higher** (including PHP 8.0, 8.1, 8.2, 8.3, and 8.4).

---

## 1. Core Principles & Philosophy

1. **Strict Type Safety**: Every single PHP file must start with `declare(strict_types=1);`.
2. **PHP 7.4 Compatibility Boundary**:
   - **Allowed**: Typed properties (`public string $name;`, `private ?int $id;`), arrow functions (`fn($x) => $x * 2`), null coalescing assignment (`??=`), return/argument type declarations, array spread operator (`[...$items]`).
   - **Prohibited in PHP 7.4 core**: Constructor property promotion (`public function __construct(public string $x)`), `match` expressions, union types (`int|string` - use PHPDoc `@param int|string` instead), attributes (`#[Attribute]`), `readonly` properties, enums (use Class Constants / Value Objects).
3. **Decoupled Architecture**: Code against interfaces (PSR-18 HTTP Client, PSR-17 Request Factory, PSR-3 Logger), avoid hard vendor lock-ins.
4. **Immutability by Default**: DTOs, Value Objects, and Currency/Rate models should be immutable with getter methods and `with*()` mutation clones.
5. **Robust Error Handling**: Create a dedicated exception hierarchy extending a base marker interface (`ScraperException` or `LibraryException`).
6. **Deterministic Testing**: 100% testable parsers and engines using static HTML/JSON fixtures without relying on external network calls for unit tests.

---

## 2. Standard Library Project Structure

```text
├── .agents/
│   └── skills/
│       └── php-library-dev/
│           ├── SKILL.md
│           └── references/
│               ├── architecture.md
│               └── testing.md
├── .github/
│   └── workflows/
│       └── ci.yml             # GitHub Actions matrix testing (PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4)
├── src/
│   ├── Contracts/             # Interfaces (ProviderInterface, ParserInterface, ClientInterface, etc.)
│   ├── DTO/                   # Data Transfer Objects (Rate, Currency, PriceHistory, etc.)
│   ├── Enums/ or ValueObjects/# Value Objects & Constants (Currency, Source, etc.)
│   ├── Exceptions/            # Domain-specific exceptions
│   ├── Http/                  # HTTP Client wrapper / PSR-18 adapter / Curl driver
│   ├── Parsers/               # HTML / JSON / DOM extractors
│   ├── Providers/             # Scraper source strategies (e.g., Bonbast, Navasan, TGJU, Nobitex)
│   ├── Support/               # Helpers, String utilities, Date/Jalali converters
│   ├── Config.php             # Configuration holder
│   └── Scraper.php / Manager.php # Main facade / Orchestrator
├── tests/
│   ├── Fixtures/              # Raw HTML/JSON responses for offline testing
│   │   ├── bonbast/
│   │   ├── navasan/
│   │   └── tgju/
│   ├── Unit/                  # Unit tests (Parsers, DTOs, Value Objects, Aggregator)
│   ├── Integration/           # Optional real HTTP tests (skipped in CI or isolated)
│   └── TestCase.php           # Base test case with fixture loaders & mock factories
├── .editorconfig
├── .gitattributes
├── .gitignore
├── .php-cs-fixer.dist.php     # Code styling (PSR-12)
├── composer.json
├── phpstan.neon.dist          # Static analysis (Level 8 / Max)
├── phpunit.xml.dist           # PHPUnit configuration
└── README.md
```

---

## 3. Recommended Architectural Patterns

### A. Provider / Strategy Pattern
Each data source (e.g., TGJU, Bonbast, Navasan, Nobitex, etc.) implements a common interface:

```php
declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

use Aicrion\IrCurrencyRateScraper\DTO\ExchangeRateCollection;

interface ProviderInterface
{
    public function getName(): string;

    public function fetchRates(): ExchangeRateCollection;

    public function supports(string $currency): bool;
}
```

### B. Parser & Extraction Separation
Keep HTTP fetching separate from HTML/JSON parsing. The parser should accept a raw string/payload and return structured DTOs:

```php
declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

use Aicrion\IrCurrencyRateScraper\DTO\ExchangeRateCollection;

interface ParserInterface
{
    public function parse(string $rawContent): ExchangeRateCollection;
}
```

### C. Immutable DTOs & Value Objects
```php
declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\DTO;

use DateTimeImmutable;

final class ExchangeRate
{
    private string $source;
    private string $currency; // e.g. "USD", "EUR", "AED"
    private float $buyPrice;
    private float $sellPrice;
    private ?float $change;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $source,
        string $currency,
        float $buyPrice,
        float $sellPrice,
        ?float $change = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->source = $source;
        $this->currency = strtoupper($currency);
        $this->buyPrice = $buyPrice;
        $this->sellPrice = $sellPrice;
        $this->change = $change;
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function getSource(): string { return $this->source; }
    public function getCurrency(): string { return $this->currency; }
    public function getBuyPrice(): float { return $this->buyPrice; }
    public function getSellPrice(): float { return $this->sellPrice; }
    public function getChange(): ?float { return $this->change; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'currency' => $this->currency,
            'buy_price' => $this->buyPrice,
            'sell_price' => $this->sellPrice,
            'change' => $this->change,
            'updated_at' => $this->updatedAt->format(DateTimeImmutable::ATOM),
        ];
    }
}
```

### D. Multi-Provider Manager & Failover / Pipeline
```php
declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper;

use Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface;
use Aicrion\IrCurrencyRateScraper\DTO\ExchangeRate;
use Aicrion\IrCurrencyRateScraper\Exceptions\RateNotFoundException;
use Aicrion\IrCurrencyRateScraper\Exceptions\ScraperException;

class CurrencyScraper
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    public function registerProvider(ProviderInterface $provider): self
    {
        $this->providers[$provider->getName()] = $provider;
        return $this;
    }

    public function getRate(string $currency, ?string $providerName = null): ExchangeRate
    {
        // If specific provider requested:
        if ($providerName !== null) {
            return $this->getProvider($providerName)->fetchRates()->get($currency);
        }

        // Fallback strategy through registered providers
        $lastException = null;
        foreach ($this->providers as $provider) {
            if (!$provider->supports($currency)) {
                continue;
            }
            try {
                $collection = $provider->fetchRates();
                if ($collection->has($currency)) {
                    return $collection->get($currency);
                }
            } catch (ScraperException $e) {
                $lastException = $e;
            }
        }

        throw new RateNotFoundException("Currency '{$currency}' not found in any available provider.", 0, $lastException);
    }
}
```

---

## 4. Exception Hierarchy

All custom exceptions must extend a library root exception/interface:

```text
Throwable
└── Exception
    └── Aicrion\IrCurrencyRateScraper\Exceptions\ScraperException (implements CurrencyScraperThrowable)
        ├── NetworkException
        │   ├── TimeoutException
        │   └── HttpFailedException
        ├── ParseException
        │   └── DomSelectorNotFoundException
        ├── ProviderUnavailableException
        ├── RateNotFoundException
        └── InvalidCurrencyException
```

---

## 5. Testing & Quality Assurance Workflow

### A. Fixture-Driven Unit Testing
Always store sample HTML/JSON responses in `tests/Fixtures/`:

```php
declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit\Parsers;

use Aicrion\IrCurrencyRateScraper\Parsers\TgjuParser;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class TgjuParserTest extends TestCase
{
    public function testParsesTgjuHtmlCorrectly(): void
    {
        $html = $this->loadFixture('tgju/sample_table.html');
        $parser = new TgjuParser();
        $collection = $parser->parse($html);

        $this->assertTrue($collection->has('USD'));
        $usd = $collection->get('USD');
        $this->assertGreaterThan(0, $usd->getSellPrice());
    }
}
```

### B. Standard `composer.json` Tooling Config

```json
{
    "name": "aicrion/ir-currency-rate-scraper",
    "description": "A modern, robust PHP 7.4+ library for scraping and aggregating Iranian currency and gold rates.",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=7.4",
        "ext-json": "*",
        "ext-curl": "*",
        "psr/http-client": "^1.0",
        "psr/http-factory": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6",
        "phpstan/phpstan": "^1.11",
        "friendsofphp/php-cs-fixer": "^3.50",
        "guzzlehttp/guzzle": "^7.8",
        "guzzlehttp/psr7": "^2.6",
        "symfony/dom-crawler": "^5.4 || ^6.4 || ^7.0",
        "symfony/css-selector": "^5.4 || ^6.4 || ^7.0"
    },
    "autoload": {
        "psr-4": {
            "Aicrion\\IrCurrencyRateScraper\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Aicrion\\IrCurrencyRateScraper\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "test:coverage": "phpunit --coverage-html coverage",
        "analyse": "phpstan analyse",
        "cs:check": "php-cs-fixer fix --dry-run --diff",
        "cs:fix": "php-cs-fixer fix"
    }
}
```

---

## 6. Step-by-Step Execution Guide for Developing Components

When developing any new component or provider in this library, follow this cycle:

1. **Define Contract**: Add or update interfaces in `src/Contracts/`.
2. **Collect Fixtures**: Obtain real representative response payload (HTML/JSON) and save in `tests/Fixtures/<provider>/`.
3. **Write Unit Test First (TDD)**: Test parser against fixture data covering normal rates, missing keys, and malformed HTML.
4. **Implement Parser**: Use DOM Crawler / Regex / JSON decoder with resilient fallback rules.
5. **Implement Provider**: Combine HTTP client + Parser with error translation to `ScraperException`.
6. **Register & Orchestrate**: Wire up provider in main manager or factory.
7. **Run QA Suite**:
   ```bash
   composer cs:fix
   composer analyse
   composer test
   ```
