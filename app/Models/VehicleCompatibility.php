<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle Compatibility Model
 *
 * Dopasowanie części (Product) do pojazdu (Product type='pojazd')
 *
 * ETAP_05d: Per-shop compatibility with SmartSuggestionEngine support
 * 2025-12-08: Changed vehicle_model_id FK from vehicle_models to products
 *
 * @property int $id
 * @property int $product_id Part (czesc zamienna)
 * @property int $vehicle_model_id Vehicle product (pojazd) - NOW points to products!
 * @property int $shop_id Per-shop compatibility (ETAP_05d)
 * @property int|null $compatibility_attribute_id Typ dopasowania (original/replacement)
 * @property int $compatibility_source_id Źródło informacji
 * @property bool $verified Czy zweryfikowane
 * @property int|null $verified_by User ID weryfikatora
 * @property \Illuminate\Support\Carbon|null $verified_at Data weryfikacji
 * @property string|null $notes Notatki
 * @property bool $is_suggested Added via SmartSuggestionEngine (ETAP_05d)
 * @property float|null $confidence_score AI confidence 0.00-1.00 (ETAP_05d)
 * @property array|null $metadata Additional JSON metadata (ETAP_05d)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Product $product Part (czesc)
 * @property-read \App\Models\Product $vehicleProduct Vehicle (pojazd) - points to products!
 * @property-read \App\Models\PrestaShopShop $shop
 * @property-read \App\Models\CompatibilityAttribute|null $compatibilityAttribute
 * @property-read \App\Models\CompatibilitySource $compatibilitySource
 * @property-read \App\Models\User|null $verifier
 */
class VehicleCompatibility extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'vehicle_compatibility';

    /**
     * Fillable attributes
     *
     * ETAP_05d: Added shop_id, is_suggested, confidence_score, metadata
     */
    protected $fillable = [
        'product_id',
        'vehicle_model_id',
        'shop_id',                      // ETAP_05d: Per-shop compatibility
        'compatibility_attribute_id',
        'compatibility_source_id',
        'verified',                     // Flag: czy zweryfikowane
        'verified_by',
        'verified_at',
        'notes',
        'is_suggested',                 // ETAP_05d: SmartSuggestionEngine
        'confidence_score',             // ETAP_05d: AI confidence 0.00-1.00
        'metadata',                     // ETAP_05d: Additional JSON data
    ];

    /**
     * Attribute casts
     *
     * ETAP_05d: Added shop_id, is_suggested, confidence_score, metadata
     */
    protected $casts = [
        'product_id' => 'integer',
        'vehicle_model_id' => 'integer',
        'shop_id' => 'integer',                 // ETAP_05d: Per-shop
        'compatibility_attribute_id' => 'integer',
        'compatibility_source_id' => 'integer',
        'verified' => 'boolean',                // czy zweryfikowane
        'verified_by' => 'integer',
        'verified_at' => 'datetime',
        'is_suggested' => 'boolean',            // ETAP_05d: SmartSuggestionEngine
        'confidence_score' => 'decimal:2',      // ETAP_05d: 0.00-1.00
        'metadata' => 'array',                  // ETAP_05d: JSON metadata
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Eager load relationships
     * 2025-12-08: Changed vehicleModel to vehicleProduct (FK now points to products)
     */
    protected $with = [
        'vehicleProduct',
        'compatibilityAttribute',
        'compatibilitySource',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent product (part)
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Vehicle product (pojazd) - points to products table
     * 2025-12-08: Changed from VehicleModel to Product
     */
    public function vehicleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'vehicle_model_id');
    }

    /**
     * Alias for backward compatibility
     * @deprecated Use vehicleProduct() instead
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->vehicleProduct();
    }

    /**
     * Shop (ETAP_05d: Per-shop compatibility)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Compatibility attribute (original/replacement/performance)
     */
    public function compatibilityAttribute(): BelongsTo
    {
        return $this->belongsTo(CompatibilityAttribute::class, 'compatibility_attribute_id');
    }

    /**
     * Compatibility source (manufacturer/tecdoc/manual)
     */
    public function compatibilitySource(): BelongsTo
    {
        return $this->belongsTo(CompatibilitySource::class, 'compatibility_source_id');
    }

    /**
     * User who verified this compatibility
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only verified compatibility
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by vehicle model
     */
    public function scopeByVehicle($query, int $vehicleModelId)
    {
        return $query->where('vehicle_model_id', $vehicleModelId);
    }

    /**
     * Scope: Filter by shop (ETAP_05d: Per-shop compatibility)
     */
    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by suggested status (ETAP_05d: SmartSuggestionEngine)
     */
    public function scopeSuggested($query)
    {
        return $query->where('is_suggested', true);
    }

    /**
     * Scope: Filter by minimum confidence (ETAP_05d: SmartSuggestionEngine)
     */
    public function scopeWithMinConfidence($query, float $minScore)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Verify this compatibility record
     */
    public function verify(User $user): void
    {
        $this->verified = true;
        $this->verified_by = $user->id;
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Check if verified
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Get display attribute (original, replacement, etc.)
     */
    public function getDisplayAttribute(): string
    {
        if ($this->compatibilityAttribute) {
            return $this->compatibilityAttribute->name;
        }

        return 'Standard';
    }

    /**
     * Get trust level from source
     */
    public function getTrustLevel(): string
    {
        if ($this->is_verified) {
            return CompatibilitySource::TRUST_VERIFIED;
        }

        return $this->compatibilitySource->trust_level;
    }

    /**
     * Get badge HTML for compatibility type
     */
    public function getTypeBadge(): string
    {
        if ($this->compatibilityAttribute) {
            return $this->compatibilityAttribute->getBadgeHtml();
        }

        return '<span class="badge badge-secondary">Standard</span>';
    }

    /**
     * Get trust badge HTML
     */
    public function getTrustBadge(): string
    {
        $color = $this->verified
            ? 'success'
            : $this->compatibilitySource->getTrustBadgeColor();

        $label = $this->verified
            ? 'Zweryfikowane'
            : $this->compatibilitySource->getTrustLevelName();

        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $color,
            htmlspecialchars($label)
        );
    }
}
