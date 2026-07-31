@props([
    'tutorial' => null,
    'acknowledged' => true,
])

@if(is_array($tutorial))
    @php
        $tutorialKey = (string) ($tutorial['key'] ?? '');
        $completeUrl = route('tenant.tutorials.complete', ['tutorialKey' => $tutorialKey]);
        $dismissUrl = route('tenant.tutorials.dismiss', ['tutorialKey' => $tutorialKey]);
    @endphp

    <div
        x-data="{
            open: false,
            step: 0,
            tutorial: @js($tutorial),
            acknowledged: @js($acknowledged),
            highlighted: null,
            init() {
                this.$nextTick(() => {
                    if (! this.acknowledged) {
                        window.setTimeout(() => this.start(), 450);
                    }
                });
            },
            start() {
                this.open = true;
                this.step = 0;
                this.highlightCurrent();
            },
            current() {
                return this.tutorial.steps[this.step] ?? null;
            },
            highlightCurrent() {
                this.clearHighlight();
                const selector = this.current()?.target;
                if (typeof selector !== 'string' || selector === '') return;
                const target = document.querySelector(selector);
                if (! target) return;
                this.highlighted = target;
                target.classList.add('onboarding-highlight');
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            },
            clearHighlight() {
                this.highlighted?.classList.remove('onboarding-highlight');
                this.highlighted = null;
            },
            previous() {
                if (this.step === 0) return;
                this.step--;
                this.$nextTick(() => this.highlightCurrent());
            },
            next() {
                if (this.step >= this.tutorial.steps.length - 1) {
                    this.finish();
                    return;
                }
                this.step++;
                this.$nextTick(() => this.highlightCurrent());
            },
            async store(url) {
                await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json',
                    },
                });
            },
            async finish() {
                this.clearHighlight();
                this.open = false;
                this.acknowledged = true;
                await this.store(@js($completeUrl));
            },
            async dismiss() {
                this.clearHighlight();
                this.open = false;
                this.acknowledged = true;
                await this.store(@js($dismissUrl));
            },
        }"
        @start-page-tutorial.window="start()"
        @keydown.escape.window="open && dismiss()"
        x-cloak
    >
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-[80] bg-ink-950/20" aria-hidden="true"></div>

        <aside
            x-show="open"
            x-transition
            class="fixed bottom-4 right-4 z-[90] w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-brand-200 bg-white p-5 shadow-2xl sm:bottom-6 sm:right-6"
            role="dialog"
            aria-modal="true"
            aria-label="Tutorial desta página"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-brand-600">
                        Passo <span x-text="step + 1"></span> de <span x-text="tutorial.steps.length"></span>
                    </p>
                    <h2 class="mt-2 text-base font-extrabold text-ink-950" x-text="current()?.title"></h2>
                </div>
                <button type="button" class="icon-button !size-8" @click="dismiss()" aria-label="Fechar tutorial">×</button>
            </div>

            <p class="mt-3 text-sm leading-6 text-ink-500" x-text="current()?.body"></p>

            <div class="mt-5 flex items-center justify-between gap-3">
                <button type="button" class="button-ghost !px-2" @click="dismiss()">Agora não</button>
                <div class="flex gap-2">
                    <button type="button" x-show="step > 0" class="button-secondary !px-3 !py-2 text-xs" @click="previous()">Voltar</button>
                    <button type="button" class="button-primary !px-3 !py-2 text-xs" @click="next()">
                        <span x-text="step === tutorial.steps.length - 1 ? 'Concluir' : 'Próximo'"></span>
                    </button>
                </div>
            </div>
        </aside>
    </div>
@endif
