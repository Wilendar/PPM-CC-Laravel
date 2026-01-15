<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create block_definitions table
 *
 * ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System
 *
 * Stores shop-specific block definitions created from prestashop-section blocks.
 * Each block has editable render_template that admin can modify directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('prestashop_shops')->cascadeOnDelete();

            // Block identification
            $table->string('type', 100)->index(); // Unique type slug per shop (e.g., 'pd-merits-kayo')
            $table->string('name', 255);          // Display name (e.g., 'Lista Zalet')
            $table->string('category', 100)->default('shop-custom');
            $table->string('icon', 100)->nullable()->default('heroicons-cube');
            $table->text('description')->nullable();

            // Block schema (JSON) - defines content and settings fields
            // Structure: { content: { items: {...} }, settings: { columns: {...} } }
            $table->json('schema');

            // Render template - Blade-like template for generating HTML
            // Admin can EDIT this directly to customize block output
            $table->longText('render_template');

            // CSS classes required by this block
            $table->json('css_classes')->nullable();

            // Original HTML that was used to create this block
            $table->longText('sample_html')->nullable();

            // Block status
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Unique constraint: one type per shop
            $table->unique(['shop_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_definitions');
    }
};
