<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

final class UlidTenantIdGenerator implements UniqueIdentifierGenerator
{
    public static function generate(mixed $resource): string
    {
        return (string) Str::ulid();
    }
}
