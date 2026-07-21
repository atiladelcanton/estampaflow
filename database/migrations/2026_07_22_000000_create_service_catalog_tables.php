<?php

declare(strict_types=1);

use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->string('code', 80);
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->text('description')->nullable();
            $table->string('pricing_mode', 20)->default(PricingMode::AUTOMATIC->value);
            $table->string('pricing_strategy', 30)->nullable();
            $table->boolean('requires_art')->default(true);
            $table->boolean('allows_multiple_positions')->default(true);
            $table->boolean('active')->default(false)->index();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->ulid('active_schema_version_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'active', 'sort_order']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('service_type_schema_versions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->ulid('service_type_id');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default(ServiceSchemaStatus::DRAFT->value);
            $table->ulid('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'service_type_id', 'version'], 'service_schema_version_unique');
            $table->index(['tenant_id', 'service_type_id', 'status'], 'service_schema_status_index');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('service_parameter_definitions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->ulid('schema_version_id');
            $table->string('key', 100);
            $table->string('label', 140);
            $table->string('field_type', 30);
            $table->string('unit', 30)->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('affects_pricing')->default(false);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('default_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'schema_version_id', 'key'], 'service_parameter_key_unique');
            $table->index(['tenant_id', 'schema_version_id', 'active'], 'service_parameter_active_index');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('schema_version_id')->references('id')->on('service_type_schema_versions')->cascadeOnDelete();
        });

        Schema::table('service_types', function (Blueprint $table): void {
            $table->foreign('active_schema_version_id')
                ->references('id')
                ->on('service_type_schema_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table): void {
            $table->dropForeign(['active_schema_version_id']);
        });

        Schema::dropIfExists('service_parameter_definitions');
        Schema::dropIfExists('service_type_schema_versions');
        Schema::dropIfExists('service_types');
    }
};
