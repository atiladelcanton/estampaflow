<div class="page-shell">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Sprint 2</p>
            <h1 class="page-title">Tipos de serviço</h1>
            <p class="page-description">Configure DTF, Silk, Sublimação, Bordado e qualquer outro serviço sem alterar o código do sistema.</p>
        </div>
        <a href="{{ route('tenant.service-types.create') }}" class="button-primary"><x-icon name="plus" class="size-4" /> Novo serviço</a>
    </div>

    @if (session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    @error('serviceType')
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror

    <div class="surface-card mt-6 p-4">
        <div class="grid gap-3 md:grid-cols-[1fr_220px]">
            <label class="relative">
                <span class="sr-only">Buscar tipos de serviço</span>
                <x-icon name="search" class="pointer-events-none absolute left-4 top-3.5 size-4 text-ink-400" />
                <input wire:model.live.debounce.300ms="search" class="field-input pl-11" placeholder="Buscar por nome ou código...">
            </label>
            <label>
                <span class="sr-only">Filtrar por status</span>
                <select wire:model.live="status" class="field-input">
                    <option value="all">Todos os status</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </label>
        </div>
    </div>

    <div class="table-shell mt-6">
        <div class="overflow-x-auto">
            <table class="data-table min-w-[1180px]">
                <thead>
                    <tr>
                        <th scope="col">Serviço</th>
                        <th scope="col">Descrição</th>
                        <th scope="col">Preço</th>
                        <th scope="col">Campos</th>
                        <th scope="col">Arte</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($serviceTypes as $serviceType)
                        <tr wire:key="service-type-{{ $serviceType->id }}">
                            <td>
                                <div class="flex min-w-52 items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-black text-brand-700">{{ str($serviceType->code)->substr(0, 2) }}</span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate font-extrabold text-ink-950">{{ $serviceType->name }}</span>
                                            @if ($serviceType->is_default)
                                                <span class="status-badge status-info">Padrão</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 font-mono text-[10px] font-bold uppercase tracking-wider text-ink-400">{{ $serviceType->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="max-w-80">
                                <p class="line-clamp-2 leading-5">{{ $serviceType->description ?: 'Sem descrição informada.' }}</p>
                            </td>
                            <td>
                                <span class="font-semibold text-ink-800">{{ $serviceType->pricing_mode->label() }}</span>
                            </td>
                            <td>
                                <div class="whitespace-nowrap">
                                    @if ($serviceType->activeSchemaVersion)
                                        @php($fieldCount = $serviceType->activeSchemaVersion->parameters->count())
                                        <p class="font-semibold text-ink-800">{{ $fieldCount === 0 ? 'Sem campos extras' : $fieldCount.' '.($fieldCount === 1 ? 'campo' : 'campos') }}</p>
                                        <p class="mt-1 text-[11px] text-ink-400">Usados nos orçamentos</p>
                                    @else
                                        <p class="font-semibold text-amber-700">Sem campos definidos</p>
                                        <p class="mt-1 text-[11px] text-ink-400">Adicione somente se precisar</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="whitespace-nowrap font-semibold text-ink-700">{{ $serviceType->requires_art ? 'Obrigatória' : 'Opcional' }}</span>
                            </td>
                            <td>
                                <span @class(['status-badge', 'status-success' => $serviceType->active, 'status-neutral' => ! $serviceType->active])>{{ $serviceType->active ? 'Ativo' : 'Inativo' }}</span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('tenant.service-types.fields', ['serviceType' => $serviceType->id]) }}" class="button-primary !px-3 !py-2" title="Definir os campos solicitados ao usar este serviço" data-testid="service-fields-link">Campos</a>
                                    <a href="{{ route('tenant.service-types.edit', ['serviceType' => $serviceType->id]) }}" class="button-secondary !px-3 !py-2">Editar</a>
                                    <button type="button" wire:click="duplicate('{{ $serviceType->id }}')" wire:loading.attr="disabled" class="button-ghost !px-2.5 !py-2">Duplicar</button>
                                    <button type="button" wire:click="toggle('{{ $serviceType->id }}')" wire:loading.attr="disabled" class="button-ghost !px-2.5 !py-2">{{ $serviceType->active ? 'Desativar' : 'Ativar' }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!py-14 text-center">
                                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-100 text-brand-700"><x-icon name="layers" /></span>
                                <h2 class="mt-4 text-lg font-extrabold text-ink-950">Nenhum serviço encontrado</h2>
                                <p class="mt-2 text-sm text-ink-500">Crie um serviço ou altere os filtros da busca.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $serviceTypes->links() }}</div>
</div>
