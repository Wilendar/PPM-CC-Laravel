<?php

namespace App\Services\Scan;

use App\Models\ERPConnection;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductScanResult;
use App\Models\ProductShopData;
use App\Models\SmartSyncBrandRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CrossSourceMatrixService
 *
 * Buduje dane macierzy Cross-Source: status każdego produktu we wszystkich
 * źródłach naraz (PrestaShop shops + ERP connections).
 * Quick Matrix ładuje dane bez skanowania - tylko LEFT JOINs.
 */
class CrossSourceMatrixService
{
    public const CELL_LINKED            = 'linked';
    public const CELL_NOT_LINKED        = 'not_linked';
    public const CELL_NOT_FOUND         = 'not_found';
    public const CELL_UNKNOWN           = 'unknown';
    public const CELL_IGNORED           = 'ignored';
    public const CELL_PENDING_SYNC      = 'pending_sync';
    public const CELL_BRAND_NOT_ALLOWED = 'brand_not_allowed';
    public const CELL_CONFLICT          = 'conflict';

    /** @deprecated Use CELL_NOT_LINKED or CELL_NOT_FOUND */
    public const CELL_MISSING           = 'unknown';

    /**
     * Zwraca listę wszystkich aktywnych źródeł (PrestaShop + ERP).
     *
     * @return array<int, array{type: string, id: int, name: string, icon: string, color: string, is_shop: bool}>
     */
    public function getAvailableSources(): array
    {
        $sources = [];

        foreach (PrestaShopShop::active()->orderBy('id')->get() as $shop) {
            $label = $shop->getLabelDataAttribute();
            $sources[] = [
                'type' => 'prestashop', 'id' => $shop->id, 'name' => $shop->name,
                'icon' => $label['icon'] ?? 'shopping-cart', 'color' => $label['color'] ?? '#06b6d4',
                'is_shop' => true,
            ];
        }

        foreach (ERPConnection::active()->orderedByPriority()->get() as $erp) {
            $label = $erp->getLabelDataAttribute();
            $sources[] = [
                'type' => $erp->erp_type, 'id' => $erp->id, 'name' => $erp->instance_name,
                'icon' => $label['icon'] ?? 'database', 'color' => $label['color'] ?? '#ea580c',
                'is_shop' => false,
            ];
        }

        return $sources;
    }

    /**
     * Quick Matrix - dane bez skanowania (LEFT JOINs).
     *
     * @param  array{search?: string, status?: string, brand?: string, manufacturer_id?: int|null} $filters
     * @param  int                                                                                  $perPage
     * @return LengthAwarePaginator (każdy produkt ma ->matrix_cells[key] => [status, external_id, sync_status])
     */
    public function getQuickMatrixData(array $filters = [], int $perPage = 100): LengthAwarePaginator
    {
        $sources = $this->getAvailableSources();

        $query = Product::whereNotNull('sku')->where('sku', '!=', '')
            ->with(['manufacturerRelation:id,name']);

        $this->applyFilters($query, $filters, $sources);

        $paginator    = $query->paginate($perPage);
        $productIds   = $paginator->pluck('id')->toArray();
        $shopLinks    = $this->loadLinks($productIds, $sources, true);
        $erpLinks     = $this->loadLinks($productIds, $sources, false);
        $scanResults  = $this->loadScanResults($productIds);

        foreach ($paginator as $product) {
            $cells = [];
            foreach ($sources as $s) {
                $key  = $s['type'] . '_' . $s['id'];
                $pool = $s['is_shop'] ? $shopLinks : $erpLinks;

                if (isset($pool[$product->id][$s['id']])) {
                    // Ma link do zrodla
                    $cells[$key] = $pool[$product->id][$s['id']];
                } else {
                    // Brak linka - sprawdz wyniki skanowania
                    $scanKey    = $s['type'] . '_' . $s['id'];
                    $scanStatus = $scanResults[$product->id][$scanKey] ?? null;

                    $cells[$key] = [
                        'status'      => $this->resolveUnlinkedStatus($scanStatus),
                        'external_id' => $scanStatus['external_id'] ?? null,
                        'sync_status' => null,
                    ];
                }
            }
            $product->matrix_cells = $cells;
        }

        return $paginator;
    }

