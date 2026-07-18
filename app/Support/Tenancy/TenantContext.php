<?php

namespace App\Support\Tenancy;

use Closure;

interface TenantContext
{
    public function currentId(): TenantId;

    public function hasTenant(): bool;

    public function run(TenantId $tenantId, Closure $callback): mixed;
}
