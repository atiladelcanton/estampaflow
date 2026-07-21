<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Models;

use App\Domains\Tenancy\Enums\DomainProvisioningStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * @property int $id
 * @property string $domain
 * @property string $tenant_id
 * @property DomainProvisioningStatus $provisioning_status
 * @property CarbonImmutable|null $provisioned_at
 * @property CarbonImmutable|null $provisioning_failed_at
 * @property string|null $provisioning_error
 */
final class Domain extends BaseDomain
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'domain',
        'tenant_id',
        'provisioning_status',
        'provisioned_at',
        'provisioning_failed_at',
        'provisioning_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provisioning_status' => DomainProvisioningStatus::class,
            'provisioned_at' => 'immutable_datetime',
            'provisioning_failed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'provisioning_status' => DomainProvisioningStatus::PROCESSING,
            'provisioning_failed_at' => null,
            'provisioning_error' => null,
        ])->save();
    }

    public function markProvisioned(): void
    {
        $this->forceFill([
            'provisioning_status' => DomainProvisioningStatus::PROVISIONED,
            'provisioned_at' => now(),
            'provisioning_failed_at' => null,
            'provisioning_error' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'provisioning_status' => DomainProvisioningStatus::FAILED,
            'provisioning_failed_at' => now(),
            'provisioning_error' => mb_substr($message, 0, 2000),
        ])->save();
    }

    public function isProvisioned(): bool
    {
        return $this->provisioning_status === DomainProvisioningStatus::PROVISIONED;
    }
}
