<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Enums;

enum PricingMode: string
{
    case AUTOMATIC = 'AUTOMATIC';
    case MANUAL = 'MANUAL';
    case HYBRID = 'HYBRID';

    public function label(): string
    {
        return match ($this) {
            self::AUTOMATIC => 'Automático',
            self::MANUAL => 'Manual',
            self::HYBRID => 'Híbrido',
        };
    }
}
