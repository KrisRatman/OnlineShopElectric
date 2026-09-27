<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Деньги в приложении — целые копейки. В рубли с дробной частью они
 * превращаются только на границах: при выводе и в API ЮKassa.
 */
final class Money
{
    /** 149000 → «1 490 ₽», 149050 → «1 490,50 ₽». */
    public static function format(int $kopecks): string
    {
        $decimals = $kopecks % 100 === 0 ? 0 : 2;

        return number_format($kopecks / 100, $decimals, ',', ' ').' ₽';
    }

    /** 149050 → "1490.50" — формат суммы в API ЮKassa. Без float, только целочисленная арифметика. */
    public static function toDecimal(int $kopecks): string
    {
        $sign = $kopecks < 0 ? '-' : '';
        $kopecks = abs($kopecks);

        return sprintf('%s%d.%02d', $sign, intdiv($kopecks, 100), $kopecks % 100);
    }

    /** "1490.5" / "1490.50" / "1490" → 149050. */
    public static function fromDecimal(string $value): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim($value), $m)) {
            throw new InvalidArgumentException("Некорректная сумма: {$value}");
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }
}
