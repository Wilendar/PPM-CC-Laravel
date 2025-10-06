<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create product_types table
 *
 * Zastępuje hardcoded ENUM system elastycznym
 * zarządzaniem typami produktów
 *
 * @package Database\Migrations
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name', 100)->comment('Nazwa typu produktu');
            $table->string('slug', 100)->unique()->comment('URL-friendly slug');
            $table->text('description')->nullable()->comment('Opis typu produktu');

            // Visual & Configuration
            $table->string('icon', 100)->nullable()->comment('Ikona typu (CSS class lub SVG)');
            $table->json('default_attributes')->nullable()->comment('Domyślne atrybuty dla typu');

            // Status & Ordering
            $table->boolean('is_active')->default(true)->comment('Status aktywności typu');
            $table->integer('sort_order')->default(0)->comment('Kolejność wyświetlania');

            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_active', 'sort_order'], 'idx_product_types_active_order');
            $table->index('slug', 'idx_product_types_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};