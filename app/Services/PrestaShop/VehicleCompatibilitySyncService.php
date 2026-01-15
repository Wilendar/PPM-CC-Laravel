<?php

namespace App\Services\PrestaShop;

use App\Models\CompatibilityAttribute;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\VehicleCompatibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VehicleCompatibilitySyncService
 *
 * ETAP_05d FAZA 4.5.1 - Bidirectional sync of vehicle compatibility with PrestaShop
 *
 * Responsibility:
 * - Transform VehicleCompatibility -> PrestaShop Features XML
 * - Transform PrestaShop Features -> VehicleCompatibility
 * - Handle feature_value ID mappings between PPM vehicles and PS feature values
 *
 * PrestaShop Feature IDs (from B2B Test DEV database):
 * - 431: Oryginal (6,693 products assigned)
 * - 432: Model (7,717 products assigned) - computed as O + Z
 * - 433: Zamiennik (2,319 products assigned)
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since 2025-12-09
 */
class VehicleCompatibilitySyncService
{
    /**
     * PrestaShop Feature IDs for compatibility types
     * These are specific to B2B Test DEV installation
     */
    public const FEATURE_ORYGINAL = 431;
    public const FEATURE_MODEL = 432;
    public const FEATURE_ZAMIENNIK = 433;

    /**
     * Cache TTL for feature value mappings (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Cache key prefix for feature value mappings
     */
    protected const CACHE_PREFIX = 'compatibility_feature_value:';

    /**
     * PrestaShop API client
     */
    protected ?BasePrestaShopClient $client = null;

    /**
     * Current shop context
     */
    protected ?PrestaShopShop $shop = null;

    /**
     * Create service instance
     */
    public function __construct()
    {
        // Client and shop are set dynamically per operation
    }

