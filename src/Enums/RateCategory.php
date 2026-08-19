<?php

declare(strict_types=1);

namespace Aicrion\IrCurrencyRateScraper\Enums;

/**
 * Standard categories of exchange rates, commodities, and digital assets.
 */
final class RateCategory
{
    public const FREE_CURRENCY = 'free_currency';     // بازار آزاد (دلار، یورو، درهم، پوند، ...)
    public const BANK_GOVERNMENT = 'bank_government'; // ارز دولتی / بانکی
    public const SANA = 'sana';                       // سامانه سنا
    public const NIMA = 'nima';                       // سامانه نیما
    public const TRANSFER = 'transfer';               // حوالجات ارزی
    public const GOLD = 'gold';                       // طلا (۱۸ عیار، ۲۴ عیار، آبشده، مثقال، ...)
    public const COIN = 'coin';                       // سکه (امامی، بهار آزادی، نیم، ربع، گرمی، ...)
    public const CRYPTO = 'crypto';                   // ارزهای دیجیتال (بیت‌کوین، اتریوم، تتر، ...)

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::FREE_CURRENCY,
            self::BANK_GOVERNMENT,
            self::SANA,
            self::NIMA,
            self::TRANSFER,
            self::GOLD,
            self::COIN,
            self::CRYPTO,
        ];
    }

    public static function isValid(string $category): bool
    {
        return in_array($category, self::all(), true);
    }
}
