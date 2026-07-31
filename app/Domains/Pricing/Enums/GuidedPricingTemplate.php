<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Enums;

enum GuidedPricingTemplate: string
{
    case DTF_METER = 'DTF_METER';
    case SILK_MATRIX = 'SILK_MATRIX';
    case SUBLIMATION_MATRIX = 'SUBLIMATION_MATRIX';
    case EMBROIDERY_MATRIX = 'EMBROIDERY_MATRIX';
    case GENERIC = 'GENERIC';

    public function isGuided(): bool
    {
        return $this !== self::GENERIC;
    }
}
