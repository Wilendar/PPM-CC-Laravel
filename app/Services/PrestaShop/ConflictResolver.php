<?php

namespace App\Services\PrestaShop;

use App\Models\ProductShopData;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

/**
 * Conflict Resolver Service
 *
 * PROBLEM 9.3: Conflict Resolution System (ETAP_07_Prestashop_API.md)
 *
 * Resolve conflicts between PPM and PrestaShop data during pull operations.
 * Setting 'sync.conflict_resolution' controls strategy:
 * - 'ppm_wins': PPM data stays, ignore PrestaShop changes
 * - 'prestashop_wins': PrestaShop data overwrites PPM
 * - 'newest_wins': Compare timestamps, newest data wins
 * - 'manual': Detect conflicts, flag for manual resolution
 *
 * USAGE:
 * $resolver = new ConflictResolver();
 * $result = $resolver->resolve($ppmData, $prestashopData);
 * if ($result['should_update']) {
 *     $shopData->update($result['data']);
 * } else {
 *     // Store conflicts for manual resolution
 * }
 *
 * @package App\Services\PrestaShop
 * @since 2025-11-13
 */
class ConflictResolver
{
    /**
     * Resolve conflict between PPM and PrestaShop data
     *
     * @param ProductShopData $ppmData PPM database record
     * @param array $psData PrestaShop API response data
     * @return array ['should_update' => bool, 'data' => array|null, 'reason' => string, 'conflicts' => array|null]
     */
    public function resolve(ProductShopData $ppmData, array $psData): array
    {
        $strategy = SystemSetting::get('sync.conflict_resolution', 'ppm_wins');

        Log::debug('ConflictResolver CALLED', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
            'strategy' => $strategy,
        ]);

