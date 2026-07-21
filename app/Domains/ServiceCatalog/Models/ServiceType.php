<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Models;

use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property PricingMode $pricing_mode
 * @property PricingStrategy|null $pricing_strategy
 * @property bool $requires_art
 * @property bool $allows_multiple_positions
 * @property bool $active
 * @property bool $is_default
 * @property int $sort_order
 * @property string|null $active_schema_version_id
 */
final class ServiceType extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'pricing_mode',
        'pricing_strategy',
        'requires_art',
        'allows_multiple_positions',
        'active',
        'is_default',
        'sort_order',
        'active_schema_version_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pricing_mode' => PricingMode::class,
            'pricing_strategy' => PricingStrategy::class,
            'requires_art' => 'boolean',
            'allows_multiple_positions' => 'boolean',
            'active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<ServiceTypeSchemaVersion, $this> */
    public function schemaVersions(): HasMany
    {
        return $this->hasMany(ServiceTypeSchemaVersion::class)->orderByDesc('version');
    }

    /** @return BelongsTo<ServiceTypeSchemaVersion, $this> */
    public function activeSchemaVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeSchemaVersion::class, 'active_schema_version_id');
    }

    /** @param Builder<ServiceType> $query */
    public function scopeAvailableForNewQuotes(Builder $query): void
    {
        $query->where('active', true)->whereNotNull('active_schema_version_id');
    }

    public function activate(): void
    {
        $this->forceFill(['active' => true])->save();
    }

    public function deactivate(): void
    {
        $this->forceFill(['active' => false])->save();
    }

    public function isAvailableForNewQuotes(): bool
    {
        return $this->active && $this->active_schema_version_id !== null;
    }
}
