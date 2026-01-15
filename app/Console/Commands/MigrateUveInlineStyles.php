<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductDescription;
use App\Services\VisualEditor\InlineStyleMigrator;
use Illuminate\Console\Command;

/**
 * Artisan command to migrate UVE inline styles to CSS-First format.
 *
 * ETAP_07h v2.0: Bulk migration of existing descriptions.
 *
 * Usage:
 * php artisan uve:migrate-inline-styles --dry-run
 * php artisan uve:migrate-inline-styles --shop=1 --limit=100
 * php artisan uve:migrate-inline-styles --product=123
 *
 * @package App\Console\Commands
 */
class MigrateUveInlineStyles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'uve:migrate-inline-styles
                            {--shop= : Migrate only for specific shop ID}
                            {--product= : Migrate only specific product ID}
                            {--limit= : Limit number of descriptions to process}
                            {--dry-run : Preview changes without saving}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate UVE descriptions from inline styles to CSS-First format (ETAP_07h v2.0)';

    /**
     * Execute the console command.
     */
    public function handle(InlineStyleMigrator $migrator): int
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  UVE CSS-First Migration Tool v2.0');
        $this->info('========================================');
        $this->info('');

        $dryRun = $this->option('dry-run');
        $shopId = $this->option('shop');
        $productId = $this->option('product');
        $limit = $this->option('limit');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->info('');
        }

        // Build query
        $query = ProductDescription::query()
            ->whereNotNull('blocks_v2')
            ->where(function ($q) {
                // Find descriptions that need migration (no css_rules or have inline styles)
                $q->whereNull('css_rules')
                    ->orWhere('css_rules', '=', '[]')
                    ->orWhereNull('css_migrated_at');
            });

        if ($shopId) {
            $query->where('shop_id', $shopId);
            $this->info("Filtering by shop ID: {$shopId}");
        }

        if ($productId) {
            $query->where('product_id', $productId);
            $this->info("Filtering by product ID: {$productId}");
        }

        if ($limit) {
            $query->limit((int) $limit);
            $this->info("Limiting to: {$limit} descriptions");
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No descriptions found that need migration.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalCount} descriptions to process");
        $this->info('');

        // Confirmation
        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm("Proceed with migration of {$totalCount} descriptions?")) {
                $this->info('Migration cancelled.');
                return Command::SUCCESS;
            }
        }

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        $results = [
            'migrated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        // Process in chunks
        $query->chunk(50, function ($descriptions) use ($migrator, $dryRun, $progressBar, &$results) {
            foreach ($descriptions as $description) {
                $progressBar->setMessage("Product {$description->product_id}, Shop {$description->shop_id}");

                $result = $migrator->migrate($description, $dryRun);

                switch ($result['status']) {
                    case 'migrated':
                        $results['migrated'][] = $result;
                        break;
                    case 'skipped':
                        $results['skipped'][] = $result;
                        break;
                    case 'error':
                        $results['errors'][] = $result;
                        break;
                }

                $progressBar->advance();
            }
        });

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->info('');
        $this->info('');

        // Summary
        $stats = $migrator->getStats();

        $this->info('========================================');
        $this->info('  Migration Summary');
        $this->info('========================================');
        $this->info('');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Migrated', $stats['migrated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
                ['Styles Extracted', $stats['styles_extracted']],
                ['Unique CSS Rules', $stats['unique_rules']],
            ]
        );

        $this->info('');

        // Show errors if any
        if (!empty($results['errors'])) {
            $this->error('Errors occurred during migration:');
            foreach ($results['errors'] as $error) {
                $this->line("  - Description {$error['description_id']}: {$error['error']}");
            }
            $this->info('');
        }

        // Show sample migrations
        if (!empty($results['migrated']) && $this->getOutput()->isVerbose()) {
            $this->info('Sample migrations:');
            foreach (array_slice($results['migrated'], 0, 5) as $migration) {
                $this->line("  - Product {$migration['product_id']}: {$migration['styles_extracted']} styles -> {$migration['unique_rules']} rules");
            }
            $this->info('');
        }

        if ($dryRun) {
            $this->warn('DRY RUN COMPLETE - No changes were saved');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->info('Migration complete!');
            $this->info('');
            $this->info('Next steps:');
            $this->info('1. Run CSS sync job to upload CSS to PrestaShop:');
            $this->info('   php artisan queue:work --queue=prestashop');
            $this->info('');
            $this->info('2. Verify CSS in PrestaShop: /themes/{theme}/css/uve-custom.css');
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
