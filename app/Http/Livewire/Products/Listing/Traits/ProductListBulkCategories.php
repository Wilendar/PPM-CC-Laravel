<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProductListBulkCategories Trait
 *
 * Manages bulk category operations on selected products:
 * - Assign categories (with primary category selection)
 * - Remove categories (common categories detection)
 * - Move categories (replace or add+keep mode)
 *
 * Requires ProductListBulkActions trait for $selectedProducts property.
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListBulkCategories
{
    /*
    |--------------------------------------------------------------------------
    | BULK CATEGORY PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Assign
    public bool $showBulkAssignCategoriesModal = false;
    public array $selectedCategoriesForBulk = [];
    public ?int $primaryCategoryForBulk = null;

    // Remove
    public bool $showBulkRemoveCategoriesModal = false;
    public array $commonCategories = [];
    public array $categoriesToRemove = [];

    // Move
    public bool $showBulkMoveCategoriesModal = false;
    public ?int $fromCategoryId = null;
    public ?int $toCategoryId = null;
    public string $moveMode = 'replace';

    /*
    |--------------------------------------------------------------------------
    | CATEGORY OPERATIONS ENTRY POINT
    |--------------------------------------------------------------------------
    */

    public function openBulkCategoryModal(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }
        $this->openBulkAssignCategories();
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN CATEGORIES
    |--------------------------------------------------------------------------
    */

    public function openBulkAssignCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $this->selectedCategoriesForBulk = [];
        $this->primaryCategoryForBulk = null;
        $this->showBulkAssignCategoriesModal = true;
    }

    public function closeBulkAssignCategories(): void
    {
        $this->showBulkAssignCategoriesModal = false;
        $this->selectedCategoriesForBulk = [];
        $this->primaryCategoryForBulk = null;
    }

    public function bulkAssignCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (empty($this->selectedCategoriesForBulk)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jedną kategorię');
            return;
        }

        if (count($this->selectedCategoriesForBulk) > 10) {
            $this->dispatch('error', message: 'Maksymalnie 10 kategorii na produkt');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);
            $categoriesCount = count($this->selectedCategoriesForBulk);

            if ($productsCount <= 50) {
                $this->executeBulkAssignSync();
                $this->dispatch('success', message: "Przypisano {$categoriesCount} kategorii do {$productsCount} produktów");
            } else {
                $this->dispatchBulkAssignJob($productsCount, $categoriesCount);
            }

            $this->resetSelection();
            $this->closeBulkAssignCategories();
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Assign Categories failed', [
                'products' => $this->selectedProducts,
                'categories' => $this->selectedCategoriesForBulk,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas przypisywania kategorii: ' . $e->getMessage());
        }
    }

    private function executeBulkAssignSync(): void
    {
        DB::transaction(function () {
            foreach ($this->selectedProducts as $productId) {
                $product = Product::find($productId);
                if (!$product) continue;

                foreach ($this->selectedCategoriesForBulk as $categoryId) {
                    $exists = DB::table('product_categories')
                        ->where('product_id', $productId)
                        ->where('category_id', $categoryId)
                        ->whereNull('shop_id')
                        ->exists();

                    if (!$exists) {
                        $isPrimary = ($categoryId == $this->primaryCategoryForBulk);

                        if ($isPrimary) {
                            DB::table('product_categories')
                                ->where('product_id', $productId)
                                ->whereNull('shop_id')
                                ->update(['is_primary' => false]);
                        }

                        DB::table('product_categories')->insert([
                            'product_id' => $productId,
                            'category_id' => $categoryId,
                            'shop_id' => null,
                            'is_primary' => $isPrimary,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } elseif ($categoryId == $this->primaryCategoryForBulk) {
                        DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->whereNull('shop_id')
                            ->update(['is_primary' => false]);

                        DB::table('product_categories')
                            ->where('product_id', $productId)
                            ->where('category_id', $categoryId)
                            ->whereNull('shop_id')
                            ->update(['is_primary' => true]);
                    }
                }

                $product->touch();
            }
        });
    }

    private function dispatchBulkAssignJob(int $productsCount, int $categoriesCount): void
    {
        $jobId = (string) \Illuminate\Support\Str::uuid();
        \App\Jobs\Products\BulkAssignCategories::dispatch(
            $this->selectedProducts,
            $this->selectedCategoriesForBulk,
            $this->primaryCategoryForBulk,
            $jobId
        );

        $this->dispatch('info', message: "Przypisywanie {$categoriesCount} kategorii do {$productsCount} produktów rozpoczęte. Postęp zobaczysz poniżej.");

        Log::info('Bulk Assign Categories queued', [
            'products_count' => $productsCount,
            'categories_count' => $categoriesCount,
            'job_id' => $jobId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REMOVE CATEGORIES
    |--------------------------------------------------------------------------
    */

    public function openBulkRemoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $this->categoriesToRemove = [];
        $this->commonCategories = $this->getCommonCategories();

        if (empty($this->commonCategories)) {
            $this->dispatch('warning', message: 'Wybrane produkty nie mają wspólnych kategorii');
            return;
        }

        $this->showBulkRemoveCategoriesModal = true;
    }

    public function closeBulkRemoveCategories(): void
    {
        $this->showBulkRemoveCategoriesModal = false;
        $this->commonCategories = [];
        $this->categoriesToRemove = [];
    }

    public function bulkRemoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (empty($this->categoriesToRemove)) {
            $this->dispatch('error', message: 'Wybierz co najmniej jedną kategorię do usunięcia');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);
            $categoriesCount = count($this->categoriesToRemove);

            if ($productsCount <= 50) {
                $this->executeBulkRemoveSync();
                $this->dispatch('success', message: "Usunięto {$categoriesCount} kategorii z {$productsCount} produktów");
            } else {
                $this->dispatchBulkRemoveJob($productsCount, $categoriesCount);
            }

            $this->resetSelection();
            $this->closeBulkRemoveCategories();
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Remove Categories failed', [
                'products' => $this->selectedProducts,
                'categories' => $this->categoriesToRemove,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas usuwania kategorii: ' . $e->getMessage());
        }
    }

    private function executeBulkRemoveSync(): void
    {
        DB::transaction(function () {
            foreach ($this->selectedProducts as $productId) {
                $product = Product::find($productId);
                if (!$product) continue;

                $removingPrimary = DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->whereIn('category_id', $this->categoriesToRemove)
                    ->whereNull('shop_id')
                    ->where('is_primary', true)
                    ->exists();

                DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->whereIn('category_id', $this->categoriesToRemove)
                    ->whereNull('shop_id')
                    ->delete();

                if ($removingPrimary) {
                    $firstRemaining = DB::table('product_categories')
                        ->where('product_id', $productId)
                        ->whereNull('shop_id')
                        ->first();

                    if ($firstRemaining) {
                        DB::table('product_categories')
                            ->where('id', $firstRemaining->id)
                            ->update(['is_primary' => true]);
                    }
                }

                $product->touch();
            }
        });
    }

    private function dispatchBulkRemoveJob(int $productsCount, int $categoriesCount): void
    {
        $jobId = (string) \Illuminate\Support\Str::uuid();
        \App\Jobs\Products\BulkRemoveCategories::dispatch(
            $this->selectedProducts,
            $this->categoriesToRemove,
            $jobId
        );

        $this->dispatch('info', message: "Usuwanie {$categoriesCount} kategorii z {$productsCount} produktów rozpoczęte. Postęp zobaczysz poniżej.");

        Log::info('Bulk Remove Categories queued', [
            'products_count' => $productsCount,
            'categories_count' => $categoriesCount,
            'job_id' => $jobId,
        ]);
    }

    private function getCommonCategories(): array
    {
        if (empty($this->selectedProducts)) {
            return [];
        }

        $productsCount = count($this->selectedProducts);

        $commonCategories = DB::table('product_categories')
            ->join('categories', 'product_categories.category_id', '=', 'categories.id')
            ->whereIn('product_categories.product_id', $this->selectedProducts)
            ->whereNull('product_categories.shop_id')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(DISTINCT product_categories.product_id) as product_count'),
                DB::raw('MAX(product_categories.is_primary) as is_primary_in_any')
            )
            ->groupBy('categories.id', 'categories.name')
            ->having('product_count', '=', $productsCount)
            ->get()
            ->toArray();

        return array_map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'is_primary_in_any' => (bool) $cat->is_primary_in_any,
            ];
        }, $commonCategories);
    }

    /*
    |--------------------------------------------------------------------------
    | MOVE CATEGORIES
    |--------------------------------------------------------------------------
    */

    public function openBulkMoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        $this->fromCategoryId = null;
        $this->toCategoryId = null;
        $this->moveMode = 'replace';
        $this->showBulkMoveCategoriesModal = true;
    }

    public function closeBulkMoveCategories(): void
    {
        $this->showBulkMoveCategoriesModal = false;
        $this->fromCategoryId = null;
        $this->toCategoryId = null;
        $this->moveMode = 'replace';
    }

    public function bulkMoveCategories(): void
    {
        if (empty($this->selectedProducts)) {
            $this->dispatch('error', message: 'Nie zaznaczono żadnych produktów');
            return;
        }

        if (!$this->fromCategoryId || !$this->toCategoryId) {
            $this->dispatch('error', message: 'Wybierz kategorię źródłową i docelową');
            return;
        }

        if ($this->fromCategoryId == $this->toCategoryId) {
            $this->dispatch('error', message: 'Kategoria źródłowa i docelowa muszą być różne');
            return;
        }

        try {
            $productsCount = count($this->selectedProducts);

            if ($productsCount <= 50) {
                $this->executeBulkMoveSync();
            } else {
                $this->dispatchBulkMoveJob($productsCount);
            }

            $this->resetSelection();
            $this->closeBulkMoveCategories();
            unset($this->products);

        } catch (\Exception $e) {
            Log::error('Bulk Move Categories failed', [
                'products' => $this->selectedProducts,
                'from_category' => $this->fromCategoryId,
                'to_category' => $this->toCategoryId,
                'mode' => $this->moveMode,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('error', message: 'Błąd podczas przenoszenia kategorii: ' . $e->getMessage());
        }
    }

    private function executeBulkMoveSync(): void
    {
        DB::transaction(function () {
            $movedCount = 0;

            foreach ($this->selectedProducts as $productId) {
                $product = Product::find($productId);
                if (!$product) continue;

                $hasFromCategory = DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->where('category_id', $this->fromCategoryId)
                    ->whereNull('shop_id')
                    ->exists();

                if (!$hasFromCategory) continue;

                $wasPrimary = DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->where('category_id', $this->fromCategoryId)
                    ->whereNull('shop_id')
                    ->value('is_primary');

                if ($this->moveMode === 'replace') {
                    DB::table('product_categories')
                        ->where('product_id', $productId)
                        ->where('category_id', $this->fromCategoryId)
                        ->whereNull('shop_id')
                        ->delete();
                }

                $existsTo = DB::table('product_categories')
                    ->where('product_id', $productId)
                    ->where('category_id', $this->toCategoryId)
                    ->whereNull('shop_id')
                    ->exists();

                if (!$existsTo) {
                    DB::table('product_categories')->insert([
                        'product_id' => $productId,
                        'category_id' => $this->toCategoryId,
                        'shop_id' => null,
                        'is_primary' => $wasPrimary,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($wasPrimary) {
                    DB::table('product_categories')
                        ->where('product_id', $productId)
                        ->whereNull('shop_id')
                        ->update(['is_primary' => false]);

                    DB::table('product_categories')
                        ->where('product_id', $productId)
                        ->where('category_id', $this->toCategoryId)
                        ->whereNull('shop_id')
                        ->update(['is_primary' => true]);
                }

                $movedCount++;
                $product->touch();
            }

            if ($movedCount === 0) {
                $this->dispatch('warning', message: 'Żaden produkt nie posiadał kategorii źródłowej');
            } else {
                $modeText = $this->moveMode === 'replace' ? 'Przeniesiono' : 'Skopiowano';
                $this->dispatch('success', message: "{$modeText} {$movedCount} produktów między kategoriami");
            }
        });
    }

    private function dispatchBulkMoveJob(int $productsCount): void
    {
        $jobId = (string) \Illuminate\Support\Str::uuid();
        \App\Jobs\Products\BulkMoveCategories::dispatch(
            $this->selectedProducts,
            $this->fromCategoryId,
            $this->toCategoryId,
            $this->moveMode,
            $jobId
        );

        $modeText = $this->moveMode === 'replace' ? 'Przenoszenie' : 'Kopiowanie';
        $this->dispatch('info', message: "{$modeText} {$productsCount} produktów między kategoriami rozpoczęte. Postęp zobaczysz poniżej.");

        Log::info('Bulk Move Categories queued', [
            'products_count' => $productsCount,
            'from_category' => $this->fromCategoryId,
            'to_category' => $this->toCategoryId,
            'mode' => $this->moveMode,
            'job_id' => $jobId,
        ]);
    }
}
