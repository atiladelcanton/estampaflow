@php
    $sublimationForm = [
        'categories' => $guidedInitial['categories'],
        'ranges' => old('ranges', $guidedInitial['ranges']),
        'valid_from' => old('valid_from', $guidedInitial['valid_from']),
        'valid_until' => old('valid_until', $guidedInitial['valid_until']),
        'sample_quantity' => old('sample_quantity', $guidedInitial['sample_quantity']),
        'sample_category' => old('sample_category', $guidedInitial['sample_category']),
    ];
    $sublimationStartStep = session('success') ? 4 : 1;
    if ($errors->hasAny(['ranges', 'ranges.*'])) $sublimationStartStep = 2;
    if ($errors->hasAny(['valid_from', 'valid_until'])) $sublimationStartStep = 3;
@endphp

<script>
    window.estampaFlowSublimationWizard = function (initial, startStep) {
        const numeric = value => {
            const normalized = String(value ?? '').trim().replace(/\./g, '').replace(',', '.');
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);

        return {
            step: startStep,
            form: {
                ...initial,
                ranges: (initial.ranges || []).map(range => ({
                    min_quantity: range.min_quantity ?? 1,
                    max_quantity: range.max_quantity ?? '',
                    prices: { ...(range.prices || {}) },
                })),
            },
            money,
            next() { this.step = Math.min(4, this.step + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            previous() { this.step = Math.max(1, this.step - 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            goTo(target) { this.step = target; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            addRange() {
                const last = this.form.ranges[this.form.ranges.length - 1];
                let nextMin = 1;
                if (last) {
                    nextMin = last.max_quantity ? Number(last.max_quantity) + 1 : Number(last.min_quantity || 1) + 30;
                    if (!last.max_quantity) last.max_quantity = nextMin - 1;
                }
                const prices = {};
                this.form.categories.forEach(category => prices[category.key] = '');
                this.form.ranges.push({ min_quantity: nextMin, max_quantity: '', prices });
            },
            removeRange(index) { if (this.form.ranges.length > 1) this.form.ranges.splice(index, 1); },
            rangeFor(quantity) {
                return this.form.ranges.find(range => {
                    const min = Number(range.min_quantity || 0);
                    const max = range.max_quantity === '' || range.max_quantity === null ? null : Number(range.max_quantity);
                    return quantity >= min && (max === null || quantity <= max);
                });
            },
            rangeLabel(range) {
                if (!range) return 'Sem faixa';
                return range.max_quantity ? `${range.min_quantity} a ${range.max_quantity} peças` : `${range.min_quantity} ou mais`;
            },
            get preview() {
                const quantity = Math.max(1, Number(this.form.sample_quantity || 0));
                const range = this.rangeFor(quantity);
                const category = this.form.categories.find(item => item.key === this.form.sample_category);
                if (!range || !category) return { valid: false, message: 'Escolha uma categoria e preencha a faixa correspondente.' };
                const unit = numeric(range.prices[category.key]);
                if (unit <= 0) return { valid: false, message: 'Informe o preço por peça desta categoria.' };
                return { valid: true, quantity, range, category, unit, total: unit * quantity, warning: unit > 500 || unit * quantity > 100000 };
            },
        };
    };
</script>

<form
    method="POST"
    action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}"
    class="mt-6"
    x-data="estampaFlowSublimationWizard(@js($sublimationForm), {{ $sublimationStartStep }})"
    data-tour="pricing-guided-steps"
>
    @csrf
    @method('PUT')

    <section class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <div class="border-b border-line bg-brand-50 px-5 py-5 sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Configuração guiada</p>
                    <h2 class="mt-1 text-lg font-black text-ink-950">Sublimação por quantidade e tipo</h2>
                </div>
                <p class="rounded-full bg-white px-4 py-2 text-xs font-extrabold text-ink-600">Etapa <span x-text="step"></span> de 4</p>
            </div>
            <div class="mt-5 grid grid-cols-4 gap-2">
                <template x-for="item in [1,2,3,4]" :key="item"><button type="button" @click="goTo(item)" class="h-2 rounded-full" :class="item <= step ? 'bg-brand-500' : 'bg-line'"></button></template>
            </div>
        </div>

        <div class="p-5 sm:p-7">
            <div x-show="step === 1" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 1</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Como esta tabela funciona?</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">Você informa quanto cobra por peça em cada quantidade. Não precisa calcular tinta, papel, prensa ou área nesta tela.</p>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <template x-for="category in form.categories" :key="category.key">
                        <div class="rounded-2xl border border-line bg-app p-5"><strong class="text-ink-950" x-text="category.label"></strong><p class="mt-2 text-xs leading-5 text-ink-500" x-text="category.modality === 'Total' ? 'Peça com cobertura total.' : 'Aplicação localizada em tamanho comercial.'"></p></div>
                    </template>
                </div>
                <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm leading-6 text-ink-600"><strong class="text-ink-950">Depois, com os insumos cadastrados,</strong> o EstampaFlow poderá comparar esta tabela com tinta, papel, produto, prensa, energia e perdas.</div>
            </div>

            <div x-show="step === 2" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 2</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Preencha sua tabela atual</h3>
                <p class="mt-2 text-sm text-ink-500">Todos os valores são por peça.</p>
                <div class="mt-6 overflow-x-auto rounded-2xl border border-line">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-app text-left text-xs font-extrabold text-ink-600"><tr><th class="p-4">Quantidade</th><template x-for="category in form.categories" :key="category.key"><th class="p-4" x-text="category.label"></th></template><th class="p-4"></th></tr></thead>
                        <tbody class="divide-y divide-line">
                            <template x-for="(range, rangeIndex) in form.ranges" :key="rangeIndex">
                                <tr>
                                    <td class="p-3"><div class="flex items-center gap-2"><input :name="`ranges[${rangeIndex}][min_quantity]`" x-model="range.min_quantity" type="number" min="1" class="field-input !w-24 !py-2"><span>até</span><input :name="`ranges[${rangeIndex}][max_quantity]`" x-model="range.max_quantity" type="number" min="1" class="field-input !w-24 !py-2" placeholder="∞"></div></td>
                                    <template x-for="category in form.categories" :key="category.key"><td class="p-3"><div class="relative"><span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span><input :name="`ranges[${rangeIndex}][prices][${category.key}]`" x-model="range.prices[category.key]" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00"></div></td></template>
                                    <td class="p-3 text-right"><button type="button" @click="removeRange(rangeIndex)" class="button-ghost text-xs">Remover</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="button" @click="addRange()" class="button-secondary mt-4">+ Adicionar faixa</button>
            </div>

            <div x-show="step === 3" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 3</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Mais opções</h3>
                <p class="mt-2 text-sm text-ink-500">Pode deixar tudo vazio e continuar.</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label><span class="field-label">Usar esta tabela a partir de</span><input type="date" name="valid_from" x-model="form.valid_from" class="field-input"></label>
                    <label><span class="field-label">Usar até</span><input type="date" name="valid_until" x-model="form.valid_until" class="field-input"></label>
                </div>
                <div class="mt-6 rounded-2xl bg-app p-4 text-sm text-ink-600">Tamanhos, produtos e modalidades adicionais poderão ser configurados no modo avançado sem alterar o motor interno.</div>
            </div>

            <div x-show="step === 4" x-cloak data-tour="pricing-preview">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 4</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Teste antes de salvar</h3>
                <div class="mt-6 grid gap-4 rounded-2xl bg-app p-5 md:grid-cols-2">
                    <label><span class="field-label">Quantidade</span><input type="number" min="1" x-model="form.sample_quantity" class="field-input"></label>
                    <label><span class="field-label">Tipo da sublimação</span><select name="sample_category" x-model="form.sample_category" class="field-input"><template x-for="category in form.categories" :key="category.key"><option :value="category.key" x-text="category.label"></option></template></select></label>
                </div>
                <div x-show="preview.valid" class="mt-6 rounded-2xl border border-line bg-white p-5">
                    <div class="flex justify-between gap-4 text-sm"><span class="text-ink-500">Faixa utilizada</span><strong x-text="rangeLabel(preview.range)"></strong></div>
                    <div class="mt-3 flex justify-between gap-4 text-sm"><span class="text-ink-500"><span x-text="preview.quantity"></span> peças × <span x-text="money(preview.unit)"></span></span><strong x-text="money(preview.total)"></strong></div>
                    <div class="mt-4 flex justify-between border-t border-line pt-4"><strong>Total do serviço</strong><strong class="text-xl text-brand-700" x-text="money(preview.total)"></strong></div>
                </div>
                <div x-show="!preview.valid" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" x-text="preview.message"></div>
                <div x-show="preview.warning" class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"><strong>Revise os valores.</strong> O resultado ficou muito alto para um exemplo comum.</div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-line px-5 py-5 sm:flex-row sm:justify-between sm:px-7">
            <button type="button" x-show="step > 1" @click="previous()" class="button-secondary">Voltar</button><span x-show="step === 1"></span>
            <div class="flex justify-end"><button type="button" x-show="step < 4" @click="next()" class="button-primary">Continuar</button><button type="submit" x-show="step === 4" class="button-primary">Salvar tabela</button></div>
        </div>
    </section>
</form>
