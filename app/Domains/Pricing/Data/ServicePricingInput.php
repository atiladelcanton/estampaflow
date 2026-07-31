<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Data;

use Carbon\CarbonImmutable;

final readonly class ServicePricingInput
{
    /** @param array<string, mixed> $parameters */
    public function __construct(
        public string $tenantId,
        public string $serviceTypeId,
        public string $schemaVersionId,
        public int $appliedQuantity,
        public array $parameters,
        public CarbonImmutable $referenceDate,
        public string $currency = 'BRL',
    ) {}
}
