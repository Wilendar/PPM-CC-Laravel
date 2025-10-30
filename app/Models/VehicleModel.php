<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vehicle Model
 *
 * Model pojazdu (np. Honda CBR 600 RR 2013-2020)
 * SKU jako unique identifier (SKU-first architecture)
 *
 * @property int $id
 * @property string $sku Unikalny SKU pojazdu
 * @property string $brand Marka (Honda, Yamaha, Kawasaki)
 * @property string $model Model (CBR 600 RR, MT-09)
 * @property int|null $year_from Rok od
 * @property int|null $year_to Rok do
 * @property string|null $engine_type Typ silnika (4-stroke, 2-stroke)
 * @property int|null $engine_cc Pojemność silnika (cm3)
 * @property bool $is_active Czy aktywny
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VehicleModel extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'vehicle_models';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'sku',
        'brand',
        'model',
        'year_from',
        'year_to',
        'engine_type',
        'engine_cc',
        'is_active',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'year_from' => 'integer',
        'year_to' => 'integer',
        'engine_cc' => 'integer',
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
     * Vehicle compatibility records
     */
    public function vehicleCompatibility(): HasMany
    {
        return $this->hasMany(VehicleCompatibility::class, 'vehicle_model_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active vehicle models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by SKU (SKU-first pattern)
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    /**
     * Scope: Filter by brand
     */
    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope: Filter by model
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model', 'like', '%' . $model . '%');
    }

    /**
     * Scope: Filter by year
     */
    public function scopeByYear($query, int $year)
    {
        return $query->where(function ($q) use ($year) {
            $q->where('year_from', '<=', $year)
              ->where(function ($q2) use ($year) {
                  $q2->where('year_to', '>=', $year)
                     ->orWhereNull('year_to');
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Find vehicle model by SKU (SKU-first architecture)
     */
    public static function findBySku(string $sku): ?self
    {
        return static::where('sku', $sku)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANCE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get full vehicle name
     */
    public function getFullName(): string
    {
        $name = $this->brand . ' ' . $this->model;

        if ($this->year_from || $this->year_to) {
            $yearRange = $this->year_from ?? '?';
            if ($this->year_to && $this->year_to !== $this->year_from) {
                $yearRange .= '-' . $this->year_to;
            }
            $name .= ' (' . $yearRange . ')';
        }

        if ($this->engine_cc) {
            $name .= ' ' . $this->engine_cc . 'cc';
        }

        return $name;
    }

    /**
     * Check if vehicle is active for specific year
     */
    public function isActiveForYear(int $year): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->year_from && $year < $this->year_from) {
            return false;
        }

        if ($this->year_to && $year > $this->year_to) {
            return false;
        }

        return true;
    }

    /**
     * Get year range string
     */
    public function getYearRange(): string
    {
        if (!$this->year_from && !$this->year_to) {
            return 'Wszystkie roczniki';
        }

        $from = $this->year_from ?? '?';
        $to = $this->year_to ?? 'obecnie';

        if ($from === $to) {
            return (string) $from;
        }

        return $from . '-' . $to;
    }
}
