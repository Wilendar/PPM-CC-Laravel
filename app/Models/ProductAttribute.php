<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

/**
 * ProductAttribute Model - FAZA C: EAV System dla Atrybutów Produktów
 * 
 * Definiuje dostępne atrybuty dla produktów w systemie PPM-CC-Laravel:
 * - Model (multiselect) - Yamaha YZ250F 2023, Honda CRF450R, etc.
 * - Oryginał (text) - OEM part numbers
 * - Zamiennik (text) - aftermarket equivalents
 * - Kolor (select) - Red, Blue, Black, etc.
 * - Rozmiar (text) - XS, S, M, L, XL dla odzieży
 * - Materiał (select) - Plastic, Metal, Carbon, etc.
 * 
 * Enterprise EAV features:
 * - Flexible attribute types (text, number, boolean, select, multiselect, date, json)
 * - Advanced validation rules w JSONB storage
 * - Form generation metadata (display groups, sorting)
 * - Filterable attributes dla search functionality
 * - Variant-specific attribute support
 * - Multi-language ready structure
 * 
 * Performance optimizations:
 * - Strategic indexing dla frequent lookups by code
 * - Efficient form generation queries
 * - Cached validation rules
 * - Optimized filterable attributes queries
 * 
 * @property int $id
 * @property string $name Nazwa atrybutu (Model, Oryginał, Zamiennik)
 * @property string $code Kod atrybutu (model, original, replacement)
 * @property string $attribute_type text|number|boolean|select|multiselect|date|json
 * @property bool $is_required Czy wymagane przy dodawaniu produktu
 * @property bool $is_filterable Czy można filtrować w wyszukiwaniu
 * @property bool $is_variant_specific Czy może różnić się między wariantami
 * @property int $sort_order Kolejność wyświetlania w formularzu
 * @property string $display_group Grupa wyświetlania (general, technical, compatibility)
 * @property array|null $validation_rules Reguły walidacji w JSONB
 * @property array|null $options Opcje dla select/multiselect
 * @property string|null $default_value Domyślna wartość
 * @property string|null $help_text Tekst pomocy dla użytkownika
 * @property string|null $unit Jednostka miary (kg, cm, L)
 * @property string|null $format_pattern Pattern formatowania wyświetlania
 * @property bool $is_active Status aktywności
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductAttributeValue[] $values
 * @property-read array $validation_rules_parsed
 * @property-read array $options_parsed
 * @property-read bool $has_options
 * @property-read bool $is_select
 * @property-read bool $is_multiselect
 * @property-read string $display_name
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder automotive()
 * @method static \Illuminate\Database\Eloquent\Builder vehicleCompatibility()
 * @method static \Illuminate\Database\Eloquent\Builder filterable()
 * @method static \Illuminate\Database\Eloquent\Builder required()
 * @method static \Illuminate\Database\Eloquent\Builder variantSpecific()
 * @method static \Illuminate\Database\Eloquent\Builder byGroup(string $group)
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA C - Media & Relations Implementation
 */
