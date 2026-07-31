<?php

declare(strict_types=1);

namespace App\Application\Pricing\Data;

use App\Domains\ServiceCatalog\Enums\PricingStrategy;

final readonly class ServicePricingSetupData
{
    /**
     * @param  list<SavePriceRuleData>  $rules
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public PricingStrategy $strategy,
        public array $rules,
        public array $settings,
        public ?string $validFrom = null,
        public ?string $validUntil = null,
    ) {}
}
