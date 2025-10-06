<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPrestaShopVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestashop:fix-versions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix empty version field in prestashop_shops table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== FIXING PRESTASHOP VERSION FIELD ===');

        // Step 1: Check current state
        $this->info("\nStep 1: Checking current version field values...");
        $shops = DB::table('prestashop_shops')
            ->select('id', 'name', 'prestashop_version')
            ->get();

        $this->info('--- BEFORE UPDATE ---');
        foreach ($shops as $shop) {
            $version = $shop->prestashop_version ?: 'NULL';
            $this->line("ID: {$shop->id} | {$shop->name} | Version: [{$version}]");
        }

        // Count shops with empty prestashop_version
        $emptyCount = DB::table('prestashop_shops')
            ->where(function($q) {
                $q->whereNull('prestashop_version')
                  ->orWhere('prestashop_version', '')
                  ->orWhereRaw('TRIM(prestashop_version) = ?', ['']);
            })
            ->count();

        if ($emptyCount === 0) {
            $this->info("\nNo shops with empty version field found. Nothing to update.");
            return Command::SUCCESS;
        }

        $this->warn("\nFound {$emptyCount} shop(s) with empty version field.");

        // Step 2: Update empty versions to '8'
        $this->info("\nStep 2: Updating empty prestashop_version fields to '8'...");

        $updated = DB::table('prestashop_shops')
            ->where(function($q) {
                $q->whereNull('prestashop_version')
                  ->orWhere('prestashop_version', '')
                  ->orWhereRaw('TRIM(prestashop_version) = ?', ['']);
            })
            ->update(['prestashop_version' => '8']);

        $this->info("Updated {$updated} shop(s) to prestashop_version '8'");

        // Step 3: Verify update
        $this->info("\nStep 3: Verifying update...");
        $shops = DB::table('prestashop_shops')
            ->select('id', 'name', 'prestashop_version')
            ->get();

        $this->info('--- AFTER UPDATE ---');
        foreach ($shops as $shop) {
            $version = $shop->prestashop_version ?: 'NULL';
            $this->line("ID: {$shop->id} | {$shop->name} | Version: [{$version}]");
        }

        $this->newLine();
        $this->info('=== UPDATE COMPLETE ===');
        $this->success("All PrestaShop shops now have version field set.");

        return Command::SUCCESS;
    }
}
