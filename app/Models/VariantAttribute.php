<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variant Attribute Model
 *
 * Wartość atrybutu dla konkretnego wariantu
 * (np. Variant "XL Czerwony" ma: attributeType=size, value_id=123 oraz attributeType=color, value_id=456)
 *
 * @property int $id
 * @property int $variant_id
 * @property int $attribute_type_id
 * @property int $value_id FK to attribute_values.id
 * @property string|null $color_hex Kod hex dla koloru (jeśli typ=color)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VariantAttribute extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'variant_attributes';

    /**
     * Fillable attributes
     *
     * UPDATED 2025-12-04: Changed from value/value_code (string) to value_id (FK)
     * per migration 2025_10_28_000001_refactor_variant_attributes_value_id.php
     */
    protected $fillable = [
        'variant_id',
        'attribute_type_id',
        'value_id', // FK to attribute_values.id (replaces old 'value' string)
        'color_hex',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'attribute_type_id' => 'integer',
        'value_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Attribute type
     */
    public function attributeType(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class, 'attribute_type_id');
    }

    /**
     * Attribute value (via value_id FK)
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'value_id');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get display value (formatted for UI)
     */
    public function getDisplayValue(): string
    {
        $value = $this->attributeValue;

        if (!$value) {
            return '';
        }

        if ($this->attributeType && $this->attributeType->isColorType() && $value->color_hex) {
            return sprintf(
                '<span style="display:inline-block;width:20px;height:20px;background:%s;border:1px solid #ccc;margin-right:5px;"></span>%s',
                $value->color_hex,
                $value->value
            );
        }

        return $value->value ?? '';
    }

    /**
     * Check if this is a color attribute
     */
    public function isColor(): bool
    {
        return $this->attributeType && $this->attributeType->isColorType();
    }
}
