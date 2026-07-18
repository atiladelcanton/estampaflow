<div class="auth-panel-pattern flex min-h-screen items-center justify-center px-5 py-10">
    <div class="w-full max-w-2xl overflow-hidden rounded-[28px] border border-line bg-white shadow-card">
        <div class="border-b border-line bg-surface-mint p-8 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20"><x-icon name="users" class="size-6" /></div>
            <p class="mt-5 text-xs font-bold uppercase tracking-[.18em] text-brand-600">Convite de equipe</p>
        </div>

        <div class="p-6 sm:p-8">
            @if($invitation && $available)
                <div class="text-center">
                    <h1 class="text-2xl font-extrabold">Entrar em {{ $invitation->tenant->name }}</h1>
                    <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-ink-400">Convite para <strong class="text-ink-700">{{ $invitation->email }}</strong> como {{ $invitation->role->label() }}.</p>
                </div>

                @error('invitation')<div class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">{{ $message }}</div>@enderror

                @auth
                    @if(mb_strtolower(auth()->user()->email) === $invitation->email_normalized)
                        <div class="mt-7 rounded-2xl bg-app p-4 text-xs"><div class="flex justify-between gap-4"><span class="text-ink-400">Conta conectada</span><strong>{{ auth()->user()->email }}</strong></div></div>
                        <button wire:click="accept" class="button-primary mt-6 w-full" wire:loading.attr="disabled"><span wire:loading.remove>Aceitar e acessar a estamparia</span><span wire:loading>Processando...</span></button>
                    @else
                        <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Você está conectado como <strong>{{ auth()->user()->email }}</strong>, mas o convite pertence a <strong>{{ $invitation->email }}</strong>.</div>
                        <form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf<button class="button-secondary w-full">Sair e entrar com a conta correta</button></form>
                    @endif
                @else
                    @if($existingUser)
                        <div class="mt-7 rounded-2xl bg-app p-5 text-sm leading-6 text-ink-500">Já existe uma conta para este e-mail. Entre com ela e você voltará automaticamente para este convite.</div>
                        <a href="{{ route('login') }}" class="button-primary mt-5 w-full">Entrar para aceitar</a>
                    @else
                        <form wire:submit="registerAndAccept" class="mt-7 space-y-4">
                            <div><label class="field-label">E-mail convidado</label><input value="{{ $invitation->email }}" disabled class="field-input bg-app"></div>
                            <div><label for="name" class="field-label">Seu nome</label><input wire:model="name" id="name" class="field-input" autocomplete="name">@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
                            <div><label for="password" class="field-label">Crie uma senha</label><input wire:model="password" id="password" type="password" class="field-input" autocomplete="new-password">@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
                            <div><label for="password_confirmation" class="field-label">Confirme a senha</label><input wire:model="password_confirmation" id="password_confirmation" type="password" class="field-input" autocomplete="new-password"></div>
                            <button class="button-primary w-full" wire:loading.attr="disabled"><span wire:loading.remove>Criar conta e entrar</span><span wire:loading>Criando acesso...</span></button>
                        </form>
                    @endif
                @endauth
            @else
                <div class="text-center"><h1 class="text-2xl font-extrabold">Convite indisponível</h1><p class="mt-3 text-sm text-ink-400">O link expirou, foi revogado ou já foi utilizado. Solicite um novo convite ao responsável pela estamparia.</p><a href="{{ route('login') }}" class="button-primary mt-7">Ir para o login</a></div>
            @endif
        </div>
    </div>
</div>
