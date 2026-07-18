<?php

namespace App\Support\Tenancy;

use RuntimeException;

final class MissingTenantContextException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Operação tenant-aware executada sem tenant inicializado.');
    }
}
