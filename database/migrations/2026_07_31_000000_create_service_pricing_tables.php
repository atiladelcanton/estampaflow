<?php

declare(strict_types=1);

use App\Domains\Pricing\Enums\PriceTableStatus;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_price_tables', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->ulid('service_type_id');
            $table->ulid('schema_version_id');
            $table->unsignedInteger('version');
            $table->string('status', 20)->default(PriceTableStatus::DRAFT->value);
            $table->string('strategy', 30)->default(PricingStrategy::UNIT->value);
            $table->char('currency', 3)->default('BRL');
            $table->json('settings')->nullable();
            $table->integer('priority')->default(100);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'service_type_id', 'version'], 'service_price_table_version_unique');
            $table->index(['tenant_id', 'service_type_id', 'status'], 'service_price_table_status_index');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
            $table->foreign('schema_version_id')->references('id')->on('service_type_schema_versions')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('service_price_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->char('tenant_id', 26);
            $table->ulid('price_table_id');
            $table->string('name', 140);
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->json('conditions')->nullable();
            $table->bigInteger('unit_amount_minor')->nullable();
            $table->decimal('rate_value', 18, 8)->nullable();
            $table->string('rate_unit', 50)->nullable();
            $table->bigInteger('setup_amount_minor')->default(0);
            $table->bigInteger('minimum_amount_minor')->default(0);
            $table->integer('priority')->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'price_table_id', 'active'], 'service_price_rule_active_index');
            $table->index(['tenant_id', 'min_quantity', 'max_quantity'], 'service_price_rule_quantity_index');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('price_table_id')->references('id')->on('service_price_tables')->cascadeOnDelete();
        });

        Schema::table('service_types', function (Blueprint $table): void {
            $table->ulid('active_price_table_id')->nullable()->after('active_schema_version_id');
            $table->foreign('active_price_table_id')
                ->references('id')
                ->on('service_price_tables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table): void {
            $table->dropForeign(['active_price_table_id']);
            $table->dropColumn('active_price_table_id');
        });

        Schema::dropIfExists('service_price_rules');
        Schema::dropIfExists('service_price_tables');
    }
};
