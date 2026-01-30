<?php

namespace App\Services\ERP\SubiektGT;

use App\Models\Product;
use App\Models\ProductErpData;
use Illuminate\Support\Facades\Log;

/**
 * SubiektDataTransformer
 *
 * ETAP: Subiekt GT ERP Integration
 *
 * Handles data transformation between PPM (Laravel) and Subiekt GT formats.
 * Supports bidirectional mapping with configurable field mappings.
 *
 * @package App\Services\ERP\SubiektGT
 * @version 1.0
 */
class SubiektDataTransformer
{
    /**
     * Default field mapping: Subiekt GT -> PPM
     */
    protected array $defaultFieldMapping = [
        // Subiekt GT field => PPM field
        'id' => 'external_id',
        'sku' => 'sku',
        'name' => 'name',
        'description' => 'long_description',
        'ean' => 'ean',
        'weight' => 'weight',
        'weight_net' => 'weight',
        'weight_gross' => 'weight',
        'price_net' => 'price_net',
        'price_gross' => 'price_gross',
        'stock_quantity' => 'stock_quantity',
        'is_active' => 'is_active',
        'updated_at' => 'erp_updated_at',
        'created_at' => 'erp_created_at',
        'manufacturer_id' => 'manufacturer_id',
        'category_id' => 'category_id',
        'vat_rate_id' => 'vat_rate_id',
    ];

    /**
     * VAT rate mapping: Subiekt GT rate ID -> percentage
     */
    protected array $defaultVatRates = [
        1 => 23.00, // Standard VAT
        2 => 8.00,  // Reduced VAT
        3 => 5.00,  // Super reduced VAT
        4 => 0.00,  // Zero VAT
        5 => -1,    // Exempt (ZW)
    ];

    protected array $fieldMapping;
    protected array $vatRates;
    protected array $warehouseMappings;
    protected array $priceGroupMappings;

    /**
     * Constructor
     *
     * @param array $config Configuration overrides
     */
    public function __construct(array $config = [])
    {
        $this->fieldMapping = $config['field_mappings'] ?? $this->defaultFieldMapping;
        $this->vatRates = $config['vat_rates'] ?? $this->defaultVatRates;
        $this->warehouseMappings = $config['warehouse_mappings'] ?? [];
        $this->priceGroupMappings = $config['price_group_mappings'] ?? [];
    }

    // ==========================================
    // SUBIEKT GT -> PPM TRANSFORMATION
    // ==========================================

