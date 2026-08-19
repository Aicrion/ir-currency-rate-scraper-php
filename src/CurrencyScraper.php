<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper;

use Aicrion\IrCurrencyRateScraper\Alerts\PriceAlertManager;
use Aicrion\IrCurrencyRateScraper\Cache\ArrayCache;
use Aicrion\IrCurrencyRateScraper\Cache\FileCache;
use Aicrion\IrCurrencyRateScraper\Cache\NullCache;
use Aicrion\IrCurrencyRateScraper\Contracts\CacheInterface;
use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface;
use Aicrion\IrCurrencyRateScraper\DTO\AggregatedRate;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\AggregationStrategy;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Exceptions\ProviderUnavailableException;
use Aicrion\IrCurrencyRateScraper\Exceptions\RateNotFoundException;
use Aicrion\IrCurrencyRateScraper\Exceptions\ScraperException;
use Aicrion\IrCurrencyRateScraper\Http\CurlHttpClient;
use Aicrion\IrCurrencyRateScraper\Providers\ArzdigitalProvider;
use Aicrion\IrCurrencyRateScraper\Providers\TgjuProvider;
use Aicrion\IrCurrencyRateScraper\Services\CurrencyConverter;

/**
 * Main Facade / Orchestration Engine for Scraping and Aggregating Currency, Gold, Coin, and Crypto Rates.
 * Supports optional caching, multi-source fallbacks, comprehensive warmup, live conversion, and price alerts.
 */
