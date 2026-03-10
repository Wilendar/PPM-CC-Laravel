<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Services\Location\LocationSuggestionEngine;

trait ProductFormLocationSuggestions
{
    /**
     * Suggest location based on prefix match (user typing)
     * Called from Alpine.js via $wire
     */
    public function suggestLocationByPrefix(int $warehouseId, string $prefix): array
    {
        if (strlen($prefix) < 2) {
            return [];
        }

        try {
            $engine = app(LocationSuggestionEngine::class);
            return $engine->suggestByPrefix($prefix, $warehouseId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Location prefix suggestion failed', [
                'warehouse_id' => $warehouseId,
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * SMART suggest location based on product category/brand
     * Called from Alpine.js via $wire when input is focused with empty value
     */
    public function suggestLocationSmart(int $warehouseId): ?array
    {
        if (!$this->product || !$this->product->id) {
            return null;
        }

        try {
            $engine = app(LocationSuggestionEngine::class);
            return $engine->suggestSmart($this->product->id, $warehouseId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Location SMART suggestion failed', [
                'product_id' => $this->product->id,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
