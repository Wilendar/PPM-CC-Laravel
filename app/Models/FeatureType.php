<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature Type Model
 *
 * Typ cechy produktu (np. Moc, Pojemność, Kolor obudowy)
 *
 * @property int $id
 * @property string $code Unikalny kod (power, capacity, housing_color)
 * @property string $name Nazwa typu
 * @property string $value_type Typ wartości (text, number, bool, select)
 * @property string|null $unit Jednostka miary (W, L, kg)
 * @property string|null $group Grupa cechy (Podstawowe, Silnik, Wymiary)
 * @property bool $is_active Czy typ aktywny
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeatureType extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'feature_types';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'code',
        'name',
        'value_type',
        'unit',
        'group',
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
     * Value type enum values
     */
    public const VALUE_TYPE_TEXT = 'text';
    public const VALUE_TYPE_NUMBER = 'number';
    public const VALUE_TYPE_BOOL = 'bool';
    public const VALUE_TYPE_SELECT = 'select';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Predefined values for this feature type (if value_type = select)
     */
    public function featureValues(): HasMany
    {
        return $this->hasMany(FeatureValue::class, 'feature_type_id');
    }

    /**
     * Product features using this type
     */
    public function productFeatures(): HasMany
    {
        return $this->hasMany(ProductFeature::class, 'feature_type_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active feature types
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

    /**
     * Scope: Filter by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Get features grouped by group
     * Returns Collection grouped by 'group' key
     */
    public function scopeGroupedByGroup($query)
    {
        return $query->active()->ordered()->get()->groupBy('group');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this feature requires predefined values
     */
    public function requiresValues(): bool
    {
        return $this->value_type === self::VALUE_TYPE_SELECT;
    }

    /**
     * Check if this is a numeric feature
     */
    public function isNumeric(): bool
    {
        return $this->value_type === self::VALUE_TYPE_NUMBER;
    }

    /**
     * Check if this is a boolean feature
     */
    public function isBoolean(): bool
    {
        return $this->value_type === self::VALUE_TYPE_BOOL;
    }

    /**
     * Get available value types
     */
    public static function getValueTypes(): array
    {
        return [
            self::VALUE_TYPE_TEXT => 'Tekst',
            self::VALUE_TYPE_NUMBER => 'Liczba',
            self::VALUE_TYPE_BOOL => 'Tak/Nie',
            self::VALUE_TYPE_SELECT => 'Lista wyboru',
        ];
    }
}
