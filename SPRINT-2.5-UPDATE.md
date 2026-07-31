# EstampaFlow — Atualização Sprint 2.5

## Aplicação

```bash
unzip -o estampaflow-sprint-2.5-update.zip -d .
chmod +x scripts/upgrade-sprint-2-5.sh
./scripts/upgrade-sprint-2-5.sh
```

## Rotas

- `/primeiros-passos` — wizard do Owner;
- `/ajuda` — Central de Ajuda;
- tutoriais contextuais nas telas existentes.

## Banco

Migration aditiva: `user_onboarding_progress`.

Não remove nem altera dados das Sprints 0, 1 e 2.
