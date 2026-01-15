<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrestaShop Feature Mapping Model
 *
 * ETAP_07e FAZA 1.3.2 - Mapowanie cech PPM <-> PrestaShop
 *
 * @property int $id
 * @property int $feature_type_id FK do feature_types
 * @property int $shop_id FK do prestashop_shops (per-shop mapping)
 * @property int $prestashop_feature_id id_feature z ps_feature
 * @property string|null $prestashop_feature_name Nazwa w PS (reference)
 * @property string $sync_direction both/ppm_to_ps/ps_to_ppm
 * @property bool $auto_create_values Czy tworzyc wartosci automatycznie
 * @property bool $is_active Czy aktywne
 * @property \Illuminate\Support\Carbon|null $last_synced_at Ostatnia sync
 * @property int $sync_count Liczba synchronizacji
 * @property string|null $last_sync_error Ostatni blad sync
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read FeatureType $featureType
 * @property-read PrestaShopShop $shop
 */
class PrestashopFeatureMapping extends Model
{
    use HasFactory;

    protected $table = 'prestashop_feature_mappings';

    protected $fillable = [
        'feature_type_id',
        'shop_id',
        'prestashop_feature_id',
        'prestashop_feature_name',
        'sync_direction',
        'auto_create_values',
        'is_active',
        'last_synced_at',
        'sync_count',
        'last_sync_error',
    ];

    protected $casts = [
        'prestashop_feature_id' => 'integer',
        'auto_create_values' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'sync_count' => 'integer',
    ];

    /**
     * Sync direction constants
     */
    public const SYNC_BOTH = 'both';
    public const SYNC_PPM_TO_PS = 'ppm_to_ps';
    public const SYNC_PS_TO_PPM = 'ps_to_ppm';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * PPM Feature Type
     */
    public function featureType(): BelongsTo
    {
        return $this->belongsTo(FeatureType::class, 'feature_type_id');
    }

    /**
     * Shop for this mapping
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
     * Scope: For specific shop
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: For specific feature type
     */
    public function scopeForFeatureType($query, int $featureTypeId)
    {
        return $query->where('feature_type_id', $featureTypeId);
    }

    /**
     * Scope: Needs sync (not synced in last X hours)
     */
    public function scopeNeedsSync($query, int $hoursThreshold = 24)
    {
        return $query->where(function ($q) use ($hoursThreshold) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hoursThreshold));
        });
    }

    /**
     * Scope: Has sync errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('last_sync_error');
    }

    /**
     * Scope: Can push to PS
     */
    public function scopeCanPushToPs($query)
    {
        return $query->whereIn('sync_direction', [self::SYNC_BOTH, self::SYNC_PPM_TO_PS]);
    }

    /**
     * Scope: Can pull from PS
     */
    public function scopeCanPullFromPs($query)
    {
        return $query->whereIn('sync_direction', [self::SYNC_BOTH, self::SYNC_PS_TO_PPM]);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if mapping needs synchronization
     */
    public function needsSync(int $hoursThreshold = 24): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->addHours($hoursThreshold)->isPast();
    }

    /**
     * Mark as synced
     */
    public function markSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_count' => $this->sync_count + 1,
            'last_sync_error' => null,
        ]);
    }

    /**
     * Mark sync error
     */
    public function markSyncError(string $error): void
    {
        $this->update([
            'last_synced_at' => now(),
            'last_sync_error' => $error,
        ]);
    }

    /**
     * Clear sync error
     */
    public function clearSyncError(): void
    {
        $this->update(['last_sync_error' => null]);
    }

    /**
     * Can push values to PrestaShop
     */
    public function canPushToPrestaShop(): bool
    {
        return $this->is_active &&
               in_array($this->sync_direction, [self::SYNC_BOTH, self::SYNC_PPM_TO_PS]);
    }

    /**
     * Can pull values from PrestaShop
     */
    public function canPullFromPrestaShop(): bool
    {
        return $this->is_active &&
               in_array($this->sync_direction, [self::SYNC_BOTH, self::SYNC_PS_TO_PPM]);
    }

    /**
     * Get human-readable sync direction
     */
    public function getSyncDirectionLabel(): string
    {
        return match ($this->sync_direction) {
            self::SYNC_BOTH => 'Dwukierunkowa',
            self::SYNC_PPM_TO_PS => 'PPM -> PrestaShop',
            self::SYNC_PS_TO_PPM => 'PrestaShop -> PPM',
            default => 'Nieznana',
        };
    }

    /**
     * Get sync status for display
     */
    public function getSyncStatus(): array
    {
        if (!$this->is_active) {
            return ['status' => 'inactive', 'label' => 'Nieaktywne', 'color' => 'gray'];
        }

        if ($this->last_sync_error) {
            return ['status' => 'error', 'label' => 'Blad', 'color' => 'red'];
        }

        if ($this->last_synced_at === null) {
            return ['status' => 'never', 'label' => 'Nigdy', 'color' => 'yellow'];
        }

        if ($this->needsSync()) {
            return ['status' => 'stale', 'label' => 'Nieaktualne', 'color' => 'orange'];
        }

        return ['status' => 'synced', 'label' => 'Zsynchronizowane', 'color' => 'green'];
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available sync directions
     */
    public static function getSyncDirections(): array
    {
        return [
            self::SYNC_BOTH => 'Dwukierunkowa',
            self::SYNC_PPM_TO_PS => 'PPM -> PrestaShop',
            self::SYNC_PS_TO_PPM => 'PrestaShop -> PPM',
        ];
    }

    /**
     * Find or create mapping for feature type and shop
     */
    public static function findOrCreateForFeature(
        int $featureTypeId,
        int $shopId,
        int $prestashopFeatureId,
        ?string $prestashopFeatureName = null
    ): self {
        return static::firstOrCreate(
            [
                'feature_type_id' => $featureTypeId,
                'shop_id' => $shopId,
            ],
            [
                'prestashop_feature_id' => $prestashopFeatureId,
                'prestashop_feature_name' => $prestashopFeatureName,
                'sync_direction' => self::SYNC_BOTH,
                'auto_create_values' => true,
                'is_active' => true,
            ]
        );
    }
}