    /**
     * Rozwiązuje status jednej komórki macierzy dla produktu + źródła.
     *
     * @param  Product    $product
     * @param  string     $sourceType
     * @param  int        $sourceId
     * @param  array|null $brandRulesCache [shopId => [brand => is_allowed]] – cache w pamięci requestu
     * @return array{status: string, external_id: string|null, sync_status: string|null}
     */
    public function resolveCell(
        Product $product,
        string $sourceType,
        int $sourceId,
        ?array $brandRulesCache = null
    ): array {
        if ($sourceType === 'prestashop') {
            $brand = $product->manufacturerRelation?->name;
            if ($brand) {
                $allowed = $brandRulesCache !== null
                    ? ($brandRulesCache[$sourceId][$brand] ?? null)
                    : SmartSyncBrandRule::forShop($sourceId)->where('brand', $brand)->value('is_allowed');

                if ($allowed === false) {
                    return ['status' => self::CELL_BRAND_NOT_ALLOWED, 'external_id' => null, 'sync_status' => null];
                }
            }

            $row = $product->shopData()->where('shop_id', $sourceId)->first();
            if (!$row) {
                return ['status' => self::CELL_MISSING, 'external_id' => null, 'sync_status' => null];
            }

            return [
                'status'      => $this->resolveSyncStatus($row->sync_status),
                'external_id' => (string) ($row->prestashop_product_id ?? ''),
                'sync_status' => $row->sync_status,
            ];
        }

        $row = $product->erpData()->where('erp_connection_id', $sourceId)->first();
        if (!$row) {
            return ['status' => self::CELL_MISSING, 'external_id' => null, 'sync_status' => null];
        }

        return [
            'status'      => $this->resolveSyncStatus($row->sync_status),
            'external_id' => $row->external_id,
            'sync_status' => $row->sync_status,
        ];
    }

    /**
     * Liczy statystyki ze wszystkich komórek macierzy.
     *
     * @param  Collection $matrixData Kolekcja produktów z ->matrix_cells
     * @param  array      $sources    Wynik getAvailableSources()
     * @return array{total: int, linked: int, missing: int, conflicts: int, brand_blocked: int, pending_sync: int}
     */
    public function getSummaryStats(Collection $matrixData, array $sources): array
    {
        $stats = [
            'total' => 0, 'linked' => 0, 'not_linked' => 0, 'not_found' => 0,
            'unknown' => 0, 'ignored' => 0, 'conflicts' => 0, 'brand_blocked' => 0, 'pending_sync' => 0,
        ];

        foreach ($matrixData as $product) {
            foreach ($product->matrix_cells ?? [] as $cell) {
                $stats['total']++;
                match ($cell['status']) {
                    self::CELL_LINKED            => $stats['linked']++,
                    self::CELL_NOT_LINKED        => $stats['not_linked']++,
                    self::CELL_NOT_FOUND         => $stats['not_found']++,
                    self::CELL_UNKNOWN           => $stats['unknown']++,
                    self::CELL_IGNORED           => $stats['ignored']++,
                    self::CELL_CONFLICT          => $stats['conflicts']++,
                    self::CELL_BRAND_NOT_ALLOWED => $stats['brand_blocked']++,
                    self::CELL_PENDING_SYNC      => $stats['pending_sync']++,
                    default                      => $stats['unknown']++,
                };
            }
        }

        return $stats;
    }

