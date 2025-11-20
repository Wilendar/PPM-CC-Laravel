<?php

namespace App\Services;

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TaxRateService
 *
 * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
 *
 * Business logic service for Tax Rate management with shop-specific overrides
 * and PrestaShop Tax Rule Group mapping validation.
 *
 * Features:
 * - Get available tax rates for a shop (based on PrestaShop mappings)
 * - Validate tax rate against shop mappings
 * - Get PrestaShop Tax Rule Group ID for a given tax rate
 * - Cache tax rate options for performance (15 minutes TTL)
 *
 * @package App\Services
 * @version 1.0
 * @since ETAP_07 FAZA 5.3
 */
class TaxRateService
{
    /**
     * Cache TTL for tax rate options (15 minutes)
     */
    const CACHE_TTL = 900;

    /**
     * Get available tax rate options for a shop
     *
     * Returns array of available tax rates based on PrestaShop tax rule group mappings.
     * Results are cached per shop for performance.
     *
     * @param PrestaShopShop $shop
     * @return array Format: [['rate' => float, 'label' => string, 'prestashop_group_id' => int|null], ...]
     */
    public function getAvailableTaxRatesForShop(PrestaShopShop $shop): array
    {
        // [FAZA 5.2 DEBUG 2025-11-14] Track service calls
        Log::debug('[FAZA 5.2 DEBUG] TaxRateService::getAvailableTaxRatesForShop CALLED', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'tax_rules_group_id_23' => $shop->tax_rules_group_id_23,
            'tax_rules_group_id_8' => $shop->tax_rules_group_id_8,
            'tax_rules_group_id_5' => $shop->tax_rules_group_id_5,
            'tax_rules_group_id_0' => $shop->tax_rules_group_id_0,
        ]);

        $cacheKey = "tax_rates_shop_{$shop->id}";

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($shop) {
            $options = [];

            // 23% VAT - Standard (Poland)
            if ($shop->tax_rules_group_id_23) {
                $options[] = [
                    'rate' => 23.00,
                    'label' => 'VAT 23% (Standard)',
                    'prestashop_group_id' => $shop->tax_rules_group_id_23,
                ];
            }

            // 8% VAT - Reduced (Poland)
            if ($shop->tax_rules_group_id_8) {
                $options[] = [
                    'rate' => 8.00,
                    'label' => 'VAT 8% (Obniżona)',
                    'prestashop_group_id' => $shop->tax_rules_group_id_8,
                ];
            }

            // 5% VAT - Super reduced (Poland)
            if ($shop->tax_rules_group_id_5) {
                $options[] = [
                    'rate' => 5.00,
                    'label' => 'VAT 5% (Super obniżona)',
                    'prestashop_group_id' => $shop->tax_rules_group_id_5,
                ];
            }

            // 0% VAT - Exempt (Poland)
            if ($shop->tax_rules_group_id_0) {
                $options[] = [
                    'rate' => 0.00,
                    'label' => 'VAT 0% (Zwolniona)',
                    'prestashop_group_id' => $shop->tax_rules_group_id_0,
                ];
            }

            Log::debug('TaxRateService: Available rates for shop', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'available_rates' => array_column($options, 'rate'),
            ]);

