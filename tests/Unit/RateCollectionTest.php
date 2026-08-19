<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class RateCollectionTest extends TestCase
{
    private RateCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = new RateCollection([
            new Rate('price_dollar_rl', 'دلار', 'USD', RateCategory::FREE_CURRENCY, 885000.0),
            new Rate('price_eur', 'یورو', 'EUR', RateCategory::FREE_CURRENCY, 960000.0),
            new Rate('geram18', 'طلای 18 عیار', 'IR18K', RateCategory::GOLD, 48500000.0),
            new Rate('btc', 'Bitcoin', 'BTC', RateCategory::CRYPTO, 96500.0, null, null, null, null, null, null, 'USD'),
        ]);
    }

    public function testCountAndAccess(): void
    {
        $this->assertCount(4, $this->collection);
        $this->assertFalse($this->collection->isEmpty());

        $usd = $this->collection->get('USD');
        $this->assertNotNull($usd);
        $this->assertSame('USD', $usd->getSymbol());
        $this->assertSame(885000.0, $usd->getPrice());
        $this->assertSame(88500.0, $usd->getPriceInTomans());
    }

    public function testGetByPersianTitle(): void
    {
        $rate = $this->collection->get('طلای 18 عیار');
        $this->assertNotNull($rate);
        $this->assertSame('IR18K', $rate->getSymbol());
    }

    public function testSearch(): void
    {
        $results = $this->collection->search('دلار');
        $this->assertCount(1, $results);
        $this->assertSame('USD', $results->first()->getSymbol());

        $crypto = $this->collection->search('BTC');
        $this->assertCount(1, $crypto);
    }

    public function testFilterByCategory(): void
    {
        $currencies = $this->collection->filterByCategory(RateCategory::FREE_CURRENCY);
        $this->assertCount(2, $currencies);

        $gold = $this->collection->filterByCategory(RateCategory::GOLD);
        $this->assertCount(1, $gold);
    }
}
