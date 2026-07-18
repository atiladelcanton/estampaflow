<div class="page-shell">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Demonstração visual</p><h1 class="page-title mt-2">Produtos</h1><p class="page-description">Listagem provisória para validar tabela, filtros, status e densidade visual. Nenhum domínio de produto foi implementado.</p></div>
        <a href="{{ route('ui.products.create') }}" class="button-primary"><x-icon name="plus" class="size-4" /> Novo produto</a>
    </div>

    <div class="mt-7 rounded-2xl border border-line bg-surface-blue/45 p-3 shadow-soft sm:p-4">
        <div class="grid gap-3 lg:grid-cols-[180px_180px_1fr_auto]">
            <button class="flex items-center gap-3 rounded-xl bg-brand-500 px-4 py-3 text-left text-white shadow-lg shadow-brand-500/15"><span class="grid size-8 place-items-center rounded-lg bg-white/15"><x-icon name="shirt" class="size-4" /></span><span><span class="block text-xs font-bold">Meus produtos</span><span class="mt-0.5 block text-[10px] text-white/65">{{ count($products) }} cadastrados</span></span></button>
            <button class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 text-left text-ink-500"><span class="grid size-8 place-items-center rounded-lg bg-app"><x-icon name="chart" class="size-4" /></span><span><span class="block text-xs font-bold">Movimentações</span><span class="mt-0.5 block text-[10px] text-ink-300">Em breve</span></span></button>
            <div class="relative"><x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink-300" /><input wire:model.live.debounce.250ms="search" class="field-input h-full pl-11" placeholder="Buscar produto, SKU ou categoria..."></div>
            <button wire:click="clearFilters" class="button-secondary"><x-icon name="refresh" class="size-4" /> Limpar</button>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            @foreach(['all' => 'Todos', 'active' => 'Ativos', 'low' => 'Estoque baixo', 'inactive' => 'Inativos'] as $value => $label)
                <button wire:click="$set('status', '{{ $value }}')" @class(['rounded-xl px-3 py-2 text-xs font-bold transition', 'bg-brand-500 text-white' => $status === $value, 'border border-line bg-white text-ink-500 hover:border-brand-300 hover:text-brand-700' => $status !== $value])>{{ $label }}</button>
            @endforeach
        </div>
        <p class="text-xs font-bold text-brand-600">{{ count($visibleProducts) }} resultados</p>
    </div>

    <div class="table-shell mt-4 overflow-x-auto">
        <table class="data-table">
            <thead><tr><th class="w-10"><input type="checkbox" class="rounded border-line text-brand-500"></th><th>Produto</th><th>SKU</th><th>Estoque</th><th>Preço sugerido</th><th>Status</th><th class="w-16"></th></tr></thead>
            <tbody>
                @forelse($visibleProducts as $index => $product)
                    <tr wire:key="product-{{ $index }}">
                        <td><input type="checkbox" class="rounded border-line text-brand-500"></td>
                        <td><div class="flex items-center gap-3"><div @class(['grid size-12 shrink-0 place-items-center rounded-2xl text-xs font-extrabold', 'bg-brand-100 text-brand-700' => $index % 3 === 0, 'bg-surface-mint text-emerald-700' => $index % 3 === 1, 'bg-surface-blue text-ink-600' => $index % 3 === 2])>{{ $product['initials'] }}</div><div><p class="font-bold text-ink-800">{{ $product['name'] }}</p><p class="mt-1 text-[11px] text-ink-400">{{ $product['category'] }}</p></div></div></td>
                        <td class="font-mono text-xs">{{ $product['sku'] }}</td>
                        <td><span @class(['font-bold', 'text-red-500' => $product['stock'] === 0, 'text-amber-600' => $product['stock'] > 0 && $product['stock'] <= 10, 'text-ink-700' => $product['stock'] > 10])>{{ $product['stock'] }}</span> <span class="text-[11px] text-ink-300">un.</span></td>
                        <td class="font-bold text-ink-700">{{ $product['price'] }}</td>
                        <td>@if($product['status'] === 'active')<span class="status-badge status-success">Ativo</span>@elseif($product['status'] === 'low')<span class="status-badge status-warning">Estoque baixo</span>@else<span class="status-badge status-danger">Inativo</span>@endif</td>
                        <td><button class="icon-button"><x-icon name="more" class="size-4" /></button></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="py-12 text-center"><div class="mx-auto grid size-12 place-items-center rounded-2xl bg-app text-ink-300"><x-icon name="search" /></div><p class="mt-4 text-sm font-bold text-ink-700">Nenhum produto encontrado</p><p class="mt-1 text-xs text-ink-400">Ajuste os filtros ou limpe a busca.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex items-center justify-between text-xs text-ink-400"><span>Dados demonstrativos da Sprint 0</span><div class="flex gap-1"><button class="icon-button size-8">1</button><button class="icon-button size-8">2</button><button class="icon-button size-8">›</button></div></div>
</div>
