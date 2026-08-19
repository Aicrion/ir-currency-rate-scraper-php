<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Exceptions;

/**
 * Thrown when raw HTML/JSON cannot be parsed or expected rate elements cannot be found.
 */
class ParseException extends ScraperException
{
}
