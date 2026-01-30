<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier Proxy Model - Dostawca
 *
 * Proxy model extending BusinessPartner with global scope type = 'supplier'.
 *
 * @see BusinessPartner Base model with all shared logic
 */
class Supplier extends BusinessPartner
{
    protected $table = 'business_partners';

    /**
     * Apply global scope to filter only suppliers.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('supplier', function (Builder $query) {
            $query->where('type', BusinessPartner::TYPE_SUPPLIER);
        });
    }

    /**
     * Auto-set type on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->type = BusinessPartner::TYPE_SUPPLIER;
        });
    }

    /**
     * Override: products specifically via supplier_id.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }
}
