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
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->json('validation_warnings')->nullable()->after('pending_fields');
            $table->boolean('has_validation_warnings')->default(false)->after('validation_warnings');
            $table->timestamp('validation_checked_at')->nullable()->after('has_validation_warnings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_shop_data', function (Blueprint $table) {
            $table->dropColumn(['validation_warnings', 'has_validation_warnings', 'validation_checked_at']);
        });
    }
};
