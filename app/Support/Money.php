<?php

namespace App\Support;

final class Money
{
    /** "RWF 150,000" (no decimals for RWF) or "USD 1,250.00". */
    public static function format(float|int|string|null $amount, ?string $currency = 'RWF'): string
    {
        $currency = $currency ?: 'RWF';
        $decimals = in_array($currency, ['RWF', 'UGX', 'TZS', 'BIF'], true) ? 0 : 2;

        return $currency . ' ' . number_format((float) $amount, $decimals);
    }
}
