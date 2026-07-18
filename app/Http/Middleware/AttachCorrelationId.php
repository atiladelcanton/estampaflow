<?php

namespace App\Http\Middleware;

use App\Support\Correlation\CorrelationContext;
use App\Support\Correlation\CorrelationId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final readonly class AttachCorrelationId
{
    public function __construct(private CorrelationContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->header('X-Correlation-ID'));
        $correlationId = new CorrelationId($incoming !== '' ? $incoming : (string) CorrelationId::generate());

        $this->context->set($correlationId);
        Log::withContext(['correlation_id' => (string) $correlationId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', (string) $correlationId);

        return $response;
    }
}
