<?php

namespace App\Support\Tenancy;

use App\Domains\Tenancy\Models\Tenant;
use RuntimeException;

final class TenantUrlGenerator
{
    public function for(Tenant $tenant, string $path = '/dashboard'): string
    {
        $domain = $tenant->primaryDomain();

        if ($domain === null) {
            throw new RuntimeException('Tenant não possui domínio configurado.');
        }

        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $normalizedPath = '/'.ltrim($path, '/');

        return sprintf(
            '%s://%s%s%s',
            $scheme,
            $domain,
            $port !== null ? ':'.$port : '',
            $normalizedPath,
        );
    }

    public function central(string $path = '/dashboard'): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
