<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class DecimalMoney
{
    public static function cents(string $value): int
    {
        if (! preg_match('/\A(\d{1,13})(?:\.(\d{1,2}))?\z/', $value, $parts)) {
            throw ValidationException::withMessages(['amount' => 'Số tiền phải là số thập phân không âm, tối đa 13 chữ số nguyên và 2 chữ số lẻ.']);
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    public static function format(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
