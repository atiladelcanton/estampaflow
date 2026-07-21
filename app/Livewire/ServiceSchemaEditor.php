<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\ServiceCatalog\Actions\UpdateServiceFieldsAction;
use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

final class ServiceSchemaEditor extends Component
{
    public string $serviceTypeId;

    /** @var list<array<string, mixed>> */
    public array $parameters = [];

    public function mount(string $serviceTypeId): void
    {
        $this->serviceTypeId = $serviceTypeId;
        $this->loadFields();
    }

    public function addSuggestedField(string $preset): void
    {
        $definition = $this->fieldPresets()[$preset] ?? null;

        if (! is_array($definition)) {
            $this->addError('parameters', 'Campo sugerido inválido.');

            return;
        }

        $alreadyExists = collect($this->parameters)->contains(
            fn (array $parameter): bool => ($parameter['key'] ?? null) === $definition['key'],
        );

        if ($alreadyExists) {
            $this->addError('parameters', 'Esse campo já foi adicionado.');

            return;
        }

        $this->parameters[] = $definition;
        $this->resetErrorBag('parameters');
    }

    public function addCustomField(): void
    {
        $this->parameters[] = [
            'key' => '',
            'label' => '',
            'field_type' => ServiceParameterFieldType::TEXT->value,
            'unit' => '',
            'required' => false,
            'affects_pricing' => false,
            'options_text' => '',
            'default_value' => null,
            'active' => true,
        ];
    }

    public function removeParameter(int $index): void
    {
        unset($this->parameters[$index]);
        $this->parameters = array_values($this->parameters);
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->parameters[$index - 1], $this->parameters[$index])) {
            return;
        }

        [$this->parameters[$index - 1], $this->parameters[$index]] = [
            $this->parameters[$index],
            $this->parameters[$index - 1],
        ];
    }

    public function moveDown(int $index): void
    {
        if (! isset($this->parameters[$index], $this->parameters[$index + 1])) {
            return;
        }

        [$this->parameters[$index], $this->parameters[$index + 1]] = [
            $this->parameters[$index + 1],
            $this->parameters[$index],
        ];
    }

    public function save(UpdateServiceFieldsAction $action): void
    {
        $serviceType = ServiceType::query()->findOrFail($this->serviceTypeId);
        $action->execute($this->authenticatedUser(), $serviceType, $this->toDefinitions());

        $this->loadFields();
        session()->flash('success', 'Alterações salvas. Os novos orçamentos já usarão estes campos.');
    }

    public function render(): View
    {
        $serviceType = ServiceType::query()
            ->with('activeSchemaVersion.parameters')
            ->findOrFail($this->serviceTypeId);

        $currentKeys = collect($this->parameters)
            ->pluck('key')
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->all();

        $suggestedFields = collect($this->fieldPresets())
            ->reject(fn (array $field): bool => in_array($field['key'], $currentKeys, true))
            ->all();

        return view('livewire.service-schema-editor', [
            'serviceType' => $serviceType,
            'fieldTypes' => ServiceParameterFieldType::cases(),
            'suggestedFields' => $suggestedFields,
        ]);
    }

    private function loadFields(): void
    {
        $serviceType = ServiceType::query()->findOrFail($this->serviceTypeId);
        $draft = $serviceType->schemaVersions()
            ->where('status', ServiceSchemaStatus::DRAFT->value)
            ->with('parameters')
            ->first();

        $source = $draft;

        if (! $source instanceof ServiceTypeSchemaVersion) {
            $source = $serviceType->activeSchemaVersion()->with('parameters')->first();
        }

        $this->parameters = [];

        if ($source instanceof ServiceTypeSchemaVersion) {
            $this->hydrateParameters($source);
        }
    }

    private function hydrateParameters(ServiceTypeSchemaVersion $version): void
    {
        $this->parameters = $version->parameters->map(fn (ServiceParameterDefinition $parameter): array => [
            'key' => $parameter->key,
            'label' => $parameter->label,
            'field_type' => $parameter->field_type->value,
            'unit' => $parameter->unit ?? '',
            'required' => $parameter->required,
            'affects_pricing' => $parameter->affects_pricing,
            'options_text' => implode("\n", $parameter->options ?? []),
            'default_value' => is_scalar($parameter->default_value) ? $parameter->default_value : null,
            'active' => $parameter->active,
        ])->values()->all();
    }

    /** @return list<SaveServiceParameterData> */
    private function toDefinitions(): array
    {
        $definitions = [];
        $usedKeys = [];

        foreach ($this->parameters as $index => $parameter) {
            $label = trim((string) ($parameter['label'] ?? ''));
            $key = trim((string) ($parameter['key'] ?? ''));

            if ($key === '') {
                $key = $this->uniqueKeyFromLabel($label, $usedKeys);
            }

            $usedKeys[] = $key;
            $type = ServiceParameterFieldType::from((string) $parameter['field_type']);
            $options = collect(preg_split('/\r\n|\r|\n/', (string) ($parameter['options_text'] ?? '')) ?: [])
                ->map(fn (string $option): string => trim($option))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $definitions[] = new SaveServiceParameterData(
                key: $key,
                label: $label,
                fieldType: $type,
                unit: trim((string) ($parameter['unit'] ?? '')) ?: null,
                required: (bool) ($parameter['required'] ?? false),
                affectsPricing: (bool) ($parameter['affects_pricing'] ?? false),
                options: $type->requiresOptions() ? $options : null,
                validationRules: null,
                defaultValue: $parameter['default_value'] ?? null,
                sortOrder: ($index + 1) * 10,
                active: (bool) ($parameter['active'] ?? true),
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
            'width_cm' => $this->preset('width_cm', 'Largura', ServiceParameterFieldType::DECIMAL, 'cm', true, true),
            'height_cm' => $this->preset('height_cm', 'Altura', ServiceParameterFieldType::DECIMAL, 'cm', true, true),
            'print_size' => $this->selectPreset('print_size', 'Tamanho da estampa', ['A6', 'A5', 'A4', 'A3', 'Personalizado'], true, true),
            'screen_colors' => $this->preset('screen_colors', 'Quantidade de cores', ServiceParameterFieldType::INTEGER, 'cores', true, true),
            'material' => $this->preset('material', 'Material ou tecido', ServiceParameterFieldType::TEXT, null, false, false),
            'finishing' => $this->preset('finishing', 'Acabamento', ServiceParameterFieldType::TEXT, null, false, false),
            'notes' => $this->preset('notes', 'Observações do serviço', ServiceParameterFieldType::TEXT, null, false, false),
        ];
    }

    /** @return array<string, mixed> */
    private function preset(
        string $key,
        string $label,
        ServiceParameterFieldType $type,
        ?string $unit,
        bool $required,
        bool $affectsPricing,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'field_type' => $type->value,
            'unit' => $unit ?? '',
            'required' => $required,
            'affects_pricing' => $affectsPricing,
            'options_text' => '',
            'default_value' => null,
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
        array $options,
        bool $required,
        bool $affectsPricing,
    ): array {
        $field = $this->preset($key, $label, ServiceParameterFieldType::SELECT, null, $required, $affectsPricing);
        $field['options_text'] = implode("\n", $options);

        return $field;
    }

    private function authenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
