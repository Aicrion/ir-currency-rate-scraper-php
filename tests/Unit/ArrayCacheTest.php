<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Cache\ArrayCache;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class ArrayCacheTest extends TestCase
{
    private ArrayCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new ArrayCache();
    }

    public function testSetGetAndHas(): void
    {
        $this->assertTrue($this->cache->set('test_key', 'hello_world', 300));
        $this->assertTrue($this->cache->has('test_key'));
        $this->assertSame('hello_world', $this->cache->get('test_key'));
    }

    public function testGetDefaultWhenMissing(): void
    {
        $this->assertNull($this->cache->get('missing'));
        $this->assertSame('default_val', $this->cache->get('missing', 'default_val'));
    }

    public function testDeleteAndClear(): void
    {
        $this->cache->set('k1', 'v1');
        $this->cache->set('k2', 'v2');

        $this->assertTrue($this->cache->delete('k1'));
        $this->assertFalse($this->cache->has('k1'));
        $this->assertTrue($this->cache->has('k2'));

        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('k2'));
    }
}
