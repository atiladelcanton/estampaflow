<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Enums;

enum PricingResultStatus: string
{
    case MATCHED = 'MATCHED';
    case MANUAL_REQUIRED = 'MANUAL_REQUIRED';
    case UNAVAILABLE = 'UNAVAILABLE';
    case AMBIGUOUS = 'AMBIGUOUS';
    case INVALID_INPUT = 'INVALID_INPUT';

    public function label(): string
    {
        return match ($this) {
            self::MATCHED => 'Preço calculado',
            self::MANUAL_REQUIRED => 'Preço manual necessário',
            self::UNAVAILABLE => 'Preço indisponível',
            self::AMBIGUOUS => 'Regras conflitantes',
            self::INVALID_INPUT => 'Dados inválidos',
        };
    }
}
