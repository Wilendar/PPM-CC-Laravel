<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Feature Model
 *
 * Cecha konkretnego produktu
 * Może używać predefiniowanej wartości (feature_value_id) lub custom value
 *
 * @property int $id
 * @property int $product_id
 * @property int $feature_type_id
 * @property int|null $feature_value_id Predefiniowana wartość (jeśli value_type=select)
 * @property string|null $custom_value Wartość niestandardowa (jeśli nie używa feature_value)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProductFeature extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'product_features';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'product_id',
        'feature_type_id',
        'feature_value_id',
        'custom_value',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'product_id' => 'integer',
        'feature_type_id' => 'integer',
        'feature_value_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Eager load relationships
     */
    protected $with = [
        'featureType',
        'featureValue',
    ];

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
     * Feature type
     */
    public function featureType(): BelongsTo
    {
        return $this->belongsTo(FeatureType::class, 'feature_type_id');
    }

    /**
     * Predefined feature value (nullable - może być custom)
     */
    public function featureValue(): BelongsTo
    {
        return $this->belongsTo(FeatureValue::class, 'feature_value_id');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get value (from FeatureValue OR custom_value)
     */
    public function getValue(): mixed
    {
        if ($this->feature_value_id && $this->featureValue) {
            return $this->featureValue->value;
        }

        return $this->custom_value;
    }

    /**
     * Get display value (formatted with unit)
     */
    public function getDisplayValue(): string
    {
        $value = $this->getValue();

        if ($this->featureType) {
            // Boolean formatting
            if ($this->featureType->isBoolean()) {
                return $value ? 'Tak' : 'Nie';
            }

            // Numeric with unit
            if ($this->featureType->isNumeric() && $this->featureType->unit) {
                return $value . ' ' . $this->featureType->unit;
            }
        }

        return (string) $value;
    }

    /**
     * Check if using predefined value
     */
    public function usesPredefinedValue(): bool
    {
        return $this->feature_value_id !== null;
    }

    /**
     * Check if using custom value
     */
    public function usesCustomValue(): bool
    {
        return $this->custom_value !== null;
    }
}
