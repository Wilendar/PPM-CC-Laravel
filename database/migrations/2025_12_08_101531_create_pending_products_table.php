<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_products', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('sku', 50)->index();
            $table->string('name')->nullable();
            $table->string('product_type')->nullable(); // 'część_zamienna', 'pojazd', 'odzież'
            $table->json('category_path')->nullable(); // [L3_id, L4_id, L5_id, ...]

            // Variant support
            $table->boolean('is_variant')->default(false);
            $table->json('variants')->nullable(); // Array of variant data

            // Features & Compatibility
            $table->json('features')->nullable(); // For vehicles: {Model, Year, Engine, VIN}
            $table->json('compatibilities')->nullable(); // For parts: [{vehicle, type}]

            // Media
            $table->json('images')->nullable(); // Array of temp file paths
            $table->integer('primary_image_index')->nullable();

            // Shop assignment
            $table->json('shop_ids')->nullable(); // Array of shop IDs

            // Import metadata
            $table->string('import_session_id', 36)->index(); // UUID for batch grouping
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('text_paste'); // 'text_paste', 'csv', 'xlsx'
            $table->integer('source_row')->nullable(); // Row number from import file

            // Status & validation
            $table->enum('status', ['incomplete', 'ready', 'published', 'error'])->default('incomplete');
            $table->json('missing_fields')->nullable(); // Array of required fields missing
            $table->json('validation_errors')->nullable(); // Array of validation errors

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['import_session_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_products');
    }
};