class CurrencyScraper
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];
    private CacheInterface $cache;
    private int $defaultTtl;
    private ?HttpClientInterface $httpClient;
    private ?CurrencyConverter $converter = null;
    private ?PriceAlertManager $alertManager = null;

    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(
        array $providers = [],
        ?CacheInterface $cache = null,
        int $defaultTtl = 300,
        ?HttpClientInterface $httpClient = null
    ) {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }

        $this->cache = $cache ?? new NullCache();
        $this->defaultTtl = $defaultTtl;
        $this->httpClient = $httpClient;
    }

    /**
     * Factory to instantiate with default configured providers (TGJU + ArzDigital).
     */
    public static function create(?HttpClientInterface $httpClient = null, ?CacheInterface $cache = null, int $defaultTtl = 300): self
    {
        $client = $httpClient ?? new CurlHttpClient();

        $scraper = new self([], $cache, $defaultTtl, $client);
        $scraper->registerProvider(new TgjuProvider($client));
        $scraper->registerProvider(new ArzdigitalProvider($client));

        return $scraper;
    }

    // ==========================================
    // HTTP Proxy & User-Agent Configuration
    // ==========================================

    public function setProxy(?string $proxy, ?string $auth = null): self
    {
        if ($this->httpClient instanceof CurlHttpClient) {
            $this->httpClient->setProxy($proxy, $auth);
        }
        return $this;
    }

    public function enableUserAgentRotation(bool $enable = true): self
    {
        if ($this->httpClient instanceof CurlHttpClient) {
            $this->httpClient->enableUserAgentRotation($enable);
        }
        return $this;
    }

    public function getHttpClient(): ?HttpClientInterface
    {
        return $this->httpClient;
    }

    // ==========================================
    // Cache Management & Configuration
    // ==========================================

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function setCache(CacheInterface $cache, ?int $defaultTtl = null): self
    {
        $this->cache = $cache;
        if ($defaultTtl !== null) {
            $this->defaultTtl = $defaultTtl;
        }
        return $this;
    }

    /**
     * Enable persistent on-disk file caching.
     */
    public function enableFileCache(?string $cacheDir = null, int $defaultTtl = 300): self
    {
        $this->cache = new FileCache($cacheDir, $defaultTtl);
        $this->defaultTtl = $defaultTtl;
        return $this;
    }

    /**
     * Enable in-memory array caching for the current PHP execution.
     */
    public function enableArrayCache(int $defaultTtl = 300): self
    {
        $this->cache = new ArrayCache();
        $this->defaultTtl = $defaultTtl;
        return $this;
    }

    /**
     * Disable caching.
     */
    public function disableCache(): self
    {
        $this->cache = new NullCache();
        return $this;
    }

    /**
     * Clear all cached rate collections.
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Check if a specific category is cached for a provider.
     */
    public function isCached(string $category, ?string $providerName = null): bool
    {
        $targetProviders = $providerName !== null
            ? [$this->getProvider($providerName)]
            : $this->providers;

        foreach ($targetProviders as $p) {
            if ($p->supportsCategory($category)) {
                $cacheKey = $this->getCategoryCacheKey($p->getName(), $category);
                if ($this->cache->has($cacheKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Preload and cache all rates across all categories and providers upfront.
     *
     * @param string[]|null $categories Specific categories to warm up, or null for all.
     * @param int|null $ttl Optional cache TTL in seconds.
     * @return RateCollection All collected rates.
     */
    public function warmup(?array $categories = null, ?int $ttl = null): RateCollection
    {
        $targetCategories = $categories ?? RateCategory::all();
        $unifiedCollection = new RateCollection();
        $effectiveTtl = $ttl ?? $this->defaultTtl;

        foreach ($this->providers as $provider) {
            foreach ($targetCategories as $category) {
                if (!$provider->supportsCategory($category)) {
                    continue;
                }

                try {
                    $collection = $provider->fetchCategory($category);
                    $cacheKey = $this->getCategoryCacheKey($provider->getName(), $category);
                    $this->cache->set($cacheKey, $collection, $effectiveTtl);

                    $unifiedCollection = $unifiedCollection->merge($collection);
                } catch (ScraperException $e) {
                    // Continue warming remaining categories
                }
            }
        }

        return $unifiedCollection;
    }

    // ==========================================
    // Currency Converter & Gold Calculator
    // ==========================================

    public function converter(): CurrencyConverter
    {
        if ($this->converter === null) {
            $this->converter = new CurrencyConverter($this);
        }
        return $this->converter;
    }

    /**
     * Convert an amount between any currencies using live exchange rates.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        return $this->converter()->convert($amount, $from, $to);
    }

    /**
     * Calculate exact gold jewelry value with craftsmanship wage, seller profit, and tax.
     *
     * @return array<string, mixed>
     */
    public function calculateGold(
        float $grams,
        int $karat = 18,
        float $wagePercent = 0.0,
        float $profitPercent = 7.0,
        float $taxPercent = 9.0
    ): array {
        return $this->converter()->calculateGold($grams, $karat, $wagePercent, $profitPercent, $taxPercent);
    }

    // ==========================================
    // Price Alert & Watcher Engine
    // ==========================================

    public function alerts(): PriceAlertManager
    {
        if ($this->alertManager === null) {
            $this->alertManager = new PriceAlertManager();
        }
        return $this->alertManager;
    }

    /**
     * Add a target price threshold alert.
     */
    public function addPriceAlert(string $symbolOrId, float $targetPrice, string $operator = '>=', ?callable $callback = null): string
    {
        return $this->alerts()->addPriceAlert($symbolOrId, $targetPrice, $operator, $callback);
    }

    /**
     * Add a 24h percentage change alert.
     */
    public function addPercentAlert(string $symbolOrId, float $percentThreshold, string $operator = '>=', ?callable $callback = null): string
    {
        return $this->alerts()->addPercentAlert($symbolOrId, $percentThreshold, $operator, $callback);
    }

    /**
     * Check registered alerts against live or cached rates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function checkAlerts(?RateCollection $rates = null): array
    {
        $data = $rates ?? $this->warmup();
        return $this->alerts()->check($data);
    }

    // ==========================================
    // Provider Registry
    // ==========================================

    public function registerProvider(ProviderInterface $provider): self
    {
        $this->providers[$provider->getName()] = $provider;
        return $this;
    }

    public function getProvider(string $name): ProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new ProviderUnavailableException("Provider '{$name}' is not registered.");
        }

        return $this->providers[$name];
    }

    /**
     * @return array<string, ProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    // ==========================================
    // Rate Retrieval & Search
    // ==========================================

    /**
     * Get a single currency/asset rate by symbol, slug, or title with fallback and caching support.
     *
     * @throws RateNotFoundException
     * @throws ScraperException
     */
    public function getRate(
        string $symbolOrId,
        ?string $category = null,
        ?string $providerName = null,
        bool $forceRefresh = false
    ): Rate {
        $lastException = null;

        $targetProviders = $providerName !== null
            ? [$this->getProvider($providerName)]
            : array_values($this->providers);

        $categoriesToCheck = $category !== null ? [$category] : [
            RateCategory::FREE_CURRENCY,
            RateCategory::GOLD,
            RateCategory::COIN,
            RateCategory::CRYPTO,
            RateCategory::BANK_GOVERNMENT,
            RateCategory::SANA,
            RateCategory::NIMA,
            RateCategory::TRANSFER,
        ];

        // 1. Try resolving from categories (handles cache lookup and caching automatically)
        foreach ($targetProviders as $provider) {
            foreach ($categoriesToCheck as $cat) {
                if (!$provider->supportsCategory($cat)) {
                    continue;
                }

                try {
                    $collection = $this->getCategory($cat, $provider->getName(), $forceRefresh);
                    $rate = $collection->get($symbolOrId);
                    if ($rate !== null) {
                        return $rate;
                    }
                } catch (ScraperException $e) {
                    $lastException = $e;
                }
            }
        }

        // 2. Fallback: Direct provider fetch (for dedicated profile URLs, etc.)
        foreach ($targetProviders as $provider) {
            if ($category !== null && !$provider->supportsCategory($category)) {
                continue;
            }

            try {
                $rate = $provider->fetchRate($symbolOrId, $category);
                if ($rate !== null) {
                    return $rate;
                }
            } catch (ScraperException $e) {
                $lastException = $e;
            }
        }

        throw new RateNotFoundException(
            "Rate '{$symbolOrId}' could not be resolved from any available provider.",
            0,
            $lastException
        );
    }

    /**
     * Fetch entire rate collection for a given category (cached if available).
     *
     * @throws ScraperException
     */
    public function getCategory(
        string $category,
        ?string $providerName = null,
        bool $forceRefresh = false
    ): RateCollection {
        $effectiveProvider = $providerName !== null
            ? $this->getProvider($providerName)
            : $this->findFirstSupportingProvider($category);

        $cacheKey = $this->getCategoryCacheKey($effectiveProvider->getName(), $category);

        if (!$forceRefresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached instanceof RateCollection) {
                return $cached;
            }
        }

        $collection = $effectiveProvider->fetchCategory($category);
        $this->cache->set($cacheKey, $collection, $this->defaultTtl);

        return $collection;
    }

    /**
     * Search across providers for matching currency, gold, coin, or crypto rates.
     */
    public function search(string $query, ?string $category = null, bool $forceRefresh = false): RateCollection
    {
        $merged = new RateCollection();
        $categoriesToSearch = $category !== null ? [$category] : RateCategory::all();

        foreach ($this->providers as $provider) {
            foreach ($categoriesToSearch as $cat) {
                if (!$provider->supportsCategory($cat)) {
                    continue;
                }

                try {
                    $collection = $this->getCategory($cat, $provider->getName(), $forceRefresh);
                    $matched = $collection->search($query);
                    if (!$matched->isEmpty()) {
                        $merged = $merged->merge($matched);
                    }
                } catch (ScraperException $e) {
                    // Keep searching in remaining categories
                }
            }
        }

        return $merged;
    }

    /**
     * Aggregate rates across all configured providers (e.g. calculate Average, Min, Max or Fallback).
     *
     * @param string[] $providerNames Specific provider names to query, or empty for all supporting providers
     * @throws RateNotFoundException
     */
    public function aggregate(
        string $symbolOrId,
        ?string $category = null,
        string $strategy = AggregationStrategy::AVERAGE,
        array $providerNames = [],
        bool $forceRefresh = false
    ): AggregatedRate {
        $rates = [];

        $targets = empty($providerNames)
            ? array_values($this->providers)
            : array_filter($this->providers, fn (ProviderInterface $p): bool => in_array($p->getName(), $providerNames, true));

        foreach ($targets as $provider) {
            if ($category !== null && !$provider->supportsCategory($category)) {
                continue;
            }

            try {
                $rate = $this->getRate($symbolOrId, $category, $provider->getName(), $forceRefresh);
                $rates[] = $rate;

                if ($strategy === AggregationStrategy::FIRST_AVAILABLE) {
                    break;
                }
            } catch (ScraperException $e) {
                // Continue to next provider
            }
        }

        if (empty($rates)) {
            throw new RateNotFoundException("No provider returned a valid rate for '{$symbolOrId}' to aggregate.");
        }

        return new AggregatedRate($symbolOrId, $strategy, $rates);
    }

    // ==========================================
    // Convenience Shorthands
    // ==========================================

    public function getUsd(bool $forceRefresh = false): Rate
    {
        return $this->getRate('USD', RateCategory::FREE_CURRENCY, null, $forceRefresh);
    }

    public function getEur(bool $forceRefresh = false): Rate
    {
        return $this->getRate('EUR', RateCategory::FREE_CURRENCY, null, $forceRefresh);
    }

    public function getGbp(bool $forceRefresh = false): Rate
    {
        return $this->getRate('GBP', RateCategory::FREE_CURRENCY, null, $forceRefresh);
    }

    public function getAed(bool $forceRefresh = false): Rate
    {
        return $this->getRate('AED', RateCategory::FREE_CURRENCY, null, $forceRefresh);
    }

    public function getGold18k(bool $forceRefresh = false): Rate
    {
        return $this->getRate('geram18', RateCategory::GOLD, null, $forceRefresh);
    }

    public function getGold24k(bool $forceRefresh = false): Rate
    {
        return $this->getRate('geram24', RateCategory::GOLD, null, $forceRefresh);
    }

    public function getCoinEmami(bool $forceRefresh = false): Rate
    {
        return $this->getRate('sekee', RateCategory::COIN, null, $forceRefresh);
    }

    public function getCoinBahar(bool $forceRefresh = false): Rate
    {
        return $this->getRate('sekeb', RateCategory::COIN, null, $forceRefresh);
    }

    public function getCrypto(string $symbol, bool $forceRefresh = false): Rate
    {
        return $this->getRate($symbol, RateCategory::CRYPTO, null, $forceRefresh);
    }

    // ==========================================
    // Internal Helpers
    // ==========================================

    private function getCategoryCacheKey(string $providerName, string $category): string
    {
        return "rates_{$providerName}_{$category}";
    }

    private function findFirstSupportingProvider(string $category): ProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsCategory($category)) {
                return $provider;
            }
        }

        throw new ProviderUnavailableException("No provider found supporting category '{$category}'");
    }
}
