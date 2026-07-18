@props(['compact' => false, 'light' => false])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <div @class([
        'grid size-10 shrink-0 place-items-center rounded-[14px] text-base font-black tracking-tight shadow-sm',
        'bg-brand-500 text-white shadow-brand-500/20' => ! $light,
        'bg-white text-brand-600' => $light,
    ])>
        D
    </div>
    @unless($compact)
        <div class="min-w-0 leading-tight">
            <p @class(['truncate text-[15px] font-extrabold tracking-tight', 'text-ink-950' => ! $light, 'text-white' => $light])>DELKA</p>
            <p @class(['mt-0.5 truncate text-[10px] font-semibold uppercase tracking-[.18em]', 'text-ink-400' => ! $light, 'text-white/65' => $light])>Gestão de Estamparia</p>
        </div>
    @endunless
</div>
