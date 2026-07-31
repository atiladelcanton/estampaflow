    <script>
        window.estampaFlowPricingEditor = function (initialRules, initialStrategy) {
            let sequence = 0;
            const uid = () => `price-rule-${Date.now()}-${++sequence}`;
            const emptyCondition = () => ({ client_id: uid(), parameter: '', operator: 'eq', value: '' });
            const normalizeRule = (rule = {}) => ({
                client_id: rule.client_id || uid(),
                name: rule.name || 'Nova regra',
                min_quantity: rule.min_quantity ?? 1,
                max_quantity: rule.max_quantity ?? '',
                unit_price: rule.unit_price ?? '',
                rate_value: rule.rate_value ?? '',
                setup_price: rule.setup_price ?? '0,00',
                minimum_price: rule.minimum_price ?? '0,00',
                priority: rule.priority ?? 100,
                conditions: Array.isArray(rule.conditions) ? rule.conditions.map(condition => ({ client_id: uid(), ...condition })) : [],
            });

            return {
                strategy: initialStrategy,
                rules: Array.isArray(initialRules) ? initialRules.map(normalizeRule) : [normalizeRule()],
                addRule() { this.rules.push(normalizeRule({ name: `Regra ${this.rules.length + 1}` })); },
                removeRule(index) { if (this.rules.length > 1) this.rules.splice(index, 1); },
                addCondition(rule) { rule.conditions.push(emptyCondition()); },
                removeCondition(rule, index) { rule.conditions.splice(index, 1); },
                usesUnitPrice() { return ['UNIT', 'QUANTITY_TIER', 'MATRIX'].includes(this.strategy); },
                usesRate() { return ['AREA', 'STITCH_RANGE'].includes(this.strategy); },
            };
        };
    </script>

    <div class="page-shell" x-data="estampaFlowPricingEditor(@js($initialRules), @js(old('strategy', $table?->strategy->value ?? $serviceType->pricing_strategy?->value ?? 'UNIT')))" data-tour="pricing-editor">

        <form method="POST" action="{{ route('tenant.pricing.update', ['serviceType' => $serviceType->id]) }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <section class="surface-card p-5 sm:p-6" data-tour="pricing-strategy">
                <h2 class="text-sm font-extrabold text-ink-950">Como este serviço é cobrado?</h2>
                <p class="mt-1 text-xs leading-5 text-ink-400">Escolha a opção mais próxima da forma como sua estamparia já trabalha.</p>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    @foreach($strategies as $strategy)
                        <label class="cursor-pointer rounded-2xl border p-4 transition" :class="strategy === '{{ $strategy->value }}' ? 'border-brand-500 bg-brand-50' : 'border-line bg-white hover:border-brand-300'">
                            <input type="radio" name="strategy" value="{{ $strategy->value }}" x-model="strategy" class="sr-only">
                            <span class="block text-sm font-extrabold text-ink-900">{{ $strategy->label() }}</span>
                            <span class="mt-2 block text-[11px] leading-5 text-ink-400">
                                {{ match($strategy->value) {
                                    'UNIT' => 'Mesmo valor por peça.',
                                    'QUANTITY_TIER' => 'Preço muda conforme a quantidade.',
                                    'AREA' => 'Calculado por largura × altura.',
                                    'MATRIX' => 'Preço muda por tinta, efeito, cores ou outros campos.',
                                    'STITCH_RANGE' => 'Calculado pela quantidade de pontos.',
                                } }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div x-show="strategy === 'AREA'" x-cloak class="mt-5 grid gap-4 rounded-2xl bg-app p-4 sm:grid-cols-2">
                    <label><span class="field-label">Campo de largura</span><select name="width_parameter" class="field-input"><option value="">Selecione</option>@foreach($pricingParameters as $parameter)<option value="{{ $parameter->key }}" @selected(old('width_parameter', $initialSettings['width_parameter'] ?? '') === $parameter->key)>{{ $parameter->label }}</option>@endforeach</select></label>
                    <label><span class="field-label">Campo de altura</span><select name="height_parameter" class="field-input"><option value="">Selecione</option>@foreach($pricingParameters as $parameter)<option value="{{ $parameter->key }}" @selected(old('height_parameter', $initialSettings['height_parameter'] ?? '') === $parameter->key)>{{ $parameter->label }}</option>@endforeach</select></label>
                </div>

                <div x-show="strategy === 'STITCH_RANGE'" x-cloak class="mt-5 rounded-2xl bg-app p-4">
                    <label class="block max-w-md"><span class="field-label">Campo com a quantidade de pontos</span><select name="stitch_parameter" class="field-input"><option value="">Selecione</option>@foreach($pricingParameters as $parameter)<option value="{{ $parameter->key }}" @selected(old('stitch_parameter', $initialSettings['stitch_parameter'] ?? '') === $parameter->key)>{{ $parameter->label }}</option>@endforeach</select></label>
                </div>
            </section>

            <section class="space-y-4" data-tour="pricing-rules">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div><h2 class="text-sm font-extrabold text-ink-950">Regras de preço</h2><p class="mt-1 text-xs text-ink-400">Uma regra pode representar uma faixa de quantidade ou uma combinação específica.</p></div>
                    <button type="button" @click="addRule()" class="button-secondary">+ Adicionar regra</button>
                </div>

                <template x-for="(rule, ruleIndex) in rules" :key="rule.client_id">
                    <article class="surface-card overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-app px-5 py-4">
                            <input :name="`rules[${ruleIndex}][name]`" x-model="rule.name" class="field-input max-w-sm !py-2 font-bold" aria-label="Nome da regra">
                            <button type="button" @click="removeRule(ruleIndex)" x-show="rules.length > 1" class="button-ghost text-red-600">Remover</button>
                        </div>

                        <div class="grid gap-5 p-5 lg:grid-cols-12">
                            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-4">
                                <label><span class="field-label">A partir de</span><input type="number" min="1" :name="`rules[${ruleIndex}][min_quantity]`" x-model="rule.min_quantity" class="field-input"></label>
                                <label><span class="field-label">Até</span><input type="number" min="1" :name="`rules[${ruleIndex}][max_quantity]`" x-model="rule.max_quantity" class="field-input" placeholder="Sem limite"></label>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3 lg:col-span-8">
                                <label x-show="usesUnitPrice()"><span class="field-label">Preço por peça</span><div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input :name="`rules[${ruleIndex}][unit_price]`" x-model="rule.unit_price" class="field-input pl-11" placeholder="0,00"></div></label>
                                <label x-show="usesRate()"><span class="field-label" x-text="strategy === 'AREA' ? 'Valor por cm²' : 'Valor por mil pontos'"></span><div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input :name="`rules[${ruleIndex}][rate_value]`" x-model="rule.rate_value" class="field-input pl-11" placeholder="0,00000000"></div></label>
                                <label><span class="field-label">Custo de preparação</span><div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input :name="`rules[${ruleIndex}][setup_price]`" x-model="rule.setup_price" class="field-input pl-11"></div></label>
                                <label><span class="field-label">Valor mínimo</span><div class="relative"><span class="absolute left-4 top-3 text-sm text-ink-400">R$</span><input :name="`rules[${ruleIndex}][minimum_price]`" x-model="rule.minimum_price" class="field-input pl-11"></div></label>
                            </div>

                            <div class="lg:col-span-12">
                                <details class="rounded-2xl border border-line bg-white">
                                    <summary class="cursor-pointer px-4 py-3 text-xs font-extrabold text-ink-700">Variações opcionais — tinta, efeito, cores e outros campos</summary>
                                    <div class="space-y-3 border-t border-line p-4">
                                        <p class="text-xs leading-5 text-ink-400">Deixe vazio para a regra valer para qualquer combinação nessa faixa.</p>
                                        <template x-for="(condition, conditionIndex) in rule.conditions" :key="condition.client_id">
                                            <div class="grid gap-3 rounded-xl bg-app p-3 md:grid-cols-[1fr_160px_1fr_auto]">
                                                <select :name="`rules[${ruleIndex}][conditions][${conditionIndex}][parameter]`" x-model="condition.parameter" class="field-input"><option value="">Escolha o campo</option>@foreach($pricingParameters as $parameter)<option value="{{ $parameter->key }}">{{ $parameter->label }}</option>@endforeach</select>
                                                <select :name="`rules[${ruleIndex}][conditions][${conditionIndex}][operator]`" x-model="condition.operator" class="field-input"><option value="eq">é igual a</option><option value="in">é um destes</option><option value="gte">a partir de</option><option value="lte">até</option><option value="between">entre</option><option value="contains_all">contém todos</option></select>
                                                <input :name="`rules[${ruleIndex}][conditions][${conditionIndex}][value]`" x-model="condition.value" class="field-input" placeholder="Valor; use vírgulas ou 1|5 para entre">
                                                <button type="button" @click="removeCondition(rule, conditionIndex)" class="button-ghost text-red-600">Remover</button>
                                            </div>
                                        </template>
                                        <button type="button" @click="addCondition(rule)" class="button-ghost">+ Adicionar variação</button>
                                    </div>
                                </details>
                                <input type="hidden" :name="`rules[${ruleIndex}][priority]`" x-model="rule.priority">
                            </div>
                        </div>
                    </article>
                </template>
            </section>

            <section class="surface-card p-5 sm:p-6">
                <details>
                    <summary class="cursor-pointer text-sm font-extrabold text-ink-800">Vigência opcional</summary>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label><span class="field-label">Começa em</span><input type="date" name="valid_from" value="{{ old('valid_from', $table?->valid_from?->format('Y-m-d')) }}" class="field-input"></label>
                        <label><span class="field-label">Termina em</span><input type="date" name="valid_until" value="{{ old('valid_until', $table?->valid_until?->format('Y-m-d')) }}" class="field-input"></label>
                    </div>
                </details>
            </section>

            <div class="sticky bottom-4 z-20 flex justify-end rounded-2xl border border-line bg-white/95 p-4 shadow-card backdrop-blur" data-tour="pricing-save">
                <button type="submit" class="button-primary">Salvar e colocar em uso</button>
            </div>
        </form>

        <section class="surface-card mt-8 p-5 sm:p-6" data-tour="pricing-simulator">
            <div><h2 class="text-sm font-extrabold text-ink-950">Testar preço atual</h2><p class="mt-1 text-xs text-ink-400">Salve as regras e simule uma combinação antes de usá-la em um orçamento.</p></div>

            @if(session('simulation'))
                @php($simulation = session('simulation'))
                <div class="mt-5 rounded-2xl border p-5 {{ ($simulation['status'] ?? '') === 'MATCHED' ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                    <p class="text-xs font-extrabold uppercase tracking-wider">{{ $simulation['status_label'] ?? 'Resultado' }}</p>
                    @if(is_array($simulation['total'] ?? null))<p class="mt-2 text-3xl font-black text-ink-950">{{ $simulation['total']['formatted'] }}</p>@endif
                    <p class="mt-2 text-sm text-ink-600">{{ $simulation['explanation'] ?? '' }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.pricing.simulate', ['serviceType' => $serviceType->id]) }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                <label><span class="field-label">Quantidade de peças</span><input type="number" min="1" name="simulation_quantity" value="{{ old('simulation_quantity', 10) }}" class="field-input" required></label>
                @foreach($pricingParameters as $parameter)
                    <label>
                        <span class="field-label">{{ $parameter->label }}</span>
                        @if($parameter->options)
                            <select name="simulation_parameters[{{ $parameter->key }}]" class="field-input"><option value="">Selecione</option>@foreach($parameter->options as $option)<option value="{{ $option }}" @selected(old('simulation_parameters.'.$parameter->key) === $option)>{{ $option }}</option>@endforeach</select>
                        @else
                            <input name="simulation_parameters[{{ $parameter->key }}]" value="{{ old('simulation_parameters.'.$parameter->key) }}" class="field-input" placeholder="{{ $parameter->unit }}">
                        @endif
                    </label>
                @endforeach
                <div class="flex items-end"><button type="submit" class="button-secondary w-full">Simular preço</button></div>
            </form>
        </section>
    </div>
