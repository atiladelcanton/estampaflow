<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Enums\GuidedPricingTemplate;
use App\Domains\ServiceCatalog\Models\ServiceType;

final class GuidedPricingTemplateResolver
{
    public function resolve(ServiceType $serviceType): GuidedPricingTemplate
    {
        return match (mb_strtoupper(trim($serviceType->code))) {
            'DTF' => GuidedPricingTemplate::DTF_METER,
            'SILK' => GuidedPricingTemplate::SILK_MATRIX,
            'SUBLIMACAO' => GuidedPricingTemplate::SUBLIMATION_MATRIX,
            'BORDADO' => GuidedPricingTemplate::EMBROIDERY_MATRIX,
            default => GuidedPricingTemplate::GENERIC,
        };
    }
}
