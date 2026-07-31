@php
    $embroideryForm = [
        'stitch_columns' => old('stitch_columns', $guidedInitial['stitch_columns']),
        'digitizing_price' => old('digitizing_price', $guidedInitial['digitizing_price']),
        'ranges' => old('ranges', $guidedInitial['ranges']),
        'valid_from' => old('valid_from', $guidedInitial['valid_from']),
        'valid_until' => old('valid_until', $guidedInitial['valid_until']),
        'sample_quantity' => old('sample_quantity', $guidedInitial['sample_quantity']),
        'sample_stitch_range' => old('sample_stitch_range', $guidedInitial['sample_stitch_range']),
    ];
    $embroideryStartStep = session('success') ? 4 : 1;
    if ($errors->hasAny(['ranges', 'ranges.*', 'stitch_columns'])) $embroideryStartStep = 2;
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
            form: { ...initial, ranges: (initial.ranges || []).map(range => ({ min_quantity: range.min_quantity ?? 1, max_quantity: range.max_quantity ?? '', prices: { ...(range.prices || {}) } })) },
            money,
            next() { this.step = Math.min(4, this.step + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            previous() { this.step = Math.max(1, this.step - 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            goTo(target) { this.step = target; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            addRange() {
                const last = this.form.ranges[this.form.ranges.length - 1]; let nextMin = 1;
                if (last) { nextMin = last.max_quantity ? Number(last.max_quantity) + 1 : Number(last.min_quantity || 1) + 30; if (!last.max_quantity) last.max_quantity = nextMin - 1; }
                const prices = {}; this.form.stitch_columns.forEach(item => prices[item.key] = '');
                this.form.ranges.push({ min_quantity: nextMin, max_quantity: '', prices });
            },
            removeRange(index) { if (this.form.ranges.length > 1) this.form.ranges.splice(index, 1); },
            rangeFor(quantity) { return this.form.ranges.find(range => { const min = Number(range.min_quantity || 0); const max = range.max_quantity === '' || range.max_quantity === null ? null : Number(range.max_quantity); return quantity >= min && (max === null || quantity <= max); }); },
            rangeLabel(range) { return !range ? 'Sem faixa' : (range.max_quantity ? `${range.min_quantity} a ${range.max_quantity} peças` : `${range.min_quantity} ou mais`); },
            get preview() {
                const quantity = Math.max(1, Number(this.form.sample_quantity || 0)); const range = this.rangeFor(quantity); const stitchRange = this.form.sample_stitch_range; const column = this.form.stitch_columns.find(item => item.label === stitchRange);
                if (!range || !stitchRange || !column) return { valid: false, message: 'Escolha a faixa de pontos e preencha a tabela.' };
                const unit = numeric(range.prices[column.key]); const digitizing = numeric(this.form.digitizing_price);
                if (unit <= 0) return { valid: false, message: 'Informe o preço por peça desta faixa de pontos.' };
                const piecesTotal = unit * quantity; const total = piecesTotal + digitizing;
                return { valid: true, quantity, range, stitchRange, unit, digitizing, piecesTotal, total, average: total / quantity, warning: unit > 500 || total > 100000 };
            },
        };
    };
</script>

<form method="POST" action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}" class="mt-6" x-data="estampaFlowEmbroideryWizard(@js($embroideryForm), {{ $embroideryStartStep }})" data-tour="pricing-guided-steps">
    @csrf
    @method('PUT')
    <template x-for="(item, index) in form.stitch_columns" :key="item.key"><span><input type="hidden" :name="`stitch_columns[${index}][key]`" :value="item.key"><input type="hidden" :name="`stitch_columns[${index}][label]`" :value="item.label"></span></template>

    <section class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <div class="border-b border-line bg-brand-50 px-5 py-5 sm:px-7">
            <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Configuração guiada</p><h2 class="mt-1 text-lg font-black text-ink-950">Bordado por quantidade e pontos</h2></div><p class="rounded-full bg-white px-4 py-2 text-xs font-extrabold text-ink-600">Etapa <span x-text="step"></span> de 4</p></div>
            <div class="mt-5 grid grid-cols-4 gap-2"><template x-for="item in [1,2,3,4]" :key="item"><button type="button" @click="goTo(item)" class="h-2 rounded-full" :class="item <= step ? 'bg-brand-500' : 'bg-line'"></button></template></div>
        </div>

        <div class="p-5 sm:p-7">
            <div x-show="step === 1" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 1</p><h3 class="mt-2 text-2xl font-black text-ink-950">Como o bordado será cobrado?</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">Use as faixas de pontos que sua máquina ou software já informa. Você só precisa preencher o preço por peça.</p>
                <div class="mt-6 grid gap-4 md:grid-cols-2"><div class="rounded-2xl border-2 border-brand-500 bg-brand-50 p-5"><strong class="text-ink-950">Quantidade × faixa de pontos</strong><p class="mt-2 text-sm leading-6 text-ink-600">O preço muda conforme a quantidade do pedido e a complexidade do bordado.</p></div><div class="rounded-2xl border border-line bg-app p-5"><strong class="text-ink-950">Matriz/digitalização separada</strong><p class="mt-2 text-sm leading-6 text-ink-600">Pode ser cobrada uma vez por pedido, na etapa 3.</p></div></div>
                <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm leading-6 text-ink-600">Linha, entretela, tempo de máquina e perdas serão analisados quando os insumos e custos operacionais estiverem cadastrados.</div>
            </div>

            <div x-show="step === 2" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 2</p><h3 class="mt-2 text-2xl font-black text-ink-950">Preencha sua tabela atual</h3><p class="mt-2 text-sm text-ink-500">Todos os valores são por peça.</p>
                <div class="mt-6 overflow-x-auto rounded-2xl border border-line"><table class="min-w-[950px] w-full text-sm"><thead class="bg-app text-left text-xs font-extrabold text-ink-600"><tr><th class="p-4">Quantidade</th><template x-for="item in form.stitch_columns" :key="item.key"><th class="p-4" x-text="item.label"></th></template><th class="p-4"></th></tr></thead><tbody class="divide-y divide-line"><template x-for="(range, rangeIndex) in form.ranges" :key="rangeIndex"><tr><td class="p-3"><div class="flex items-center gap-2"><input :name="`ranges[${rangeIndex}][min_quantity]`" x-model="range.min_quantity" type="number" min="1" class="field-input !w-24 !py-2"><span>até</span><input :name="`ranges[${rangeIndex}][max_quantity]`" x-model="range.max_quantity" type="number" min="1" class="field-input !w-24 !py-2" placeholder="∞"></div></td><template x-for="item in form.stitch_columns" :key="item.key"><td class="p-3"><div class="relative"><span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span><input :name="`ranges[${rangeIndex}][prices][${item.key}]`" x-model="range.prices[item.key]" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00"></div></td></template><td class="p-3 text-right"><button type="button" @click="removeRange(rangeIndex)" class="button-ghost text-xs">Remover</button></td></tr></template></tbody></table></div>
                <button type="button" @click="addRange()" class="button-secondary mt-4">+ Adicionar faixa</button>
            </div>

            <div x-show="step === 3" x-cloak>
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 3</p><h3 class="mt-2 text-2xl font-black text-ink-950">Matriz e mais opções</h3>
                <div class="mt-6 grid gap-5 md:grid-cols-2"><label><span class="field-label">Criação ou digitalização da matriz</span><div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input name="digitizing_price" x-model="form.digitizing_price" inputmode="decimal" class="field-input pl-11" placeholder="0,00"></div><span class="mt-2 block text-xs text-ink-400">Cobrada uma vez por pedido. Deixe vazio quando já estiver incluída.</span></label><div></div><label><span class="field-label">Usar esta tabela a partir de</span><input type="date" name="valid_from" x-model="form.valid_from" class="field-input"></label><label><span class="field-label">Usar até</span><input type="date" name="valid_until" x-model="form.valid_until" class="field-input"></label></div>
            </div>

            <div x-show="step === 4" x-cloak data-tour="pricing-preview">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 4</p><h3 class="mt-2 text-2xl font-black text-ink-950">Teste antes de salvar</h3>
                <div class="mt-6 grid gap-4 rounded-2xl bg-app p-5 md:grid-cols-2"><label><span class="field-label">Quantidade</span><input type="number" min="1" x-model="form.sample_quantity" class="field-input"></label><label><span class="field-label">Faixa de pontos</span><select x-model="form.sample_stitch_range" class="field-input"><template x-for="item in form.stitch_columns" :key="item.key"><option :value="item.label" x-text="item.label"></option></template></select></label></div>
                <div x-show="preview.valid" class="mt-6 rounded-2xl border border-line bg-white p-5"><div class="flex justify-between gap-4 text-sm"><span class="text-ink-500">Faixa utilizada</span><strong x-text="rangeLabel(preview.range)"></strong></div><div class="mt-3 flex justify-between gap-4 text-sm"><span class="text-ink-500"><span x-text="preview.quantity"></span> peças × <span x-text="money(preview.unit)"></span></span><strong x-text="money(preview.piecesTotal)"></strong></div><div x-show="preview.digitizing > 0" class="mt-3 flex justify-between gap-4 text-sm"><span class="text-ink-500">Matriz/digitalização</span><strong x-text="money(preview.digitizing)"></strong></div><div class="mt-4 flex justify-between border-t border-line pt-4"><strong>Total do serviço</strong><strong class="text-xl text-brand-700" x-text="money(preview.total)"></strong></div><p class="mt-2 text-xs text-ink-400">Média de <span x-text="money(preview.average)"></span> por peça.</p></div>
                <div x-show="!preview.valid" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" x-text="preview.message"></div><div x-show="preview.warning" class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"><strong>Revise os valores.</strong> O resultado ficou muito alto para um exemplo comum.</div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-line px-5 py-5 sm:flex-row sm:justify-between sm:px-7"><button type="button" x-show="step > 1" @click="previous()" class="button-secondary">Voltar</button><span x-show="step === 1"></span><div class="flex justify-end"><button type="button" x-show="step < 4" @click="next()" class="button-primary">Continuar</button><button type="submit" x-show="step === 4" class="button-primary">Salvar tabela</button></div></div>
    </section>
</form>
