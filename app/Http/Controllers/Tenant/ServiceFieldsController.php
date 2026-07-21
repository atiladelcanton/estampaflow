<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\ServiceCatalog\Actions\UpdateServiceFieldsAction;
use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ServiceFieldsController
{
    public function show(string $serviceType): View
    {
        $service = ServiceType::query()->findOrFail($serviceType);
        $source = $service->schemaVersions()
            ->where('status', ServiceSchemaStatus::DRAFT->value)
            ->first();

        if (! $source instanceof ServiceTypeSchemaVersion) {
            $source = $service->activeSchemaVersion()->first();
        }

        /** @var list<array<string, mixed>> $initialFields */
        $initialFields = $source instanceof ServiceTypeSchemaVersion
            ? $source->parameters()
                ->get()
                ->map(fn (ServiceParameterDefinition $parameter): array => [
                    'key' => $parameter->key,
                    'label' => $parameter->label,
                    'field_type' => $parameter->field_type->value,
                    'unit' => $parameter->unit ?? '',
                    'required' => $parameter->required,
                    'affects_pricing' => $parameter->affects_pricing,
                    'options_text' => implode("\n", $parameter->options ?? []),
                    'default_value' => is_scalar($parameter->default_value)
                        ? (string) $parameter->default_value
                        : '',
                    'active' => $parameter->active,
                ])
                ->values()
                ->all()
            : [];

        return view('tenant.service-types.schema', [
            'serviceType' => $service,
            'initialFields' => $initialFields,
            'fieldTypes' => ServiceParameterFieldType::cases(),
            'fieldPresets' => $this->fieldPresets(),
        ]);
    }

    public function update(
        Request $request,
        string $serviceType,
        UpdateServiceFieldsAction $action,
    ): RedirectResponse {
        $service = ServiceType::query()->findOrFail($serviceType);

        $validated = $request->validate([
            'fields' => ['sometimes', 'array', 'max:40'],
            'fields.*.key' => ['nullable', 'string', 'max:100'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.field_type' => ['required', 'string', Rule::enum(ServiceParameterFieldType::class)],
            'fields.*.unit' => ['nullable', 'string', 'max:40'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.affects_pricing' => ['nullable', 'boolean'],
            'fields.*.options_text' => ['nullable', 'string', 'max:4000'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.active' => ['nullable', 'boolean'],
        ]);

        $rawFields = $validated['fields'] ?? [];

        if (! is_array($rawFields)) {
            $rawFields = [];
        }

        /** @var list<array<string, mixed>> $fieldsInput */
        $fieldsInput = array_values($rawFields);
        $definitions = $this->toDefinitions($fieldsInput);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $action->execute($actor, $service, $definitions);

        return back()->with(
            'success',
            'Campos atualizados. Os próximos orçamentos já usarão esta configuração.',
        );
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return list<SaveServiceParameterData>
     */
    private function toDefinitions(array $fields): array
    {
        $definitions = [];
        $usedKeys = [];

        foreach ($fields as $index => $field) {
            $label = trim((string) ($field['label'] ?? ''));
            $key = trim((string) ($field['key'] ?? ''));

            if ($key === '') {
                $key = $this->uniqueKeyFromLabel($label, $usedKeys);
            }

            $usedKeys[] = $key;
            $type = ServiceParameterFieldType::from((string) $field['field_type']);
            $options = collect(preg_split('/\r\n|\r|\n/', (string) ($field['options_text'] ?? '')) ?: [])
                ->map(static fn (string $option): string => trim($option))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $defaultValue = trim((string) ($field['default_value'] ?? ''));

            $definitions[] = new SaveServiceParameterData(
                key: $key,
                label: $label,
                fieldType: $type,
                unit: trim((string) ($field['unit'] ?? '')) ?: null,
                required: filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOL),
                affectsPricing: filter_var($field['affects_pricing'] ?? false, FILTER_VALIDATE_BOOL),
                options: $type->requiresOptions() ? $options : null,
                validationRules: null,
                defaultValue: $defaultValue !== '' ? $defaultValue : null,
                sortOrder: ($index + 1) * 10,
                active: filter_var($field['active'] ?? true, FILTER_VALIDATE_BOOL),
            );
        }

        return $definitions;
    }

    /** @param list<string> $usedKeys */
    private function uniqueKeyFromLabel(string $label, array $usedKeys): string
    {
        $base = Str::snake(Str::ascii($label));

        if ($base === '' || preg_match('/^[a-z]/', $base) !== 1) {
            $base = 'campo';
        }

        $key = $base;
        $suffix = 2;

        while (in_array($key, $usedKeys, true)) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        return $key;
    }

    /** @return array<string, array<string, mixed>> */
    private function fieldPresets(): array
    {
        return [
            'width_cm' => $this->preset(
                'width_cm',
                'Largura',
                'Medida em centímetros',
                ServiceParameterFieldType::DECIMAL,
                'cm',
                true,
                true,
            ),
            'height_cm' => $this->preset(
                'height_cm',
                'Altura',
                'Medida em centímetros',
                ServiceParameterFieldType::DECIMAL,
                'cm',
                true,
                true,
            ),
            'print_size' => $this->selectPreset(
                'print_size',
                'Tamanho da estampa',
                'A6, A5, A4, A3 ou personalizado',
                ['A6', 'A5', 'A4', 'A3', 'Personalizado'],
                true,
                true,
            ),
            'screen_colors' => $this->preset(
                'screen_colors',
                'Quantidade de cores',
                'Número de cores usadas',
                ServiceParameterFieldType::INTEGER,
                'cores',
                true,
                true,
            ),
            'material' => $this->preset(
                'material',
                'Material ou tecido',
                'Ex.: algodão, poliéster ou papel',
                ServiceParameterFieldType::TEXT,
                null,
                false,
                false,
            ),
            'finishing' => $this->preset(
                'finishing',
                'Acabamento',
                'Detalhes extras do serviço',
                ServiceParameterFieldType::TEXT,
                null,
                false,
                false,
            ),
            'notes' => $this->preset(
                'notes',
                'Observações do serviço',
                'Informações livres para a equipe',
                ServiceParameterFieldType::TEXT,
                null,
                false,
                false,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function preset(
        string $key,
        string $label,
        string $hint,
        ServiceParameterFieldType $type,
        ?string $unit,
        bool $required,
        bool $affectsPricing,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'hint' => $hint,
            'field_type' => $type->value,
            'unit' => $unit ?? '',
            'required' => $required,
            'affects_pricing' => $affectsPricing,
            'options_text' => '',
            'default_value' => '',
            'active' => true,
        ];
    }

    /**
     * @param  list<string>  $options
     * @return array<string, mixed>
     */
    private function selectPreset(
        string $key,
        string $label,
        string $hint,
        array $options,
        bool $required,
        bool $affectsPricing,
    ): array {
        $field = $this->preset(
            $key,
            $label,
            $hint,
            ServiceParameterFieldType::SELECT,
            null,
            $required,
            $affectsPricing,
        );
        $field['options_text'] = implode("\n", $options);

        return $field;
    }
}
