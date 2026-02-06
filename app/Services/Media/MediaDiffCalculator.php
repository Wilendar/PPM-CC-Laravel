<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\DTOs\Media\MediaSyncDiff;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * MediaDiffCalculator - Calculates diff between desired media state and current PrestaShop state
 *
 * Determines minimal set of API operations needed to sync media:
 * - Which images need uploading (new)
 * - Which images need deleting (removed)
 * - Whether cover changed
 * - Whether sort order changed
 */
class MediaDiffCalculator
{
    /**
     * Calculate diff between desired media collection and current PS state
     *
     * @param Collection $desired Desired media state (what SHOULD be on PS)
     * @param int $shopId PrestaShop shop ID
     * @return MediaSyncDiff
     */
    public function calculateDiff(Collection $desired, int $shopId): MediaSyncDiff
    {
        $storeKey = "store_{$shopId}";

        // Partition desired media into those with/without valid PS mapping
        $toUpload = collect();
        $unchanged = collect();
        $positionUpdates = [];

        foreach ($desired as $media) {
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $psImageId = $mapping['ps_image_id'] ?? null;

            if (empty($psImageId)) {
                // No PS mapping → needs upload
                $toUpload->push($media);
            } else {
                // Has valid mapping → unchanged (still desired)
                $unchanged->push($media);
            }
        }

        // Find media to DELETE: currently in PS but NOT in desired set
        $desiredIds = $desired->pluck('id')->toArray();
        $toDelete = $this->findMediaToDelete($desiredIds, $shopId, $desired);

        // Detect cover change
        $coverChanged = false;
        $newCoverPsImageId = null;
        $this->detectCoverChange($desired, $unchanged, $storeKey, $coverChanged, $newCoverPsImageId);

        // Detect sort order changes for unchanged images
        $orderChanged = false;
        $this->detectOrderChanges($unchanged, $storeKey, $orderChanged, $positionUpdates);

        $diff = new MediaSyncDiff(
            toUpload: $toUpload,
            toDelete: $toDelete,
            unchanged: $unchanged,
            coverChanged: $coverChanged,
            newCoverPsImageId: $newCoverPsImageId,
            orderChanged: $orderChanged,
            positionUpdates: $positionUpdates,
        );

        Log::debug('[MEDIA DIFF] Calculated diff', array_merge(
            ['shop_id' => $shopId],
            $diff->toLogArray(),
            [
                'desired_count' => $desired->count(),
                'to_upload_ids' => $toUpload->pluck('id')->toArray(),
                'to_delete_ids' => $toDelete->pluck('id')->toArray(),
            ]
        ));

        return $diff;
    }

    /**
     * Find media that exists in PS (has mapping) but is NOT in the desired set
     */
    private function findMediaToDelete(array $desiredIds, int $shopId, Collection $desired): Collection
    {
        // Get the product from first desired media (they all belong to same product)
        if ($desired->isEmpty()) {
            return collect();
        }

        $firstMedia = $desired->first();
        $productId = $firstMedia->mediable_id;
        $storeKey = "store_{$shopId}";

        // Find all media for this product that have a valid PS mapping for this shop
        // but are NOT in the desired set
        // IMPORTANT: withTrashed() to detect soft-deleted media that still have PS images
        $allProductMedia = Media::withTrashed()
            ->where('mediable_type', Product::class)
            ->where('mediable_id', $productId)
            ->whereNotNull('prestashop_mapping')
            ->get();

        return $allProductMedia->filter(function (Media $media) use ($desiredIds, $storeKey) {
            // Only consider media with valid PS mapping
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $hasPsImage = !empty($mapping['ps_image_id']);

            // Should delete if: has PS mapping but NOT in desired set
            return $hasPsImage && !in_array($media->id, $desiredIds);
        });
    }

    /**
     * Detect if the cover (primary) image changed
     */
    private function detectCoverChange(
        Collection $desired,
        Collection $unchanged,
        string $storeKey,
        bool &$coverChanged,
        ?int &$newCoverPsImageId
    ): void {
        $primaryMedia = $desired->firstWhere('is_primary', true);

        if (!$primaryMedia) {
            return;
        }

        $mapping = $primaryMedia->prestashop_mapping[$storeKey] ?? [];
        $psImageId = $mapping['ps_image_id'] ?? null;
        $isCoverInMapping = $mapping['is_cover'] ?? false;

        if (empty($psImageId)) {
            // Primary image needs upload first → cover will be set after upload
            $coverChanged = true;
            $newCoverPsImageId = null;
            return;
        }

        if (!$isCoverInMapping) {
            // Has PS image but not marked as cover → need to set cover
            $coverChanged = true;
            $newCoverPsImageId = (int) $psImageId;
        }
    }

    /**
     * Detect sort order changes for unchanged images
     */
    private function detectOrderChanges(
        Collection $unchanged,
        string $storeKey,
        bool &$orderChanged,
        array &$positionUpdates
    ): void {
        foreach ($unchanged as $media) {
            $mapping = $media->prestashop_mapping[$storeKey] ?? [];
            $syncedSortOrder = $mapping['synced_sort_order'] ?? null;
            $currentSortOrder = $media->sort_order ?? 0;
            $psImageId = $mapping['ps_image_id'] ?? null;

            if (!$psImageId) {
                continue;
            }

            // Compare current sort_order with what was synced
            if ($syncedSortOrder === null || (int) $syncedSortOrder !== (int) $currentSortOrder) {
                $orderChanged = true;
                $positionUpdates[(int) $psImageId] = (int) $currentSortOrder;
            }
        }
    }
}
