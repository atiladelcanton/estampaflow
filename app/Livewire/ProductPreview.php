<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProductPreview extends Component
{
    public string $search = '';

    public string $status = 'all';

    /** @var list<array{name:string, category:string, sku:string, stock:int, price:string, status:string, initials:string}> */
    public array $products = [
        ['name' => 'Camiseta Algodão Premium', 'category' => 'Camisetas', 'sku' => 'CAM-ALG-001', 'stock' => 148, 'price' => 'R$ 32,90', 'status' => 'active', 'initials' => 'CA'],
        ['name' => 'Moletom Canguru', 'category' => 'Moletons', 'sku' => 'MOL-CAN-004', 'stock' => 32, 'price' => 'R$ 89,00', 'status' => 'active', 'initials' => 'MC'],
        ['name' => 'Ecobag Crua 35x40', 'category' => 'Bolsas', 'sku' => 'ECO-CRU-010', 'stock' => 8, 'price' => 'R$ 18,50', 'status' => 'low', 'initials' => 'EC'],
        ['name' => 'Boné Trucker', 'category' => 'Acessórios', 'sku' => 'BON-TRU-003', 'stock' => 74, 'price' => 'R$ 27,90', 'status' => 'active', 'initials' => 'BT'],
        ['name' => 'Caneca Cerâmica Branca', 'category' => 'Canecas', 'sku' => 'CAN-BRA-002', 'stock' => 0, 'price' => 'R$ 16,00', 'status' => 'inactive', 'initials' => 'CB'],
        ['name' => 'Camiseta Dry Fit', 'category' => 'Esportiva', 'sku' => 'CAM-DRY-008', 'stock' => 56, 'price' => 'R$ 41,90', 'status' => 'active', 'initials' => 'CD'],
    ];

    /** @return list<array{name:string, category:string, sku:string, stock:int, price:string, status:string, initials:string}> */
    public function visibleProducts(): array
    {
        return array_values(array_filter($this->products, function (array $product): bool {
            $matchesSearch = $this->search === '' || str_contains(mb_strtolower($product['name'].' '.$product['sku'].' '.$product['category']), mb_strtolower($this->search));
            $matchesStatus = $this->status === 'all' || $product['status'] === $this->status;

            return $matchesSearch && $matchesStatus;
        }));
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
    }

    public function render(): View
    {
        return view('livewire.product-preview', ['visibleProducts' => $this->visibleProducts()]);
    }
}
