<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ProductType;

/**
 * Migration: Convert product_type ENUM to foreign key
 *
 * Migruje istniejące produkty z ENUM na relację z product_types
 *
 * @package Database\Migrations
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add new product_type_id column (skip if already exists)
        if (!Schema::hasColumn('products', 'product_type_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('product_type_id')->nullable()->after('slug');
                $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('set null');
                $table->index('product_type_id', 'idx_products_product_type_id');
            });
        }

        // Step 2: Migrate existing data
        $this->migrateExistingData();

        // Step 3: Remove old ENUM column and rename new column (skip if already done)
        if (Schema::hasColumn('products', 'product_type') && !Schema::hasColumn('products', 'product_type_old')) {
            Schema::table('products', function (Blueprint $table) {
                // Rename old column to backup
                $table->renameColumn('product_type', 'product_type_old');
            });
        }

        // Step 4: Make product_type_id non-nullable and add index (skip if already done)
        if (Schema::hasColumn('products', 'product_type_id')) {
            // Set default product type for any remaining null values
            DB::table('products')->whereNull('product_type_id')->update([
                'product_type_id' => ProductType::where('slug', 'inne')->first()?->id ?? 1
            ]);

            // Try to make non-nullable if not already
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedBigInteger('product_type_id')->nullable(false)->change();
                });
            } catch (\Exception $e) {
                // Column might already be non-nullable
            }
        }
    }

    /**
     * Migrate existing ENUM data to foreign keys
     */
    private function migrateExistingData(): void
    {
        // Mapping from old ENUM values to new slugs
        $enumToSlugMapping = [
            'vehicle' => 'pojazd',
            'spare_part' => 'czesc-zamiennicza',
            'clothing' => 'odziez',
            'other' => 'inne',
        ];

        foreach ($enumToSlugMapping as $enumValue => $slug) {
            $productType = ProductType::where('slug', $slug)->first();

            if ($productType) {
                DB::table('products')
                    ->where('product_type', $enumValue)
                    ->update(['product_type_id' => $productType->id]);
            }
        }

        // Handle any unmapped values - set to 'inne'
        $defaultType = ProductType::where('slug', 'inne')->first();
        if ($defaultType) {
            DB::table('products')
                ->whereNull('product_type_id')
                ->update(['product_type_id' => $defaultType->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Restore old ENUM values
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_type_old', 'product_type');
        });

        // Step 2: Remove foreign key and new column
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropIndex('idx_products_product_type_id');
            $table->dropColumn('product_type_id');
        });
    }
};