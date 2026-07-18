<?php

namespace App\Domains\Tenancy\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
        };
    }
}
