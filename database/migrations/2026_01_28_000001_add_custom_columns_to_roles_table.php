<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add custom columns to roles table for PPM role management
 *
 * Columns:
 * - level: Role hierarchy level (1=Admin ... 7=User)
 * - color: Role badge color for UI
 * - description: Role description text
 * - is_system: Flag for system roles (cannot be deleted)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->tinyInteger('level')->default(7)->after('guard_name');
            $table->string('color', 50)->default('gray')->after('level');
            $table->string('description', 1000)->nullable()->after('color');
            $table->boolean('is_system')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['level', 'color', 'description', 'is_system']);
        });
    }
};
