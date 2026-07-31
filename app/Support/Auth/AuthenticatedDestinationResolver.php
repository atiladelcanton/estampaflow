<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Domains\Onboarding\Services\OnboardingProgressService;
use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Models\User;
use App\Support\Tenancy\TenantUrlGenerator;

final readonly class AuthenticatedDestinationResolver
{
    public function __construct(
        private TenantUrlGenerator $urls,
        private OnboardingProgressService $progress,
        private OnboardingRegistry $registry,
    ) {}

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

        if ($membership === null) {
            return null;
        }

        $path = '/dashboard';

        if ($membership->isOwner()) {
            $wizard = $this->registry->wizard();
            $acknowledged = $this->progress->isAcknowledged(
                $user,
                (string) $membership->tenant_id,
                (string) ($wizard['key'] ?? 'owner-first-steps'),
                (int) ($wizard['version'] ?? 1),
            );

            if (! $acknowledged) {
                $path = '/primeiros-passos';
            }
        }

        return $this->urls->for($membership->tenant, $path);
    }
}
