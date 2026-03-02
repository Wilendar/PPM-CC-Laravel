<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Exports\ScanMatrixExport;
use App\Jobs\ERP\SyncProductToERP;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Models\DismissedBrandSuggestion;
use App\Models\ERPConnection;
use App\Models\Media;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductScanResult;
use App\Models\ProductShopData;
use App\Models\SmartSyncBrandRule;
use App\Services\PrestaShop\CategorySyncService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\Scan\CrossSourceMatrixService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * MatrixActionsTrait
 *
 * Obsluguje akcje na komorkach macierzy Cross-Source:
 * - Powiazanie (link) produktu ze zrodlem
 * - Eksport (publish) produktu do zrodla
 * - Ignorowanie / przywracanie produktow
 * - Bulk actions na zaznaczonych produktach
 * - Popup, diff viewer, selekcja
 */
trait MatrixActionsTrait
{
    /** @var array<int> IDs produktow z rozwiniętym diff viewerem */
    public array $expandedDiffs = [];

    /** @var array{productId: int, sourceKey: string}|null Aktywny popup */
    public ?array $activePopup = null;

    /** @var array<int> Zaznaczone produkty (widoczne) */
    public array $selectedProducts = [];

    /** Checkbox "zaznacz wszystkie" w naglowku */
    public bool $selectAll = false;

    /** Tryb "zaznacz wszystkie pasujace" (Gmail pattern) */
    public bool $selectAllMatching = false;

    /** @var array<int> Wykluczone produkty w trybie selectAllMatching */
    public array $excludedProducts = [];

    /** Calkowita liczba produktow pasujacych do filtrow */
    public int $totalMatchingCount = 0;

    /** Sekcja odrzuconych sugestii brandow */
    public bool $showDismissedSuggestions = false;

    // =========================================================================
    // CELL ACTION DISPATCHER
    // =========================================================================

    public function cellAction(int $productId, string $sourceType, int $sourceId, string $action): void
    {
        Log::info('MatrixActionsTrait::cellAction CALLED', compact('productId', 'sourceType', 'sourceId', 'action'));

        match ($action) {
            'link'       => $this->linkProduct($productId, $sourceType, $sourceId),
            'publish'    => $this->publishToSource($productId, $sourceType, $sourceId),
            'ignore'     => $this->ignoreProduct($productId, $sourceType, $sourceId),
            'unignore'   => $this->unignoreProduct($productId, $sourceType, $sourceId),
            'unlink'     => $this->unlinkProduct($productId, $sourceType, $sourceId),
            'add_brand'  => $this->addBrandFromProduct($productId, $sourceId),
            'force_sync' => $this->forceSync($productId, $sourceType, $sourceId),
            default      => Log::warning('MatrixActionsTrait: unknown action', ['action' => $action]),
        };

        $this->closePopup();
    }

    // =========================================================================
    // BUG1 FIX: LINK - produkt istnieje w zrodle, tworzymy powiazanie w PPM
    // =========================================================================

    public function linkProduct(int $productId, string $sourceType, int $sourceId): void
    {
        // Znajdz wynik skanowania z danymi produktu w zrodle
        $scanResult = ProductScanResult::where('ppm_product_id', $productId)
            ->where('external_source_type', $sourceType)
            ->where('external_source_id', $sourceId)
            ->whereIn('match_status', [
                ProductScanResult::MATCH_MATCHED,
                ProductScanResult::MATCH_CONFLICT,
                ProductScanResult::MATCH_MULTIPLE,
            ])
            ->latest('id')
            ->first();

        if (!$scanResult) {
            // Fallback - szukaj po SKU
            $product = Product::find($productId);
            if ($product) {
                $scanResult = ProductScanResult::where('sku', $product->sku)
                    ->where('external_source_type', $sourceType)
                    ->where('external_source_id', $sourceId)
                    ->whereIn('match_status', [
                        ProductScanResult::MATCH_MATCHED,
                        ProductScanResult::MATCH_CONFLICT,
                        ProductScanResult::MATCH_MULTIPLE,
                    ])
                    ->latest('id')
                    ->first();
            }
        }

        $externalId = $scanResult?->external_id;

        if ($sourceType === 'prestashop') {
            ProductShopData::updateOrCreate(
                ['product_id' => $productId, 'shop_id' => $sourceId],
                [
                    'prestashop_product_id' => $externalId,
                    'sync_status' => 'synced',
                    'is_published' => true,
                    'last_sync_at' => now(),
                ]
            );
        } else {
            ProductErpData::updateOrCreate(
                ['product_id' => $productId, 'erp_connection_id' => $sourceId],
                [
                    'external_id' => $externalId,
                    'sync_status' => ProductErpData::STATUS_SYNCED,
                    'last_sync_at' => now(),
                ]
            );
        }

        // Oznacz scan result jako linked
        if ($scanResult) {
            $scanResult->update([
                'resolution_status' => ProductScanResult::RESOLUTION_LINKED,
                'ppm_product_id' => $productId,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);
        }

        $this->dispatch('cell-updated', productId: $productId, sourceKey: $sourceType . '_' . $sourceId);
        $this->refreshMatrix();
    }

    // =========================================================================
    // BUG2 FIX: PUBLISH/EXPORT - produkt NIE istnieje w zrodle, eksportujemy
    // =========================================================================

    public function publishToSource(int $productId, string $sourceType, int $sourceId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            session()->flash('error', 'Produkt nie znaleziony.');
            return;
        }

        if ($sourceType === 'prestashop') {
            $this->publishToPrestaShop($product, $sourceId);
        } else {
            $this->publishToErp($product, $sourceId);
        }

        // Aktualizuj scan result
        ProductScanResult::where('ppm_product_id', $productId)
            ->where('external_source_type', $sourceType)
            ->where('external_source_id', $sourceId)
            ->update(['resolution_status' => ProductScanResult::RESOLUTION_CREATED]);

        $this->dispatch('cell-updated', productId: $productId, sourceKey: $sourceType . '_' . $sourceId);
        $this->refreshMatrix();
    }

