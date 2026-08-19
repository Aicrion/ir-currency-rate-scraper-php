<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Enums;

/**
 * Aggregation and resolution strategies when querying rates across multiple sources.
 */
final class AggregationStrategy
{
    /**
     * Return the rate from the first source that successfully returns a non-null rate (Fallback mode).
     */
    public const FIRST_AVAILABLE = 'first_available';

    /**
     * Compute average price, along with min and max across all responsive sources.
     */
    public const AVERAGE = 'average';

    /**
     * Return all available rates from every responsive source in an AggregatedRate container.
     */
    public const ALL_SOURCES = 'all_sources';
}
