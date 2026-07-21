<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\ServiceCatalog\Actions\CreateServiceTypeAction;
use App\Application\ServiceCatalog\Actions\UpdateServiceTypeAction;
use App\Application\ServiceCatalog\Data\CreateServiceTypeData;
use App\Application\ServiceCatalog\Data\UpdateServiceTypeData;
use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class ServiceTypeForm extends Component
{
    public ?string $serviceTypeId = null;

    public string $name = '';

    public string $code = '';

    public ?string $description = null;

    public string $pricingMode = 'AUTOMATIC';

    public ?string $pricingStrategy = 'UNIT';

    public bool $requiresArt = true;

    public bool $allowsMultiplePositions = true;

    public int $sortOrder = 0;

    public function mount(?string $serviceTypeId = null): void
    {
        $this->serviceTypeId = $serviceTypeId;

        if ($serviceTypeId === null) {
            return;
        }

        $serviceType = ServiceType::query()->findOrFail($serviceTypeId);
        $this->name = $serviceType->name;
        $this->code = $serviceType->code;
        $this->description = $serviceType->description;
        $this->pricingMode = $serviceType->pricing_mode->value;
        $this->pricingStrategy = $serviceType->pricing_strategy?->value;
        $this->requiresArt = $serviceType->requires_art;
        $this->allowsMultiplePositions = $serviceType->allows_multiple_positions;
        $this->sortOrder = $serviceType->sort_order;
    }

    public function save(CreateServiceTypeAction $create, UpdateServiceTypeAction $update): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'code' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[A-Za-z0-9_ -]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pricingMode' => ['required', Rule::enum(PricingMode::class)],
            'pricingStrategy' => ['nullable', Rule::enum(PricingStrategy::class)],
            'requiresArt' => ['boolean'],
            'allowsMultiplePositions' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
        ]);

        $actor = $this->authenticatedUser();
        $mode = PricingMode::from($validated['pricingMode']);
        $strategy = $validated['pricingStrategy'] === null || $validated['pricingStrategy'] === ''
            ? null
            : PricingStrategy::from($validated['pricingStrategy']);

        if ($mode !== PricingMode::MANUAL && $strategy === null) {
            $this->addError('pricingStrategy', 'Selecione uma estratégia para o preço automático ou híbrido.');

            return null;
        }

        if ($mode === PricingMode::MANUAL) {
            $strategy = null;
        }

        if ($this->serviceTypeId === null) {
            $serviceType = $create->execute($actor, new CreateServiceTypeData(
                name: $validated['name'],
                code: $validated['code'],
                description: $validated['description'],
                pricingMode: $mode,
                pricingStrategy: $strategy,
                requiresArt: $validated['requiresArt'],
                allowsMultiplePositions: $validated['allowsMultiplePositions'],
                sortOrder: $validated['sortOrder'],
            ));

            session()->flash('success', 'Serviço criado. Agora defina os campos que deverão ser preenchidos ao utilizá-lo.');

            return redirect()->route('tenant.service-types.fields', ['serviceType' => $serviceType->getKey()]);
        }

        $serviceType = ServiceType::query()->findOrFail($this->serviceTypeId);
        $update->execute($actor, $serviceType, new UpdateServiceTypeData(
            name: $validated['name'],
            description: $validated['description'],
            pricingMode: $mode,
            pricingStrategy: $strategy,
            requiresArt: $validated['requiresArt'],
            allowsMultiplePositions: $validated['allowsMultiplePositions'],
            sortOrder: $validated['sortOrder'],
        ));

        session()->flash('success', 'Serviço atualizado.');

        return redirect()->route('tenant.service-types.index');
    }

    public function render(): View
    {
        return view('livewire.service-type-form', [
            'pricingModes' => PricingMode::cases(),
            'pricingStrategies' => PricingStrategy::cases(),
            'editing' => $this->serviceTypeId !== null,
        ]);
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
