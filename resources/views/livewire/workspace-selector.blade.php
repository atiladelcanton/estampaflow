<div class="page-shell">
    <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Conta global</p>
            <h1 class="page-title mt-2">Seus ambientes</h1>
            <p class="page-description">Escolha uma estamparia para entrar. Cada ambiente possui domínio, usuários e dados isolados.</p>
        </div>
        <a href="{{ route('central.onboarding') }}" class="button-primary">
            <x-icon name="plus" class="size-4" />
            Criar estamparia
        </a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($memberships as $item)
            <a href="{{ $item['url'] }}" class="group surface-card relative overflow-hidden p-6 transition duration-200 hover:-translate-y-1 hover:border-brand-300 hover:shadow-card">
                <div class="absolute -right-8 -top-8 size-28 rounded-full bg-brand-100/70 transition group-hover:scale-125"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="grid size-12 place-items-center rounded-2xl bg-brand-500 text-lg font-extrabold text-white">
                        {{ str($item['tenant']->name)->substr(0, 2)->upper() }}
                    </div>
                    <span class="status-badge status-success">Ativo</span>
                </div>
                <div class="relative mt-8">
                    <h2 class="text-lg font-extrabold text-ink-900">{{ $item['tenant']->name }}</h2>
                    <p class="mt-1 text-xs text-ink-400">{{ $item['tenant']->primaryDomain() }}</p>
                    <div class="mt-5 flex items-center justify-between border-t border-line pt-4">
                        <span class="status-badge status-info">{{ $item['membership']->role->label() }}</span>
                        <span class="inline-flex items-center gap-2 text-xs font-bold text-brand-600">
                            Entrar <x-icon name="arrow-right" class="size-4" />
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="surface-card col-span-full px-6 py-14 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                    <x-icon name="factory" class="size-6" />
                </div>
                <h2 class="mt-5 text-lg font-extrabold">Crie seu primeiro ambiente</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-400">Sua identidade já existe, mas ainda não está vinculada a uma estamparia.</p>
                <a href="{{ route('central.onboarding') }}" class="button-primary mt-6">Começar agora</a>
            </div>
        @endforelse
    </div>

    <section class="mt-8 grid gap-4 lg:grid-cols-3">
        <div class="metric-card">
            <span class="metric-label">Identidade</span>
            <p class="mt-3 text-lg font-extrabold">{{ auth()->user()->name }}</p>
            <p class="metric-help">{{ auth()->user()->email }}</p>
        </div>
        <div class="metric-card">
            <span class="metric-label">Ambientes ativos</span>
            <p class="metric-value">{{ $memberships->count() }}</p>
            <p class="metric-help">Memberships liberadas</p>
        </div>
        <div class="metric-card border-brand-200 bg-brand-50">
            <span class="metric-label text-brand-700">Sprint 1</span>
            <p class="mt-3 text-lg font-extrabold text-brand-800">Tenancy operacional</p>
            <p class="metric-help text-brand-700/70">Resolução por domínio e acesso fail closed</p>
        </div>
    </section>
</div>
