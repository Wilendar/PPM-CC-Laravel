<?php

namespace App\Console\Commands;

use App\Jobs\PullProductsFromPrestaShop;
use App\Models\PrestaShopShop;
use Illuminate\Console\Command;

/**
 * Pull Products From PrestaShop Command
 *
 * FIX #4 - BUG #7 (2025-11-12)
 * CLI command to manually trigger product import from PrestaShop → PPM
 *
 * Usage:
 * - php artisan prestashop:pull-products 1        (single shop)
 * - php artisan prestashop:pull-products --all    (all active shops)
 *
 * @package App\Console\Commands
 */
class PullProductsFromPrestaShopCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prestashop:pull-products
                            {shop_id? : ID konkretnego sklepu PrestaShop}
                            {--all : Import z wszystkich aktywnych sklepów}';

    /**
     * The console command description.
     */
    protected $description = 'Import products, prices, and stock FROM PrestaShop TO PPM';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->pullFromAllShops();
        }

        $shopId = $this->argument('shop_id');

        if (!$shopId) {
            $this->error('Podaj shop_id lub użyj --all dla wszystkich sklepów');
            $this->line('');
            $this->line('Przykłady:');
            $this->line('  php artisan prestashop:pull-products 1');
            $this->line('  php artisan prestashop:pull-products --all');
            return Command::FAILURE;
        }

        return $this->pullFromShop($shopId);
    }

    /**
     * Pull products from a single shop
     */
    private function pullFromShop(int $shopId): int
    {
        $shop = PrestaShopShop::find($shopId);

        if (!$shop) {
            $this->error("Sklep o ID {$shopId} nie istnieje");
            return Command::FAILURE;
        }

        if (!$shop->is_active) {
            $this->error("Sklep '{$shop->name}' nie jest aktywny");
            $this->line('Aby aktywować sklep, zmień is_active na true w tabeli prestashop_shops');
            return Command::FAILURE;
        }

        $this->info("Rozpoczynam import z sklepu: {$shop->name}");
        $this->line("URL: {$shop->url}");
        $this->line("Wersja PrestaShop: {$shop->prestashop_version}");
        $this->line('');

        // Dispatch job
        PullProductsFromPrestaShop::dispatch($shop);

        $this->info('✓ Job dispatch successful!');
        $this->line('');
        $this->line('Sprawdź postęp w:');
        $this->line('  - Admin UI: /admin/shops/sync');
        $this->line('  - Logi: storage/logs/laravel.log');
        $this->line('  - Tabela: sync_jobs (job_type = import_products)');

        return Command::SUCCESS;
    }

    /**
     * Pull products from all active shops
     */
    private function pullFromAllShops(): int
    {
        $shops = PrestaShopShop::where('is_active', true)
            ->where('auto_sync_products', true)
            ->get();

        if ($shops->isEmpty()) {
            $this->warn('Brak aktywnych sklepów PrestaShop z włączonym auto_sync_products');
            $this->line('');
            $this->line('Aby włączyć auto sync dla sklepu:');
            $this->line('  UPDATE prestashop_shops SET auto_sync_products = true WHERE id = X;');
            return Command::SUCCESS;
        }

        $this->info("Znaleziono {$shops->count()} aktywnych sklepów z auto_sync_products = true");
        $this->line('');

        $bar = $this->output->createProgressBar($shops->count());
        $bar->start();

        foreach ($shops as $shop) {
            $this->line('');
            $this->line("Dispatching import job dla: {$shop->name} (ID: {$shop->id})");
            PullProductsFromPrestaShop::dispatch($shop);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        $this->info("✓ Wszystkie {$shops->count()} jobów dispatch successful!");
        $this->line('');
        $this->line('Sprawdź postęp w:');
        $this->line('  - Admin UI: /admin/shops/sync');
        $this->line('  - Logi: storage/logs/laravel.log');
        $this->line('  - Tabela: sync_jobs (job_type = import_products)');

        return Command::SUCCESS;
    }
}
