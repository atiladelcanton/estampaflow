<?php

namespace App\Domains\Tenancy\Enums;

enum TenantRole: string
{
    case OWNER = 'OWNER';
    case USER = 'USER';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Proprietário',
            self::USER => 'Usuário',
        };
    }
}
