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
        Schema::table('block_definitions', function (Blueprint $table) {
            // Visual Block Builder document (JSON structure for visual editing)
            $table->json('builder_document')->nullable()->after('render_template');
            $table->string('builder_version', 10)->default('1.0')->after('builder_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('block_definitions', function (Blueprint $table) {
            $table->dropColumn(['builder_document', 'builder_version']);
        });
    }
};
