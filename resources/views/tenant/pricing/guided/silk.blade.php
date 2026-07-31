@php
    $silkForm = [
        'setup_charge_mode' => old('setup_charge_mode', $guidedInitial['setup_charge_mode']),
        'setup_per_color' => old('setup_per_color', $guidedInitial['setup_per_color']),
        'white_base_mode' => old('white_base_mode', $guidedInitial['white_base_mode']),
        'white_base_addon' => old('white_base_addon', $guidedInitial['white_base_addon']),
        'colors' => old('colors', $guidedInitial['colors']),
        'ranges' => old('ranges', $guidedInitial['ranges']),
        'addons_enabled' => (bool) old('addons_enabled', $guidedInitial['addons_enabled']),
        'ink_addons' => old('ink_addons', $guidedInitial['ink_addons']),
        'effect_addons' => old('effect_addons', $guidedInitial['effect_addons']),
        'valid_from' => old('valid_from', $guidedInitial['valid_from']),
        'valid_until' => old('valid_until', $guidedInitial['valid_until']),
        'sample_quantity' => old('sample_quantity', $guidedInitial['sample_quantity']),
        'sample_colors' => old('sample_colors', $guidedInitial['sample_colors']),
        'sample_white_base' => old('sample_white_base', $guidedInitial['sample_white_base']),
        'sample_ink_system' => old('sample_ink_system', $guidedInitial['sample_ink_system']),
        'sample_print_effect' => old('sample_print_effect', $guidedInitial['sample_print_effect']),
    ];

    $silkStartStep = session('success') ? 4 : 1;
    if ($errors->hasAny(['colors', 'ranges', 'ranges.*'])) $silkStartStep = 2;
    if ($errors->hasAny(['addons_enabled', 'ink_addons', 'effect_addons', 'valid_from', 'valid_until'])) $silkStartStep = 3;
@endphp

