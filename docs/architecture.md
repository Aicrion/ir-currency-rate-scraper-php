# Library Architecture & Design Patterns

The `aicrion/ir-currency-rate-scraper` library is built using clean architecture and classic software design patterns, ensuring loose coupling, strict typing, extensibility, and maintainability.

---

## 🏛️ Layered Architecture Diagram

```
+-------------------------------------------------------------------------------+
|                            Client Application / API                           |
+-------------------------------------------------------------------------------+
                                       │
                                       ▼
+-------------------------------------------------------------------------------+
|                       CurrencyScraper (Orchestrator Facade)                   |
|  - Manages provider registry                                                  |
|  - Strategy-based fallback & multi-source aggregation                         |
|  - Global search & category routing                                           |
|  - Cache Layer (ArrayCache, FileCache, Psr16CacheAdapter, NullCache)          |
|  - Warmup & Preload Engine                                                    |
+-------------------------------------------------------------------------------+
               │                                │                               │
               ▼                                ▼                               ▼
+------------------------------+ +------------------------------+ +------------------------------+
|      ProviderInterface       | |      CacheInterface          | |      HttpClientInterface     |
|  - TgjuProvider              | |  - FileCache (Persistent)    | |  - CurlHttpClient (default)  |
|  - ArzdigitalProvider        | |  - ArrayCache (In-memory)    | |  - Psr18HttpClientAdapter    |
|  - Custom Providers          | |  - Psr16CacheAdapter (Redis) | +------------------------------+
+------------------------------+ |  - NullCache (Disabled)      |               │
               │                 +------------------------------+               │
               ▼                                                                │ (Raw HTML/JSON)
+------------------------------+                                                │
|       ParserInterface        | <──────────────────────────────────────────────┘
|  - TgjuTableParser           |
|  - TgjuProfileParser         |
|  - ArzdigitalParser          |
+------------------------------+
               │
               ▼
+-------------------------------------------------------------------------------+
|                          Immutable DTOs & Collections                         |
|  - Rate (Price, Buy/Sell, 24h Change, Units, Timestamps, Metas)               |
|  - RateCollection (Fluent query, search, filtering)                           |
|  - AggregatedRate (Calculated average, min/max, sources summary)              |
+-------------------------------------------------------------------------------+
```

---

## 🎯 Key Design Patterns

### 1. Facade Pattern (`CurrencyScraper`)
Provides a simple, clean, and expressive unified API for client applications, hiding the complexity of HTTP requests, HTML/JSON parsing, fallback strategies, data normalization, and cache management.

### 2. Strategy Pattern (`ProviderInterface`)
Data sources (e.g. TGJU, Arzdigital, Bonbast, Navasan) implement a unified `ProviderInterface`. This allows adding new sources or swapping implementations without altering any client code.

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

### 3. Caching & Adapter Pattern (`CacheInterface` & `Psr16CacheAdapter`)
Caching is fully decoupled behind `CacheInterface`. The library includes:
- **`FileCache`**: Persistent disk cache with TTL and atomic file locks.
- **`ArrayCache`**: In-memory cache for request-scoped lifecycles.
- **`Psr16CacheAdapter`**: Adapter allowing any PSR-16 compliant cache (Redis, Memcached, Laravel Cache, Symfony Cache) to be injected.
- **`NullCache`**: Default no-op implementation when caching is disabled.

### 4. Adapter Pattern (`Psr18HttpClientAdapter`)
The HTTP layer is abstracted behind `HttpClientInterface`. While the library ships with a fast, zero-dependency `CurlHttpClient`, users can inject any PSR-18 compliant client (Guzzle, Symfony HTTP Client):

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Aicrion\IrCurrencyRateScraper\Http\Psr18HttpClientAdapter;
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$psr18 = new Psr18HttpClientAdapter(new GuzzleClient(), new HttpFactory());
$scraper = CurrencyScraper::create($psr18);
```

### 5. Single Responsibility Principle (SRP)
- **HTTP Layer**: Responsible solely for network connectivity, redirects, timeouts, and headers.
- **Parsers**: Responsible solely for converting raw strings into structured DTOs.
- **Providers**: Responsible for mapping categories to URLs and orchestrating HTTP + Parser.
- **Support Layer**: Dedicated utilities for number normalizations (`PriceNormalizer`) and DOM header detection (`DomHelper`).
- **Cache Layer**: Dedicated drivers for storage, retrieval, TTL validation, and flushing.

### 6. Immutability (Value Objects & DTOs)
All data representations (`Rate`, `AggregatedRate`) are immutable. Once created, their internal state cannot be modified, eliminating concurrency bugs and accidental state mutations.
