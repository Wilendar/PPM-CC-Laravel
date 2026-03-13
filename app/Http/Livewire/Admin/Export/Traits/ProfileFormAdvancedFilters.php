<?php

namespace App\Http\Livewire\Admin\Export\Traits;

use App\Models\BusinessPartner;
use App\Models\ProductType;
use App\Models\Warehouse;
use App\Models\PriceGroup;
use App\Models\ERPConnection;
use App\Models\ExportProfile;

/**
 * ProfileFormAdvancedFilters Trait
 *
 * Advanced filter logic for ExportProfileForm wizard (Step 3).
 * Manages: manufacturer/supplier multiselect, stock status, warehouse, price range,
 * ERP connections, date filters, media filter, compatibility filter.
 *
 * @package App\Http\Livewire\Admin\Export\Traits
 */
trait ProfileFormAdvancedFilters
{
    // Filter properties
    public array $filterManufacturerIds = [];
    public array $filterSupplierIds = [];
    public string $filterProductTypeId = '';
    public string $filterStockStatus = '';      // ''/in_stock/low_stock/out_of_stock
    public array $filterWarehouseIds = [];
    public float $filterPriceMin = 0;
    public float $filterPriceMax = 0;
    public string $filterPriceGroupId = '';
    public array $filterErpConnectionIds = [];
    public string $filterDateFrom = '';
    public string $filterDateTo = '';
    public string $filterDateType = 'created_at';
    public string $filterMediaStatus = '';      // ''/with_images/without_images
    public string $filterHasCompatibility = ''; // ''/with/without
    public bool $showAdvancedFilters = false;

    // Data sources (loaded from DB)
    public array $availableManufacturersList = [];
    public array $availableSuppliersList = [];
    public array $availableProductTypes = [];
    public array $availableWarehousesList = [];
    public array $availablePriceGroupsList = [];
    public array $availableErpConnections = [];

    /**
     * Initialize advanced filter data from DB.
     */
    public function initAdvancedFilters(): void
    {
        $this->availableManufacturersList = BusinessPartner::manufacturers()
            ->active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn($bp) => ['id' => $bp->id, 'name' => $bp->name])
            ->toArray();

