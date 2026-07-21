<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $builder->where(
                $builder->qualifyColumn('tenant_id'),
                (string) app(TenantContext::class)->currentId(),
            );
        });

        static::creating(function (Model $model): void {
            $tenantId = (string) app(TenantContext::class)->currentId();
            $current = $model->getAttribute('tenant_id');

            if ($current !== null && $current !== $tenantId) {
                throw new LogicException('Não é permitido criar um registro para outro tenant.');
            }

            $model->setAttribute('tenant_id', $tenantId);
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new LogicException('O tenant de um registro não pode ser alterado.');
            }
        });
    }
}
