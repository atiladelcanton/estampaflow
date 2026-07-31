@php
    $dtfForm = [
        'meter_cost' => old('meter_cost', $guidedInitial['meter_cost']),
        'usable_width_cm' => old('usable_width_cm', $guidedInitial['usable_width_cm']),
        'application_price' => old('application_price', $guidedInitial['application_price']),
        'material_markup_percent' => old('material_markup_percent', $guidedInitial['material_markup_percent']),
        'spacing_cm' => old('spacing_cm', $guidedInitial['spacing_cm']),
        'waste_percent' => old('waste_percent', $guidedInitial['waste_percent']),
        'allow_rotation' => (bool) old('allow_rotation', $guidedInitial['allow_rotation']),
        'valid_from' => old('valid_from', $guidedInitial['valid_from']),
        'valid_until' => old('valid_until', $guidedInitial['valid_until']),
        'sample_quantity' => old('sample_quantity', $guidedInitial['sample_quantity']),
        'sample_width_cm' => old('sample_width_cm', $guidedInitial['sample_width_cm']),
        'sample_height_cm' => old('sample_height_cm', $guidedInitial['sample_height_cm']),
    ];

    $dtfStartStep = session('success') ? 4 : 1;
    if ($errors->hasAny(['meter_cost', 'usable_width_cm'])) $dtfStartStep = 2;
    if ($errors->hasAny(['application_price', 'material_markup_percent', 'spacing_cm', 'waste_percent'])) $dtfStartStep = 3;
@endphp

