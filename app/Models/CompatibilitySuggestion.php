<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Compatibility Suggestion Model
 *
 * ETAP_05d FAZA 1 - AI-generated compatibility suggestions cache
 *
 * PURPOSE:
 * - Cache SmartSuggestionEngine results (expensive to compute)
 * - Per-shop suggestions (user decision 2025-12-05)
 * - TTL-based expiration (24h default)
 * - Track applied/dismissed suggestions
 *
 * ALGORITHM (SmartSuggestionEngine):
 * - Brand match: product.manufacturer == vehicle.brand → +0.50
 * - Name match: product.name CONTAINS vehicle.model → +0.30
 * - Description match: product.description CONTAINS vehicle → +0.10
 * - Category match: matching category patterns → +0.10
 * - Total confidence: 0.00 - 1.00
 *
 * @property int $id
 * @property int $product_id
 * @property string $part_sku SKU backup for cache key
 * @property int $vehicle_model_id
 * @property string $vehicle_sku SKU backup for cache key
 * @property int $shop_id Per-shop suggestions
 * @property string $suggestion_reason Primary reason (brand_match, name_match, etc.)
 * @property float $confidence_score AI confidence 0.00-1.00
 * @property string $suggested_type Suggested compatibility type (original/replacement)
 * @property bool $is_applied Whether suggestion was applied
 * @property Carbon|null $applied_at When suggestion was applied
 * @property int|null $applied_by User who applied suggestion
 * @property bool $is_dismissed Whether suggestion was dismissed
 * @property Carbon|null $dismissed_at When suggestion was dismissed
 * @property int|null $dismissed_by User who dismissed suggestion
 * @property Carbon $expires_at TTL expiration time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Product $product
 * @property-read VehicleModel $vehicleModel
 * @property-read PrestaShopShop $shop
 * @property-read User|null $applier
 * @property-read User|null $dismisser
 */
class CompatibilitySuggestion extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'compatibility_suggestions';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'product_id',
        'part_sku',
        'vehicle_model_id',
        'vehicle_sku',
        'shop_id',
        'suggestion_reason',
        'confidence_score',
        'suggested_type',
        'is_applied',
        'applied_at',
        'applied_by',
        'is_dismissed',
        'dismissed_at',
        'dismissed_by',
        'expires_at',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'product_id' => 'integer',
        'vehicle_model_id' => 'integer',
        'shop_id' => 'integer',
        'confidence_score' => 'decimal:2',
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
        'applied_by' => 'integer',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
        'dismissed_by' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Suggestion Reason Constants
     */
    public const REASON_BRAND_MATCH = 'brand_match';
    public const REASON_NAME_MATCH = 'name_match';
    public const REASON_DESCRIPTION_MATCH = 'description_match';
    public const REASON_CATEGORY_MATCH = 'category_match';
    public const REASON_SIMILAR_PRODUCT = 'similar_product';

    /**
     * Suggested Type Constants
     */
    public const TYPE_ORIGINAL = 'original';
    public const TYPE_REPLACEMENT = 'replacement';

    /**
     * Default TTL in hours
     */
    public const DEFAULT_TTL_HOURS = 24;

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Product reference
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Vehicle model reference
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /**
     * Shop reference (per-shop suggestions)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * User who applied this suggestion
     */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * User who dismissed this suggestion
     */
    public function dismisser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active (not expired, not applied, not dismissed)
     */
    public function scopeActive($query)
    {
        return $query->where('is_applied', false)
                     ->where('is_dismissed', false)
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope: Only expired suggestions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: Filter by shop
     */
    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by minimum confidence
     */
    public function scopeWithMinConfidence($query, float $minScore)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    /**
     * Scope: High confidence suggestions (>= 0.75)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 0.75);
    }

    /**
     * Scope: Auto-apply ready (>= 0.90 confidence)
     */
    public function scopeAutoApplyReady($query)
    {
        return $query->active()
                     ->where('confidence_score', '>=', 0.90);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Apply this suggestion (create VehicleCompatibility record)
     */
    public function apply(User $user): VehicleCompatibility
    {
        // Create compatibility record
        $compatibility = VehicleCompatibility::create([
            'product_id' => $this->product_id,
            'part_sku' => $this->part_sku,
            'vehicle_model_id' => $this->vehicle_model_id,
            'vehicle_sku' => $this->vehicle_sku,
            'shop_id' => $this->shop_id,
            'is_suggested' => true,
            'confidence_score' => $this->confidence_score,
            'metadata' => [
                'suggestion_id' => $this->id,
                'suggestion_reason' => $this->suggestion_reason,
                'suggested_type' => $this->suggested_type,
            ],
        ]);

        // Mark as applied
        $this->is_applied = true;
        $this->applied_at = now();
        $this->applied_by = $user->id;
        $this->save();

        return $compatibility;
    }

    /**
     * Dismiss this suggestion
     */
    public function dismiss(User $user): void
    {
        $this->is_dismissed = true;
        $this->dismissed_at = now();
        $this->dismissed_by = $user->id;
        $this->save();
    }

    /**
     * Check if suggestion is still valid (not expired)
     */
    public function isValid(): bool
    {
        return !$this->is_applied
            && !$this->is_dismissed
            && $this->expires_at->isFuture();
    }

    /**
     * Check if suggestion is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get human-readable reason
     */
    public function getReasonLabel(): string
    {
        return match ($this->suggestion_reason) {
            self::REASON_BRAND_MATCH => 'Dopasowanie marki',
            self::REASON_NAME_MATCH => 'Dopasowanie nazwy',
            self::REASON_DESCRIPTION_MATCH => 'Dopasowanie opisu',
            self::REASON_CATEGORY_MATCH => 'Dopasowanie kategorii',
            self::REASON_SIMILAR_PRODUCT => 'Podobny produkt',
            default => 'Nieznany powod',
        };
    }

    /**
     * Get confidence level label
     */
    public function getConfidenceLabel(): string
    {
        if ($this->confidence_score >= 0.90) {
            return 'Bardzo wysoka';
        }
        if ($this->confidence_score >= 0.75) {
            return 'Wysoka';
        }
        if ($this->confidence_score >= 0.50) {
            return 'Srednia';
        }
        return 'Niska';
    }

    /**
     * Get badge color based on confidence
     */
    public function getConfidenceBadgeColor(): string
    {
        if ($this->confidence_score >= 0.90) {
            return 'success';
        }
        if ($this->confidence_score >= 0.75) {
            return 'primary';
        }
        if ($this->confidence_score >= 0.50) {
            return 'warning';
        }
        return 'secondary';
    }

    /**
     * Create new suggestion with default TTL
     */
    public static function createWithTTL(array $attributes): self
    {
        $attributes['expires_at'] = $attributes['expires_at']
            ?? now()->addHours(self::DEFAULT_TTL_HOURS);

        return self::create($attributes);
    }

    /**
     * Cleanup expired suggestions
     */
    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }
}
