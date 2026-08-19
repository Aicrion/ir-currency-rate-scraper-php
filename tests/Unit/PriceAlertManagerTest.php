<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Tests\Unit;

use Aicrion\IrCurrencyRateScraper\Alerts\PriceAlertManager;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;
use Aicrion\IrCurrencyRateScraper\DTO\RateCollection;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Tests\TestCase;

final class PriceAlertManagerTest extends TestCase
{
    private PriceAlertManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PriceAlertManager();
    }

    public function testTriggersPriceThresholdAlert(): void
    {
        $callbackTriggered = false;
        $triggeredRate = null;

        $this->manager->addPriceAlert('USD', 880000.0, '>=', function (Rate $rate, array $alert) use (&$callbackTriggered, &$triggeredRate) {
            $callbackTriggered = true;
            $triggeredRate = $rate;
        });

        $rates = new RateCollection([
            new Rate('usd', 'دلار', 'USD', RateCategory::FREE_CURRENCY, 885000.0),
        ]);

        $triggered = $this->manager->check($rates);

        $this->assertCount(1, $triggered);
        $this->assertTrue($callbackTriggered);
        $this->assertNotNull($triggeredRate);
        $this->assertSame('USD', $triggeredRate->getSymbol());
    }

    public function testDoesNotTriggerWhenConditionNotMet(): void
    {
        $this->manager->addPriceAlert('USD', 900000.0, '>='); // Target higher than current

        $rates = new RateCollection([
            new Rate('usd', 'دلار', 'USD', RateCategory::FREE_CURRENCY, 885000.0),
        ]);

        $triggered = $this->manager->check($rates);
        $this->assertEmpty($triggered);
    }

    public function testTriggersPercentageChangeAlert(): void
    {
        $this->manager->addPercentAlert('BTC', 2.0, '>='); // Trigger if 24h change >= 2.0%

        $rates = new RateCollection([
            new Rate('btc', 'Bitcoin', 'BTC', RateCategory::CRYPTO, 96500.0, null, null, null, 2.5),
        ]);

        $triggered = $this->manager->check($rates);
        $this->assertCount(1, $triggered);
        $this->assertSame(2.5, $triggered[0]['current_value']);
    }

    public function testRemoveAndClearAlerts(): void
    {
        $id1 = $this->manager->addPriceAlert('USD', 800000.0);
        $id2 = $this->manager->addPriceAlert('EUR', 900000.0);

        $this->assertCount(2, $this->manager->getAlerts());

        $this->assertTrue($this->manager->removeAlert($id1));
        $this->assertCount(1, $this->manager->getAlerts());

        $this->manager->clearAlerts();
        $this->assertEmpty($this->manager->getAlerts());
    }
}