            return $options;
        });

        // [FAZA 5.2 DEBUG 2025-11-14] Track result
        Log::debug('[FAZA 5.2 DEBUG] TaxRateService result', [
            'shop_id' => $shop->id,
            'options_count' => count($result),
            'options' => $result,
        ]);

        return $result;
    }

    /**
     * Validate tax rate against shop mappings
     *
     * Checks if the provided tax rate is mapped in the shop's PrestaShop
     * Tax Rule Groups configuration.
     *
     * @param float $taxRate Tax rate to validate (e.g., 23.00)
     * @param PrestaShopShop $shop Shop to validate against
     * @return array ['valid' => bool, 'warning' => string|null, 'prestashop_group_id' => int|null]
     */
    public function validateTaxRateForShop(float $taxRate, PrestaShopShop $shop): array
    {
        $availableRates = $this->getAvailableTaxRatesForShop($shop);
        $mappedRates = array_column($availableRates, 'rate');

        // Find matching rate (with precision tolerance)
        $normalizedRate = round($taxRate, 2);
        $matchingRate = null;

        foreach ($availableRates as $option) {
            if (round($option['rate'], 2) === $normalizedRate) {
                $matchingRate = $option;
                break;
            }
        }

        if ($matchingRate) {
            return [
                'valid' => true,
                'warning' => null,
                'prestashop_group_id' => $matchingRate['prestashop_group_id'],
            ];
        }

        // Invalid - rate not mapped
        $ratesString = !empty($mappedRates)
            ? implode('%, ', array_map(fn($r) => number_format($r, 2), $mappedRates)) . '%'
            : 'brak zmapowanych stawek';

        return [
            'valid' => false,
            'warning' => "Stawka VAT {$taxRate}% nie jest zmapowana w ustawieniach sklepu PrestaShop. Dostępne stawki: {$ratesString}",
            'prestashop_group_id' => null,
        ];
    }

    /**
     * Get PrestaShop Tax Rule Group ID for a tax rate
     *
     * Returns the PrestaShop Tax Rule Group ID that corresponds to the
     * given tax rate for this shop, or null if not mapped.
     *
     * @param float $taxRate Tax rate (e.g., 23.00)
     * @param PrestaShopShop $shop Shop instance
     * @return int|null PrestaShop Tax Rule Group ID or null
     */
    public function getPrestaShopTaxRuleGroupId(float $taxRate, PrestaShopShop $shop): ?int
    {
        $normalizedRate = round($taxRate, 2);

        // Direct mapping based on rate
        $mapping = [
            23.00 => $shop->tax_rules_group_id_23,
            8.00 => $shop->tax_rules_group_id_8,
            5.00 => $shop->tax_rules_group_id_5,
            0.00 => $shop->tax_rules_group_id_0,
        ];

        return $mapping[$normalizedRate] ?? null;
    }

    /**
     * Get tax rate options for UI dropdown
     *
     * Returns formatted options ready for HTML <select> dropdown.
     *
     * @param PrestaShopShop $shop
     * @return array Format: [['value' => float, 'label' => string], ...]
     */
    public function getTaxRateOptionsForDropdown(PrestaShopShop $shop): array
    {
        $availableRates = $this->getAvailableTaxRatesForShop($shop);

        return array_map(function ($option) {
            return [
                'value' => $option['rate'],
                'label' => $option['label'],
            ];
        }, $availableRates);
    }

    /**
     * Validate product tax rate for all exported shops
     *
     * Checks if the product's tax rate (or shop-specific override) is valid
     * for all shops it's exported to. Returns array of validation results.
     *
     * @param Product $product
     * @return array [shopId => ['shop_name' => string, 'valid' => bool, 'warning' => string|null], ...]
     */
    public function validateProductTaxRateForAllShops(Product $product): array
    {
        $results = [];

        // Get all shop data for this product
        $shopDataRecords = ProductShopData::where('product_id', $product->id)
            ->with('shop')
            ->get();

        foreach ($shopDataRecords as $shopData) {
            if (!$shopData->shop) {
                continue;
            }

            $effectiveTaxRate = $shopData->getEffectiveTaxRate();
            $validation = $this->validateTaxRateForShop($effectiveTaxRate, $shopData->shop);

            $results[$shopData->shop_id] = [
                'shop_name' => $shopData->shop->name,
                'effective_tax_rate' => $effectiveTaxRate,
                'tax_rate_source' => $shopData->getTaxRateSourceType(),
                'valid' => $validation['valid'],
                'warning' => $validation['warning'],
                'prestashop_group_id' => $validation['prestashop_group_id'],
            ];
        }

        return $results;
    }

    /**
     * Clear cached tax rate options for a shop
     *
     * Use this when shop's tax rule group mappings are updated.
     *
     * @param PrestaShopShop $shop
     * @return bool
     */
    public function clearCacheForShop(PrestaShopShop $shop): bool
    {
        $cacheKey = "tax_rates_shop_{$shop->id}";
        Cache::forget($cacheKey);

        Log::info('TaxRateService: Cache cleared for shop', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
        ]);

        return true;
    }

    /**
     * Get all standard Poland VAT rates
     *
     * Returns standard Poland VAT rates for reference/initialization.
     *
     * @return array
     */
    public static function getStandardPolandVATRates(): array
    {
        return [
            23.00 => 'VAT 23% (Standard)',
            8.00 => 'VAT 8% (Obniżona)',
            5.00 => 'VAT 5% (Super obniżona)',
            0.00 => 'VAT 0% (Zwolniona)',
        ];
    }
}
