<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attribute Value Model
 *
 * Predefined value for an attribute type (e.g., "Czerwony" for "Kolor")
 *
 * ETAP_05b FAZA 2 - Database-backed attribute values
 *
 * FEATURES:
 * - Dynamic CRUD via AttributeManager service
 * - Color hex support for color types
 * - Sortable with position column
 * - Active/inactive status
 *
 * RELATIONSHIPS:
 * - belongs to AttributeType
 * - has many VariantAttribute (usage tracking)
 *
 * @property int $id
 * @property int $attribute_type_id Foreign key to attribute_types
 * @property string $code Unique code per type (e.g., 'red')
 * @property string $label Display label (e.g., 'Czerwony')
 * @property string|null $color_hex Hex color code (e.g., '#ff0000')
 * @property int $position Sort order
 * @property bool $is_active Active status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @package App\Models
 * @version 1.0
 * @since 2025-10-24
 */
class AttributeValue extends Model
{
    use HasFactory;

    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_type_id',
        'code',
        'label',
        'color_hex',
        'position',
        'is_active',
    ];

    protected $casts = [
        'attribute_type_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Attribute type this value belongs to
     */
    public function attributeType(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class, 'attribute_type_id');
    }

    /**
     * Variant attributes using this value
     */
    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class, 'value_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by attribute type
     */
    public function scopeByType($query, int $typeId)
    {
        return $query->where('attribute_type_id', $typeId);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a color value
     */
    public function hasColor(): bool
    {
        return !empty($this->color_hex);
    }
}
