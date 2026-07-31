<?php

declare(strict_types=1);

namespace App\Domains\Onboarding\Services;

use App\Domains\Onboarding\Models\UserOnboardingProgress;
use App\Models\User;

final class OnboardingProgressService
{
    public function isAcknowledged(User $user, string $tenantId, string $key, int $version): bool
    {
        return UserOnboardingProgress::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getKey())
            ->where('tutorial_key', $key)
            ->where('version', $version)
            ->where(function ($query): void {
                $query->whereNotNull('completed_at')->orWhereNotNull('dismissed_at');
            })
            ->exists();
    }

    public function complete(User $user, string $tenantId, string $key, int $version): UserOnboardingProgress
    {
        $progress = $this->findOrCreate($user, $tenantId, $key, $version);
        $progress->forceFill([
            'completed_at' => now(),
            'dismissed_at' => null,
        ])->save();

        return $progress->refresh();
    }

    public function dismiss(User $user, string $tenantId, string $key, int $version): UserOnboardingProgress
    {
        $progress = $this->findOrCreate($user, $tenantId, $key, $version);
        $progress->forceFill([
            'completed_at' => null,
            'dismissed_at' => now(),
        ])->save();

        return $progress->refresh();
    }

    public function reset(User $user, string $tenantId, string $key, int $version): void
    {
        UserOnboardingProgress::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getKey())
            ->where('tutorial_key', $key)
            ->where('version', $version)
            ->delete();
    }

    private function findOrCreate(User $user, string $tenantId, string $key, int $version): UserOnboardingProgress
    {
        return UserOnboardingProgress::query()->firstOrCreate([
            'tenant_id' => $tenantId,
            'user_id' => (string) $user->getKey(),
            'tutorial_key' => $key,
            'version' => $version,
        ]);
    }
}
