<div class="page-shell">
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('central.dashboard') }}" class="inline-flex items-center gap-2 text-xs font-bold text-ink-400 transition hover:text-brand-600">
            ← Voltar aos ambientes
        </a>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_.72fr]">
            <section class="surface-card p-6 sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Onboarding</p>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight">Crie sua estamparia</h1>
                <p class="mt-2 text-sm leading-6 text-ink-400">Você será o proprietário inicial e poderá convidar sua equipe depois.</p>

                <form wire:submit="create" class="mt-8 space-y-5">
                    <div>
                        <label class="field-label" for="name">Nome da empresa</label>
                        <input wire:model.live.debounce.350ms="name" id="name" class="field-input" placeholder="Ex.: Estamparia Alpha" autofocus>
                        @error('name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="field-label" for="slug">Endereço do ambiente</label>
                        <div class="flex overflow-hidden rounded-xl border border-line bg-white shadow-sm focus-within:border-brand-400 focus-within:ring-4 focus-within:ring-brand-100">
                            <input wire:model.blur="slug" id="slug" class="min-w-0 flex-1 border-0 px-4 py-3 text-sm focus:ring-0" placeholder="minha-estamparia">
                            <span class="flex items-center border-l border-line bg-app px-3 text-xs font-semibold text-ink-400">{{ $domainSuffix }}</span>
                        </div>
                        @error('slug')<span class="field-error">{{ $message }}</span>@enderror
                        <p class="field-help">Esse endereço identifica o tenant. Ele poderá receber domínio próprio em uma evolução futura.</p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ route('central.dashboard') }}" class="button-secondary">Cancelar</a>
                        <button class="button-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Criar ambiente</span>
                            <span wire:loading>Criando...</span>
                        </button>
                    </div>
                </form>
            </section>

            <aside class="rounded-3xl bg-ink-950 p-6 text-white shadow-card sm:p-8">
                <span class="status-badge bg-white/10 text-white">7 dias de trial</span>
                <h2 class="mt-6 text-2xl font-extrabold">Um ambiente isolado para sua operação.</h2>
                <p class="mt-3 text-sm leading-7 text-white/60">O sistema criará o tenant, o domínio e seu vínculo de Owner em uma única transação.</p>

                <div class="mt-8 space-y-3">
                    @foreach([
                        ['shield', 'Isolamento fail closed'],
                        ['users', 'Owner protegido'],
                        ['file', 'Auditoria das alterações'],
                        ['layers', 'Base para serviços dinâmicos'],
                    ] as [$icon, $label])
                        <div class="flex items-center gap-3 rounded-2xl bg-white/5 p-4">
                            <span class="grid size-9 place-items-center rounded-xl bg-brand-500/20 text-brand-300"><x-icon :name="$icon" class="size-4" /></span>
                            <span class="text-sm font-bold">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</div>
