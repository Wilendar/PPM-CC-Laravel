<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * ProductAttributeValue Model - FAZA C: EAV Values Storage z Inheritance Logic
 * 
 * Przechowuje rzeczywiste wartości atrybutów dla produktów w systemie PPM-CC-Laravel:
 * - Wspiera różne typy danych (text, number, boolean, date, json)
 * - Zaawansowany inheritance system między Product a ProductVariant
 * - Optimized queries dla automotive compatibility (Model/Oryginał/Zamiennik)
 * - Strategic performance dla EAV operations
 * - Validation system z business rules
 * 
 * Enterprise EAV features:
 * - Universal value storage z type casting
 * - Intelligent inheritance logic (variant -> master product)
 * - Effective value resolution z override support
 * - Multi-type value handling (text/number/boolean/date/json)
 * - Performance-optimized dla large datasets
 * - Validation integration z ProductAttribute rules
 * 
 * Inheritance Logic:
 * - Master Product: Definicje bazowe dla wszystkich wariantów
 * - Variant Override: Nadpisanie specyficzne dla wariantu
 * - Effective Value: Wartość używana (own value || inherited value)
 * - Sync mechanism: Automatyczne propagowanie zmian
 * 
 * @property int $id
 * @property int $product_id ID produktu głównego
 * @property int|null $product_variant_id ID wariantu (NULL = wartość dla produktu głównego)
 * @property int $attribute_id ID definicji atrybutu
 * @property string|null $value_text Wartość tekstowa
 * @property float|null $value_number Wartość numeryczna
 * @property bool|null $value_boolean Wartość boolean
 * @property \Carbon\Carbon|null $value_date Wartość daty
 * @property array|null $value_json Złożone dane JSON
 * @property bool $is_inherited Czy dziedziczy z produktu głównego
 * @property bool $is_override Czy nadpisuje wartość z głównego produktu
 * @property bool $is_valid Czy wartość przeszła walidację
 * @property string|null $validation_error Błędy walidacji
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductVariant|null $variant
 * @property-read \App\Models\ProductAttribute $attribute
 * @property-read mixed $value Universal value getter
 * @property-read mixed $effective_value Wartość z uwzględnieniem dziedziczenia
 * @property-read string $formatted_value Sformatowana wartość do wyświetlenia
 * @property-read bool $has_value Czy ma jakąkolwiek wartość
 * @property-read string $value_type Typ wartości (text, number, boolean, date, json)
 * 
 * @method static \Illuminate\Database\Eloquent\Builder forProduct(int $productId)
 * @method static \Illuminate\Database\Eloquent\Builder forVariant(int $variantId)
 * @method static \Illuminate\Database\Eloquent\Builder forAttribute(int $attributeId)
 * @method static \Illuminate\Database\Eloquent\Builder withValue()
 * @method static \Illuminate\Database\Eloquent\Builder inherited()
 * @method static \Illuminate\Database\Eloquent\Builder overrides()
 * @method static \Illuminate\Database\Eloquent\Builder valid()
 * @method static \Illuminate\Database\Eloquent\Builder textValues()
 * @method static \Illuminate\Database\Eloquent\Builder numericValues()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA C - Media & Relations Implementation
 */
class ProductAttributeValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'attribute_id',
        'value_text',
        'value_number',
        'value_boolean',
        'value_date',
        'value_json',
        'is_inherited',
        'is_override',
        'is_valid',
        'validation_error',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'validation_error', // Hide validation errors from API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'product_variant_id' => 'integer',
            'attribute_id' => 'integer',
            'value_number' => 'decimal:6',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
            'value_json' => 'array',
            'is_inherited' => 'boolean',
            'is_override' => 'boolean',
            'is_valid' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-validation i inheritance handling
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-validate value before saving
        static::saving(function ($attributeValue) {
            $attributeValue->validateAndSetStatus();
        });

        // Handle inheritance logic on create/update
        static::saved(function ($attributeValue) {
            $attributeValue->handleInheritanceLogic();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the product this attribute value belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant this attribute value belongs to (if any).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the attribute definition.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Universal value getter (returns value based on attribute type)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function value(): Attribute
    {
        return Attribute::make(
            get: function (): mixed {
                if (!$this->attribute) {
                    return null;
                }

                return match ($this->attribute->attribute_type) {
                    'text' => $this->value_text,
                    'number' => $this->value_number,
                    'boolean' => $this->value_boolean,
                    'date' => $this->value_date,
                    'json', 'select', 'multiselect' => $this->value_json,
                    default => $this->value_text,
                };
            },
            set: function (mixed $value): array {
                if (!$this->attribute) {
                    return [];
                }

                // Clear all value fields first
                $attributes = [
                    'value_text' => null,
                    'value_number' => null,
                    'value_boolean' => null,
                    'value_date' => null,
                    'value_json' => null,
                ];

                // Set appropriate field based on attribute type
                switch ($this->attribute->attribute_type) {
                    case 'text':
                        $attributes['value_text'] = $value ? (string) $value : null;
                        break;
                    case 'number':
                        $attributes['value_number'] = is_numeric($value) ? (float) $value : null;
                        break;
                    case 'boolean':
                        $attributes['value_boolean'] = is_bool($value) ? $value : (bool) $value;
                        break;
                    case 'date':
                        $attributes['value_date'] = $value instanceof Carbon ? $value : Carbon::parse($value);
                        break;
                    case 'json':
                    case 'select':
                    case 'multiselect':
                        $attributes['value_json'] = is_array($value) ? $value : [$value];
                        break;
                    default:
                        $attributes['value_text'] = $value ? (string) $value : null;
                }

                return $attributes;
            }
        );
    }

    /**
     * Get effective value (with inheritance consideration)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function effectiveValue(): Attribute
    {
        return Attribute::make(
            get: function (): mixed {
                // If this is a variant value and it's inherited, get master value
                if ($this->product_variant_id && $this->is_inherited) {
                    $masterValue = static::where('product_id', $this->product_id)
                        ->whereNull('product_variant_id')
                        ->where('attribute_id', $this->attribute_id)
                        ->first();

                    return $masterValue?->value ?? $this->value;
                }

                // Otherwise return own value
                return $this->value;
            }
        );
    }

    /**
     * Get formatted value for display
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedValue(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $value = $this->effective_value;

                if ($value === null) {
                    return '';
                }

                if (!$this->attribute) {
                    return (string) $value;
                }

                return match ($this->attribute->attribute_type) {
                    'boolean' => $value ? 'Tak' : 'Nie',
                    'date' => $value instanceof Carbon ? $value->format('d.m.Y') : $value,
                    'number' => $this->formatNumber($value),
                    'multiselect', 'json' => $this->formatArray($value),
                    default => (string) $value,
                };
            }
        );
    }

    /**
     * Check if has any value
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasValue(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->value_text !== null 
                    || $this->value_number !== null 
                    || $this->value_boolean !== null 
                    || $this->value_date !== null 
                    || !empty($this->value_json);
            }
        );
    }

    /**
     * Get value type based on stored data
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function valueType(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->value_text !== null) return 'text';
                if ($this->value_number !== null) return 'number';
                if ($this->value_boolean !== null) return 'boolean';
                if ($this->value_date !== null) return 'date';
                if (!empty($this->value_json)) return 'json';
                
                return 'empty';
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Values for specific product
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Values for specific variant
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $variantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForVariant(Builder $query, int $variantId): Builder
    {
        return $query->where('product_variant_id', $variantId);
    }

    /**
     * Scope: Values for specific attribute
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $attributeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAttribute(Builder $query, int $attributeId): Builder
    {
        return $query->where('attribute_id', $attributeId);
    }

    /**
     * Scope: Values that have actual data
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValue(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('value_text')
              ->orWhereNotNull('value_number')
              ->orWhereNotNull('value_boolean')
              ->orWhereNotNull('value_date')
              ->orWhereNotNull('value_json');
        });
    }

    /**
     * Scope: Inherited values only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInherited(Builder $query): Builder
    {
        return $query->where('is_inherited', true);
    }

    /**
     * Scope: Override values only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverrides(Builder $query): Builder
    {
        return $query->where('is_override', true);
    }

    /**
     * Scope: Valid values only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope: Text values only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTextValues(Builder $query): Builder
    {
        return $query->whereNotNull('value_text');
    }

    /**
     * Scope: Numeric values only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNumericValues(Builder $query): Builder
    {
        return $query->whereNotNull('value_number');
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Validate value and set validation status
     *
     * @return void
     */
    private function validateAndSetStatus(): void
    {
        if (!$this->attribute) {
            $this->is_valid = false;
            $this->validation_error = 'Missing attribute definition';
            return;
        }

        $validation = $this->attribute->validateValue($this->value);
        
        $this->is_valid = $validation['valid'];
        $this->validation_error = $validation['valid'] ? null : implode(', ', $validation['errors']);
    }

    /**
     * Handle inheritance logic after saving
     *
     * @return void
     */
    private function handleInheritanceLogic(): void
    {
        // If this is a master product value, propagate to variants that should inherit
        if (!$this->product_variant_id) {
            $this->propagateToInheritingVariants();
        }
    }

    /**
     * Propagate value to variants that should inherit
     *
     * @return void
     */
    private function propagateToInheritingVariants(): void
    {
        $variantValues = static::where('product_id', $this->product_id)
            ->whereNotNull('product_variant_id')
            ->where('attribute_id', $this->attribute_id)
            ->where('is_inherited', true)
            ->where('is_override', false)
            ->get();

        foreach ($variantValues as $variantValue) {
            // Copy value from master to variant
            $variantValue->value = $this->value;
            $variantValue->save();
        }
    }

    /**
     * Get inherited value from master product
     *
     * @return mixed
     */
    public function getInheritedValue(): mixed
    {
        if (!$this->product_variant_id) {
            return null; // Master products don't inherit
        }

        $masterValue = static::where('product_id', $this->product_id)
            ->whereNull('product_variant_id')
            ->where('attribute_id', $this->attribute_id)
            ->first();

        return $masterValue?->value;
    }

    /**
     * Check if value is inherited from master
     *
     * @return bool
     */
    public function isInherited(): bool
    {
        return $this->is_inherited;
    }

    /**
     * Sync value with master product (inherit current master value)
     *
     * @return bool
     */
    public function syncWithMaster(): bool
    {
        if (!$this->product_variant_id) {
            return false; // Cannot sync master with itself
        }

        $masterValue = $this->getInheritedValue();
        
        if ($masterValue !== null) {
            $this->value = $masterValue;
            $this->is_inherited = true;
            $this->is_override = false;
            
            return $this->save();
        }

        return false;
    }

    /**
     * Override value from master (stop inheriting)
     *
     * @param mixed $newValue
     * @return bool
     */
    public function overrideValue(mixed $newValue): bool
    {
        $this->value = $newValue;
        $this->is_inherited = false;
        $this->is_override = true;
        
        return $this->save();
    }

    /**
     * Format numeric value with unit
     *
     * @param mixed $value
     * @return string
     */
    private function formatNumber(mixed $value): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        $formatted = number_format((float) $value, 2, ',', ' ');
        
        // Remove trailing zeros
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, ',');

        // Add unit if available
        if ($this->attribute && $this->attribute->unit) {
            $formatted .= ' ' . $this->attribute->unit;
        }

        return $formatted;
    }

    /**
     * Format array/JSON value for display
     *
     * @param mixed $value
     * @return string
     */
    private function formatArray(mixed $value): string
    {
        if (!is_array($value)) {
            return (string) $value;
        }

        if (empty($value)) {
            return '';
        }

        // If it's a simple array of strings, join them
        if (array_is_list($value) && count(array_filter($value, 'is_string')) === count($value)) {
            return implode(', ', $value);
        }

        // For complex arrays, JSON encode
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Clone value to another product or variant
     *
     * @param int $targetProductId
     * @param int|null $targetVariantId
     * @return static|null
     */
    public function cloneToTarget(int $targetProductId, ?int $targetVariantId = null): ?static
    {
        // Check if target already has this attribute
        $existing = static::where('product_id', $targetProductId)
            ->where('product_variant_id', $targetVariantId)
            ->where('attribute_id', $this->attribute_id)
            ->first();

        if ($existing) {
            return null; // Cannot clone to existing attribute value
        }

        $clone = $this->replicate();
        $clone->product_id = $targetProductId;
        $clone->product_variant_id = $targetVariantId;
        $clone->is_inherited = false;
        $clone->is_override = false;
        $clone->save();

        return $clone;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return 'product_attribute_values';
    }
}