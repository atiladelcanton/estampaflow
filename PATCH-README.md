# Patch — Precificação guiada dos quatro serviços padrão

## Objetivo

Substituir a tela técnica da Sprint 3 por fluxos operacionais curtos para:

- DTF;
- Silk Screen;
- Sublimação;
- Bordado.

O motor genérico, versionado e tenant-aware permanece nos bastidores. Serviços personalizados continuam usando o editor avançado como fallback.

## Aplicação

```bash
unzip -o estampaflow-sprint-3-guided-all-services-update.zip -d .
chmod +x scripts/validate-sprint-3-guided-pricing.sh
./scripts/validate-sprint-3-guided-pricing.sh
```

Depois execute:

```bash
make quality
```

## Banco

Não há migration. Configurações antigas continuam preservadas e são aposentadas somente quando uma nova versão é salva.
