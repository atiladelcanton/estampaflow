@php
    $selectedEmbroideryColumns = old('selected_stitch_columns', $guidedInitial['selected_stitch_columns']);
    if (! is_array($selectedEmbroideryColumns) || $selectedEmbroideryColumns === []) {
        $selectedEmbroideryColumns = $guidedInitial['selected_stitch_columns'];
    }

    $embroideryForm = [
        'stitch_columns' => $guidedInitial['stitch_columns'],
        'selected_stitch_columns' => array_values($selectedEmbroideryColumns),
        'digitizing_charge_mode' => old('digitizing_charge_mode', $guidedInitial['digitizing_charge_mode']),
        'digitizing_price' => old('digitizing_price', $guidedInitial['digitizing_price']),
        'ranges' => old('ranges', $guidedInitial['ranges']),
        'valid_from' => old('valid_from', $guidedInitial['valid_from']),
        'valid_until' => old('valid_until', $guidedInitial['valid_until']),
        'sample_quantity' => old('sample_quantity', $guidedInitial['sample_quantity']),
        'sample_stitch_range' => old('sample_stitch_range', $guidedInitial['sample_stitch_range']),
    ];

    $embroideryStartStep = session('success') ? 4 : 1;
    if ($errors->hasAny(['digitizing_charge_mode', 'stitch_columns', 'stitch_columns.*'])) $embroideryStartStep = 1;
    if ($errors->hasAny(['ranges', 'ranges.*'])) $embroideryStartStep = 2;
    if ($errors->hasAny(['digitizing_price', 'valid_from', 'valid_until'])) $embroideryStartStep = 3;
@endphp

