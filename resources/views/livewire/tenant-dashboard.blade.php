<div class="page-shell">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="status-badge status-info">Sprint 1</span>
                <span class="status-badge status-success">{{ $tenant->status->label() }}</span>
            </div>
            <h1 class="page-title mt-4">{{ $tenant->name }}</h1>
            <p class="page-description">O tenant foi resolvido pelo domínio <strong class="text-ink-700">{{ request()->getHost() }}</strong> e o acesso foi validado pela membership ativa.</p>
        </div>

        @if($currentMembership?->isOwner())
            <a href="{{ route('tenant.users') }}" class="button-primary">
                <x-icon name="users" class="size-4" />
                Gerenciar equipe
            </a>
        @endif
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <span class="metric-label">Usuários ativos</span>
            <p class="metric-value">{{ $membersCount }}</p>
            <p class="metric-help">Memberships com acesso</p>
        </div>
        <div class="metric-card">
            <span class="metric-label">Convites pendentes</span>
            <p class="metric-value">{{ $pendingInvitations }}</p>
            <p class="metric-help">Expiram automaticamente</p>
        </div>
        <div class="metric-card">
            <span class="metric-label">Seu papel</span>
            <p class="mt-3 text-2xl font-extrabold">{{ $currentMembership?->role->label() }}</p>
            <p class="metric-help">Escopo deste ambiente</p>
        </div>
        <div class="metric-card border-brand-200 bg-brand-500 text-white">
            <span class="text-xs font-semibold text-white/70">Trial</span>
            <p class="mt-3 text-2xl font-extrabold">{{ $tenant->trial_ends_at?->diffForHumans() }}</p>
            <p class="mt-1 text-[11px] text-white/70">Billing completo entra na Sprint 9</p>
        </div>
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <div class="surface-card overflow-hidden">
            <div class="surface-card-header">
                <div>
                    <h2 class="text-sm font-extrabold">Fundação do tenant</h2>
                    <p class="mt-1 text-xs text-ink-400">Recursos implementados nesta sprint</p>
                </div>
                <span class="status-badge status-success">Operacional</span>
            </div>

            <div class="divide-y divide-line">
                @foreach([
                    ['shield', 'Resolução por domínio', 'O host identifica o tenant antes da rota operacional.'],
                    ['users', 'Membership ativa', 'O usuário precisa estar ativo neste ambiente.'],
                    ['database', 'Single database', 'Os próximos módulos usarão tenant_id e escopo fail closed.'],
                    ['file', 'Auditoria', 'Convites, papéis e propriedade deixam histórico.'],
                ] as [$icon, $title, $description])
                    <div class="flex gap-4 px-5 py-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon :name="$icon" class="size-[18px]" /></span>
                        <div>
                            <p class="text-sm font-bold text-ink-800">{{ $title }}</p>
                            <p class="mt-1 text-xs leading-5 text-ink-400">{{ $description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="surface-card p-5">
            <p class="text-xs font-bold uppercase tracking-[.15em] text-brand-600">Próxima sprint</p>
            <h2 class="mt-3 text-xl font-extrabold">Catálogo dinâmico</h2>
            <p class="mt-2 text-sm leading-6 text-ink-400">ServiceType, versões de schema e parâmetros configuráveis entram na Sprint 2.</p>

            <div class="mt-6 rounded-2xl bg-surface-mint p-4">
                <p class="text-xs font-bold text-ink-700">Contexto atual</p>
                <dl class="mt-3 space-y-3 text-xs">
                    <div class="flex justify-between gap-4"><dt class="text-ink-400">Tenant ID</dt><dd class="truncate font-mono font-semibold text-ink-700">{{ $tenant->getTenantKey() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-400">Timezone</dt><dd class="font-semibold text-ink-700">{{ $tenant->timezone }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-400">Slug</dt><dd class="font-semibold text-ink-700">{{ $tenant->slug }}</dd></div>
                </dl>
            </div>
        </aside>
    </section>
</div>
