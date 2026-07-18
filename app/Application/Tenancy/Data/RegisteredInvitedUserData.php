<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Data;

use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;

final readonly class RegisteredInvitedUserData
{
    public function __construct(
        public User $user,
        public TenantMembership $membership,
    ) {}
}