    /**
     * Transform single Subiekt GT product to PPM format
     *
     * @param object $subiektProduct Product data from SubiektQueryBuilder
     * @param array|null $prices Optional price data for all price types
     * @param array|null $stock Optional stock data for all warehouses
     * @return array PPM-compatible product data
     */
    public function subiektToPPM(
        object $subiektProduct,
        ?array $prices = null,
        ?array $stock = null
    ): array {
        $ppmData = [
            // Basic identification
            'external_id' => (string) $subiektProduct->id,
            'sku' => $subiektProduct->sku ?? null,
            'ean' => $subiektProduct->ean ?? null,

            // Product info
            'name' => $this->cleanString($subiektProduct->name ?? ''),
            'short_description' => $this->cleanString($subiektProduct->fiscal_name ?? ''),
            'long_description' => $this->cleanString($subiektProduct->description ?? ''),

            // Physical properties
            'weight' => $this->parseDecimal($subiektProduct->weight ?? $subiektProduct->weight_gross ?? 0),

            // Status
            'is_active' => (bool) ($subiektProduct->is_active ?? true),

            // Timestamps from ERP
            'erp_updated_at' => $subiektProduct->updated_at ?? null,
            'erp_created_at' => $subiektProduct->created_at ?? null,

            // ERP references (for mapping)
            'erp_manufacturer_id' => $subiektProduct->manufacturer_id ?? null,
            'erp_category_id' => $subiektProduct->category_id ?? null,
            'erp_vat_rate_id' => $subiektProduct->vat_rate_id ?? null,

            // Default price (from query)
            'price_net' => $this->parseDecimal($subiektProduct->price_net ?? 0),
            'price_gross' => $this->parseDecimal($subiektProduct->price_gross ?? 0),

            // Default stock (from query)
            'stock_quantity' => (int) ($subiektProduct->stock_quantity ?? 0),

            // Tax rate
            'tax_rate' => $this->getVatRateValue($subiektProduct->vat_rate_id ?? 1),

            // === ETAP_08 FAZA 7: Extended fields from Subiekt GT ===
            // Text fields (tw_Pole1-5, tw_Pole8, tw_Uwagi)
            'Pole1' => $subiektProduct->Pole1 ?? $subiektProduct->pole1 ?? null,
            'Pole2' => $subiektProduct->Pole2 ?? $subiektProduct->pole2 ?? null,
            'Pole3' => $subiektProduct->Pole3 ?? $subiektProduct->pole3 ?? null,
            'Pole4' => $subiektProduct->Pole4 ?? $subiektProduct->pole4 ?? null,
            'Pole5' => $subiektProduct->Pole5 ?? $subiektProduct->pole5 ?? null,
            // Pole6, Pole7 - unused (reserved for future use)
            'Pole8' => $subiektProduct->Pole8 ?? $subiektProduct->pole8 ?? null,  // parent_sku for variants
            'Notes' => $subiektProduct->Notes ?? $subiektProduct->notes ?? null,
            // Boolean flags
            'ShopInternet' => $subiektProduct->ShopInternet ?? $subiektProduct->shopInternet ?? null,
            'SplitPayment' => $subiektProduct->SplitPayment ?? $subiektProduct->splitPayment ?? null,
            // Manufacturer and supplier
            'ManufacturerName' => $subiektProduct->ManufacturerName ?? $subiektProduct->manufacturerName ?? null,
            'SupplierCode' => $subiektProduct->SupplierCode ?? $subiektProduct->supplierCode ?? null,
            // Contractor IDs (FK to kh__Kontrahent) - for BusinessPartner mapping
            'SupplierContractorId' => $subiektProduct->SupplierContractorId ?? $subiektProduct->supplierContractorId ?? null,
            'ManufacturerContractorId' => $subiektProduct->ManufacturerContractorId ?? $subiektProduct->manufacturerContractorId ?? null,
            'ManufacturerContractorName' => $subiektProduct->ManufacturerContractorName ?? $subiektProduct->manufacturerContractorName ?? null,
        ];

        // Add all prices if provided
        if ($prices !== null) {
            $ppmData['prices'] = $this->transformPrices($prices);
        }

        // Add all stock levels if provided
        if ($stock !== null) {
            $ppmData['stock'] = $this->transformStock($stock);
        }

        return $ppmData;
    }

    /**
     * Transform batch of Subiekt GT products to PPM format
     *
     * @param iterable $subiektProducts Collection/array of products
     * @param array|null $allPrices Prices grouped by product_id
     * @param array|null $allStock Stock grouped by product_id
     * @return array Array of PPM-compatible products
     */
    public function subiektToPPMBatch(
        iterable $subiektProducts,
        ?array $allPrices = null,
        ?array $allStock = null
    ): array {
        $ppmProducts = [];

        foreach ($subiektProducts as $product) {
            $productId = $product->id;

            $prices = $allPrices[$productId] ?? null;
            $stock = $allStock[$productId] ?? null;

            $ppmProducts[] = $this->subiektToPPM($product, $prices, $stock);
        }

        return $ppmProducts;
    }

