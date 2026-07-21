<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Models;

use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $service_type_id
 * @property int $version
 * @property ServiceSchemaStatus $status
 * @property string|null $created_by
 * @property CarbonImmutable|null $activated_at
 */
final class ServiceTypeSchemaVersion extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'service_type_id',
        'version',
        'status',
        'created_by',
        'activated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => ServiceSchemaStatus::class,
            'activated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ServiceType, $this> */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ServiceParameterDefinition, $this> */
    public function parameters(): HasMany
    {
        return $this->hasMany(ServiceParameterDefinition::class, 'schema_version_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function isDraft(): bool
    {
        return $this->status === ServiceSchemaStatus::DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === ServiceSchemaStatus::ACTIVE;
    }
}
