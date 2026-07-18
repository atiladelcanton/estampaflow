<x-layouts.app title="Guia visual • Delka">
    <div class="page-shell">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Design system</p><h1 class="page-title mt-2">Guia visual da Delka</h1><p class="page-description">Tokens e componentes aprovados para todas as próximas sprints.</p></div><span class="status-badge status-success">Versão 1.0</span></div>

        <section class="mt-8 surface-card p-6"><h2 class="text-sm font-extrabold">Paleta principal</h2><div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([['#FFFFFF','Branco','Fundo principal','bg-white border'],['#EFFFFA','Menta','Fundo suave','bg-surface-mint'],['#E5ECF4','Azul gelo','Superfície','bg-surface-blue'],['#C3BEF7','Lavanda','Apoio','bg-brand-300'],['#8A4FFF','Roxo Delka','Primária','bg-brand-500 text-white']] as [$hex,$name,$use,$class])
                <div class="overflow-hidden rounded-2xl border border-line"><div class="h-28 {{ $class }}"></div><div class="p-4"><p class="text-sm font-bold">{{ $name }}</p><p class="mt-1 font-mono text-xs text-ink-500">{{ $hex }}</p><p class="mt-2 text-[11px] text-ink-400">{{ $use }}</p></div></div>
            @endforeach
        </div></section>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="surface-card p-6"><h2 class="text-sm font-extrabold">Botões</h2><div class="mt-5 flex flex-wrap items-center gap-3"><button class="button-primary">Ação principal</button><button class="button-secondary">Ação secundária</button><button class="button-ghost">Ação neutra</button><button disabled class="button-primary">Desabilitado</button></div></section>
            <section class="surface-card p-6"><h2 class="text-sm font-extrabold">Status</h2><div class="mt-5 flex flex-wrap gap-3"><span class="status-badge status-success">Ativo</span><span class="status-badge status-warning">Pendente</span><span class="status-badge status-danger">Bloqueado</span><span class="status-badge status-info">Em produção</span><span class="status-badge status-neutral">Planejado</span></div></section>
            <section class="surface-card p-6"><h2 class="text-sm font-extrabold">Campos</h2><div class="mt-5 space-y-5"><div><label class="field-label">Campo padrão</label><input class="field-input" placeholder="Digite uma informação"><p class="field-help">Texto auxiliar curto e objetivo.</p></div><div><label class="field-label">Seleção</label><select class="field-input"><option>Selecione uma opção</option></select></div></div></section>
            <section class="surface-card p-6"><h2 class="text-sm font-extrabold">Cards</h2><div class="mt-5 grid grid-cols-2 gap-4"><div class="metric-card"><p class="metric-label">Orçamentos</p><p class="metric-value">28</p><p class="metric-help">7 aguardando retorno</p></div><div class="metric-card border-brand-200 bg-brand-500 text-white"><p class="text-xs text-white/70">Produção</p><p class="mt-3 text-3xl font-extrabold">12</p><p class="mt-1 text-[11px] text-white/70">ordens em andamento</p></div></div></section>
        </div>

        <section class="mt-6 surface-card p-6"><h2 class="text-sm font-extrabold">Princípios</h2><div class="mt-5 grid gap-4 md:grid-cols-3">@foreach ([['Clareza','Uma ação principal por contexto e hierarquia visual evidente.'],['Leveza','Sombras discretas, bordas suaves e bastante espaço em branco.'],['Operação','Tabelas amplas, filtros objetivos e feedback próximo da ação.']] as [$title,$text])<div class="rounded-2xl bg-app p-5"><p class="text-sm font-bold">{{ $title }}</p><p class="mt-2 text-xs leading-5 text-ink-400">{{ $text }}</p></div>@endforeach</div></section>
    </div>
</x-layouts.app>