    protected function publishToPrestaShop(Product $product, int $shopId): void
    {
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            session()->flash('error', 'Sklep PrestaShop nie znaleziony.');
            return;
        }

        // Sync kategorii - bierz z default tab produktu, twórz w PS jesli nie istnieja
        $categoryMappings = $this->syncCategoriesForExport($product, $shop);

        // Utworz/zaktualizuj ProductShopData z pelnymi danymi produktu
        ProductShopData::updateOrCreate(
            ['product_id' => $product->id, 'shop_id' => $shopId],
            [
                'sync_status' => 'pending',
                'is_published' => false,
                'name' => $product->name,
                'short_description' => $product->short_description ?? '',
                'long_description' => $product->long_description ?? '',
                'meta_title' => $product->meta_title ?? $product->name,
                'meta_description' => $product->meta_description ?? '',
                'category_mappings' => $categoryMappings,
            ]
        );

        // Przygotuj media changes - auto-sync WSZYSTKICH zdjec produktu
        $pendingMediaChanges = $this->buildMediaChanges($product->id, $shopId);

        // Dispatch job sync do PrestaShop (queued)
        SyncProductToPrestaShop::dispatch($product, $shop, auth()->id(), $pendingMediaChanges);
        Log::info('Matrix: dispatched SyncProductToPrestaShop', [
            'product_id' => $product->id, 'shop_id' => $shopId,
            'media_count' => count($pendingMediaChanges),
            'has_categories' => !empty($categoryMappings['mappings']),
        ]);
    }

    protected function publishToErp(Product $product, int $connectionId): void
    {
        $connection = ERPConnection::find($connectionId);
        if (!$connection) {
            session()->flash('error', 'Polaczenie ERP nie znalezione.');
            return;
        }

        ProductErpData::updateOrCreate(
            ['product_id' => $product->id, 'erp_connection_id' => $connectionId],
            ['sync_status' => 'pending']
        );

        // Dispatch ERP sync (synchronous - brak queue workera na Hostido)
        SyncProductToERP::dispatchSync($product, $connection, null, [
            'sync_prices' => true,
            'sync_stock' => true,
        ]);
        Log::info('Matrix: SyncProductToERP completed', [
            'product_id' => $product->id, 'erp_connection_id' => $connectionId,
        ]);
    }

    /**
     * Buduje pendingMediaChanges: wszystkie media produktu oznaczone jako "sync".
     * Format: ["mediaId:shopId" => "sync"]
     */
    protected function buildMediaChanges(int $productId, int $shopId): array
    {
        $media = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $productId)
            ->where('is_active', true)
            ->pluck('id');

        $changes = [];
        foreach ($media as $mediaId) {
            $changes["{$mediaId}:{$shopId}"] = 'sync';
        }

        return $changes;
    }

    /**
     * Synchronizuje kategorie produktu do PrestaShop.
     * Bierze default categories z PPM, tworzy w PS jesli nie istnieja.
     * Zwraca category_mappings do zapisania w ProductShopData.
     */
    protected function syncCategoriesForExport(Product $product, PrestaShopShop $shop): array
    {
        $categories = $product->categories()->get();

        if ($categories->isEmpty()) {
            return [
                'ui' => ['selected' => [], 'primary' => null],
                'mappings' => [],
                'metadata' => [
                    'last_updated' => now()->format('Y-m-d\TH:i:sP'),
                    'source' => 'sync',
                ],
            ];
        }

        try {
            $categorySyncService = app(CategorySyncService::class);
            $client = PrestaShopClientFactory::create($shop);

            // Sync kazdej kategorii - tworzy w PS jesli nie istnieje
            $psCategoryIds = $categorySyncService->syncProductCategories($categories, $client, $shop);

            // Buduj mappings: PPM ID => PS ID
            $mappings = [];
            $ppmIds = $categories->pluck('id')->values()->toArray();
            foreach ($ppmIds as $index => $ppmId) {
                $mappings[(string) $ppmId] = $psCategoryIds[$index] ?? 0;
            }

            // Filtruj zerowe (nieudane)
            $mappings = array_filter($mappings, fn ($v) => $v > 0);
            $psIds = array_values($mappings);

            Log::info('Matrix: categories synced for export', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'ppm_categories' => $ppmIds,
                'ps_categories' => $psIds,
            ]);

            return [
                'ui' => [
                    'selected' => $ppmIds,
                    'primary' => $categories->last()?->id,
                ],
                'mappings' => $mappings,
                'metadata' => [
                    'last_updated' => now()->format('Y-m-d\TH:i:sP'),
                    'source' => 'sync',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Matrix: category sync failed', [
                'product_id' => $product->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback: pusta struktura
            return [
                'ui' => ['selected' => [], 'primary' => null],
                'mappings' => [],
                'metadata' => [
                    'last_updated' => now()->format('Y-m-d\TH:i:sP'),
                    'source' => 'sync',
                ],
            ];
        }
    }

    // =========================================================================
    // BUG5 FIX: IGNORE / UNIGNORE
    // =========================================================================

    public function ignoreProduct(int $productId, string $sourceType, int $sourceId): void
    {
        ProductScanResult::where('ppm_product_id', $productId)
            ->where('external_source_type', $sourceType)
            ->where('external_source_id', $sourceId)
            ->update([
                'resolution_status' => ProductScanResult::RESOLUTION_IGNORED,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

        // Fallback: szukaj po SKU jesli ppm_product_id NULL
        $product = Product::find($productId);
        if ($product) {
            ProductScanResult::where('sku', $product->sku)
                ->whereNull('ppm_product_id')
                ->where('external_source_type', $sourceType)
                ->where('external_source_id', $sourceId)
                ->update([
                    'resolution_status' => ProductScanResult::RESOLUTION_IGNORED,
                    'ppm_product_id' => $productId,
                    'resolved_at' => now(),
                    'resolved_by' => auth()->id(),
                ]);
        }

        $this->dispatch('cell-updated', productId: $productId, sourceKey: $sourceType . '_' . $sourceId);
        $this->refreshMatrix();
    }

    public function unignoreProduct(int $productId, string $sourceType, int $sourceId): void
    {
        ProductScanResult::where('ppm_product_id', $productId)
            ->where('external_source_type', $sourceType)
            ->where('external_source_id', $sourceId)
            ->where('resolution_status', ProductScanResult::RESOLUTION_IGNORED)
            ->update([
                'resolution_status' => ProductScanResult::RESOLUTION_PENDING,
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

        $this->dispatch('cell-updated', productId: $productId, sourceKey: $sourceType . '_' . $sourceId);
        $this->refreshMatrix();
    }

    // =========================================================================
    // UNLINK / FORCE SYNC
    // =========================================================================

    public function unlinkProduct(int $productId, string $sourceType, int $sourceId): void
    {
        if ($sourceType === 'prestashop') {
            ProductShopData::where('product_id', $productId)->where('shop_id', $sourceId)->delete();
        } else {
            ProductErpData::where('product_id', $productId)->where('erp_connection_id', $sourceId)->delete();
        }

        $this->refreshMatrix();
    }

    public function forceSync(int $productId, string $sourceType, int $sourceId): void
    {
        if ($sourceType === 'prestashop') {
            ProductShopData::where('product_id', $productId)->where('shop_id', $sourceId)
                ->update(['sync_status' => 'pending']);
        } else {
            ProductErpData::where('product_id', $productId)->where('erp_connection_id', $sourceId)
                ->update(['sync_status' => 'pending']);
        }

        $this->refreshMatrix();
    }

    // =========================================================================
    // BUG4 FIX: BULK ACTIONS
    // =========================================================================

    /**
     * Bulk action na zaznaczonych produktach.
     * Obsluguje tryb selectAllMatching (Gmail pattern) - przetwarza WSZYSTKIE pasujace produkty w chunkach.
     *
     * @param string      $action    link_all|export_all|ignore_all|link_source|export_source|ignore_source
     * @param string|null $sourceKey Opcjonalny klucz zrodla (np. 'prestashop_1')
     */
    public function bulkAction(string $action, ?string $sourceKey = null): void
    {
        $effectiveCount = $this->getEffectiveSelectedCount();
        if ($effectiveCount === 0) {
            session()->flash('error', 'Nie zaznaczono zadnych produktow.');
            return;
        }

        $baseAction = str_replace(['_all', '_source'], '', $action);

        // Resolve target sources
        $targetSources = $this->sources;
        if ($sourceKey) {
            $targetSources = array_filter($this->sources, fn ($s) => ($s['type'] . '_' . $s['id']) === $sourceKey);
        }

        $count = 0;

        if ($this->selectAllMatching) {
            // Tryb "wszystkie pasujace" - przetwarzaj w chunkach po 200
            $allIds = array_values(array_diff(
                $this->getAllMatchingProductIds(),
                $this->excludedProducts
            ));

            /** @var CrossSourceMatrixService $service */
            $service = app(CrossSourceMatrixService::class);

            foreach (array_chunk($allIds, 200) as $chunk) {
                $chunkFilters = array_merge($this->getActiveFilters(), ['ids' => $chunk]);
                $chunkData = $service->getQuickMatrixData($chunkFilters, count($chunk));

                foreach ($chunkData as $product) {
                    $count += $this->processProductBulkAction($product, $baseAction, $targetSources);
                }
            }
        } else {
            // Tryb normalny - tylko widoczne zaznaczone produkty
            $matrixData = $this->getMatrixData();
            foreach ($this->selectedProducts as $productId) {
                $product = $matrixData->firstWhere('id', $productId);
                if (!$product) {
                    continue;
                }
                $count += $this->processProductBulkAction($product, $baseAction, $targetSources);
            }
        }

        // Reset selekcji
        $this->clearSelection();

        $actionLabel = match ($baseAction) {
            'link'   => 'powiazanych',
            'export' => 'eksportowanych',
            'ignore' => 'zignorowanych',
            default  => 'przetworzonych',
        };

        session()->flash('success', "Operacja bulk: {$count} komorek {$actionLabel}.");
        $this->refreshMatrix();
    }

    /**
     * Przetwarza bulk action dla jednego produktu we wszystkich target sources.
     */
    private function processProductBulkAction($product, string $baseAction, array $targetSources): int
    {
        $count = 0;

        foreach ($targetSources as $source) {
            $key = $source['type'] . '_' . $source['id'];
            $cellStatus = $product->matrix_cells[$key]['status'] ?? 'unknown';

            $shouldAct = match ($baseAction) {
                'link'   => $cellStatus === CrossSourceMatrixService::CELL_NOT_LINKED,
                'export' => $cellStatus === CrossSourceMatrixService::CELL_NOT_FOUND,
                'ignore' => in_array($cellStatus, [
                    CrossSourceMatrixService::CELL_NOT_LINKED,
                    CrossSourceMatrixService::CELL_NOT_FOUND,
                    CrossSourceMatrixService::CELL_UNKNOWN,
                ]),
                default  => false,
            };

            if ($shouldAct) {
                match ($baseAction) {
                    'link'   => $this->linkProduct($product->id, $source['type'], $source['id']),
                    'export' => $this->publishToSource($product->id, $source['type'], $source['id']),
                    'ignore' => $this->ignoreProduct($product->id, $source['type'], $source['id']),
                    default  => null,
                };
                $count++;
            }
        }

        return $count;
    }

    // =========================================================================
    // BRAND SUGGESTIONS
    // =========================================================================

    protected function addBrandFromProduct(int $productId, int $shopId): void
    {
        $product = Product::with('manufacturerRelation:id,name')->find($productId);
        $brandName = $product?->manufacturerRelation?->name ?? '';

        if (empty($brandName)) {
            session()->flash('error', 'Nie mozna odczytac nazwy producenta.');
            return;
        }

        $this->addBrandToAllowed($brandName, $shopId);
    }

    public function dismissBrandSuggestion(string $brand, int $shopId): void
    {
        DismissedBrandSuggestion::dismiss(auth()->id() ?? 8, $shopId, $brand);
        $this->dispatch('suggestion-dismissed');
    }

    public function restoreBrandSuggestion(string $brand, int $shopId): void
    {
        DismissedBrandSuggestion::restore(auth()->id() ?? 8, $shopId, $brand);
        $this->dispatch('suggestion-restored');
    }

    public function toggleDismissedSuggestions(): void
    {
        $this->showDismissedSuggestions = !$this->showDismissedSuggestions;
    }

    public function addBrandToAllowed(string $brand, int $shopId): void
    {
        SmartSyncBrandRule::create([
            'shop_id' => $shopId, 'brand' => $brand,
            'is_allowed' => true, 'auto_sync' => false,
            'created_by' => auth()->id() ?? 1,
        ]);
        $this->refreshMatrix();
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    public function exportMatrix(string $format = 'xlsx')
    {
        $matrixData = $this->getMatrixData();
        $export = new ScanMatrixExport($matrixData->items(), $this->sources);
        return Excel::download($export, 'matrix-' . now()->format('Y-m-d-His') . '.' . $format);
    }

    // =========================================================================
    // UI HELPERS: POPUP, DIFF, SELECTION
    // =========================================================================

    public function toggleDiffViewer(int $productId, ?string $sourceKey = null): void
    {
        $idx = array_search($productId, $this->expandedDiffs, true);
        if ($idx !== false) {
            array_splice($this->expandedDiffs, $idx, 1);
        } else {
            $this->expandedDiffs[] = $productId;
        }
    }

    public function openPopup(int $productId, string $sourceKey): void
    {
        $this->activePopup = ['productId' => $productId, 'sourceKey' => $sourceKey];
    }

    public function closePopup(): void
    {
        $this->activePopup = null;
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $matrixData = $this->getMatrixData();
            $this->selectedProducts = collect($matrixData->items())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            // Oblicz total matching count (do bannera "Zaznacz wszystkie X pasujacych")
            $this->totalMatchingCount = $this->getTotalMatchingCount();
        } else {
            $this->clearSelection();
        }
    }

    public function updatedSelectedProducts(): void
    {
        if ($this->selectAllMatching) {
            // W trybie selectAllMatching: zarzadzaj lista wykluczonych
            $matrixData = $this->getMatrixData();
            $allVisibleIds = collect($matrixData->items())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->toArray();

            // Widoczne ale niezaznaczone = nowo wykluczone
            $uncheckedVisible = array_values(array_diff($allVisibleIds, $this->selectedProducts));

            // Wykluczone ale zaznaczone ponownie = przywrocone
            $rechecked = array_values(array_intersect($this->selectedProducts, $this->excludedProducts));

            // Aktualizuj liste wykluczonych
            $this->excludedProducts = array_values(array_unique(array_merge(
                array_diff($this->excludedProducts, $rechecked),
                $uncheckedVisible
            )));

            // Aktualizuj header checkbox
            $this->selectAll = count($uncheckedVisible) === 0;

            // Jesli wszystkie widoczne odznaczone - wyjdz z trybu selectAllMatching
            if (empty($this->selectedProducts)) {
                $this->selectAllMatching = false;
                $this->excludedProducts = [];
                $this->totalMatchingCount = 0;
            }
        } else {
            // Tryb normalny
            $matrixData = $this->getMatrixData();
            $totalVisible = $matrixData->count();
            $this->selectAll = $totalVisible > 0 && count($this->selectedProducts) >= $totalVisible;
        }
    }

    /**
     * Wchodzi w tryb "zaznacz wszystkie pasujace" (Gmail pattern).
     * Zaznacza WSZYSTKIE produkty pasujace do aktywnych filtrow.
     */
    public function enableSelectAllMatching(): void
    {
        $this->selectAllMatching = true;
        $this->excludedProducts = [];
        $this->totalMatchingCount = $this->getTotalMatchingCount();

        // Zaznacz widoczne checkboxy
        $matrixData = $this->getMatrixData();
        $this->selectedProducts = collect($matrixData->items())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->selectAll = true;
    }

    /**
     * Czysci cala selekcje (wyjscie z trybu selectAllMatching).
     */
    public function clearSelection(): void
    {
        $this->selectAllMatching = false;
        $this->selectAll = false;
        $this->selectedProducts = [];
        $this->excludedProducts = [];
        $this->totalMatchingCount = 0;
    }

    /**
     * Zwraca efektywna liczbe zaznaczonych produktow.
     * W trybie selectAllMatching: totalMatching - excluded.
     * W trybie normalnym: count(selectedProducts).
     */
    public function getEffectiveSelectedCount(): int
    {
        if ($this->selectAllMatching) {
            return $this->totalMatchingCount - count($this->excludedProducts);
        }

        return count($this->selectedProducts);
    }
}
