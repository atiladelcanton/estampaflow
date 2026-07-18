<?php

namespace App\Http\Middleware;

use App\Domains\Tenancy\Exceptions\TenantAuthorizationException;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureActiveTenantMembership
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
            $membership = $this->memberships->assertActiveMember(
                $user,
                (string) $this->tenantContext->currentId(),
            );
        } catch (TenantAuthorizationException) {
            abort(403, 'Você não possui acesso ativo a esta estamparia.');
        }

        $request->attributes->set('tenantMembership', $membership);

        return $next($request);
    }
}