    /**
     * Transform Subiekt GT product to ProductErpData format
     *
     * @param object $subiektProduct Product from SubiektQueryBuilder
     * @param int $erpConnectionId ERP connection ID
     * @return array Data ready for ProductErpData model
     */
    public function subiektToProductErpData(object $subiektProduct, int $erpConnectionId): array
    {
        return [
            'erp_connection_id' => $erpConnectionId,
            'external_id' => (string) $subiektProduct->id,
            'sku' => $subiektProduct->sku ?? null,
            'ean' => $subiektProduct->ean ?? null,
            'name' => $this->cleanString($subiektProduct->name ?? ''),
            'short_description' => $this->cleanString($subiektProduct->fiscal_name ?? ''),
            'long_description' => $this->cleanString($subiektProduct->description ?? ''),
            'weight' => $this->parseDecimal($subiektProduct->weight ?? $subiektProduct->weight_gross ?? 0),
            'tax_rate' => $this->getVatRateValue($subiektProduct->vat_rate_id ?? 1),
            'is_active' => (bool) ($subiektProduct->is_active ?? true),
            'sync_status' => ProductErpData::STATUS_SYNCED,
            'last_pull_at' => now(),
            'last_sync_at' => now(),
            'external_data' => [
                'subiekt_id' => $subiektProduct->id,
                'manufacturer_id' => $subiektProduct->manufacturer_id ?? null,
                'category_id' => $subiektProduct->category_id ?? null,
                'vat_rate_id' => $subiektProduct->vat_rate_id ?? null,
                'price_net' => $subiektProduct->price_net ?? null,
                'price_gross' => $subiektProduct->price_gross ?? null,
                'stock_quantity' => $subiektProduct->stock_quantity ?? 0,
                'updated_at' => $subiektProduct->updated_at ?? null,
            ],
        ];
    }

    // ==========================================
    // PPM -> SUBIEKT GT TRANSFORMATION
    // ==========================================

    /**
     * Transform PPM product to Subiekt GT format
     *
     * NOTE: This is primarily for validation and comparison.
     * Writing to Subiekt GT requires Sfera API or REST wrapper.
     *
     * @param Product $product PPM Product model
     * @param ProductErpData|null $erpData ERP-specific data
     * @return array Subiekt GT-compatible data structure
     */
    public function ppmToSubiekt(Product $product, ?ProductErpData $erpData = null): array
    {
        // Use ERP-specific data if available, otherwise fall back to product defaults
        $name = $erpData?->name ?? $product->name;
        $sku = $erpData?->sku ?? $product->sku;
        $ean = $erpData?->ean ?? $product->ean;
        $description = $erpData?->long_description ?? $product->description;
        $weight = $erpData?->weight ?? $product->weight;
        $taxRate = $erpData?->tax_rate ?? $product->tax_rate ?? 23.00;

        return [
            'tw_Symbol' => $sku,
            'tw_Nazwa' => $this->truncateString($name, 100),
            'tw_NazwaFiskalna' => $this->truncateString($name, 40),
            'tw_Opis' => $description,
            'tw_KodKreskowy' => $ean,
            'tw_WagaBrutto' => $weight,
            'tw_Aktywny' => $product->is_active ? 1 : 0,
            'tw_StawkaVatSprzId' => $this->getVatRateId($taxRate),
        ];
    }

    // ==========================================
    // PRICE TRANSFORMATION
    // ==========================================

    /**
     * Transform Subiekt GT prices to PPM format
     *
     * @param array $prices Array of price objects from SubiektQueryBuilder
     * @return array PPM-formatted prices
     */
    public function transformPrices(array $prices): array
    {
        $ppmPrices = [];

        // DEBUG: Log raw prices data from REST API
        Log::debug('SubiektDataTransformer::transformPrices RAW DATA', [
            'prices_count' => count($prices),
            'first_item' => !empty($prices) ? $prices[0] : null,
            'first_item_keys' => !empty($prices) && is_array($prices[0]) ? array_keys($prices[0]) : 'not_array',
        ]);

        foreach ($prices as $price) {
            // Support both object (from QueryBuilder) and array (from REST API)
            // REST API returns PascalCase: PriceLevel, PriceLevelName, PriceNet, PriceGross
            $priceTypeId = is_array($price)
                ? ($price['PriceLevel'] ?? $price['priceLevel'] ?? $price['price_type_id'] ?? 0)
                : ($price->PriceLevel ?? $price->priceLevel ?? $price->price_type_id ?? 0);
            $priceTypeCode = is_array($price)
                ? ($price['PriceLevelName'] ?? $price['priceLevelName'] ?? $price['price_type_code'] ?? null)
                : ($price->PriceLevelName ?? $price->priceLevelName ?? $price->price_type_code ?? null);
            $priceNet = is_array($price)
                ? ($price['PriceNet'] ?? $price['priceNet'] ?? $price['price_net'] ?? 0)
                : ($price->PriceNet ?? $price->priceNet ?? $price->price_net ?? 0);
            $priceGross = is_array($price)
                ? ($price['PriceGross'] ?? $price['priceGross'] ?? $price['price_gross'] ?? 0)
                : ($price->PriceGross ?? $price->priceGross ?? $price->price_gross ?? 0);

            $ppmPriceGroupId = $this->mapPriceTypeToGroup($priceTypeId);

            if ($ppmPriceGroupId !== null) {
                $ppmPrices[$ppmPriceGroupId] = [
                    'price_group_id' => $ppmPriceGroupId,
                    'erp_price_type_id' => $priceTypeId,
                    'erp_price_type_code' => $priceTypeCode,
                    'price_net' => $this->parseDecimal($priceNet),
                    'price_gross' => $this->parseDecimal($priceGross),
                ];
            }
        }

        return $ppmPrices;
    }

