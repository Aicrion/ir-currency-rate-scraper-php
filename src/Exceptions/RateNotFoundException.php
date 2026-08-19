<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Exceptions;

/**
 * Thrown when a specific currency, gold, or crypto rate is not found in the scraped data.
 */
class RateNotFoundException extends ScraperException
{
}
