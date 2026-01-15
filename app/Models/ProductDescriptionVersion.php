<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ETAP_07f Faza 6.1.4.3 - Product Description Version Model
 *
 * Historia zmian opisow wizualnych produktow.
 * Przechowuje snapshoty blocks_json i rendered_html.
 *
 * @property int $id
 * @property int $product_description_id
 * @property int $version_number
 * @property array|null $blocks_json
 * @property string|null $rendered_html
 * @property int|null $created_by
 * @property string $change_type
 * @property array|null $metadata
 * @property string|null $checksum
 * @property \Carbon\Carbon $created_at
 *
 * @property-read ProductDescription $description
 * @property-read User|null $creator
 */
class ProductDescriptionVersion extends Model
{
    const UPDATED_AT = null; // Only created_at, no updated_at

    /**
     * Change types
     */
    const CHANGE_CREATED = 'created';
    const CHANGE_UPDATED = 'updated';
    const CHANGE_SYNCED = 'synced';
    const CHANGE_TEMPLATE_APPLIED = 'template_applied';
    const CHANGE_RESTORED = 'restored';
    const CHANGE_AUTO_SAVE = 'auto_save';

    protected $fillable = [
        'product_description_id',
        'version_number',
        'blocks_json',
        'rendered_html',
        'created_by',
        'change_type',
        'metadata',
        'checksum',
    ];

    protected $casts = [
        'blocks_json' => 'array',
        'metadata' => 'array',
        'version_number' => 'integer',
        'product_description_id' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Parent description
     */
    public function description(): BelongsTo
    {
        return $this->belongsTo(ProductDescription::class, 'product_description_id');
    }

    /**
     * User who created this version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: Versions for specific description
     */
    public function scopeForDescription(Builder $query, int $descriptionId): Builder
    {
        return $query->where('product_description_id', $descriptionId);
    }

    /**
     * Scope: Order by version (newest first)
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('version_number');
    }

    /**
     * Scope: Order by version (oldest first)
     */
    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->orderBy('version_number');
    }

    /**
     * Scope: Filter by change type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope: Created by specific user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get blocks with fallback to empty array
     */
    public function getBlocksAttribute(): array
    {
        return $this->blocks_json ?? [];
    }

    /**
     * Get block count
     */
    public function getBlockCountAttribute(): int
    {
        return count($this->blocks_json ?? []);
    }

    /**
     * Get human-readable change type
     */
    public function getChangeTypeLabelAttribute(): string
    {
        return match ($this->change_type) {
            self::CHANGE_CREATED => 'Utworzono',
            self::CHANGE_UPDATED => 'Edytowano',
            self::CHANGE_SYNCED => 'Zsynchronizowano',
            self::CHANGE_TEMPLATE_APPLIED => 'Zastosowano szablon',
            self::CHANGE_RESTORED => 'Przywrocono',
            self::CHANGE_AUTO_SAVE => 'Auto-zapis',
            default => ucfirst($this->change_type),
        };
    }

    /**
     * Get change type icon
     */
    public function getChangeTypeIconAttribute(): string
    {
        return match ($this->change_type) {
            self::CHANGE_CREATED => 'fas fa-plus-circle',
            self::CHANGE_UPDATED => 'fas fa-edit',
            self::CHANGE_SYNCED => 'fas fa-sync',
            self::CHANGE_TEMPLATE_APPLIED => 'fas fa-file-import',
            self::CHANGE_RESTORED => 'fas fa-undo',
            self::CHANGE_AUTO_SAVE => 'fas fa-save',
            default => 'fas fa-history',
        };
    }

    /**
     * Get metadata value
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    // =====================
    // STATIC METHODS
    // =====================

    /**
     * Create a new version for a description
     */
    public static function createVersion(
        ProductDescription $description,
        string $changeType = self::CHANGE_UPDATED,
        ?int $userId = null,
        array $metadata = []
    ): self {
        // Get next version number
        $maxVersion = self::forDescription($description->id)->max('version_number') ?? 0;

        return self::create([
            'product_description_id' => $description->id,
            'version_number' => $maxVersion + 1,
            'blocks_json' => $description->blocks_json,
            'rendered_html' => $description->rendered_html,
            'created_by' => $userId ?? auth()->id(),
            'change_type' => $changeType,
            'metadata' => $metadata,
            'checksum' => $description->calculateChecksum(),
        ]);
    }

    /**
     * Get latest version for description
     */
    public static function getLatest(int $descriptionId): ?self
    {
        return self::forDescription($descriptionId)
            ->latestFirst()
            ->first();
    }

    /**
     * Get version count for description
     */
    public static function getVersionCount(int $descriptionId): int
    {
        return self::forDescription($descriptionId)->count();
    }

    // =====================
    // INSTANCE METHODS
    // =====================

    /**
     * Restore this version to the parent description
     */
    public function restore(?int $userId = null): ProductDescription
    {
        $description = $this->description;

        // Create version before restore (to track the restore action)
        self::createVersion(
            $description,
            self::CHANGE_RESTORED,
            $userId,
            [
                'restored_from_version' => $this->version_number,
                'restored_at' => now()->toIso8601String(),
            ]
        );

        // Restore blocks
        $description->update([
            'blocks_json' => $this->blocks_json,
            'rendered_html' => null, // Force re-render
            'last_rendered_at' => null,
        ]);

        return $description->fresh();
    }

    /**
     * Compare this version with another
     */
    public function compare(self $other): array
    {
        $thisBlocks = $this->blocks_json ?? [];
        $otherBlocks = $other->blocks_json ?? [];

        return [
            'this_version' => $this->version_number,
            'other_version' => $other->version_number,
            'this_block_count' => count($thisBlocks),
            'other_block_count' => count($otherBlocks),
            'blocks_changed' => $thisBlocks !== $otherBlocks,
            'checksum_this' => $this->checksum,
            'checksum_other' => $other->checksum,
        ];
    }
}
