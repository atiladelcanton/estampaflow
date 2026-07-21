# Patch — Campos do serviço sem Livewire

## Correções

- remove o 404 ao adicionar sugestões ou campos personalizados;
- elimina requisições Livewire desta tela;
- adiciona campos instantaneamente no navegador com Alpine.js;
- envia dados apenas ao salvar;
- substitui chips apertados por cartões uniformes em grade;
- mantém `/schema` compatível;
- adiciona teste HTTP real de salvamento no domínio do tenant;
- mantém bloqueio de usuários comuns.

## Aplicação

```bash
unzip -o estampaflow-sprint-2-fields-form-fix.zip -d .
chmod +x scripts/validate-sprint-2-fields-form.sh
./scripts/validate-sprint-2-fields-form.sh
make quality
```

Não há migration nem alteração destrutiva de dados.
