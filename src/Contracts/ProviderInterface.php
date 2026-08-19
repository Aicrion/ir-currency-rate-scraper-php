<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Exceptions\ScraperException;

interface ProviderInterface
{
    /**
     * Unique identifier name for this provider (e.g. 'tgju', 'arzdigital').
     */
    public function getName(): string;

    /**
     * Check if this provider supports the given RateCategory.
     */
    public function supportsCategory(string $category): bool;

    /**
     * Fetch all available rates for a category.
     *
     * @throws ScraperException
     */
    public function fetchCategory(string $category): RateCollection;

    /**
     * Fetch a specific rate item by symbol or ID.
     *
     * @throws ScraperException
     */
    public function fetchRate(string $symbolOrId, ?string $category = null): ?Rate;

    /**
     * Search within this provider by keyword or symbol.
     *
     * @throws ScraperException
     */
    public function search(string $query, ?string $category = null): RateCollection;
}
