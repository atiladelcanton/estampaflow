<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Data;

use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;

final readonly class CreateServiceTypeData
{
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public PricingMode $pricingMode,
        public ?PricingStrategy $pricingStrategy,
        public bool $requiresArt,
        public bool $allowsMultiplePositions,
        public bool $isDefault = false,
        public int $sortOrder = 0,
    ) {}
}
