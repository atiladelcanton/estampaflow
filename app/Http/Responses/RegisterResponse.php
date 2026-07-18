<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Auth\AuthenticatedDestinationResolver;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

final readonly class RegisterResponse implements RegisterResponseContract
{
    public function __construct(private AuthenticatedDestinationResolver $destinations) {}

    public function toResponse($request): Response
    {
        /** @var Request $request */
        $destination = (string) $request->session()->pull('registration.tenant_url', '');

        if ($destination === '') {
            $destination = $this->destinations->resolve($request->user()) ?? route('login');
        }

        return redirect()->away($destination);
    }
}
