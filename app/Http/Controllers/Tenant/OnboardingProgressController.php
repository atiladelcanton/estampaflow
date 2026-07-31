<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Domains\Onboarding\Services\OnboardingProgressService;
use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class OnboardingProgressController
{
    public function __construct(
        private TenantContext $tenantContext,
        private OnboardingProgressService $progress,
        private OnboardingRegistry $registry,
        private AuditLogger $auditLogger,
    ) {}

    public function complete(Request $request, string $tutorialKey): JsonResponse
    {
        return $this->store($request, $tutorialKey, true);
    }

    public function dismiss(Request $request, string $tutorialKey): JsonResponse
    {
        return $this->store($request, $tutorialKey, false);
    }

    private function store(Request $request, string $tutorialKey, bool $completed): JsonResponse
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $membership = $user->activeMembershipFor($tenantId);
        abort_if($membership === null, 403);

        $tutorial = $this->registry->tutorial($tutorialKey, $membership->role);
        abort_if($tutorial === null, 404);

        $version = (int) ($tutorial['version'] ?? 1);

        if ($completed) {
            $this->progress->complete($user, $tenantId, $tutorialKey, $version);
        } else {
            $this->progress->dismiss($user, $tenantId, $tutorialKey, $version);
        }

        $this->auditLogger->record(new AuditEntryData(
            action: $completed ? 'onboarding.tutorial.completed' : 'onboarding.tutorial.dismissed',
            tenantId: $tenantId,
            actorId: (string) $user->getKey(),
            auditableType: Tenant::class,
            auditableId: $tenantId,
            after: [
                'tutorial_key' => $tutorialKey,
                'version' => $version,
            ],
        ));

        return response()->json(['ok' => true]);
    }
}