    // ==========================================
    // STOCK TRANSFORMATION
    // ==========================================

    /**
     * Transform Subiekt GT stock to PPM format
     *
     * @param array $stockData Array of stock objects from SubiektQueryBuilder
     * @return array PPM-formatted stock per warehouse
     */
    public function transformStock(array $stockData): array
    {
        $ppmStock = [];

        // DEBUG: Log raw stock data from REST API
        Log::debug('SubiektDataTransformer::transformStock RAW DATA', [
            'stockData_count' => count($stockData),
            'first_item' => !empty($stockData) ? $stockData[0] : null,
            'first_item_keys' => !empty($stockData) && is_array($stockData[0]) ? array_keys($stockData[0]) : 'not_array',
        ]);

        foreach ($stockData as $stock) {
            // Support both object (from QueryBuilder) and array (from REST API)
            // REST API returns PascalCase: WarehouseId, WarehouseName, Quantity, Reserved
            $warehouseId = is_array($stock)
                ? ($stock['WarehouseId'] ?? $stock['warehouseId'] ?? $stock['warehouse_id'] ?? 0)
                : ($stock->WarehouseId ?? $stock->warehouseId ?? $stock->warehouse_id ?? 0);
            $warehouseCode = is_array($stock)
                ? ($stock['WarehouseName'] ?? $stock['warehouseName'] ?? $stock['warehouse_code'] ?? null)
                : ($stock->WarehouseName ?? $stock->warehouseName ?? $stock->warehouse_code ?? null);
            $quantity = is_array($stock)
                ? ($stock['Quantity'] ?? $stock['quantity'] ?? 0)
                : ($stock->Quantity ?? $stock->quantity ?? 0);
            $reserved = is_array($stock)
                ? ($stock['Reserved'] ?? $stock['reserved'] ?? 0)
                : ($stock->Reserved ?? $stock->reserved ?? 0);
            $available = is_array($stock)
                ? ($stock['available'] ?? $quantity - $reserved)
                : ($stock->available ?? $stock->quantity ?? 0);

            $ppmWarehouseId = $this->mapWarehouseToPPM($warehouseId);

            if ($ppmWarehouseId !== null) {
                $ppmStock[$ppmWarehouseId] = [
                    'warehouse_id' => $ppmWarehouseId,
                    'erp_warehouse_id' => $warehouseId,
                    'erp_warehouse_code' => $warehouseCode,
                    'quantity' => (int) $quantity,
                    'reserved' => (int) $reserved,
                    'available' => (int) $available,
                ];
            }
        }

        return $ppmStock;
    }

    // ==========================================
    // MAPPING HELPERS
    // ==========================================

    /**
     * Map Subiekt GT warehouse ID to PPM warehouse ID
     *
     * @param int $subiektWarehouseId Subiekt GT warehouse ID
     * @return int|null PPM warehouse ID or null if not mapped
     */
    public function mapWarehouseToPPM(int $subiektWarehouseId): ?int
    {
        // Reverse lookup: find PPM ID by Subiekt ID
        foreach ($this->warehouseMappings as $ppmId => $subiektId) {
            if ($subiektId == $subiektWarehouseId) {
                return (int) $ppmId;
            }
        }

        // Fallback: return same ID (1:1 mapping) - allows all warehouses to display
        return $subiektWarehouseId;
    }

