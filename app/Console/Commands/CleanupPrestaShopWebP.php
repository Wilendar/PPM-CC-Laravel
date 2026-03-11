<?php

namespace App\Console\Commands;

use App\Models\PrestaShopShop;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Clean up stale WebP files from PrestaShop image folders
 *
 * FIX 2026-02-05: PrestaShop modules (LiteSpeed, ps_webp) generate WebP copies
 * but don't regenerate them when JPG is replaced via API. This command deletes
 * old WebP files so they get regenerated from new JPG.
 *
 * Usage:
 *   php artisan prestashop:cleanup-webp 1              # Shop ID 1, all products
 *   php artisan prestashop:cleanup-webp 1 --product=11310  # Specific product
 *   php artisan prestashop:cleanup-webp 1 --image-ids=30778,30779  # Specific images
 */
class CleanupPrestaShopWebP extends Command
{
    protected $signature = 'prestashop:cleanup-webp
                            {shop_id : PrestaShop shop ID}
                            {--product= : PPM product ID (optional)}
                            {--image-ids= : Comma-separated PrestaShop image IDs (optional)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Remove stale WebP files from PrestaShop image folders after image sync';

    /**
     * SSH configuration for known shops (hardcoded for now)
     * TODO: Move to database or config file
     */
    private array $sshConfig = [
        // dev.mpptrade.pl (B2B Test DEV)
        1 => [
            'host' => 'host379076.hostido.net.pl',
            'port' => 64321,
            'user' => 'host379076',
            'key_path' => 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk',
            'root_path' => '/home/host379076/domains/dev.mpptrade.pl/public_html',
        ],
    ];

    public function handle(): int
    {
        $shopId = (int) $this->argument('shop_id');
        $productId = $this->option('product') ? (int) $this->option('product') : null;
        $imageIdsStr = $this->option('image-ids');
        $dryRun = $this->option('dry-run');

        // Validate shop
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            $this->error("Shop ID {$shopId} not found!");
            return 1;
        }

        // Check SSH config
        if (!isset($this->sshConfig[$shopId])) {
            $this->error("No SSH configuration for shop ID {$shopId}. Add it to CleanupPrestaShopWebP::sshConfig");
            return 1;
        }

        $ssh = $this->sshConfig[$shopId];
        $this->info("Shop: {$shop->name} ({$shop->url})");
        $this->info("SSH: {$ssh['user']}@{$ssh['host']}:{$ssh['port']}");

        // Collect image IDs to clean
        $imageIds = [];

        if ($imageIdsStr) {
            // Explicit image IDs provided
            $imageIds = array_map('intval', explode(',', $imageIdsStr));
            $this->info("Cleaning WebP for image IDs: " . implode(', ', $imageIds));
        } elseif ($productId) {
            // Get image IDs from product mapping
            $product = Product::find($productId);
            if (!$product) {
                $this->error("Product ID {$productId} not found!");
                return 1;
            }

            $imageIds = $this->getImageIdsFromProduct($product, $shopId);
            $this->info("Product: {$product->sku} ({$product->name})");
            $this->info("Found " . count($imageIds) . " mapped images");
        } else {
            $this->error("Either --product or --image-ids must be specified");
            return 1;
        }

        if (empty($imageIds)) {
            $this->warn("No image IDs found to clean");
            return 0;
        }

        // Build and execute cleanup commands
        $totalDeleted = 0;
        foreach ($imageIds as $imageId) {
            $path = $this->buildImagePath($imageId);
            $fullPath = $ssh['root_path'] . '/img/p/' . $path;

            $this->line("  Image {$imageId}: /img/p/{$path}");

            if ($dryRun) {
                $this->info("    [DRY-RUN] Would delete: {$fullPath}*.webp");
                continue;
            }

            $deleted = $this->deleteWebPFiles($ssh, $fullPath, $imageId);
            $totalDeleted += $deleted;

            if ($deleted > 0) {
                $this->info("    Deleted {$deleted} WebP files");
            } else {
                $this->line("    No WebP files found");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("Dry run complete. Use without --dry-run to actually delete files.");
        } else {
            $this->newLine();
            $this->info("Cleanup complete. Total WebP files deleted: {$totalDeleted}");

            Log::info('[CLEANUP WEBP] Command completed', [
                'shop_id' => $shopId,
                'product_id' => $productId,
                'image_ids_count' => count($imageIds),
                'deleted_count' => $totalDeleted,
            ]);
        }

        return 0;
    }

    /**
     * Get PrestaShop image IDs from product media mappings
     */
    private function getImageIdsFromProduct(Product $product, int $shopId): array
    {
        $imageIds = [];

        foreach ($product->media as $media) {
            $mapping = $media->prestashop_mapping ?? [];
            $storeKey = "store_{$shopId}";

            if (isset($mapping[$storeKey]['ps_image_id'])) {
                $imageIds[] = (int) $mapping[$storeKey]['ps_image_id'];
            }
        }

        return $imageIds;
    }

    /**
     * Build PrestaShop image path from ID
     * Example: 30778 -> 3/0/7/7/8/
     */
    private function buildImagePath(int $imageId): string
    {
        return implode('/', str_split((string) $imageId)) . '/';
    }

    /**
     * Delete WebP files via SSH
     */
    private function deleteWebPFiles(array $ssh, string $path, int $imageId): int
    {
        $keyPath = $ssh['key_path'];
        $host = $ssh['user'] . '@' . $ssh['host'];
        $port = $ssh['port'];

        // Count existing WebP files first
        $countCmd = "ls -1 {$path}{$imageId}*.webp 2>/dev/null | wc -l";
        $plinkCmd = sprintf(
            'plink -ssh %s -P %d -i "%s" -batch "%s"',
            $host,
            $port,
            $keyPath,
            $countCmd
        );

        $count = (int) trim(shell_exec($plinkCmd) ?? '0');

        if ($count === 0) {
            return 0;
        }

        // Delete WebP files
        $deleteCmd = "rm -f {$path}{$imageId}*.webp";
        $plinkCmd = sprintf(
            'plink -ssh %s -P %d -i "%s" -batch "%s"',
            $host,
            $port,
            $keyPath,
            $deleteCmd
        );

        shell_exec($plinkCmd);

        return $count;
    }
}
