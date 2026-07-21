<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\ServiceCatalog\Actions\DuplicateServiceTypeAction;
use App\Application\ServiceCatalog\Actions\ToggleServiceTypeAction;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class ServiceTypesTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function toggle(string $serviceTypeId, ToggleServiceTypeAction $action): void
    {
        $serviceType = ServiceType::query()->findOrFail($serviceTypeId);
        $action->execute($this->authenticatedUser(), $serviceType);
        session()->flash('success', $serviceType->active ? 'Serviço ativado.' : 'Serviço desativado.');
    }

    public function duplicate(string $serviceTypeId, DuplicateServiceTypeAction $action): mixed
    {
        $serviceType = ServiceType::query()->findOrFail($serviceTypeId);
        $copy = $action->execute($this->authenticatedUser(), $serviceType);
        session()->flash('success', 'Serviço duplicado. Revise os campos antes de ativá-lo.');

        return redirect()->route('tenant.service-types.fields', ['serviceType' => $copy->getKey()]);
    }

    public function render(): View
    {
        $query = ServiceType::query()
            ->with(['activeSchemaVersion.parameters'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderBy('sort_order')
            ->orderBy('name');

        return view('livewire.service-types-table', [
            'serviceTypes' => $query->paginate(12),
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
