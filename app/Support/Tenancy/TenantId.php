<?php

namespace App\Support\Tenancy;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

final readonly class TenantId implements Stringable
{
    public function __construct(public string $value)
    {
        if (! Str::isUlid($value)) {
            throw new InvalidArgumentException('Tenant ID deve ser um ULID válido.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
