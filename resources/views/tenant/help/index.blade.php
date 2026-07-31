<x-layouts.app title="Ajuda e tutoriais • EstampaFlow">
    <div class="page-shell">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-brand-600">Central de Ajuda</p>
            <h1 class="page-title mt-2">Ajuda e tutoriais</h1>
            <p class="page-description">Encontre instruções curtas por tarefa. Sem manuais longos e sem termos técnicos.</p>
        </div>

        <div class="mt-8 space-y-8">
            @foreach($categories as $category => $categoryArticles)
                <section>
                    <h2 class="text-sm font-extrabold text-ink-900">{{ $category }}</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($categoryArticles as $slug => $article)
                            <a href="{{ route('tenant.help.show', ['article' => $slug]) }}" class="surface-card group p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-card">
                                <span class="grid size-10 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="file" class="size-4" /></span>
                                <h3 class="mt-4 text-sm font-extrabold text-ink-900 group-hover:text-brand-700">{{ $article['title'] }}</h3>
                                <p class="mt-2 text-xs leading-5 text-ink-400">{{ $article['summary'] }}</p>
                                <span class="mt-4 inline-flex text-xs font-bold text-brand-700">Abrir tutorial →</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
