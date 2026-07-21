<x-layouts.app title="Campos do serviço • EstampaFlow">
    @php
        $editorFields = old('fields', $initialFields);
        $fieldsAction = '/configuracoes/servicos/'.$serviceType->id.'/campos';
        $indexUrl = '/configuracoes/servicos';
        $editUrl = '/configuracoes/servicos/'.$serviceType->id.'/editar';
    @endphp

    <div
        class="page-shell"
        x-data="serviceFieldsEditor(@js($editorFields), @js($fieldPresets))"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ $indexUrl }}" class="icon-button border border-line bg-white" aria-label="Voltar para tipos de serviço">
                    <span aria-hidden="true">←</span>
                </a>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">{{ $serviceType->code }}</p>
                    <h1 class="page-title">Campos de {{ $serviceType->name }}</h1>
                    <p class="page-description">Escolha somente as informações que sua equipe precisa preencher ao usar este serviço.</p>
                </div>
            </div>
            <a href="{{ $editUrl }}" class="button-secondary">Editar dados do serviço</a>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-extrabold">Revise os campos antes de salvar:</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-5">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-brand-700 shadow-sm">
                    <x-icon name="layers" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-brand-900">A maior parte já vem pronta</h2>
                    <p class="mt-1 max-w-4xl text-xs leading-5 text-brand-800">
                        DTF, Silk, Sublimação e Bordado já possuem os campos mais comuns. Altere esta tela apenas quando sua rotina pedir algo diferente.
                    </p>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ $fieldsAction }}" class="mt-6">
            @csrf
            @method('PATCH')

            <section class="surface-card p-5 sm:p-6" data-testid="suggested-fields">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-extrabold text-ink-950">Adicionar campos comuns</h2>
                        <p class="mt-1 text-xs leading-5 text-ink-500">Escolha apenas o que realmente será preenchido pela equipe.</p>
                    </div>
                    <p class="text-[11px] font-semibold text-ink-400">O campo desaparece daqui depois de ser adicionado.</p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($fieldPresets as $preset => $field)
                        <button
                            type="button"
                            x-show="! hasField(@js($field['key']))"
                            x-cloak
                            @click="addPreset(@js($preset))"
                            class="group flex min-h-[72px] items-center gap-3 rounded-2xl border border-line bg-white p-3.5 text-left shadow-sm transition duration-150 hover:-translate-y-px hover:border-brand-300 hover:bg-brand-50 hover:shadow-md focus-visible:outline-2 focus-visible:outline-brand-500"
                            aria-label="Adicionar {{ $field['label'] }}"
                            data-testid="suggested-field-button"
                        >
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-lg font-black text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white" aria-hidden="true">+</span>
                            <span class="min-w-0">
                                <span class="block text-xs font-extrabold text-ink-900">{{ $field['label'] }}</span>
                                <span class="mt-1 block text-[10px] leading-4 text-ink-400">{{ $field['hint'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="surface-card mt-6 overflow-hidden">
                <div class="surface-card-header gap-4">
                    <div>
                        <h2 class="text-sm font-extrabold">Campos usados neste serviço</h2>
                        <p class="mt-1 text-[11px] text-ink-400">Quantidade e posição da aplicação já fazem parte do sistema.</p>
                    </div>
                    <button type="button" @click="addCustomField" class="button-secondary shrink-0 !px-3 !py-2">
                        <span aria-hidden="true">+</span>
                        Adicionar campo personalizado
                    </button>
                </div>

                <div class="space-y-3 p-5">
                    <template x-for="(field, index) in fields" :key="field.client_id">
                        <article class="rounded-2xl border border-line bg-app/40 p-4">
                            <input type="hidden" :name="`fields[${index}][key]`" x-model="field.key">
                            <input type="hidden" :name="`fields[${index}][active]`" value="1">

                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
                                <div class="flex min-w-0 flex-1 items-start gap-3">
                                    <span class="mt-1 grid size-8 shrink-0 place-items-center rounded-lg bg-white text-[10px] font-black text-brand-700 shadow-sm" x-text="index + 1"></span>

                                    <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-[minmax(220px,1fr)_220px]">
                                        <label>
                                            <span class="field-label">Nome do campo</span>
                                            <input
                                                :name="`fields[${index}][label]`"
                                                x-model="field.label"
                                                class="field-input"
                                                placeholder="Ex.: Tipo de tecido"
                                                data-service-field-label
                                                required
                                            >
                                        </label>

                                        <label>
                                            <span class="field-label">Tipo de resposta</span>
                                            <select :name="`fields[${index}][field_type]`" x-model="field.field_type" class="field-input">
                                                @foreach ($fieldTypes as $fieldType)
                                                    <option value="{{ $fieldType->value }}">{{ $fieldType->label() }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="md:col-span-2" x-show="['SELECT', 'MULTISELECT'].includes(field.field_type)" x-cloak>
                                            <span class="field-label">Opções disponíveis — uma por linha</span>
                                            <textarea
                                                :name="`fields[${index}][options_text]`"
                                                x-model="field.options_text"
                                                rows="3"
                                                class="field-input"
                                                placeholder="Pequeno&#10;Médio&#10;Grande"
                                            ></textarea>
                                        </label>

                                        <div class="flex flex-wrap items-center gap-4 md:col-span-2">
                                            <input type="hidden" :name="`fields[${index}][required]`" value="0">
                                            <label class="flex items-center gap-2 text-xs font-bold text-ink-600">
                                                <input
                                                    :name="`fields[${index}][required]`"
                                                    x-model="field.required"
                                                    type="checkbox"
                                                    value="1"
                                                    class="rounded border-line text-brand-500"
                                                >
                                                Obrigatório
                                            </label>

                                            <details class="group w-full sm:w-auto">
                                                <summary class="cursor-pointer list-none text-xs font-bold text-brand-700 hover:text-brand-800">Mais opções</summary>
                                                <div class="mt-3 grid gap-3 rounded-xl border border-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-3">
                                                    <label>
                                                        <span class="field-label">Unidade</span>
                                                        <input :name="`fields[${index}][unit]`" x-model="field.unit" class="field-input" placeholder="cm, cores, pontos...">
                                                    </label>
                                                    <label>
                                                        <span class="field-label">Valor inicial</span>
                                                        <input :name="`fields[${index}][default_value]`" x-model="field.default_value" class="field-input" placeholder="Opcional">
                                                    </label>
                                                    <div class="self-end">
                                                        <input type="hidden" :name="`fields[${index}][affects_pricing]`" value="0">
                                                        <label class="flex items-center gap-2 rounded-xl border border-line px-3 py-3 text-xs font-bold text-ink-600">
                                                            <input
                                                                :name="`fields[${index}][affects_pricing]`"
                                                                x-model="field.affects_pricing"
                                                                type="checkbox"
                                                                value="1"
                                                                class="rounded border-line text-brand-500"
                                                            >
                                                            Usar no cálculo do preço
                                                        </label>
                                                    </div>
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center justify-end gap-1">
                                    <button type="button" @click="moveField(index, -1)" class="icon-button !size-8" aria-label="Mover campo para cima">↑</button>
                                    <button type="button" @click="moveField(index, 1)" class="icon-button !size-8" aria-label="Mover campo para baixo">↓</button>
                                    <button type="button" @click="removeField(index)" class="icon-button !size-8 text-red-500" aria-label="Remover campo">×</button>
                                </div>
                            </div>
                        </article>
                    </template>

                    <div
                        x-show="fields.length === 0"
                        x-cloak
                        class="rounded-2xl border border-dashed border-brand-300 bg-brand-50 px-6 py-10 text-center"
                    >
                        <p class="text-sm font-bold text-brand-700">Este serviço não precisa de informações extras.</p>
                        <p class="mt-2 text-xs leading-5 text-brand-700">Você pode deixá-lo assim ou adicionar somente o que sua equipe realmente usa.</p>
                        <button type="button" @click="addCustomField" class="button-secondary mt-4">Adicionar um campo</button>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-line bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[11px] leading-5 text-ink-400">As alterações anteriores continuam preservadas para não modificar orçamentos antigos.</p>
                    <button type="submit" class="button-primary">Salvar alterações</button>
                </div>
            </section>
        </form>
    </div>
</x-layouts.app>
