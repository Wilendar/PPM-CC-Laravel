<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle Feature Value Mapping Model
 *
 * ETAP_05d FAZA 4.5.2 - Maps PPM vehicles to PrestaShop feature values
 *
 * Enables bidirectional sync of vehicle compatibility:
 * - Export: PPM vehicle_product_id -> PrestaShop feature_value_id
 * - Import: PrestaShop feature_value_id -> PPM vehicle_product_id
 *
 * @property int $id
 * @property int $vehicle_product_id PPM vehicle product ID
 * @property int $prestashop_feature_id PS feature ID (431/432/433)
 * @property int $prestashop_feature_value_id PS feature value ID
 * @property int $shop_id PPM shop ID
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read Product $vehicle Vehicle product
 * @property-read PrestaShopShop $shop Shop
 *
 * @since 2025-12-09
 */
class VehicleFeatureValueMapping extends Model
{
    /**
     * Table name
     */
    protected $table = 'vehicle_feature_value_mappings';

    /**
     * No updated_at column
     */
    public $timestamps = false;

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'vehicle_product_id',
        'prestashop_feature_id',
        'prestashop_feature_value_id',
        'shop_id',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'vehicle_product_id' => 'integer',
        'prestashop_feature_id' => 'integer',
        'prestashop_feature_value_id' => 'integer',
        'shop_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Vehicle product
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'vehicle_product_id');
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
     * Scope: Filter by shop
     */
    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by feature ID
     */
    public function scopeByFeature($query, int $featureId)
    {
        return $query->where('prestashop_feature_id', $featureId);
    }

    /**
     * Scope: Filter by vehicle
     */
    public function scopeByVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_product_id', $vehicleId);
    }

    /**
     * Scope: Filter by feature value
     */
    public function scopeByFeatureValue($query, int $featureValueId)
    {
        return $query->where('prestashop_feature_value_id', $featureValueId);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Find vehicle ID by PrestaShop feature value
     *
     * @param int $featureValueId
     * @param int $shopId
     * @return int|null
     */
    public static function findVehicleId(int $featureValueId, int $shopId): ?int
    {
        return static::byShop($shopId)
            ->byFeatureValue($featureValueId)
            ->value('vehicle_product_id');
    }

    /**
     * Find feature value ID for vehicle and feature
     *
     * @param int $vehicleId
     * @param int $featureId
     * @param int $shopId
     * @return int|null
     */
    public static function findFeatureValueId(
        int $vehicleId,
        int $featureId,
        int $shopId
    ): ?int {
        return static::byShop($shopId)
            ->byVehicle($vehicleId)
            ->byFeature($featureId)
            ->value('prestashop_feature_value_id');
    }

    /**
     * Create or update mapping
     *
     * @param int $vehicleId
     * @param int $featureId
     * @param int $featureValueId
     * @param int $shopId
     * @return static
     */
    public static function upsert(
        int $vehicleId,
        int $featureId,
        int $featureValueId,
        int $shopId
    ): static {
        return static::updateOrCreate(
            [
                'vehicle_product_id' => $vehicleId,
                'prestashop_feature_id' => $featureId,
                'shop_id' => $shopId,
            ],
            [
                'prestashop_feature_value_id' => $featureValueId,
            ]
        );
    }
}
