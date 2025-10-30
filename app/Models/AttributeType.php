<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Attribute Type Model
 *
 * Typ atrybutu wariantu (np. Kolor, Rozmiar, Materiał)
 *
 * @property int $id
 * @property string $code Unikalny kod (color, size, material)
 * @property string $name Nazwa typu
 * @property string $display_type Typ wyświetlania (dropdown, radio, color, button)
 * @property bool $is_active Czy typ aktywny
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AttributeType extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'attribute_types';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'code',
        'name',
        'display_type',
        'is_active',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Display type enum values
     */
    public const DISPLAY_TYPE_DROPDOWN = 'dropdown';
    public const DISPLAY_TYPE_RADIO = 'radio';
    public const DISPLAY_TYPE_COLOR = 'color';
    public const DISPLAY_TYPE_BUTTON = 'button';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Predefined values for this attribute type
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_type_id');
    }

    /**
     * Variant attributes using this type
     */
    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class, 'attribute_type_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active attribute types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
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
     * Check if this is a color attribute
     */
    public function isColorType(): bool
    {
        return $this->display_type === self::DISPLAY_TYPE_COLOR;
    }

    /**
     * Get available display types
     */
    public static function getDisplayTypes(): array
    {
        return [
            self::DISPLAY_TYPE_DROPDOWN => 'Lista rozwijana',
            self::DISPLAY_TYPE_RADIO => 'Radio buttons',
            self::DISPLAY_TYPE_COLOR => 'Próbnik koloru',
            self::DISPLAY_TYPE_BUTTON => 'Przyciski',
        ];
    }
}
