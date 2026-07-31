<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Data;

use App\Domains\Pricing\ValueObjects\Money;

final readonly class RulePriceCalculation
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public Money $total,
        public array $details = [],
        public ?string $explanation = null,
    ) {}
}
