<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_07 FAZA 5 - Migration 2/5
     *
     * Creates import_templates table for reusable column mapping configurations.
     *
     * PURPOSE:
     * - Store reusable column mappings for XLSX imports
     * - Avoid manual mapping for recurring import patterns
     * - Enable template sharing across users (is_shared flag)
     * - Track template popularity (usage_count)
     *
     * BUSINESS RULES:
     * - Cascade delete: if user deleted → owned templates deleted
     * - Shared templates (is_shared=true) visible to all users
     * - Private templates (is_shared=false) visible only to owner
     * - Usage count incremented each time template is used
     *
     * MAPPING CONFIG FORMAT (JSON):
     * {
     *   "A": "sku",
     *   "B": "name",
     *   "C": "variant.attributes.Kolor",
     *   "D": "variant.attributes.Rozmiar",
     *   "E": "price.detaliczna",
     *   "F": "stock.MPPTRADE"
     * }
     *
     * EXAMPLES:
     * - name='VARIANTS_TEMPLATE_v1', is_shared=true, usage_count=25
     * - name='MY_CUSTOM_TEMPLATE', is_shared=false, usage_count=3
     *
     * RELATIONSHIPS:
     * - belongs to User (cascade delete)
     */
    public function up(): void
    {
        Schema::create('import_templates', function (Blueprint $table) {
            $table->id();

            // Owner relation (cascade delete)
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Template owner');

            // Template metadata
            $table->string('name')
                  ->comment('Template name (e.g., VARIANTS_TEMPLATE_v1)');

            $table->text('description')->nullable()
                  ->comment('Template description for users');

            // Column mapping configuration (JSON)
            $table->json('mapping_config')
                  ->comment('Column mapping: {"A": "sku", "B": "name", ...}');

            // Sharing settings
            $table->boolean('is_shared')->default(false)
                  ->comment('Share with other users?');

            // Usage tracking
            $table->integer('usage_count')->default(0)
                  ->comment('How many times template was used');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_shared'], 'idx_template_user_shared');
            $table->index('usage_count', 'idx_template_usage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_templates');
    }
};
