<x-layouts.app title="Novo produto • EstampaFlow">
    <div class="page-shell pb-28">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Demonstração visual</p><h1 class="page-title mt-2">Adicionar produto</h1><p class="page-description">Formulário de referência. O salvamento será implementado na Sprint 4.</p></div>
            <span class="status-badge status-warning">Somente visual</span>
        </div>

        <div class="mt-7 grid gap-5 xl:grid-cols-[1.45fr_.55fr]">
            <div class="space-y-5">
                <section class="surface-card p-5 sm:p-6">
                    <h2 class="text-sm font-extrabold">Informações gerais</h2>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="field-label">Nome do produto *</label><input class="field-input" placeholder="Ex.: Camiseta Algodão Premium"><p class="field-help">Use um nome claro e fácil de reconhecer nos orçamentos.</p></div>
                        <div><label class="field-label">SKU</label><input class="field-input" placeholder="CAM-ALG-001"></div>
                        <div><label class="field-label">Categoria</label><select class="field-input"><option>Selecione uma categoria</option><option>Camisetas</option><option>Moletons</option><option>Canecas</option></select></div>
                        <div class="sm:col-span-2"><label class="field-label">Descrição</label><textarea rows="6" class="field-input resize-y" placeholder="Características, composição, orientações de uso..."></textarea></div>
                    </div>
                </section>

                <section class="surface-card p-5 sm:p-6">
                    <div class="flex items-center justify-between"><div><h2 class="text-sm font-extrabold">Imagens do produto</h2><p class="mt-1 text-xs text-ink-400">PNG, JPG ou WEBP</p></div><span class="status-badge status-neutral">Máx. 8 MB</span></div>
                    <div class="mt-6 grid min-h-52 place-items-center rounded-2xl border-2 border-dashed border-brand-200 bg-brand-50/40 p-8 text-center transition hover:bg-brand-50">
                        <div><div class="mx-auto grid size-14 place-items-center rounded-2xl bg-white text-brand-500 shadow-soft"><x-icon name="upload" /></div><p class="mt-4 text-sm font-bold text-ink-700">Arraste uma imagem ou clique para selecionar</p><p class="mt-2 text-xs text-ink-400">A imagem principal será usada nas listagens e no orçamento.</p><button class="button-secondary mt-5" type="button">Selecionar arquivo</button></div>
                    </div>
                </section>

                <section class="surface-card p-5 sm:p-6">
                    <h2 class="text-sm font-extrabold">Estoque inicial</h2>
                    <div class="mt-6 grid gap-5 sm:grid-cols-3"><div><label class="field-label">Controla variantes?</label><select class="field-input"><option>Não</option><option>Sim</option></select></div><div><label class="field-label">Quantidade</label><input type="number" class="field-input" value="0"></div><div><label class="field-label">Alerta mínimo</label><input type="number" class="field-input" value="5"></div></div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="surface-card p-5"><h2 class="text-sm font-extrabold">Miniatura</h2><div class="mt-5 grid aspect-square place-items-center rounded-2xl border-2 border-dashed border-brand-200 bg-brand-50/40 text-brand-300"><div class="text-center"><x-icon name="upload" class="mx-auto size-8" /><p class="mt-3 text-xs font-semibold">Imagem principal</p></div></div></section>
                <section class="surface-card p-5"><h2 class="text-sm font-extrabold">Detalhes comerciais</h2><div class="mt-5 space-y-5"><div><label class="field-label">Status</label><select class="field-input"><option>Ativo</option><option>Inativo</option></select></div><div><label class="field-label">Preço sugerido</label><div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-ink-400">R$</span><input class="field-input pl-11" placeholder="0,00"></div></div><label class="flex items-start gap-3 rounded-xl bg-surface-mint p-4"><input type="checkbox" checked class="mt-0.5 rounded border-line text-brand-500"><span><span class="block text-xs font-bold text-ink-700">Disponível para orçamento</span><span class="mt-1 block text-[11px] leading-4 text-ink-400">Permite selecionar este produto em novos orçamentos.</span></span></label></div></section>
                <section class="rounded-2xl border border-brand-200 bg-brand-50 p-5"><p class="text-xs font-extrabold text-brand-800">Sprint 4</p><p class="mt-2 text-xs leading-5 text-brand-700/75">Models, variantes, ledger de estoque e persistência serão implementados somente no escopo correto.</p></section>
            </aside>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-white/90 p-4 shadow-[0_-12px_35px_rgba(20,18,26,.06)] backdrop-blur transition-all duration-200" :class="{ 'lg:left-[92px]': sidebarCollapsed, 'lg:left-[270px]': ! sidebarCollapsed }">
        <div class="mx-auto flex max-w-[1600px] justify-end gap-3"><a href="{{ route('ui.products') }}" class="button-secondary">Cancelar</a><button disabled class="button-primary">Salvar produto</button></div>
    </div>
</x-layouts.app>
