<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create business_partner_shop pivot table
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Links business partners to PrestaShop shops with sync tracking.
 * Supports both manufacturer and supplier IDs per shop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_partner_shop', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_partner_id')
                ->constrained('business_partners')
                ->cascadeOnDelete();

            // FK to prestashop_shops only if table exists
            if (Schema::hasTable('prestashop_shops')) {
                $table->foreignId('prestashop_shop_id')
                    ->constrained('prestashop_shops')
                    ->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('prestashop_shop_id');
            }

            // PrestaShop entity IDs
            $table->unsignedInteger('ps_manufacturer_id')->nullable()
                ->comment('PrestaShop manufacturer ID for this shop');
            $table->unsignedInteger('ps_supplier_id')->nullable()
                ->comment('PrestaShop supplier ID for this shop');

            // Sync tracking
            $table->enum('sync_status', ['pending', 'synced', 'error'])
                ->default('pending');
            $table->timestamp('last_synced_at')->nullable();

            // Logo sync
            $table->boolean('logo_synced')->default(false);
            $table->timestamp('logo_synced_at')->nullable();

            // Error tracking
            $table->text('sync_error')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(
                ['business_partner_id', 'prestashop_shop_id'],
                'bp_shop_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_partner_shop');
    }
};
