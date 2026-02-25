<?php

namespace App\Http\Livewire\Products\Listing\Traits;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Jobs\PrestaShop\BulkImportProducts;
use Illuminate\Support\Facades\Log;

/**
 * ProductListPrestaShopImport Trait
 *
 * Manages PrestaShop import functionality:
 * - Import modal (all/category/individual modes)
 * - Category tree loading with lazy expand
 * - Product search and selection
 * - Import execution (non-blocking with JobProgress)
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListPrestaShopImport
{
    /*
    |--------------------------------------------------------------------------
    | IMPORT PROPERTIES
    |--------------------------------------------------------------------------
    */

    public bool $showImportModal = false;
    public ?int $importShopId = null;
    public string $importMode = 'all';
    public ?int $importCategoryId = null;
    public array $selectedProductsToImport = [];
    public array $prestashopProducts = [];
    public array $prestashopCategories = [];
    public array $expandedCategories = [];
    public array $cachedCategoryChildren = [];
    public array $cachedProductSearches = [];
    public string $importSearch = '';
    public bool $importIncludeSubcategories = true;
    public bool $importWithVariants = false;

    // Category Preview Loading State
    public bool $isAnalyzingCategories = false;
    public ?string $analyzingShopName = null;
    public array $shownPreviewIds = [];

    /*
    |--------------------------------------------------------------------------
    | MODAL CONTROL
    |--------------------------------------------------------------------------
    */

    public function openImportModal(string $mode = 'all'): void
    {
        $this->importMode = $mode;
        $this->showImportModal = true;
        $this->importShopId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->importSearch = '';
        $this->prestashopCategories = [];
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importShopId = null;
        $this->importMode = 'all';
        $this->importCategoryId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->prestashopCategories = [];
        $this->importSearch = '';
        $this->expandedCategories = [];
        $this->cachedCategoryChildren = [];
        $this->cachedProductSearches = [];
    }

    public function resetShopSelection(): void
    {
        $this->importShopId = null;
        $this->importCategoryId = null;
        $this->selectedProductsToImport = [];
        $this->prestashopProducts = [];
        $this->prestashopCategories = [];
        $this->importSearch = '';
    }

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE HOOKS
    |--------------------------------------------------------------------------
    */

    public function setImportShop(int $shopId): void
    {
        $this->importShopId = $shopId;

        if ($this->importMode === 'category') {
            $this->loadPrestaShopCategories();
        } elseif ($this->importMode === 'individual') {
            $this->loadPrestaShopProducts();
        }
    }

    public function updatedImportShopId($value): void
    {
        if ($value) {
            $this->setImportShop((int) $value);
        }
    }

    public function updatedImportSearch(): void
    {
        if ($this->importMode === 'individual' && $this->importShopId) {
            if (empty($this->importSearch) || strlen($this->importSearch) < 3) {
                $this->prestashopProducts = [];
                return;
            }
            $this->loadPrestaShopProducts();
        }
    }

    public function updatedImportMode($value): void
    {
        if (!$this->importShopId) {
            return;
        }

        if ($value === 'category') {
            $this->loadPrestaShopCategories();
        } elseif ($value === 'individual') {
            $this->prestashopProducts = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT ALL PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function importAllProducts(): void
    {
        if (!$this->importShopId) {
            $this->dispatch('error', message: 'Wybierz sklep PrestaShop');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);
        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress($jobId, $shop, 'import', 0);

        $progress = \App\Models\JobProgress::where('job_id', $jobId)->first();
        if ($progress) {
            $progress->updateMetadata([
                'mode' => 'all',
                'shop_name' => $shop->name,
                'initiated_by' => auth()->user()?->name ?? 'System',
                'phase' => 'queued',
                'phase_label' => 'W kolejce - oczekuje na uruchomienie',
            ]);
            $progress->user_id = auth()->id();
            $progress->save();
        }

        BulkImportProducts::dispatch($shop, 'all', [
            'import_with_variants' => $this->importWithVariants,
        ], $jobId);

        $this->dispatch('success', message: "Import z {$shop->name} uruchomiony. Postep widoczny w belce 'Aktywne operacje'.");
        $this->closeImportModal();
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT FROM CATEGORY
    |--------------------------------------------------------------------------
    */

    public function selectImportCategory(int $categoryId): void
    {
        $this->importCategoryId = $categoryId;
    }

    public function importFromCategory(): void
    {
        if (!$this->importShopId || !$this->importCategoryId) {
            $this->dispatch('error', message: 'Wybierz sklep i kategorię');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);
        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress($jobId, $shop, 'import', 0);

        $progress = \App\Models\JobProgress::where('job_id', $jobId)->first();
        if ($progress) {
            $categoryName = $this->prestashopCategories[$this->importCategoryId]['name'] ?? "Kategoria #{$this->importCategoryId}";
            $progress->updateMetadata([
                'mode' => 'category',
                'category_id' => $this->importCategoryId,
                'category_name' => $categoryName,
                'include_subcategories' => $this->importIncludeSubcategories,
                'shop_name' => $shop->name,
                'initiated_by' => auth()->user()?->name ?? 'System',
                'phase' => 'queued',
                'phase_label' => 'W kolejce - oczekuje na uruchomienie',
            ]);
            $progress->user_id = auth()->id();
            $progress->save();
        }

        BulkImportProducts::dispatch($shop, 'category', [
            'category_id' => $this->importCategoryId,
            'include_subcategories' => $this->importIncludeSubcategories,
            'import_with_variants' => $this->importWithVariants,
        ], $jobId);

        $this->dispatch('success', message: "Import z kategorii uruchomiony. Postep widoczny w belce 'Aktywne operacje'.");
        $this->closeImportModal();
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT SELECTED PRODUCTS
    |--------------------------------------------------------------------------
    */

    public function toggleProductSelection(int $productId): void
    {
        $key = array_search($productId, $this->selectedProductsToImport);

        if ($key !== false) {
            unset($this->selectedProductsToImport[$key]);
            $this->selectedProductsToImport = array_values($this->selectedProductsToImport);
        } else {
            $this->selectedProductsToImport[] = $productId;
        }
    }

    public function importSelectedProducts(): void
    {
        if (!$this->importShopId || empty($this->selectedProductsToImport)) {
            $this->dispatch('error', message: 'Wybierz sklep i przynajmniej jeden produkt');
            return;
        }

        $shop = PrestaShopShop::find($this->importShopId);
        if (!$shop) {
            $this->dispatch('error', message: 'Sklep nie został znaleziony');
            return;
        }

        $productCount = count($this->selectedProductsToImport);

        $jobId = (string) \Illuminate\Support\Str::uuid();
        $progressService = app(\App\Services\JobProgressService::class);

        $progressService->createPendingJobProgress($jobId, $shop, 'import', $productCount);

        $progress = \App\Models\JobProgress::where('job_id', $jobId)->first();
        if ($progress) {
            $progress->updateMetadata([
                'mode' => 'individual',
                'product_count' => $productCount,
                'product_ids' => $this->selectedProductsToImport,
                'shop_name' => $shop->name,
                'initiated_by' => auth()->user()?->name ?? 'System',
                'phase' => 'queued',
                'phase_label' => 'W kolejce - oczekuje na uruchomienie',
            ]);
            $progress->user_id = auth()->id();
            $progress->save();
        }

        BulkImportProducts::dispatch($shop, 'individual', [
            'product_ids' => $this->selectedProductsToImport,
            'import_with_variants' => $this->importWithVariants,
        ], $jobId);

        $this->dispatch('success', message: sprintf("Import %d produktow uruchomiony. Postep widoczny w belce 'Aktywne operacje'.", $productCount));
        $this->closeImportModal();
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY TREE LOADING
    |--------------------------------------------------------------------------
    */

    public function loadPrestaShopCategories(): void
    {
        if (!$this->importShopId) {
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) {
                $this->dispatch('error', message: 'Sklep nie został znaleziony');
                return;
            }

            if (empty($shop->version)) {
                $this->dispatch('error', message: 'Sklep nie ma ustawionej wersji PrestaShop. Skonfiguruj wersję w panelu zarządzania sklepami.');
                Log::error('PrestaShop shop missing version', ['shop_id' => $shop->id, 'shop_name' => $shop->name]);
                return;
            }

            $client = PrestaShopClientFactory::create($shop);

            $response = $client->getCategories([
                'display' => '[id,name,id_parent,level_depth,nb_products_recursive]',
                'language' => 1,
                'filter[level_depth]' => '[0,2]',
            ]);

            $this->prestashopCategories = [];

            Log::debug('loadPrestaShopCategories response structure', [
                'response_keys' => is_array($response) ? array_keys($response) : 'not_array',
                'has_categories_key' => isset($response['categories']),
            ]);

            if (is_array($response)) {
                if (isset($response['categories']) && is_array($response['categories'])) {
                    if (isset($response['categories']['category'])) {
                        $categories = $response['categories']['category'];
                        $this->prestashopCategories = is_array($categories) ? (isset($categories[0]) ? $categories : [$categories]) : [];
                    } else {
                        $this->prestashopCategories = $response['categories'];
                    }
                } elseif (isset($response[0]) && is_array($response[0])) {
                    $this->prestashopCategories = $response;
                } elseif (isset($response['prestashop']['categories'])) {
                    $categories = $response['prestashop']['categories'];
                    if (isset($categories['category'])) {
                        $this->prestashopCategories = is_array($categories['category'][0] ?? null)
                            ? $categories['category']
                            : [$categories['category']];
                    } else {
                        $this->prestashopCategories = is_array($categories) ? $categories : [];
                    }
                }
            }

            usort($this->prestashopCategories, function($a, $b) {
                $levelA = $a['level_depth'] ?? 0;
                $levelB = $b['level_depth'] ?? 0;
                if ($levelA !== $levelB) {
                    return $levelA <=> $levelB;
                }
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            });

            Log::info('PrestaShop root categories loaded', [
                'shop_id' => $this->importShopId,
                'count' => count($this->prestashopCategories),
            ]);

            $this->expandedCategories = [1, 2];

        } catch (\Exception $e) {
            Log::error('Failed to load PrestaShop categories', [
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', message: 'Nie udało się pobrać kategorii: ' . $e->getMessage());
        }
    }

    public function toggleCategoryExpand(int $categoryId): void
    {
        $key = array_search($categoryId, $this->expandedCategories);

        if ($key !== false) {
            unset($this->expandedCategories[$key]);
            $this->expandedCategories = array_values($this->expandedCategories);
            return;
        }

        try {
            $existingChildren = array_filter($this->prestashopCategories, function($cat) use ($categoryId) {
                return ($cat['id_parent'] ?? null) == $categoryId;
            });

            if (!empty($existingChildren)) {
                $this->expandedCategories[] = $categoryId;
                return;
            }

            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) return;

            $client = PrestaShopClientFactory::create($shop);

            $response = $client->getCategories([
                'display' => 'full',
                'language' => 1,
                'filter[id_parent]' => "[{$categoryId}]",
            ]);

            $children = [];
            if (isset($response['categories']) && is_array($response['categories'])) {
                $children = $response['categories'];
            }

            $parentIndex = null;
            foreach ($this->prestashopCategories as $index => $cat) {
                if ($cat['id'] == $categoryId) {
                    $parentIndex = $index;
                    break;
                }
            }

            if ($parentIndex !== null && !empty($children)) {
                array_splice($this->prestashopCategories, $parentIndex + 1, 0, $children);
                $this->expandedCategories[] = $categoryId;
            }

        } catch (\Exception $e) {
            Log::error('Failed to load category children', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function fetchCategoryChildren(int $categoryId): bool
    {
        try {
            $existingChildren = array_filter($this->prestashopCategories, function($cat) use ($categoryId) {
                return ($cat['id_parent'] ?? null) == $categoryId;
            });

            if (!empty($existingChildren)) {
                $this->skipRender();
                return true;
            }

            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) return false;

            $client = PrestaShopClientFactory::create($shop);

            $response = $client->getCategories([
                'display' => '[id,name,id_parent,level_depth,nb_products_recursive]',
                'language' => 1,
                'filter[id_parent]' => "[{$categoryId}]",
            ]);

            $children = [];
            if (isset($response['categories']) && is_array($response['categories'])) {
                $children = $response['categories'];
            }

            if (!empty($children)) {
                $parentIndex = null;
                $parentLevel = 0;
                foreach ($this->prestashopCategories as $index => $cat) {
                    if ($cat['id'] == $categoryId) {
                        $parentIndex = $index;
                        $parentLevel = (int)($cat['level_depth'] ?? 0);
                        break;
                    }
                }

                $childLevel = $parentLevel + 1;
                foreach ($children as &$child) {
                    if (!isset($child['level_depth']) || $child['level_depth'] == 0) {
                        $child['level_depth'] = $childLevel;
                    }
                }
                unset($child);

                if ($parentIndex !== null) {
                    array_splice($this->prestashopCategories, $parentIndex + 1, 0, $children);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to fetch category children', [
                'category_id' => $categoryId,
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nie udało się załadować podkategorii: ' . $e->getMessage(),
            ]);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT LOADING
    |--------------------------------------------------------------------------
    */

    public function loadPrestaShopProducts(): void
    {
        if (!$this->importShopId) return;

        $cacheKey = $this->importSearch;
        if (isset($this->cachedProductSearches[$cacheKey])) {
            $this->prestashopProducts = $this->cachedProductSearches[$cacheKey];
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->importShopId);
            if (!$shop) {
                $this->dispatch('error', message: 'Sklep nie został znaleziony');
                return;
            }

            if (empty($shop->version)) {
                $this->dispatch('error', message: 'Sklep nie ma ustawionej wersji PrestaShop.');
                return;
            }

            $client = PrestaShopClientFactory::create($shop);

            $params = ['display' => 'full', 'language' => 1];
            $allProducts = [];

            if (!empty($this->importSearch)) {
                $paramsName = $params;
                $paramsName['filter[name]'] = '%[' . $this->importSearch . ']%';
                $responseByName = $client->getProducts($paramsName);

                $paramsRef = $params;
                $paramsRef['filter[reference]'] = '[' . $this->importSearch . ']';
                $responseByReference = $client->getProducts($paramsRef);

                if (empty($responseByName['products']) && empty($responseByReference['products'])) {
                    $paramsBegins = $params;
                    $paramsBegins['filter[name]'] = '[' . $this->importSearch . ']%';
                    $responseBegins = $client->getProducts($paramsBegins);
                    $response = ['products' => $responseBegins['products'] ?? []];
                } else {
                    $response = [
                        'products' => array_merge(
                            $responseByName['products'] ?? [],
                            $responseByReference['products'] ?? []
                        )
                    ];
                }
            } else {
                $response = $client->getProducts($params);
            }

            $allProducts = [];

            if (is_array($response)) {
                if (isset($response['products']) && is_array($response['products'])) {
                    if (isset($response['products']['product'])) {
                        $products = $response['products']['product'];
                        $allProducts = is_array($products) ? (isset($products[0]) ? $products : [$products]) : [];
                    } else {
                        $allProducts = $response['products'];
                    }
                } elseif (isset($response[0]) && is_array($response[0])) {
                    $allProducts = $response;
                } elseif (isset($response['prestashop']['products'])) {
                    $products = $response['prestashop']['products'];
                    if (isset($products['product'])) {
                        $allProducts = is_array($products['product'][0] ?? null)
                            ? $products['product']
                            : [$products['product']];
                    } else {
                        $allProducts = is_array($products) ? $products : [];
                    }
                }
            }

            $uniqueProducts = [];
            foreach ($allProducts as $product) {
                $productId = $product['id'] ?? null;
                if ($productId && !isset($uniqueProducts[$productId])) {
                    $uniqueProducts[$productId] = $product;
                }
            }

            $this->prestashopProducts = array_values($uniqueProducts);
            $this->cachedProductSearches[$cacheKey] = $this->prestashopProducts;

            Log::info('PrestaShop products loaded', [
                'shop_id' => $this->importShopId,
                'total' => count($allProducts),
                'filtered' => count($this->prestashopProducts),
                'search' => $this->importSearch,
            ]);

            $this->autoCheckVariantCheckbox();

        } catch (\Exception $e) {
            Log::error('Failed to load PrestaShop products', [
                'shop_id' => $this->importShopId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', message: 'Nie udało się pobrać produktów: ' . $e->getMessage());
        }
    }

    private function autoCheckVariantCheckbox(): void
    {
        foreach ($this->prestashopProducts as $product) {
            $hasCombinations = false;

            if (isset($product['associations']['combinations'])) {
                $combinations = $product['associations']['combinations'];

                if (isset($combinations['combination']) && is_array($combinations['combination'])) {
                    $combArray = $combinations['combination'];
                    $hasCombinations = !empty($combArray) && (isset($combArray[0]) || isset($combArray['id']));
                } elseif (is_array($combinations) && !empty($combinations)) {
                    $hasCombinations = true;
                }
            }

            if ($hasCombinations) {
                $this->importWithVariants = true;
                return;
            }
        }
    }

    private function productExistsInPPM(array $prestashopProduct): bool
    {
        $sku = $prestashopProduct['reference'] ?? null;
        if (!$sku) return false;
        return Product::where('sku', $sku)->exists();
    }
}
