<x-layouts.app title="Precificação • EstampaFlow">
    <div class="page-shell">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Sprint 3</p>
                <h1 class="page-title">Precificação</h1>
                <p class="page-description">Escolha um serviço e siga um passo a passo curto. O sistema esconde as contas técnicas e mostra uma prévia antes de salvar.</p>
            </div>
            <a href="{{ route('tenant.help.show', ['article' => 'configurar-precos']) }}" class="button-secondary">Como funciona?</a>
        </div>

        <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm text-ink-600">
            <strong class="text-ink-900">Feito para quem não quer perder tempo.</strong>
            DTF, Silk, Sublimação e Bordado possuem configuração guiada. Serviços personalizados continuam adaptáveis em um modo avançado.
        </div>

        <div class="table-shell mt-6" data-tour="pricing-services">
            <div class="overflow-x-auto">
                <table class="data-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Serviço</th>
                            <th>Forma de cobrança</th>
                            <th>Configuração</th>
                            <th>Regras</th>
                            <th class="text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                            <tr>
                                <td>
                                    <p class="font-extrabold text-ink-950">{{ $service->name }}</p>
                                    <p class="mt-1 font-mono text-[10px] font-bold text-ink-400">{{ $service->code }}</p>
                                </td>
                                <td>
                                    @php
                                        $chargeLabel = match(strtoupper($service->code)) {
                                            'DTF' => 'Metro inteiro e aproveitamento',
                                            'SILK' => 'Quantidade e número de cores',
                                            'BORDADO' => 'Faixa de pontos',
                                            'SUBLIMACAO' => 'Tamanho e modalidade',
                                            default => $service->pricing_strategy?->label() ?? 'Ainda não definida',
                                        };
                                    @endphp
                                    <span class="font-semibold text-ink-800">{{ $chargeLabel }}</span>
                                    @if(in_array(strtoupper($service->code), ['DTF', 'SILK', 'SUBLIMACAO', 'BORDADO'], true))
                                        <span class="mt-1 block text-[11px] font-bold text-brand-600">Configuração guiada</span>
                                    @endif
                                </td>
                                <td>
                                    @if($service->activePriceTable)
                                        <span class="status-badge status-success">Em uso</span>
                                        <p class="mt-1 text-[11px] text-ink-400">Versão {{ $service->activePriceTable->version }}</p>
                                    @else
                                        <span class="status-badge status-neutral">Não configurada</span>
                                    @endif
                                </td>
                                <td>{{ $service->activePriceTable?->rules->count() ?? 0 }}</td>
                                <td class="text-right">
                                    <a href="{{ route('tenant.pricing.edit', ['serviceType' => $service->id]) }}" class="button-primary !px-4 !py-2" data-tour="pricing-configure">
                                        {{ $service->activePriceTable ? 'Revisar preços' : 'Configurar preços' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
