<?php

declare(strict_types=1);

namespace app\components;

/**
 * Decimal money math.
 *
 * Prices are stored as DECIMAL and come back from PDO as strings.
 * bcmath keeps line totals exact (19.99 * 3 = 59.97, not a float).
 */
final class Money
{
    public static function normalize(string|int|float $amount): string
    {
        if (is_int($amount)) {
            return bcadd((string) $amount, '0', 2);
        }

        if (is_float($amount)) {
            return number_format($amount, 2, '.', '');
        }

        return bcadd($amount, '0', 2);
    }

    public static function mul(string|int|float $price, int $quantity): string
    {
        return bcmul(self::normalize($price), (string) $quantity, 2);
    }

    public static function add(string|int|float $left, string|int|float $right): string
    {
        return bcadd(self::normalize($left), self::normalize($right), 2);
    }
}
