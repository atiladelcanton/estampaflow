<x-layouts.app title="Primeiros passos • EstampaFlow">
    <div class="page-shell" x-data="{ step: {{ $errors->has('invitation_email') ? 4 : ($errors->any() ? 2 : 1) }}, total: 5 }">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="status-badge status-info">Configuração inicial</span>
                    <h1 class="page-title mt-3">Vamos deixar sua estamparia pronta</h1>
                    <p class="page-description">São poucos passos. Você pode pular agora e retomar depois pela Central de Ajuda.</p>
                </div>

                @unless($reviewing)
                    <form method="POST" action="{{ route('tenant.onboarding.skip') }}">
                        @csrf
                        <button type="submit" class="button-ghost">Pular por enquanto</button>
                    </form>
                @endunless
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-bold">Revise as informações abaixo:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-5 grid grid-cols-5 gap-2" aria-label="Progresso dos primeiros passos">
                @for ($index = 1; $index <= 5; $index++)
                    <div class="h-2 rounded-full transition" :class="step >= {{ $index }} ? 'bg-brand-500' : 'bg-ink-100'"></div>
                @endfor
            </div>

            <form method="POST" action="{{ route('tenant.onboarding.complete') }}" class="surface-card overflow-hidden">
                @csrf

                <section x-show="step === 1" x-cloak class="p-6 sm:p-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-brand-100 text-2xl font-black text-brand-700">EF</span>
                        <h2 class="mt-6 text-2xl font-extrabold text-ink-950">Bem-vindo ao EstampaFlow</h2>
                        <p class="mt-3 text-sm leading-7 text-ink-500">
                            Primeiro vamos confirmar sua estamparia, os serviços utilizados e, se desejar, convidar alguém da equipe.
                        </p>
                        <div class="mt-8 grid gap-3 text-left sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([
                                ['1', 'Confirme os dados', 'Nome e fuso horário.'],
                                ['2', 'Escolha os serviços', 'Mantenha somente o que utiliza.'],
                                ['3', 'Convide a equipe', 'Esta etapa é opcional.'],
                                ['4', 'Configure preços', 'Pode fazer depois, com calma.'],
                            ] as [$number, $title, $description])
                                <div class="rounded-2xl bg-app p-4">
                                    <span class="grid size-8 place-items-center rounded-xl bg-white text-xs font-black text-brand-700 shadow-sm">{{ $number }}</span>
                                    <p class="mt-3 text-sm font-extrabold">{{ $title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-ink-400">{{ $description }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section x-show="step === 2" x-cloak class="p-6 sm:p-10">
                    <div class="mx-auto max-w-2xl">
                        <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Etapa 2 de 5</p>
                        <h2 class="mt-3 text-2xl font-extrabold">Confirme sua estamparia</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-500">Você poderá alterar esses dados depois.</p>

                        <div class="mt-8 space-y-5">
                            <label>
                                <span class="field-label">Nome da estamparia</span>
                                <input name="business_name" value="{{ old('business_name', $tenant->name) }}" class="field-input" required maxlength="120">
                            </label>
                            <label>
                                <span class="field-label">Fuso horário</span>
                                <select name="timezone" class="field-input" required>
                                    @foreach($timezones as $value => $label)
                                        <option value="{{ $value }}" @selected(old('timezone', $tenant->timezone) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="field-help">Usado nos prazos, relatórios e movimentações.</span>
                            </label>
                            <div class="rounded-2xl bg-surface-mint px-4 py-3 text-xs text-ink-500">
                                Seu endereço continua <strong class="text-ink-800">{{ request()->getHost() }}</strong>.
                            </div>
                        </div>
                    </div>
                </section>

                <section x-show="step === 3" x-cloak class="p-6 sm:p-10">
                    <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Etapa 3 de 5</p>
                    <h2 class="mt-3 text-2xl font-extrabold">Quais serviços você utiliza?</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-500">Os serviços já vêm configurados. Desmarque somente o que sua estamparia não oferece.</p>

                    <div class="mt-7 grid gap-3 sm:grid-cols-2">
                        @foreach($services as $service)
                            <label class="flex cursor-pointer items-start gap-4 rounded-2xl border border-line bg-white p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                                <input
                                    type="checkbox"
                                    name="services[]"
                                    value="{{ $service->id }}"
                                    class="mt-1 rounded border-line text-brand-500"
                                    @checked(in_array($service->id, old('services', $services->where('active', true)->pluck('id')->all()), true))
                                >
                                <span>
                                    <span class="block text-sm font-extrabold text-ink-900">{{ $service->name }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-ink-400">{{ $service->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section x-show="step === 4" x-cloak class="p-6 sm:p-10">
                    <div class="mx-auto max-w-2xl">
                        <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Etapa 4 de 5</p>
                        <h2 class="mt-3 text-2xl font-extrabold">Convide alguém da equipe</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-500">Opcional. A pessoa receberá um e-mail com o link para entrar nesta estamparia.</p>

                        <label class="mt-8 block">
                            <span class="field-label">E-mail da pessoa</span>
                            <input name="invitation_email" value="{{ old('invitation_email') }}" type="email" class="field-input" placeholder="pessoa@empresa.com">
                            <span class="field-help">Deixe em branco para fazer isso depois.</span>
                        </label>

                        <div class="mt-5 rounded-2xl bg-app p-4 text-xs leading-6 text-ink-500">
                            O convite expira em sete dias e será enviado pela fila de e-mail configurada no sistema.
                        </div>
                    </div>
                </section>

                <section x-show="step === 5" x-cloak class="p-6 sm:p-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-50 text-2xl font-black text-emerald-700">✓</span>
                        <h2 class="mt-6 text-2xl font-extrabold">Tudo pronto para começar</h2>
                        <p class="mt-3 text-sm leading-7 text-ink-500">
                            Ao concluir, você irá para a visão geral. Cada módulo mostrará um tutorial curto apenas na primeira visita.
                        </p>
                        <div class="mt-8 rounded-2xl bg-surface-mint p-5 text-left">
                            <p class="text-sm font-extrabold text-ink-800">Depois você poderá:</p>
                            <ul class="mt-3 space-y-2 text-xs leading-5 text-ink-500">
                                <li>• rever qualquer tutorial pela Central de Ajuda;</li>
                                <li>• ativar ou desativar serviços quando precisar;</li>
                                <li>• convidar outras pessoas pela tela Equipe.</li>
                                <li>• configurar e testar os preços de cada serviço.</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" x-show="step > 1" @click="step--" class="button-secondary">Voltar</button>
                    <span x-show="step === 1" class="hidden sm:block"></span>

                    <button type="button" x-show="step < total" @click="step++" class="button-primary">Próximo</button>
                    <button type="submit" x-show="step === total" class="button-primary">Concluir primeiros passos</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
