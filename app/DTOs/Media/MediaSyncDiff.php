<?php

declare(strict_types=1);

namespace App\DTOs\Media;

use Illuminate\Support\Collection;

/**
 * MediaSyncDiff - Result of comparing desired media state vs current PrestaShop state
 *
 * Used by SmartMediaSyncService to determine minimal API operations needed.
 */
class MediaSyncDiff
{
    public function __construct(
        /** Media items to upload (no PS mapping or ps_image_id is null) */
        public readonly Collection $toUpload,
        /** Media items to delete from PS (in PS but not in desired) */
        public readonly Collection $toDelete,
        /** Media items unchanged (valid mapping, still desired) */
        public readonly Collection $unchanged,
        /** Whether the cover/primary image changed */
        public readonly bool $coverChanged,
        /** PS image ID of the new cover (null if cover not changing or new upload needed) */
        public readonly ?int $newCoverPsImageId,
        /** Whether sort_order changed for any unchanged images */
        public readonly bool $orderChanged,
        /** Position updates: [ps_image_id => new_position] */
        public readonly array $positionUpdates,
    ) {}

    /**
     * Check if any sync operations are needed
     */
    public function hasAnyChanges(): bool
    {
        return $this->toUpload->isNotEmpty()
            || $this->toDelete->isNotEmpty()
            || $this->coverChanged
            || $this->orderChanged;
    }

    /**
     * Check if diff is completely empty (no changes at all)
     */
    public function isEmpty(): bool
    {
        return !$this->hasAnyChanges();
    }

    /**
     * Get summary for logging
     */
    public function toLogArray(): array
    {
        return [
            'to_upload' => $this->toUpload->count(),
            'to_delete' => $this->toDelete->count(),
            'unchanged' => $this->unchanged->count(),
            'cover_changed' => $this->coverChanged,
            'new_cover_ps_image_id' => $this->newCoverPsImageId,
            'order_changed' => $this->orderChanged,
            'position_updates_count' => count($this->positionUpdates),
        ];
    }
}
