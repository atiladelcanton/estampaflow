# Model: ServiceParameterDefinition

## Objetivo
Definir um campo dinâmico apresentado ao configurar uma aplicação produtiva.

## Tabela
`service_parameter_definitions`.

## Tipos
TEXT, INTEGER, DECIMAL, BOOLEAN, SELECT e MULTISELECT.

## Campos relevantes
key, label, field_type, unit, required, affects_pricing, options, validation_rules, default_value, sort_order e active.

## Regras
- chave única por versão;
- select e multiselect exigem opções;
- posição e quantidade aplicada não são cadastradas aqui.
