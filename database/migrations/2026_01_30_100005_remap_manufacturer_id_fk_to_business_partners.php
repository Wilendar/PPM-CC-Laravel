<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remap manufacturer_id FK from manufacturers to business_partners
 * ETAP: BusinessPartner System (Dostawca/Producent/Importer)
 *
 * Drops existing FK on products.manufacturer_id -> manufacturers(id)
 * and creates new FK on products.manufacturer_id -> business_partners(id).
 * Same for pending_products (if applicable).
 *
 * IMPORTANT: Migration 3 preserved original IDs, so FK values remain valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remap products.manufacturer_id FK
        if (Schema::hasColumn('products', 'manufacturer_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Drop old FK to manufacturers (if exists)
                $this->dropForeignKeySafe('products', 'manufacturer_id', 'manufacturers');
            });

            Schema::table('products', function (Blueprint $table) {
                // Add new FK to business_partners
                $table->foreign('manufacturer_id', 'fk_products_manufacturer_bp')
                    ->references('id')
                    ->on('business_partners')
                    ->nullOnDelete();
            });
        }

        // Remap pending_products.manufacturer_id FK (if table and column exist)
        if (Schema::hasTable('pending_products') && Schema::hasColumn('pending_products', 'manufacturer_id')) {
            Schema::table('pending_products', function (Blueprint $table) {
                $this->dropForeignKeySafe('pending_products', 'manufacturer_id', 'manufacturers');
            });

            Schema::table('pending_products', function (Blueprint $table) {
                $table->foreign('manufacturer_id', 'fk_pending_products_manufacturer_bp')
                    ->references('id')
                    ->on('business_partners')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Restore products FK to manufacturers
        if (Schema::hasColumn('products', 'manufacturer_id') && Schema::hasTable('manufacturers')) {
            Schema::table('products', function (Blueprint $table) {
                $this->dropForeignKeySafe('products', 'manufacturer_id', 'business_partners');
            });

            Schema::table('products', function (Blueprint $table) {
                $table->foreign('manufacturer_id')
                    ->references('id')
                    ->on('manufacturers')
                    ->nullOnDelete();
            });
        }

        // Restore pending_products FK to manufacturers
        if (Schema::hasTable('pending_products')
            && Schema::hasColumn('pending_products', 'manufacturer_id')
            && Schema::hasTable('manufacturers')
        ) {
            Schema::table('pending_products', function (Blueprint $table) {
                $this->dropForeignKeySafe('pending_products', 'manufacturer_id', 'business_partners');
            });

            Schema::table('pending_products', function (Blueprint $table) {
                $table->foreign('manufacturer_id')
                    ->references('id')
                    ->on('manufacturers')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Safely drop FK constraint by trying common naming conventions.
     */
    private function dropForeignKeySafe(string $table, string $column, string $referencedTable): void
    {
        $possibleNames = [
            "{$table}_{$column}_foreign",           // Laravel default convention
            "fk_{$table}_manufacturer_bp",           // Our new naming
            "fk_{$table}_manufacturer",              // Alternative naming
        ];

        // Query actual FK constraints from information_schema
        $dbName = DB::getDatabaseName();
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table]
        );

        $constraintNames = array_map(fn($c) => $c->CONSTRAINT_NAME, $constraints);

        // Find and drop matching FK constraint
        foreach ($possibleNames as $name) {
            if (in_array($name, $constraintNames)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
                return;
            }
        }

        // Fallback: drop any FK on this column referencing the target table
        $fkOnColumn = DB::select(
            "SELECT kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
               AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
               AND kcu.TABLE_NAME = tc.TABLE_NAME
             WHERE kcu.TABLE_SCHEMA = ?
               AND kcu.TABLE_NAME = ?
               AND kcu.COLUMN_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME = ?
               AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $column, $referencedTable]
        );

        foreach ($fkOnColumn as $fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
    }
};
