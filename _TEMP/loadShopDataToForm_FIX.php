    /**
     * Load shop data to form fields with inheritance from defaults
     *
     * BUGFIX 2025-11-06: Priority - loadedShopData (from PrestaShop API) > shopData (from DB) > defaultData
     * - If loadedShopData exists (user clicked "Pobierz dane z PrestaShop"), use it
     * - Otherwise fall back to shopData (from DB)
     * - Finally fall back to defaultData (product defaults)
     */
    private function loadShopDataToForm(int $shopId): void
    {
        Log::info('loadShopDataToForm CALLED', [
            'shop_id' => $shopId,
            'product_id' => $this->product?->id,
            'has_loadedShopData' => isset($this->loadedShopData[$shopId]),
            'has_shopData' => isset($this->shopData[$shopId]),
            'has_defaultData' => !empty($this->defaultData),
        ]);

        // === PRIORITY: loadedShopData (from PrestaShop API) > shopData (from DB) > defaultData ===
        $prestaShopData = $this->loadedShopData[$shopId] ?? null;

        if ($prestaShopData) {
            Log::info('loadShopDataToForm: Using loadedShopData (from PrestaShop API)', [
                'shop_id' => $shopId,
                'has_name' => isset($prestaShopData['name']),
                'has_description_short' => isset($prestaShopData['description_short']),
                'has_link_rewrite' => isset($prestaShopData['link_rewrite']),
            ]);

            // Load from PrestaShop API data (loadedShopData)
            $this->name = $prestaShopData['name'] ?? $this->getShopValue($shopId, 'name') ?? $this->name;
            $this->slug = $prestaShopData['link_rewrite'] ?? $this->getShopValue($shopId, 'slug') ?? $this->slug;
            $this->short_description = $prestaShopData['description_short'] ?? $this->getShopValue($shopId, 'short_description') ?? $this->short_description;
            $this->long_description = $prestaShopData['description'] ?? $this->getShopValue($shopId, 'long_description') ?? $this->long_description;
            $this->weight = $prestaShopData['weight'] ?? $this->getShopValue($shopId, 'weight') ?: $this->weight;
            $this->ean = $prestaShopData['ean13'] ?? $this->getShopValue($shopId, 'ean') ?? $this->ean;
            $this->is_active = $prestaShopData['active'] ?? $this->getShopValue($shopId, 'is_active') ?? $this->is_active;

            Log::info('loadShopDataToForm: Loaded from PrestaShop API', [
                'shop_id' => $shopId,
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        } else {
            Log::info('loadShopDataToForm: Using shopData/defaultData (no PrestaShop data loaded)', [
                'shop_id' => $shopId,
            ]);

            // Fall back to existing logic (shopData > defaultData)
            // === BASIC INFORMATION ===
            $this->sku = $this->getShopValue($shopId, 'sku') ?? $this->sku;
            $this->name = $this->getShopValue($shopId, 'name') ?? $this->name;
            $this->slug = $this->getShopValue($shopId, 'slug') ?? $this->slug;
            $this->product_type_id = $this->getShopValue($shopId, 'product_type_id') ?: $this->product_type_id;
            $this->manufacturer = $this->getShopValue($shopId, 'manufacturer') ?? $this->manufacturer;
            $this->supplier_code = $this->getShopValue($shopId, 'supplier_code') ?? $this->supplier_code;
            $this->ean = $this->getShopValue($shopId, 'ean') ?? $this->ean;

            // === DESCRIPTIONS & SEO ===
            $this->short_description = $this->getShopValue($shopId, 'short_description') ?? $this->short_description;
            $this->long_description = $this->getShopValue($shopId, 'long_description') ?? $this->long_description;
            $this->meta_title = $this->getShopValue($shopId, 'meta_title') ?? $this->meta_title;
            $this->meta_description = $this->getShopValue($shopId, 'meta_description') ?? $this->meta_description;

            // === PHYSICAL PROPERTIES ===
            $this->weight = $this->getShopValue($shopId, 'weight') ?: $this->weight;
            $this->height = $this->getShopValue($shopId, 'height') ?: $this->height;
            $this->width = $this->getShopValue($shopId, 'width') ?: $this->width;
            $this->length = $this->getShopValue($shopId, 'length') ?: $this->length;
            $this->tax_rate = $this->getShopValue($shopId, 'tax_rate') ?: $this->tax_rate;

            // === STATUS & SETTINGS ===
            $this->is_active = $this->getShopValue($shopId, 'is_active') ?? $this->is_active;
            $this->is_variant_master = $this->getShopValue($shopId, 'is_variant_master') ?? $this->is_variant_master;
            $this->is_featured = $this->getShopValue($shopId, 'is_featured') ?? $this->is_featured;
            $this->sort_order = $this->getShopValue($shopId, 'sort_order') ?: $this->sort_order;
        }

        // === CATEGORIES ===
        // REMOVED 2025-10-13: loadShopCategories() uses OLD architecture (ProductShopCategory table)
        // Categories are now loaded by ProductCategoryManager during mount() using NEW architecture (shop_id in pivot)
        // $this->loadShopCategories($shopId);

        // Force update of computed properties for UI reactivity
        $this->updateCategoryColorCoding();

        Log::info('loadShopDataToForm COMPLETED', [
            'shop_id' => $shopId,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description_length' => strlen($this->short_description ?? ''),
        ]);
    }
