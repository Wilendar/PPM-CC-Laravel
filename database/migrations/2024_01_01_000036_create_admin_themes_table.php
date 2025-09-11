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
        Schema::create('admin_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Basic theme info
            $table->string('theme_name', 100);
            $table->string('primary_color', 7)->default('#3b82f6');
            $table->string('secondary_color', 7)->default('#64748b');
            $table->string('accent_color', 7)->default('#10b981');
            
            // Layout configuration
            $table->enum('layout_density', ['compact', 'normal', 'spacious'])->default('normal');
            $table->enum('sidebar_position', ['left', 'right'])->default('left');
            $table->enum('header_style', ['fixed', 'static', 'floating'])->default('fixed');
            
            // Custom CSS dla advanced customization
            $table->text('custom_css')->nullable();
            
            // Company branding
            $table->string('company_logo')->nullable();
            $table->string('company_name', 100)->default('PPM Admin');
            $table->json('company_colors')->nullable(); // Array of brand colors
            
            // Widget & dashboard configuration
            $table->json('widget_layout')->nullable(); // Stored widget positions
            $table->json('dashboard_settings')->nullable(); // Dashboard preferences
            
            // Status flags
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            
            // Additional settings jako JSON dla flexibility
            $table->json('settings_json')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['is_default']);
            $table->index(['theme_name']);
            
            // Ensure tylko jeden active theme per user
            $table->unique(['user_id', 'is_active'], 'unique_active_theme_per_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_themes');
    }
};