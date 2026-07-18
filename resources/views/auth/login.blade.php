<x-layouts.guest title="Entrar • EstampaFlow">
    <div class="grid min-h-screen lg:grid-cols-[.92fr_1.08fr]">
        <section class="flex min-h-screen items-center justify-center bg-white px-6 py-12 sm:px-10 lg:px-16">
            <div class="w-full max-w-[430px]">
                <x-brand />

                <div class="mt-14">
                    <span class="status-badge status-info">Acesso EstampaFlow</span>
                    <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-ink-950">Bem-vindo de volta <span aria-hidden="true">👋</span></h1>
                    <p class="mt-3 text-sm leading-6 text-ink-400">Entre e você será direcionado automaticamente para a sua estamparia.</p>
                </div>

                @if (session('status'))
                    <div class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="field-label">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="field-input" placeholder="voce@empresa.com.br">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="text-xs font-bold text-ink-700">Senha</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-bold text-brand-600 transition hover:text-brand-700">Esqueci minha senha</a>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="field-input" placeholder="••••••••">
                        @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <label class="flex items-center gap-3 text-xs font-medium text-ink-500">
                        <input type="checkbox" name="remember" class="size-4 rounded border-line text-brand-500 focus:ring-brand-300">
                        Manter conectado neste dispositivo
                    </label>

                    <button class="button-primary w-full py-3">Entrar</button>
                </form>

                <p class="mt-7 text-center text-xs text-ink-400">
                    Ainda não possui acesso?
                    <a href="{{ route('register') }}" class="font-bold text-brand-600">Criar uma conta</a>
                </p>

                <div class="mt-10 rounded-2xl bg-surface-mint p-4 text-xs leading-5 text-ink-500">
                    <strong class="text-ink-700">Acesso local:</strong> admin@delka.local / password
                </div>
            </div>
        </section>

        <section class="auth-panel-pattern relative hidden min-h-screen overflow-hidden border-l border-line p-12 lg:flex lg:items-center lg:justify-center">
            <div class="absolute left-12 top-10"><x-brand light /></div>

            <div class="relative w-full max-w-[700px]">
                <div class="absolute -left-4 top-10 z-10 rounded-2xl bg-ink-950 px-5 py-4 text-white shadow-2xl">
                    <p class="text-xs font-semibold text-white/60">Gestão centralizada</p>
                    <p class="mt-1 text-base font-bold">Da arte à entrega</p>
                </div>

                <div class="rotate-[2deg] rounded-[28px] border border-white/80 bg-white/90 p-4 shadow-[0_30px_90px_rgba(62,36,121,.18)] backdrop-blur">
                    <div class="rounded-[22px] border border-line bg-white p-6">
                        <div class="flex items-center justify-between">
                            <x-brand />
                            <div class="flex gap-2"><span class="size-2 rounded-full bg-brand-300"></span><span class="size-2 rounded-full bg-surface-blue"></span></div>
                        </div>
                        <div class="mt-6 rounded-2xl bg-app px-4 py-3 text-xs text-ink-300">Buscar clientes, produtos e ordens...</div>
                        <div class="mt-6 grid grid-cols-4 gap-3">
                            @foreach ([['file','Orçamentos'],['shirt','Produtos'],['factory','Produção'],['palette','Artes'],['users','Clientes'],['box','Estoque'],['layers','Serviços'],['chart','Relatórios']] as [$icon,$label])
                                <div class="rounded-2xl bg-app p-3 text-center">
                                    <div class="mx-auto grid size-10 place-items-center rounded-xl bg-white text-brand-600 shadow-sm"><x-icon :name="$icon" class="size-5" /></div>
                                    <p class="mt-2 truncate text-[9px] font-bold text-ink-600">{{ $label }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-brand-500 p-4 text-white"><p class="text-[9px] text-white/70">Em produção</p><p class="mt-2 text-2xl font-extrabold">12</p></div>
                            <div class="rounded-2xl bg-surface-mint p-4"><p class="text-[9px] text-ink-400">Artes pendentes</p><p class="mt-2 text-2xl font-extrabold">04</p></div>
                            <div class="rounded-2xl bg-surface-blue/60 p-4"><p class="text-[9px] text-ink-400">Entregas hoje</p><p class="mt-2 text-2xl font-extrabold">07</p></div>
                        </div>
                    </div>
                </div>
                <div class="mx-auto h-8 w-2/3 rounded-b-[100%] bg-brand-300/25 blur-xl"></div>
            </div>
        </section>
    </div>
</x-layouts.guest>
