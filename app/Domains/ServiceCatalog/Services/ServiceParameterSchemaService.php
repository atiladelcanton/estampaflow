<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Services;

use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ServiceParameterSchemaService
{
    /** @param list<SaveServiceParameterData> $parameters */
    public function validateDefinitions(array $parameters): void
    {
        $keys = [];

        foreach ($parameters as $parameter) {
            $key = Str::snake(trim($parameter->key));

            if ($key === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                throw ValidationException::withMessages([
                    'parameters' => "Não foi possível preparar o campo '{$parameter->label}'. Revise o nome informado.",
                ]);
            }

            if (in_array($key, $keys, true)) {
                throw ValidationException::withMessages([
                    'parameters' => 'Existem dois campos com o mesmo nome. Renomeie um deles.',
                ]);
            }

            if (trim($parameter->label) === '') {
                throw ValidationException::withMessages([
                    'parameters' => 'Todos os campos precisam de um nome.',
                ]);
            }

            if ($parameter->fieldType->requiresOptions() && ($parameter->options === null || $parameter->options === [])) {
                throw ValidationException::withMessages([
                    'parameters' => "O campo '{$parameter->label}' precisa de pelo menos uma opção.",
                ]);
            }

            $keys[] = $key;
        }
    }

    /** @return array<string, list<mixed>> */
    public function buildValidationRules(ServiceTypeSchemaVersion $version): array
    {
        $rules = [];

        foreach ($version->parameters()->where('active', true)->get() as $parameter) {
            $fieldRules = $parameter->required ? ['required'] : ['nullable'];

            $fieldRules[] = match ($parameter->field_type) {
                ServiceParameterFieldType::INTEGER => 'integer',
                ServiceParameterFieldType::DECIMAL => 'numeric',
                ServiceParameterFieldType::BOOLEAN => 'boolean',
                ServiceParameterFieldType::MULTISELECT => 'array',
                default => 'string',
            };

            if ($parameter->field_type === ServiceParameterFieldType::SELECT) {
                $fieldRules[] = Rule::in($parameter->options ?? []);
            }

            $rules[$parameter->key] = $fieldRules;

            if ($parameter->field_type === ServiceParameterFieldType::MULTISELECT) {
                $rules[$parameter->key.'.*'] = [Rule::in($parameter->options ?? [])];
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function normalize(ServiceTypeSchemaVersion $version, array $values): array
    {
        $normalized = [];

        foreach ($version->parameters()->where('active', true)->get() as $parameter) {
            $value = $values[$parameter->key] ?? $parameter->default_value;

            $normalized[$parameter->key] = match ($parameter->field_type) {
                ServiceParameterFieldType::INTEGER => $value === null ? null : (int) $value,
                ServiceParameterFieldType::DECIMAL => $value === null ? null : (string) $value,
                ServiceParameterFieldType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOL),
                ServiceParameterFieldType::MULTISELECT => is_array($value) ? array_values($value) : [],
                default => $value === null ? null : trim((string) $value),
            };
        }

        return $normalized;
    }
}
