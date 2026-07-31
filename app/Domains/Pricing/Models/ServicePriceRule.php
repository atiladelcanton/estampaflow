<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $price_table_id
 * @property string $name
 * @property int|null $min_quantity
 * @property int|null $max_quantity
 * @property list<array{parameter: string, operator: string, value: mixed}>|null $conditions
 * @property int|null $unit_amount_minor
 * @property string|null $rate_value
 * @property string|null $rate_unit
 * @property int $setup_amount_minor
 * @property int $minimum_amount_minor
 * @property int $priority
 * @property int $sort_order
 * @property bool $active
 */
final class ServicePriceRule extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'price_table_id',
        'name',
        'min_quantity',
        'max_quantity',
        'conditions',
        'unit_amount_minor',
        'rate_value',
        'rate_unit',
        'setup_amount_minor',
        'minimum_amount_minor',
        'priority',
        'sort_order',
        'active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'conditions' => 'array',
            'unit_amount_minor' => 'integer',
            'rate_value' => 'decimal:8',
            'setup_amount_minor' => 'integer',
            'minimum_amount_minor' => 'integer',
            'priority' => 'integer',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<ServicePriceTable, $this> */
    public function priceTable(): BelongsTo
    {
        return $this->belongsTo(ServicePriceTable::class, 'price_table_id');
    }
}
