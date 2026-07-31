<?php

declare(strict_types=1);

namespace App\Application\Pricing\Actions;

use App\Application\Pricing\Data\SavePriceRuleData;
use App\Domains\Pricing\Enums\PriceTableStatus;
use App\Domains\Pricing\Models\ServicePriceRule;
use App\Domains\Pricing\Models\ServicePriceTable;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SaveServicePricingAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  list<SavePriceRuleData>  $rules
     * @param  array<string, mixed>  $settings
     */
    public function execute(
        User $actor,
        ServiceType $service,
        PricingStrategy $strategy,
        array $rules,
        array $settings,
        ?string $validFrom,
        ?string $validUntil,
    ): ServicePriceTable {
        if ($service->active_schema_version_id === null) {
            throw ValidationException::withMessages([
                'pricing' => 'Defina os campos do serviço antes de configurar os preços.',
            ]);
        }

        if ($rules === []) {
            throw ValidationException::withMessages(['rules' => 'Adicione pelo menos uma regra de preço.']);
        }

        $this->validateRules($service, $strategy, $rules, $settings);

        return DB::transaction(function () use ($actor, $service, $strategy, $rules, $settings, $validFrom, $validUntil): ServicePriceTable {
            $previous = $service->activePriceTable()->first();
            $nextVersion = ((int) $service->priceTables()->max('version')) + 1;

            if ($previous instanceof ServicePriceTable) {
                $previous->forceFill(['status' => PriceTableStatus::RETIRED])->save();
            }

            $table = ServicePriceTable::query()->create([
                'service_type_id' => $service->getKey(),
                'schema_version_id' => $service->active_schema_version_id,
                'version' => $nextVersion,
                'status' => PriceTableStatus::ACTIVE,
                'strategy' => $strategy,
                'currency' => 'BRL',
                'settings' => $settings,
                'priority' => 100,
                'valid_from' => $validFrom ?: null,
                'valid_until' => $validUntil ?: null,
                'created_by' => $actor->getKey(),
                'activated_at' => now(),
            ]);

            foreach ($rules as $rule) {
                ServicePriceRule::query()->create([
                    'price_table_id' => $table->getKey(),
                    'name' => $rule->name,
                    'min_quantity' => $rule->minQuantity,
                    'max_quantity' => $rule->maxQuantity,
                    'conditions' => $rule->conditions,
                    'unit_amount_minor' => $rule->unitAmountMinor,
                    'rate_value' => $rule->rateValue,
                    'rate_unit' => $rule->rateUnit,
                    'setup_amount_minor' => $rule->setupAmountMinor,
                    'minimum_amount_minor' => $rule->minimumAmountMinor,
                    'priority' => $rule->priority,
                    'sort_order' => $rule->sortOrder,
                    'active' => true,
                ]);
            }

            $service->forceFill([
                'pricing_strategy' => $strategy,
                'active_price_table_id' => $table->getKey(),
            ])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'service_pricing.activated',
                tenantId: (string) $service->tenant_id,
                actorId: (string) $actor->getKey(),
                auditableType: ServicePriceTable::class,
                auditableId: (string) $table->getKey(),
                before: $previous instanceof ServicePriceTable ? [
                    'price_table_id' => $previous->getKey(),
                    'version' => $previous->version,
                ] : null,
                after: [
                    'service_type_id' => $service->getKey(),
                    'version' => $table->version,
                    'strategy' => $strategy->value,
                    'rules' => count($rules),
                ],
            ));

            return $table->load('rules');
        });
    }

    /**
     * @param  list<SavePriceRuleData>  $rules
     * @param  array<string, mixed>  $settings
     */
    private function validateRules(
        ServiceType $service,
        PricingStrategy $strategy,
        array $rules,
        array $settings,
    ): void {
        if (
            $strategy === PricingStrategy::AREA
            && (
                ! is_string($settings['width_parameter'] ?? null)
                || ! is_string($settings['height_parameter'] ?? null)
            )
        ) {
            throw ValidationException::withMessages([
                'strategy' => 'Escolha os campos de largura e altura usados no cálculo por área.',
            ]);
        }

        if (
            $strategy === PricingStrategy::STITCH_RANGE
            && ! is_string($settings['stitch_parameter'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'strategy' => 'Escolha o campo que informa a quantidade de pontos.',
            ]);
        }

        if (
            $strategy === PricingStrategy::ROLL_LENGTH
            && (
                (int) ($settings['meter_cost_minor'] ?? 0) <= 0
                || ! is_string($settings['usable_width_cm'] ?? null)
            )
        ) {
            throw ValidationException::withMessages([
                'meter_cost' => 'Informe o custo do metro e a largura útil do material.',
            ]);
        }
        /** @var list<string> $allowedParameters */
        $allowedParameters = ServiceParameterDefinition::query()
            ->where('schema_version_id', $service->active_schema_version_id)
            ->where('affects_pricing', true)
            ->where('active', true)
            ->pluck('key')
            ->map(static fn (mixed $key): string => (string) $key)
            ->all();

        /** @var list<string> $signatures */
        $signatures = [];

        foreach ($rules as $index => $rule) {
            if (
                in_array($strategy, [PricingStrategy::UNIT, PricingStrategy::QUANTITY_TIER, PricingStrategy::MATRIX], true)
                && $rule->unitAmountMinor === null
            ) {
                throw ValidationException::withMessages([
                    "rules.{$index}.unit_price" => 'Informe o preço por peça desta regra.',
                ]);
            }

            if (
                in_array($strategy, [PricingStrategy::AREA, PricingStrategy::STITCH_RANGE], true)
                && $rule->rateValue === null
            ) {
                throw ValidationException::withMessages([
                    "rules.{$index}.rate_value" => 'Informe a taxa usada nesta regra.',
                ]);
            }

            if ($rule->minQuantity !== null && $rule->maxQuantity !== null && $rule->minQuantity > $rule->maxQuantity) {
                throw ValidationException::withMessages([
                    "rules.{$index}.max_quantity" => 'A quantidade final deve ser maior ou igual à inicial.',
                ]);
            }

            foreach ($rule->conditions as $condition) {
                if (! in_array($condition['parameter'], $allowedParameters, true)) {
                    throw ValidationException::withMessages([
                        "rules.{$index}.conditions" => 'Uma condição usa um campo que não está marcado para precificação.',
                    ]);
                }
            }

            $signature = json_encode([
                $rule->minQuantity,
                $rule->maxQuantity,
                $rule->conditions,
                $rule->priority,
            ], JSON_THROW_ON_ERROR);

            if (in_array($signature, $signatures, true)) {
                throw ValidationException::withMessages([
                    "rules.{$index}" => 'Existem duas regras idênticas. Ajuste a faixa, a condição ou a prioridade.',
                ]);
            }

            $signatures[] = $signature;
        }
    }
}
