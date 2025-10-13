<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FIX: Make 'category' column nullable in integration_logs table
     *
     * Root cause: Column was created without ->nullable() or ->default()
     * causing SQL error: "Field 'category' doesn't have a default value"
     * when IntegrationLog is created without explicitly setting category.
     */
    public function up(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->string('category', 100)->nullable(false)->change();
        });
    }
};
