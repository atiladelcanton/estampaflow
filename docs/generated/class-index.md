# Índice de Classes

> Atualizado para a correção de fluxo da Sprint 1.

| Classe | Arquivo | Métodos públicos declarados |
|---|---|---|
| `App\Actions\Fortify\CreateNewUser` | `app/Actions/Fortify/CreateNewUser.php` | `__construct()`, `create()` |
| `App\Actions\Fortify\PasswordValidationRules` | `app/Actions/Fortify/PasswordValidationRules.php` | — |
| `App\Actions\Fortify\ResetUserPassword` | `app/Actions/Fortify/ResetUserPassword.php` | `reset()` |
| `App\Application\Tenancy\Actions\AcceptTenantInvitationAction` | `app/Application/Tenancy/Actions/AcceptTenantInvitationAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\ChangeTenantMembershipAction` | `app/Application/Tenancy/Actions/ChangeTenantMembershipAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\CreateTenantAction` | `app/Application/Tenancy/Actions/CreateTenantAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\InviteTenantUserAction` | `app/Application/Tenancy/Actions/InviteTenantUserAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\RegisterInvitedUserAction` | `app/Application/Tenancy/Actions/RegisterInvitedUserAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\RegisterTenantOwnerAction` | `app/Application/Tenancy/Actions/RegisterTenantOwnerAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\RevokeTenantInvitationAction` | `app/Application/Tenancy/Actions/RevokeTenantInvitationAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Actions\TransferTenantOwnershipAction` | `app/Application/Tenancy/Actions/TransferTenantOwnershipAction.php` | `__construct()`, `execute()` |
| `App\Application\Tenancy\Data\CreateTenantData` | `app/Application/Tenancy/Data/CreateTenantData.php` | `__construct()` |
| `App\Application\Tenancy\Data\CreatedInvitationData` | `app/Application/Tenancy/Data/CreatedInvitationData.php` | `__construct()` |
| `App\Application\Tenancy\Data\RegisterTenantOwnerData` | `app/Application/Tenancy/Data/RegisterTenantOwnerData.php` | `__construct()` |
| `App\Application\Tenancy\Data\RegisteredInvitedUserData` | `app/Application/Tenancy/Data/RegisteredInvitedUserData.php` | `__construct()` |
| `App\Application\Tenancy\Data\RegisteredTenantOwnerData` | `app/Application/Tenancy/Data/RegisteredTenantOwnerData.php` | `__construct()` |
| `App\Console\Commands\DocsCheckCommand` | `app/Console/Commands/DocsCheckCommand.php` | `handle()` |
| `App\Console\Commands\DocsGenerateCommand` | `app/Console/Commands/DocsGenerateCommand.php` | `handle()` |
| `App\Console\Commands\ProjectAuditCommand` | `app/Console/Commands/ProjectAuditCommand.php` | `handle()` |
| `App\Domains\Tenancy\Exceptions\TenantAuthorizationException` | `app/Domains/Tenancy/Exceptions/TenantAuthorizationException.php` | — |
| `App\Domains\Tenancy\Models\Tenant` | `app/Domains/Tenancy/Models/Tenant.php` | `getCustomColumns()`, `memberships()`, `users()`, `invitations()`, `isActive()`, `isTrialActive()`, `primaryDomain()` |
| `App\Domains\Tenancy\Models\TenantInvitation` | `app/Domains/Tenancy/Models/TenantInvitation.php` | `tenant()`, `inviter()`, `acceptedBy()`, `isPending()`, `markExpired()` |
| `App\Domains\Tenancy\Models\TenantMembership` | `app/Domains/Tenancy/Models/TenantMembership.php` | `tenant()`, `user()`, `inviter()`, `isActive()`, `isOwner()` |
| `App\Domains\Tenancy\Services\TenantMembershipService` | `app/Domains/Tenancy/Services/TenantMembershipService.php` | `assertActiveMember()`, `assertOwner()`, `activeOwnerCount()`, `assertCanRemoveOwner()` |
| `App\Http\Middleware\AttachCorrelationId` | `app/Http/Middleware/AttachCorrelationId.php` | `__construct()`, `handle()` |
| `App\Http\Middleware\EnsureActiveTenantMembership` | `app/Http/Middleware/EnsureActiveTenantMembership.php` | `__construct()`, `handle()` |
| `App\Http\Middleware\EnsurePlatformAdmin` | `app/Http/Middleware/EnsurePlatformAdmin.php` | `handle()` |
| `App\Http\Middleware\EnsureTenantOwner` | `app/Http/Middleware/EnsureTenantOwner.php` | `__construct()`, `handle()` |
| `App\Http\Middleware\InitializeTenancyForRequest` | `app/Http/Middleware/InitializeTenancyForRequest.php` | `__construct()`, `handle()` |
| `App\Http\Responses\LoginResponse` | `app/Http/Responses/LoginResponse.php` | `__construct()`, `toResponse()` |
| `App\Http\Responses\RegisterResponse` | `app/Http/Responses/RegisterResponse.php` | `__construct()`, `toResponse()` |
| `App\Livewire\AcceptInvitation` | `app/Livewire/AcceptInvitation.php` | `mount()`, `accept()`, `registerAndAccept()`, `render()` |
| `App\Livewire\PlatformDashboard` | `app/Livewire/PlatformDashboard.php` | `render()` |
| `App\Livewire\ProductPreview` | `app/Livewire/ProductPreview.php` | `visibleProducts()`, `clearFilters()`, `render()` |
| `App\Livewire\TenantDashboard` | `app/Livewire/TenantDashboard.php` | `render()` |
| `App\Livewire\TenantUsers` | `app/Livewire/TenantUsers.php` | `invite()`, `toggleStatus()`, `changeRole()`, `transferOwnership()`, `revokeInvitation()`, `render()` |
| `App\Models\AuditLog` | `app/Models/AuditLog.php` | — |
| `App\Models\User` | `app/Models/User.php` | `memberships()`, `tenants()`, `activeMembershipFor()` |
| `App\Notifications\TenantInvitationNotification` | `app/Notifications/TenantInvitationNotification.php` | `__construct()`, `via()`, `toMail()` |
| `App\Providers\AppServiceProvider` | `app/Providers/AppServiceProvider.php` | `register()`, `boot()` |
| `App\Providers\FortifyServiceProvider` | `app/Providers/FortifyServiceProvider.php` | `register()`, `boot()` |
| `App\Support\Audit\AuditEntryData` | `app/Support/Audit/AuditEntryData.php` | `__construct()` |
| `App\Support\Audit\AuditLogger` | `app/Support/Audit/AuditLogger.php` | `__construct()`, `record()` |
| `App\Support\Auth\AuthenticatedDestinationResolver` | `app/Support/Auth/AuthenticatedDestinationResolver.php` | `__construct()`, `resolve()` |
| `App\Support\Correlation\CorrelationContext` | `app/Support/Correlation/CorrelationContext.php` | `set()`, `current()`, `hasCurrent()` |
| `App\Support\Correlation\CorrelationId` | `app/Support/Correlation/CorrelationId.php` | `__construct()`, `generate()`, `__toString()` |
| `App\Support\Tenancy\MissingTenantContextException` | `app/Support/Tenancy/MissingTenantContextException.php` | `create()` |
| `App\Support\Tenancy\StanclTenantContext` | `app/Support/Tenancy/StanclTenantContext.php` | `currentId()`, `hasTenant()`, `run()` |
| `App\Support\Tenancy\TenantContext` | `app/Support/Tenancy/TenantContext.php` | `currentId()`, `hasTenant()`, `run()` |
| `App\Support\Tenancy\TenantId` | `app/Support/Tenancy/TenantId.php` | `__construct()`, `__toString()` |
| `App\Support\Tenancy\TenantUrlGenerator` | `app/Support/Tenancy/TenantUrlGenerator.php` | `for()`, `central()` |
| `App\Support\Tenancy\UlidTenantIdGenerator` | `app/Support/Tenancy/UlidTenantIdGenerator.php` | `generate()` |
| `App\Support\Tenancy\UniqueTenantSlugGenerator` | `app/Support/Tenancy/UniqueTenantSlugGenerator.php` | `generate()` |
