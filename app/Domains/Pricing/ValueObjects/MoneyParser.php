<?php

declare(strict_types=1);

namespace App\Domains\Pricing\ValueObjects;

use InvalidArgumentException;

final class MoneyParser
{
    public function majorToMinor(string $value): int
    {
        $normalized = preg_replace('/\s+/', '', trim($value)) ?? '';

        if ($normalized === '') {
            return 0;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        [$major, $cents] = array_pad(explode('.', $normalized, 2), 2, '');
        $cents = str_pad($cents, 2, '0');

        return ((int) $major * 100) + (int) substr($cents, 0, 2);
    }

    public function minorToMajor(int $amountMinor): string
    {
        $major = intdiv($amountMinor, 100);
        $cents = $amountMinor % 100;

        return $major.','.str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
    }
}
