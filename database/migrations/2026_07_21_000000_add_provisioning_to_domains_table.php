<?php

declare(strict_types=1);

use App\Domains\Tenancy\Enums\DomainProvisioningStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table): void {
            $table->string('provisioning_status', 20)
                ->default(DomainProvisioningStatus::PENDING->value)
                ->index();
            $table->timestamp('provisioned_at')->nullable()->index();
            $table->timestamp('provisioning_failed_at')->nullable();
            $table->text('provisioning_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table): void {
            $table->dropIndex(['provisioning_status']);
            $table->dropIndex(['provisioned_at']);
            $table->dropColumn([
                'provisioning_status',
                'provisioned_at',
                'provisioning_failed_at',
                'provisioning_error',
            ]);
        });
    }
};
