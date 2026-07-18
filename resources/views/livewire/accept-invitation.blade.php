<div class="page-shell">
    <div class="mx-auto max-w-2xl">
        <div class="surface-card overflow-hidden">
            <div class="auth-panel-pattern border-b border-line p-8 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                    <x-icon name="users" class="size-6" />
                </div>
                <p class="mt-5 text-xs font-bold uppercase tracking-[.18em] text-brand-600">Convite de acesso</p>
            </div>

            <div class="p-6 text-center sm:p-8">
                @if($invitation)
                    <h1 class="text-2xl font-extrabold">Entrar em {{ $invitation->tenant->name }}</h1>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink-400">
                        O convite foi enviado para <strong class="text-ink-700">{{ $invitation->email }}</strong> com o papel de {{ $invitation->role->label() }}.
                    </p>

                    @error('invitation')
                        <div class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">{{ $message }}</div>
                    @enderror

                    <div class="mt-7 rounded-2xl bg-app p-4 text-left">
                        <div class="flex justify-between gap-4 text-xs">
                            <span class="text-ink-400">Usuário conectado</span>
                            <strong class="text-ink-700">{{ auth()->user()->email }}</strong>
                        </div>
                        <div class="mt-3 flex justify-between gap-4 text-xs">
                            <span class="text-ink-400">Expiração</span>
                            <strong class="text-ink-700">{{ $invitation->expires_at->translatedFormat('d/m/Y H:i') }}</strong>
                        </div>
                    </div>

                    <button wire:click="accept" class="button-primary mt-7 w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove>Aceitar convite</span>
                        <span wire:loading>Processando...</span>
                    </button>
                @else
                    <h1 class="text-2xl font-extrabold">Convite não encontrado</h1>
                    <p class="mt-3 text-sm text-ink-400">O link pode estar incorreto ou já ter sido removido.</p>
                    <a href="{{ route('central.dashboard') }}" class="button-primary mt-7">Voltar aos ambientes</a>
                @endif
            </div>
        </div>
    </div>
</div>
