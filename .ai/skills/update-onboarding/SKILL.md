# Skill — Atualizar onboarding de um módulo

1. Defina se a funcionalidade é essencial para novos tenants.
2. Se for essencial, atualize o wizard inicial sem aumentar o total além do necessário.
3. Adicione ou atualize o tutorial contextual da rota em `config/onboarding.php`.
4. Adicione ou atualize o artigo da Central de Ajuda.
5. Use linguagem operacional, sem termos técnicos.
6. Salve progresso por `tenant_id`, `user_id`, `tutorial_key` e `version`.
7. Não reapresente tutorial concluído ou dispensado sem motivo de versão.
8. Adicione testes por papel e isolamento de tenant.
9. Atualize a documentação da sprint e `docs:check`.
