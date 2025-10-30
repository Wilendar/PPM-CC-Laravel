<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compatibility Cache Model
 *
 * Cache compatibility data dla szybkiego odczytu (PrestaShop export)
 * SKU-first pattern z backup column (part_sku)
 *
 * @property int $id
 * @property int $product_id
 * @property string $part_sku SKU części (backup for SKU-first)
 * @property int|null $prestashop_shop_id Sklep PrestaShop (null = global)
 * @property array $data JSON z compatibility data
 * @property \Illuminate\Support\Carbon $expires_at Wygasa po (default 15 min)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompatibilityCache extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'compatibility_cache';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'product_id',
        'part_sku',
        'prestashop_shop_id',
        'data',
        'expires_at',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'product_id' => 'integer',
        'prestashop_shop_id' => 'integer',
        'data' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default TTL in seconds (15 minutes)
     */
    public const DEFAULT_TTL = 900;

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * PrestaShop shop (nullable - global cache if null)
     */
    public function prestashopShop(): BelongsTo
    {
        return $this->belongsTo(PrestashopShop::class, 'prestashop_shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only not expired cache entries
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Find by part SKU (SKU-first pattern)
     */
    public function scopeByPartSku($query, string $sku)
    {
        return $query->where('part_sku', $sku);
    }

    /**
     * Scope: Filter by shop
     */
    public function scopeForShop($query, ?int $shopId = null)
    {
        return $query->where('prestashop_shop_id', $shopId);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if cache is expired
     */
    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    /**
     * Get cached data (JSON decoded)
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * Refresh cache with new data
     */
    public function refresh(array $data, int $ttl = self::DEFAULT_TTL): void
    {
        $this->data = $data;
        $this->expires_at = now()->addSeconds($ttl);
        $this->save();
    }

    /**
     * Invalidate cache (mark as expired)
     */
    public function invalidate(): void
    {
        $this->expires_at = now()->subSecond();
        $this->save();
    }

    /**
     * Create or update cache entry
     */
    public static function updateOrCreateCache(
        int $productId,
        string $partSku,
        array $data,
        ?int $shopId = null,
        int $ttl = self::DEFAULT_TTL
    ): self {
        return static::updateOrCreate(
            [
                'product_id' => $productId,
                'prestashop_shop_id' => $shopId,
            ],
            [
                'part_sku' => $partSku,
                'data' => $data,
                'expires_at' => now()->addSeconds($ttl),
            ]
        );
    }

    /**
     * Get cached data or null if expired
     */
    public static function getCached(
        int $productId,
        ?int $shopId = null
    ): ?array {
        $cache = static::where('product_id', $productId)
            ->where('prestashop_shop_id', $shopId)
            ->notExpired()
            ->first();

        return $cache ? $cache->getData() : null;
    }

    /**
     * Invalidate all cache for product
     */
    public static function invalidateProduct(int $productId): void
    {
        static::where('product_id', $productId)->delete();
    }
}
