<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_onboarding_progress', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->ulid('user_id');
            $table->string('tutorial_key', 100);
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'user_id', 'tutorial_key', 'version'],
                'onboarding_progress_unique',
            );
            $table->index(['tenant_id', 'tutorial_key']);
            $table->index(['user_id', 'completed_at']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
};
