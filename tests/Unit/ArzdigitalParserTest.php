<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Parsers\ArzdigitalParser;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class ArzdigitalParserTest extends TestCase
{
    private ArzdigitalParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ArzdigitalParser();
    }

    public function testParsesHtmlFixture(): void
    {
        $html = $this->loadFixture('arzdigital/coins.html');
        $collection = $this->parser->parse($html, RateCategory::CRYPTO);

        $this->assertCount(3, $collection);

        $btc = $collection->get('BTC');
        $this->assertNotNull($btc);
        $this->assertSame('بیت کوین', $btc->getTitle());
        $this->assertSame('BTC', $btc->getSymbol());
        $this->assertSame(96500.0, $btc->getPrice());
        $this->assertSame(2.5, $btc->getChangePercent());
    }

    public function testParsesJsonFixture(): void
    {
        $json = $this->loadFixture('arzdigital/coins.json');
        $collection = $this->parser->parse($json, RateCategory::CRYPTO);

        $this->assertCount(4, $collection);

        $eth = $collection->get('ETH');
        $this->assertNotNull($eth);
        $this->assertSame('Ethereum', $eth->getTitle());
        $this->assertSame(2850.25, $eth->getPrice());
        $this->assertSame(-0.85, $eth->getChangePercent());
    }
}
