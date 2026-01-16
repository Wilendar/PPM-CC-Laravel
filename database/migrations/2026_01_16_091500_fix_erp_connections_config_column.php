<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix erp_connections.connection_config column type
 *
 * Problem: Column is JSON but model encrypts data before storing.
 * Encrypted data is NOT valid JSON, causing CHECK constraint violation.
 * Solution: Change column type from JSON to TEXT to store encrypted strings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // Change from json to text to allow encrypted data storage
            $table->text('connection_config')->change();
        });
    }

    public function down(): void
    {
        Schema::table('erp_connections', function (Blueprint $table) {
            // Revert to json (only works if data is valid JSON)
            $table->json('connection_config')->change();
        });
    }
};
