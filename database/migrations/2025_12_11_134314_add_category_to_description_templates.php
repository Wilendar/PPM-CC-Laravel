<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add category column to description_templates.
     *
     * Categories allow grouping templates by product type or section type:
     * - motocykle, quady, czesci, akcesoria, odziez (product types)
     * - intro, features, specs, gallery (section types)
     * - other (default)
     */
    public function up(): void
    {
        Schema::table('description_templates', function (Blueprint $table) {
            $table->string('category', 50)
                ->default('other')
                ->after('shop_id')
                ->comment('Kategoria szablonu (motocykle, quady, czesci, akcesoria, odziez, intro, features, specs, gallery, other)');

            $table->index('category', 'idx_templates_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('description_templates', function (Blueprint $table) {
            $table->dropIndex('idx_templates_category');
            $table->dropColumn('category');
        });
    }
};
