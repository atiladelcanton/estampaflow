<?php

namespace App\Http\Middleware;

use App\Domains\Tenancy\Exceptions\TenantAuthorizationException;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTenantOwner
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        try {
            $membership = $this->memberships->assertOwner(
                $user,
                (string) $this->tenantContext->currentId(),
            );
        } catch (TenantAuthorizationException) {
            abort(403, 'Somente o proprietário pode acessar esta área.');
        }

        $request->attributes->set('tenantMembership', $membership);

        return $next($request);
    }
}
