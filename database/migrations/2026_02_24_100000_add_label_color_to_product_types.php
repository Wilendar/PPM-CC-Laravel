<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductType;

/**
 * Migration: Add label_color column to product_types table
 *
 * Dodaje kolor etykiety dla wizualnego oznaczania typow produktow
 * w interfejsie (badge, chip, tag).
 *
 * @since Compatibility Tiles - Visual Product Type Labels
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->string('label_color', 7)
                ->nullable()
                ->after('icon')
                ->comment('HEX color for type label badge (e.g. #3b82f6)');
        });

        // Seed default colors for existing types
        $colorMap = [
            'pojazdy' => '#3b82f6',
            'pojazd' => '#3b82f6',
            'czesci-zamienne' => '#f59e0b',
            'czesc-zamienna' => '#f59e0b',
            'akcesoria' => '#10b981',
            'odziez' => '#a855f7',
            'oleje-i-chemia' => '#06b6d4',
            'outlet' => '#ef4444',
            'inne' => '#6b7280',
        ];

        foreach ($colorMap as $slug => $hex) {
            ProductType::where('slug', $slug)->update(['label_color' => $hex]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropColumn('label_color');
        });
    }
};
