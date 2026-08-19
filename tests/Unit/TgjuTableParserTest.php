<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Parsers\TgjuTableParser;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class TgjuTableParserTest extends TestCase
{
    private TgjuTableParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TgjuTableParser();
    }

    public function testParsesCurrencyTableFixture(): void
    {
        $html = $this->loadFixture('tgju/currency_table.html');
        $collection = $this->parser->parse($html, RateCategory::FREE_CURRENCY);

        $this->assertCount(4, $collection);

        $dollar = $collection->get('USD');
        $this->assertNotNull($dollar);
        $this->assertSame('دلار', $dollar->getTitle());
        $this->assertSame(885500.0, $dollar->getPrice());
        $this->assertSame(88550.0, $dollar->getPriceInTomans());
        $this->assertSame(2500.0, $dollar->getChange());
        $this->assertSame(0.28, $dollar->getChangePercent());
        $this->assertSame(881000.0, $dollar->getMinPrice());
        $this->assertSame(887000.0, $dollar->getMaxPrice());

        $eur = $collection->get('price_eur');
        $this->assertNotNull($eur);
        $this->assertSame(960200.0, $eur->getPrice());
        $this->assertSame(-1100.0, $eur->getChange());
    }

    public function testParsesGoldTableFixture(): void
    {
        $html = $this->loadFixture('tgju/gold_table.html');
        $collection = $this->parser->parse($html, RateCategory::GOLD);

        $this->assertCount(3, $collection);

        $gold18 = $collection->get('geram18');
        $this->assertNotNull($gold18);
        $this->assertSame(48500000.0, $gold18->getPrice());
        $this->assertSame('IR18K', $gold18->getSymbol());
    }

    public function testParsesCoinTableFixture(): void
    {
        $html = $this->loadFixture('tgju/coin_table.html');
        $collection = $this->parser->parse($html, RateCategory::COIN);

        $this->assertCount(3, $collection);

        $emami = $collection->get('sekee');
        $this->assertNotNull($emami);
        $this->assertSame(550000000.0, $emami->getPrice());
        $this->assertSame('SEKEE', $emami->getSymbol());
    }
}
