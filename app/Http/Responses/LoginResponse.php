<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Auth\AuthenticatedDestinationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

final readonly class LoginResponse implements LoginResponseContract
{
    public function __construct(private AuthenticatedDestinationResolver $destinations) {}

    public function toResponse($request): Response
    {
        /** @var Request $request */
        $intended = (string) $request->session()->pull('url.intended', '');

        if ($intended !== '' && str_contains((string) parse_url($intended, PHP_URL_PATH), '/convites/')) {
            return redirect()->to($intended);
        }

        $destination = $this->destinations->resolve($request->user());

        if ($destination !== null) {
            return redirect()->away($destination);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Sua conta ainda não está vinculada a uma estamparia. Solicite um novo convite ao responsável.',
        ]);
    }
}
