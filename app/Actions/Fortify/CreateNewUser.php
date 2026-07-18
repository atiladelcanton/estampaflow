<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Application\Tenancy\Actions\RegisterTenantOwnerAction;
use App\Application\Tenancy\Data\RegisterTenantOwnerData;
use App\Models\User;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly RegisterTenantOwnerAction $registerTenantOwner,
        private readonly TenantUrlGenerator $urls,
    ) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $registration = $this->registerTenantOwner->execute(new RegisterTenantOwnerData(
            ownerName: $input['name'],
            businessName: $input['business_name'],
            email: $input['email'],
            password: $input['password'],
        ));

        session()->put('registration.tenant_url', $this->urls->for($registration->tenant));

        return $registration->user;
    }
}
