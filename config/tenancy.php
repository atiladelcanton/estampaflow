<?php

use App\Domains\Tenancy\Models\Tenant;
use App\Support\Tenancy\UlidTenantIdGenerator;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\Domain;

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant model
    |--------------------------------------------------------------------------
    |
    | O projeto usa o model do pacote apenas como infraestrutura de resolução.
    | Os dados operacionais continuam no mesmo banco e são isolados por
    | tenant_id + TenantContext fail closed.
    |
    */
    'tenant_model' => Tenant::class,
    'id_generator' => UlidTenantIdGenerator::class,
    'domain_model' => Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Domínios centrais e base dos subdomínios
    |--------------------------------------------------------------------------
    */
    'central_domains' => array_values(array_filter(array_map(
        static fn (string $domain): string => trim($domain),
        explode(',', (string) env('CENTRAL_DOMAINS', env('CENTRAL_DOMAIN', 'app.estamparia.test'))),
    ))),

    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'estamparia.test'),

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    |
    | Single database: não usamos DatabaseTenancyBootstrapper. O pacote
    | inicializa a identidade do tenant e o TenantContext próprio aplica o
    | isolamento da aplicação.
    |
    */
    'bootstrappers' => [
        QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'managers' => [],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [],
        'root_override' => [],
        'suffix_storage_path' => false,
        'asset_helper_tenancy' => false,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [],
    'routes' => true,
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];
