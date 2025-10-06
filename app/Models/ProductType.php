<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductType Model - Edytowalne typy produktów
 *
 * Zastępuje hardcoded ENUM system elastycznym
 * zarządzaniem typami produktów przez Admin/Manager
 *
 * @property int $id
 * @property string $name Nazwa typu (np. "Części zamienne")
 * @property string $slug Slug URL-friendly (np. "czesci-zamienne")
 * @property string|null $description Opis typu produktu
 * @property string|null $icon Ikona typu (CSS class lub SVG)
 * @property array|null $default_attributes Domyślne atrybuty dla typu
 * @property bool $is_active Status aktywności typu
 * @property int $sort_order Kolejność wyświetlania
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int $products_count
 *
 * @method static Builder active()
 * @method static Builder ordered()
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_05 FAZA 4 - Editable Product Types
 */
class ProductType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'default_attributes',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_attributes' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get products of this type
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get only active product types
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order then name
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Set slug attribute automatically from name
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;

        // Auto-generate slug if not exists
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = \Illuminate\Support\Str::slug($value);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this type can be deleted (no products assigned)
     */
    public function canBeDeleted(): bool
    {
        return $this->products()->count() === 0;
    }

    /**
     * Get default product types for seeding
     */
    public static function getDefaultTypes(): array
    {
        return [
            [
                'name' => 'Pojazd',
                'slug' => 'pojazd',
                'description' => 'Kompletne pojazdy - samochody, motocykle, skutery',
                'icon' => 'fas fa-car',
                'is_active' => true,
                'sort_order' => 1,
                'default_attributes' => [
                    'model' => null,
                    'year' => null,
                    'engine' => null,
                    'vin' => null,
                ]
            ],
            [
                'name' => 'Część zamiennicza',
                'slug' => 'czesc-zamiennicza',
                'description' => 'Części zamienne do pojazdów - silnik, zawieszenie, hamulce',
                'icon' => 'fas fa-cog',
                'is_active' => true,
                'sort_order' => 2,
                'default_attributes' => [
                    'compatibility' => [],
                    'original_number' => null,
                    'replacement_number' => null,
                ]
            ],
            [
                'name' => 'Odzież',
                'slug' => 'odziez',
                'description' => 'Odzież motocyklowa i akcesoria',
                'icon' => 'fas fa-tshirt',
                'is_active' => true,
                'sort_order' => 3,
                'default_attributes' => [
                    'sizes' => [],
                    'colors' => [],
                    'material' => null,
                ]
            ],
            [
                'name' => 'Inne',
                'slug' => 'inne',
                'description' => 'Pozostałe produkty nieskategoryzowane',
                'icon' => 'fas fa-box',
                'is_active' => true,
                'sort_order' => 99,
                'default_attributes' => []
            ],
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate sort_order if not set
        static::creating(function (ProductType $productType) {
            if (is_null($productType->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $productType->sort_order = $maxOrder + 10;
            }
        });
    }
}