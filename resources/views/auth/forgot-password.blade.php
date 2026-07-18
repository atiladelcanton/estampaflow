<x-layouts.guest title="Recuperar senha • Delka">
    <div class="grid min-h-screen place-items-center bg-app px-6 py-12">
        <div class="w-full max-w-md rounded-[28px] border border-line bg-white p-8 shadow-card">
            <x-brand />
            <div class="mt-10 grid size-12 place-items-center rounded-2xl bg-brand-100 text-brand-700"><x-icon name="shield" /></div>
            <h1 class="mt-5 text-3xl font-extrabold">Recuperar senha</h1>
            <p class="mt-3 text-sm leading-6 text-ink-400">Informe seu e-mail. No ambiente local, o link é registrado em <code class="rounded bg-app px-1 py-0.5 text-ink-600">storage/logs/laravel.log</code>.</p>
            @if (session('status'))<div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>@endif
            <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
                @csrf
                <div><label class="field-label" for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field-input" placeholder="voce@empresa.com.br">@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
                <button class="button-primary w-full">Enviar link de recuperação</button>
                <a href="{{ route('login') }}" class="button-ghost w-full">Voltar ao login</a>
            </form>
        </div>
    </div>
</x-layouts.guest>
