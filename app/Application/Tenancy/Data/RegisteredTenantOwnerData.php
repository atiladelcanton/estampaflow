<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Data;

use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;

final readonly class RegisteredTenantOwnerData
{
    public function __construct(
        public User $user,
        public Tenant $tenant,
    ) {}
}
