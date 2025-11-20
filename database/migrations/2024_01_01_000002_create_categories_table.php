<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Categories Table - Self-Referencing Tree Structure
     * Obsługuje: 5 poziomów zagnieżdżenia, path optimization, breadcrumbs
     * Format path: '/1/2/5' dla szybkich ancestor/descendant queries
     * 
     * Performance: Index na path dla tree queries <50ms
     * Tree operations: parent_id dla relationhips, level dla depth control
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // === PRIMARY IDENTITY ===
            $table->id(); // SERIAL PRIMARY KEY
            $table->unsignedBigInteger('parent_id')->nullable(); // Self-reference
            
            // === BASIC CATEGORY INFO ===
            $table->string('name', 300); // Nazwa kategorii
            $table->string('slug', 300)->nullable(); // URL-friendly slug
            $table->text('description')->nullable(); // Opis kategorii
            
            // === TREE STRUCTURE OPTIMIZATION ===
            $table->tinyInteger('level')->default(0); // Poziom zagnieżdżenia (0-4)
            $table->string('path', 500)->nullable(); // '/1/2/5' - szybkie tree queries
            
            // === CATEGORY STATUS & ORDERING ===
            $table->integer('sort_order')->default(0); // Sortowanie w kategorii
            $table->boolean('is_active')->default(true)->index();
            $table->string('icon', 200)->nullable(); // Ikona kategorii (font-awesome, custom)
            
            // === SEO METADATA ===
            $table->string('meta_title', 300)->nullable();
            $table->string('meta_description', 300)->nullable();
            
            // === AUDIT & TIMESTAMPS ===
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft delete support
            
            // === FOREIGN KEY CONSTRAINTS ===
            $table->foreign('parent_id')
                  ->references('id')->on('categories')
                  ->onDelete('cascade') // Cascade delete children when parent deleted
                  ->onUpdate('cascade');
            
            // === PERFORMANCE INDEXES ===
            $table->index(['parent_id']); // Tree traversal
            $table->index(['level', 'sort_order']); // Level-based sorting
            $table->index(['path']); // CRITICAL dla tree queries performance
            $table->index(['is_active', 'level']); // Active categories per level
            $table->index(['slug']); // URL lookups
            $table->index(['created_at']); // Chronological sorting
        });

        // === ADDITIONAL CONSTRAINTS ===
        // Prevent circular references and deep nesting
        DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_max_level CHECK (level >= 0 AND level <= 4)');
        // TEMPORARY DISABLED: MySQL 8+ doesn't allow check constraints on FK columns with CASCADE
        // TODO: Move to application-level validation or use BEFORE INSERT trigger
        // DB::statement('ALTER TABLE categories ADD CONSTRAINT chk_no_self_parent CHECK (id != parent_id)');
    }

    /**
     * Reverse the migrations.
     * 
     * Rollback support - usuwa tabelę categories z wszystkimi constraintami
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};