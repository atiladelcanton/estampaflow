<?php

namespace App\Domains\Tenancy\Enums;

enum MembershipStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case REVOKED = 'REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
            self::REVOKED => 'Revogado',
        };
    }
}
