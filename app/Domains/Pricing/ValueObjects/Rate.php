<?php

declare(strict_types=1);

namespace App\Domains\Pricing\ValueObjects;

use App\Domains\Pricing\Enums\RateUnit;
use InvalidArgumentException;

final readonly class Rate
{
    public string $value;

    public function __construct(
        string $value,
        public string $currency,
        public RateUnit $unit,
    ) {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^\d+(?:\.\d{1,8})?$/', $normalized)) {
            throw new InvalidArgumentException('Taxa inválida.');
        }

        $this->value = $normalized;
    }
}
