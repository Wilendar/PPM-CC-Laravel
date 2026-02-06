<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fix Media Mappings Command
 *
 * Repairs broken prestashop_mapping for product media when race condition
 * caused NULL ps_image_id values during REPLACE ALL strategy.
 *
 * FIX 2026-02-05: Created to repair product 11310 mappings
 *
 * @package App\Console\Commands
 */
class FixMediaMappings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:fix-mappings
                            {product_id : PPM Product ID to fix}
                            {shop_id : PrestaShop Shop ID}
                            {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix broken prestashop_mapping for product media (repairs race condition damage)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $productId = (int) $this->argument('product_id');
        $shopId = (int) $this->argument('shop_id');
        $dryRun = $this->option('dry-run');

        $product = Product::find($productId);
        if (!$product) {
            $this->error("Product ID {$productId} not found!");
            return Command::FAILURE;
        }

        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            $this->error("Shop ID {$shopId} not found!");
            return Command::FAILURE;
        }

        $this->info("Fixing media mappings for: {$product->name} (ID: {$productId})");
        $this->info("Shop: {$shop->name} (ID: {$shopId})");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - no changes will be made");
        }

        // Get PrestaShop product ID
        $shopData = $product->shopData()->where('shop_id', $shopId)->first();
        if (!$shopData || !$shopData->prestashop_product_id) {
            $this->error("Product not mapped to PrestaShop for this shop!");
            return Command::FAILURE;
        }

        $psProductId = $shopData->prestashop_product_id;
        $this->info("PrestaShop Product ID: {$psProductId}");

        // Fetch images from PrestaShop
        $client = new PrestaShop8Client($shop);
        $psImages = $client->getProductImages($psProductId);

        if (empty($psImages)) {
            $this->warn("No images found in PrestaShop for this product.");
            return Command::SUCCESS;
        }

        $psImageIds = array_map(
            fn($img) => is_array($img) ? (int) ($img['id'] ?? 0) : (int) $img,
            $psImages
        );
        sort($psImageIds);

        $this->info("PrestaShop images: " . implode(', ', $psImageIds));

        // Get PPM media for this product
        $ppmMedia = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $productId)
            ->active()
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();

        $this->info("PPM media count: " . $ppmMedia->count());

        // Check for mismatches
        $storeKey = "store_{$shopId}";
        $mismatches = [];
        $alreadyMapped = [];

        foreach ($ppmMedia as $media) {
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $currentPsId = $mapping['ps_image_id'] ?? null;

            if ($currentPsId && in_array($currentPsId, $psImageIds)) {
                $alreadyMapped[$media->id] = $currentPsId;
            } elseif ($currentPsId === null) {
                $mismatches[] = $media;
            }
        }

        if (empty($mismatches)) {
            $this->info("All media mappings are correct!");
            return Command::SUCCESS;
        }

        $this->warn("Found " . count($mismatches) . " media with NULL mappings");

        // Find unmapped PrestaShop image IDs
        $unmappedPsIds = array_diff($psImageIds, $alreadyMapped);
        sort($unmappedPsIds);

        $this->info("Unmapped PrestaShop IDs: " . implode(', ', $unmappedPsIds));

        // Match by position (order in PPM = order in PrestaShop)
        $this->newLine();
        $this->table(
            ['PPM Media ID', 'File Name', 'Current PS ID', 'Will Map To'],
            collect($mismatches)->map(function ($media, $index) use ($unmappedPsIds) {
                return [
                    $media->id,
                    $media->file_name,
                    'NULL',
                    $unmappedPsIds[$index] ?? 'N/A',
                ];
            })->toArray()
        );

        if ($dryRun) {
            $this->warn("DRY RUN - no changes made. Remove --dry-run to apply fixes.");
            return Command::SUCCESS;
        }

        // Apply fixes
        $fixed = 0;
        foreach ($mismatches as $index => $media) {
            if (!isset($unmappedPsIds[$index])) {
                $this->warn("No unmapped PS ID available for media {$media->id}");
                continue;
            }

            $newPsId = $unmappedPsIds[$index];

            $media->setPrestaShopMapping($shopId, [
                'ps_product_id' => $psProductId,
                'ps_image_id' => $newPsId,
                'is_cover' => $media->is_primary,
                'synced_at' => now()->toIso8601String(),
                'fixed_by' => 'media:fix-mappings command',
                'fixed_at' => now()->toIso8601String(),
            ]);

            $this->info("Fixed: Media {$media->id} -> PS Image {$newPsId}");
            $fixed++;
        }

        Log::info('[MEDIA FIX] Mappings repaired by command', [
            'product_id' => $productId,
            'shop_id' => $shopId,
            'fixed_count' => $fixed,
        ]);

        $this->newLine();
        $this->info("Successfully fixed {$fixed} media mappings!");

        return Command::SUCCESS;
    }
}
