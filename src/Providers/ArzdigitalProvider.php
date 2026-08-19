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
use Aicrion\IrCurrencyRateScraper\Parsers\ArzdigitalParser;

final class ArzdigitalProvider implements ProviderInterface
{
    public const NAME = 'arzdigital';

    private HttpClientInterface $httpClient;
    private ParserInterface $parser;
    private string $coinsUrl;

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        ?ParserInterface $parser = null,
        string $coinsUrl = 'https://arzdigital.com/coins/'
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->parser = $parser ?? new ArzdigitalParser();
        $this->coinsUrl = $coinsUrl;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function supportsCategory(string $category): bool
    {
        return $category === RateCategory::CRYPTO;
    }

    public function fetchCategory(string $category = RateCategory::CRYPTO): RateCollection
    {
        if (!$this->supportsCategory($category)) {
            throw new ScraperException('ArzDigital provider only supports crypto category');
        }

        try {
            $content = $this->httpClient->get($this->coinsUrl);
            return $this->parser->parse($content, RateCategory::CRYPTO);
        } catch (NetworkException | ParseException $e) {
            throw new ScraperException("Failed fetching crypto from Arzdigital ({$this->coinsUrl}): {$e->getMessage()}", 0, $e);
        }
    }

    public function fetchRate(string $symbolOrId, ?string $category = null): ?Rate
    {
        if ($category !== null && !$this->supportsCategory($category)) {
            return null;
        }

        $collection = $this->fetchCategory(RateCategory::CRYPTO);
        return $collection->get($symbolOrId);
    }

    public function search(string $query, ?string $category = null): RateCollection
    {
        if ($category !== null && !$this->supportsCategory($category)) {
            return new RateCollection();
        }

        $collection = $this->fetchCategory(RateCategory::CRYPTO);
        return $collection->search($query);
    }
}
