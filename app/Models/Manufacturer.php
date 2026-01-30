<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manufacturer Proxy Model - Producent/Marka
 *
 * Proxy model extending BusinessPartner with global scope type = 'manufacturer'.
 * Backward compatible: Manufacturer::active()->get() still works.
 *
 * @see BusinessPartner Base model with all shared logic
 */
class Manufacturer extends BusinessPartner
{
    protected $table = 'business_partners';

    /**
     * Apply global scope to filter only manufacturers.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('manufacturer', function (Builder $query) {
            $query->where('type', BusinessPartner::TYPE_MANUFACTURER);
        });
    }

    /**
     * Auto-set type on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->type = BusinessPartner::TYPE_MANUFACTURER;
        });
    }

    /**
     * Override: products specifically via manufacturer_id.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'manufacturer_id');
    }

    /**
     * Backward compat: get dropdown without type param.
     */
    public static function getForDropdown(?string $type = null): \Illuminate\Support\Collection
    {
        return parent::getForDropdown($type ?? BusinessPartner::TYPE_MANUFACTURER);
    }
}
