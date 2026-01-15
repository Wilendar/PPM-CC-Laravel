<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * ShopVariant Model - Per-Shop Variant Customization
 *
 * ETAP_05c: Per-Shop Variants System
 *
 * Stores shop-specific variant data with 4 operation types:
 * - ADD: New variant ONLY for this shop (variant_id = null)
 * - OVERRIDE: Modified existing variant for this shop
 * - DELETE: Hidden variant in this shop (exists in product_variants but not synced)
 * - INHERIT: Use default from product_variants (no override)
 *
 * @property int $id
 * @property int $shop_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int|null $prestashop_combination_id
 * @property string $operation_type (ADD|OVERRIDE|DELETE|INHERIT)
 * @property array|null $variant_data
 * @property string $sync_status (pending|in_progress|synced|failed)
 * @property \Carbon\Carbon|null $last_sync_at
 * @property string|null $sync_error_message
 */
class ShopVariant extends Model
{
    use SoftDeletes;

    protected $table = 'shop_variants';

    protected $fillable = [
        'shop_id',
        'product_id',
        'variant_id',
        'prestashop_combination_id',
        'operation_type',
        'variant_data',
        'sync_status',
        'last_sync_at',
        'sync_error_message',
    ];

    protected $casts = [
        'variant_data' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Shop this variant belongs to
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Product this variant belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Base variant from product_variants (null for ADD operations)
     */
    public function baseVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | OPERATION TYPE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is an ADD operation (new shop-only variant)
     */
    public function isAddOperation(): bool
    {
        return $this->operation_type === 'ADD';
    }

    /**
     * Check if this is an OVERRIDE operation (modified existing variant)
     */
    public function isOverrideOperation(): bool
    {
        return $this->operation_type === 'OVERRIDE';
    }

    /**
     * Check if this is a DELETE operation (hidden in shop)
     */
    public function isDeleteOperation(): bool
    {
        return $this->operation_type === 'DELETE';
    }

    /**
     * Check if this is an INHERIT operation (use default)
     */
    public function isInheritOperation(): bool
    {
        return $this->operation_type === 'INHERIT';
    }

    /*
    |--------------------------------------------------------------------------
    | DATA ACCESS
    |--------------------------------------------------------------------------
    */

    /**
     * Get effective variant data (merged with base variant if OVERRIDE)
     *
     * Returns complete variant data based on operation type:
     * - ADD: Returns variant_data as-is
     * - OVERRIDE: Merges base variant with overrides
     * - INHERIT: Returns base variant data
     * - DELETE: Returns empty array (hidden)
     */
    public function getEffectiveVariantData(): array
    {
        if ($this->isDeleteOperation()) {
            return []; // Hidden in this shop
        }

        if ($this->isAddOperation()) {
            return $this->variant_data ?? [];
        }

        if ($this->isOverrideOperation() && $this->baseVariant) {
            $baseData = $this->baseVariantToArray();
            $overrides = $this->variant_data ?? [];
            return array_merge($baseData, $overrides);
        }

        if ($this->isInheritOperation() && $this->baseVariant) {
            return $this->baseVariantToArray();
        }

        return [];
    }

    /**
     * Convert base variant to array format matching variant_data structure
     */
    protected function baseVariantToArray(): array
    {
        if (!$this->baseVariant) {
            return [];
        }

        $variant = $this->baseVariant;

        return [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'is_active' => $variant->is_active,
            'is_default' => $variant->is_default,
            'position' => $variant->position,
            'attributes' => $variant->attributes->map(fn($a) => [
                'attribute_type_id' => $a->attribute_type_id,
                'value_id' => $a->value_id,
            ])->toArray(),
            'prices' => $variant->prices->map(fn($p) => [
                'price_group_id' => $p->price_group_id,
                'price' => $p->price,
                'price_special' => $p->price_special,
            ])->toArray(),
            'stock' => $variant->stock->map(fn($s) => [
                'warehouse_id' => $s->warehouse_id,
                'quantity' => $s->quantity,
                'reserved' => $s->reserved,
            ])->toArray(),
            'images' => $variant->images->map(fn($i) => [
                'image_path' => $i->image_path,
                'is_cover' => $i->is_cover,
                'position' => $i->position,
            ])->toArray(),
        ];
    }

    /**
     * Get a specific field from effective data
     */
    public function getEffectiveField(string $field, $default = null)
    {
        $data = $this->getEffectiveVariantData();
        return $data[$field] ?? $default;
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if sync is pending
     */
    public function isPending(): bool
    {
        return $this->sync_status === 'pending';
    }

    /**
     * Check if sync is in progress
     */
    public function isInProgress(): bool
    {
        return $this->sync_status === 'in_progress';
    }

    /**
     * Check if synced successfully
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced';
    }

    /**
     * Check if sync failed
     */
    public function isFailed(): bool
    {
        return $this->sync_status === 'failed';
    }

    /**
     * Mark as synced
     */
    public function markAsSynced(?int $prestashopCombinationId = null): void
    {
        $updateData = [
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'sync_error_message' => null,
        ];

        if ($prestashopCombinationId !== null) {
            $updateData['prestashop_combination_id'] = $prestashopCombinationId;
        }

        $this->update($updateData);

        Log::info('[ShopVariant] Marked as synced', [
            'id' => $this->id,
            'prestashop_combination_id' => $prestashopCombinationId,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error_message' => $errorMessage,
        ]);

        Log::error('[ShopVariant] Marked as failed', [
            'id' => $this->id,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Mark as in progress
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'sync_status' => 'in_progress',
        ]);
    }

    /**
     * Mark as pending (needs sync)
     */
    public function markAsPending(): void
    {
        $this->update([
            'sync_status' => 'pending',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: variants for specific shop
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: variants for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: pending sync
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Scope: failed sync
     */
    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    /**
     * Scope: active variants (not DELETE)
     */
    public function scopeActive($query)
    {
        return $query->where('operation_type', '!=', 'DELETE');
    }

    /**
     * Scope: shop-only variants (ADD)
     */
    public function scopeShopOnly($query)
    {
        return $query->where('operation_type', 'ADD');
    }

    /**
     * Scope: overrides (OVERRIDE)
     */
    public function scopeOverrides($query)
    {
        return $query->where('operation_type', 'OVERRIDE');
    }
}
