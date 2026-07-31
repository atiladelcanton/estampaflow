<?php

declare(strict_types=1);

return [
    'wizard' => [
        'key' => 'owner-first-steps',
        'version' => 1,
        'title' => 'Primeiros passos no EstampaFlow',
    ],

    'tutorials' => [
        'dashboard-owner' => [
            'key' => 'dashboard-owner',
            'version' => 1,
            'title' => 'Conheça sua visão geral',
            'routes' => ['tenant.dashboard'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="dashboard-summary"]',
                    'title' => 'Resumo da estamparia',
                    'body' => 'Aqui você acompanha equipe, convites, serviços ativos e o período de teste.',
                ],
                [
                    'target' => '[data-tour="dashboard-services"]',
                    'title' => 'Seus tipos de serviço',
                    'body' => 'Use esta ação para revisar DTF, Silk, Sublimação, Bordado e outros serviços.',
                ],
                [
                    'target' => '[data-tour="dashboard-team"]',
                    'title' => 'Convide sua equipe',
                    'body' => 'Adicione quem participa da operação. Você continua controlando papéis e acessos.',
                ],
            ],
        ],
        'dashboard-user' => [
            'key' => 'dashboard-user',
            'version' => 1,
            'title' => 'Conheça sua área de trabalho',
            'routes' => ['tenant.dashboard'],
            'roles' => ['USER'],
            'steps' => [
                [
                    'target' => '[data-tour="dashboard-summary"]',
                    'title' => 'Visão geral',
                    'body' => 'Aqui você acompanha o que já está disponível nesta estamparia.',
                ],
                [
                    'target' => '[data-tour="help-menu"]',
                    'title' => 'Ajuda sempre disponível',
                    'body' => 'Abra a Central de Ajuda sempre que precisar rever um passo.',
                ],
            ],
        ],
        'team-management' => [
            'key' => 'team-management',
            'version' => 1,
            'title' => 'Gerencie sua equipe',
            'routes' => ['tenant.users'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="team-invite"]',
                    'title' => 'Convide uma pessoa',
                    'body' => 'Informe o e-mail e envie o convite. O link também fica disponível para copiar.',
                ],
                [
                    'target' => '[data-tour="team-members"]',
                    'title' => 'Controle os acessos',
                    'body' => 'Aqui você altera o papel, suspende acessos e transfere a propriedade com segurança.',
                ],
            ],
        ],
        'service-catalog' => [
            'key' => 'service-catalog',
            'version' => 1,
            'title' => 'Configure seus serviços',
            'routes' => ['tenant.service-types.index'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="service-create"]',
                    'title' => 'Serviço diferente?',
                    'body' => 'Cadastre apenas quando sua estamparia oferecer algo além dos serviços que já vieram prontos.',
                ],
                [
                    'target' => '[data-tour="service-table"]',
                    'title' => 'Lista operacional',
                    'body' => 'Ative, desative ou edite um serviço. A coluna Campos mostra quais informações serão pedidas no orçamento.',
                ],
                [
                    'target' => '[data-tour="service-fields-link"]',
                    'title' => 'Campos do serviço',
                    'body' => 'Abra somente quando precisar mudar as informações solicitadas para aquele serviço.',
                ],
            ],
        ],
        'service-fields' => [
            'key' => 'service-fields',
            'version' => 1,
            'title' => 'Defina somente o necessário',
            'routes' => ['tenant.service-types.fields', 'tenant.service-types.schema'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="service-field-suggestions"]',
                    'title' => 'Campos comuns',
                    'body' => 'Clique nas sugestões que realmente fazem parte do seu processo. Não é necessário usar todas.',
                ],
                [
                    'target' => '[data-tour="service-fields-current"]',
                    'title' => 'Campos usados',
                    'body' => 'Organize, remova ou ajuste os campos. As opções avançadas podem ficar fechadas.',
                ],
                [
                    'target' => '[data-tour="service-fields-save"]',
                    'title' => 'Salve ao terminar',
                    'body' => 'O sistema preserva a configuração anterior para não mudar orçamentos antigos.',
                ],
            ],
        ],
        'pricing-overview' => [
            'key' => 'pricing-overview',
            'version' => 1,
            'title' => 'Comece pelos preços essenciais',
            'routes' => ['tenant.pricing.index'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="pricing-services"]',
                    'title' => 'Um serviço por vez',
                    'body' => 'Veja quais serviços ainda precisam de preço. Não é necessário configurar tudo no mesmo dia.',
                ],
                [
                    'target' => '[data-tour="pricing-configure"]',
                    'title' => 'Configure ou revise',
                    'body' => 'Abra um serviço, informe os valores e teste uma combinação antes de usar.',
                ],
            ],
        ],
        'pricing-editor' => [
            'key' => 'pricing-editor',
            'version' => 2,
            'title' => 'Configure sem perder tempo',
            'routes' => ['tenant.pricing.edit'],
            'roles' => ['OWNER'],
            'steps' => [
                [
                    'target' => '[data-tour="pricing-guided-steps"]',
                    'title' => 'Uma etapa por vez',
                    'body' => 'DTF, Silk, Sublimação e Bordado usam perguntas curtas e exemplos do dia a dia. As regras técnicas ficam escondidas.',
                ],
                [
                    'target' => '[data-tour="pricing-preview"]',
                    'title' => 'Confira antes de salvar',
                    'body' => 'Mude o pedido de exemplo e veja a conta completa imediatamente.',
                ],
                [
                    'target' => '[x-ref="helpButton"]',
                    'title' => 'Ajuda quando precisar',
                    'body' => 'Abra Entenda esta tela para saber o motivo de cada campo sem sair da configuração.',
                ],
            ],
        ],
    ],

    'articles' => [
        'primeiros-passos' => [
            'title' => 'Primeiros passos',
            'summary' => 'Confirme sua estamparia, escolha os serviços e convide a equipe.',
            'category' => 'Comece aqui',
            'roles' => ['OWNER'],
            'route' => 'tenant.onboarding.show',
            'steps' => [
                'Revise o nome e o fuso horário da estamparia.',
                'Mantenha ativos somente os serviços que você utiliza.',
                'Convide alguém da equipe agora ou deixe para depois.',
            ],
        ],
        'visao-geral' => [
            'title' => 'Entendendo a visão geral',
            'summary' => 'Veja onde acompanhar os principais números e atalhos.',
            'category' => 'Uso diário',
            'roles' => ['OWNER', 'USER'],
            'route' => 'tenant.dashboard',
            'steps' => [
                'Os cartões mostram equipe, convites e serviços disponíveis.',
                'Os atalhos variam de acordo com o seu papel.',
                'Use o botão Tutorial desta página para rever as orientações.',
            ],
        ],
        'equipe' => [
            'title' => 'Convidando e gerenciando a equipe',
            'summary' => 'Envie convites e controle quem pode acessar a estamparia.',
            'category' => 'Configuração',
            'roles' => ['OWNER'],
            'route' => 'tenant.users',
            'steps' => [
                'Digite o e-mail da pessoa e escolha o papel.',
                'O convite é enviado pela fila de e-mail e expira em sete dias.',
                'Você pode suspender o acesso sem apagar o histórico.',
            ],
        ],
        'tipos-de-servico' => [
            'title' => 'Tipos de serviço',
            'summary' => 'Ative os serviços utilizados e mantenha a lista simples.',
            'category' => 'Configuração',
            'roles' => ['OWNER'],
            'route' => 'tenant.service-types.index',
            'steps' => [
                'DTF, Silk Screen, Sublimação e Bordado já vêm configurados.',
                'Desative o que sua estamparia não oferece.',
                'Crie outro serviço somente quando realmente precisar.',
            ],
        ],
        'campos-do-servico' => [
            'title' => 'Campos do serviço',
            'summary' => 'Defina quais informações serão pedidas ao usar cada serviço.',
            'category' => 'Configuração',
            'roles' => ['OWNER'],
            'route' => 'tenant.service-types.index',
            'steps' => [
                'Abra Campos no serviço desejado.',
                'Adicione apenas as informações necessárias para orçamento e produção.',
                'Itens que alteram preço serão usados pelo motor de precificação na Sprint 3.',
            ],
        ],
        'configurar-precos' => [
            'title' => 'Configurando preços',
            'summary' => 'Siga um passo a passo curto e confira um pedido antes de salvar.',
            'category' => 'Configuração',
            'roles' => ['OWNER'],
            'route' => 'tenant.pricing.index',
            'steps' => [
                'Abra Precificação e escolha um serviço.',
                'No DTF, informe preço do metro, largura útil e aplicação.',
                'No Silk, preencha a tabela por quantidade e número de cores.',
                'Na Sublimação, use quantidade e tipo de aplicação.',
                'No Bordado, use quantidade, faixa de pontos e matriz opcional.',
                'Use tintas e efeitos apenas quando realmente mudarem o valor.',
                'Confira o pedido de exemplo e salve a configuração.',
            ],
        ],
        'entendendo-variacoes-de-preco' => [
            'title' => 'Tinta, efeito, cores e outras variações',
            'summary' => 'Use adicionais somente quando sua estamparia já cobra diferente.',
            'category' => 'Precificação',
            'roles' => ['OWNER'],
            'route' => 'tenant.pricing.index',
            'steps' => [
                'No Silk, a tabela principal define o preço por quantidade e cores.',
                'Base branca pode contar como uma cor, virar adicional ou não alterar o preço.',
                'Plastisol, Puff e outros adicionais são opcionais e somados por peça.',
                'O cadastro de insumos permitirá comparar esses valores com o custo real na Sprint 4.',
            ],
        ],
    ],
];
