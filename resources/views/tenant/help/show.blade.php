<x-layouts.app title="{{ $article['title'] }} • EstampaFlow">
    <div class="page-shell">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('tenant.help.index') }}" class="button-ghost -ml-4">← Voltar para a Central de Ajuda</a>

            <article class="surface-card mt-4 overflow-hidden">
                <div class="border-b border-line bg-brand-50/60 p-6 sm:p-8">
                    <span class="status-badge status-info">{{ $article['category'] }}</span>
                    <h1 class="mt-4 text-2xl font-extrabold text-ink-950">{{ $article['title'] }}</h1>
                    <p class="mt-3 text-sm leading-6 text-ink-500">{{ $article['summary'] }}</p>
                </div>

                <div class="p-6 sm:p-8">
                    <ol class="space-y-5">
                        @foreach($article['steps'] as $index => $step)
                            <li class="flex gap-4">
                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-black text-brand-700">{{ $index + 1 }}</span>
                                <p class="pt-2 text-sm leading-6 text-ink-600">{{ $step }}</p>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
                        @if(($article['route'] ?? null) === 'tenant.onboarding.show')
                            <a href="{{ route('tenant.onboarding.show', ['rever' => 1]) }}" class="button-primary">Rever primeiros passos</a>
                        @elseif(is_string($article['route'] ?? null) && Route::has($article['route']))
                            <a href="{{ route($article['route']) }}" class="button-primary">Abrir esta tela</a>
                        @endif
                        <a href="{{ route('tenant.help.index') }}" class="button-secondary">Ver outros tutoriais</a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</x-layouts.app>
