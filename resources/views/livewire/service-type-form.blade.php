<div class="page-shell">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('tenant.service-types.index') }}" class="icon-button border border-line bg-white"><span aria-hidden="true">←</span></a>
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Catálogo dinâmico</p>
            <h1 class="page-title">{{ $editing ? 'Editar serviço' : 'Novo serviço' }}</h1>
        </div>
    </div>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section class="surface-card p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2">
                <label>
                    <span class="field-label">Nome do serviço</span>
                    <input wire:model="name" class="field-input" placeholder="Ex.: Cromia">
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </label>

                <label>
                    <span class="field-label">Código estável</span>
                    <input wire:model="code" @disabled($editing) class="field-input disabled:bg-app disabled:text-ink-400" placeholder="CROMIA">
                    <span class="field-help">Não poderá ser alterado depois da criação.</span>
                    @error('code')<span class="field-error">{{ $message }}</span>@enderror
                </label>

                <label class="md:col-span-2">
                    <span class="field-label">Descrição</span>
                    <textarea wire:model="description" rows="4" class="field-input resize-y" placeholder="Explique quando e como este serviço é utilizado."></textarea>
                    @error('description')<span class="field-error">{{ $message }}</span>@enderror
                </label>

                <label>
                    <span class="field-label">Modo de precificação</span>
                    <select wire:model.live="pricingMode" class="field-input">
                        @foreach ($pricingModes as $mode)<option value="{{ $mode->value }}">{{ $mode->label() }}</option>@endforeach
                    </select>
                </label>

                <label>
                    <span class="field-label">Estratégia inicial</span>
                    <select wire:model="pricingStrategy" class="field-input">
                        <option value="">Sem estratégia automática</option>
                        @foreach ($pricingStrategies as $strategy)<option value="{{ $strategy->value }}">{{ $strategy->label() }}</option>@endforeach
                    </select>
                </label>

                <label>
                    <span class="field-label">Ordem de exibição</span>
                    <input wire:model="sortOrder" type="number" min="0" class="field-input">
                </label>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="surface-card p-5">
                <h2 class="text-sm font-extrabold">Comportamento operacional</h2>
                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl bg-app p-4">
                    <input wire:model="requiresArt" type="checkbox" class="mt-0.5 size-4 rounded border-line text-brand-500 focus:ring-brand-300">
                    <span><span class="block text-xs font-bold text-ink-800">Exige arte</span><span class="mt-1 block text-[11px] leading-4 text-ink-400">A produção aguardará uma versão aprovada.</span></span>
                </label>
                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-2xl bg-app p-4">
                    <input wire:model="allowsMultiplePositions" type="checkbox" class="mt-0.5 size-4 rounded border-line text-brand-500 focus:ring-brand-300">
                    <span><span class="block text-xs font-bold text-ink-800">Múltiplas posições</span><span class="mt-1 block text-[11px] leading-4 text-ink-400">Permite frente, costas, mangas e outras aplicações separadas.</span></span>
                </label>
            </section>

            <section class="surface-card p-5">
                <p class="text-xs leading-5 text-ink-500">Após salvar um serviço novo, você definirá os campos que a equipe precisará preencher ao utilizá-lo em um orçamento.</p>
                <div class="mt-5 flex gap-3">
                    <a href="{{ route('tenant.service-types.index') }}" class="button-secondary flex-1">Cancelar</a>
                    <button class="button-primary flex-1" wire:loading.attr="disabled"><span wire:loading.remove>Salvar</span><span wire:loading>Salvando...</span></button>
                </div>
            </section>
        </aside>
    </form>
</div>
