<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Enums;

enum ServiceParameterFieldType: string
{
    case TEXT = 'TEXT';
    case INTEGER = 'INTEGER';
    case DECIMAL = 'DECIMAL';
    case BOOLEAN = 'BOOLEAN';
    case SELECT = 'SELECT';
    case MULTISELECT = 'MULTISELECT';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texto',
            self::INTEGER => 'Número inteiro',
            self::DECIMAL => 'Número decimal',
            self::BOOLEAN => 'Sim ou não',
            self::SELECT => 'Seleção única',
            self::MULTISELECT => 'Seleção múltipla',
        };
    }

    public function requiresOptions(): bool
    {
        return in_array($this, [self::SELECT, self::MULTISELECT], true);
    }
}
