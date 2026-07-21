<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Enums;

enum PricingStrategy: string
{
    case UNIT = 'UNIT';
    case QUANTITY_TIER = 'QUANTITY_TIER';
    case AREA = 'AREA';
    case MATRIX = 'MATRIX';
    case STITCH_RANGE = 'STITCH_RANGE';

    public function label(): string
    {
        return match ($this) {
            self::UNIT => 'Por unidade',
            self::QUANTITY_TIER => 'Faixa de quantidade',
            self::AREA => 'Área',
            self::MATRIX => 'Matriz de parâmetros',
            self::STITCH_RANGE => 'Faixa de pontos',
        };
    }
}