    /**
     * Set client for operations
     *
     * @param BasePrestaShopClient $client
     * @return $this
     */
    public function setClient(BasePrestaShopClient $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Set shop context
     *
     * @param PrestaShopShop $shop
     * @return $this
     */
    public function setShop(PrestaShopShop $shop): self
    {
        $this->shop = $shop;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT: PPM -> PrestaShop
    |--------------------------------------------------------------------------
    */

    /**
     * Transform vehicle compatibilities to PrestaShop features format
     *
     * Creates the product_features association array for PrestaShop API.
     * Automatically calculates Model feature (union of Original + Replacement).
     *
     * @param Product $product PPM product (spare part)
     * @param int $shopId PPM shop ID
     * @return array Array of feature associations for PS API
     */
    public function transformToPrestaShopFeatures(Product $product, int $shopId): array
    {
        $associations = [];

        Log::debug('[COMPAT SYNC] Transforming compatibilities to PS features', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'shop_id' => $shopId,
        ]);

        // Load compatibilities for this product and shop
        $compatibilities = VehicleCompatibility::byProduct($product->id)
            ->where(function ($query) use ($shopId) {
                $query->where('shop_id', $shopId)
                    ->orWhereNull('shop_id');
            })
            ->with(['vehicleProduct', 'compatibilityAttribute'])
            ->get();

        if ($compatibilities->isEmpty()) {
            Log::debug('[COMPAT SYNC] No compatibilities found', [
                'product_id' => $product->id,
            ]);
            return [];
        }

        // Group by type
        $originalVehicles = [];
        $zamiennikVehicles = [];

        foreach ($compatibilities as $compat) {
            $vehicleName = $this->getVehicleDisplayName($compat->vehicleProduct);
            $featureId = $this->mapTypeToFeatureId($compat->compatibilityAttribute->code ?? 'original');
            $featureValueId = $this->getOrCreateFeatureValue(
                $featureId,
                $vehicleName,
                $shopId
            );

            if (!$featureValueId) {
                Log::warning('[COMPAT SYNC] Could not get/create feature value', [
                    'vehicle_id' => $compat->vehicle_model_id,
                    'vehicle_name' => $vehicleName,
                ]);
                continue;
            }

            // Save mapping for reverse lookup during import
            $this->saveFeatureValueMapping(
                $compat->vehicle_model_id,
                $featureId,
                $featureValueId,
                $shopId
            );

            $type = $compat->compatibilityAttribute->code ?? 'original';

            if ($type === CompatibilityAttribute::CODE_ORIGINAL) {
                $originalVehicles[$featureValueId] = $vehicleName;
            } elseif ($type === CompatibilityAttribute::CODE_REPLACEMENT) {
                $zamiennikVehicles[$featureValueId] = $vehicleName;
            }
        }

        // Add Original feature associations
        foreach ($originalVehicles as $featureValueId => $vehicleName) {
            $associations[] = [
                'id' => self::FEATURE_ORYGINAL,
                'id_feature_value' => $featureValueId,
            ];
        }

        // Add Zamiennik feature associations
        foreach ($zamiennikVehicles as $featureValueId => $vehicleName) {
            $associations[] = [
                'id' => self::FEATURE_ZAMIENNIK,
                'id_feature_value' => $featureValueId,
            ];
        }

        // Calculate and add Model feature (union of O + Z)
        $modelVehicles = array_merge($originalVehicles, $zamiennikVehicles);
        foreach ($modelVehicles as $featureValueId => $vehicleName) {
            // Get Model feature value ID (may be different from O/Z value IDs)
            $modelValueId = $this->getOrCreateFeatureValue(
                self::FEATURE_MODEL,
                $vehicleName,
                $shopId
            );

            if ($modelValueId) {
                $associations[] = [
                    'id' => self::FEATURE_MODEL,
                    'id_feature_value' => $modelValueId,
                ];
            }
        }

        Log::info('[COMPAT SYNC] Transformed compatibilities to PS features', [
            'product_id' => $product->id,
            'original_count' => count($originalVehicles),
            'zamiennik_count' => count($zamiennikVehicles),
            'model_count' => count($modelVehicles),
            'total_associations' => count($associations),
        ]);

        return $associations;
    }

    /**
     * Map PPM compatibility type to PrestaShop feature ID
     *
     * @param string $type PPM compatibility attribute code
     * @return int PrestaShop feature ID
     */
    public function mapTypeToFeatureId(string $type): int
    {
        return match ($type) {
            CompatibilityAttribute::CODE_ORIGINAL => self::FEATURE_ORYGINAL,
            CompatibilityAttribute::CODE_REPLACEMENT => self::FEATURE_ZAMIENNIK,
            default => self::FEATURE_ORYGINAL, // Default to Original
        };
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT: PrestaShop -> PPM
    |--------------------------------------------------------------------------
    */

    /**
     * Import compatibility from PrestaShop features
     *
     * Parses product_features associations and creates VehicleCompatibility records.
     *
     * @param array $productData PrestaShop product data from API
     * @param Product $product PPM product
     * @param int $shopId PPM shop ID
     * @return Collection Created/updated VehicleCompatibility records
     */
    public function importFromPrestaShopFeatures(
        array $productData,
        Product $product,
        int $shopId
    ): Collection {
        $imported = collect();
        $missingVehicles = [];

        Log::debug('[COMPAT SYNC] Importing compatibilities from PS features', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
        ]);

        $features = $productData['associations']['product_features'] ?? [];

        // Filter only compatibility features (431 Oryginał, 433 Zamiennik)
        // Note: 432 Model is computed from O+Z, so we skip it
        $compatibilityFeatures = collect($features)->filter(
            fn($f) => in_array((int) $f['id'], [
                self::FEATURE_ORYGINAL,
                self::FEATURE_ZAMIENNIK
            ])
        );

        if ($compatibilityFeatures->isEmpty()) {
            Log::debug('[COMPAT SYNC] No compatibility features found in product', [
                'product_id' => $product->id,
            ]);
            return $imported;
        }

        Log::info('[COMPAT SYNC] Found compatibility features to import', [
            'product_id' => $product->id,
            'features_count' => $compatibilityFeatures->count(),
        ]);

        // Get attribute IDs
        $originalAttrId = CompatibilityAttribute::byCode(CompatibilityAttribute::CODE_ORIGINAL)
            ->value('id');
        $zamiennikAttrId = CompatibilityAttribute::byCode(CompatibilityAttribute::CODE_REPLACEMENT)
            ->value('id');

        // Get default source ID (PrestaShop import)
        $sourceId = DB::table('compatibility_sources')
            ->where('code', 'prestashop')
            ->value('id') ?? 1;

        foreach ($compatibilityFeatures as $feature) {
            $featureId = (int) $feature['id'];
            $featureValueId = (int) ($feature['id_feature_value'] ?? 0);

            if (!$featureValueId) {
                continue;
            }

            // Find vehicle product by feature value (with name lookup)
            $result = $this->findVehicleByFeatureValueWithName($featureValueId, $shopId);
            $vehicleProductId = $result['vehicle_id'];
            $psVehicleName = $result['ps_name'];

            if (!$vehicleProductId) {
                // Track missing vehicle for reporting
                $featureNames = [
                    self::FEATURE_ORYGINAL => 'Oryginał',
                    self::FEATURE_ZAMIENNIK => 'Zamiennik',
                ];
                $missingVehicles[] = [
                    'ps_name' => $psVehicleName,
                    'feature_type' => $featureNames[$featureId] ?? 'Unknown',
                    'feature_value_id' => $featureValueId,
                ];

                Log::warning('[COMPAT SYNC] Missing vehicle in PPM - cannot import', [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'ps_vehicle_name' => $psVehicleName,
                    'feature_type' => $featureNames[$featureId] ?? 'Unknown',
                    'feature_value_id' => $featureValueId,
                ]);
                continue;
            }

            // Determine attribute ID
            $attributeId = match ($featureId) {
                self::FEATURE_ORYGINAL => $originalAttrId,
                self::FEATURE_ZAMIENNIK => $zamiennikAttrId,
                default => $originalAttrId,
            };

            // Create or update compatibility
            $compat = VehicleCompatibility::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'vehicle_model_id' => $vehicleProductId,
                    'shop_id' => $shopId,
                ],
                [
                    'compatibility_attribute_id' => $attributeId,
                    'compatibility_source_id' => $sourceId,
                    'is_suggested' => false,
                    'metadata' => [
                        'imported_from_ps' => true,
                        'ps_feature_id' => $featureId,
                        'ps_feature_value_id' => $featureValueId,
                        'ps_vehicle_name' => $psVehicleName,
                        'imported_at' => now()->toIso8601String(),
                    ],
                ]
            );

            $imported->push($compat);
        }

