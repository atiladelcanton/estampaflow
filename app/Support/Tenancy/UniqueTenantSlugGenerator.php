<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

final class UniqueTenantSlugGenerator
{
    public function generate(string $businessName): string
    {
        $base = Str::slug($businessName);
        $base = $base !== '' ? Str::limit($base, 50, '') : 'estamparia';
        $candidate = $base;
        $suffix = 2;

        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = Str::limit($base, 50 - strlen((string) $suffix) - 1, '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
