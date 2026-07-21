<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Data;

use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;

final readonly class UpdateServiceTypeData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public PricingMode $pricingMode,
        public ?PricingStrategy $pricingStrategy,
        public bool $requiresArt,
        public bool $allowsMultiplePositions,
        public int $sortOrder,
    ) {}
}
