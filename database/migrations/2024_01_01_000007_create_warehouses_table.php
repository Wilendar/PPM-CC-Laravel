<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            // Core identification (Strategy B + FAZA B merged)
            $table->string('name', 100); // e.g., "MPPTRADE", "Shop 1 Warehouse"
            $table->string('code', 50)->unique(); // e.g., "mpptrade", "shop1_wh"
            $table->enum('type', ['master', 'shop_linked', 'custom'])->default('custom');

            // Location Information (from FAZA B)
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 50)->default('PL');

            // Strategy B: Shop linkage (nullable for master/custom warehouses)
            // FK constraint added later in 2024_01_01_000026_create_prestashop_shops_table migration
            $table->unsignedBigInteger('shop_id')->nullable();

            // Business Logic Fields (from FAZA B)
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Operational Settings (from FAZA B)
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('auto_reserve_stock')->default(true);
            $table->integer('default_minimum_stock')->default(0);

            // Strategy B: Inheritance settings
            $table->boolean('inherit_from_shop')->default(false); // Pull stock from PrestaShop

            // Integration Mappings (from FAZA B)
            $table->json('prestashop_mapping')->nullable();
            $table->json('erp_mapping')->nullable();

            // Contact Information (from FAZA B)
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();

            // Operational Notes (from FAZA B)
            $table->text('operating_hours')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes (Strategy B + FAZA B merged)
            $table->index('type');
            $table->index('shop_id');
            $table->index(['type', 'is_active']);
            $table->index(['is_active'], 'idx_warehouses_active');
            $table->index(['is_default'], 'idx_warehouses_default');
            $table->index(['sort_order', 'is_active'], 'idx_warehouses_sort_active');
            $table->index(['city', 'is_active'], 'idx_warehouses_city_active');

            // Prevent duplicate warehouse codes per shop (Strategy B)
            $table->unique(['shop_id', 'code'], 'warehouses_shop_code_unique');
        });

        // Add check constraints dla business rules (from FAZA B)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_sort CHECK (sort_order >= 0)');
            DB::statement('ALTER TABLE warehouses ADD CONSTRAINT chk_warehouses_min_stock CHECK (default_minimum_stock >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop check constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_sort');
            DB::statement('ALTER TABLE warehouses DROP CONSTRAINT IF EXISTS chk_warehouses_min_stock');
        }

        Schema::dropIfExists('warehouses');
    }
};
