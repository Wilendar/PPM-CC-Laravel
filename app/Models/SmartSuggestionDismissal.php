<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartSuggestionDismissal extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'vehicle_product_id',
        'dismissal_reason',
        'is_permanent',
        'dismissed_by',
        'dismissed_at',
        'restored_at',
        'restored_by',
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'dismissed_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function vehicleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'vehicle_product_id');
    }

    public function dismisser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function scopeActiveDismissals($query)
    {
        return $query->whereNull('restored_at');
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopePermanent($query)
    {
        return $query->where('is_permanent', true);
    }

    public function restore(User $user): void
    {
        $this->restored_at = now();
        $this->restored_by = $user->id;
        $this->save();
    }

    public static function isDismissed(int $productId, int $vehicleProductId): bool
    {
        return self::where('product_id', $productId)
            ->where('vehicle_product_id', $vehicleProductId)
            ->whereNull('restored_at')
            ->exists();
    }
}
