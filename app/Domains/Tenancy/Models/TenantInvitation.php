<?php

namespace App\Domains\Tenancy\Models;

use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantInvitation extends Model
{
    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'email_normalized',
        'pending_email_key',
        'role',
        'status',
        'token_hash',
        'invited_by',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'revoked_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING && $this->expires_at->isFuture();
    }

    public function markExpired(): void
    {
        $this->forceFill([
            'status' => InvitationStatus::EXPIRED,
            'pending_email_key' => null,
        ])->save();
    }
}
