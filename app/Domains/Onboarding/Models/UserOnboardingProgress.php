<?php

declare(strict_types=1);

namespace App\Domains\Onboarding\Models;

use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property string $tutorial_key
 * @property int $version
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $dismissed_at
 */
final class UserOnboardingProgress extends Model
{
    use HasUlids;

    protected $table = 'user_onboarding_progress';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'tutorial_key',
        'version',
        'completed_at',
        'dismissed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'completed_at' => 'immutable_datetime',
            'dismissed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAcknowledged(): bool
    {
        return $this->completed_at !== null || $this->dismissed_at !== null;
    }
}
