<?php

namespace App\Services\PrestaShop;

use App\Models\BusinessPartner;
use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ImporterSyncService - Sync importers between PPM and PrestaShop suppliers
 *
 * ETAP 08: Importer → PS Supplier Sync
 *
 * PPM Importer (BusinessPartner type='importer') maps to PrestaShop Supplier entity.
 * PrestaShop suppliers and manufacturers have identical XML structure but different API endpoints.
 *
 * Features:
 * - Import PS suppliers → PPM importers (BusinessPartner)
 * - Push PPM importers → PS suppliers (create/update)
 * - Logo sync (upload/download)
 * - Per-shop sync status tracking via pivot (business_partner_shop)
 *
 * Key differences vs ManufacturerSyncService:
 * - Endpoint: /suppliers (not /manufacturers)
 * - Pivot field: ps_supplier_id (not ps_manufacturer_id)
 * - Model: BusinessPartner with type='importer'
 *
 * @package App\Services\PrestaShop
 */
class ImporterSyncService
{
    protected ManufacturerTransformer $transformer;

    public function __construct(ManufacturerTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Import all suppliers from PrestaShop shop as PPM importers
     *
     * @param PrestaShopShop $shop Target shop
     * @param bool $includeImages Also download logos
     * @param int|null $defaultLangId Language ID for text extraction
     * @return array ['imported' => int, 'updated' => int, 'errors' => array]
     */
    public function importFromPrestaShop(
        PrestaShopShop $shop,
        bool $includeImages = false,
        ?int $defaultLangId = 1
    ): array {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $client = $shop->getApiClient();
            $psSuppliers = $client->getSuppliers(['display' => 'full']);

            Log::info('[IMPORTER SYNC] Starting import from PrestaShop', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'supplier_count' => count($psSuppliers),
            ]);

            foreach ($psSuppliers as $psData) {
                try {
                    $result = $this->importSingleImporter($shop, $psData, $includeImages, $defaultLangId);

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

                    Log::error('[IMPORTER SYNC] Import failed for single supplier', [
                        'ps_id' => $psData['id'] ?? null,
                        'name' => $psData['name'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[IMPORTER SYNC] Import completed', [
                'shop_id' => $shop->id,
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => count($results['errors']),
            ]);

        } catch (\Exception $e) {
            Log::error('[IMPORTER SYNC] Import failed', [
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
     * Import single supplier from PrestaShop as PPM importer
     */
    protected function importSingleImporter(
        PrestaShopShop $shop,
        array $psData,
        bool $includeImage = false,
        ?int $defaultLangId = 1
    ): array {
        $psSupplierId = (int) ($psData['id'] ?? 0);
        $name = $psData['name'] ?? '';

        if (empty($name) || $psSupplierId === 0) {
            throw new \InvalidArgumentException('Invalid supplier data: missing name or ID');
        }

        // Transform PrestaShop data to PPM format (reuse manufacturer transformer - same structure)
        $ppmData = $this->transformer->transformFromPrestaShop($psData, $defaultLangId ?? 1);

        // Add BusinessPartner-specific fields
        $ppmData['type'] = 'importer';

        // Check if importer already exists (by pivot mapping)
        $existingImporter = BusinessPartner::whereHas('shops', function ($query) use ($shop, $psSupplierId) {
            $query->where('prestashop_shops.id', $shop->id)
                  ->where('business_partner_shop.ps_supplier_id', $psSupplierId);
        })->where('type', 'importer')->first();

        // Or check by name/code
        if (!$existingImporter) {
            $existingImporter = BusinessPartner::where('type', 'importer')
                ->where(function ($q) use ($name, $ppmData) {
                    $q->where('name', $name)
                      ->orWhere('code', $ppmData['code']);
                })
                ->first();
        }

        $created = false;
        $updated = false;

        if ($existingImporter) {
            $existingImporter->update($ppmData);
            $importer = $existingImporter;
            $updated = true;
        } else {
            $importer = BusinessPartner::create($ppmData);
            $created = true;
        }

        // Update shop pivot
        $importer->assignToShopAsSupplier($shop->id, $psSupplierId);

        // Import logo if requested
        if ($includeImage) {
            $this->importLogoFromPrestaShop($importer, $shop);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'importer' => $importer,
        ];
    }

    /**
     * Sync single importer to PrestaShop as supplier
     *
     * @param BusinessPartner $importer PPM Importer (BusinessPartner type='importer')
     * @param PrestaShopShop $shop Target shop
     * @param bool $syncLogo Also sync logo image
     * @return array ['success' => bool, 'action' => string, 'ps_id' => int|null, 'error' => string|null]
     */
    public function syncToPrestaShop(
        BusinessPartner $importer,
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
            if ($importer->type !== 'importer') {
                throw new \InvalidArgumentException("BusinessPartner #{$importer->id} is not an importer (type: {$importer->type})");
            }

            // Validate before sync
            $validation = $this->transformer->validateForSync($importer);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException(implode('; ', $validation['errors']));
            }

            $client = $shop->getApiClient();
            $psSupplierId = $importer->getPsSupplierIdForShop($shop->id);

            // Transform PPM data to PrestaShop format (reuse manufacturer transformer)
            $psData = $this->transformer->transformForPrestaShop($importer);

            if ($psSupplierId) {
                // UPDATE existing supplier
                Log::info('[IMPORTER SYNC] Updating supplier in PrestaShop', [
                    'importer_id' => $importer->id,
                    'ps_supplier_id' => $psSupplierId,
                    'shop_id' => $shop->id,
                ]);

                $client->updateSupplier($psSupplierId, $psData);
                $result['action'] = 'updated';
                $result['ps_id'] = $psSupplierId;

            } else {
                // CREATE new supplier
                Log::info('[IMPORTER SYNC] Creating supplier in PrestaShop', [
                    'importer_id' => $importer->id,
                    'name' => $importer->name,
                    'shop_id' => $shop->id,
                ]);

                $response = $client->createSupplier($psData);
                $psSupplierId = (int) ($response['supplier']['id'] ?? 0);

                if ($psSupplierId === 0) {
                    throw new \RuntimeException('PrestaShop did not return supplier ID');
                }

                $result['action'] = 'created';
                $result['ps_id'] = $psSupplierId;

                // Update pivot with new PS ID
                $importer->assignToShopAsSupplier($shop->id, $psSupplierId);
            }

            // Update sync status
            $importer->updateSupplierSyncStatus($shop->id, 'synced', $psSupplierId);

            // Sync logo if requested
            if ($syncLogo && $importer->hasLogo()) {
                $logoSynced = $this->syncLogoToPrestaShop($importer, $shop);
                if (!$logoSynced) {
                    Log::warning('[IMPORTER SYNC] Logo sync failed', [
                        'importer_id' => $importer->id,
                        'shop_id' => $shop->id,
                    ]);
                }
            }

            $result['success'] = true;

            Log::info('[IMPORTER SYNC] Sync completed', [
                'importer_id' => $importer->id,
                'action' => $result['action'],
                'ps_id' => $result['ps_id'],
                'shop_id' => $shop->id,
            ]);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();

            Log::error('[IMPORTER SYNC] Sync failed', [
                'importer_id' => $importer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Sync logo from PPM to PrestaShop supplier
     */
    public function syncLogoToPrestaShop(BusinessPartner $importer, PrestaShopShop $shop): bool
    {
        try {
            if (!$importer->hasLogo()) {
                return false;
            }

            $psSupplierId = $importer->getPsSupplierIdForShop($shop->id);
            if (!$psSupplierId) {
                Log::warning('[IMPORTER SYNC] Cannot sync logo - no PS supplier ID', [
                    'importer_id' => $importer->id,
                    'shop_id' => $shop->id,
                ]);
                return false;
            }

            $logoPath = storage_path('app/public/' . $importer->logo_path);

            if (!file_exists($logoPath)) {
                Log::warning('[IMPORTER SYNC] Logo file not found', [
                    'importer_id' => $importer->id,
                    'logo_path' => $logoPath,
                ]);
                return false;
            }

            $client = $shop->getApiClient();
            $client->uploadSupplierImage($psSupplierId, $logoPath);

            Log::info('[IMPORTER SYNC] Logo synced to PrestaShop', [
                'importer_id' => $importer->id,
                'ps_supplier_id' => $psSupplierId,
                'shop_id' => $shop->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[IMPORTER SYNC] Logo sync failed', [
                'importer_id' => $importer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Import logo from PrestaShop to PPM
     */
    public function importLogoFromPrestaShop(BusinessPartner $importer, PrestaShopShop $shop): bool
    {
        try {
            $psSupplierId = $importer->getPsSupplierIdForShop($shop->id);

            if (!$psSupplierId) {
                return false;
            }

            $client = $shop->getApiClient();

            if (!$client->hasSupplierImage($psSupplierId)) {
                return false;
            }

            $imageData = $client->downloadSupplierImage($psSupplierId);

            if (empty($imageData)) {
                return false;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            $extension = $this->getExtensionFromMimeType($mimeType);

            $filename = 'importers/' . Str::slug($importer->name) . '-' . $importer->id . '.' . $extension;

            Storage::disk('public')->put($filename, $imageData);

            $importer->update(['logo_path' => $filename]);

            Log::info('[IMPORTER SYNC] Logo imported from PrestaShop', [
                'importer_id' => $importer->id,
                'ps_supplier_id' => $psSupplierId,
                'shop_id' => $shop->id,
                'filename' => $filename,
                'size' => strlen($imageData),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[IMPORTER SYNC] Logo import failed', [
                'importer_id' => $importer->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bulk sync multiple importers to shop
     *
     * @param PrestaShopShop $shop Target shop
     * @param array $importerIds Specific importer IDs (empty = all active)
     * @param bool $syncLogos Also sync logos
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkSyncToShop(
        PrestaShopShop $shop,
        array $importerIds = [],
        bool $syncLogos = true
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $query = BusinessPartner::where('type', 'importer')
            ->where('is_active', true);

        if (!empty($importerIds)) {
            $query->whereIn('id', $importerIds);
        }

        $importers = $query->get();

        Log::info('[IMPORTER SYNC] Starting bulk sync', [
            'shop_id' => $shop->id,
            'importer_count' => $importers->count(),
        ]);

        foreach ($importers as $importer) {
            $result = $this->syncToPrestaShop($importer, $shop, $syncLogos);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $importer->id,
                    'name' => $importer->name,
                    'error' => $result['error'],
                ];
            }
        }

        Log::info('[IMPORTER SYNC] Bulk sync completed', [
            'shop_id' => $shop->id,
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Get extension from MIME type
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
}