<script>
    window.estampaFlowSilkWizard = function (initial, startStep) {
        const numeric = value => {
            const text = String(value ?? '').trim();
            const normalized = text.includes(',') ? text.replace(/\./g, '').replace(',', '.') : text;
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);

        return {
            step: startStep,
            form: {
                ...initial,
                colors: (initial.colors || [1, 2, 3, 4]).map(Number).sort((a, b) => a - b),
                ranges: (initial.ranges || []).map(range => ({
                    min_quantity: range.min_quantity ?? 1,
                    max_quantity: range.max_quantity ?? '',
                    prices: { ...(range.prices || {}) },
                })),
                ink_addons: (initial.ink_addons || []).map(item => ({ ...item })),
                effect_addons: (initial.effect_addons || []).map(item => ({ ...item })),
            },
            next() { this.step = Math.min(4, this.step + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            previous() { this.step = Math.max(1, this.step - 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
            goTo(target) { this.step = target; window.scrollTo({ top: 0, behavior: 'smooth' }); },
            addColor() {
                const next = Math.max(...this.form.colors, 0) + 1;
                if (next > 12) return;
                this.form.colors.push(next);
                this.form.ranges.forEach(range => range.prices[String(next)] = '');
            },
            removeColor(color) {
                if (this.form.colors.length <= 1) return;
                this.form.colors = this.form.colors.filter(item => item !== color);
                this.form.ranges.forEach(range => delete range.prices[String(color)]);
                if (Number(this.form.sample_colors) === Number(color)) this.form.sample_colors = this.form.colors[0];
            },
            addRange() {
                const last = this.form.ranges[this.form.ranges.length - 1];
                let nextMin = 1;
                if (last) {
                    if (last.max_quantity) {
                        nextMin = Number(last.max_quantity) + 1;
                    } else {
                        nextMin = Number(last.min_quantity || 1) + 50;
                        last.max_quantity = nextMin - 1;
                    }
                }
                const prices = {};
                this.form.colors.forEach(color => prices[String(color)] = '');
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
                }) || null;
            },
            addon(rows, selected) {
                const item = (rows || []).find(row => String(row.option) === String(selected));
                return item ? numeric(item.amount) : 0;
            },
            get preview() {
                const quantity = Math.max(1, Math.floor(Number(this.form.sample_quantity || 0)));
                const informedColors = Math.max(1, Math.floor(Number(this.form.sample_colors || 0)));
                const whiteBase = this.form.sample_white_base === 'Sim';
                const effectiveColors = informedColors + (whiteBase && this.form.white_base_mode === 'ADD_COLOR' ? 1 : 0);
                const range = this.rangeFor(quantity);

                if (!range) {
                    return { valid: false, message: 'Nenhuma faixa de quantidade atende a este exemplo.' };
                }

                if (!this.form.colors.includes(effectiveColors)) {
                    return { valid: false, message: `O exemplo precisa da coluna de ${effectiveColors} cores. Adicione essa coluna ou altere a base branca.` };
                }

                const baseUnit = numeric(range.prices[String(effectiveColors)]);
                if (baseUnit <= 0) {
                    return { valid: false, message: `Preencha o preço de ${effectiveColors} cores na faixa escolhida.` };
                }

                let addonUnit = 0;
                if (this.form.addons_enabled) {
                    addonUnit += this.addon(this.form.ink_addons, this.form.sample_ink_system);
                    addonUnit += this.addon(this.form.effect_addons, this.form.sample_print_effect);
                }
                if (whiteBase && this.form.white_base_mode === 'ADD_PER_ITEM') {
                    addonUnit += numeric(this.form.white_base_addon);
                }

                const finalUnit = baseUnit + addonUnit;
                const piecesTotal = finalUnit * quantity;
                const setupTotal = this.form.setup_charge_mode === 'SEPARATE'
                    ? numeric(this.form.setup_per_color) * effectiveColors
                    : 0;
                const total = piecesTotal + setupTotal;
                const average = total / quantity;

                return {
                    valid: true,
                    quantity,
                    informedColors,
                    effectiveColors,
                    whiteBaseCounted: whiteBase && this.form.white_base_mode === 'ADD_COLOR',
                    range,
                    baseUnit,
                    addonUnit,
                    finalUnit,
                    piecesTotal,
                    setupTotal,
                    total,
                    average,
                    warning: finalUnit > 500 || total > 100000,
                };
            },
            rangeLabel(range) {
                if (!range) return '';
                return range.max_quantity ? `${range.min_quantity} a ${range.max_quantity} peças` : `${range.min_quantity} ou mais peças`;
            },
            money,
        };
    };
</script>

<form
    method="POST"
    action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}"
    class="mt-6"
    x-data="estampaFlowSilkWizard(@js($silkForm), {{ $silkStartStep }})"
    data-tour="pricing-guided-steps"
>
    @csrf
    @method('PUT')
    <input type="hidden" name="setup_template" value="SILK_MATRIX">

    <section class="surface-card overflow-hidden">
        <div class="border-b border-line bg-app px-5 py-5 sm:px-7">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Configuração guiada</p>
                    <h2 class="mt-1 text-lg font-black text-ink-950">Silk por quantidade e número de cores</h2>
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
            <div x-show="step === 1" x-cloak data-tour="pricing-silk-method">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 1</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Como você cobra a preparação?</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">O preço principal será montado por quantidade de peças e número de cores.</p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="cursor-pointer rounded-2xl border-2 p-5 transition" :class="form.setup_charge_mode === 'INCLUDED' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                        <input type="radio" name="setup_charge_mode" value="INCLUDED" x-model="form.setup_charge_mode" class="sr-only">
                        <strong class="block text-base text-ink-950">Já incluo as telas no preço por peça</strong>
                        <span class="mt-2 block text-sm leading-6 text-ink-500">A tabela da próxima etapa já contém matriz, gravação e preparação.</span>
                    </label>

                    <label class="cursor-pointer rounded-2xl border-2 p-5 transition" :class="form.setup_charge_mode === 'SEPARATE' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                        <input type="radio" name="setup_charge_mode" value="SEPARATE" x-model="form.setup_charge_mode" class="sr-only">
                        <strong class="block text-base text-ink-950">Cobro uma taxa separada por tela/cor</strong>
                        <span class="mt-2 block text-sm leading-6 text-ink-500">O sistema soma a quantidade de cores ao valor fixo de preparação.</span>
                    </label>
                </div>

                <label x-show="form.setup_charge_mode === 'SEPARATE'" x-cloak class="mt-5 block max-w-md">
                    <span class="field-label">Valor cobrado por tela/cor</span>
                    <div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input name="setup_per_color" x-model="form.setup_per_color" inputmode="decimal" class="field-input pl-11" placeholder="30,00"></div>
                    <span class="mt-2 block text-xs leading-5 text-ink-400">Exemplo: 3 cores × R$ 30,00 = R$ 90,00 de preparação.</span>
                </label>

                <div class="mt-8">
                    <h4 class="text-sm font-extrabold text-ink-950">Quando houver base branca</h4>
                    <p class="mt-1 text-xs leading-5 text-ink-400">Escolha como você já costuma cobrar em peças escuras.</p>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.white_base_mode === 'ADD_COLOR' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                            <input type="radio" name="white_base_mode" value="ADD_COLOR" x-model="form.white_base_mode" class="sr-only">
                            <strong class="text-sm text-ink-950">Contar como uma cor adicional</strong>
                            <span class="mt-2 block text-xs leading-5 text-ink-400">2 cores + base branca usam a coluna de 3 cores.</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.white_base_mode === 'ADD_PER_ITEM' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                            <input type="radio" name="white_base_mode" value="ADD_PER_ITEM" x-model="form.white_base_mode" class="sr-only">
                            <strong class="text-sm text-ink-950">Cobrar adicional por peça</strong>
                            <span class="mt-2 block text-xs leading-5 text-ink-400">Mantém o número de cores e soma um valor.</span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border p-4" :class="form.white_base_mode === 'NONE' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'">
                            <input type="radio" name="white_base_mode" value="NONE" x-model="form.white_base_mode" class="sr-only">
                            <strong class="text-sm text-ink-950">Não alterar automaticamente</strong>
                            <span class="mt-2 block text-xs leading-5 text-ink-400">A base já está incluída na sua tabela.</span>
                        </label>
                    </div>

                    <label x-show="form.white_base_mode === 'ADD_PER_ITEM'" x-cloak class="mt-4 block max-w-md">
                        <span class="field-label">Adicional de base branca por peça</span>
                        <div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input name="white_base_addon" x-model="form.white_base_addon" inputmode="decimal" class="field-input pl-11" placeholder="1,50"></div>
                    </label>
                </div>
            </div>

            <div x-show="step === 2" x-cloak data-tour="pricing-silk-table">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 2</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Preencha sua tabela atual</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500"><strong>Todos os valores são por peça.</strong> Você pode adicionar ou remover faixas e colunas.</p>

                <template x-for="color in form.colors" :key="`hidden-color-${color}`">
                    <input type="hidden" name="colors[]" :value="color">
                </template>

                <div class="mt-6 overflow-x-auto rounded-2xl border border-line">
                    <table class="min-w-[760px] w-full border-collapse bg-white text-sm">
                        <thead class="bg-app">
                            <tr>
                                <th class="border-b border-r border-line px-4 py-4 text-left font-extrabold text-ink-800">Quantidade</th>
                                <template x-for="color in form.colors" :key="`head-${color}`">
                                    <th class="border-b border-r border-line px-3 py-3 text-center last:border-r-0">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="font-extrabold text-ink-800" x-text="color === 1 ? '1 cor' : `${color} cores`"></span>
                                            <button type="button" @click="removeColor(color)" x-show="form.colors.length > 1" class="text-xs font-black text-red-500" :aria-label="`Remover coluna de ${color} cores`">×</button>
                                        </div>
                                    </th>
                                </template>
                                <th class="w-12 border-b border-line"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(range, rangeIndex) in form.ranges" :key="`range-${rangeIndex}`">
                                <tr>
                                    <td class="border-b border-r border-line p-3 align-top">
                                        <div class="grid grid-cols-2 gap-2">
                                            <label><span class="block text-[10px] font-bold uppercase text-ink-400">De</span><input type="number" min="1" :name="`ranges[${rangeIndex}][min_quantity]`" x-model="range.min_quantity" class="field-input !px-3 !py-2"></label>
                                            <label><span class="block text-[10px] font-bold uppercase text-ink-400">Até</span><input type="number" min="1" :name="`ranges[${rangeIndex}][max_quantity]`" x-model="range.max_quantity" class="field-input !px-3 !py-2" placeholder="Sem limite"></label>
                                        </div>
                                    </td>
                                    <template x-for="color in form.colors" :key="`price-${rangeIndex}-${color}`">
                                        <td class="border-b border-r border-line p-3 last:border-r-0">
                                            <div class="relative"><span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span><input :name="`ranges[${rangeIndex}][prices][${color}]`" x-model="range.prices[String(color)]" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00"></div>
                                        </td>
                                    </template>
                                    <td class="border-b border-line p-2 text-center"><button type="button" @click="removeRange(rangeIndex)" x-show="form.ranges.length > 1" class="button-ghost !px-2 text-red-600" aria-label="Remover faixa">×</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" @click="addRange()" class="button-secondary">+ Adicionar faixa</button>
                    <button type="button" @click="addColor()" class="button-secondary">+ Adicionar número de cores</button>
                </div>

                <div class="mt-5 rounded-2xl bg-app p-4 text-sm leading-6 text-ink-600">
                    Exemplo: na linha <strong>20 a 49</strong> e coluna <strong>2 cores</strong>, informe quanto você cobra por uma peça desse pedido.
                </div>
            </div>

            <div x-show="step === 3" x-cloak data-tour="pricing-silk-addons">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 3</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Tintas e efeitos</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Só preencha esta parte quando você realmente cobra valores diferentes.</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2" role="radiogroup" aria-label="Cobrança de adicionais">
                    <button
                        type="button"
                        role="radio"
                        :aria-checked="!form.addons_enabled"
                        @click="form.addons_enabled = false"
                        class="rounded-2xl border-2 p-5 text-left"
                        :class="!form.addons_enabled ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'"
                    >
                        <strong class="block text-base text-ink-950">Não cobro adicionais</strong>
                        <span class="mt-2 block text-sm text-ink-500">Minha tabela já contém as tintas e efeitos.</span>
                    </button>
                    <button
                        type="button"
                        role="radio"
                        :aria-checked="form.addons_enabled"
                        @click="form.addons_enabled = true"
                        class="rounded-2xl border-2 p-5 text-left"
                        :class="form.addons_enabled ? 'border-brand-500 bg-brand-50' : 'border-line bg-white'"
                    >
                        <strong class="block text-base text-ink-950">Cobro alguns adicionais</strong>
                        <span class="mt-2 block text-sm text-ink-500">Quero informar valores por tinta ou efeito.</span>
                    </button>
                </div>
                <input type="hidden" name="addons_enabled" :value="form.addons_enabled ? 1 : 0">

                <div x-show="form.addons_enabled" x-cloak class="mt-6 grid gap-6 xl:grid-cols-2">
                    <section class="rounded-2xl border border-line bg-white p-5">
                        <h4 class="font-extrabold text-ink-950">Sistema de tinta</h4>
                        <p class="mt-1 text-xs leading-5 text-ink-400">Deixe vazio quando não houver adicional.</p>
                        <div class="mt-4 space-y-3">
                            <template x-for="(item, index) in form.ink_addons" :key="`ink-${index}`">
                                <label class="grid grid-cols-[1fr_150px] items-center gap-3">
                                    <span class="text-sm font-semibold text-ink-700" x-text="item.option"></span>
                                    <span class="relative"><input type="hidden" :name="`ink_addons[${index}][option]`" :value="item.option"><span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span><input :name="`ink_addons[${index}][amount]`" x-model="item.amount" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00"></span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-line bg-white p-5">
                        <h4 class="font-extrabold text-ink-950">Efeito ou acabamento</h4>
                        <p class="mt-1 text-xs leading-5 text-ink-400">Informe somente o que sua estamparia oferece.</p>
                        <div class="mt-4 max-h-[420px] space-y-3 overflow-y-auto pr-1">
                            <template x-for="(item, index) in form.effect_addons" :key="`effect-${index}`">
                                <label class="grid grid-cols-[1fr_150px] items-center gap-3">
                                    <span class="text-sm font-semibold text-ink-700" x-text="item.option"></span>
                                    <span class="relative"><input type="hidden" :name="`effect_addons[${index}][option]`" :value="item.option"><span class="absolute left-3 top-2.5 text-xs text-ink-400">R$</span><input :name="`effect_addons[${index}][amount]`" x-model="item.amount" inputmode="decimal" class="field-input !py-2 pl-9" placeholder="0,00"></span>
                                </label>
                            </template>
                        </div>
                    </section>
                </div>

                <details class="mt-6 rounded-2xl border border-line bg-white">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-extrabold text-ink-800">Mais opções</summary>
                    <div class="grid gap-5 border-t border-line p-5 md:grid-cols-2">
                        <label><span class="field-label">Usar esta tabela a partir de</span><input type="date" name="valid_from" x-model="form.valid_from" class="field-input"></label>
                        <label><span class="field-label">Usar até</span><input type="date" name="valid_until" x-model="form.valid_until" class="field-input"></label>
                    </div>
                </details>
            </div>

            <div x-show="step === 4" x-cloak data-tour="pricing-preview">
                <p class="text-xs font-extrabold uppercase tracking-[.14em] text-brand-600">Etapa 4</p>
                <h3 class="mt-2 text-2xl font-black text-ink-950">Teste um pedido antes de salvar</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">O cálculo abaixo usa exatamente a tabela e os adicionais preenchidos.</p>

                <div class="mt-6 grid gap-4 rounded-2xl bg-app p-5 md:grid-cols-2 xl:grid-cols-5">
                    <label><span class="field-label">Quantidade</span><input type="number" min="1" x-model="form.sample_quantity" class="field-input"></label>
                    <label><span class="field-label">Cores da arte</span><input type="number" min="1" max="20" x-model="form.sample_colors" class="field-input"></label>
                    <label><span class="field-label">Base branca</span><select x-model="form.sample_white_base" class="field-input"><option>Não</option><option>Sim</option></select></label>
                    <label><span class="field-label">Sistema de tinta</span><select x-model="form.sample_ink_system" class="field-input"><option value="">Sem adicional</option>@foreach($silkOptions['ink_system'] ?? [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
                    <label><span class="field-label">Efeito</span><select x-model="form.sample_print_effect" class="field-input"><option value="">Sem adicional</option>@foreach($silkOptions['print_effect'] ?? [] as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
                </div>

                <div x-show="preview.valid" class="mt-6">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-line bg-white p-4"><p class="text-xs font-bold text-ink-400">Faixa utilizada</p><p class="mt-2 text-lg font-black text-ink-950" x-text="rangeLabel(preview.range)"></p></div>
                        <div class="rounded-2xl border border-line bg-white p-4"><p class="text-xs font-bold text-ink-400">Cores consideradas</p><p class="mt-2 text-2xl font-black text-ink-950"><span x-text="preview.effectiveColors"></span></p><p x-show="preview.whiteBaseCounted" class="mt-1 text-xs text-ink-400">Inclui a base branca</p></div>
                        <div class="rounded-2xl border border-brand-200 bg-brand-50 p-4"><p class="text-xs font-bold text-brand-700">Preço médio por peça</p><p class="mt-2 text-2xl font-black text-ink-950" x-text="money(preview.average)"></p></div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-line bg-white p-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4"><span class="text-ink-500">Preço da tabela por peça</span><strong x-text="money(preview.baseUnit)"></strong></div>
                            <div class="flex justify-between gap-4" x-show="preview.addonUnit > 0"><span class="text-ink-500">Adicionais por peça</span><strong x-text="money(preview.addonUnit)"></strong></div>
                            <div class="flex justify-between gap-4"><span class="text-ink-500"><span x-text="preview.quantity"></span> peças × <span x-text="money(preview.finalUnit)"></span></span><strong x-text="money(preview.piecesTotal)"></strong></div>
                            <div class="flex justify-between gap-4" x-show="preview.setupTotal > 0"><span class="text-ink-500">Preparação das telas</span><strong x-text="money(preview.setupTotal)"></strong></div>
                            <div class="flex justify-between gap-4 border-t border-line pt-3 text-base"><strong>Total do serviço</strong><strong class="text-xl text-brand-700" x-text="money(preview.total)"></strong></div>
                        </div>
                    </div>

                    <div x-show="preview.warning" class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        <strong>Revise os valores.</strong> O resultado ficou muito acima de um pedido comum. Confira se algum preço foi digitado como total em vez de preço por peça.
                    </div>
                </div>

                <div x-show="!preview.valid" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                    <strong>O exemplo ainda não pode ser calculado.</strong>
                    <span x-text="preview.message"></span>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-line bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <button type="button" x-show="step > 1" @click="previous()" class="button-secondary">Voltar</button>
            <span x-show="step === 1" class="hidden sm:block"></span>
            <div class="flex justify-end gap-3">
                <button type="button" x-show="step < 4" @click="next()" class="button-primary">Continuar</button>
                <button type="submit" x-show="step === 4" class="button-primary">Salvar tabela de preços</button>
            </div>
        </div>
    </section>
</form>
