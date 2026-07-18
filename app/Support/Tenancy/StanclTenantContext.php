<?php

namespace App\Support\Tenancy;

use App\Domains\Tenancy\Models\Tenant;
use Closure;

final class StanclTenantContext implements TenantContext
{
    public function currentId(): TenantId
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            throw MissingTenantContextException::create();
        }

        return new TenantId((string) $tenant->getTenantKey());
    }

    public function hasTenant(): bool
    {
        return tenant() instanceof Tenant;
    }

    public function run(TenantId $tenantId, Closure $callback): mixed
    {
        $previous = tenant();
        $target = Tenant::query()->findOrFail((string) $tenantId);

        tenancy()->initialize($target);

        try {
            return $callback();
        } finally {
            tenancy()->end();

            if ($previous instanceof Tenant) {
                tenancy()->initialize($previous);
            }
        }
    }
}
