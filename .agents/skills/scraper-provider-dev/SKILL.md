---
name: scraper-provider-dev
description: >-
  Step-by-step workflow for implementing, registering, testing, and maintaining new rate providers,
  parsers, and endpoints within the Currency Rate Scraper library.
---

# Scraper Provider Development Skill

This skill guides the agent through adding a new currency, gold, commodity, or cryptocurrency scraping provider to the `aicrion/ir-currency-rate-scraper` library.

---

## 📋 5-Step Provider Implementation Workflow

### Step 1: Collect Representative Fixtures
Never write a parser blindly. First, capture a sample HTML/JSON response from the target website or API:
1. Fetch sample content or save snapshot into `tests/Fixtures/<provider_name>/<category>.html` (or `.json`).
2. Include both successful tables/cards and edge cases (empty states, minus changes, malformed rows).

### Step 2: Implement the Parser (`src/Parsers/<Name>Parser.php`)
1. Implement `Aicrion\IrCurrencyRateScraper\Contracts\ParserInterface`.
2. Use `PriceNormalizer` for Persian/Arabic digit conversion and float parsing:
   ```php
   $price = PriceNormalizer::parsePrice($text);
   [$change, $percent] = PriceNormalizer::parseChangeAndPercentage($changeText);
   ```
3. Use `DomHelper::detectTableColumnMap` to scan headers dynamically instead of hardcoding static column indices.
4. Return a `RateCollection` containing immutable `Rate` objects.

### Step 3: Implement the Provider (`src/Providers/<Name>Provider.php`)
1. Implement `Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface`.
2. Set provider name constant `public const NAME = 'provider_slug';`.
3. Support category checking via `supportsCategory(string $category): bool`.
4. Wrap network & parse exceptions into `ScraperException`:
   ```php
   try {
       $html = $this->httpClient->get($url);
       return $this->parser->parse($html, $category);
   } catch (\Throwable $e) {
       throw new ScraperException("Error fetching {$category} from {$url}: {$e->getMessage()}", 0, $e);
   }
   ```
5. Implement in-memory cache to prevent duplicate HTTP requests during a single lifecycle.

### Step 4: Write Unit Tests (`tests/Unit/<Name>ParserTest.php`)
1. Extend `Aicrion\IrCurrencyRateScraper\Tests\TestCase`.
2. Load the fixture using `$html = $this->loadFixture('<provider>/<file>');`.
3. Assert that:
   - Collection is not empty.
   - Specific symbols / titles are found (`$collection->get('USD') !== null`).
   - Prices, buy/sell prices, and percentages are positive/expected floats.
   - Toman conversion calculates properly (`getPriceInTomans()`).

### Step 5: Register in `CurrencyScraper`
1. Add the new provider to `CurrencyScraper::create()` factory if it should be enabled by default.
2. Ensure `CurrencyScraper::aggregate()` and `CurrencyScraper::search()` interact properly with the new provider.
3. Run the validation suite:
   ```bash
   vendor/bin/phpunit
   vendor/bin/phpstan analyse --memory-limit=1G
   vendor/bin/php-cs-fixer fix
   ```