    /**
     * Marki z >=minProducts produktów w PPM bez linku do $shopId
     * i bez istniejącej reguły SmartSyncBrandRule.
     *
     * @param  int $shopId
     * @param  int $minProducts
     * @return Collection
     */
    public function getBrandSuggestions(int $shopId, int $minProducts = 5): Collection
    {
        $brands = Product::select('manufacturer_id', DB::raw('COUNT(*) as product_count'))
            ->whereNotNull('sku')->where('sku', '!=', '')
            ->whereNotNull('manufacturer_id')
            ->whereDoesntHave('shopData', fn (Builder $q) => $q->where('shop_id', $shopId))
            ->groupBy('manufacturer_id')
            ->having('product_count', '>=', $minProducts)
            ->with('manufacturerRelation:id,name')
            ->get();

        $existingBrands = SmartSyncBrandRule::forShop($shopId)->pluck('brand')->toArray();

        return $brands->reject(
            fn ($item) => in_array($item->manufacturerRelation?->name, $existingBrands, true)
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Rozwiazuje status dla produktu BEZ linka na podstawie wynikow skanowania.
     *
     * @param  array|null $scanResult Wynik skanowania [match_status, external_id]
     * @return string Status komorki
     */
    private function resolveUnlinkedStatus(?array $scanResult): string
    {
        if ($scanResult === null) {
            return self::CELL_UNKNOWN;
        }

        // Sprawdz czy jest zignorowany (resolution_status)
        if (($scanResult['resolution_status'] ?? '') === ProductScanResult::RESOLUTION_IGNORED) {
            return self::CELL_IGNORED;
        }

        return match ($scanResult['match_status']) {
            ProductScanResult::MATCH_MATCHED,
            ProductScanResult::MATCH_CONFLICT,
            ProductScanResult::MATCH_MULTIPLE,
            ProductScanResult::MATCH_ALREADY_LINKED => self::CELL_NOT_LINKED,
            ProductScanResult::MATCH_UNMATCHED      => self::CELL_NOT_FOUND,
            default                                 => self::CELL_UNKNOWN,
        };
    }

    /**
     * Laduje najnowsze wyniki skanowania per produkt + zrodlo (batch).
     * Szuka po ppm_product_id LUB po SKU (stary system zapisywal z NULL ppm_product_id).
     *
     * @param  int[] $productIds
     * @return array<int, array<string, array{match_status: string, external_id: string|null}>>
     */
    private function loadScanResults(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        // Mapa SKU -> product_id dla fallback lookup
        $skuMap = Product::whereIn('id', $productIds)
            ->whereNotNull('sku')
            ->pluck('id', 'sku')
            ->toArray();

        $skus = array_keys($skuMap);

        // Pobierz wyniki po ppm_product_id LUB po SKU
        $rows = ProductScanResult::where(function ($q) use ($productIds, $skus) {
                $q->whereIn('ppm_product_id', $productIds)
                  ->orWhereIn('sku', $skus);
            })
            ->orderByDesc('id')
            ->get(['ppm_product_id', 'sku', 'external_source_type', 'external_source_id', 'match_status', 'external_id', 'resolution_status']);

        $result = [];
        $seen   = [];

        foreach ($rows as $row) {
            // Resolve product ID: z ppm_product_id lub z SKU map
            $productId = $row->ppm_product_id;
            if (!$productId && $row->sku && isset($skuMap[$row->sku])) {
                $productId = $skuMap[$row->sku];
            }

            if (!$productId || !in_array($productId, $productIds)) {
                continue;
            }

            $sourceKey = $row->external_source_type . '_' . $row->external_source_id;
            $dedupeKey = $productId . '_' . $sourceKey;

            // Tylko najnowszy wynik per produkt+zrodlo
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $result[$productId][$sourceKey] = [
                'match_status'      => $row->match_status,
                'external_id'       => $row->external_id,
                'resolution_status' => $row->resolution_status,
            ];
        }

        return $result;
    }

    private function resolveSyncStatus(string $syncStatus): string
    {
        return in_array($syncStatus, [
            ProductShopData::STATUS_PENDING,
            ProductShopData::STATUS_SYNCING,
        ], true) ? self::CELL_PENDING_SYNC : self::CELL_LINKED;
    }

    private function applyFilters(Builder $query, array $filters, array $sources): void
    {
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn (Builder $q) =>
                $q->where('sku', 'LIKE', $term)->orWhere('name', 'LIKE', $term)
            );
        }

        if (!empty($filters['manufacturer_id'])) {
            $query->where('manufacturer_id', (int) $filters['manufacturer_id']);
        } elseif (!empty($filters['brand'])) {
            $query->whereHas('manufacturerRelation', fn (Builder $q) =>
                $q->where('name', 'LIKE', '%' . $filters['brand'] . '%')
            );
        }

        // Filtr statusu - filtruje produkty BEZ linka do jakiegokolwiek zrodla
        $statusFilter = $filters['status'] ?? '';
        if (in_array($statusFilter, ['not_linked', 'not_found', 'unknown', 'missing'])) {
            $query->where(function (Builder $q) use ($sources) {
                foreach ($sources as $s) {
                    if ($s['is_shop']) {
                        $q->orWhereDoesntHave('shopData', fn (Builder $sq) => $sq->where('shop_id', $s['id']));
                    } else {
                        $q->orWhereDoesntHave('erpData', fn (Builder $eq) => $eq->where('erp_connection_id', $s['id']));
                    }
                }
            });
        }

        // Sortowanie
        $sortField     = $filters['sort_field'] ?? 'sku';
        $sortDirection = in_array($filters['sort_direction'] ?? 'asc', ['asc', 'desc']) ? $filters['sort_direction'] : 'asc';

        $sortColumn = match ($sortField) {
            'name'         => 'name',
            'manufacturer' => 'manufacturer_id',
            default        => 'sku',
        };

        $query->orderBy($sortColumn, $sortDirection);
    }

    /**
     * @param  int[]  $productIds
     * @param  array  $sources
     * @param  bool   $isShop  true = ładuje ProductShopData, false = ProductErpData
     * @return array<int, array<int, array{status: string, external_id: string|null, sync_status: string|null}>>
     */
    private function loadLinks(array $productIds, array $sources, bool $isShop): array
    {
        if (empty($productIds)) {
            return [];
        }

        $ids = array_values(array_map(
            fn ($s) => $s['id'],
            array_filter($sources, fn ($s) => $s['is_shop'] === $isShop)
        ));

        if (empty($ids)) {
            return [];
        }

        $result = [];

        if ($isShop) {
            $rows = ProductShopData::whereIn('product_id', $productIds)
                ->whereIn('shop_id', $ids)
                ->get(['product_id', 'shop_id', 'prestashop_product_id', 'sync_status']);

            foreach ($rows as $row) {
                $result[$row->product_id][$row->shop_id] = [
                    'status'      => $this->resolveSyncStatus($row->sync_status),
                    'external_id' => (string) ($row->prestashop_product_id ?? ''),
                    'sync_status' => $row->sync_status,
                ];
            }
        } else {
            $rows = ProductErpData::whereIn('product_id', $productIds)
                ->whereIn('erp_connection_id', $ids)
                ->get(['product_id', 'erp_connection_id', 'external_id', 'sync_status']);

            foreach ($rows as $row) {
                $result[$row->product_id][$row->erp_connection_id] = [
                    'status'      => $this->resolveSyncStatus($row->sync_status),
                    'external_id' => $row->external_id,
                    'sync_status' => $row->sync_status,
                ];
            }
        }

        return $result;
    }
}
