<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Enums;

enum PriceTableStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case RETIRED = 'RETIRED';
}
