<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Tax Rules Dynamic Mapping
     *
     * Problem: Hardcoded tax_rules_group IDs in ProductTransformer caused incorrect tax rates
     * - Mapped 23% VAT → group ID 1, but shop uses group ID 6
     * - Different PrestaShop installations have different group IDs
     *
     * Solution: Per-shop configurable tax rules mapping with smart defaults
     * - Admin can configure which tax_rules_group ID to use for each rate
     * - System auto-detects from PrestaShop API if not configured
     * - Cache-friendly (no API calls during sync once configured)
     *
     * Reference: _ISSUES_FIXES/PRESTASHOP_TAX_RULES_OVERWRITE_ISSUE.md
     */
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Tax Rules Group ID mapping (configurable per shop)
            $table->integer('tax_rules_group_id_23')->nullable()
                ->comment('PrestaShop tax_rules_group ID for 23% VAT (PL Standard Rate)');

            $table->integer('tax_rules_group_id_8')->nullable()
                ->comment('PrestaShop tax_rules_group ID for 8% VAT (PL Reduced Rate)');

            $table->integer('tax_rules_group_id_5')->nullable()
                ->comment('PrestaShop tax_rules_group ID for 5% VAT (PL Super Reduced Rate)');

            $table->integer('tax_rules_group_id_0')->nullable()
                ->comment('PrestaShop tax_rules_group ID for 0% VAT (Exempt)');

            // Auto-detection timestamp
            $table->timestamp('tax_rules_last_fetched_at')->nullable()
                ->comment('Last time tax rules were auto-detected from PrestaShop API');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn([
                'tax_rules_group_id_23',
                'tax_rules_group_id_8',
                'tax_rules_group_id_5',
                'tax_rules_group_id_0',
                'tax_rules_last_fetched_at',
            ]);
        });
    }
};
