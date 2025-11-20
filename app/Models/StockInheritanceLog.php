<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockInheritanceLog Model - Audit trail for stock inheritance operations
 *
 * Strategy B Feature:
 * - Tracks all stock inheritance/sync operations
 * - Debugging inheritance issues
 * - Audit compliance
 * - Performance analytics
 *
 * Logged Operations:
 * - inherit: Shop inherited stock from warehouse
 * - pull: Shop pulled stock from PrestaShop API
 * - override: Shop manually overridden stock
 * - sync: Stock synchronized between systems
 *
 * @property int $id
 * @property int $product_id
 * @property int $shop_id
 * @property int|null $warehouse_id
 * @property string $action (inherit, pull, override, sync)
 * @property string $source (warehouse, shop, manual, api)
 * @property int|null $quantity_before
 * @property int $quantity_after
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\PrestaShopShop $shop
 * @property-read \App\Models\Warehouse|null $warehouse
 *
 * @package App\Models
 * @version Strategy B - Complex Warehouse Redesign
 * @since 2025-11-13
 */
class StockInheritanceLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'shop_id',
        'warehouse_id',
        'action',
        'source',
        'quantity_before',
        'quantity_after',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product that stock changed for
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Shop that received/modified stock
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Source warehouse (if applicable)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get quantity change delta
     *
     * @return int
     */
    public function getQuantityDelta(): int
    {
        if ($this->quantity_before === null) {
            return $this->quantity_after;
        }

        return $this->quantity_after - $this->quantity_before;
    }

    /**
     * Check if stock increased
     *
     * @return bool
     */
    public function isIncrease(): bool
    {
        return $this->getQuantityDelta() > 0;
    }

    /**
     * Check if stock decreased
     *
     * @return bool
     */
    public function isDecrease(): bool
    {
        return $this->getQuantityDelta() < 0;
    }

    /**
     * Format action for display
     *
     * @return string
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            'inherit' => 'Inherited from warehouse',
            'pull' => 'Pulled from PrestaShop',
            'override' => 'Manual override',
            'sync' => 'Synchronized',
            default => ucfirst($this->action),
        };
    }

    /**
     * Format source for display
     *
     * @return string
     */
    public function getSourceLabel(): string
    {
        return match ($this->source) {
            'warehouse' => 'Warehouse',
            'shop' => 'Shop',
            'manual' => 'Manual',
            'api' => 'API',
            default => ucfirst($this->source),
        };
    }
}
