<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Importer Proxy Model - Importer
 *
 * Proxy model extending BusinessPartner with global scope type = 'importer'.
 *
 * @see BusinessPartner Base model with all shared logic
 */
class Importer extends BusinessPartner
{
    protected $table = 'business_partners';

    /**
     * Apply global scope to filter only importers.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('importer', function (Builder $query) {
            $query->where('type', BusinessPartner::TYPE_IMPORTER);
        });
    }

    /**
     * Auto-set type on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->type = BusinessPartner::TYPE_IMPORTER;
        });
    }

    /**
     * Override: products specifically via importer_id.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'importer_id');
    }
}