        // Log summary with missing vehicles info
        Log::info('[COMPAT SYNC] Imported compatibilities from PS', [
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'imported_count' => $imported->count(),
            'missing_vehicles_count' => count($missingVehicles),
            'total_features' => $compatibilityFeatures->count(),
        ]);

        // If there are missing vehicles, log them as a batch for easy review
        if (!empty($missingVehicles)) {
            Log::warning('[COMPAT SYNC] MISSING VEHICLES REPORT', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'missing_vehicles' => array_unique(array_column($missingVehicles, 'ps_name')),
            ]);
        }

        return $imported;
    }

    /**
     * Find vehicle by feature value and also return the PS name
     *
     * @param int $featureValueId
     * @param int $shopId
     * @return array ['vehicle_id' => int|null, 'ps_name' => string]
     */
    protected function findVehicleByFeatureValueWithName(int $featureValueId, int $shopId): array
    {
        $result = ['vehicle_id' => null, 'ps_name' => ''];

        // First check mapping table
        $vehicleId = DB::table('vehicle_feature_value_mappings')
            ->where('prestashop_feature_value_id', $featureValueId)
            ->where('shop_id', $shopId)
            ->value('vehicle_product_id');

        if ($vehicleId) {
            $result['vehicle_id'] = (int) $vehicleId;
            // Get PS name from cache or API for logging
            $result['ps_name'] = $this->getFeatureValueName($featureValueId);
            return $result;
        }

        // Try to match by name from PrestaShop
        if ($this->client) {
            try {
                $response = $this->client->getProductFeatureValue($featureValueId);
                $valueData = $response['product_feature_value'] ?? $response;
                $valueName = $this->extractMultilangValue($valueData['value'] ?? []);

                $result['ps_name'] = $valueName;

                if ($valueName) {
                    $vehicle = $this->findVehicleByFlexibleMatch($valueName);

                    if ($vehicle) {
                        // Save mapping for future use
                        $featureId = $valueData['id_feature'] ?? 0;

                        DB::table('vehicle_feature_value_mappings')->updateOrInsert(
                            [
                                'prestashop_feature_value_id' => $featureValueId,
                                'shop_id' => $shopId,
                            ],
                            [
                                'vehicle_product_id' => $vehicle->id,
                                'prestashop_feature_id' => $featureId,
                                'created_at' => now(),
                            ]
                        );

                        Log::info('[COMPAT SYNC] Matched vehicle by name', [
                            'feature_value_id' => $featureValueId,
                            'ps_name' => $valueName,
                            'vehicle_id' => $vehicle->id,
                            'vehicle_name' => $vehicle->name,
                        ]);

                        $result['vehicle_id'] = $vehicle->id;
                    }
                }

            } catch (\Exception $e) {
                Log::warning('[COMPAT SYNC] Error finding vehicle by feature value', [
                    'feature_value_id' => $featureValueId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Get feature value name from PrestaShop (with caching)
     *
     * @param int $featureValueId
     * @return string
     */
    protected function getFeatureValueName(int $featureValueId): string
    {
        $cacheKey = "ps_feature_value_name:{$featureValueId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($featureValueId) {
            if (!$this->client) {
                return '';
            }

            try {
                $response = $this->client->getProductFeatureValue($featureValueId);
                $valueData = $response['product_feature_value'] ?? $response;
                return $this->extractMultilangValue($valueData['value'] ?? []);
            } catch (\Exception $e) {
                return '';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE VALUE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Get or create feature value for a vehicle name
     *
     * Checks cache first, then PrestaShop API, creates if needed.
     *
     * @param int $featureId PrestaShop feature ID (431/432/433)
     * @param string $vehicleName Vehicle display name
     * @param int $shopId PPM shop ID for cache key
     * @return int|null PrestaShop feature value ID
     */
    public function getOrCreateFeatureValue(
        int $featureId,
        string $vehicleName,
        int $shopId
    ): ?int {
        if (!$this->client) {
            Log::error('[COMPAT SYNC] Client not set for getOrCreateFeatureValue');
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . "{$shopId}:{$featureId}:" . md5($vehicleName);

        // Check cache first
        $cachedId = Cache::get($cacheKey);
        if ($cachedId !== null) {
            return (int) $cachedId;
        }

        try {
            // Try to find existing value by name
            $existingValue = $this->findFeatureValueByName($featureId, $vehicleName);

            if ($existingValue) {
                Cache::put($cacheKey, $existingValue, self::CACHE_TTL);
                return $existingValue;
            }

            // Create new feature value
            $newValueId = $this->createFeatureValue($featureId, $vehicleName);

            if ($newValueId) {
                Cache::put($cacheKey, $newValueId, self::CACHE_TTL);
                return $newValueId;
            }

        } catch (\Exception $e) {
            Log::error('[COMPAT SYNC] Error in getOrCreateFeatureValue', [
                'feature_id' => $featureId,
                'vehicle_name' => $vehicleName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Find feature value ID by name
     *
     * @param int $featureId
     * @param string $valueName
     * @return int|null
     */
    protected function findFeatureValueByName(int $featureId, string $valueName): ?int
    {
        try {
            Log::debug('[COMPAT SYNC] Finding feature value by name', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
            ]);

            // Get all values for this feature
            $response = $this->client->getProductFeatureValues([
                'filter[id_feature]' => $featureId,
                'display' => 'full',
            ]);

            // Extract values from response - PS returns nested structure
            $values = $response['product_feature_values']['product_feature_value'] ?? $response['product_feature_values'] ?? $response ?? [];

            // Handle single value as object vs array of values
            if (isset($values['id'])) {
                $values = [$values];
            }

            Log::debug('[COMPAT SYNC] Found feature values', [
                'feature_id' => $featureId,
                'count' => count($values),
            ]);

            foreach ($values as $value) {
                // Extract value name from multilang structure
                $name = $this->extractMultilangValue($value['value'] ?? []);

                if (strtolower(trim($name)) === strtolower(trim($valueName))) {
                    Log::debug('[COMPAT SYNC] Matched feature value', [
                        'feature_id' => $featureId,
                        'value_id' => $value['id'],
                        'value_name' => $name,
                    ]);
                    return (int) $value['id'];
                }
            }

            Log::debug('[COMPAT SYNC] No matching feature value found', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
            ]);

        } catch (\Exception $e) {
            Log::error('[COMPAT SYNC] Error finding feature value by name', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Create new feature value in PrestaShop
     *
     * @param int $featureId
     * @param string $valueName
     * @return int|null Created value ID
     */
    protected function createFeatureValue(int $featureId, string $valueName): ?int
    {
        try {
            Log::debug('[COMPAT SYNC] Creating new feature value', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
            ]);

            $result = $this->client->createProductFeatureValue([
                'id_feature' => $featureId,
                'value' => [
                    ['id' => 1, 'value' => $valueName], // Polish (default)
                ],
            ]);

            // FIX: Extract ID from nested response structure
            // createProductFeatureValue returns: ['product_feature_value' => ['id' => X, ...]]
            $valueId = $result['product_feature_value']['id']
                ?? $result['id']
                ?? null;

            Log::debug('[COMPAT SYNC] createFeatureValue result', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
                'extracted_id' => $valueId,
                'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
            ]);

            if ($valueId) {
                Log::info('[COMPAT SYNC] Created feature value', [
                    'feature_id' => $featureId,
                    'value_id' => $valueId,
                    'value_name' => $valueName,
                ]);
            }

            return $valueId ? (int) $valueId : null;

        } catch (\Exception $e) {
            Log::error('[COMPAT SYNC] Error creating feature value', [
                'feature_id' => $featureId,
                'value_name' => $valueName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Find vehicle product by PrestaShop feature value ID
     *
     * Uses vehicle_feature_value_mappings table for lookup.
     *
     * @param int $featureValueId
     * @param int $shopId
     * @return int|null Vehicle product ID
     */
    protected function findVehicleByFeatureValue(int $featureValueId, int $shopId): ?int
    {
        // First check mapping table
        $vehicleId = DB::table('vehicle_feature_value_mappings')
            ->where('prestashop_feature_value_id', $featureValueId)
            ->where('shop_id', $shopId)
            ->value('vehicle_product_id');

        if ($vehicleId) {
            Log::debug('[COMPAT SYNC] Found vehicle from mapping table', [
                'feature_value_id' => $featureValueId,
                'vehicle_id' => $vehicleId,
            ]);
            return (int) $vehicleId;
        }

        // Try to match by name from PrestaShop
        if ($this->client) {
            try {
                // API returns nested structure: product_feature_values.product_feature_value
                $response = $this->client->getProductFeatureValue($featureValueId);

                // Handle nested response structure
                $valueData = $response['product_feature_value'] ?? $response;

                Log::debug('[COMPAT SYNC] Got feature value from PS', [
                    'feature_value_id' => $featureValueId,
                    'response_keys' => is_array($response) ? array_keys($response) : 'not_array',
                    'value_data_keys' => is_array($valueData) ? array_keys($valueData) : 'not_array',
                ]);

                $valueName = $this->extractMultilangValue($valueData['value'] ?? []);

                if ($valueName) {
                    Log::debug('[COMPAT SYNC] Searching for vehicle by name', [
                        'feature_value_id' => $featureValueId,
                        'value_name' => $valueName,
                    ]);

                    // Search for vehicle by name (product_type_id = 1 = Pojazd)
                    // Use more flexible matching - split name into keywords
                    $vehicle = $this->findVehicleByFlexibleMatch($valueName);

                    if ($vehicle) {
                        // Save mapping for future use
                        $featureId = $valueData['id_feature'] ?? 0;

                        DB::table('vehicle_feature_value_mappings')->updateOrInsert(
                            [
                                'prestashop_feature_value_id' => $featureValueId,
                                'shop_id' => $shopId,
                            ],
                            [
                                'vehicle_product_id' => $vehicle->id,
                                'prestashop_feature_id' => $featureId,
                                'created_at' => now(),
                            ]
                        );

                        Log::info('[COMPAT SYNC] Matched vehicle by name', [
                            'feature_value_id' => $featureValueId,
                            'ps_name' => $valueName,
                            'vehicle_id' => $vehicle->id,
                            'vehicle_name' => $vehicle->name,
                        ]);

                        return $vehicle->id;
                    }

                    Log::warning('[COMPAT SYNC] No vehicle match found for name', [
                        'feature_value_id' => $featureValueId,
                        'value_name' => $valueName,
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning('[COMPAT SYNC] Error finding vehicle by feature value', [
                    'feature_value_id' => $featureValueId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Find vehicle by flexible name matching
     *
     * PRIORITY: SKU is the main identifier in PPM!
     *
     * Uses keyword-based matching to handle cases like:
     * - PS: "KAYO AU150 CVT" -> PPM: "Quad KAYO AU150"
     * - PS: "Buggy KAYO S200" -> PPM: "Buggy KAYO S200"
     * - PS: "MRF E-Dirt 6.0" -> PPM: "Dirt Bike MRF eDIRT 6.0"
     *
     * @param string $psName Name from PrestaShop feature value
     * @return Product|null
     */
    protected function findVehicleByFlexibleMatch(string $psName): ?Product
    {
        // Clean and extract keywords from PS name
        $keywords = $this->extractSearchKeywords($psName);

        Log::debug('[COMPAT SYNC] Flexible match attempt', [
            'ps_name' => $psName,
            'keywords' => $keywords,
        ]);

        // Load all vehicles once for multiple strategies
        $vehicles = Product::where('product_type_id', 1)->get(['id', 'name', 'sku']);

        // Strategy 1: EXACT SKU match (highest priority!)
        $vehicle = $vehicles->first(fn($v) => strcasecmp($v->sku, $psName) === 0);
        if ($vehicle) {
            Log::debug('[COMPAT SYNC] Strategy 1 match (exact SKU)', ['vehicle' => $vehicle->name, 'sku' => $vehicle->sku]);
            return $vehicle;
        }

        // Strategy 2: SKU contains PS name keywords (normalized)
        $normalizedPsName = $this->normalizeForMatch($psName);
        foreach ($vehicles as $v) {
            $normalizedSku = $this->normalizeForMatch($v->sku);

            // Check if normalized SKU contains main identifiers from PS name
            if (!empty($keywords)) {
                $allKeywordsInSku = true;
                foreach ($keywords as $keyword) {
                    $normalizedKeyword = $this->normalizeForMatch($keyword);
                    if (stripos($normalizedSku, $normalizedKeyword) === false) {
                        $allKeywordsInSku = false;
                        break;
                    }
                }
                if ($allKeywordsInSku) {
                    Log::debug('[COMPAT SYNC] Strategy 2 match (keywords in SKU)', [
                        'vehicle' => $v->name,
                        'sku' => $v->sku,
                        'keywords' => $keywords,
                    ]);
                    return $v;
                }
            }
        }

        // Strategy 3: PS name contains vehicle SKU
        foreach ($vehicles as $v) {
            $normalizedSku = $this->normalizeForMatch($v->sku);
            if (strlen($normalizedSku) >= 4 && stripos($normalizedPsName, $normalizedSku) !== false) {
                Log::debug('[COMPAT SYNC] Strategy 3 match (SKU in PS name)', [
                    'vehicle' => $v->name,
                    'sku' => $v->sku,
                ]);
                return $v;
            }
        }

        // Strategy 4: Normalized name contains all normalized keywords
        if (!empty($keywords)) {
            foreach ($vehicles as $v) {
                $normalizedVehicleName = $this->normalizeForMatch($v->name);

                // Check if all keywords are present in normalized vehicle name
                $allMatch = true;
                foreach ($keywords as $keyword) {
                    $normalizedKeyword = $this->normalizeForMatch($keyword);
                    if (stripos($normalizedVehicleName, $normalizedKeyword) === false) {
                        $allMatch = false;
                        break;
                    }
                }

                if ($allMatch) {
                    Log::debug('[COMPAT SYNC] Strategy 4 match (normalized keywords in name)', [
                        'vehicle' => $v->name,
                        'normalized_ps' => $normalizedPsName,
                        'normalized_vehicle' => $normalizedVehicleName,
                    ]);
                    return $v;
                }
            }
        }

        // Strategy 5: Brand + model number match (main identifiers)
        // Find keywords that look like model numbers (contain digits)
        $modelKeywords = array_filter($keywords, fn($k) => preg_match('/\d/', $k));
        $brandKeywords = array_filter($keywords, fn($k) => !preg_match('/\d/', $k) && strlen($k) >= 3);

        if (!empty($modelKeywords) && !empty($brandKeywords)) {
            $mainBrand = reset($brandKeywords);

            foreach ($vehicles as $v) {
                // Check SKU first (priority!)
                $normalizedSku = $this->normalizeForMatch($v->sku);
                $normalizedVehicleName = $this->normalizeForMatch($v->name);

                // Must match brand in name or SKU
                $brandInSku = stripos($normalizedSku, $this->normalizeForMatch($mainBrand)) !== false;
                $brandInName = stripos($normalizedVehicleName, $this->normalizeForMatch($mainBrand)) !== false;

                if (!$brandInSku && !$brandInName) {
                    continue;
                }

                // Must match at least one model identifier in SKU or name
                foreach ($modelKeywords as $modelKw) {
                    $normalizedModel = $this->normalizeForMatch($modelKw);
                    if (stripos($normalizedSku, $normalizedModel) !== false ||
                        stripos($normalizedVehicleName, $normalizedModel) !== false) {
                        Log::debug('[COMPAT SYNC] Strategy 5 match (brand+model)', [
                            'vehicle' => $v->name,
                            'sku' => $v->sku,
                            'brand' => $mainBrand,
                            'model' => $modelKw,
                        ]);
                        return $v;
                    }
                }
            }
        }

        Log::debug('[COMPAT SYNC] No flexible match found', [
            'ps_name' => $psName,
            'keywords' => $keywords,
            'vehicles_checked' => $vehicles->count(),
        ]);

        return null;
    }

    /**
     * Normalize string for flexible matching
     *
     * Removes separators, normalizes case, handles e-dirt/edirt variants
     *
     * @param string $str
     * @return string
     */
    protected function normalizeForMatch(string $str): string
    {
        // Convert to lowercase
        $normalized = strtolower($str);

        // Remove common separators but keep alphanumeric
        $normalized = preg_replace('/[\s\-_\/\.]+/', '', $normalized);

        return $normalized;
    }

    /**
     * Extract search keywords from vehicle name
     *
     * Filters out common words and short words, keeps meaningful identifiers.
     * Also merges hyphenated words like "E-Dirt" into "edirt" for matching.
     *
     * @param string $name
     * @return array
     */
    protected function extractSearchKeywords(string $name): array
    {
        // Common words to ignore
        $stopWords = ['quad', 'buggy', 'motocykl', 'cross', 'enduro', 'cvt', 'atv', 'utv', 'pit', 'bike', 'dirt'];

        // First, create merged versions of hyphenated words (E-Dirt -> edirt)
        $mergedName = preg_replace('/(\w+)-(\w+)/', '$1$2 $1-$2', $name);

        // Split by spaces (keep hyphenated for alternative matching)
        $words = preg_split('/[\s\/]+/', strtolower($mergedName));

        // Filter
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            $word = trim($word, '-_.');
            // Keep if:
            // - Not empty
            // - At least 2 chars
            // - Not a stop word OR contains numbers (model identifiers like AU150, 6.0)
            return strlen($word) >= 2 &&
                   (!in_array($word, $stopWords) || preg_match('/\d/', $word));
        });

        // Clean up hyphens from keywords for normalization
        $keywords = array_map(fn($k) => trim($k, '-_.'), $keywords);

        // Re-index and return unique
        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Save feature value mapping for reverse lookup during import
     *
     * @param int $vehicleProductId PPM vehicle product ID
     * @param int $featureId PrestaShop feature ID (431/432/433)
     * @param int $featureValueId PrestaShop feature value ID
     * @param int $shopId PPM shop ID
     */
    protected function saveFeatureValueMapping(
        int $vehicleProductId,
        int $featureId,
        int $featureValueId,
        int $shopId
    ): void {
        try {
            DB::table('vehicle_feature_value_mappings')->updateOrInsert(
                [
                    'vehicle_product_id' => $vehicleProductId,
                    'prestashop_feature_id' => $featureId,
                    'shop_id' => $shopId,
                ],
                [
                    'prestashop_feature_value_id' => $featureValueId,
                    'created_at' => now(),
                ]
            );

            Log::debug('[COMPAT SYNC] Saved feature value mapping', [
                'vehicle_id' => $vehicleProductId,
                'feature_id' => $featureId,
                'feature_value_id' => $featureValueId,
                'shop_id' => $shopId,
            ]);
        } catch (\Exception $e) {
            Log::warning('[COMPAT SYNC] Could not save feature value mapping', [
                'vehicle_id' => $vehicleProductId,
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get display name for a vehicle product
     *
     * @param Product|null $vehicle
     * @return string
     */
    protected function getVehicleDisplayName(?Product $vehicle): string
    {
        if (!$vehicle) {
            return 'Unknown Vehicle';
        }

        // Try to build a meaningful name
        $parts = [];

        // Brand from features or name
        $brand = $vehicle->getFeatureValue('marka') ?? '';
        if ($brand) {
            $parts[] = $brand;
        }

        // Model from features or name
        $model = $vehicle->getFeatureValue('model') ?? $vehicle->name;
        if ($model) {
            $parts[] = $model;
        }

        // Year range if available
        $yearFrom = $vehicle->getFeatureValue('rok_od');
        $yearTo = $vehicle->getFeatureValue('rok_do');
        if ($yearFrom || $yearTo) {
            $yearRange = ($yearFrom ?? '?') . '-' . ($yearTo ?? '?');
            $parts[] = "({$yearRange})";
        }

        return implode(' ', $parts) ?: $vehicle->name ?: $vehicle->sku;
    }

    /**
     * Extract value from PrestaShop multilang structure
     *
     * Handles various PS response formats:
     * - Direct string: "value"
     * - Object with value key: { "value": "..." }
     * - Language array: { "language": [{"id": 1, "value": "..."}] }
     *
     * @param mixed $multilang Value from PS API (string, array, or null)
     * @param int $langId Language ID (1 = Polish by default)
     * @return string
     */
    protected function extractMultilangValue(mixed $multilang, int $langId = 1): string
    {
        // Handle null/empty
        if ($multilang === null || $multilang === '') {
            return '';
        }

        // Handle direct string value (most common in PS8 JSON responses)
        if (is_string($multilang)) {
            return $multilang;
        }

        // Handle non-array
        if (!is_array($multilang)) {
            return (string) $multilang;
        }

        // Handle direct value key
        if (isset($multilang['value'])) {
            return (string) $multilang['value'];
        }

        // Handle language array
        if (isset($multilang['language'])) {
            $languages = $multilang['language'];

            // Ensure it's an array
            if (!is_array($languages)) {
                return (string) $languages;
            }

            // Handle single language as object
            if (isset($languages['value'])) {
                return (string) $languages['value'];
            }

            // Handle multiple languages
            foreach ($languages as $lang) {
                if (is_array($lang) && ((int) ($lang['@attributes']['id'] ?? $lang['id'] ?? 0)) === $langId) {
                    return (string) ($lang['value'] ?? $lang);
                }
            }

            // Fallback to first language
            $first = reset($languages);
            return is_array($first) ? (string) ($first['value'] ?? '') : (string) $first;
        }

        return '';
    }

    /**
     * Clear feature value cache for a shop
     *
     * @param int $shopId
     * @return void
     */
    public function clearCache(int $shopId): void
    {
        // Clear all cache keys for this shop
        // Note: This is a simplified approach. For production,
        // consider using cache tags or a more sophisticated invalidation strategy
        Log::info('[COMPAT SYNC] Clearing feature value cache', [
            'shop_id' => $shopId,
        ]);

        // Cannot easily clear by prefix in Laravel Cache
        // Individual keys are cleared on update
    }

    /**
     * Get sync statistics for a product
     *
     * @param Product $product
     * @param int $shopId
     * @return array
     */
    public function getSyncStats(Product $product, int $shopId): array
    {
        $compatibilities = VehicleCompatibility::byProduct($product->id)
            ->where(function ($query) use ($shopId) {
                $query->where('shop_id', $shopId)
                    ->orWhereNull('shop_id');
            })
            ->with('compatibilityAttribute')
            ->get();

        $original = $compatibilities->filter(
            fn($c) => $c->compatibilityAttribute?->code === CompatibilityAttribute::CODE_ORIGINAL
        )->count();

        $replacement = $compatibilities->filter(
            fn($c) => $c->compatibilityAttribute?->code === CompatibilityAttribute::CODE_REPLACEMENT
        )->count();

        return [
            'original_count' => $original,
            'replacement_count' => $replacement,
            'model_count' => $original + $replacement, // Union
            'total_records' => $compatibilities->count(),
            'has_data' => $compatibilities->isNotEmpty(),
        ];
    }
}
