<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variant Attribute Model
 *
 * Wartość atrybutu dla konkretnego wariantu
 * (np. Variant "XL Czerwony" ma: attributeType=size, value=XL oraz attributeType=color, value=Czerwony)
 *
 * @property int $id
 * @property int $variant_id
 * @property int $attribute_type_id
 * @property string $value Wartość atrybutu (text)
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
     */
    protected $fillable = [
        'variant_id',
        'attribute_type_id',
        'value',
        'value_code',
        'color_hex',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'attribute_type_id' => 'integer',
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
        if ($this->attributeType->isColorType() && $this->color_hex) {
            return sprintf(
                '<span style="display:inline-block;width:20px;height:20px;background:%s;border:1px solid #ccc;margin-right:5px;"></span>%s',
                $this->color_hex,
                $this->value
            );
        }

        return $this->value;
    }

    /**
     * Check if this is a color attribute
     */
    public function isColor(): bool
    {
        return $this->attributeType && $this->attributeType->isColorType();
    }
}
