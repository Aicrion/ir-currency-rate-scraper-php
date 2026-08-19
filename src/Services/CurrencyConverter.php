<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Services;

use Aicrion\IrCurrencyRateScraper\CurrencyScraper;
use Aicrion\IrCurrencyRateScraper\Enums\RateCategory;
use Aicrion\IrCurrencyRateScraper\Exceptions\RateNotFoundException;
use InvalidArgumentException;

/**
 * High-precision Currency Converter and Gold Calculator Service.
 */
final class CurrencyConverter
{
    private CurrencyScraper $scraper;

    public function __construct(CurrencyScraper $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Convert an amount from one currency to another using real-time scraped rates.
     *
     * @param float $amount The amount to convert.
     * @param string $from Source currency code (e.g. 'USD', 'EUR', 'IRR', 'TOMAN', 'BTC').
     * @param string $to Target currency code (e.g. 'TOMAN', 'IRR', 'USD', 'EUR').
     * @return float The converted amount.
     * @throws RateNotFoundException
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $normalizedFrom = strtoupper(trim($from));
        $normalizedTo = strtoupper(trim($to));

        if ($amount === 0.0 || $normalizedFrom === $normalizedTo) {
            return $amount;
        }

        // Toman <-> Rial direct conversion
        if ($normalizedFrom === 'TOMAN' && $normalizedTo === 'IRR') {
            return $amount * 10.0;
        }
        if ($normalizedFrom === 'IRR' && $normalizedTo === 'TOMAN') {
            return $amount / 10.0;
        }

        // 1. Convert Source Currency to Iranian Rials (IRR)
        $amountInRials = $this->convertToRials($amount, $normalizedFrom);

        // 2. Convert Rials to Target Currency
        return $this->convertFromRials($amountInRials, $normalizedTo);
    }

    /**
     * Calculate exact gold jewelry price with weight, karat, craftsmanship wage, seller profit, and VAT.
     *
     * @param float $grams Weight in grams.
     * @param int $karat Gold karat (18 or 24). Defaults to 18.
     * @param float $wagePercent Craftsmanship wage percentage (اجرت ساخت). Defaults to 0%.
     * @param float $profitPercent Seller profit percentage (سود طلافروش). Defaults to 7%.
     * @param float $taxPercent Value added tax percentage on wage & profit (مالیات بر ارزش افزوده). Defaults to 9%.
     * @return array<string, mixed> Detailed calculation breakdown.
     * @throws RateNotFoundException
     */
    public function calculateGold(
        float $grams,
        int $karat = 18,
        float $wagePercent = 0.0,
        float $profitPercent = 7.0,
        float $taxPercent = 9.0
    ): array {
        if ($grams <= 0) {
            throw new InvalidArgumentException('Gold weight in grams must be greater than zero.');
        }

        $rateItem = $karat === 24
            ? $this->scraper->getGold24k()
            : $this->scraper->getGold18k();

        $gramPriceRial = $rateItem->getPrice();
        $baseGoldPriceRial = $grams * $gramPriceRial;

        // Wage (اجرت ساخت)
        $wageAmountRial = $baseGoldPriceRial * ($wagePercent / 100.0);

        // Seller Profit (سود فروشنده معمولاً ۷٪ روی قیمت طلا + اجرت)
        $profitAmountRial = ($baseGoldPriceRial + $wageAmountRial) * ($profitPercent / 100.0);

        // VAT Tax (طبق قانون جدید مالیات فقط به اجرت و سود تعلق می‌گیرد و نه اصل طلا)
        $taxAmountRial = ($wageAmountRial + $profitAmountRial) * ($taxPercent / 100.0);

        $totalRial = $baseGoldPriceRial + $wageAmountRial + $profitAmountRial + $taxAmountRial;

        return [
            'grams' => $grams,
            'karat' => $karat,
            'gram_price_rial' => $gramPriceRial,
            'gram_price_toman' => $gramPriceRial / 10.0,
            'base_gold_rial' => round($baseGoldPriceRial, 2),
            'base_gold_toman' => round($baseGoldPriceRial / 10.0, 2),
            'wage_percent' => $wagePercent,
            'wage_amount_rial' => round($wageAmountRial, 2),
            'profit_percent' => $profitPercent,
            'profit_amount_rial' => round($profitAmountRial, 2),
            'tax_percent' => $taxPercent,
            'tax_amount_rial' => round($taxAmountRial, 2),
            'total_price_rial' => round($totalRial, 2),
            'total_price_toman' => round($totalRial / 10.0, 2),
        ];
    }

    private function convertToRials(float $amount, string $currency): float
    {
        if ($currency === 'IRR') {
            return $amount;
        }
        if ($currency === 'TOMAN') {
            return $amount * 10.0;
        }

        // Check if crypto (priced in USD)
        try {
            $cryptoRate = $this->scraper->getRate($currency, RateCategory::CRYPTO);
            $usdPrice = $cryptoRate->getPrice();
            $usdInRials = $this->scraper->getUsd()->getPrice();
            return $amount * $usdPrice * $usdInRials;
        } catch (RateNotFoundException $e) {
            // Not a crypto rate, continue to fiat currency lookup
        }

        // Fiat currency lookup (USD, EUR, GBP, AED, etc.)
        $rate = $this->scraper->getRate($currency, RateCategory::FREE_CURRENCY);
        return $amount * $rate->getPrice();
    }

    private function convertFromRials(float $amountInRials, string $currency): float
    {
        if ($currency === 'IRR') {
            return $amountInRials;
        }
        if ($currency === 'TOMAN') {
            return $amountInRials / 10.0;
        }

        // Check if target is crypto (priced in USD)
        try {
            $cryptoRate = $this->scraper->getRate($currency, RateCategory::CRYPTO);
            $usdPrice = $cryptoRate->getPrice();
            $usdInRials = $this->scraper->getUsd()->getPrice();
            $cryptoInRials = $usdPrice * $usdInRials;

            return $cryptoInRials > 0 ? $amountInRials / $cryptoInRials : 0.0;
        } catch (RateNotFoundException $e) {
            // Not a crypto rate, continue to fiat
        }

        // Fiat currency
        $rate = $this->scraper->getRate($currency, RateCategory::FREE_CURRENCY);
        $ratePrice = $rate->getPrice();

        return $ratePrice > 0 ? $amountInRials / $ratePrice : 0.0;
    }
}
