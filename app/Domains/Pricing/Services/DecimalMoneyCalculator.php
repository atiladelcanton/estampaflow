<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\ValueObjects\Money;
use InvalidArgumentException;

final class DecimalMoneyCalculator
{
    public function majorToMoney(string $amount, string $currency = 'BRL'): Money
    {
        $normalized = str_replace(',', '.', trim($amount));

        if (! is_numeric($normalized) || bccomp($normalized, '0', 8) < 0) {
            throw new InvalidArgumentException('Valor decimal inválido.');
        }

        $minorWithDecimals = bcmul($normalized, '100', 8);
        [$integer, $fraction] = array_pad(explode('.', $minorWithDecimals, 2), 2, '');
        $rounded = (int) $integer;

        if (($fraction[0] ?? '0') >= '5') {
            $rounded++;
        }

        return new Money($rounded, $currency);
    }
}
