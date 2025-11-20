<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add database credentials to prestashop_shops table
     *
     * BUGFIX 2025-11-05: PrestaShop 8 API ignores product-category associations
     *
     * Root Cause:
     * - PrestaShop API accepts XML with associations.categories
     * - Returns HTTP 200 (success)
     * - BUT does NOT save associations to ps_category_product table
     * - This makes products invisible in PrestaShop admin panel
     *
     * Solution:
     * - After successful API sync, connect to PrestaShop database directly
     * - INSERT product-category associations to ps_category_product
     * - This requires database credentials for each shop
     *
     * Security:
     * - Database passwords are encrypted using Laravel's encrypt() helper
     * - Stored in encrypted TEXT column
     * - Decrypted only when needed for database connection
     */
    public function up(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            // Database Connection Settings (for direct database access)
            $table->string('db_host', 200)->nullable()->after('api_version')
                ->comment('PrestaShop database host (dla category associations workaround)');

            $table->string('db_name', 200)->nullable()->after('db_host')
                ->comment('PrestaShop database name');

            $table->string('db_user', 200)->nullable()->after('db_name')
                ->comment('PrestaShop database user');

            $table->text('db_password')->nullable()->after('db_user')
                ->comment('PrestaShop database password (encrypted)');

            $table->boolean('enable_db_workaround')->default(true)->after('db_password')
                ->comment('Enable direct DB workaround for category associations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestashop_shops', function (Blueprint $table) {
            $table->dropColumn([
                'db_host',
                'db_name',
                'db_user',
                'db_password',
                'enable_db_workaround',
            ]);
        });
    }
};
