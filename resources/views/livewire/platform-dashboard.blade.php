<div class="page-shell">
    <div>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Administração da plataforma</p>
        <h1 class="page-title mt-2">EstampaFlow</h1>
        <p class="page-description">Visão central exclusiva do Platform Admin para acompanhar clientes SaaS, acessos e evolução da plataforma.</p>
    </div>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="metric-card"><p class="metric-label">Estamparias</p><p class="metric-value">{{ $tenantCount }}</p></div>
        <div class="metric-card"><p class="metric-label">Trials ativos</p><p class="metric-value">{{ $trialCount }}</p></div>
        <div class="metric-card"><p class="metric-label">Usuários globais</p><p class="metric-value">{{ $userCount }}</p></div>
    </section>

    <section class="mt-8 table-shell overflow-x-auto">
        <div class="surface-card-header">
            <div><h2 class="text-sm font-extrabold">Clientes da plataforma</h2><p class="mt-1 text-xs text-ink-400">Cobranças e suporte serão aprofundados na Sprint 9.</p></div>
        </div>
        <table class="data-table">
            <thead><tr><th>Estamparia</th><th>Domínio</th><th>Provisionamento</th><th>Membros</th><th>Trial</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr>
                        <td><p class="font-bold text-ink-800">{{ $tenant->name }}</p><p class="mt-1 text-[11px] text-ink-400">{{ $tenant->slug }}</p></td>
                        <td class="text-xs text-ink-500">{{ $tenant->primaryDomain() ?? '—' }}</td>
                        <td>
                            @php($domain = $tenant->domains->first())
                            <span class="status-badge {{ $domain?->isProvisioned() ? 'status-success' : 'status-warning' }}">
                                {{ $domain?->provisioning_status->label() ?? '—' }}
                            </span>
                        </td>
                        <td>{{ $tenant->memberships_count }}</td>
                        <td class="text-xs text-ink-500">{{ $tenant->trial_ends_at?->translatedFormat('d/m/Y') ?? '—' }}</td>
                        <td><span class="status-badge status-success">{{ $tenant->status->value }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-sm text-ink-400">Nenhuma estamparia cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
