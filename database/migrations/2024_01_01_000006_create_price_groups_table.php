<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Price Groups Table
 * 
 * FAZA B: Pricing & Inventory System - Price Groups Implementation
 * 
 * Business Logic:
 * - 8 grup cenowych PPM: Detaliczna, Dealer Standard/Premium, Warsztat/Premium, 
 *   Szkółka-Komis-Drop, Pracownik
 * - Tylko jedna grupa może być domyślna (is_default=true)
 * - Każda grupa ma domyślną marżę dla kalkulacji cen
 * - Integration ready dla PrestaShop specific_prices mapping
 * 
 * Performance Optimization:
 * - Unique constraint na code dla fast lookups
 * - Index na is_default dla default group queries
 * - Index na is_active dla active groups filtering
 * - Optimized dla Hostido shared hosting environment
 * 
 * @package Database\Migrations
 * @version FAZA B
 * @since 2024-09-09
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates price_groups table z complete business constraints
     * Performance optimized dla enterprise scale operations
     */
    public function up(): void
    {
        Schema::create('price_groups', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Core Business Fields
            $table->string('name', 100)->comment('Display name: Detaliczna, Dealer Standard, etc.');
            $table->string('code', 50)->unique()->comment('Unique code: retail, dealer_std, dealer_premium, etc.');
            
            // Business Logic Fields
            $table->boolean('is_default')->default(false)->comment('Only one group can be default');
            $table->decimal('margin_percentage', 5, 2)->nullable()->comment('Default margin % for this group (-100.00 to 999.99)');
            
            // Status and Ordering
            $table->boolean('is_active')->default(true)->comment('Active status for filtering');
            $table->integer('sort_order')->default(0)->comment('Display order in UI');
            
            // PrestaShop Integration Fields
            $table->json('prestashop_mapping')->nullable()->comment('PrestaShop specific_price groups mapping per shop');
            
            // ERP Integration Fields  
            $table->json('erp_mapping')->nullable()->comment('ERP systems price groups mapping (Baselinker, Subiekt, Dynamics)');
            
            // Audit Trail
            $table->text('description')->nullable()->comment('Group description and usage notes');
            $table->timestamps();
            
            // Performance Indexes
            $table->index(['is_active'], 'idx_price_groups_active');
            $table->index(['is_default'], 'idx_price_groups_default');
            $table->index(['sort_order', 'is_active'], 'idx_price_groups_sort_active');
            
            // Business Constraints Comments
            $table->comment('PPM Price Groups: 8 pricing tiers dla wielopoziomowego systemu cenowego');
        });
        
        // Add check constraints dla business rules
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE price_groups ADD CONSTRAINT chk_price_groups_margin CHECK (margin_percentage >= -100.00 AND margin_percentage <= 999.99)');
            DB::statement('ALTER TABLE price_groups ADD CONSTRAINT chk_price_groups_sort CHECK (sort_order >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops price_groups table z cascade handling
     * Warning: Will fail jeśli istnieją product_prices records
     */
    public function down(): void
    {
        // Drop constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE price_groups DROP CONSTRAINT IF EXISTS chk_price_groups_margin');
            DB::statement('ALTER TABLE price_groups DROP CONSTRAINT IF EXISTS chk_price_groups_sort');
        }
        
        Schema::dropIfExists('price_groups');
    }
};