<script>
    window.estampaFlowDtfWizard = function (initial, startStep) {
        const decimal = value => {
            const text = String(value ?? '').trim();
            const normalized = text.includes(',') ? text.replace(/\./g, '').replace(',', '.') : text;
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const simpleDecimal = value => {
            const normalized = String(value ?? '').trim().replace(',', '.');
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        const formatNumber = value => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);

        return {
            step: startStep,
            form: { ...initial },
            next() { this.step = Math.min(4, this.step + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            previous() { this.step = Math.max(1, this.step - 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            goTo(target) { this.step = target; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            layout(itemWidth, itemHeight, usableWidth, spacing, quantity, rotated) {
                if (itemWidth <= 0 || itemHeight <= 0 || itemWidth > usableWidth) return null;
                const itemsPerRow = Math.max(1, Math.floor((usableWidth + spacing) / (itemWidth + spacing)));
                const rows = Math.ceil(quantity / itemsPerRow);
                return {
                    itemsPerRow,
                    rows,
                    lengthCm: (rows * itemHeight) + (Math.max(0, rows - 1) * spacing),
                    rotated,
                };
            },
            get preview() {
                const quantity = Math.max(1, Math.floor(simpleDecimal(this.form.sample_quantity)));
                const width = simpleDecimal(this.form.sample_width_cm);
                const height = simpleDecimal(this.form.sample_height_cm);
                const usable = simpleDecimal(this.form.usable_width_cm);
                const spacing = Math.max(0, simpleDecimal(this.form.spacing_cm));
                const normal = this.layout(width, height, usable, spacing, quantity, false);
                const rotated = this.form.allow_rotation ? this.layout(height, width, usable, spacing, quantity, true) : null;
                let layout = normal;
                if (!layout || (rotated && rotated.lengthCm < layout.lengthCm)) layout = rotated;

                if (!layout || width <= 0 || height <= 0 || usable <= 0) {
                    return { valid: false, message: 'Informe medidas que caibam na largura útil do material.' };
                }

                const waste = Math.max(0, simpleDecimal(this.form.waste_percent));
                const requiredCm = layout.lengthCm * (1 + waste / 100);
                const requiredMeters = requiredCm / 100;
                const chargedMeters = Math.max(1, Math.ceil(requiredMeters));
                const meterCost = decimal(this.form.meter_cost);
                const application = decimal(this.form.application_price);
                const markup = Math.max(0, simpleDecimal(this.form.material_markup_percent));
                const materialCost = chargedMeters * meterCost;
                const materialMarkup = materialCost * markup / 100;
                const applicationTotal = quantity * application;
                const total = materialCost + materialMarkup + applicationTotal;
                const perItem = total / quantity;
                const leftover = chargedMeters - requiredMeters;

                return {
                    valid: meterCost > 0,
                    quantity,
                    ...layout,
                    requiredMeters,
                    chargedMeters,
                    leftover,
                    materialCost,
                    materialMarkup,
                    applicationTotal,
                    total,
                    perItem,
                    warning: perItem > 500 || total > 100000,
                };
            },
            money,
            formatNumber,
            decimal,
        };
    };
</script>

<form
    method="POST"
    action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}"
    class="mt-6"
    x-data="estampaFlowDtfWizard(@js($dtfForm), {{ $dtfStartStep }})"
    data-tour="pricing-guided-steps"
>
    @csrf
    @method('PUT')
    <input type="hidden" name="setup_template" value="DTF_METER">

    <section class="surface-card overflow-hidden">
        <div class="border-b border-line bg-app px-5 py-5 sm:px-7">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Configuração guiada</p>
                    <h2 class="mt-1 text-lg font-black text-ink-950">DTF comprado por metro inteiro</h2>
                </div>
                <p class="rounded-full bg-white px-4 py-2 text-xs font-extrabold text-ink-600">Etapa <span x-text="step"></span> de 4</p>
            </div>

            <div class="mt-5 grid grid-cols-4 gap-2" aria-label="Progresso da configuração">
                <template x-for="item in [1, 2, 3, 4]" :key="item">
                    <button type="button" @click="goTo(item)" class="h-2 rounded-full transition" :class="item <= step ? 'bg-brand-500' : 'bg-line'" :aria-label="`Ir para etapa ${item}`"></button>
                </template>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            <div x-show="step === 1" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 1</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Como você trabalha com DTF?</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Escolha o cenário que representa sua operação hoje.</p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="rounded-2xl border-2 border-brand-500 bg-brand-50 p-5">
                        <span class="flex items-start gap-3">
                            <input type="radio" checked class="mt-1" aria-label="Compro DTF pronto por metro">
                            <span>
                                <strong class="block text-base text-ink-950">Compro o DTF pronto por metro</strong>
                                <span class="mt-2 block text-sm leading-6 text-ink-600">O fornecedor cobra somente metros inteiros. Esta opção já está pronta para configurar.</span>
                                <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-extrabold text-emerald-700">Selecionado</span>
                            </span>
                        </span>
                    </label>

                    <div class="rounded-2xl border border-line bg-app p-5 opacity-75">
                        <strong class="block text-base text-ink-800">Produzo o DTF internamente</strong>
                        <p class="mt-2 text-sm leading-6 text-ink-500">Usará filme, tintas, poliamida, energia e perdas cadastrados em Insumos e Estoque.</p>
                        <span class="mt-3 inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-extrabold text-ink-500">Disponível após a Sprint 4</span>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm leading-6 text-ink-600">
                    <strong class="text-ink-950">Você não precisará calcular área nem valor por cm².</strong>
                    No orçamento, a equipe informa largura, altura e quantidade. O EstampaFlow calcula o aproveitamento do metro.
                </div>
            </div>

            <div x-show="step === 2" x-cloak data-tour="pricing-dtf-supplier">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 2</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Informe somente os dados do fornecedor</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">São os dois dados necessários para calcular quanto material será comprado.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="field-label">Quanto você paga por 1 metro?</span>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm text-ink-400">R$</span>
                            <input name="meter_cost" x-model="form.meter_cost" inputmode="decimal" class="field-input pl-11" placeholder="40,00" required>
                        </div>
                        <span class="mt-2 block text-xs leading-5 text-ink-400">Use o valor efetivamente cobrado pelo fornecedor.</span>
                    </label>

                    <label>
                        <span class="field-label">Qual é a largura útil do material?</span>
                        <div class="relative">
                            <input name="usable_width_cm" x-model="form.usable_width_cm" inputmode="decimal" class="field-input pr-14" placeholder="58" required>
                            <span class="absolute right-4 top-3 text-sm text-ink-400">cm</span>
                        </div>
                        <span class="mt-2 block text-xs leading-5 text-ink-400">É o espaço realmente disponível para encaixar as artes.</span>
                    </label>
                </div>

                <div class="mt-6 flex items-center gap-3 rounded-2xl bg-app p-4 text-sm text-ink-600">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white font-black text-brand-700">✓</span>
                    <span><strong class="text-ink-950">Compra mínima configurada:</strong> 1 metro inteiro. Qualquer fração será arredondada para cima.</span>
                </div>
            </div>

            <div x-show="step === 3" x-cloak data-tour="pricing-dtf-commercial">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 3</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Aplicação e acréscimo</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Estes campos são opcionais. Preencha apenas o que você já utiliza no preço.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="field-label">Quanto você cobra para prensar cada peça?</span>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm text-ink-400">R$</span>
                            <input name="application_price" x-model="form.application_price" inputmode="decimal" class="field-input pl-11" placeholder="2,00">
                        </div>
                        <span class="mt-2 block text-xs leading-5 text-ink-400">Deixe vazio se a aplicação não for cobrada separadamente.</span>
                    </label>

                    <label>
                        <span class="field-label">Acréscimo sobre o custo do DTF</span>
                        <div class="relative">
                            <input name="material_markup_percent" x-model="form.material_markup_percent" inputmode="decimal" class="field-input pr-12" placeholder="35">
                            <span class="absolute right-4 top-3 text-sm text-ink-400">%</span>
                        </div>
                        <span class="mt-2 block text-xs leading-5 text-ink-400">É aplicado somente sobre o material comprado.</span>
                    </label>
                </div>

                <details class="mt-6 rounded-2xl border border-line bg-white">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-extrabold text-ink-800">Mais opções</summary>
                    <div class="grid gap-5 border-t border-line p-5 md:grid-cols-2">
                        <label>
                            <span class="field-label">Espaço entre as artes</span>
                            <div class="relative"><input name="spacing_cm" x-model="form.spacing_cm" inputmode="decimal" class="field-input pr-14"><span class="absolute right-4 top-3 text-sm text-ink-400">cm</span></div>
                            <span class="mt-2 block text-xs text-ink-400">Folga para corte e separação.</span>
                        </label>
                        <label>
                            <span class="field-label">Perda de segurança</span>
                            <div class="relative"><input name="waste_percent" x-model="form.waste_percent" inputmode="decimal" class="field-input pr-12" placeholder="0"><span class="absolute right-4 top-3 text-sm text-ink-400">%</span></div>
                            <span class="mt-2 block text-xs text-ink-400">Pode ficar zerada no início.</span>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl bg-app p-4 md:col-span-2">
                            <input type="hidden" name="allow_rotation" value="0">
                            <input type="checkbox" name="allow_rotation" value="1" x-model="form.allow_rotation" class="rounded border-line text-brand-600">
                            <span><strong class="block text-sm text-ink-900">Girar a arte quando aproveitar melhor o material</strong><span class="mt-1 block text-xs text-ink-400">O sistema compara as duas posições e usa a que gastar menos comprimento.</span></span>
                        </label>
                        <label><span class="field-label">Usar esta configuração a partir de</span><input type="date" name="valid_from" x-model="form.valid_from" class="field-input"></label>
                        <label><span class="field-label">Usar até</span><input type="date" name="valid_until" x-model="form.valid_until" class="field-input"></label>
                    </div>
                </details>
            </div>

            <div x-show="step === 4" x-cloak data-tour="pricing-preview">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 4</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Confira com um pedido real</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Mude o exemplo abaixo. O resultado atualiza antes de salvar.</p>

                <div class="mt-6 grid gap-5 rounded-2xl bg-app p-5 sm:grid-cols-3">
                    <label><span class="field-label">Quantidade</span><input type="number" min="1" x-model="form.sample_quantity" class="field-input"></label>
                    <label><span class="field-label">Largura da arte</span><div class="relative"><input x-model="form.sample_width_cm" inputmode="decimal" class="field-input pr-14"><span class="absolute right-4 top-3 text-sm text-ink-400">cm</span></div></label>
                    <label><span class="field-label">Altura da arte</span><div class="relative"><input x-model="form.sample_height_cm" inputmode="decimal" class="field-input pr-14"><span class="absolute right-4 top-3 text-sm text-ink-400">cm</span></div></label>
                </div>

                <div class="mt-6" x-show="preview.valid">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-line bg-white p-4"><p class="text-xs font-bold text-ink-400">Comprimento estimado</p><p class="mt-2 text-2xl font-black text-ink-950"><span x-text="formatNumber(preview.requiredMeters)"></span> m</p></div>
                        <div class="rounded-2xl border border-line bg-white p-4"><p class="text-xs font-bold text-ink-400">Metros comprados</p><p class="mt-2 text-2xl font-black text-ink-950"><span x-text="preview.chargedMeters"></span> m</p></div>
                        <div class="rounded-2xl border border-line bg-white p-4"><p class="text-xs font-bold text-ink-400">Sobra estimada</p><p class="mt-2 text-2xl font-black text-ink-950"><span x-text="formatNumber(preview.leftover)"></span> m</p></div>
                        <div class="rounded-2xl border border-brand-200 bg-brand-50 p-4"><p class="text-xs font-bold text-brand-700">Valor médio por peça</p><p class="mt-2 text-2xl font-black text-ink-950" x-text="money(preview.perItem)"></p></div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-line bg-white p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><p class="text-xs font-bold text-ink-400">Organização estimada</p><p class="mt-1 font-extrabold text-ink-950"><span x-text="preview.itemsPerRow"></span> arte(s) por linha · <span x-text="preview.rows"></span> linha(s)</p></div>
                            <span class="status-badge status-neutral" x-text="preview.rotated ? 'Arte girada para melhor aproveitamento' : 'Arte na posição original'"></span>
                        </div>

                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between gap-4"><span class="text-ink-500">Material: <span x-text="preview.chargedMeters"></span> m × <span x-text="money(decimal(form.meter_cost))"></span></span><strong x-text="money(preview.materialCost)"></strong></div>
                            <div class="flex justify-between gap-4" x-show="preview.materialMarkup > 0"><span class="text-ink-500">Acréscimo no material</span><strong x-text="money(preview.materialMarkup)"></strong></div>
                            <div class="flex justify-between gap-4" x-show="preview.applicationTotal > 0"><span class="text-ink-500">Aplicação das peças</span><strong x-text="money(preview.applicationTotal)"></strong></div>
                            <div class="flex justify-between gap-4 border-t border-line pt-3 text-base"><strong>Total estimado do serviço</strong><strong class="text-xl text-brand-700" x-text="money(preview.total)"></strong></div>
                        </div>
                    </div>

                    <div x-show="preview.warning" class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        <strong>Revise este resultado antes de salvar.</strong> O valor ficou muito alto para um exemplo comum. Confira principalmente o preço do metro e se usou vírgula corretamente.
                    </div>
                </div>

                <div x-show="!preview.valid" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    <strong>Não foi possível montar o exemplo.</strong>
                    <span x-text="preview.message"></span>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-line bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <button type="button" x-show="step > 1" @click="previous()" class="button-secondary">Voltar</button>
            <span x-show="step === 1" class="hidden sm:block"></span>

            <div class="flex justify-end gap-3">
                <button type="button" x-show="step < 4" @click="next()" class="button-primary">Continuar</button>
                <button type="submit" x-show="step === 4" class="button-primary">Salvar configuração</button>
            </div>
        </div>
    </section>
</form>
