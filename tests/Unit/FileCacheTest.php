<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Cache\FileCache;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class FileCacheTest extends TestCase
{
    private string $tempDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_currency_cache_' . uniqid();
        $this->cache = new FileCache($this->tempDir, 300);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testSetAndGetComplexObject(): void
    {
        $rate = new Rate('usd', 'دلار', 'USD', RateCategory::FREE_CURRENCY, 885000.0);
        $this->assertTrue($this->cache->set('usd_rate', $rate, 300));
        $this->assertTrue($this->cache->has('usd_rate'));

        /** @var Rate $cached */
        $cached = $this->cache->get('usd_rate');
        $this->assertInstanceOf(Rate::class, $cached);
        $this->assertSame('USD', $cached->getSymbol());
        $this->assertSame(885000.0, $cached->getPrice());
    }

    public function testDeleteAndClear(): void
    {
        $this->cache->set('a', '1');
        $this->cache->set('b', '2');

        $this->cache->delete('a');
        $this->assertFalse($this->cache->has('a'));
        $this->assertTrue($this->cache->has('b'));

        $this->cache->clear();
        $this->assertFalse($this->cache->has('b'));
    }
}
