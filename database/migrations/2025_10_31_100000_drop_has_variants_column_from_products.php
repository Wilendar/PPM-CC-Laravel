<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CONTEXT: Removing redundant has_variants column
     * The has_variants column was redundant with is_variant_master.
     * Both tracked the same state, causing synchronization issues.
     *
     * DECISION: Keep only is_variant_master as single source of truth
     *
     * SAFETY: All code references to has_variants have been replaced
     * with is_variant_master in:
     * - Product.php (model scopes)
     * - ProductForm.php (UI logic)
     * - ProductFormVariants.php (CRUD operations)
     * - ProductFormSaver.php (save logic)
     * - VariantConversionService.php (conversion logic)
     * - HasVariants.php trait (helper methods)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_variants');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_variants')
                  ->default(false)
                  ->after('is_variant_master')
                  ->comment('Indicates if product has variants (DEPRECATED - use is_variant_master)');
        });
    }
};
