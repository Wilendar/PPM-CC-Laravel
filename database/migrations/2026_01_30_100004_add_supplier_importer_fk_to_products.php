<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add supplier_id and importer_id FK columns to products and pending_products
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Both columns reference business_partners table with SET NULL on delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add to products table
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')
                ->nullable()
                ->after('manufacturer_id')
                ->comment('FK to business_partners (supplier)');

            $table->unsignedBigInteger('importer_id')
                ->nullable()
                ->after('supplier_id')
                ->comment('FK to business_partners (importer)');

            $table->foreign('supplier_id', 'fk_products_supplier')
                ->references('id')
                ->on('business_partners')
                ->nullOnDelete();

            $table->foreign('importer_id', 'fk_products_importer')
                ->references('id')
                ->on('business_partners')
                ->nullOnDelete();

            $table->index('supplier_id', 'idx_products_supplier');
            $table->index('importer_id', 'idx_products_importer');
        });

        // Add to pending_products table (if exists)
        if (Schema::hasTable('pending_products')) {
            Schema::table('pending_products', function (Blueprint $table) {
                $afterColumn = Schema::hasColumn('pending_products', 'manufacturer_id')
                    ? 'manufacturer_id'
                    : 'id';

                $table->unsignedBigInteger('supplier_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->comment('FK to business_partners (supplier)');

                $table->unsignedBigInteger('importer_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->comment('FK to business_partners (importer)');

                $table->foreign('supplier_id', 'fk_pending_products_supplier')
                    ->references('id')
                    ->on('business_partners')
                    ->nullOnDelete();

                $table->foreign('importer_id', 'fk_pending_products_importer')
                    ->references('id')
                    ->on('business_partners')
                    ->nullOnDelete();

                $table->index('supplier_id', 'idx_pending_products_supplier');
                $table->index('importer_id', 'idx_pending_products_importer');
            });
        }
    }

    public function down(): void
    {
        // Remove from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('fk_products_supplier');
            $table->dropForeign('fk_products_importer');
            $table->dropIndex('idx_products_supplier');
            $table->dropIndex('idx_products_importer');
            $table->dropColumn(['supplier_id', 'importer_id']);
        });

        // Remove from pending_products (if exists)
        if (Schema::hasTable('pending_products')) {
            Schema::table('pending_products', function (Blueprint $table) {
                $table->dropForeign('fk_pending_products_supplier');
                $table->dropForeign('fk_pending_products_importer');
                $table->dropIndex('idx_pending_products_supplier');
                $table->dropIndex('idx_pending_products_importer');
                $table->dropColumn(['supplier_id', 'importer_id']);
            });
        }
    }
};
