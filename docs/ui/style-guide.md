# Guia Visual — Delka Estamparia

- **Versão:** 1.0
- **Sprint:** 0
- **Status:** ACCEPTED

## Direção

A interface segue uma linguagem clara, espaçosa e minimalista, inspirada em dashboards modulares contemporâneos. A referência serve para composição, densidade, hierarquia e organização; não deve ser copiada literalmente.

## Paleta

| Token | Cor | Uso |
|---|---|---|
| `background` | `#FFFFFF` | fundo principal |
| `surface-mint` | `#EFFFFA` | superfícies suaves e feedback positivo |
| `surface-blue` | `#E5ECF4` | filtros, áreas secundárias e bordas |
| `brand-soft` | `#C3BEF7` | seleção, chips e hover |
| `brand` | `#8A4FFF` | ação primária e navegação ativa |
| `ink` | `#14121A` | texto principal |

`#C3BEF7` não deve ser usado como texto pequeno sobre branco.

## Layout

- sidebar recolhível em desktop;
- drawer em mobile;
- header fixo com busca e usuário;
- largura operacional ampla;
- cards com raio de 16 px;
- sombra apenas para separação leve;
- formulários longos em conteúdo principal + painel lateral;
- barra fixa de ações em formulários extensos.

## Componentes

Classes reutilizáveis ficam em `resources/css/app.css`:

- `.button-primary`;
- `.button-secondary`;
- `.button-ghost`;
- `.field-input`;
- `.status-badge`;
- `.surface-card`;
- `.metric-card`;
- `.quick-card`;
- `.data-table`.

## Estados

Toda tela futura deve prever:

- loading/skeleton;
- empty state;
- validação próxima ao campo;
- erro recuperável;
- sucesso;
- disabled;
- autorização negada.

## Acessibilidade

- contraste mínimo WCAG AA para texto;
- foco visível roxo;
- labels explícitos;
- ícones acompanhados de texto quando representam navegação;
- não depender apenas de cor para status;
- layout funcional em 360 px.

## Telas de referência implementadas

- login dividido;
- dashboard modular;
- listagem de produtos demonstrativa;
- formulário de produto demonstrativo;
- guia visual navegável.


## Padrões adicionados na Sprint 1

### Seletor de ambientes

- card por tenant;
- nome, domínio, papel e estado;
- ação inteira clicável;
- novo tenant em botão primário;
- não misturar dados de tenants diferentes no mesmo dashboard operacional.

### Contexto ativo

No ambiente tenant, o header deve mostrar:

- nome da estamparia;
- opção para voltar ao seletor;
- papel do usuário;
- distinção visual entre conta global e operação.

### Gestão de equipe

- formulário de convite separado da tabela;
- status em badge;
- ações destrutivas com confirmação;
- proteção do Owner refletida no estado disabled;
- link de convite visível apenas após criação local.


## E-mails transacionais

Os e-mails do EstampaFlow seguem a mesma identidade da aplicação:

- fundo geral `#F7F8FB`;
- superfície branca;
- destaque primário `#8A4FFF`;
- superfície auxiliar `#EFFFFA`;
- apoio roxo `#C3BEF7` / `#EEEAFF`;
- texto principal escuro;
- card com borda suave e raio visual equivalente a 16–22 px;
- botão principal roxo;
- versão HTML com estilos inline e versão texto puro.

E-mails nunca devem depender de JavaScript, CSS externo ou imagens remotas para transmitir informação essencial.
