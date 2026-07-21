<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Enums;

enum ServiceSchemaStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case RETIRED = 'RETIRED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::ACTIVE => 'Ativa',
            self::RETIRED => 'Descontinuada',
        };
    }
}
