<x-layouts.guest title="Redefinir senha • EstampaFlow">
    <div class="grid min-h-screen place-items-center bg-app px-6 py-12">
        <div class="w-full max-w-md rounded-[28px] border border-line bg-white p-8 shadow-card">
            <x-brand />
            <h1 class="mt-10 text-3xl font-extrabold">Definir nova senha</h1>
            <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <div><label class="field-label" for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required class="field-input">@error('email')<span class="field-error">{{ $message }}</span>@enderror</div>
                <div><label class="field-label" for="password">Nova senha</label><input id="password" name="password" type="password" required class="field-input">@error('password')<span class="field-error">{{ $message }}</span>@enderror</div>
                <div><label class="field-label" for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" required class="field-input"></div>
                <button class="button-primary w-full">Salvar nova senha</button>
            </form>
        </div>
    </div>
</x-layouts.guest>
