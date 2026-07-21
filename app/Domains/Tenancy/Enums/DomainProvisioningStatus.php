<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Enums;

enum DomainProvisioningStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case PROVISIONED = 'PROVISIONED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::PROVISIONED => 'Provisionado',
            self::FAILED => 'Falhou',
        };
    }
}
