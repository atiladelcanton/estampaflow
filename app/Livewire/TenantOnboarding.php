<?php

namespace App\Livewire;

use App\Application\Tenancy\Actions\CreateTenantAction;
use App\Application\Tenancy\Data\CreateTenantData;
use App\Domains\Tenancy\Models\Tenant;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class TenantOnboarding extends Component
{
    public string $name = '';

    public string $slug = '';

    public bool $slugWasEdited = false;

    public function updatedName(string $value): void
    {
        if (! $this->slugWasEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slugWasEdited = true;
        $this->slug = Str::slug($value);
    }

    public function create(CreateTenantAction $action, TenantUrlGenerator $urls): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => [
                'required',
                'alpha_dash:ascii',
                'min:2',
                'max:60',
                Rule::unique(Tenant::class, 'slug'),
            ],
        ]);

        $domain = $validated['slug'].'.'.config('tenancy.tenant_base_domain');

        $tenant = $action->execute(new CreateTenantData(
            name: $validated['name'],
            slug: $validated['slug'],
            domain: $domain,
            owner: auth()->user(),
        ));

        if (app()->environment('local')) {
            session()->flash(
                'success',
                "Ambiente criado. Para abrir localmente, rode: make add-host DOMAIN={$domain}",
            );

            return redirect()->route('central.dashboard');
        }

        return redirect()->away($urls->for($tenant));
    }

    public function render(): View
    {
        return view('livewire.tenant-onboarding', [
            'domainSuffix' => '.'.config('tenancy.tenant_base_domain'),
        ]);
    }
}
