<x-layouts.guest title="Criar estamparia • EstampaFlow">
    <div class="grid min-h-screen lg:grid-cols-[.92fr_1.08fr]">
        <section class="flex items-center justify-center px-6 py-12 sm:px-10 lg:px-16">
            <div class="w-full max-w-[430px]">
                <x-brand />
                <h1 class="mt-12 text-4xl font-extrabold tracking-tight">Crie sua estamparia</h1>
                <p class="mt-3 text-sm leading-6 text-ink-400">Ao concluir o cadastro, seu ambiente será criado com trial de 7 dias e você entrará diretamente no sistema.</p>

                <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
                    @csrf
                    <div><label class="field-label" for="business_name">Nome da estamparia</label><input id="business_name" name="business_name" value="{{ old('business_name') }}" required autofocus class="field-input" placeholder="Ex.: Estamparia Criativa">@error('business_name')<span class="field-error">{{ $message }}</span>@enderror</div>
                    <div><label class="field-label" for="name">Seu nome</label><input id="name" name="name" value="{{ old('name') }}" required class="field-input" placeholder="Seu nome completo">@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
                    <div><label class="field-label" for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" required class="field-input" placeholder="voce@empresa.com.br">@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
                    <div><label class="field-label" for="password">Senha</label><input id="password" name="password" type="password" required class="field-input" placeholder="Mínimo de 8 caracteres">@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
                    <div><label class="field-label" for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" required class="field-input" placeholder="Repita a senha"></div>
                    <button class="button-primary w-full py-3">Criar estamparia e começar</button>
                </form>
                <p class="mt-7 text-center text-xs text-ink-400">Já possui uma conta ou recebeu um convite? <a href="{{ route('login') }}" class="font-bold text-brand-600">Entrar</a></p>
            </div>
        </section>
        <section class="auth-panel-pattern hidden items-center justify-center border-l border-line p-12 lg:flex">
            <div class="max-w-lg rounded-[32px] border border-white bg-white/80 p-8 shadow-card backdrop-blur">
                <span class="status-badge status-info">Onboarding automático</span>
                <h2 class="mt-5 text-3xl font-extrabold">Do cadastro ao sistema em um único fluxo</h2>
                <p class="mt-3 text-sm leading-7 text-ink-500">Criamos o ambiente, o acesso de proprietário e o trial sem exigir uma etapa separada de configuração.</p>
            </div>
        </section>
    </div>
</x-layouts.guest>
