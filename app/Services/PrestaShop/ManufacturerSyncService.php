<?php

namespace App\Services\PrestaShop;

use App\Models\Manufacturer;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ManufacturerSyncService - Sync manufacturers between PPM and PrestaShop
 *
 * ETAP 07g: Manufacturer Sync System
 *
 * Features:
 * - Import manufacturers from PrestaShop
 * - Push manufacturers to PrestaShop (create/update)
 * - Logo sync (upload/download)
 * - Per-shop sync status tracking
 *
 * @package App\Services\PrestaShop
 */
class ManufacturerSyncService
{
    protected ManufacturerTransformer $transformer;

    public function __construct(ManufacturerTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Import all manufacturers from PrestaShop shop
     *
     * @param PrestaShopShop $shop Target shop
     * @param bool $includeImages Also download logos
     * @param int $defaultLangId Language ID for text extraction
     * @return array ['imported' => int, 'updated' => int, 'errors' => array]
     */
    public function importFromPrestaShop(
        PrestaShopShop $shop,
        bool $includeImages = false,
        int $defaultLangId = 1
    ): array {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $client = $shop->getApiClient();
            $psManufacturers = $client->getManufacturers(['display' => 'full']);

            Log::info('[MANUFACTURER SYNC] Starting import from PrestaShop', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'manufacturer_count' => count($psManufacturers),
            ]);

            foreach ($psManufacturers as $psData) {
                try {
                    $result = $this->importSingleManufacturer($shop, $psData, $includeImages, $defaultLangId);

                    if ($result['created']) {
                        $results['imported']++;
                    } elseif ($result['updated']) {
                        $results['updated']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'ps_id' => $psData['id'] ?? 'unknown',
                        'name' => $psData['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[MANUFACTURER SYNC] Import failed for single manufacturer', [
                        'ps_id' => $psData['id'] ?? null,
                        'name' => $psData['name'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[MANUFACTURER SYNC] Import completed', [
                'shop_id' => $shop->id,
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => count($results['errors']),
            ]);

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER SYNC] Import failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            $results['errors'][] = [
                'ps_id' => null,
                'name' => null,
                'error' => 'Import failed: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Import single manufacturer from PrestaShop data
     *
     * @param PrestaShopShop $shop
     * @param array $psData PrestaShop manufacturer data
     * @param bool $includeImage
     * @param int $defaultLangId
     * @return array ['created' => bool, 'updated' => bool, 'manufacturer' => Manufacturer|null]
     */
    protected function importSingleManufacturer(
        PrestaShopShop $shop,
        array $psData,
        bool $includeImage = false,
        int $defaultLangId = 1
    ): array {
        $psManufacturerId = (int) ($psData['id'] ?? 0);
        $name = $psData['name'] ?? '';

        if (empty($name) || $psManufacturerId === 0) {
            throw new \InvalidArgumentException('Invalid manufacturer data: missing name or ID');
        }

        // Transform PrestaShop data to PPM format
        $ppmData = $this->transformer->transformFromPrestaShop($psData, $defaultLangId);

        // Check if manufacturer already exists (by pivot mapping)
        $existingManufacturer = Manufacturer::whereHas('shops', function ($query) use ($shop, $psManufacturerId) {
            $query->where('prestashop_shops.id', $shop->id)
                  ->where('manufacturer_shop.ps_manufacturer_id', $psManufacturerId);
        })->first();

        // Or check by name/code
        if (!$existingManufacturer) {
            $existingManufacturer = Manufacturer::where('name', $name)
                ->orWhere('code', $ppmData['code'])
                ->first();
        }

        $created = false;
        $updated = false;

        if ($existingManufacturer) {
            // Update existing
            $existingManufacturer->update($ppmData);
            $manufacturer = $existingManufacturer;
            $updated = true;
        } else {
            // Create new
            $manufacturer = Manufacturer::create($ppmData);
            $created = true;
        }

        // Update shop pivot
        $manufacturer->assignToShop($shop->id, $psManufacturerId);
        $manufacturer->updateSyncStatus($shop->id, 'synced', $psManufacturerId);

        // Import logo if requested
        if ($includeImage) {
            $this->importLogoFromPrestaShop($manufacturer, $shop);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'manufacturer' => $manufacturer,
        ];
    }

    /**
     * Sync single manufacturer to PrestaShop
     *
     * @param Manufacturer $manufacturer PPM Manufacturer
     * @param PrestaShopShop $shop Target shop
     * @param bool $syncLogo Also sync logo image
     * @return array ['success' => bool, 'action' => string, 'ps_id' => int|null, 'error' => string|null]
     */
    public function syncToPrestaShop(
        Manufacturer $manufacturer,
        PrestaShopShop $shop,
        bool $syncLogo = true
    ): array {
        $result = [
            'success' => false,
            'action' => 'none',
            'ps_id' => null,
            'error' => null,
        ];

        try {
            // Validate before sync
            $validation = $this->transformer->validateForSync($manufacturer);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException(implode('; ', $validation['errors']));
            }

            $client = $shop->getApiClient();
            $psManufacturerId = $manufacturer->getPsIdForShop($shop->id);

            // Transform PPM data to PrestaShop format
            $psData = $this->transformer->transformForPrestaShop($manufacturer);

            if ($psManufacturerId) {
                // UPDATE existing manufacturer
                Log::info('[MANUFACTURER SYNC] Updating manufacturer in PrestaShop', [
                    'manufacturer_id' => $manufacturer->id,
                    'ps_manufacturer_id' => $psManufacturerId,
                    'shop_id' => $shop->id,
                ]);

                $client->updateManufacturer($psManufacturerId, $psData);
                $result['action'] = 'updated';
                $result['ps_id'] = $psManufacturerId;

            } else {
                // CREATE new manufacturer
                Log::info('[MANUFACTURER SYNC] Creating manufacturer in PrestaShop', [
                    'manufacturer_id' => $manufacturer->id,
                    'name' => $manufacturer->name,
                    'shop_id' => $shop->id,
                ]);

                $response = $client->createManufacturer($psData);
                $psManufacturerId = (int) ($response['manufacturer']['id'] ?? 0);

                if ($psManufacturerId === 0) {
                    throw new \RuntimeException('PrestaShop did not return manufacturer ID');
                }

                $result['action'] = 'created';
                $result['ps_id'] = $psManufacturerId;

                // Update pivot with new PS ID
                $manufacturer->assignToShop($shop->id, $psManufacturerId);
            }

            // Update sync status
            $manufacturer->updateSyncStatus($shop->id, 'synced', $psManufacturerId);
            $manufacturer->clearSyncError($shop->id);

            // Sync logo if requested
            if ($syncLogo && $manufacturer->hasLogo()) {
                $logoSynced = $this->syncLogoToPrestaShop($manufacturer, $shop);
                if (!$logoSynced) {
                    Log::warning('[MANUFACTURER SYNC] Logo sync failed', [
                        'manufacturer_id' => $manufacturer->id,
                        'shop_id' => $shop->id,
                    ]);
                }
            }

            $result['success'] = true;

            Log::info('[MANUFACTURER SYNC] Sync completed', [
                'manufacturer_id' => $manufacturer->id,
                'action' => $result['action'],
                'ps_id' => $result['ps_id'],
                'shop_id' => $shop->id,
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();

            $manufacturer->setSyncError($shop->id, $e->getMessage());

            Log::error('[MANUFACTURER SYNC] Sync failed', [
                'manufacturer_id' => $manufacturer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Sync logo from PPM to PrestaShop
     *
     * @param Manufacturer $manufacturer
     * @param PrestaShopShop $shop
     * @return bool Success
     */
    public function syncLogoToPrestaShop(Manufacturer $manufacturer, PrestaShopShop $shop): bool
    {
        try {
            if (!$manufacturer->hasLogo()) {
                Log::debug('[MANUFACTURER SYNC] No logo to sync', [
                    'manufacturer_id' => $manufacturer->id,
                ]);
                return false;
            }

            $psManufacturerId = $manufacturer->getPsIdForShop($shop->id);
            if (!$psManufacturerId) {
                Log::warning('[MANUFACTURER SYNC] Cannot sync logo - no PS ID', [
                    'manufacturer_id' => $manufacturer->id,
                    'shop_id' => $shop->id,
                ]);
                return false;
            }

            $logoPath = storage_path('app/public/' . $manufacturer->logo_path);

            if (!file_exists($logoPath)) {
                Log::warning('[MANUFACTURER SYNC] Logo file not found', [
                    'manufacturer_id' => $manufacturer->id,
                    'logo_path' => $logoPath,
                ]);
                return false;
            }

            $client = $shop->getApiClient();
            $client->uploadManufacturerImage($psManufacturerId, $logoPath);

            $manufacturer->updateLogoSyncStatus($shop->id, true);

            Log::info('[MANUFACTURER SYNC] Logo synced to PrestaShop', [
                'manufacturer_id' => $manufacturer->id,
                'ps_manufacturer_id' => $psManufacturerId,
                'shop_id' => $shop->id,
            ]);

            return true;

        } catch (\Exception $e) {
            $manufacturer->updateLogoSyncStatus($shop->id, false, $e->getMessage());

            Log::error('[MANUFACTURER SYNC] Logo sync failed', [
                'manufacturer_id' => $manufacturer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Import logo from PrestaShop to PPM
     *
     * @param Manufacturer $manufacturer
     * @param PrestaShopShop $shop
     * @return bool Success
     */
    public function importLogoFromPrestaShop(Manufacturer $manufacturer, PrestaShopShop $shop): bool
    {
        try {
            $psManufacturerId = $manufacturer->getPsIdForShop($shop->id);

            if (!$psManufacturerId) {
                Log::debug('[MANUFACTURER SYNC] Cannot import logo - no PS ID', [
                    'manufacturer_id' => $manufacturer->id,
                ]);
                return false;
            }

            $client = $shop->getApiClient();

            // Check if logo exists
            if (!$client->hasManufacturerImage($psManufacturerId)) {
                Log::debug('[MANUFACTURER SYNC] No logo in PrestaShop', [
                    'manufacturer_id' => $manufacturer->id,
                    'ps_manufacturer_id' => $psManufacturerId,
                ]);
                return false;
            }

            // Download logo
            $imageData = $client->downloadManufacturerImage($psManufacturerId);

            if (empty($imageData)) {
                return false;
            }

            // Detect image type and extension
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            $extension = $this->getExtensionFromMimeType($mimeType);

            // Generate filename
            $filename = 'manufacturers/' . Str::slug($manufacturer->name) . '-' . $manufacturer->id . '.' . $extension;

            // Save to storage
            Storage::disk('public')->put($filename, $imageData);

            // Update manufacturer
            $manufacturer->update(['logo_path' => $filename]);
            $manufacturer->updateLogoSyncStatus($shop->id, true);

            Log::info('[MANUFACTURER SYNC] Logo imported from PrestaShop', [
                'manufacturer_id' => $manufacturer->id,
                'ps_manufacturer_id' => $psManufacturerId,
                'shop_id' => $shop->id,
                'filename' => $filename,
                'size' => strlen($imageData),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[MANUFACTURER SYNC] Logo import failed', [
                'manufacturer_id' => $manufacturer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bulk sync multiple manufacturers to shop
     *
     * @param PrestaShopShop $shop Target shop
     * @param array $manufacturerIds Optional: specific manufacturer IDs (empty = all)
     * @param bool $syncLogos Also sync logos
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkSyncToShop(
        PrestaShopShop $shop,
        array $manufacturerIds = [],
        bool $syncLogos = true
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $query = Manufacturer::active();

        if (!empty($manufacturerIds)) {
            $query->whereIn('id', $manufacturerIds);
        }

        $manufacturers = $query->get();

        Log::info('[MANUFACTURER SYNC] Starting bulk sync', [
            'shop_id' => $shop->id,
            'manufacturer_count' => $manufacturers->count(),
        ]);

        foreach ($manufacturers as $manufacturer) {
            $result = $this->syncToPrestaShop($manufacturer, $shop, $syncLogos);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $manufacturer->id,
                    'name' => $manufacturer->name,
                    'error' => $result['error'],
                ];
            }
        }

        Log::info('[MANUFACTURER SYNC] Bulk sync completed', [
            'shop_id' => $shop->id,
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Get extension from MIME type
     *
     * @param string $mimeType
     * @return string
     */
    protected function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        return $map[$mimeType] ?? 'jpg';
    }

    /**
     * Get sync summary for manufacturer across all shops
     *
     * @param Manufacturer $manufacturer
     * @return array
     */
    public function getSyncSummary(Manufacturer $manufacturer): array
    {
        return $manufacturer->getSyncSummary();
    }

    /**
     * Check if manufacturer needs sync to shop
     *
     * @param Manufacturer $manufacturer
     * @param PrestaShopShop $shop
     * @return bool
     */
    public function needsSync(Manufacturer $manufacturer, PrestaShopShop $shop): bool
    {
        $pivot = $manufacturer->shops()
            ->where('prestashop_shops.id', $shop->id)
            ->first()?->pivot;

        if (!$pivot) {
            return true; // Not assigned to shop
        }

        if ($pivot->sync_status !== 'synced') {
            return true;
        }

        // Check if manufacturer was updated after last sync
        if ($pivot->last_synced_at && $manufacturer->updated_at > $pivot->last_synced_at) {
            return true;
        }

        return false;
    }
}
