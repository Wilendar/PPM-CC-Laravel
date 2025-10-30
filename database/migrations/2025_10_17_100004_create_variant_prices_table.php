<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 4/15
     *
     * Creates variant_prices table for storing variant prices per price group.
     *
     * PURPOSE:
     * - Store different prices for each variant per price group
     * - Support special/promotional pricing with date ranges
     * - Enable price-based variant differentiation (e.g., premium materials = higher price)
     *
     * BUSINESS RULES:
     * - Each variant can have ONLY ONE price per price group (unique constraint)
     * - Cascade delete: if variant deleted → prices deleted
     * - Cascade delete: if price group deleted → variant prices deleted
     *
     * PRICE GROUPS (from existing system):
     * - Detaliczna, Dealer Standard, Dealer Premium, Warsztat, Warsztat Premium, Szkółka-Komis-Drop, Pracownik
     *
     * EXAMPLES:
     * - variant_id=1, price_group_id=1(Detaliczna), price=149.99, price_special=129.99
     * - variant_id=2, price_group_id=1(Detaliczna), price=199.99 (no special price)
     *
     * RELATIONSHIPS:
     * - belongs to ProductVariant (cascade delete)
     * - belongs to PriceGroup (cascade delete)
     */
    public function up(): void
    {
        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();

            // Relations (both cascade delete)
            $table->foreignId('variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            $table->foreignId('price_group_id')
                  ->constrained('price_groups')
                  ->cascadeOnDelete();

            // Pricing data
            $table->decimal('price', 10, 2);
            $table->decimal('price_special', 10, 2)->nullable();

            // Special price date range
            $table->date('special_from')->nullable();
            $table->date('special_to')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['variant_id', 'price_group_id'], 'idx_variant_price_group');
            $table->index(['special_from', 'special_to'], 'idx_variant_price_special');

            // Unique constraint: ONE price per group per variant
            $table->unique(['variant_id', 'price_group_id'], 'uniq_variant_price_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_prices');
    }
};