        return match($strategy) {
            'ppm_wins' => $this->ppmWins($ppmData, $psData),
            'prestashop_wins' => $this->prestashopWins($ppmData, $psData),
            'newest_wins' => $this->newestWins($ppmData, $psData),
            'manual' => $this->manual($ppmData, $psData),
            default => $this->ppmWins($ppmData, $psData),
        };
    }

    /**
     * Strategy: PPM data stays, ignore PrestaShop changes
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function ppmWins(ProductShopData $ppmData, array $psData): array
    {
        Log::debug('ConflictResolver ppmWins - keeping PPM data', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
        ]);

        return [
            'should_update' => false,
            'data' => null,
            'reason' => 'PPM wins strategy - keeping PPM data',
            'conflicts' => null,
        ];
    }

    /**
     * Strategy: PrestaShop data overwrites PPM
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function prestashopWins(ProductShopData $ppmData, array $psData): array
    {
        Log::debug('ConflictResolver prestashopWins - updating from PrestaShop', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
        ]);

        return [
            'should_update' => true,
            'data' => $this->normalizePrestaShopData($psData),
            'reason' => 'PrestaShop wins strategy - updating from PrestaShop',
            'conflicts' => null,
        ];
    }

    /**
     * Strategy: Compare timestamps, newest data wins
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function newestWins(ProductShopData $ppmData, array $psData): array
    {
        $ppmTimestamp = $ppmData->updated_at;
        $psTimestamp = isset($psData['date_upd']) ? strtotime($psData['date_upd']) : 0;

        Log::debug('ConflictResolver newestWins - comparing timestamps', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
            'ppm_timestamp' => $ppmTimestamp->toDateTimeString(),
            'ps_timestamp' => $psTimestamp > 0 ? date('Y-m-d H:i:s', $psTimestamp) : 'N/A',
        ]);

        if ($psTimestamp > $ppmTimestamp->timestamp) {
            Log::debug('ConflictResolver newestWins - PrestaShop data is newer', [
                'product_id' => $ppmData->product_id,
                'shop_id' => $ppmData->shop_id,
            ]);

            return [
                'should_update' => true,
                'data' => $this->normalizePrestaShopData($psData),
                'reason' => 'PrestaShop data is newer (PS: ' . date('Y-m-d H:i:s', $psTimestamp) . ', PPM: ' . $ppmTimestamp->toDateTimeString() . ')',
                'conflicts' => null,
            ];
        }

        Log::debug('ConflictResolver newestWins - PPM data is newer', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
        ]);

        return [
            'should_update' => false,
            'data' => null,
            'reason' => 'PPM data is newer (PPM: ' . $ppmTimestamp->toDateTimeString() . ', PS: ' . date('Y-m-d H:i:s', $psTimestamp) . ')',
            'conflicts' => null,
        ];
    }

    /**
     * Strategy: Detect conflicts, flag for manual resolution
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function manual(ProductShopData $ppmData, array $psData): array
    {
        $conflicts = $this->detectConflicts($ppmData, $psData);

        Log::debug('ConflictResolver manual - detecting conflicts', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
            'conflicts_count' => count($conflicts),
        ]);

        if (empty($conflicts)) {
            Log::debug('ConflictResolver manual - no conflicts detected, safe to update', [
                'product_id' => $ppmData->product_id,
                'shop_id' => $ppmData->shop_id,
            ]);

            return [
                'should_update' => true,
                'data' => $this->normalizePrestaShopData($psData),
                'reason' => 'No conflicts detected - safe to update',
                'conflicts' => null,
            ];
        }

        Log::warning('ConflictResolver manual - conflicts detected, manual resolution required', [
            'product_id' => $ppmData->product_id,
            'shop_id' => $ppmData->shop_id,
            'conflicts' => $conflicts,
        ]);

        return [
            'should_update' => false,
            'data' => null,
            'reason' => 'Conflicts detected - manual resolution required (' . count($conflicts) . ' fields)',
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Detect conflicts between PPM and PrestaShop data
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array Array of conflicts with field name, PPM value, PrestaShop value
     */
    private function detectConflicts(ProductShopData $ppmData, array $psData): array
    {
        $conflicts = [];

        // Compare name
        $psName = data_get($psData, 'name.0.value') ?? data_get($psData, 'name');
        if ($ppmData->name && $ppmData->name !== $psName) {
            $conflicts['name'] = [
                'field' => 'name',
                'ppm' => $ppmData->name,
                'prestashop' => $psName,
            ];
        }

        // Compare slug (link_rewrite)
        $psSlug = data_get($psData, 'link_rewrite.0.value') ?? data_get($psData, 'link_rewrite');
        if ($ppmData->slug && $ppmData->slug !== $psSlug) {
            $conflicts['slug'] = [
                'field' => 'slug',
                'ppm' => $ppmData->slug,
                'prestashop' => $psSlug,
            ];
        }

        // Compare short description
        $psShortDesc = data_get($psData, 'description_short.0.value') ?? data_get($psData, 'description_short');
        if ($ppmData->short_description && $ppmData->short_description !== $psShortDesc) {
            $conflicts['short_description'] = [
                'field' => 'short_description',
                'ppm' => substr($ppmData->short_description, 0, 100) . '...',
                'prestashop' => substr($psShortDesc ?? '', 0, 100) . '...',
            ];
        }

        // Compare long description
        $psLongDesc = data_get($psData, 'description.0.value') ?? data_get($psData, 'description');
        if ($ppmData->long_description && $ppmData->long_description !== $psLongDesc) {
            $conflicts['long_description'] = [
                'field' => 'long_description',
                'ppm' => substr($ppmData->long_description, 0, 100) . '...',
                'prestashop' => substr($psLongDesc ?? '', 0, 100) . '...',
            ];
        }

        // Compare active status
        $psActive = data_get($psData, 'active') === '1' || data_get($psData, 'active') === 1;
        if ($ppmData->is_active !== null && (bool)$ppmData->is_active !== $psActive) {
            $conflicts['is_active'] = [
                'field' => 'is_active',
                'ppm' => $ppmData->is_active ? 'Active' : 'Inactive',
                'prestashop' => $psActive ? 'Active' : 'Inactive',
            ];
        }

        // Compare weight
        $psWeight = data_get($psData, 'weight');
        if ($ppmData->weight !== null && $psWeight !== null && (float)$ppmData->weight !== (float)$psWeight) {
            $conflicts['weight'] = [
                'field' => 'weight',
                'ppm' => $ppmData->weight,
                'prestashop' => $psWeight,
            ];
        }

        // Compare EAN
        $psEan = data_get($psData, 'ean13') ?? data_get($psData, 'ean');
        if ($ppmData->ean && $ppmData->ean !== $psEan) {
            $conflicts['ean'] = [
                'field' => 'ean',
                'ppm' => $ppmData->ean,
                'prestashop' => $psEan,
            ];
        }

        return $conflicts;
    }

    /**
     * Normalize PrestaShop API data to PPM format
     *
     * @param array $psData Raw PrestaShop API response
     * @return array Normalized data for ProductShopData update
     */
    private function normalizePrestaShopData(array $psData): array
    {
        return [
            'name' => data_get($psData, 'name.0.value') ?? data_get($psData, 'name'),
            'slug' => data_get($psData, 'link_rewrite.0.value') ?? data_get($psData, 'link_rewrite'),
            'short_description' => data_get($psData, 'description_short.0.value') ?? data_get($psData, 'description_short'),
            'long_description' => data_get($psData, 'description.0.value') ?? data_get($psData, 'description'),
            'is_active' => data_get($psData, 'active') === '1' || data_get($psData, 'active') === 1,
            'weight' => data_get($psData, 'weight'),
            'ean' => data_get($psData, 'ean13') ?? data_get($psData, 'ean'),
            'last_pulled_at' => now(),
        ];
    }
}
