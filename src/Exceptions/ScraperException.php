<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Exceptions;

use Exception;
use Aicrion\IrCurrencyRateScraper\Contracts\CurrencyScraperThrowable;

/**
 * Base exception class for all scraper errors.
 */
class ScraperException extends Exception implements CurrencyScraperThrowable
{
}
