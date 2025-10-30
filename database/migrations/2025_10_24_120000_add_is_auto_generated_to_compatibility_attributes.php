<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05d CONDITION 1 - Migration
     *
     * Adds is_auto_generated column to compatibility_attributes table.
     *
     * PURPOSE:
     * - Flag "Model" attribute as auto-generated (computed from Oryginał + Zamiennik)
     * - Enable UI to display Model as read-only (cannot be manually assigned)
     * - Support auto-generation logic in CompatibilityManager service
     *
     * BUSINESS RULES:
     * - Model attribute: is_auto_generated = true (computed, read-only)
     * - Oryginał, Zamiennik: is_auto_generated = false (manually assignable)
     *
     * RELATED:
     * - ETAP_05d SEKCJA 0.2: PrestaShop ps_feature* mapping
     * - ETAP_05d FAZA 3: Oryginał/Zamiennik/Model Labels System
     * - CompatibilityAttributeSeeder: Polish names + colors update
     */
    public function up(): void
    {
        Schema::table('compatibility_attributes', function (Blueprint $table) {
            // Flag for auto-generated attributes (Model is computed from Oryginał + Zamiennik)
            $table->boolean('is_auto_generated')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compatibility_attributes', function (Blueprint $table) {
            $table->dropColumn('is_auto_generated');
        });
    }
};
