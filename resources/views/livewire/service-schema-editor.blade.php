<div class="page-shell">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-start gap-3">
            <a href="{{ route('tenant.service-types.index') }}" class="icon-button border border-line bg-white" aria-label="Voltar para tipos de serviço"><span aria-hidden="true">←</span></a>
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">{{ $serviceType->code }}</p>
                <h1 class="page-title">Campos de {{ $serviceType->name }}</h1>
                <p class="page-description">Escolha somente as informações que sua equipe precisa preencher ao usar este serviço.</p>
            </div>
        </div>
        <a href="{{ route('tenant.service-types.edit', ['serviceType' => $serviceType->id]) }}" class="button-secondary">Editar dados do serviço</a>
    </div>

    @if (session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    @error('schema')
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror

    @error('parameters')
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror

    <section class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-5">
        <div class="flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-brand-700 shadow-sm"><x-icon name="layers" class="size-5" /></span>
            <div>
                <h2 class="text-sm font-extrabold text-brand-900">A maior parte já vem pronta</h2>
                <p class="mt-1 max-w-4xl text-xs leading-5 text-brand-800">DTF, Silk, Sublimação e Bordado já possuem os campos mais comuns. Altere esta tela apenas quando a rotina da sua estamparia pedir algo diferente.</p>
            </div>
        </div>
    </section>

    @if ($suggestedFields !== [])
        <section class="surface-card mt-6 p-5 sm:p-6">
            <div>
                <h2 class="text-sm font-extrabold text-ink-950">Adicionar campos comuns</h2>
                <p class="mt-1 text-xs leading-5 text-ink-500">Clique somente nos campos que fazem parte da rotina deste serviço.</p>
            </div>

            <div class="mt-4 flex flex-wrap gap-2.5" data-testid="suggested-fields">
                @foreach ($suggestedFields as $preset => $field)
                    <button
                        type="button"
                        wire:click="addSuggestedField('{{ $preset }}')"
                        class="group inline-flex min-h-10 items-center gap-2 whitespace-nowrap rounded-full border border-brand-200 bg-brand-50/70 px-3.5 py-2 text-xs font-extrabold text-brand-800 shadow-sm transition duration-150 hover:-translate-y-px hover:border-brand-400 hover:bg-brand-100 hover:shadow-md focus-visible:outline-2 focus-visible:outline-brand-500"
                        aria-label="Adicionar {{ $field['label'] }}"
                        data-testid="suggested-field-button"
                    >
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-white text-sm font-black leading-none text-brand-600 shadow-sm ring-1 ring-brand-200 transition group-hover:bg-brand-600 group-hover:text-white">+</span>
                        <span>{{ $field['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <section class="surface-card mt-6 overflow-hidden">
        <div class="surface-card-header">
            <div>
                <h2 class="text-sm font-extrabold">Campos usados neste serviço</h2>
                <p class="mt-1 text-[11px] text-ink-400">Quantidade e posição da aplicação já fazem parte do sistema e não precisam ser adicionadas aqui.</p>
            </div>
            <button type="button" wire:click="addCustomField" class="button-secondary !px-3 !py-2">
                <x-icon name="plus" class="size-4" /> Adicionar campo personalizado
            </button>
        </div>

        <div class="space-y-3 p-5">
            @forelse ($parameters as $index => $parameter)
                <article wire:key="service-field-{{ $index }}" class="rounded-2xl border border-line bg-app/40 p-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="mt-1 grid size-8 shrink-0 place-items-center rounded-lg bg-white text-[10px] font-black text-brand-700 shadow-sm">{{ $index + 1 }}</span>

                            <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-[minmax(220px,1fr)_220px]">
                                <label>
                                    <span class="field-label">Nome do campo</span>
                                    <input wire:model="parameters.{{ $index }}.label" class="field-input" placeholder="Ex.: Tipo de tecido">
                                </label>

                                <label>
                                    <span class="field-label">Tipo de resposta</span>
                                    <select wire:model.live="parameters.{{ $index }}.field_type" class="field-input">
                                        @foreach ($fieldTypes as $fieldType)
                                            <option value="{{ $fieldType->value }}">{{ $fieldType->label() }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                @if (in_array($parameter['field_type'], ['SELECT', 'MULTISELECT'], true))
                                    <label class="md:col-span-2">
                                        <span class="field-label">Opções disponíveis — uma por linha</span>
                                        <textarea wire:model="parameters.{{ $index }}.options_text" rows="3" class="field-input" placeholder="Pequeno&#10;Médio&#10;Grande"></textarea>
                                    </label>
                                @endif

                                <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                                    <label class="flex items-center gap-2 text-xs font-bold text-ink-600">
                                        <input wire:model="parameters.{{ $index }}.required" type="checkbox" class="rounded border-line text-brand-500">
                                        Obrigatório
                                    </label>

                                    <details class="group">
                                        <summary class="cursor-pointer list-none text-xs font-bold text-brand-700 hover:text-brand-800">Mais opções</summary>
                                        <div class="mt-3 grid gap-3 rounded-xl border border-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <label>
                                                <span class="field-label">Unidade</span>
                                                <input wire:model="parameters.{{ $index }}.unit" class="field-input" placeholder="cm, cores, pontos...">
                                            </label>
                                            <label>
                                                <span class="field-label">Valor inicial</span>
                                                <input wire:model="parameters.{{ $index }}.default_value" class="field-input" placeholder="Opcional">
                                            </label>
                                            <label class="flex items-center gap-2 self-end rounded-xl border border-line px-3 py-3 text-xs font-bold text-ink-600">
                                                <input wire:model="parameters.{{ $index }}.affects_pricing" type="checkbox" class="rounded border-line text-brand-500">
                                                Usar no cálculo do preço
                                            </label>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-end gap-1">
                            <button type="button" wire:click="moveUp({{ $index }})" class="icon-button !size-8" title="Mover para cima" aria-label="Mover campo para cima">↑</button>
                            <button type="button" wire:click="moveDown({{ $index }})" class="icon-button !size-8" title="Mover para baixo" aria-label="Mover campo para baixo">↓</button>
                            <button type="button" wire:click="removeParameter({{ $index }})" class="icon-button !size-8 text-red-500" title="Remover campo" aria-label="Remover campo">×</button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-brand-300 bg-brand-50 px-6 py-10 text-center">
                    <p class="text-sm font-bold text-brand-700">Este serviço não precisa de informações extras.</p>
                    <p class="mt-2 text-xs leading-5 text-brand-700">Você pode deixá-lo assim ou adicionar somente o que sua equipe realmente usa.</p>
                    <button type="button" wire:click="addCustomField" class="button-secondary mt-4">Adicionar um campo</button>
                </div>
            @endforelse
        </div>

        <div class="flex flex-col gap-3 border-t border-line bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[11px] leading-5 text-ink-400">O sistema guarda as alterações anteriores automaticamente para não mudar orçamentos antigos.</p>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="button-primary">
                <span wire:loading.remove wire:target="save">Salvar alterações</span>
                <span wire:loading wire:target="save">Salvando...</span>
            </button>
        </div>
    </section>
</div>