<script>
    window.estampaFlowEmbroideryWizard = function (initial, startStep) {
        const numeric = value => {
            const normalized = String(value ?? '').trim().replace(/\./g, '').replace(',', '.');
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);

        return {
            step: startStep,
            selectionMessage: '',
            form: {
                ...initial,
                selected_stitch_columns: (initial.selected_stitch_columns || []).map(String),
                ranges: (initial.ranges || []).map(range => ({
                    min_quantity: range.min_quantity ?? 1,
                    max_quantity: range.max_quantity ?? '',
                    prices: { ...(range.prices || {}) },
                })),
            },
            money,
            get activeStitchColumns() {
                return this.form.stitch_columns.filter(item => this.form.selected_stitch_columns.includes(String(item.key)));
            },
            isStitchColumnSelected(key) {
                return this.form.selected_stitch_columns.includes(String(key));
            },
            toggleStitchColumn(key) {
                const value = String(key);
                this.selectionMessage = '';

                if (this.isStitchColumnSelected(value)) {
                    if (this.form.selected_stitch_columns.length === 1) {
                        this.selectionMessage = 'Mantenha pelo menos uma faixa de pontos selecionada.';
                        return;
                    }
                    this.form.selected_stitch_columns = this.form.selected_stitch_columns.filter(item => item !== value);
                } else {
                    this.form.selected_stitch_columns.push(value);
                }

                const selectedLabels = this.activeStitchColumns.map(item => item.label);
                if (!selectedLabels.includes(String(this.form.sample_stitch_range))) {
                    this.form.sample_stitch_range = selectedLabels[0] || '';
                }
            },
            next() {
                this.step = Math.min(4, this.step + 1);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            previous() {
                this.step = Math.max(1, this.step - 1);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            goTo(target) {
                this.step = target;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            addRange() {
                const last = this.form.ranges[this.form.ranges.length - 1];
                let nextMin = 1;

                if (last) {
                    nextMin = last.max_quantity ? Number(last.max_quantity) + 1 : Number(last.min_quantity || 1) + 30;
                    if (!last.max_quantity) last.max_quantity = nextMin - 1;
                }

                const prices = {};
                this.form.stitch_columns.forEach(item => prices[item.key] = '');
                this.form.ranges.push({ min_quantity: nextMin, max_quantity: '', prices });
            },
            removeRange(index) {
                if (this.form.ranges.length > 1) this.form.ranges.splice(index, 1);
            },
            rangeFor(quantity) {
                return this.form.ranges.find(range => {
                    const min = Number(range.min_quantity || 0);
                    const max = range.max_quantity === '' || range.max_quantity === null ? null : Number(range.max_quantity);
                    return quantity >= min && (max === null || quantity <= max);
                });
            },
            rangeLabel(range) {
                return !range ? 'Sem faixa' : (range.max_quantity ? `${range.min_quantity} a ${range.max_quantity} peças` : `${range.min_quantity} ou mais`);
            },
            get preview() {
                const quantity = Math.max(1, Number(this.form.sample_quantity || 0));
                const range = this.rangeFor(quantity);
                const stitchRange = this.form.sample_stitch_range;
                const column = this.activeStitchColumns.find(item => item.label === stitchRange);

                if (!range || !stitchRange || !column) {
                    return { valid: false, message: 'Escolha uma faixa de pontos e preencha a tabela.' };
                }

                const unit = numeric(range.prices[column.key]);
                const digitizing = this.form.digitizing_charge_mode === 'SEPARATE'
                    ? numeric(this.form.digitizing_price)
                    : 0;

                if (unit <= 0) return { valid: false, message: 'Informe o preço por peça desta faixa de pontos.' };

                const piecesTotal = unit * quantity;
                const total = piecesTotal + digitizing;

                return {
                    valid: true,
                    quantity,
                    range,
                    stitchRange,
                    unit,
                    digitizing,
                    piecesTotal,
                    total,
                    average: total / quantity,
                    warning: unit > 500 || total > 100000,
                };
            },
        };
    };
</script>

<form
    method="POST"
    action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}"
    class="mt-6"
    x-data="estampaFlowEmbroideryWizard(@js($embroideryForm), {{ $embroideryStartStep }})"
    data-tour="pricing-guided-steps"
>
    @csrf
    @method('PUT')

    <template x-for="(item, index) in activeStitchColumns" :key="item.key">
        <span>
            <input type="hidden" name="selected_stitch_columns[]" :value="item.key">
            <input type="hidden" :name="`stitch_columns[${index}][key]`" :value="item.key">
            <input type="hidden" :name="`stitch_columns[${index}][label]`" :value="item.label">
        </span>
    </template>

    <section class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <div class="border-b border-line bg-brand-50 px-5 py-5 sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Configuração guiada</p>
                    <h2 class="mt-1 text-lg font-black text-ink-950">Bordado por quantidade e pontos</h2>
                </div>
                <p class="rounded-full bg-white px-4 py-2 text-xs font-extrabold text-ink-600">Etapa <span x-text="step"></span> de 4</p>
            </div>
            <div class="mt-5 grid grid-cols-4 gap-2">
                <template x-for="item in [1,2,3,4]" :key="item">
                    <button type="button" @click="goTo(item)" class="h-2 rounded-full" :class="item <= step ? 'bg-brand-500' : 'bg-line'" :aria-label="`Ir para etapa ${item}`"></button>
                </template>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            <div x-show="step === 1" x-cloak data-tour="pricing-embroidery-options">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 1</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Como você trabalha com bordado?</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">Escolha as faixas de pontos que usa e como cobra a criação da matriz.</p>

                <h4 class="mt-7 text-sm font-extrabold text-ink-950">Quais faixas de pontos você utiliza?</h4>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4" role="group" aria-label="Faixas de pontos utilizadas">
                    <template x-for="item in form.stitch_columns" :key="item.key">
                        <button
                            type="button"
                            role="checkbox"
                            :aria-checked="isStitchColumnSelected(item.key)"
                            @click="toggleStitchColumn(item.key)"
                            class="rounded-2xl border-2 p-4 text-left transition"
                            :class="isStitchColumnSelected(item.key) ? 'border-brand-500 bg-brand-50' : 'border-line bg-white hover:border-brand-200'"
                            data-pricing-option
                        >
                            <span class="flex items-center justify-between gap-3">
                                <strong class="text-sm text-ink-950" x-text="item.label"></strong>
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border text-xs font-black" :class="isStitchColumnSelected(item.key) ? 'border-brand-500 bg-brand-500 text-white' : 'border-line bg-white text-transparent'">✓</span>
                            </span>
                        </button>
                    </template>
                </div>
                <p x-show="selectionMessage" x-text="selectionMessage" class="mt-3 text-sm font-bold text-amber-700"></p>
                @error('stitch_columns')<p class="mt-3 text-sm font-bold text-red-600">{{ $message }}</p>@enderror

                <h4 class="mt-8 text-sm font-extrabold text-ink-950">Como você cobra a matriz/digitalização?</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-2" role="radiogroup" aria-label="Cobrança da matriz de bordado">
                    <label class="cursor-pointer rounded-2xl border-2 p-5 transition" :class="form.digitizing_charge_mode === 'INCLUDED' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                        <input type="radio" name="digitizing_charge_mode" value="INCLUDED" x-model="form.digitizing_charge_mode" class="sr-only">
                        <strong class="block text-base text-ink-950">Já incluo no preço por peça</strong>
                        <span class="mt-2 block text-sm leading-6 text-ink-500">A tabela da próxima etapa já cobre a preparação do arquivo.</span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border-2 p-5 transition" :class="form.digitizing_charge_mode === 'SEPARATE' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                        <input type="radio" name="digitizing_charge_mode" value="SEPARATE" x-model="form.digitizing_charge_mode" class="sr-only">
                        <strong class="block text-base text-ink-950">Cobro uma vez por pedido</strong>
                        <span class="mt-2 block text-sm leading-6 text-ink-500">O valor da matriz será somado separadamente ao total.</span>
                    </label>
                </div>
                @error('digitizing_charge_mode')<p class="mt-3 text-sm font-bold text-red-600">{{ $message }}</p>@enderror

                <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm leading-6 text-ink-600">Linha, entretela, tempo de máquina e perdas serão analisados quando os insumos e custos operacionais estiverem cadastrados.</div>
            </div>

            <div x-show="step === 2" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 2</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Preencha sua tabela atual</h3>
                <p class="mt-2 text-sm text-ink-500">Todos os valores são por peça. Só aparecem as faixas escolhidas.</p>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-line">
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="bg-app text-left text-xs font-extrabold text-ink-600">
                            <tr>
                                <th class="p-4">Quantidade</th>
                                <template x-for="item in activeStitchColumns" :key="item.key"><th class="p-4" x-text="item.label"></th></template>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            <template x-for="(range, rangeIndex) in form.ranges" :key="rangeIndex">
                                <tr>
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <input :name="`ranges[${rangeIndex}][min_quantity]`" x-model="range.min_quantity" type="number" min="1" class="field-input !w-24 !py-2">
                                            <span>até</span>
                                            <input :name="`ranges[${rangeIndex}][max_quantity]`" x-model="range.max_quantity" type="number" min="1" class="field-input !w-24 !py-2" placeholder="∞">
                                        </div>
                                    </td>
                                    <template x-for="item in activeStitchColumns" :key="item.key">
                                        <td class="p-3">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span>
                                                <input :name="`ranges[${rangeIndex}][prices][${item.key}]`" x-model="range.prices[item.key]" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00">
                                            </div>
                                        </td>
                                    </template>
                                    <td class="p-3 text-right"><button type="button" @click="removeRange(rangeIndex)" class="button-ghost text-xs">Remover</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="button" @click="addRange()" class="button-secondary mt-4">+ Adicionar faixa de quantidade</button>
            </div>

            <div x-show="step === 3" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 3</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Matriz e mais opções</h3>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label x-show="form.digitizing_charge_mode === 'SEPARATE'" x-cloak>
                        <span class="field-label">Valor da criação ou digitalização da matriz</span>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm text-ink-400">R$</span>
                            <input name="digitizing_price" x-model="form.digitizing_price" :disabled="form.digitizing_charge_mode !== 'SEPARATE'" inputmode="decimal" class="field-input pl-11" placeholder="50,00">
                        </div>
                        <span class="mt-2 block text-xs text-ink-400">Cobrado uma única vez no pedido.</span>
                    </label>
                    <input type="hidden" name="digitizing_price" value="0" :disabled="form.digitizing_charge_mode === 'SEPARATE'">
                    <div></div>
                    <label><span class="field-label">Usar esta tabela a partir de</span><input type="date" name="valid_from" x-model="form.valid_from" class="field-input"></label>
                    <label><span class="field-label">Usar até</span><input type="date" name="valid_until" x-model="form.valid_until" class="field-input"></label>
                </div>
            </div>

            <div x-show="step === 4" x-cloak data-tour="pricing-preview">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 4</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Teste antes de salvar</h3>
                <div class="mt-6 grid gap-4 rounded-2xl bg-app p-5 md:grid-cols-2">
                    <label><span class="field-label">Quantidade</span><input type="number" min="1" x-model="form.sample_quantity" class="field-input"></label>
                    <label>
                        <span class="field-label">Faixa de pontos</span>
                        <select x-model="form.sample_stitch_range" class="field-input">
                            <template x-for="item in activeStitchColumns" :key="item.key"><option :value="item.label" x-text="item.label"></option></template>
                        </select>
                    </label>
                </div>
                <div x-show="preview.valid" class="mt-6 rounded-2xl border border-line bg-white p-5">
                    <div class="flex justify-between gap-4 text-sm"><span class="text-ink-500">Faixa utilizada</span><strong x-text="rangeLabel(preview.range)"></strong></div>
                    <div class="mt-3 flex justify-between gap-4 text-sm"><span class="text-ink-500"><span x-text="preview.quantity"></span> peças × <span x-text="money(preview.unit)"></span></span><strong x-text="money(preview.piecesTotal)"></strong></div>
                    <div x-show="preview.digitizing > 0" class="mt-3 flex justify-between gap-4 text-sm"><span class="text-ink-500">Matriz/digitalização</span><strong x-text="money(preview.digitizing)"></strong></div>
                    <div class="mt-4 flex justify-between border-t border-line pt-4"><strong>Total do serviço</strong><strong class="text-xl text-brand-700" x-text="money(preview.total)"></strong></div>
                    <p class="mt-2 text-xs text-ink-400">Média de <span x-text="money(preview.average)"></span> por peça.</p>
                </div>
                <div x-show="!preview.valid" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" x-text="preview.message"></div>
                <div x-show="preview.warning" class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"><strong>Revise os valores.</strong> O resultado ficou muito alto para um exemplo comum.</div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-line px-5 py-5 sm:flex-row sm:justify-between sm:px-7">
            <button type="button" x-show="step > 1" @click="previous()" class="button-secondary">Voltar</button>
            <span x-show="step === 1"></span>
            <div class="flex justify-end">
                <button type="button" x-show="step < 4" @click="next()" class="button-primary">Continuar</button>
                <button type="submit" x-show="step === 4" class="button-primary">Salvar tabela</button>
            </div>
        </div>
    </section>
</form>
