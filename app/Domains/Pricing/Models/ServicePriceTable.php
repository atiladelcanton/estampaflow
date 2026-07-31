<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Models;

use App\Domains\Pricing\Enums\PriceTableStatus;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Models\User;
use App\Support\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $service_type_id
 * @property string $schema_version_id
 * @property int $version
 * @property PriceTableStatus $status
 * @property PricingStrategy $strategy
 * @property string $currency
 * @property array<string, mixed>|null $settings
 * @property int $priority
 * @property CarbonImmutable|null $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property string|null $created_by
 * @property CarbonImmutable|null $activated_at
 * @property-read Collection<int, ServicePriceRule> $rules
 */
final class ServicePriceTable extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'service_type_id',
        'schema_version_id',
        'version',
        'status',
        'strategy',
        'currency',
        'settings',
        'priority',
        'valid_from',
        'valid_until',
        'created_by',
        'activated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => PriceTableStatus::class,
            'strategy' => PricingStrategy::class,
            'settings' => 'array',
            'priority' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'activated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ServiceType, $this> */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /** @return BelongsTo<ServiceTypeSchemaVersion, $this> */
    public function schemaVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeSchemaVersion::class, 'schema_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ServicePriceRule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(ServicePriceRule::class, 'price_table_id')
            ->orderBy('sort_order')
            ->orderByDesc('priority');
    }

    public function isValidOn(CarbonImmutable $date): bool
    {
        if ($this->status !== PriceTableStatus::ACTIVE) {
            return false;
        }

        if ($this->valid_from !== null && $date->isBefore($this->valid_from)) {
            return false;
        }

        return $this->valid_until === null || ! $date->isAfter($this->valid_until);
    }
}
