<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Delka Estamparia' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-app text-ink-950 antialiased" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-ink-950/25 backdrop-blur-[2px] lg:hidden" @click="sidebarOpen = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[270px] -translate-x-full flex-col border-r border-line bg-white transition-all duration-200 lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen, 'lg:w-[92px]': sidebarCollapsed }"
    >
        <div class="flex h-[74px] items-center justify-between border-b border-line px-5">
            <x-brand x-show="! sidebarCollapsed" />
            <div x-show="sidebarCollapsed" class="mx-auto"><x-brand compact /></div>
            <button type="button" class="icon-button hidden lg:grid" @click="sidebarCollapsed = ! sidebarCollapsed" aria-label="Recolher menu">
                <x-icon name="menu" class="size-[18px]" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            @if($currentTenant)
                <p x-show="! sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.18em] text-ink-400">Ambiente</p>

                <a href="{{ route('tenant.dashboard') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('tenant.dashboard')]) title="Visão geral">
                    <x-icon name="home" />
                    <span x-show="! sidebarCollapsed">Visão geral</span>
                </a>

                @if($currentMembership?->isOwner())
                    <a href="{{ route('tenant.users') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('tenant.users')]) title="Equipe">
                        <x-icon name="users" />
                        <span x-show="! sidebarCollapsed">Equipe</span>
                        <span x-show="! sidebarCollapsed" class="nav-pill">Sprint 1</span>
                    </a>
                @endif

                @foreach ([
                    ['users', 'Clientes', 'Sprint 5'],
                    ['shirt', 'Produtos', 'Sprint 4'],
                    ['box', 'Estoque', 'Sprint 4'],
                    ['layers', 'Tipos de serviço', 'Sprint 2'],
                    ['calculator', 'Precificação', 'Sprint 3'],
                    ['file', 'Orçamentos', 'Sprint 5'],
                    ['factory', 'Produção', 'Sprint 7'],
                    ['palette', 'Artes', 'Sprint 7'],
                    ['chart', 'Relatórios', 'Sprint 10'],
                ] as [$icon, $label, $sprint])
                    <div class="nav-item nav-item-disabled" title="{{ $label }} — {{ $sprint }}">
                        <x-icon :name="$icon" />
                        <span x-show="! sidebarCollapsed">{{ $label }}</span>
                        <span x-show="! sidebarCollapsed" class="ml-auto text-[9px] font-semibold uppercase tracking-wide text-ink-300">{{ $sprint }}</span>
                    </div>
                @endforeach
            @else
                <p x-show="! sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.18em] text-ink-400">Conta</p>

                <a href="{{ route('central.dashboard') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('central.dashboard')]) title="Ambientes">
                    <x-icon name="grid" />
                    <span x-show="! sidebarCollapsed">Meus ambientes</span>
                </a>
                <a href="{{ route('central.onboarding') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('central.onboarding')]) title="Criar estamparia">
                    <x-icon name="plus" />
                    <span x-show="! sidebarCollapsed">Criar estamparia</span>
                </a>

                <div class="my-4 border-t border-line"></div>
                <p x-show="! sidebarCollapsed" class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.18em] text-ink-400">Fundação visual</p>
                <a href="{{ route('ui.products') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('ui.products*')]) title="Produtos demo">
                    <x-icon name="shirt" />
                    <span x-show="! sidebarCollapsed">Produtos</span>
                    <span x-show="! sidebarCollapsed" class="nav-pill">Demo</span>
                </a>
                <a href="{{ route('ui.style-guide') }}" @class(['nav-item', 'nav-item-active' => request()->routeIs('ui.style-guide')]) title="Guia visual">
                    <x-icon name="palette" />
                    <span x-show="! sidebarCollapsed">Guia visual</span>
                </a>
            @endif
        </nav>

        <div class="border-t border-line p-3">
            <div class="flex items-center gap-3 rounded-2xl bg-surface-mint p-3" :class="{ 'justify-center': sidebarCollapsed }">
                <div class="grid size-9 shrink-0 place-items-center rounded-xl bg-ink-950 text-xs font-bold text-white">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</div>
                <div x-show="! sidebarCollapsed" class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[10px] text-ink-400">{{ $currentTenant?->name ?? 'Conta global' }}</p>
                </div>
                <form x-show="! sidebarCollapsed" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-ink-400 transition hover:text-brand-600" aria-label="Sair"><x-icon name="logout" class="size-4" /></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="min-h-screen transition-all duration-200 lg:pl-[270px]" :class="{ 'lg:pl-[92px]': sidebarCollapsed }">
        <header class="sticky top-0 z-30 flex h-[74px] items-center gap-4 border-b border-line bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <button type="button" class="icon-button lg:hidden" @click="sidebarOpen = true" aria-label="Abrir menu"><x-icon name="menu" /></button>

            @if($currentTenant)
                <a href="{{ route('central.dashboard') }}" class="hidden items-center gap-3 rounded-2xl border border-line bg-white px-3 py-2 shadow-sm transition hover:border-brand-300 md:flex">
                    <span class="grid size-8 place-items-center rounded-xl bg-brand-100 text-xs font-extrabold text-brand-700">{{ str($currentTenant->name)->substr(0, 2)->upper() }}</span>
                    <span>
                        <span class="block max-w-48 truncate text-xs font-bold text-ink-800">{{ $currentTenant->name }}</span>
                        <span class="block text-[10px] text-ink-400">Trocar ambiente</span>
                    </span>
                    <x-icon name="chevron-down" class="size-4 text-ink-300" />
                </a>
            @else
                <div class="hidden max-w-xl flex-1 items-center gap-3 rounded-2xl bg-app px-4 py-3 md:flex">
                    <x-icon name="search" class="size-4 text-ink-400" />
                    <span class="text-xs text-ink-400">Buscar ambientes ou ações...</span>
                    <span class="ml-auto rounded-lg border border-line bg-white px-2 py-1 text-[10px] font-semibold text-ink-400">⌘ K</span>
                </div>
            @endif

            <div class="ml-auto flex items-center gap-2">
                @if(! $currentTenant)
                    <a href="{{ route('ui.style-guide') }}" class="hidden rounded-xl px-3 py-2 text-xs font-semibold text-ink-500 transition hover:bg-app hover:text-ink-950 sm:block">Documentação</a>
                @endif
                <button class="icon-button" aria-label="Notificações"><x-icon name="bell" class="size-[18px]" /><span class="absolute right-2 top-2 size-1.5 rounded-full bg-brand-500 ring-2 ring-white"></span></button>
                <div class="ml-1 flex items-center gap-2 rounded-2xl border border-line bg-white p-1.5 pr-3 shadow-soft">
                    <div class="grid size-8 place-items-center rounded-xl bg-brand-100 text-xs font-extrabold text-brand-700">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</div>
                    <div class="hidden text-left sm:block">
                        <p class="max-w-32 truncate text-xs font-bold">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-ink-400">{{ $currentMembership?->role->label() ?? (auth()->user()->is_platform_admin ? 'Platform Admin' : 'Usuário') }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="min-w-0">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
