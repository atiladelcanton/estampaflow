<?php

namespace App\Support\Tenancy;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantIdGenerator;

final class UlidTenantIdGenerator implements TenantIdGenerator
{
    /**
     * @param  list<string>  $domains
     */
    public function generate(array $domains): string
    {
        return (string) Str::ulid();
    }
}
