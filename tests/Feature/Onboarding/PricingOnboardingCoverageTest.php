<?php

declare(strict_types=1);

use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\Tenancy\Enums\TenantRole;

it('inclui precificação guiada no tutorial contextual e na Central de Ajuda', function (): void {
    $registry = app(OnboardingRegistry::class);
    $overview = $registry->tutorialForRoute('tenant.pricing.index', TenantRole::OWNER);
    $editor = $registry->tutorialForRoute('tenant.pricing.edit', TenantRole::OWNER);
    $articles = $registry->articlesFor(TenantRole::OWNER);
    $wizard = file_get_contents(resource_path('views/tenant/onboarding/wizard.blade.php'));

    expect($overview)->not->toBeNull()
        ->and($overview['key'] ?? null)->toBe('pricing-overview')
        ->and($editor)->not->toBeNull()
        ->and($editor['version'] ?? null)->toBe(2)
        ->and($editor['steps'][0]['target'] ?? null)->toBe('[data-tour="pricing-guided-steps"]')
        ->and($editor['steps'][1]['target'] ?? null)->toBe('[data-tour="pricing-preview"]')
        ->and($articles)->toHaveKey('configurar-precos')
        ->and($articles)->toHaveKey('entendendo-variacoes-de-preco')
        ->and($articles['configurar-precos']['steps'] ?? [])->toContain('No DTF, informe preço do metro, largura útil e aplicação.')
        ->and($articles['configurar-precos']['steps'] ?? [])->toContain('Na Sublimação, use quantidade e tipo de aplicação.')
        ->and($articles['configurar-precos']['steps'] ?? [])->toContain('No Bordado, use quantidade, faixa de pontos e matriz opcional.')
        ->and($wizard)->toContain('Configure preços');
});
