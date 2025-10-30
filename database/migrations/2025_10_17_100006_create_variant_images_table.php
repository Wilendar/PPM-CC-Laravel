<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ETAP_05a FAZA 1 - Migration 6/15
     *
     * Creates variant_images table for storing variant-specific images.
     *
     * PURPOSE:
     * - Allow each variant to have unique images (e.g., Red jacket shows red images, Blue shows blue)
     * - Support multiple images per variant (gallery)
     * - Define cover image (primary/featured image)
     * - Control image ordering via position
     *
     * BUSINESS RULES:
     * - Cascade delete: if variant deleted → images deleted
     * - One variant can have multiple images
     * - One image can be marked as cover (is_cover=true)
     * - Images ordered by position for display
     *
     * STORAGE:
     * - image_path: Full-size image path (e.g., "products/variants/123/image.jpg")
     * - image_thumb_path: Thumbnail image path (e.g., "products/variants/123/thumb_image.jpg")
     *
     * EXAMPLES:
     * - variant_id=1, image_path="variants/red-xl/front.jpg", is_cover=true, position=1
     * - variant_id=1, image_path="variants/red-xl/back.jpg", is_cover=false, position=2
     *
     * RELATIONSHIPS:
     * - belongs to ProductVariant (cascade delete)
     */
    public function up(): void
    {
        Schema::create('variant_images', function (Blueprint $table) {
            $table->id();

            // Variant relation (cascade delete)
            $table->foreignId('variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            // Image paths
            $table->string('image_path', 500);
            $table->string('image_thumb_path', 500)->nullable();

            // Image metadata
            $table->boolean('is_cover')->default(false);
            $table->integer('position')->default(0);

            $table->timestamps();

            // Indexes for performance
            $table->index(['variant_id', 'is_cover'], 'idx_variant_img_cover');
            $table->index(['variant_id', 'position'], 'idx_variant_img_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_images');
    }
};
