<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Models\User;
use App\Support\Tenancy\TenantUrlGenerator;

final readonly class AuthenticatedDestinationResolver
{
    public function __construct(private TenantUrlGenerator $urls) {}

    public function resolve(User $user): ?string
    {
        if ($user->is_platform_admin) {
            return route('platform.dashboard');
        }

        $membership = $user->memberships()
            ->with('tenant.domains')
            ->where('status', MembershipStatus::ACTIVE->value)
            ->orderByDesc('joined_at')
            ->first();

        return $membership === null
            ? null
            : $this->urls->for($membership->tenant);
    }
}
