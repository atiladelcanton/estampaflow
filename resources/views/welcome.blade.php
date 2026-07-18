<x-layouts.guest title="EstampaFlow">
    <div class="min-h-screen bg-white">
        <header class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6">
            <x-brand />
            <div class="flex items-center gap-3">
                @auth
                    @php($welcomeDestination = app(\App\Support\Auth\AuthenticatedDestinationResolver::class)->resolve(auth()->user()) ?? route('login'))
                    <a href="{{ $welcomeDestination }}" class="button-primary">Acessar sistema <x-icon name="arrow-right" class="size-4" /></a>
                @else
                    <a href="{{ route('login') }}" class="button-ghost">Entrar</a>
                    <a href="{{ route('register') }}" class="button-primary">Criar conta</a>
                @endauth
            </div>
        </header>

        <section class="mx-auto grid min-h-[calc(100vh-80px)] max-w-7xl items-center gap-14 px-6 py-16 lg:grid-cols-[1fr_.95fr]">
            <div>
                <span class="status-badge status-info">Sprint 1 • Multi-tenant</span>
                <h1 class="mt-6 max-w-3xl text-5xl font-extrabold tracking-[-.04em] text-ink-950 sm:text-6xl">Cada estamparia no seu ambiente. Uma única plataforma para crescer.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-ink-500">Identidade global, ambientes isolados por domínio, equipe com papéis claros e uma base pronta para o catálogo dinâmico.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? (app(\App\Support\Auth\AuthenticatedDestinationResolver::class)->resolve(auth()->user()) ?? route('login')) : route('login') }}" class="button-primary px-6 py-3">Explorar a Sprint 1 <x-icon name="arrow-right" class="size-4" /></a>
                    <a href="/up" class="button-secondary px-6 py-3">Verificar saúde</a>
                </div>
                <div class="mt-12 grid max-w-xl grid-cols-3 gap-4">
                    <div><p class="text-2xl font-extrabold">11</p><p class="mt-1 text-xs text-ink-400">ADRs aceitos</p></div>
                    <div><p class="text-2xl font-extrabold">1:N</p><p class="mt-1 text-xs text-ink-400">Usuário × tenants</p></div>
                    <div><p class="text-2xl font-extrabold">Fail</p><p class="mt-1 text-xs text-ink-400">Closed por padrão</p></div>
                </div>
            </div>

            <div class="auth-panel-pattern rounded-[36px] border border-brand-100 p-6 sm:p-10">
                <div class="rounded-[28px] border border-white bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between"><x-brand /><span class="status-badge status-success">SPRINT 1</span></div>
                    <div class="mt-6 rounded-2xl bg-app px-4 py-3 text-xs text-ink-300">Acesso direto ao seu ambiente...</div>
                    <div class="mt-5 space-y-3">
                        @foreach ([
                            ['EA', 'Estamparia Alpha', 'Owner', 'alpha.estamparia.test'],
                            ['EB', 'Estamparia Beta', 'User', 'beta.estamparia.test'],
                        ] as [$initials, $name, $role, $domain])
                            <div class="flex items-center gap-3 rounded-2xl border border-line p-4">
                                <span class="grid size-11 place-items-center rounded-2xl bg-brand-100 text-xs font-extrabold text-brand-700">{{ $initials }}</span>
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $name }}</p><p class="mt-1 truncate text-[10px] text-ink-400">{{ $domain }}</p></div>
                                <span class="status-badge status-info">{{ $role }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 h-24 rounded-2xl bg-gradient-to-r from-brand-500 to-brand-300 p-4 text-white"><p class="text-xs text-white/70">Fundação técnica</p><p class="mt-2 text-xl font-extrabold">Laravel 13 + Tenancy</p></div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.guest>
