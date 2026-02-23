<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SmartSyncBrandRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'brand',
        'is_allowed',
        'auto_sync',
        'min_confidence',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shop_id' => 'integer',
        'is_allowed' => 'boolean',
        'auto_sync' => 'boolean',
        'min_confidence' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (self $model) {
            Cache::forget("smart_sync_rules_shop_{$model->shop_id}");
        });

        static::deleted(function (self $model) {
            Cache::forget("smart_sync_rules_shop_{$model->shop_id}");
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAllowed($query)
    {
        return $query->where('is_allowed', true);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeAutoSync($query)
    {
        return $query->where('auto_sync', true);
    }
}
