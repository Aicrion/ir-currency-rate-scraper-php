<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Http\CurlHttpClient;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class CurlHttpClientProxyTest extends TestCase
{
    public function testSetsProxyAndAuth(): void
    {
        $client = new CurlHttpClient();
        $this->assertNull($client->getProxy());

        $client->setProxy('http://127.0.0.1:8080', 'user:pass');
        $this->assertSame('http://127.0.0.1:8080', $client->getProxy());
    }

    public function testRotatesUserAgent(): void
    {
        $client = new CurlHttpClient('DefaultAgent/1.0');
        $this->assertSame('DefaultAgent/1.0', $client->getActiveUserAgent());

        $pool = ['AgentOne/1.0', 'AgentTwo/2.0'];
        $client->setUserAgentPool($pool);
        $client->enableUserAgentRotation(true);

        $ua = $client->getActiveUserAgent();
        $this->assertContains($ua, $pool);
    }

    public function testScraperFacadeProxyHelpers(): void
    {
        $scraper = CurrencyScraper::create();
        $scraper->setProxy('http://proxy.test:8080');
        $scraper->enableUserAgentRotation(true);

        $httpClient = $scraper->getHttpClient();
        $this->assertInstanceOf(CurlHttpClient::class, $httpClient);
        $this->assertSame('http://proxy.test:8080', $httpClient->getProxy());
    }
}
