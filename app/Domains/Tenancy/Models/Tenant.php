<?php

namespace App\Domains\Tenancy\Models;

use App\Domains\Tenancy\Enums\TenantStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

final class Tenant extends BaseTenant
{
    use HasDomains;

    /**
     * Colunas reais da tabela. Qualquer atributo fora desta lista seria
     * armazenado no JSON `data` pelo model base do pacote.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'status',
            'timezone',
            'trial_ends_at',
            'data',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'timezone',
        'trial_ends_at',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'immutable_datetime',
            'data' => 'array',
        ];
    }

    /**
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<TenantInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    public function isTrialActive(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function primaryDomain(): ?string
    {
        return $this->domains()->orderBy('id')->value('domain');
    }
}
