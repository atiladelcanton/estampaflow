<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

final readonly class InitializeTenancyForRequest
{
    public function __construct(
        private InitializeTenancyByDomain $initializer,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Garante que testes, workers persistentes e requisições subsequentes
        // nunca reutilizem acidentalmente o tenant de uma execução anterior.
        if (tenant() !== null) {
            tenancy()->end();
        }

        $host = mb_strtolower($request->getHost());
        $centralDomains = array_map(
            static fn (string $domain): string => mb_strtolower(trim($domain)),
            config('tenancy.central_domains', []),
        );

        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        return $this->initializer->handle($request, $next);
    }
}
