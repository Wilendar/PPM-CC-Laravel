<?php

namespace App\Http\Livewire\Products\Listing\Traits;

/**
 * ProductListPreferences Trait
 *
 * Manages user preferences persistence for ProductList:
 * - Per page setting
 * - View mode (table/grid)
 * - Sort column and direction
 *
 * @package App\Http\Livewire\Products\Listing\Traits
 */
trait ProductListPreferences
{
    /*
    |--------------------------------------------------------------------------
    | USER PREFERENCES
    |--------------------------------------------------------------------------
    */

    private function loadUserPreferences(): void
    {
        if (session()->has('product_list_preferences')) {
            $preferences = session('product_list_preferences');

            $this->perPage = $preferences['per_page'] ?? 25;
            $this->viewMode = $preferences['view_mode'] ?? 'table';
            $this->sortBy = $preferences['sort_by'] ?? 'updated_at';
            $this->sortDirection = $preferences['sort_direction'] ?? 'desc';
            $this->priceDisplayMode = $preferences['price_display_mode'] ?? 'netto';
        }
    }

    private function saveUserPreferences(): void
    {
        $preferences = [
            'per_page' => $this->perPage,
            'view_mode' => $this->viewMode,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'price_display_mode' => $this->priceDisplayMode,
        ];

        session(['product_list_preferences' => $preferences]);
    }
}
