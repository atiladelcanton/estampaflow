<section>
    <h3 class="font-extrabold text-ink-950">Para que serve?</h3>
    <p class="mt-2">Esta configuração estima quanto DTF precisa ser comprado para um pedido e evita cobrar apenas o pedaço usado quando o fornecedor vende metros inteiros.</p>
</section>

<section class="rounded-2xl bg-app p-4">
    <h3 class="font-extrabold text-ink-950">Exemplo real</h3>
    <p class="mt-2">Se 10 artes de 20 × 30 cm ocuparem 1,50 m, o EstampaFlow considera a compra de 2 metros. A sobra aparece no resumo, mas o custo do pedido cobre os 2 metros pagos ao fornecedor.</p>
</section>

<section>
    <h3 class="font-extrabold text-ink-950">O que significa cada campo?</h3>
    <dl class="mt-3 space-y-3">
        <div><dt class="font-bold text-ink-900">Preço do metro</dt><dd>Quanto o fornecedor cobra por 1 metro inteiro de DTF.</dd></div>
        <div><dt class="font-bold text-ink-900">Largura útil</dt><dd>Espaço realmente disponível para organizar as artes. Pode ser menor que a largura nominal do material.</dd></div>
        <div><dt class="font-bold text-ink-900">Aplicação por peça</dt><dd>Valor cobrado para posicionar e prensar cada aplicação.</dd></div>
        <div><dt class="font-bold text-ink-900">Acréscimo no material</dt><dd>Percentual adicionado sobre o custo do DTF comprado. Pode ficar zerado.</dd></div>
        <div><dt class="font-bold text-ink-900">Espaço entre artes</dt><dd>Folga usada para corte e separação. Já vem com um valor sugerido.</dd></div>
    </dl>
</section>

<section>
    <h3 class="font-extrabold text-ink-950">O que o sistema faz sozinho?</h3>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>testa a arte na posição original e girada;</li>
        <li>calcula quantas cabem na largura;</li>
        <li>estima o comprimento necessário;</li>
        <li>arredonda sempre para metros inteiros;</li>
        <li>mostra custo do material, aplicação, sobra e valor por peça.</li>
    </ul>
</section>

<section class="rounded-2xl border border-brand-200 bg-brand-50 p-4">
    <h3 class="font-extrabold text-ink-950">Produção própria</h3>
    <p class="mt-2">Filme, tinta, poliamida, limpeza, energia e manutenção serão cadastrados em Insumos e Estoque. Depois disso, o EstampaFlow poderá calcular o custo interno do metro produzido.</p>
</section>
