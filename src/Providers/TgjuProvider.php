<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Providers;

use Aicrion\IrCurrencyRateScraper\Contracts\HttpClientInterface;
use Aicrion\IrCurrencyRateScraper\Contracts\ParserInterface;
use Aicrion\IrCurrencyRateScraper\Contracts\ProviderInterface;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Exceptions\NetworkException;
use Aicrion\IrCurrencyRateScraper\Exceptions\ParseException;
use Aicrion\IrCurrencyRateScraper\Exceptions\ScraperException;
use Aicrion\IrCurrencyRateScraper\Http\CurlHttpClient;
use Aicrion\IrCurrencyRateScraper\Parsers\TgjuProfileParser;
use Aicrion\IrCurrencyRateScraper\Parsers\TgjuTableParser;

final class TgjuProvider implements ProviderInterface
{
    public const NAME = 'tgju';

    private HttpClientInterface $httpClient;
    private ParserInterface $tableParser;
    private TgjuProfileParser $profileParser;

    /** @var array<string, string> */
    private array $categoryUrls = [
        RateCategory::FREE_CURRENCY => 'https://www.tgju.org/currency',
        RateCategory::BANK_GOVERNMENT => 'https://www.tgju.org/bank',
        RateCategory::SANA => 'https://www.tgju.org/sana',
        RateCategory::NIMA => 'https://www.tgju.org/%D9%86%D8%B1%D8%AE-%D8%A7%D8%B1%D8%B2-%D9%86%DB%8C%D9%85%D8%A7%DB%8C%DB%8C',
        RateCategory::TRANSFER => 'https://www.tgju.org/transfer',
        RateCategory::GOLD => 'https://www.tgju.org/gold-chart',
        RateCategory::COIN => 'https://www.tgju.org/coin',
        RateCategory::CRYPTO => 'https://www.tgju.org/crypto',
    ];

    /** @var array<string, string> Profile fallback URLs */
    private array $profileFallbacks = [
        'usd' => 'https://www.tgju.org/profile/price_dollar_rl',
        'price_dollar_rl' => 'https://www.tgju.org/profile/price_dollar_rl',
        'eur' => 'https://www.tgju.org/profile/price_eur',
        'gbp' => 'https://www.tgju.org/profile/price_gbp',
        'aed' => 'https://www.tgju.org/profile/price_aed',
        'ir18k' => 'https://www.tgju.org/profile/geram18',
        'geram18' => 'https://www.tgju.org/profile/geram18',
        'ir24k' => 'https://www.tgju.org/profile/geram24',
        'geram24' => 'https://www.tgju.org/profile/geram24',
        'mesghal' => 'https://www.tgju.org/profile/mesghal',
        'sekee' => 'https://www.tgju.org/profile/sekee',
        'sekeb' => 'https://www.tgju.org/profile/sekeb',
        'nim' => 'https://www.tgju.org/profile/nim',
        'rob' => 'https://www.tgju.org/profile/rob',
        'gerami' => 'https://www.tgju.org/profile/gerami',
    ];

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        ?ParserInterface $tableParser = null,
        ?TgjuProfileParser $profileParser = null
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->tableParser = $tableParser ?? new TgjuTableParser();
        $this->profileParser = $profileParser ?? new TgjuProfileParser();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function supportsCategory(string $category): bool
    {
        return isset($this->categoryUrls[$category]);
    }

    public function fetchCategory(string $category): RateCollection
    {
        if (!$this->supportsCategory($category)) {
            throw new ScraperException("TGJU does not support category '{$category}'");
        }

        $url = $this->categoryUrls[$category];
        try {
            $html = $this->httpClient->get($url);
            return $this->tableParser->parse($html, $category);
        } catch (NetworkException | ParseException $e) {
            throw new ScraperException("Failed fetching TGJU category '{$category}' from {$url}: {$e->getMessage()}", 0, $e);
        }
    }

    public function fetchRate(string $symbolOrId, ?string $category = null): ?Rate
    {
        $normalizedKey = strtolower(trim($symbolOrId));

        // 1. If category is specified, search in that category
        if ($category !== null && $this->supportsCategory($category)) {
            try {
                $collection = $this->fetchCategory($category);
                $rate = $collection->get($normalizedKey);
                if ($rate !== null) {
                    return $rate;
                }
            } catch (ScraperException $e) {
                // If direct category failed, allow profile fallback or broad search
            }
        }

        // 2. Search across free currency or matching categories
        $categoriesToSearch = $category !== null ? [$category] : [
            RateCategory::FREE_CURRENCY,
            RateCategory::GOLD,
            RateCategory::COIN,
            RateCategory::CRYPTO,
            RateCategory::BANK_GOVERNMENT,
            RateCategory::SANA,
            RateCategory::NIMA,
            RateCategory::TRANSFER,
        ];

        foreach ($categoriesToSearch as $cat) {
            try {
                $collection = $this->fetchCategory($cat);
                $rate = $collection->get($normalizedKey);
                if ($rate !== null) {
                    return $rate;
                }
            } catch (ScraperException $e) {
                // Continue searching remaining categories
            }
        }

        // 3. Fallback: Check dedicated profile page fallback (e.g. price_dollar_rl, geram18, etc.)
        if (isset($this->profileFallbacks[$normalizedKey])) {
            return $this->fetchProfileFallback($this->profileFallbacks[$normalizedKey], $category ?? RateCategory::FREE_CURRENCY);
        }

        return null;
    }

    public function search(string $query, ?string $category = null): RateCollection
    {
        $results = new RateCollection();

        $categories = $category !== null ? [$category] : RateCategory::all();
        foreach ($categories as $cat) {
            if (!$this->supportsCategory($cat)) {
                continue;
            }

            try {
                $collection = $this->fetchCategory($cat);
                $matched = $collection->search($query);
                if (!$matched->isEmpty()) {
                    $results = $results->merge($matched);
                }
            } catch (ScraperException $e) {
                // Ignore failure of single category during search
            }
        }

        return $results;
    }

    private function fetchProfileFallback(string $profileUrl, string $category): ?Rate
    {
        try {
            $html = $this->httpClient->get($profileUrl);
            $collection = $this->profileParser->parse($html, $category);
            return $collection->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
