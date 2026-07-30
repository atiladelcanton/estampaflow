<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = $this->silkDefaults();

        DB::table('service_types')
            ->where('code', 'SILK')
            ->orderBy('id')
            ->get()
            ->each(function (object $serviceType) use ($defaults): void {
                DB::transaction(function () use ($serviceType, $defaults): void {
                    $activeVersionId = is_string($serviceType->active_schema_version_id)
                        ? $serviceType->active_schema_version_id
                        : null;

                    $existingParameters = $activeVersionId === null
                        ? collect()
                        : DB::table('service_parameter_definitions')
                            ->where('tenant_id', $serviceType->tenant_id)
                            ->where('schema_version_id', $activeVersionId)
                            ->orderBy('sort_order')
                            ->orderBy('label')
                            ->get();

                    $existingKeys = $existingParameters
                        ->pluck('key')
                        ->filter(static fn (mixed $key): bool => is_string($key))
                        ->all();

                    $missingDefaults = array_values(array_filter(
                        $defaults,
                        static fn (array $definition): bool => ! in_array($definition['key'], $existingKeys, true),
                    ));

                    if ($missingDefaults === []) {
                        return;
                    }

                    $now = now();
                    $newVersionId = (string) Str::ulid();
                    $nextVersion = ((int) DB::table('service_type_schema_versions')
                        ->where('tenant_id', $serviceType->tenant_id)
                        ->where('service_type_id', $serviceType->id)
                        ->max('version')) + 1;

                    DB::table('service_type_schema_versions')->insert([
                        'id' => $newVersionId,
                        'tenant_id' => $serviceType->tenant_id,
                        'service_type_id' => $serviceType->id,
                        'version' => $nextVersion,
                        'status' => 'ACTIVE',
                        'created_by' => null,
                        'activated_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $rows = [];

                    foreach ($existingParameters as $parameter) {
                        $rows[] = [
                            'id' => (string) Str::ulid(),
                            'tenant_id' => $serviceType->tenant_id,
                            'schema_version_id' => $newVersionId,
                            'key' => $parameter->key,
                            'label' => $parameter->label,
                            'field_type' => $parameter->field_type,
                            'unit' => $parameter->unit,
                            'required' => $parameter->required,
                            'affects_pricing' => $parameter->affects_pricing,
                            'options' => $parameter->options,
                            'validation_rules' => $parameter->validation_rules,
                            'default_value' => $parameter->default_value,
                            'sort_order' => $parameter->sort_order,
                            'active' => $parameter->active,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $nextSortOrder = max(
                        0,
                        ...$existingParameters
                            ->pluck('sort_order')
                            ->map(static fn (mixed $value): int => (int) $value)
                            ->all(),
                    );

                    foreach ($missingDefaults as $definition) {
                        $nextSortOrder += 10;
                        $rows[] = [
                            'id' => (string) Str::ulid(),
                            'tenant_id' => $serviceType->tenant_id,
                            'schema_version_id' => $newVersionId,
                            'key' => $definition['key'],
                            'label' => $definition['label'],
                            'field_type' => $definition['field_type'],
                            'unit' => $definition['unit'],
                            'required' => $definition['required'],
                            'affects_pricing' => $definition['affects_pricing'],
                            'options' => $definition['options'] === null
                                ? null
                                : json_encode($definition['options'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                            'validation_rules' => null,
                            'default_value' => $definition['default_value'] === null
                                ? null
                                : json_encode($definition['default_value'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                            'sort_order' => $nextSortOrder,
                            'active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table('service_parameter_definitions')->insert($rows);

                    DB::table('service_type_schema_versions')
                        ->where('tenant_id', $serviceType->tenant_id)
                        ->where('service_type_id', $serviceType->id)
                        ->where('id', '!=', $newVersionId)
                        ->where('status', 'ACTIVE')
                        ->update([
                            'status' => 'RETIRED',
                            'updated_at' => $now,
                        ]);

                    DB::table('service_types')
                        ->where('id', $serviceType->id)
                        ->where('tenant_id', $serviceType->tenant_id)
                        ->update([
                            'active' => true,
                            'active_schema_version_id' => $newVersionId,
                            'updated_at' => $now,
                        ]);
                });
            });
    }

    public function down(): void
    {
        // Alteração de dados intencionalmente irreversível para preservar histórico e customizações.
    }

    /** @return list<array<string, mixed>> */
    private function silkDefaults(): array
    {
        return [
            $this->definition('screen_colors', 'Quantidade de cores', 'INTEGER', 'cores', true, true),
            $this->definition(
                'ink_system',
                'Sistema de tinta',
                'SELECT',
                null,
                true,
                true,
                ['Base água', 'Plastisol', 'Discharge/corrosão', 'Silicone', 'Híbrida/alto sólido', 'Outro'],
            ),
            $this->definition(
                'print_effect',
                'Efeito ou acabamento',
                'SELECT',
                null,
                true,
                true,
                [
                    'Sem efeito',
                    'Puff/relevo',
                    'Alta densidade/3D',
                    'Camurça/suede',
                    'Gel/alto brilho',
                    'Metalizado',
                    'Perolado/shimmer',
                    'Glitter',
                    'Refletivo',
                    'Fosforescente/brilha no escuro',
                    'Fluorescente/neon',
                    'Craquelado/vintage',
                    'Foil',
                    'Outro',
                ],
                'Sem efeito',
            ),
            $this->definition('width_cm', 'Largura da estampa', 'DECIMAL', 'cm', true, true),
            $this->definition('height_cm', 'Altura da estampa', 'DECIMAL', 'cm', true, true),
            $this->definition(
                'white_base',
                'Base branca',
                'SELECT',
                null,
                false,
                true,
                ['Automático', 'Sim', 'Não'],
                'Automático',
            ),
            $this->definition('technical_notes', 'Observação técnica', 'TEXT', null, false, false),
        ];
    }

    /**
     * @param  list<string>|null  $options
     * @return array<string, mixed>
     */
    private function definition(
        string $key,
        string $label,
        string $fieldType,
        ?string $unit,
        bool $required,
        bool $affectsPricing,
        ?array $options = null,
        mixed $defaultValue = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'field_type' => $fieldType,
            'unit' => $unit,
            'required' => $required,
            'affects_pricing' => $affectsPricing,
            'options' => $options,
            'default_value' => $defaultValue,
        ];
    }
};
