<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variant Price Model
 *
 * Cena wariantu dla konkretnej grupy cenowej
 * Wspiera special prices z zakresem dat
 *
 * @property int $id
 * @property int $variant_id
 * @property int $price_group_id
 * @property float $price Cena regularna
 * @property float|null $special_price Cena promocyjna
 * @property \Illuminate\Support\Carbon|null $special_price_from Data rozpoczęcia promocji
 * @property \Illuminate\Support\Carbon|null $special_price_to Data zakończenia promocji
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VariantPrice extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'variant_prices';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'variant_id',
        'price_group_id',
        'price',
        'special_price',
        'special_price_from',
        'special_price_to',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'price_group_id' => 'integer',
        'price' => 'decimal:2',
        'special_price' => 'decimal:2',
        'special_price_from' => 'datetime',
        'special_price_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Price group
     */
    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_id');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get effective price (special if active, otherwise regular)
     */
    public function getEffectivePrice(): float
    {
        if ($this->isSpecialActive()) {
            return (float) $this->special_price;
        }

        return (float) $this->price;
    }

    /**
     * Check if special price is currently active
     */
    public function isSpecialActive(): bool
    {
        if (!$this->special_price) {
            return false;
        }

        $now = now();

        // Check date range
        if ($this->special_price_from && $now->lt($this->special_price_from)) {
            return false;
        }

        if ($this->special_price_to && $now->gt($this->special_price_to)) {
            return false;
        }

        return true;
    }

    /**
     * Get price difference (regular vs special)
     */
    public function getSavings(): ?float
    {
        if (!$this->isSpecialActive()) {
            return null;
        }

        return (float) $this->price - (float) $this->special_price;
    }

    /**
     * Get savings percentage
     */
    public function getSavingsPercentage(): ?int
    {
        if (!$this->isSpecialActive() || $this->price <= 0) {
            return null;
        }

        return (int) round((($this->price - $this->special_price) / $this->price) * 100);
    }
}
