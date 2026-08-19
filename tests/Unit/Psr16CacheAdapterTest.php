<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Cache\Psr16CacheAdapter;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

final class Psr16CacheAdapterTest extends TestCase
{
    public function testDelegatesToPsr16Implementation(): void
    {
        $mockPsr16 = $this->createMock(Psr16CacheInterface::class);

        $mockPsr16->expects($this->once())
            ->method('set')
            ->with('foo', 'bar', 300)
            ->willReturn(true);

        $mockPsr16->expects($this->once())
            ->method('get')
            ->with('foo', null)
            ->willReturn('bar');

        $mockPsr16->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true);

        $mockPsr16->expects($this->once())
            ->method('delete')
            ->with('foo')
            ->willReturn(true);

        $mockPsr16->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $adapter = new Psr16CacheAdapter($mockPsr16);

        $this->assertTrue($adapter->set('foo', 'bar', 300));
        $this->assertSame('bar', $adapter->get('foo'));
        $this->assertTrue($adapter->has('foo'));
        $this->assertTrue($adapter->delete('foo'));
        $this->assertTrue($adapter->clear());
    }
}