    /**
     * Map PPM warehouse ID to Subiekt GT warehouse ID
     *
     * @param int $ppmWarehouseId PPM warehouse ID
     * @return int|null Subiekt GT warehouse ID or null if not mapped
     */
    public function mapWarehouseToSubiekt(int $ppmWarehouseId): ?int
    {
        return $this->warehouseMappings[$ppmWarehouseId] ?? null;
    }

    /**
     * Map Subiekt GT price type to PPM price group
     *
     * @param int $subiektPriceTypeId Subiekt GT price type ID
     * @return int|null PPM price group ID or null if not mapped
     */
    public function mapPriceTypeToGroup(int $subiektPriceTypeId): ?int
    {
        // Reverse lookup
        foreach ($this->priceGroupMappings as $ppmId => $subiektId) {
            if ($subiektId == $subiektPriceTypeId) {
                return (int) $ppmId;
            }
        }

        // Fallback: return same ID (1:1 mapping) - allows all price levels to display
        return $subiektPriceTypeId;
    }

    /**
     * Map PPM price group to Subiekt GT price type
     *
     * @param int $ppmPriceGroupId PPM price group ID
     * @return int|null Subiekt GT price type ID or null if not mapped
     */
    public function mapPriceGroupToSubiekt(int $ppmPriceGroupId): ?int
    {
        return $this->priceGroupMappings[$ppmPriceGroupId] ?? null;
    }

    /**
     * Get VAT rate percentage from Subiekt GT rate ID
     *
     * @param int $vatRateId Subiekt GT VAT rate ID
     * @return float VAT percentage
     */
    public function getVatRateValue(int $vatRateId): float
    {
        return $this->vatRates[$vatRateId] ?? 23.00;
    }

    /**
     * Get Subiekt GT VAT rate ID from percentage
     *
     * @param float $vatRate VAT percentage
     * @return int Subiekt GT VAT rate ID
     */
    public function getVatRateId(float $vatRate): int
    {
        $flipped = array_flip($this->vatRates);
        return $flipped[$vatRate] ?? 1; // Default to standard VAT
    }

    // ==========================================
    // STRING HELPERS
    // ==========================================

    /**
     * Clean string from problematic characters
     *
     * @param string|null $value Input string
     * @return string Cleaned string
     */
    protected function cleanString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Remove null bytes and trim
        $value = str_replace("\0", '', $value);
        $value = trim($value);

        return $value;
    }

    /**
     * Truncate string to max length
     *
     * @param string|null $value Input string
     * @param int $maxLength Maximum length
     * @return string Truncated string
     */
    protected function truncateString(?string $value, int $maxLength): string
    {
        if ($value === null) {
            return '';
        }

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    /**
     * Parse decimal value safely
     *
     * @param mixed $value Input value
     * @return float Parsed decimal
     */
    protected function parseDecimal($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Handle comma as decimal separator
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9.\-]/', '', $value);
            return (float) $value;
        }

        return 0.0;
    }

    // ==========================================
    // CONFIGURATION
    // ==========================================

    /**
     * Set warehouse mappings
     *
     * @param array $mappings PPM warehouse ID => Subiekt warehouse ID
     * @return self
     */
    public function setWarehouseMappings(array $mappings): self
    {
        $this->warehouseMappings = $mappings;
        return $this;
    }

    /**
     * Set price group mappings
     *
     * @param array $mappings PPM price group ID => Subiekt price type ID
     * @return self
     */
    public function setPriceGroupMappings(array $mappings): self
    {
        $this->priceGroupMappings = $mappings;
        return $this;
    }

    /**
     * Set VAT rate mappings
     *
     * @param array $vatRates Subiekt VAT rate ID => percentage
     * @return self
     */
    public function setVatRates(array $vatRates): self
    {
        $this->vatRates = $vatRates;
        return $this;
    }
}
