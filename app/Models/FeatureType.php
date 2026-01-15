<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature Type Model
 *
 * ETAP_07e - Typ cechy produktu (np. Moc, Pojemnosc, Kolor obudowy)
 *
 * @property int $id
 * @property string $code Unikalny kod (power, capacity, housing_color)
 * @property string $name Nazwa typu
 * @property string $value_type Typ wartosci (text, number, bool, select)
 * @property string|null $unit Jednostka miary (W, L, kg)
 * @property string|null $group Stara grupa (string) - deprecated, use feature_group_id
 * @property int|null $feature_group_id FK do feature_groups
 * @property string|null $input_placeholder Podpowiedz dla inputa
 * @property array|null $validation_rules JSON reguly walidacji
 * @property string|null $conditional_group Grupa warunkowa (elektryczne/spalinowe)
 * @property string|null $excel_column Kolumna Excel (A, B, AA)
 * @property string|null $prestashop_name Nazwa w PrestaShop
 * @property bool $is_active Czy typ aktywny
 * @property int|null $position Kolejnosc wyswietlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read FeatureGroup|null $featureGroup
 * @property-read \Illuminate\Database\Eloquent\Collection|FeatureValue[] $featureValues
 * @property-read \Illuminate\Database\Eloquent\Collection|ProductFeature[] $productFeatures
 * @property-read \Illuminate\Database\Eloquent\Collection|PrestashopFeatureMapping[] $prestashopMappings
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
        'feature_group_id',
        'input_placeholder',
        'validation_rules',
        'conditional_group',
        'excel_column',
        'prestashop_name',
        'is_active',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'feature_group_id' => 'integer',
        'validation_rules' => 'array',
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

    /**
     * Conditional group constants
     */
    public const CONDITIONAL_ELECTRIC = 'elektryczne';
    public const CONDITIONAL_COMBUSTION = 'spalinowe';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Feature group (normalized)
     */
    public function featureGroup(): BelongsTo
    {
        return $this->belongsTo(FeatureGroup::class, 'feature_group_id');
    }

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

    /**
     * PrestaShop mappings for this feature type
     */
    public function prestashopMappings(): HasMany
    {
        return $this->hasMany(PrestashopFeatureMapping::class, 'feature_type_id');
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

    /**
     * Scope: Filter by conditional group (elektryczne/spalinowe)
     */
    public function scopeForConditionalGroup($query, ?string $conditionalGroup)
    {
        return $query->where(function ($q) use ($conditionalGroup) {
            $q->whereNull('conditional_group');
            if ($conditionalGroup) {
                $q->orWhere('conditional_group', $conditionalGroup);
            }
        });
    }

    /**
     * Scope: Filter by feature group ID
     */
    public function scopeInFeatureGroup($query, int $featureGroupId)
    {
        return $query->where('feature_group_id', $featureGroupId);
    }

    /**
     * Scope: Has PrestaShop mapping for shop
     */
    public function scopeWithPrestashopMapping($query, int $shopId)
    {
        return $query->whereHas('prestashopMappings', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)->where('is_active', true);
        });
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

    /**
     * Check if feature is conditional (only for specific vehicle type)
     */
    public function isConditional(): bool
    {
        return $this->conditional_group !== null;
    }

    /**
     * Check if feature should be visible for given vehicle type
     *
     * @param string|null $vehicleType 'elektryczne', 'spalinowe', or null
     */
    public function isVisibleForVehicleType(?string $vehicleType): bool
    {
        if ($this->conditional_group === null) {
            return true;
        }

        return $this->conditional_group === $vehicleType;
    }

    /**
     * Check if feature has unit
     */
    public function hasUnit(): bool
    {
        return !empty($this->unit);
    }

    /**
     * Get display name with unit
     */
    public function getNameWithUnit(): string
    {
        if ($this->hasUnit()) {
            return "{$this->name} ({$this->unit})";
        }

        return $this->name;
    }

    /**
     * Get PrestaShop mapping for shop
     */
    public function getPrestashopMapping(int $shopId): ?PrestashopFeatureMapping
    {
        return $this->prestashopMappings()
                    ->where('shop_id', $shopId)
                    ->where('is_active', true)
                    ->first();
    }

    /**
     * Get PrestaShop feature ID for shop
     */
    public function getPrestashopFeatureId(int $shopId): ?int
    {
        $mapping = $this->getPrestashopMapping($shopId);
        return $mapping?->prestashop_feature_id;
    }

    /**
     * Check if feature is mapped to PrestaShop for shop
     */
    public function isMappedToPrestaShop(int $shopId): bool
    {
        return $this->getPrestashopMapping($shopId) !== null;
    }

    /**
     * Get validation rules as array
     */
    public function getValidationRulesArray(): array
    {
        return $this->validation_rules ?? [];
    }

    /**
     * Get group display name (from FeatureGroup or legacy string)
     */
    public function getGroupDisplayName(): string
    {
        if ($this->featureGroup) {
            return $this->featureGroup->getDisplayName();
        }

        return $this->group ?? 'Inne';
    }

    /**
     * Get conditional group label
     */
    public function getConditionalGroupLabel(): ?string
    {
        return match ($this->conditional_group) {
            self::CONDITIONAL_ELECTRIC => 'Pojazdy elektryczne',
            self::CONDITIONAL_COMBUSTION => 'Pojazdy spalinowe',
            default => null,
        };
    }

    /**
     * Get available conditional groups
     */
    public static function getConditionalGroups(): array
    {
        return [
            self::CONDITIONAL_ELECTRIC => 'Pojazdy elektryczne',
            self::CONDITIONAL_COMBUSTION => 'Pojazdy spalinowe',
        ];
    }
}
