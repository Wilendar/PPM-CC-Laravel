<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compatibility Feature Mapping Model
 *
 * ETAP_05d FAZA 4.5.2 - Maps PPM compatibility attributes to PrestaShop features
 *
 * Allows per-shop configuration of which PrestaShop feature ID corresponds
 * to which PPM compatibility attribute (original, replacement, etc.)
 *
 * Default mappings for B2B Test DEV:
 * - original -> Feature 431 (Oryginal)
 * - replacement -> Feature 433 (Zamiennik)
 *
 * @property int $id
 * @property int $compatibility_attribute_id FK to compatibility_attributes
 * @property int $prestashop_feature_id PS feature ID
 * @property int $shop_id FK to prestashop_shops
 * @property bool $is_active Whether mapping is active
 * @property string $sync_direction ppm_to_ps, ps_to_ppm, both
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property string|null $last_sync_error
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read CompatibilityAttribute $compatibilityAttribute
 * @property-read PrestaShopShop $shop
 *
 * @since 2025-12-09
 */
class CompatibilityFeatureMapping extends Model
{
    /**
     * Table name
     */
    protected $table = 'compatibility_feature_mappings';

    /**
     * Sync direction constants
     */
    public const SYNC_PPM_TO_PS = 'ppm_to_ps';
    public const SYNC_PS_TO_PPM = 'ps_to_ppm';
    public const SYNC_BOTH = 'both';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'compatibility_attribute_id',
        'prestashop_feature_id',
        'shop_id',
        'is_active',
        'sync_direction',
        'last_synced_at',
        'last_sync_error',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'compatibility_attribute_id' => 'integer',
        'prestashop_feature_id' => 'integer',
        'shop_id' => 'integer',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Compatibility attribute
     */
    public function compatibilityAttribute(): BelongsTo
    {
        return $this->belongsTo(CompatibilityAttribute::class, 'compatibility_attribute_id');
    }

    /**
     * Shop
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active mappings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by shop
     */
    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by compatibility attribute
     */
    public function scopeByAttribute($query, int $attributeId)
    {
        return $query->where('compatibility_attribute_id', $attributeId);
    }

    /**
     * Scope: Mappings that can push to PrestaShop
     */
    public function scopeCanPush($query)
    {
        return $query->whereIn('sync_direction', [self::SYNC_PPM_TO_PS, self::SYNC_BOTH]);
    }

    /**
     * Scope: Mappings that can pull from PrestaShop
     */
    public function scopeCanPull($query)
    {
        return $query->whereIn('sync_direction', [self::SYNC_PS_TO_PPM, self::SYNC_BOTH]);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if can push to PrestaShop
     */
    public function canPushToPrestaShop(): bool
    {
        return in_array($this->sync_direction, [self::SYNC_PPM_TO_PS, self::SYNC_BOTH]);
    }

    /**
     * Check if can pull from PrestaShop
     */
    public function canPullFromPrestaShop(): bool
    {
        return in_array($this->sync_direction, [self::SYNC_PS_TO_PPM, self::SYNC_BOTH]);
    }

    /**
     * Mark as successfully synced
     */
    public function markSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ]);
    }

    /**
     * Mark sync error
     */
    public function markSyncError(string $error): void
    {
        $this->update([
            'last_sync_error' => $error,
        ]);
    }

    /**
     * Get sync direction label
     */
    public function getSyncDirectionLabel(): string
    {
        return match ($this->sync_direction) {
            self::SYNC_PPM_TO_PS => 'PPM -> PrestaShop',
            self::SYNC_PS_TO_PPM => 'PrestaShop -> PPM',
            self::SYNC_BOTH => 'Dwukierunkowa',
            default => 'Nieznana',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop feature ID for compatibility attribute
     *
     * @param string $attributeCode e.g., 'original', 'replacement'
     * @param int $shopId
     * @return int|null
     */
    public static function getFeatureId(string $attributeCode, int $shopId): ?int
    {
        $attribute = CompatibilityAttribute::byCode($attributeCode)->first();

        if (!$attribute) {
            return null;
        }

        return static::active()
            ->byShop($shopId)
            ->byAttribute($attribute->id)
            ->canPush()
            ->value('prestashop_feature_id');
    }

    /**
     * Get compatibility attribute code for PrestaShop feature ID
     *
     * @param int $featureId
     * @param int $shopId
     * @return string|null
     */
    public static function getAttributeCode(int $featureId, int $shopId): ?string
    {
        $mapping = static::active()
            ->byShop($shopId)
            ->where('prestashop_feature_id', $featureId)
            ->canPull()
            ->with('compatibilityAttribute')
            ->first();

        return $mapping?->compatibilityAttribute?->code;
    }

    /**
     * Get all mappings for a shop as array
     *
     * @param int $shopId
     * @return array ['attribute_code' => feature_id, ...]
     */
    public static function getMappingsForShop(int $shopId): array
    {
        return static::active()
            ->byShop($shopId)
            ->with('compatibilityAttribute')
            ->get()
            ->mapWithKeys(fn($m) => [
                $m->compatibilityAttribute->code => $m->prestashop_feature_id
            ])
            ->toArray();
    }
}
