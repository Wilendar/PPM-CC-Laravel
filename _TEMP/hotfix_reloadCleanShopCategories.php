<?php
// HOTFIX: reloadCleanShopCategories() optional parameter
// Problem: Method called without param at line 4786, but signature requires int $shopId
// Solution: Make $shopId optional - if null, reload ALL shops (legacy behavior)

use App\Models\ProductShopData;
use App\Services\CategoryMappingsConverter;
use Illuminate\Support\Facades\Log;

echo "=== HOTFIX: reloadCleanShopCategories() signature ===\n\n";

$file = 'app/Http/Livewire/Products/Management/ProductForm.php';

// Read file
$content = file_get_contents($file);

// Find and replace method signature
$oldSignature = 'protected function reloadCleanShopCategories(int $shopId): void';
$newSignature = 'protected function reloadCleanShopCategories(?int $shopId = null): void';

if (strpos($content, $oldSignature) === false) {
    echo "❌ Old signature NOT FOUND\n";
    echo "Searching for: $oldSignature\n";
    exit(1);
}

$content = str_replace($oldSignature, $newSignature, $content);

// Now replace method body to handle both cases
$oldBody = <<<'PHP'
    {
        $shopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();

        if ($shopData && $shopData->hasCategoryMappings()) {
            $converter = app(CategoryMappingsConverter::class);
            $this->shopCategories[$shopId] = $converter->toUiFormat(
                $shopData->category_mappings
            );

            Log::debug('[FIX #12] Reloaded shop categories to UI', [
                'shop_id' => $shopId,
                'ui_categories' => $this->shopCategories[$shopId],
            ]);

            // Trigger Livewire re-render
            $this->dispatch('shop-categories-reloaded', shopId: $shopId);
        }
    }
PHP;

$newBody = <<<'PHP'
    {
        // FIX #12 + HOTFIX: Support both single-shop and all-shops reload
        if ($shopId !== null) {
            // Single shop reload (FIX #12 - new behavior)
            $shopData = ProductShopData::where('product_id', $this->product->id)
                ->where('shop_id', $shopId)
                ->first();

            if ($shopData && $shopData->hasCategoryMappings()) {
                $converter = app(CategoryMappingsConverter::class);
                $this->shopCategories[$shopId] = $converter->toUiFormat(
                    $shopData->category_mappings
                );

                Log::debug('[FIX #12] Reloaded shop categories to UI (single)', [
                    'shop_id' => $shopId,
                    'ui_categories' => $this->shopCategories[$shopId],
                ]);

                // Trigger Livewire re-render
                $this->dispatch('shop-categories-reloaded', shopId: $shopId);
            }
        } else {
            // All shops reload (legacy behavior for backward compatibility)
            if (!$this->product || !$this->product->exists) {
                return;
            }

            // Clear potentially contaminated shopCategories
            $this->shopCategories = [];

            // Load clean shop categories from database for ALL shops
            $allShopData = ProductShopData::where('product_id', $this->product->id)
                ->whereNotNull('category_mappings')
                ->get();

            $converter = app(CategoryMappingsConverter::class);

            foreach ($allShopData as $shopData) {
                if ($shopData->hasCategoryMappings()) {
                    $this->shopCategories[$shopData->shop_id] = $converter->toUiFormat(
                        $shopData->category_mappings
                    );
                }
            }

            Log::debug('[HOTFIX] Reloaded shop categories to UI (all shops)', [
                'product_id' => $this->product->id,
                'shop_count' => count($this->shopCategories),
                'shop_ids' => array_keys($this->shopCategories),
            ]);
        }
    }
PHP;

$content = str_replace($oldBody, $newBody, $content);

// Save
file_put_contents($file, $content);

echo "✅ Method signature changed:\n";
echo "   OLD: protected function reloadCleanShopCategories(int \$shopId): void\n";
echo "   NEW: protected function reloadCleanShopCategories(?int \$shopId = null): void\n\n";

echo "✅ Method body updated:\n";
echo "   - If \$shopId provided → reload single shop (FIX #12)\n";
echo "   - If \$shopId = null → reload ALL shops (legacy)\n\n";

echo "✅ HOTFIX COMPLETE\n";
