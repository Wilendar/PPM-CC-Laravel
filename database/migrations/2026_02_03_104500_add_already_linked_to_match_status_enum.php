<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ETAP_10: Add 'already_linked' to match_status ENUM
 *
 * This migration adds 'already_linked' status to product_scan_results
 * for products that are already linked to the source before scan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL ENUM modification requires raw SQL
        DB::statement("ALTER TABLE `product_scan_results` MODIFY COLUMN `match_status` ENUM('matched', 'unmatched', 'conflict', 'multiple', 'already_linked') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove already_linked from ENUM
        // Note: This will fail if any rows have 'already_linked' status
        DB::statement("ALTER TABLE `product_scan_results` MODIFY COLUMN `match_status` ENUM('matched', 'unmatched', 'conflict', 'multiple') NOT NULL");
    }
};