        $this->availableSuppliersList = BusinessPartner::suppliers()
            ->active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn($bp) => ['id' => $bp->id, 'name' => $bp->name])
            ->toArray();

        $this->availableProductTypes = ProductType::active()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn($pt) => ['id' => $pt->id, 'name' => $pt->name])
            ->toArray();

        $this->availableWarehousesList = Warehouse::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->map(fn($wh) => ['id' => $wh->id, 'name' => $wh->name, 'code' => $wh->code])
            ->toArray();

        $this->availablePriceGroupsList = PriceGroup::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->map(fn($pg) => ['id' => $pg->id, 'name' => $pg->name, 'code' => $pg->code])
            ->toArray();

        $this->availableErpConnections = ERPConnection::active()
            ->orderBy('instance_name')
            ->get(['id', 'instance_name', 'erp_type'])
            ->map(fn($erp) => [
                'id' => $erp->id,
                'name' => $erp->instance_name,
                'erp_type' => $erp->erp_type,
            ])
            ->toArray();
    }

    /**
     * Build advanced filter config for profile JSON storage.
     *
     * @return array<string, mixed>
     */
    public function getAdvancedFilterConfig(): array
    {
        $config = [];

        if (!empty($this->filterManufacturerIds)) {
            $config['manufacturer_ids'] = array_map('intval', $this->filterManufacturerIds);
        }

        if (!empty($this->filterSupplierIds)) {
            $config['supplier_ids'] = array_map('intval', $this->filterSupplierIds);
        }

        if (!empty($this->filterProductTypeId)) {
            $config['product_type_id'] = (int) $this->filterProductTypeId;
        }

        if (!empty($this->filterStockStatus)) {
            $config['stock_status'] = $this->filterStockStatus;
        }

        if (!empty($this->filterWarehouseIds)) {
            $config['warehouse_ids'] = array_map('intval', $this->filterWarehouseIds);
        }

        if ($this->filterPriceMin > 0) {
            $config['price_min'] = $this->filterPriceMin;
        }

        if ($this->filterPriceMax > 0) {
            $config['price_max'] = $this->filterPriceMax;
        }

        if (!empty($this->filterPriceGroupId)) {
            $config['price_group_id'] = (int) $this->filterPriceGroupId;
        }

        if (!empty($this->filterErpConnectionIds)) {
            $config['erp_connection_ids'] = array_map('intval', $this->filterErpConnectionIds);
        }

        if (!empty($this->filterDateFrom)) {
            $config['date_from'] = $this->filterDateFrom;
        }

        if (!empty($this->filterDateTo)) {
            $config['date_to'] = $this->filterDateTo;
        }

        if ($this->filterDateType !== 'created_at') {
            $config['date_type'] = $this->filterDateType;
        }

        if (!empty($this->filterMediaStatus)) {
            $config['media_filter'] = $this->filterMediaStatus;
        }

        if (!empty($this->filterHasCompatibility)) {
            $config['has_compatibility'] = $this->filterHasCompatibility;
        }

        return $config;
    }

    /**
     * Load advanced filters from existing profile (edit mode).
     */
    public function loadAdvancedFiltersFromProfile(ExportProfile $profile): void
    {
        $config = $profile->filter_config ?? [];

        $this->filterManufacturerIds = array_map('strval', (array) ($config['manufacturer_ids'] ?? []));
        $this->filterSupplierIds = array_map('strval', (array) ($config['supplier_ids'] ?? []));
        $this->filterProductTypeId = (string) ($config['product_type_id'] ?? '');
        $this->filterStockStatus = (string) ($config['stock_status'] ?? '');
        $this->filterWarehouseIds = array_map('strval', (array) ($config['warehouse_ids'] ?? []));
        $this->filterPriceMin = (float) ($config['price_min'] ?? 0);
        $this->filterPriceMax = (float) ($config['price_max'] ?? 0);
        $this->filterPriceGroupId = (string) ($config['price_group_id'] ?? '');
        $this->filterErpConnectionIds = array_map('strval', (array) ($config['erp_connection_ids'] ?? []));
        $this->filterDateFrom = (string) ($config['date_from'] ?? '');
        $this->filterDateTo = (string) ($config['date_to'] ?? '');
        $this->filterDateType = (string) ($config['date_type'] ?? 'created_at');
        $this->filterMediaStatus = (string) ($config['media_filter'] ?? '');
        $this->filterHasCompatibility = (string) ($config['has_compatibility'] ?? '');

        // Auto-show advanced section if any advanced filter is set
        $this->showAdvancedFilters = !empty($this->filterSupplierIds)
            || !empty($this->filterWarehouseIds)
            || $this->filterPriceMin > 0
            || $this->filterPriceMax > 0
            || !empty($this->filterPriceGroupId)
            || !empty($this->filterErpConnectionIds)
            || !empty($this->filterDateFrom)
            || !empty($this->filterDateTo)
            || !empty($this->filterMediaStatus)
            || !empty($this->filterHasCompatibility);
    }

    /**
     * Reset all advanced filters to defaults.
     */
    public function resetAdvancedFilters(): void
    {
        $this->filterManufacturerIds = [];
        $this->filterSupplierIds = [];
        $this->filterProductTypeId = '';
        $this->filterStockStatus = '';
        $this->filterWarehouseIds = [];
        $this->filterPriceMin = 0;
        $this->filterPriceMax = 0;
        $this->filterPriceGroupId = '';
        $this->filterErpConnectionIds = [];
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterDateType = 'created_at';
        $this->filterMediaStatus = '';
        $this->filterHasCompatibility = '';
    }

    /**
     * Get count of active advanced filters (for badge display).
     */
    public function getActiveAdvancedFilterCount(): int
    {
        $count = 0;

        if (!empty($this->filterManufacturerIds)) $count++;
        if (!empty($this->filterSupplierIds)) $count++;
        if (!empty($this->filterProductTypeId)) $count++;
        if (!empty($this->filterStockStatus)) $count++;
        if (!empty($this->filterWarehouseIds)) $count++;
        if ($this->filterPriceMin > 0 || $this->filterPriceMax > 0) $count++;
        if (!empty($this->filterPriceGroupId)) $count++;
        if (!empty($this->filterErpConnectionIds)) $count++;
        if (!empty($this->filterDateFrom) || !empty($this->filterDateTo)) $count++;
        if (!empty($this->filterMediaStatus)) $count++;
        if (!empty($this->filterHasCompatibility)) $count++;

        return $count;
    }
}
