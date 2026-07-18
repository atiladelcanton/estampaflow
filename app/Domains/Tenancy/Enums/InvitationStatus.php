<?php

namespace App\Domains\Tenancy\Enums;

enum InvitationStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case EXPIRED = 'EXPIRED';
    case REVOKED = 'REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::ACCEPTED => 'Aceito',
            self::EXPIRED => 'Expirado',
            self::REVOKED => 'Revogado',
        };
    }
}
