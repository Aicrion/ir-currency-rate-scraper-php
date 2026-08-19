# Testing & Quality Assurance Guide

This guide describes how to run tests, add new test fixtures, perform static analysis, and enforce PSR-12 code styling in `aicrion/ir-currency-rate-scraper`.

---

## 🧪 Testing Philosophy

1. **Deterministic & Offline**: Unit tests must **never** depend on external network connectivity or live website state.
2. **Fixture-Driven**: Test cases use representative static HTML/JSON snapshots stored in `tests/Fixtures/`.
3. **100% Coverage on Parsing**: Parsers must be tested against normal rows, missing columns, negative numbers, and parenthesized percentages.

---

## 🚀 Running Tests

Execute PHPUnit test suite:

```bash
# Run all unit tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/Unit/TgjuTableParserTest.php

# Run with test filtering
vendor/bin/phpunit --filter testParsesDollarProfileFixture
```

---

## 🔍 Static Analysis (PHPStan)

The codebase strictly adheres to **PHPStan Level 8**:

```bash
vendor/bin/phpstan analyse --memory-limit=1G
```

### Static Analysis Rules:
- All properties and method returns must have explicit type declarations or PHPDoc annotations for generics/iterables.
- Nullability must be handled explicitly (`?float`, `?string`).
- No `mixed` types in method contracts.

---

## 🎨 Code Style (PHP-CS-Fixer & PSR-12)

Check and automatically fix coding standards:

```bash
# Dry run check (see diffs without modifying files)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Automatically format all files to PSR-12
vendor/bin/php-cs-fixer fix
```

---

## 📁 Adding a New Fixture

To add a new test fixture for a provider:
1. Save the raw HTML or JSON payload into `tests/Fixtures/<provider_name>/<category>.html`.
2. In your test class, extend `Aicrion\IrCurrencyRateScraper\Tests\TestCase`.
3. Load the fixture cleanly:
   ```php
   $html = $this->loadFixture('tgju/custom_page.html');
   $collection = $this->parser->parse($html, RateCategory::FREE_CURRENCY);
   $this->assertNotEmpty($collection);
   ```
