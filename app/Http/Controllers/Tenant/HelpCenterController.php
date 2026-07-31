<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class HelpCenterController
{
    public function __construct(
        private TenantContext $tenantContext,
        private OnboardingRegistry $registry,
    ) {}

    public function index(Request $request): View
    {
        $membership = $this->membership($request);
        $articles = $this->registry->articlesFor($membership->role);

        return view('tenant.help.index', [
            'articles' => $articles,
            'categories' => collect($articles)->groupBy('category'),
        ]);
    }

    public function show(Request $request, string $article): View
    {
        $membership = $this->membership($request);
        $content = $this->registry->article($article, $membership->role);
        abort_if($content === null, 404);

        return view('tenant.help.show', [
            'slug' => $article,
            'article' => $content,
        ]);
    }

    private function membership(Request $request): TenantMembership
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $membership = $user->activeMembershipFor((string) $this->tenantContext->currentId());
        abort_if($membership === null, 403);

        return $membership;
    }
}
