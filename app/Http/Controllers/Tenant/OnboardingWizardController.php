<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Tenancy\Actions\InviteTenantUserAction;
use App\Domains\Onboarding\Services\OnboardingProgressService;
use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final readonly class OnboardingWizardController
{
    public function __construct(
        private TenantContext $tenantContext,
        private OnboardingProgressService $progress,
        private OnboardingRegistry $registry,
        private InviteTenantUserAction $inviteTenantUser,
        private AuditLogger $auditLogger,
    ) {}

    public function show(Request $request): View
    {
        $tenant = $this->tenant();
        $services = ServiceType::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('tenant.onboarding.wizard', [
            'tenant' => $tenant,
            'services' => $services,
            'timezones' => [
                'America/Sao_Paulo' => 'Brasília, São Paulo e Curitiba',
                'America/Manaus' => 'Manaus',
                'America/Cuiaba' => 'Cuiabá',
                'America/Rio_Branco' => 'Rio Branco',
                'America/Noronha' => 'Fernando de Noronha',
            ],
            'reviewing' => $request->boolean('rever'),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $user = $this->user($request);

        /** @var list<string> $availableServiceIds */
        $availableServiceIds = ServiceType::query()
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'min:2', 'max:120'],
            'timezone' => ['required', 'string', Rule::in([
                'America/Sao_Paulo',
                'America/Manaus',
                'America/Cuiaba',
                'America/Rio_Branco',
                'America/Noronha',
            ])],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', Rule::in($availableServiceIds)],
            'invitation_email' => ['nullable', 'email:rfc', 'max:255'],
        ], [
            'business_name.required' => 'Informe o nome da estamparia.',
            'services.required' => 'Selecione pelo menos um serviço utilizado pela estamparia.',
            'invitation_email.email' => 'Informe um e-mail válido para o convite.',
        ]);

        /** @var list<mixed> $requestedServices */
        $requestedServices = is_array($validated['services']) ? array_values($validated['services']) : [];
        /** @var list<string> $selectedServices */
        $selectedServices = array_values(array_intersect(
            $availableServiceIds,
            array_map(static fn (mixed $id): string => (string) $id, $requestedServices),
        ));

        DB::transaction(function () use ($tenant, $validated, $selectedServices): void {
            $tenant->forceFill([
                'name' => trim((string) $validated['business_name']),
                'timezone' => (string) $validated['timezone'],
            ])->save();

            ServiceType::query()->update(['active' => false]);

            if ($selectedServices !== []) {
                ServiceType::query()->whereKey($selectedServices)->update(['active' => true]);
            }
        });

        $invitationEmail = trim((string) ($validated['invitation_email'] ?? ''));

        if ($invitationEmail !== '') {
            $this->inviteTenantUser->execute($tenant, $user, $invitationEmail, TenantRole::USER);
        }

        $wizard = $this->registry->wizard();
        $key = (string) ($wizard['key'] ?? 'owner-first-steps');
        $version = (int) ($wizard['version'] ?? 1);

        $this->progress->complete($user, (string) $tenant->getTenantKey(), $key, $version);
        $this->auditLogger->record(new AuditEntryData(
            action: 'onboarding.wizard.completed',
            tenantId: (string) $tenant->getTenantKey(),
            actorId: (string) $user->getKey(),
            auditableType: Tenant::class,
            auditableId: (string) $tenant->getTenantKey(),
            after: [
                'wizard_key' => $key,
                'version' => $version,
                'active_services' => count($selectedServices),
                'invitation_requested' => $invitationEmail !== '',
            ],
        ));

        return redirect()->route('tenant.dashboard')->with(
            'success',
            'Configuração inicial concluída. Sua estamparia está pronta para continuar.',
        );
    }

    public function skip(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $user = $this->user($request);
        $wizard = $this->registry->wizard();
        $key = (string) ($wizard['key'] ?? 'owner-first-steps');
        $version = (int) ($wizard['version'] ?? 1);

        $this->progress->dismiss($user, (string) $tenant->getTenantKey(), $key, $version);
        $this->auditLogger->record(new AuditEntryData(
            action: 'onboarding.wizard.dismissed',
            tenantId: (string) $tenant->getTenantKey(),
            actorId: (string) $user->getKey(),
            auditableType: Tenant::class,
            auditableId: (string) $tenant->getTenantKey(),
            after: ['wizard_key' => $key, 'version' => $version],
        ));

        return redirect()->route('tenant.dashboard')->with(
            'success',
            'Você pode retomar os primeiros passos pela Central de Ajuda.',
        );
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->findOrFail((string) $this->tenantContext->currentId());
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
