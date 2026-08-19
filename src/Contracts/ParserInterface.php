<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Contracts;

use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Exceptions\ParseException;

interface ParserInterface
{
    /**
     * Parse raw HTML or JSON into a RateCollection.
     *
     * @param string $content
     * @param string $category
     * @return RateCollection
     * @throws ParseException
     */
    public function parse(string $content, string $category): RateCollection;
}
