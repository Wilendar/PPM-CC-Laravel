<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrestaShopShopPriceMapping Model
 *
 * BUG FIX #13 (2025-11-13): Liczniki mapowań na liście sklepów
 *
 * Reprezentuje mapowanie grup cenowych między PPM a PrestaShop.
 * Każda grupa cenowa w PrestaShop może być mapowana na grupę PPM,
 * umożliwiając synchronizację cen między systemami.
 *
 * Enterprise Features:
 * - One-to-one mapping per shop (unique constraint)
 * - Cascading deletion przy usunięciu sklepu
 * - Performance indexes dla szybkich lookup operations
 *
 * @property int $id
 * @property int $prestashop_shop_id
 * @property int $prestashop_price_group_id
 * @property string $prestashop_price_group_name
 * @property string $ppm_price_group_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 */
class PrestaShopShopPriceMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'prestashop_shop_price_mappings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prestashop_shop_id',
        'prestashop_price_group_id',
        'prestashop_price_group_name',
        'ppm_price_group_name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'prestashop_shop_id' => 'integer',
        'prestashop_price_group_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the PrestaShop shop that owns this price mapping.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'prestashop_shop_id');
    }

    /**
     * Scope to get mappings for specific shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('prestashop_shop_id', $shopId);
    }

    /**
     * Scope to get mappings for specific PPM price group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ppmPriceGroup
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPpmGroup($query, string $ppmPriceGroup)
    {
        return $query->where('ppm_price_group_name', $ppmPriceGroup);
    }

    /**
     * Scope to get mappings for specific PrestaShop price group.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $prestashopGroupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPrestashopGroup($query, int $prestashopGroupId)
    {
        return $query->where('prestashop_price_group_id', $prestashopGroupId);
    }

    /**
     * Get formatted mapping display string.
     *
     * @return string
     */
    public function getFormattedMappingAttribute(): string
    {
        return "{$this->prestashop_price_group_name} → {$this->ppm_price_group_name}";
    }
}
