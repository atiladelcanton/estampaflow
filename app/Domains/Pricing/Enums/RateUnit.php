<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Enums;

enum RateUnit: string
{
    case PER_CM2 = 'PER_CM2';
    case PER_THOUSAND_STITCHES = 'PER_THOUSAND_STITCHES';

    public function label(): string
    {
        return match ($this) {
            self::PER_CM2 => 'por cm²',
            self::PER_THOUSAND_STITCHES => 'por mil pontos',
        };
    }
}
