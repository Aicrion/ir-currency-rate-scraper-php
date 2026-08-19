# Price Alerts & Market Watcher Guide

The Price Alert and Market Watcher engine allows registering price threshold rules and 24h percentage fluctuation watchers, triggering custom notification callbacks (e.g. sending SMS, Telegram alerts, webhooks, or email).

---

## 🔔 Setting Up Price Threshold Alerts

Register an alert that triggers when an asset crosses a specified price point:

```php
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\DTO\Rate;

$scraper = CurrencyScraper::create();

// 1. Alert when USD rises above 900,000 Rials (90,000 Tomans)
$scraper->addPriceAlert('USD', 900000.0, '>=', function (Rate $rate, array $alert) {
    echo "🚨 ALERT: {$rate->getTitle()} reached " . number_format($rate->getPrice()) . " Rials!\n";
    // Send Telegram message / SMS / Webhook here
});

// 2. Alert when 18K Gold drops below 48,000,000 Rials
$scraper->addPriceAlert('IR18K', 48000000.0, '<=', function (Rate $rate, array $alert) {
    echo "📉 Gold price drop: " . number_format($rate->getPrice()) . "\n";
});
```

---

## 📊 Setting Up Percentage Fluctuation Alerts

Trigger notifications when an asset's 24-hour change percentage exceeds a threshold:

```php
// Alert if Bitcoin gains 5% or more in 24 hours
$scraper->addPercentAlert('BTC', 5.0, '>=', function (Rate $rate, array $alert) {
    echo "🚀 Bitcoin surged by +{$rate->getChangePercent()}% in 24 hours!\n";
});

// Alert if Euro falls by 2% or more
$scraper->addPercentAlert('EUR', -2.0, '<=', function (Rate $rate, array $alert) {
    echo "⚠️ Euro dropped by {$rate->getChangePercent()}%\n";
});
```

---

## 🔄 Evaluating Alerts

To evaluate registered alerts in a periodic Cron job or event listener:

```php
// Check all registered alerts against real-time rates:
$triggeredList = $scraper->checkAlerts();

foreach ($triggeredList as $item) {
    echo "Triggered alert ID: {$item['alert_id']} for {$item['symbol']}\n";
}
```
