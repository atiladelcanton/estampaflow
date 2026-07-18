<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->string('email');
            $table->string('email_normalized');
            $table->string('pending_email_key')->nullable();
            $table->string('role', 20);
            $table->string('status', 20)->default('PENDING');
            $table->char('token_hash', 64)->unique();
            $table->ulid('invited_by');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->ulid('accepted_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'pending_email_key']);
            $table->index(['tenant_id', 'status', 'expires_at']);
            $table->index('email_normalized');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
