<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Product Prices Table
 * 
 * FAZA B: Pricing & Inventory System - Advanced Pricing Management
 * 
 * Business Logic:
 * - Wielopoziomowe ceny dla każdej grupy cenowej (8 grup PPM)
 * - Support dla product variants (product OR product_variant pricing)
 * - Auto-calculation margins based on cost_price vs selling price
 * - Time-based pricing z valid_from/valid_to periods
 * - Currency support z exchange rates dla international pricing
 * 
 * Performance Optimization:
 * - Composite unique constraint (product_id, product_variant_id, price_group_id)
 * - Strategic indexes dla frequent price lookup queries
 * - Decimal precision optimized dla financial calculations
 * - Partial indexes dla active prices only
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
     * Creates product_prices table z advanced business logic support
     * Enterprise-grade pricing system dla PPM operations
     */
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            // Primary Key
            $table->id();
            
            // Foreign Keys - Product Relations
            $table->unsignedBigInteger('product_id')->comment('Products.id - REQUIRED');
            $table->unsignedBigInteger('product_variant_id')->nullable()->comment('Product_variants.id - OPTIONAL for variant-specific pricing');
            $table->unsignedBigInteger('price_group_id')->comment('Price_groups.id - REQUIRED');
            
            // Core Pricing Fields
            $table->decimal('price_net', 10, 2)->comment('Net price (before tax) in base currency');
            $table->decimal('price_gross', 10, 2)->comment('Gross price (with tax) in base currency');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Purchase/cost price - SENSITIVE DATA (Admin/Manager only)');
            
            // Currency & Exchange Rate Support
            $table->string('currency', 3)->default('PLN')->comment('Price currency (ISO 4217)');
            $table->decimal('exchange_rate', 8, 4)->default(1.0000)->comment('Exchange rate to base currency at time of pricing');
            
            // Time-Based Pricing
            $table->timestamp('valid_from')->nullable()->comment('Price validity start date');
            $table->timestamp('valid_to')->nullable()->comment('Price validity end date');
            
            // Business Calculations (Auto-computed)
            $table->decimal('margin_percentage', 5, 2)->nullable()->comment('Profit margin % ((price_net - cost_price) / cost_price * 100)');
            $table->decimal('markup_percentage', 5, 2)->nullable()->comment('Markup % ((price_net - cost_price) / price_net * 100)');
            
            // PrestaShop Integration Fields
            $table->json('prestashop_mapping')->nullable()->comment('
                PrestaShop specific_price mapping per shop:
                {
                    "shop_1": {"specific_price_id": 123, "reduction": 0.15, "reduction_type": "percentage"},
                    "shop_2": {"specific_price_id": 124, "reduction": 5.00, "reduction_type": "amount"}
                }
            ');
            
            // Price Calculation Rules
            $table->boolean('auto_calculate_gross')->default(true)->comment('Auto-calculate gross price from net + tax_rate');
            $table->boolean('auto_calculate_margin')->default(true)->comment('Auto-calculate margin from cost_price');
            $table->boolean('price_includes_tax')->default(false)->comment('Whether price_net already includes tax');
            
            // Business Status
            $table->boolean('is_active')->default(true)->comment('Price is active and should be used');
            $table->boolean('is_promotion')->default(false)->comment('Promotional/special price indicator');
            
            // Audit Trail
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this price');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('User who last updated this price');
            $table->timestamps();
            
            // Foreign Key Constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('price_group_id')->references('id')->on('price_groups')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Business Constraints
            $table->unique(['product_id', 'product_variant_id', 'price_group_id'], 'uk_product_variant_price_group');
            
            // Performance Indexes
            $table->index(['product_id', 'price_group_id'], 'idx_product_price_group');
            $table->index(['product_variant_id', 'price_group_id'], 'idx_variant_price_group');
            $table->index(['price_group_id', 'is_active'], 'idx_price_group_active');
            $table->index(['currency', 'is_active'], 'idx_currency_active');
            $table->index(['valid_from', 'valid_to', 'is_active'], 'idx_price_validity');
            $table->index(['is_active', 'is_promotion'], 'idx_active_promotion');
            
            // Partial indexes dla active prices only (better performance)
            $table->index(['product_id'], 'idx_product_active_prices')->where('is_active', true);
            
            $table->comment('PPM Product Prices: Multi-tier pricing system z variant support');
        });
        
        // Add check constraints dla business rules
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_positive CHECK (price_net >= 0 AND price_gross >= 0 AND (cost_price IS NULL OR cost_price >= 0))');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_gross_net CHECK (price_gross >= price_net)');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_exchange_rate CHECK (exchange_rate > 0)');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_margin_range CHECK (margin_percentage >= -100.00 AND margin_percentage <= 1000.00)');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_markup_range CHECK (markup_percentage >= 0.00 AND markup_percentage <= 100.00)');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT chk_prices_validity_dates CHECK (valid_to IS NULL OR valid_to > valid_from)');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops product_prices table z constraint cleanup
     * Safe rollback z proper foreign key handling
     */
    public function down(): void
    {
        // Drop check constraints first (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_positive');
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_gross_net');
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_exchange_rate');
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_margin_range');
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_markup_range');
            DB::statement('ALTER TABLE product_prices DROP CONSTRAINT IF EXISTS chk_prices_validity_dates');
        }
        
        Schema::dropIfExists('product_prices');
    }
};