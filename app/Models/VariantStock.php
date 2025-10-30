<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variant Stock Model
 *
 * Stan magazynowy wariantu dla konkretnego magazynu
 * Wspiera rezerwacje (reserved quantity)
 *
 * @property int $id
 * @property int $variant_id
 * @property int $warehouse_id
 * @property int $quantity Ilość całkowita
 * @property int $reserved Ilość zarezerwowana
 * @property int $available Ilość dostępna (computed: quantity - reserved)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VariantStock extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'variant_stock';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'variant_id',
        'warehouse_id',
        'quantity',
        'reserved',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'warehouse_id' => 'integer',
        'quantity' => 'integer',
        'reserved' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Appended accessors
     */
    protected $appends = [
        'available',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available quantity (computed)
     */
    public function getAvailableAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available quantity (method version)
     */
    public function getAvailable(): int
    {
        return $this->available;
    }

    /**
     * Reserve quantity
     */
    public function reserve(int $quantity): bool
    {
        if (!$this->isAvailable($quantity)) {
            return false;
        }

        $this->reserved += $quantity;
        return $this->save();
    }

    /**
     * Release reserved quantity
     */
    public function release(int $quantity): bool
    {
        if ($quantity > $this->reserved) {
            return false;
        }

        $this->reserved -= $quantity;
        return $this->save();
    }

    /**
     * Check if quantity is available
     */
    public function isAvailable(int $quantity = 1): bool
    {
        return $this->getAvailable() >= $quantity;
    }

    /**
     * Add stock
     */
    public function addStock(int $quantity): bool
    {
        $this->quantity += $quantity;
        return $this->save();
    }

    /**
     * Remove stock
     */
    public function removeStock(int $quantity): bool
    {
        if ($quantity > $this->quantity) {
            return false;
        }

        $this->quantity -= $quantity;
        return $this->save();
    }
}
