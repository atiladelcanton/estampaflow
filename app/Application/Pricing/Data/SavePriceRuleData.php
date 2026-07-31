<?php

declare(strict_types=1);

namespace App\Application\Pricing\Data;

final readonly class SavePriceRuleData
{
    /** @param list<array{parameter: string, operator: string, value: mixed}> $conditions */
    public function __construct(
        public string $name,
        public ?int $minQuantity,
        public ?int $maxQuantity,
        public array $conditions,
        public ?int $unitAmountMinor,
        public ?string $rateValue,
        public ?string $rateUnit,
        public int $setupAmountMinor,
        public int $minimumAmountMinor,
        public int $priority,
        public int $sortOrder,
    ) {}
}
