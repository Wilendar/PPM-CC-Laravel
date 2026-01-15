<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\FeatureType;
use App\Models\FeatureGroup;
use App\Models\FeatureTemplate;
use App\Models\ProductFeature;
use Illuminate\Support\Facades\Log;

/**
 * ProductFormFeatures Trait
 *
 * ETAP_07e FAZA 3 - Manages product features (technical specifications) in ProductForm
 *
 * Features:
 * - Load/save product features with ProductForm
 * - Add/remove individual features
 * - Apply feature templates
 * - Integration with PrestaShop sync (via save)
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since 2025-12-02
 */
trait ProductFormFeatures
{
    /*
    |--------------------------------------------------------------------------
    | FEATURE PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Product features array (current working set)
     * Format: [['feature_type_id' => int, 'value' => mixed], ...]
     *
     * @var array
     */
    public array $productFeatures = [];

    /**
     * Default product features snapshot (from PPM database at mount)
     * Used for comparison when in shop context
     * Format: [feature_type_id => value]
     *
     * @var array
     */
    public array $defaultProductFeatures = [];

    /**
     * Shop product features loaded from PrestaShop per shop
     * Format: [shopId => [feature_type_id => value, ...]]
     *
     * @var array
     */
    public array $shopProductFeatures = [];

    /*
    |--------------------------------------------------------------------------
    | FEATURE LOADING
    |--------------------------------------------------------------------------
    */

    /**
     * Load product features from database
     * Called during mount() for edit mode
     *
     * Also stores snapshot to defaultProductFeatures for comparison system
     */
    public function loadProductFeatures(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->productFeatures = [];
            $this->defaultProductFeatures = [];
            return;
        }

