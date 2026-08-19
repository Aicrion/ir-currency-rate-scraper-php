# Scraper Library Architecture & Design Patterns

## Architectural Layering

```
+-------------------------------------------------------------------+
|                        Client Application                         |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                   CurrencyScraper (Facade / Manager)              |
|  - Manages provider registry                                      |
|  - Strategy / Fallback / Pipeline execution                       |
|  - Caching (FileCache, ArrayCache, Psr16CacheAdapter, NullCache)  |
|  - Warmup & Background Preload Engine                             |
+-------------------------------------------------------------------+
         │                        │                 │
         ▼                        ▼                 ▼
+---------------------+ +--------------------+ +--------------------+
|  ProviderInterface  | |   CacheInterface   | |HttpClientInterface |
| (Tgju, Arzdigital)  | |(File, Array, PSR16)| |(PSR-18, Curl)      |
+---------------------+ +--------------------+ +--------------------+
         │                                          │
         ▼                                          │
+---------------------+                             │
|   ParserInterface   | <───────────────────────────+ (Raw HTML/JSON)
|  - Extract DOM/JSON |
|  - Normalize values |
+---------------------+
         │
         ▼
+-------------------------------------------------------------------+
|                 DTOs / Immutable Collections                      |
|  - Rate, RateCollection, AggregatedRate                           |
+-------------------------------------------------------------------+
```

---

## Key Design Patterns

### 1. Strategy Pattern (Providers)
Allows easily adding new currency exchange data sources without modifying existing business logic.

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

### 2. Cache Driver & Adapter Pattern (`CacheInterface`)
Decouples storage engines (File on disk, In-Memory Array, PSR-16 Redis/Laravel) behind a lightweight unified contract.

### 3. Adapter Pattern (HTTP Client)
Decouples external HTTP implementations (Guzzle, Symfony HttpClient, native cURL) through PSR-18 or a custom lightweight transport layer.

### 4. Parser / Extractor Single Responsibility (SRP)
Separates the concerns of network communication from raw payload parsing (HTML scraping, JSON parsing, regex extraction).

### 5. Value Object & DTO Immutability
All financial rate data (prices, timestamps, source origins) are represented as immutable objects preventing accidental mutation across downstream consumers.

### 6. Chain of Responsibility / Fallback Manager
If one provider is down or blocked (Cloudflare, captcha, network timeout), the manager automatically falls back to the next eligible provider that supports the requested currency.
