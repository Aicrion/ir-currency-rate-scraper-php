# Currency Converter & Gold Calculator Guide

The `Aicrion\IrCurrencyRateScraper` library includes built-in financial calculators for converting amounts across global currencies, Tomans, Rials, and digital assets, as well as breaking down gold jewelry purchase costs.

---

## 💱 Real-Time Currency Conversion

The converter automatically queries live scraped exchange rates to perform high-precision conversions.

```php
use Aicrion\IrCurrencyRateScraper\CurrencyScraper;

$scraper = CurrencyScraper::create();

// 1. Convert US Dollars to Iranian Rials & Tomans
$rials = $scraper->convert(100, 'USD', 'IRR');
$tomans = $scraper->convert(100, 'USD', 'TOMAN');
echo "100 USD = " . number_format($tomans) . " Tomans (" . number_format($rials) . " Rials)\n";

// 2. Convert Tomans back to Foreign Currencies
$dollars = $scraper->convert(8855000, 'TOMAN', 'USD');
$euros = $scraper->convert(8855000, 'TOMAN', 'EUR');
echo "8,855,000 Tomans = $" . number_format($dollars, 2) . " / €" . number_format($euros, 2) . "\n";

// 3. Convert Cryptocurrencies directly to Toman or Rial
$toman = $scraper->convert(0.5, 'BTC', 'TOMAN');
echo "0.5 Bitcoin = " . number_format($toman) . " Tomans\n";

// 4. Cross-Currency Conversions (e.g. Euro to UAE Dirham)
$aed = $scraper->convert(500, 'EUR', 'AED');
echo "500 EUR = " . number_format($aed, 2) . " AED\n";
```

---

## 🥇 Gold Jewelry Value Calculator

Under Iranian gold commerce regulations, jewelry pricing consists of:
1. **Raw Gold Price** (وزن طلا × قیمت هر گرم طلا)
2. **Craftsmanship Wage** (اجرت ساخت - درصد از ارزش خام طلا)
3. **Seller Profit** (سود طلافروش - معمولاً ۷٪ روی قیمت طلا + اجرت)
4. **Value Added Tax / VAT** (مالیات بر ارزش افزوده - طبق قانون جدید ۹٪ فقط روی اجرت و سود، نه اصل طلا)

```php
// Calculate cost of a 4.5 gram 18K gold ring with 10% wage, 7% profit, and 9% VAT:
$calc = $scraper->calculateGold(
    grams: 4.5,
    karat: 18,
    wagePercent: 10.0,
    profitPercent: 7.0,
    taxPercent: 9.0
);

echo "Gold Weight:     " . $calc['grams'] . " Grams (" . $calc['karat'] . " Karat)\n";
echo "Gram Price:      " . number_format($calc['gram_price_rial']) . " Rial (" . number_format($calc['gram_price_toman']) . " Toman)\n";
echo "Base Gold Value: " . number_format($calc['base_gold_rial']) . " Rial\n";
echo "Wage Amount:     " . number_format($calc['wage_amount_rial']) . " Rial\n";
echo "Seller Profit:   " . number_format($calc['profit_amount_rial']) . " Rial\n";
echo "VAT Tax Amount:  " . number_format($calc['tax_amount_rial']) . " Rial\n";
echo "--------------------------------------------------\n";
echo "TOTAL PRICE:     " . number_format($calc['total_price_rial']) . " Rial (" . number_format($calc['total_price_toman']) . " Toman)\n";
```