        try {
            $features = ProductFeature::where('product_id', $this->product->id)
                ->with('featureType')
                ->get();

            $this->productFeatures = $features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'feature_type_id' => $feature->feature_type_id,
                    'value' => $feature->custom_value ?? $feature->feature_value_id,
                ];
            })->toArray();

            // Store snapshot for comparison (indexed by feature_type_id for quick lookup)
            $this->defaultProductFeatures = [];
            foreach ($this->productFeatures as $feature) {
                $this->defaultProductFeatures[$feature['feature_type_id']] = $feature['value'];
            }

            Log::debug('ProductFormFeatures::loadProductFeatures', [
                'product_id' => $this->product->id,
                'features_count' => count($this->productFeatures),
                'default_snapshot_count' => count($this->defaultProductFeatures),
            ]);
        } catch (\Exception $e) {
            Log::error('ProductFormFeatures::loadProductFeatures FAILED', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            $this->productFeatures = [];
            $this->defaultProductFeatures = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE MANIPULATION
    |--------------------------------------------------------------------------
    */

    /**
     * Add a feature to the product
     *
     * @param int $featureTypeId
     */
    public function addProductFeature(int $featureTypeId): void
    {
        // Check if already added
        $alreadyExists = collect($this->productFeatures)->contains('feature_type_id', $featureTypeId);
        if ($alreadyExists) {
            $this->dispatch('notify', type: 'warning', message: 'Ta cecha jest juz dodana do produktu.');
            return;
        }

        $featureType = FeatureType::find($featureTypeId);
        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono typu cechy.');
            return;
        }

        // Add new feature with empty value
        $this->productFeatures[] = [
            'id' => null, // Will be set on save
            'feature_type_id' => $featureTypeId,
            'value' => $featureType->value_type === 'bool' ? false : null,
        ];

        // Mark as unsaved
        $this->hasUnsavedChanges = true;

        Log::debug('ProductFormFeatures::addProductFeature', [
            'feature_type_id' => $featureTypeId,
            'feature_name' => $featureType->name,
            'total_features' => count($this->productFeatures),
        ]);

        $this->dispatch('notify', type: 'success', message: "Dodano ceche: {$featureType->name}");
    }

    /**
     * Remove a feature from the product
     *
     * @param int $featureTypeId
     */
    public function removeProductFeature(int $featureTypeId): void
    {
        $this->productFeatures = collect($this->productFeatures)
            ->reject(fn($f) => $f['feature_type_id'] == $featureTypeId)
            ->values()
            ->toArray();

        // Mark as unsaved
        $this->hasUnsavedChanges = true;

        Log::debug('ProductFormFeatures::removeProductFeature', [
            'feature_type_id' => $featureTypeId,
            'remaining_features' => count($this->productFeatures),
        ]);

        $this->dispatch('notify', type: 'info', message: 'Usunieto ceche.');
    }

    /**
     * Clear all product features
     */
    public function clearAllProductFeatures(): void
    {
        $count = count($this->productFeatures);
        $this->productFeatures = [];

        // Mark as unsaved
        $this->hasUnsavedChanges = true;

        Log::debug('ProductFormFeatures::clearAllProductFeatures', [
            'cleared_count' => $count,
        ]);

        $this->dispatch('notify', type: 'info', message: "Usunieto {$count} cech.");
    }

    /**
     * Apply a feature template to the product
     *
     * Template stores features in JSON column 'features' with structure:
     * [{ feature_type_id: int, code: string, name: string, type: string, ... }, ...]
     *
     * @param int $templateId
     */
    public function applyFeatureTemplate(int $templateId): void
    {
        $template = FeatureTemplate::find($templateId);
        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono szablonu.');
            return;
        }

        // Template stores features in JSON column 'features'
        $templateFeatures = $template->features ?? [];
        if (empty($templateFeatures)) {
            $this->dispatch('notify', type: 'warning', message: 'Szablon nie zawiera zadnych cech.');
            return;
        }

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($templateFeatures as $templateFeature) {
            $featureTypeId = $templateFeature['feature_type_id'] ?? null;
            if (!$featureTypeId) {
                continue;
            }

            // Check if already added
            $alreadyExists = collect($this->productFeatures)->contains('feature_type_id', $featureTypeId);

            if (!$alreadyExists) {
                // Get feature type for value_type info
                $featureType = FeatureType::find($featureTypeId);
                $valueType = $featureType->value_type ?? $templateFeature['type'] ?? 'text';

                $this->productFeatures[] = [
                    'id' => null,
                    'feature_type_id' => $featureTypeId,
                    'value' => $valueType === 'bool' ? false : ($templateFeature['default'] ?? null),
                ];
                $addedCount++;
            } else {
                $skippedCount++;
            }
        }

        // Mark as unsaved
        $this->hasUnsavedChanges = true;

        Log::debug('ProductFormFeatures::applyFeatureTemplate', [
            'template_id' => $templateId,
            'template_name' => $template->name,
            'template_features_count' => count($templateFeatures),
            'added' => $addedCount,
            'skipped' => $skippedCount,
        ]);

        $message = "Szablon \"{$template->name}\" zastosowany: dodano {$addedCount} cech";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} pominieto - juz istnieja)";
        }

        $this->dispatch('notify', type: 'success', message: $message);
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE SAVING
    |--------------------------------------------------------------------------
    */

    /**
     * Save product features to database
     * Called during save() after product is saved
     *
     * @return bool Success status
     */
    public function saveProductFeatures(): bool
    {
        if (!$this->product || !$this->product->id) {
            Log::warning('ProductFormFeatures::saveProductFeatures - No product to save features for');
            return false;
        }

        try {
            // Get existing feature IDs
            $existingFeatureIds = ProductFeature::where('product_id', $this->product->id)
                ->pluck('id', 'feature_type_id')
                ->toArray();

            $currentFeatureTypeIds = collect($this->productFeatures)->pluck('feature_type_id')->toArray();

            // Delete removed features
            $toDelete = array_diff(array_keys($existingFeatureIds), $currentFeatureTypeIds);
            if (!empty($toDelete)) {
                ProductFeature::where('product_id', $this->product->id)
                    ->whereIn('feature_type_id', $toDelete)
                    ->delete();

                Log::debug('ProductFormFeatures::saveProductFeatures - Deleted features', [
                    'deleted_count' => count($toDelete),
                    'feature_type_ids' => $toDelete,
                ]);
            }

            // Update or create features
            foreach ($this->productFeatures as $feature) {
                $featureType = FeatureType::find($feature['feature_type_id']);
                if (!$featureType) {
                    continue;
                }

                $data = [
                    'product_id' => $this->product->id,
                    'feature_type_id' => $feature['feature_type_id'],
                ];

                // Determine if value is feature_value_id (select) or custom_value
                if ($featureType->value_type === 'select' && is_numeric($feature['value'])) {
                    $data['feature_value_id'] = $feature['value'];
                    $data['custom_value'] = null;
                } else {
                    $data['feature_value_id'] = null;
                    $data['custom_value'] = $feature['value'];
                }

                ProductFeature::updateOrCreate(
                    [
                        'product_id' => $this->product->id,
                        'feature_type_id' => $feature['feature_type_id'],
                    ],
                    $data
                );
            }

            Log::info('ProductFormFeatures::saveProductFeatures SUCCESS', [
                'product_id' => $this->product->id,
                'features_count' => count($this->productFeatures),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('ProductFormFeatures::saveProductFeatures FAILED', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Save product features to shop-specific storage (attribute_mappings in ProductShopData)
     *
     * FIX 2025-12-03: Per-shop features - OPCJA B
     * Each shop can have its own feature values independent from PPM defaults
     *
     * Storage: ProductShopData.attribute_mappings JSON field
     * Format: { "features": { feature_type_id: value, ... } }
     *
     * @param int $shopId Shop ID to save features for
     * @return bool Success status
     */
    public function saveShopFeatures(int $shopId): bool
    {
        if (!$this->product || !$this->product->id) {
            Log::warning('ProductFormFeatures::saveShopFeatures - No product');
            return false;
        }

        try {
            // Get or create shop data
            $shopData = $this->product->shopData()->where('shop_id', $shopId)->first();

            if (!$shopData) {
                Log::warning('ProductFormFeatures::saveShopFeatures - No shop data', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return false;
            }

            // Build features array from current productFeatures
            // Format: [feature_type_id => value, ...]
            $featuresData = [];
            foreach ($this->productFeatures as $feature) {
                $featuresData[$feature['feature_type_id']] = $feature['value'];
            }

            // Get current attribute_mappings or create empty structure
            $attributeMappings = $shopData->attribute_mappings ?? [];

            // Store features in attribute_mappings.features
            $attributeMappings['features'] = $featuresData;
            $attributeMappings['features_updated_at'] = now()->toIso8601String();

            // Save to database
            $shopData->update([
                'attribute_mappings' => $attributeMappings,
            ]);

            // Update local cache for UI
            $this->shopProductFeatures[$shopId] = $featuresData;

            Log::info('ProductFormFeatures::saveShopFeatures SUCCESS', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'features_count' => count($featuresData),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('ProductFormFeatures::saveShopFeatures FAILED', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Load shop-specific features from attribute_mappings
     *
     * FIX 2025-12-03: Per-shop features - OPCJA B
     * Load features from ProductShopData.attribute_mappings if available
     *
     * @param int $shopId
     * @return array [feature_type_id => value, ...]
     */
    public function loadShopFeaturesFromStorage(int $shopId): array
    {
        if (!$this->product || !$this->product->id) {
            return [];
        }

        try {
            $shopData = $this->product->shopData()->where('shop_id', $shopId)->first();

            if (!$shopData) {
                return [];
            }

            $attributeMappings = $shopData->attribute_mappings ?? [];
            $features = $attributeMappings['features'] ?? [];

            Log::debug('ProductFormFeatures::loadShopFeaturesFromStorage', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'features_count' => count($features),
            ]);

            return $features;

        } catch (\Exception $e) {
            Log::error('ProductFormFeatures::loadShopFeaturesFromStorage FAILED', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP SYNC INTEGRATION
    |--------------------------------------------------------------------------
    */

    /**
     * Get features data prepared for PrestaShop sync
     * Used by SyncProductToPrestaShop job
     *
     * FIX 2025-12-03: Per-shop features - OPCJA B
     * Prioritizes shop-specific features from attribute_mappings over global product_features
     *
     * @param int $shopId
     * @return array Features with PrestaShop mapping info
     */
    public function getProductFeaturesForPrestaShop(int $shopId): array
    {
        if (empty($this->productFeatures)) {
            return [];
        }

        $featuresForPs = [];

        foreach ($this->productFeatures as $feature) {
            $featureType = FeatureType::with('prestashopMappings')->find($feature['feature_type_id']);
            if (!$featureType) {
                continue;
            }

            // Get PrestaShop mapping for this shop
            $mapping = $featureType->getPrestashopMapping($shopId);
            if (!$mapping) {
                // No mapping - skip this feature for this shop
                Log::debug('ProductFormFeatures::getProductFeaturesForPrestaShop - No mapping', [
                    'feature_type_id' => $feature['feature_type_id'],
                    'feature_name' => $featureType->name,
                    'shop_id' => $shopId,
                ]);
                continue;
            }

            $featuresForPs[] = [
                'ppm_feature_type_id' => $featureType->id,
                'ppm_feature_name' => $featureType->name,
                'prestashop_feature_id' => $mapping->prestashop_feature_id,
                'value' => $feature['value'],
                'value_type' => $featureType->value_type,
                'unit' => $featureType->unit,
            ];
        }

        Log::debug('ProductFormFeatures::getProductFeaturesForPrestaShop', [
            'shop_id' => $shopId,
            'total_features' => count($this->productFeatures),
            'mapped_features' => count($featuresForPs),
        ]);

        return $featuresForPs;
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE COMPARISON SYSTEM (Default vs Shop)
    |--------------------------------------------------------------------------
    | Provides status comparison between PPM default features and PrestaShop
    | shop features. Mirrors the existing field comparison system used in
    | basic-tab, descriptions-tab, etc.
    */

    /**
     * Get feature status for comparison (Default vs Shop context)
     *
     * Status values:
     * - 'default': We are in default mode (activeShopId === null)
     * - 'inherited': Feature value equals default (will use PPM default on sync)
     * - 'same': Alias for inherited - value matches PPM default
     * - 'different': Feature value differs from PPM default
     *
     * FAZA 5.1 FIX: Compare current value vs default directly for real-time updates
     *
     * @param int $featureTypeId
     * @return string
     */
    public function getFeatureStatus(int $featureTypeId): string
    {
        // If we're in default mode, it's always default
        if ($this->activeShopId === null) {
            return 'default';
        }

        // Get current value from productFeatures (working set)
        $currentValue = $this->getFeatureValue($featureTypeId);

        // Get default value from snapshot taken at mount()
        $defaultValue = $this->defaultProductFeatures[$featureTypeId] ?? null;

        // Normalize values for comparison
        $normalizedCurrent = $this->normalizeFeatureValue($currentValue);
        $normalizedDefault = $this->normalizeFeatureValue($defaultValue);

        // Compare current working value vs default snapshot
        // This enables real-time CSS updates when user types
        if ($normalizedCurrent === $normalizedDefault) {
            // Value matches default - show as inherited (purple)
            return 'inherited';
        }

        // Value differs from default - show as different (orange)
        return 'different';
    }

    /**
     * Normalize feature value for comparison
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeFeatureValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    /**
     * Get CSS classes for feature input based on comparison status
     *
     * @param int $featureTypeId
     * @return string
     */
    public function getFeatureClasses(int $featureTypeId): string
    {
        // Taller inputs (py-2.5) with proper text padding (px-4) from edges
        $baseClasses = 'block w-full rounded-md shadow-sm focus:ring-orange-500 sm:text-sm transition-all duration-200 px-4 py-2.5';

        $status = $this->getFeatureStatus($featureTypeId);

        switch ($status) {
            case 'default':
                // Normal mode - standard dark styling
                return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';

            case 'inherited':
                // Inherited - purple styling (CSS class defined in product-form.css)
                return $baseClasses . ' field-status-inherited';

            case 'same':
                // Same as default - green styling (CSS class defined in product-form.css)
                return $baseClasses . ' field-status-same';

            case 'different':
                // Different from default - orange styling (CSS class defined in product-form.css)
                return $baseClasses . ' field-status-different';

            default:
                return $baseClasses . ' border-gray-600 bg-gray-700 text-white focus:border-orange-500';
        }
    }

    /**
     * Get status indicator for feature (visual badge)
     *
     * @param int $featureTypeId
     * @return array
     */
    public function getFeatureStatusIndicator(int $featureTypeId): array
    {
        $status = $this->getFeatureStatus($featureTypeId);

        switch ($status) {
            case 'default':
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];

            case 'inherited':
                return [
                    'show' => true,
                    'text' => 'Dziedziczone',
                    'class' => 'status-label-inherited'
                ];

            case 'same':
                return [
                    'show' => true,
                    'text' => 'Zgodne',
                    'class' => 'status-label-same'
                ];

            case 'different':
                return [
                    'show' => true,
                    'text' => 'Wlasne',
                    'class' => 'status-label-different'
                ];

            default:
                return [
                    'show' => false,
                    'text' => '',
                    'class' => ''
                ];
        }
    }

    /**
     * Load features from PrestaShop for specific shop
     * Called when switching to shop tab
     *
     * @param int $shopId
     * @return void
     */
    public function loadShopFeaturesFromPrestaShop(int $shopId): void
    {
        // Skip if already loaded
        if (isset($this->shopProductFeatures[$shopId])) {
            Log::debug('ProductFormFeatures::loadShopFeaturesFromPrestaShop - Already cached', [
                'shop_id' => $shopId,
            ]);
            return;
        }

        if (!$this->product || !$this->product->id) {
            Log::warning('ProductFormFeatures::loadShopFeaturesFromPrestaShop - No product');
            return;
        }

        try {
            // Get shop data
            $shopData = $this->product->shopData->where('shop_id', $shopId)->first();
            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::debug('ProductFormFeatures::loadShopFeaturesFromPrestaShop - No PS product ID', [
                    'shop_id' => $shopId,
                ]);
                $this->shopProductFeatures[$shopId] = [];
                return;
            }

            $shop = $shopData->shop;
            if (!$shop || !$shop->is_active) {
                Log::warning('ProductFormFeatures::loadShopFeaturesFromPrestaShop - Shop inactive', [
                    'shop_id' => $shopId,
                ]);
                $this->shopProductFeatures[$shopId] = [];
                return;
            }

            // Fetch features from PrestaShop via API
            $client = \App\Services\PrestaShop\PrestaShopClientFactory::create($shop);
            $psProductId = $shopData->prestashop_product_id;

            // Get product features from PrestaShop (returns array)
            $productData = $client->getProduct($psProductId);
            $features = $this->parsePrestaShopFeatures($productData, $shop, $client);

            $this->shopProductFeatures[$shopId] = $features;

            Log::info('ProductFormFeatures::loadShopFeaturesFromPrestaShop SUCCESS', [
                'shop_id' => $shopId,
                'prestashop_product_id' => $psProductId,
                'features_count' => count($features),
            ]);

        } catch (\Exception $e) {
            Log::error('ProductFormFeatures::loadShopFeaturesFromPrestaShop FAILED', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            $this->shopProductFeatures[$shopId] = [];
        }
    }

    /**
     * Parse PrestaShop product data (array) to extract features
     *
     * @param array|null $productData Product data from PrestaShop API
     * @param \App\Models\PrestaShopShop $shop
     * @param mixed $client PrestaShop API client
     * @return array [feature_type_id => value, ...]
     */
    private function parsePrestaShopFeatures(?array $productData, $shop, $client): array
    {
        $features = [];

        if (!$productData) {
            return $features;
        }

        try {
            // Unwrap if nested in 'product' key
            if (isset($productData['product'])) {
                $productData = $productData['product'];
            }

            // PrestaShop stores features in associations/product_features/product_feature
            $associations = $productData['associations'] ?? null;
            if (!$associations) {
                return $features;
            }

            $productFeatures = $associations['product_features']['product_feature'] ?? [];

            // Ensure it's an array (single feature returns as associative array)
            if (isset($productFeatures['id'])) {
                $productFeatures = [$productFeatures];
            }

            foreach ($productFeatures as $psFeature) {
                $psFeatureId = (int) ($psFeature['id'] ?? 0);
                $psFeatureValueId = (int) ($psFeature['id_feature_value'] ?? 0);

                if (!$psFeatureId || !$psFeatureValueId) {
                    continue;
                }

                // Find PPM feature_type_id via prestashop mapping
                $mapping = \App\Models\PrestashopFeatureMapping::where('shop_id', $shop->id)
                    ->where('prestashop_feature_id', $psFeatureId)
                    ->first();

                if (!$mapping) {
                    continue;
                }

                // Get the feature value text from PrestaShop
                $featureValue = $this->getPrestaShopFeatureValueText($client, $psFeatureValueId);

                $features[$mapping->feature_type_id] = $featureValue;
            }

        } catch (\Exception $e) {
            Log::warning('ProductFormFeatures::parsePrestaShopFeatures FAILED', [
                'error' => $e->getMessage(),
            ]);
        }

        return $features;
    }

    /**
     * Get feature value text from PrestaShop
     *
     * @param mixed $client PrestaShop API client
     * @param int $featureValueId
     * @return string|null
     */
    private function getPrestaShopFeatureValueText($client, int $featureValueId): ?string
    {
        try {
            // Use getProductFeatureValue method (returns array)
            $valueData = $client->getProductFeatureValue($featureValueId);

            // Unwrap if nested
            if (isset($valueData['product_feature_value'])) {
                $valueData = $valueData['product_feature_value'];
            }

            // Extract value text (multilang structure)
            // Structure: {value: [{id: 1, value: "Text PL"}, {id: 2, value: "Text EN"}]}
            $valueText = data_get($valueData, 'value');

            if (is_array($valueText)) {
                // Multilang - get first language value
                $firstLang = reset($valueText);
                return is_array($firstLang) ? data_get($firstLang, 'value') : $firstLang;
            }

            return $valueText;

        } catch (\Exception $e) {
            Log::warning('ProductFormFeatures::getPrestaShopFeatureValueText FAILED', [
                'feature_value_id' => $featureValueId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Clear cached shop features (e.g., after sync)
     *
     * @param int|null $shopId If null, clear all shops
     */
    public function clearShopFeaturesCache(?int $shopId = null): void
    {
        if ($shopId === null) {
            $this->shopProductFeatures = [];
        } else {
            unset($this->shopProductFeatures[$shopId]);
        }

        Log::debug('ProductFormFeatures::clearShopFeaturesCache', [
            'shop_id' => $shopId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES FOR BLADE
    |--------------------------------------------------------------------------
    */

    /**
     * Get all feature groups for display
     * Used in attributes-tab.blade.php accordion
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeatureGroupsProperty()
    {
        try {
            return FeatureGroup::with(['featureTypes' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('position')
                          ->with(['featureValues' => function ($q) {
                              $q->where('is_active', true)->orderBy('position');
                          }]);
                }])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        } catch (\Exception $e) {
            Log::warning('ProductFormFeatures::getFeatureGroupsProperty FAILED', [
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    /**
     * Get all feature templates for quick-apply dropdown
     * Features are stored in JSON column, not relation
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeatureTemplatesProperty()
    {
        try {
            return FeatureTemplate::where('is_active', true)
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            Log::warning('ProductFormFeatures::getFeatureTemplatesProperty FAILED', [
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    /**
     * Get all available feature types for add-feature dropdown
     * Excludes features already added to this product
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableFeatureTypesProperty()
    {
        try {
            $existingFeatureTypeIds = collect($this->productFeatures)->pluck('feature_type_id')->toArray();

            return FeatureType::with('group')
                ->where('is_active', true)
                ->whereNotIn('id', $existingFeatureTypeIds)
                ->orderBy('feature_group_id')
                ->orderBy('position')
                ->get();
        } catch (\Exception $e) {
            Log::warning('ProductFormFeatures::getAvailableFeatureTypesProperty FAILED', [
                'error' => $e->getMessage(),
            ]);
            return collect([]);
        }
    }

    /**
     * Get feature value by feature_type_id from productFeatures array
     *
     * @param int $featureTypeId
     * @return mixed|null
     */
    public function getFeatureValue(int $featureTypeId)
    {
        $feature = collect($this->productFeatures)->firstWhere('feature_type_id', $featureTypeId);
        return $feature['value'] ?? null;
    }

    /**
     * Check if a feature type is already added to this product
     *
     * @param int $featureTypeId
     * @return bool
     */
    public function hasFeature(int $featureTypeId): bool
    {
        return collect($this->productFeatures)->contains('feature_type_id', $featureTypeId);
    }

    /**
     * Update feature value (called from wire:model binding)
     *
     * @param int $featureTypeId
     * @param mixed $value
     */
    public function updateFeatureValue(int $featureTypeId, $value): void
    {
        $this->productFeatures = collect($this->productFeatures)
            ->map(function ($feature) use ($featureTypeId, $value) {
                if ($feature['feature_type_id'] == $featureTypeId) {
                    $feature['value'] = $value;
                }
                return $feature;
            })
            ->toArray();

        // Mark as unsaved
        $this->hasUnsavedChanges = true;
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE GROUP ASSIGNMENT (Admin functionality)
    |--------------------------------------------------------------------------
    */

    /**
     * Selected feature type ID for group assignment modal
     * @var int|null
     */
    public ?int $assignGroupFeatureTypeId = null;

    /**
     * Open group assignment modal for a feature type
     *
     * @param int $featureTypeId
     */
    public function openAssignGroupModal(int $featureTypeId): void
    {
        $this->assignGroupFeatureTypeId = $featureTypeId;
        $this->dispatch('open-assign-group-modal');
    }

    /**
     * Assign feature type to a group
     *
     * @param int $groupId
     */
    public function assignFeatureToGroup(int $groupId): void
    {
        if (!$this->assignGroupFeatureTypeId) {
            return;
        }

        $featureType = FeatureType::find($this->assignGroupFeatureTypeId);
        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono typu cechy.');
            return;
        }

        $group = FeatureGroup::find($groupId);
        if (!$group) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono grupy.');
            return;
        }

        $featureType->update(['feature_group_id' => $groupId]);

        Log::info('FeatureType assigned to group', [
            'feature_type_id' => $featureType->id,
            'feature_name' => $featureType->name,
            'group_id' => $groupId,
            'group_name' => $group->name_pl,
        ]);

        $this->assignGroupFeatureTypeId = null;
        $this->dispatch('close-assign-group-modal');
        $this->dispatch('notify', type: 'success', message: "Cecha \"{$featureType->name}\" przypisana do grupy \"{$group->getDisplayName()}\"");
    }

    /**
     * Create new group and assign feature to it
     *
     * @param string $groupName
     */
    public function createGroupAndAssign(string $groupName): void
    {
        if (!$this->assignGroupFeatureTypeId || empty($groupName)) {
            return;
        }

        $featureType = FeatureType::find($this->assignGroupFeatureTypeId);
        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono typu cechy.');
            return;
        }

        // Generate code from name
        $code = strtolower($groupName);
        $code = preg_replace('/[^a-z0-9_\s]/', '', $code);
        $code = preg_replace('/\s+/', '_', $code);
        $code = trim($code, '_');

        // Ensure unique code
        $baseCode = $code;
        $counter = 1;
        while (FeatureGroup::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        $group = FeatureGroup::create([
            'code' => $code,
            'name' => $groupName,
            'name_pl' => $groupName,
            'icon' => 'info',
            'color' => 'blue',
            'sort_order' => FeatureGroup::max('sort_order') + 1,
            'is_active' => true,
            'is_collapsible' => true,
        ]);

        $featureType->update(['feature_group_id' => $group->id]);

        Log::info('Created new FeatureGroup and assigned feature', [
            'group_id' => $group->id,
            'group_name' => $groupName,
            'feature_type_id' => $featureType->id,
            'feature_name' => $featureType->name,
        ]);

        $this->assignGroupFeatureTypeId = null;
        $this->dispatch('close-assign-group-modal');
        $this->dispatch('notify', type: 'success', message: "Utworzono grupe \"{$groupName}\" i przypisano ceche \"{$featureType->name}\"");
    }

    /**
     * Add feature type to a template
     *
     * @param int $templateId
     */
    public function addFeatureToTemplate(int $templateId): void
    {
        if (!$this->assignGroupFeatureTypeId) {
            return;
        }

        $featureType = FeatureType::find($this->assignGroupFeatureTypeId);
        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono typu cechy.');
            return;
        }

        $template = FeatureTemplate::find($templateId);
        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono szablonu.');
            return;
        }

        // Get current template features (JSON column)
        $features = $template->features ?? [];

        // Check if already in template
        $alreadyExists = collect($features)->contains('feature_type_id', $featureType->id);
        if ($alreadyExists) {
            $this->dispatch('notify', type: 'warning', message: 'Ta cecha juz istnieje w szablonie.');
            return;
        }

        // Add to template
        $features[] = [
            'feature_type_id' => $featureType->id,
            'code' => $featureType->code,
            'name' => $featureType->name,
            'type' => $featureType->value_type,
        ];

        $template->update(['features' => $features]);

        Log::info('Feature added to template', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'feature_type_id' => $featureType->id,
            'feature_name' => $featureType->name,
        ]);

        $this->dispatch('notify', type: 'success', message: "Cecha \"{$featureType->name}\" dodana do szablonu \"{$template->name}\"");
    }

    /**
     * Remove feature type from a template
     *
     * @param int $templateId
     */
    public function removeFeatureFromTemplate(int $templateId): void
    {
        if (!$this->assignGroupFeatureTypeId) {
            return;
        }

        $featureType = FeatureType::find($this->assignGroupFeatureTypeId);
        if (!$featureType) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono typu cechy.');
            return;
        }

        $template = FeatureTemplate::find($templateId);
        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono szablonu.');
            return;
        }

        // Get current template features and filter out the one to remove
        $features = collect($template->features ?? [])
            ->reject(fn($f) => ($f['feature_type_id'] ?? null) == $featureType->id)
            ->values()
            ->toArray();

        $template->update(['features' => $features]);

        Log::info('Feature removed from template', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'feature_type_id' => $featureType->id,
            'feature_name' => $featureType->name,
        ]);

        $this->dispatch('notify', type: 'success', message: "Cecha \"{$featureType->name}\" usunieta z szablonu \"{$template->name}\"");
    }

    /**
     * Get feature type being assigned (for modal display)
     */
    public function getAssignGroupFeatureTypeProperty(): ?FeatureType
    {
        if (!$this->assignGroupFeatureTypeId) {
            return null;
        }
        return FeatureType::find($this->assignGroupFeatureTypeId);
    }
}
