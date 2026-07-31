<x-layouts.app title="Preços de {{ $serviceType->name }} • EstampaFlow">
    <div class="page-shell" x-data="{ helpOpen: false }" @keydown.escape.window="if (helpOpen) { helpOpen = false; $nextTick(() => $refs.helpButton?.focus()) }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('tenant.pricing.index') }}" class="text-xs font-bold text-brand-700">← Voltar para precificação</a>
                <h1 class="page-title mt-3">Preços de {{ $serviceType->name }}</h1>
                <p class="page-description">
                    @if($pricingTemplate->isGuided())
                        Responda uma etapa por vez. O EstampaFlow faz as contas técnicas nos bastidores.
                    @else
                        Configure as regras comerciais deste serviço.
                    @endif
                </p>
            </div>

            <button
                type="button"
                x-ref="helpButton"
                @click="helpOpen = true; $nextTick(() => $refs.helpClose?.focus())"
                class="button-secondary"
                aria-haspopup="dialog"
                :aria-expanded="helpOpen"
            >
                ? Entenda esta tela
            </button>
        </div>

        @if(session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($legacyConfiguration)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900">
                <strong>Encontramos uma configuração feita no modelo antigo.</strong>
                Ela foi preservada, mas não será copiada para este passo a passo. Revise os campos e salve para colocar o novo modelo em uso.
            </div>
        @endif

        @if($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <p class="font-bold">Revise estes pontos:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @switch($pricingTemplate)
            @case(\App\Domains\Pricing\Enums\GuidedPricingTemplate::DTF_METER)
                @include('tenant.pricing.guided.dtf')
                @break

            @case(\App\Domains\Pricing\Enums\GuidedPricingTemplate::SILK_MATRIX)
                @include('tenant.pricing.guided.silk')
                @break

            @case(\App\Domains\Pricing\Enums\GuidedPricingTemplate::SUBLIMATION_MATRIX)
                @include('tenant.pricing.guided.sublimation')
                @break

            @case(\App\Domains\Pricing\Enums\GuidedPricingTemplate::EMBROIDERY_MATRIX)
                @include('tenant.pricing.guided.embroidery')
                @break

            @default
                @include('tenant.pricing.guided.generic')
        @endswitch

        <div
            x-show="helpOpen"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="pricing-help-title"
        >
            <div class="absolute inset-0 bg-ink-950/50" @click="helpOpen = false; $nextTick(() => $refs.helpButton?.focus())"></div>
            <div class="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-line bg-white shadow-2xl">
                <div class="sticky top-0 flex items-center justify-between gap-4 border-b border-line bg-white px-6 py-5">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[.16em] text-brand-600">Ajuda rápida</p>
                        <h2 id="pricing-help-title" class="mt-1 text-xl font-black text-ink-950">Entenda a precificação de {{ $serviceType->name }}</h2>
                    </div>
                    <button
                        type="button"
                        x-ref="helpClose"
                        @click="helpOpen = false; $nextTick(() => $refs.helpButton?.focus())"
                        class="button-ghost"
                        aria-label="Fechar ajuda"
                    >
                        Fechar
                    </button>
                </div>

                <div class="space-y-6 p-6 text-sm leading-6 text-ink-600">
                    @if($pricingTemplate === \App\Domains\Pricing\Enums\GuidedPricingTemplate::DTF_METER)
                        @include('tenant.pricing.guided.help-dtf')
                    @elseif($pricingTemplate === \App\Domains\Pricing\Enums\GuidedPricingTemplate::SILK_MATRIX)
                        @include('tenant.pricing.guided.help-silk')
                    @elseif($pricingTemplate === \App\Domains\Pricing\Enums\GuidedPricingTemplate::SUBLIMATION_MATRIX)
                        @include('tenant.pricing.guided.help-sublimation')
                    @elseif($pricingTemplate === \App\Domains\Pricing\Enums\GuidedPricingTemplate::EMBROIDERY_MATRIX)
                        @include('tenant.pricing.guided.help-embroidery')
                    @else
                        <section>
                            <h3 class="font-extrabold text-ink-950">Para que serve?</h3>
                            <p class="mt-2">Esta tela registra como sua estamparia cobra o serviço. Comece com a regra mais simples e use condições somente quando elas realmente alterarem o preço.</p>
                        </section>
                        <section>
                            <h3 class="font-extrabold text-ink-950">O que fica escondido?</h3>
                            <p class="mt-2">Versionamento, prioridade, validação e auditoria são controlados pelo sistema. Você precisa apenas informar os valores comerciais.</p>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