class ProductAttribute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'code',
        'attribute_type',
        'is_required',
        'is_filterable',
        'is_variant_specific',
        'sort_order',
        'display_group',
        'validation_rules',
        'options',
        'default_value',
        'help_text',
        'unit',
        'format_pattern',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        // No sensitive data to hide
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_variant_specific' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'validation_rules' => 'array',
            'options' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-generation kodu z nazwy jeśli nie podano
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generate unique code from name if not provided
        static::creating(function ($attribute) {
            if (empty($attribute->code)) {
                $attribute->code = $attribute->generateUniqueCode($attribute->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Get all attribute values for this attribute.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Get parsed validation rules as array
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function validationRulesParsed(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (empty($this->validation_rules)) {
                    return [];
                }
                
                $rules = [];
                $rawRules = is_array($this->validation_rules) ? $this->validation_rules : [];
                
                // Convert to Laravel validation format
                foreach ($rawRules as $rule => $value) {
                    switch ($rule) {
                        case 'min_length':
                            $rules[] = "min:{$value}";
                            break;
                        case 'max_length':
                            $rules[] = "max:{$value}";
                            break;
                        case 'min_value':
                            if ($this->attribute_type === 'number') {
                                $rules[] = "min:{$value}";
                            }
                            break;
                        case 'max_value':
                            if ($this->attribute_type === 'number') {
                                $rules[] = "max:{$value}";
                            }
                            break;
                        case 'pattern':
                            $rules[] = "regex:{$value}";
                            break;
                        case 'required':
                            if ($value) {
                                $rules[] = 'required';
                            }
                            break;
                    }
                }
                
                // Add type-based validation
                switch ($this->attribute_type) {
                    case 'number':
                        $rules[] = 'numeric';
                        break;
                    case 'boolean':
                        $rules[] = 'boolean';
                        break;
                    case 'date':
                        $rules[] = 'date';
                        break;
                    case 'select':
                        if ($this->has_options) {
                            $options = collect($this->options_parsed)->pluck('value')->toArray();
                            $rules[] = 'in:' . implode(',', $options);
                        }
                        break;
                    case 'multiselect':
                        $rules[] = 'array';
                        if ($this->has_options) {
                            $options = collect($this->options_parsed)->pluck('value')->toArray();
                            $rules[] = 'in:' . implode(',', $options);
                        }
                        break;
                }
                
                return $rules;
            }
        );
    }

    /**
     * Get parsed options as standardized array
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function optionsParsed(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (empty($this->options) || !is_array($this->options)) {
                    return [];
                }
                
                $parsed = [];
                
                foreach ($this->options as $option) {
                    // Handle different option formats
                    if (is_string($option)) {
                        $parsed[] = [
                            'value' => $option,
                            'label' => $option,
                        ];
                    } elseif (is_array($option)) {
                        $parsed[] = [
                            'value' => $option['value'] ?? $option['label'] ?? '',
                            'label' => $option['label'] ?? $option['value'] ?? '',
                            'description' => $option['description'] ?? null,
                            'is_active' => $option['is_active'] ?? true,
                        ];
                    }
                }
                
                return $parsed;
            }
        );
    }

    /**
     * Check if attribute has options (for select/multiselect)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasOptions(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->attribute_type, ['select', 'multiselect']) && !empty($this->options)
        );
    }

    /**
     * Check if attribute is select type
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isSelect(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->attribute_type === 'select'
        );
    }

    /**
     * Check if attribute is multiselect type
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isMultiselect(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->attribute_type === 'multiselect'
        );
    }

    /**
     * Get display name with unit if applicable
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = $this->name;
                
                if (!empty($this->unit)) {
                    $name .= " ({$this->unit})";
                }
                
                return $name;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active attributes only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by attribute type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('attribute_type', $type);
    }

    /**
     * Scope: Automotive-specific attributes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAutomotive(Builder $query): Builder
    {
        return $query->whereIn('code', [
            'model',        // Model pojazdu
            'original',     // Numer oryginału OEM  
            'replacement',  // Numer zamiennika
            'engine',       // Typ silnika
            'year_from',    // Rok produkcji od
            'year_to',      // Rok produkcji do
            'vin_range',    // Zakres VIN
            'body_type',    // Typ nadwozia
            'fuel_type',    // Typ paliwa
        ]);
    }

    /**
     * Scope: Vehicle compatibility attributes (Model/Oryginał/Zamiennik)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVehicleCompatibility(Builder $query): Builder
    {
        return $query->whereIn('code', ['model', 'original', 'replacement']);
    }

    /**
     * Scope: Filterable attributes only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    /**
     * Scope: Required attributes only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Variant-specific attributes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVariantSpecific(Builder $query): Builder
    {
        return $query->where('is_variant_specific', true);
    }

    /**
     * Scope: Filter by display group
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('display_group', $group);
    }

    /**
     * Scope: Ordered by sort_order and name
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Validate value against attribute rules
     *
     * @param mixed $value
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateValue($value): array
    {
        $rules = $this->validation_rules_parsed;
        
        if (empty($rules)) {
            return ['valid' => true, 'errors' => []];
        }
        
        // Use Laravel validator
        $validator = validator(
            ['value' => $value],
            ['value' => $rules]
        );
        
        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->get('value'),
        ];
    }

    /**
     * Check if attribute is required
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Check if attribute is filterable in search
     *
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->is_filterable;
    }

    /**
     * Generate unique code from name
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name, '_');
        $code = $baseCode;
        $counter = 1;
        
        while (static::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Add new option to select/multiselect attribute
     *
     * @param string $value
     * @param string|null $label
     * @param string|null $description
     * @return bool
     */
    public function addOption(string $value, ?string $label = null, ?string $description = null): bool
    {
        if (!$this->has_options) {
            return false;
        }
        
        $options = $this->options ?? [];
        
        // Check if option already exists
        $existingValues = collect($options)->pluck('value')->toArray();
        if (in_array($value, $existingValues)) {
            return false;
        }
        
        $options[] = [
            'value' => $value,
            'label' => $label ?? $value,
            'description' => $description,
            'is_active' => true,
        ];
        
        $this->options = $options;
        
        return $this->save();
    }

    /**
     * Remove option from select/multiselect attribute
     *
     * @param string $value
     * @return bool
     */
    public function removeOption(string $value): bool
    {
        if (!$this->has_options) {
            return false;
        }
        
        $options = $this->options ?? [];
        $filteredOptions = collect($options)
            ->filter(fn ($option) => $option['value'] !== $value)
            ->values()
            ->toArray();
        
        $this->options = $filteredOptions;
        
        return $this->save();
    }

    /**
     * Get attribute values count for statistics
     *
     * @return int
     */
    public function getValuesCount(): int
    {
        return $this->values()->count();
    }

    /**
     * Get unique values for this attribute (for filter generation)
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getUniqueValues(int $limit = 50): \Illuminate\Support\Collection
    {
        return $this->values()
            ->select('value_text')
            ->distinct()
            ->whereNotNull('value_text')
            ->limit($limit)
            ->pluck('value_text')
            ->filter()
            ->unique();
    }

    /**
     * Clone attribute with new name/code
     *
     * @param string $newName
     * @param string|null $newCode
     * @return static
     */
    public function cloneAttribute(string $newName, ?string $newCode = null): static
    {
        $clone = $this->replicate();
        $clone->name = $newName;
        $clone->code = $newCode ?? $this->generateUniqueCode($newName);
        $clone->save();
        
        return $clone;
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'code'; // Use code instead of ID for more readable URLs
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try code first, then ID as fallback
        return $this->where('code', $value)->first() 
            ?? $this->where('id', $value)->first();
    }
}