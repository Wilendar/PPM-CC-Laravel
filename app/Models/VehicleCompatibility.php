<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle Compatibility Model
 *
 * Dopasowanie części (Product) do pojazdu (VehicleModel)
 * SKU-first pattern z backup columns (part_sku, vehicle_sku)
 *
 * @property int $id
 * @property int $product_id
 * @property string $part_sku SKU części (backup for SKU-first)
 * @property int $vehicle_model_id
 * @property string $vehicle_sku SKU pojazdu (backup for SKU-first)
 * @property int|null $compatibility_attribute_id Typ dopasowania (original/replacement)
 * @property int $compatibility_source_id Źródło informacji
 * @property bool $is_verified Czy zweryfikowane
 * @property int|null $verified_by User ID weryfikatora
 * @property \Illuminate\Support\Carbon|null $verified_at Data weryfikacji
 * @property string|null $notes Notatki
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
     */
    protected $fillable = [
        'product_id',
        'part_sku',
        'vehicle_model_id',
        'vehicle_sku',
        'compatibility_attribute_id',
        'compatibility_source_id',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'product_id' => 'integer',
        'vehicle_model_id' => 'integer',
        'compatibility_attribute_id' => 'integer',
        'compatibility_source_id' => 'integer',
        'is_verified' => 'boolean',
        'verified_by' => 'integer',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Eager load relationships
     */
    protected $with = [
        'vehicleModel',
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
     * Vehicle model
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
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
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Find by part SKU (SKU-first pattern)
     */
    public function scopeByPartSku($query, string $sku)
    {
        return $query->where('part_sku', $sku);
    }

    /**
     * Scope: Find by vehicle SKU (SKU-first pattern)
     */
    public function scopeByVehicleSku($query, string $sku)
    {
        return $query->where('vehicle_sku', $sku);
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
        $this->is_verified = true;
        $this->verified_by = $user->id;
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Check if verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
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
        $color = $this->is_verified
            ? 'success'
            : $this->compatibilitySource->getTrustBadgeColor();

        $label = $this->is_verified
            ? 'Zweryfikowane'
            : $this->compatibilitySource->getTrustLevelName();

        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $color,
            htmlspecialchars($label)
        );
    }
}
