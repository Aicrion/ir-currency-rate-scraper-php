<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Parsers\TgjuProfileParser;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class TgjuProfileParserTest extends TestCase
{
    private TgjuProfileParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TgjuProfileParser();
    }

    public function testParsesDollarProfileFixture(): void
    {
        $html = $this->loadFixture('tgju/profile_dollar.html');
        $collection = $this->parser->parse($html, RateCategory::FREE_CURRENCY);

        $this->assertCount(1, $collection);
        $rate = $collection->first();

        $this->assertNotNull($rate);
        $this->assertSame('دلار', $rate->getTitle());
        $this->assertSame('USD', $rate->getSymbol());
        $this->assertSame(886000.0, $rate->getPrice());
        $this->assertSame(3000.0, $rate->getChange());
        $this->assertSame(0.34, $rate->getChangePercent());
        $this->assertSame(880000.0, $rate->getMinPrice());
        $this->assertSame(888000.0, $rate->getMaxPrice());
    }
}
