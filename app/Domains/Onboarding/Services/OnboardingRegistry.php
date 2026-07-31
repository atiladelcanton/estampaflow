<?php

declare(strict_types=1);

namespace App\Domains\Onboarding\Services;

use App\Domains\Tenancy\Enums\TenantRole;

final class OnboardingRegistry
{
    /** @return array<string, mixed> */
    public function wizard(): array
    {
        $wizard = config('onboarding.wizard', []);

        return is_array($wizard) ? $wizard : [];
    }

    /** @return array<string, mixed>|null */
    public function tutorialForRoute(?string $routeName, ?TenantRole $role): ?array
    {
        if ($routeName === null || $role === null) {
            return null;
        }

        foreach ($this->tutorials() as $tutorial) {
            $routes = $tutorial['routes'] ?? [];
            $roles = $tutorial['roles'] ?? [];

            if (
                is_array($routes)
                && is_array($roles)
                && in_array($routeName, $routes, true)
                && in_array($role->value, $roles, true)
            ) {
                return $tutorial;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function tutorial(string $key, TenantRole $role): ?array
    {
        foreach ($this->tutorials() as $tutorial) {
            $roles = $tutorial['roles'] ?? [];

            if (
                ($tutorial['key'] ?? null) === $key
                && is_array($roles)
                && in_array($role->value, $roles, true)
            ) {
                return $tutorial;
            }
        }

        return null;
    }

    /** @return array<string, array<string, mixed>> */
    public function articlesFor(TenantRole $role): array
    {
        return array_filter(
            $this->articles(),
            static function (array $article) use ($role): bool {
                $roles = $article['roles'] ?? [];

                return is_array($roles) && in_array($role->value, $roles, true);
            },
        );
    }

    /** @return array<string, mixed>|null */
    public function article(string $slug, TenantRole $role): ?array
    {
        $article = $this->articles()[$slug] ?? null;

        if (! is_array($article)) {
            return null;
        }

        $roles = $article['roles'] ?? [];

        return is_array($roles) && in_array($role->value, $roles, true)
            ? $article
            : null;
    }

    /** @return list<array<string, mixed>> */
    private function tutorials(): array
    {
        $configured = config('onboarding.tutorials', []);

        if (! is_array($configured)) {
            return [];
        }

        $tutorials = [];

        foreach ($configured as $tutorial) {
            if (is_array($tutorial)) {
                $tutorials[] = $tutorial;
            }
        }

        return $tutorials;
    }

    /** @return array<string, array<string, mixed>> */
    private function articles(): array
    {
        $configured = config('onboarding.articles', []);

        if (! is_array($configured)) {
            return [];
        }

        $articles = [];

        foreach ($configured as $slug => $article) {
            if (is_string($slug) && is_array($article)) {
                $articles[$slug] = $article;
            }
        }

        return $articles;
    }
}
