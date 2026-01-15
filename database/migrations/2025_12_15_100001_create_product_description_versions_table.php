<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ETAP_07f Faza 6.1.4.3 - Product Description Version History
 *
 * Przechowuje historię zmian opisów wizualnych produktów.
 * Umożliwia przywracanie poprzednich wersji i śledzenie zmian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_description_versions', function (Blueprint $table) {
            $table->id();

            // Reference to parent description
            $table->foreignId('product_description_id')
                ->constrained('product_descriptions')
                ->cascadeOnDelete();

            // Version number (auto-increment per description)
            $table->unsignedInteger('version_number');

            // Snapshot of blocks_json at this version
            $table->json('blocks_json')->nullable();

            // Snapshot of rendered HTML at this version
            $table->longText('rendered_html')->nullable();

            // User who made the change
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Type of change that created this version
            $table->enum('change_type', [
                'created',           // Initial creation
                'updated',           // Manual edit
                'synced',            // After sync to PrestaShop
                'template_applied',  // Template was applied
                'restored',          // Restored from previous version
                'auto_save',         // Auto-save during editing
            ])->default('updated');

            // Additional metadata (sync info, template info, etc.)
            $table->json('metadata')->nullable();

            // Checksum for quick comparison
            $table->string('checksum', 32)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['product_description_id', 'version_number'], 'pdv_desc_version');
            $table->index(['product_description_id', 'created_at'], 'pdv_desc_created');
            $table->index('created_by');
            $table->index('change_type');

            // Unique version number per description
            $table->unique(['product_description_id', 'version_number'], 'pdv_unique_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_description_versions');
    }
};
