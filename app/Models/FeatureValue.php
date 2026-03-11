<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature Value Model
 *
 * Predefiniowana wartość cechy (dla feature_type.value_type = 'select')
 * (np. dla FeatureType=Kolor: FeatureValue=Czarny, Biały, Srebrny)
 *
 * @property int $id
 * @property int $feature_type_id
 * @property string $value Wartość
 * @property bool $is_active Czy wartość aktywna
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeatureValue extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'feature_values';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'feature_type_id',
        'value',
        'is_active',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'feature_type_id' => 'integer',
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Feature type
     */
    public function featureType(): BelongsTo
    {
        return $this->belongsTo(FeatureType::class, 'feature_type_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active feature values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Get display value (with unit if applicable)
     */
    public function getDisplayValue(): string
    {
        $display = $this->value;

        if ($this->featureType && $this->featureType->unit) {
            $display .= ' ' . $this->featureType->unit;
        }

        return $display;
    }
}
