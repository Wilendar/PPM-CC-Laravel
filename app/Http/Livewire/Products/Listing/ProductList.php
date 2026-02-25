<?php

namespace App\Http\Livewire\Products\Listing;

use Livewire\Component;
use Livewire\WithPagination;
use App\Http\Livewire\Products\Listing\Traits\ProductListERPImport;
use App\Http\Livewire\Products\Listing\Traits\ProductListFilters;
use App\Http\Livewire\Products\Listing\Traits\ProductListColumns;
use App\Http\Livewire\Products\Listing\Traits\ProductListBulkActions;
use App\Http\Livewire\Products\Listing\Traits\ProductListBulkCategories;
use App\Http\Livewire\Products\Listing\Traits\ProductListPrestaShopImport;
use App\Http\Livewire\Products\Listing\Traits\ProductListPreferences;
use App\Http\Livewire\Products\Listing\Traits\ProductListPresets;
use App\Http\Livewire\Products\Listing\Traits\ProductListQuickActions;

/**
 * ProductList Component - Main product listing interface
 *
 * Traits:
 * - ProductListFilters: Search, filter properties and query building
 * - ProductListColumns: Sorting, display, computed properties, job progress
 * - ProductListBulkActions: Selection, bulk activate/deactivate/delete/export/send
 * - ProductListBulkCategories: Bulk category assign/remove/move
 * - ProductListPrestaShopImport: Import modal, category tree, product search
 * - ProductListPreferences: User preferences persistence
 * - ProductListPresets: Saved filter presets (DB-backed)
 * - ProductListERPImport: ERP import functionality
 * - ProductListQuickActions: Single-product actions, preview, delete, duplicate, polling
 *
 * @package App\Http\Livewire\Products\Listing
 */
class ProductList extends Component
{
    use WithPagination;
    use ProductListFilters;
    use ProductListColumns;
    use ProductListBulkActions;
    use ProductListBulkCategories;
    use ProductListPrestaShopImport;
    use ProductListPreferences;
    use ProductListPresets;
    use ProductListERPImport;
    use ProductListQuickActions;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Computed
    public bool $hasFilters = false;

    /*
    |--------------------------------------------------------------------------
    | COMPONENT LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->loadUserPreferences();
        $this->loadDefaultPresetOnMount();
        $this->updateHasFilters();
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.products.listing.product-list', [
            'products' => $this->products,
            'categories' => $this->categories,
        ])->layout('layouts.admin', [
            'title' => 'Lista produktów - PPM',
            'breadcrumb' => 'Lista produktów'
        ]);
    }
}
