<?php

namespace App\Models;

use App\Models\Concerns\BusinessPartner\HasShopSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * BusinessPartner Model - Unified base for Manufacturer, Supplier, Importer
 *
 * Single Table Inheritance via 'type' column with proxy models:
 * - Manufacturer (type = 'manufacturer') - Producent/Marka
 * - Supplier (type = 'supplier') - Dostawca
 * - Importer (type = 'importer') - Importer
 *
 * @property int $id
 * @property string $type One of: supplier, manufacturer, importer
 * @property string $name
 * @property string $code Unique slug-based code
 * @property string|null $company_name
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $city
 * @property string|null $country
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $ps_link_rewrite SEO-friendly URL slug
 * @property string|null $description
 * @property string|null $short_description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $logo_path
 * @property string|null $website
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $subiekt_contractor_id FK to Subiekt GT kh__Kontrahent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Collection|PrestaShopShop[] $shops
 * @property-read Collection|Product[] $productsAsManufacturer
 * @property-read Collection|Product[] $productsAsSupplier
 * @property-read Collection|Product[] $productsAsImporter
 */
class BusinessPartner extends Model
{
    use HasFactory, SoftDeletes;
    use HasShopSync;

    protected $table = 'business_partners';

    /*
    |--------------------------------------------------------------------------
    | TYPE CONSTANTS
    |--------------------------------------------------------------------------
    */

    public const TYPE_SUPPLIER = 'supplier';
    public const TYPE_MANUFACTURER = 'manufacturer';
    public const TYPE_IMPORTER = 'importer';

    public const TYPES = [
        self::TYPE_SUPPLIER,
        self::TYPE_MANUFACTURER,
        self::TYPE_IMPORTER,
    ];

    public const TYPE_LABELS = [
        self::TYPE_SUPPLIER => 'Dostawca',
        self::TYPE_MANUFACTURER => 'Producent',
        self::TYPE_IMPORTER => 'Importer',
    ];

    protected $fillable = [
        'type',
        'name',
        'code',
        'company_name',
        'address',
        'postal_code',
        'city',
        'country',
        'email',
        'phone',
        'ps_link_rewrite',
        'description',
        'short_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'logo_path',
        'website',
        'is_active',
        'sort_order',
        'subiekt_contractor_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'subiekt_contractor_id' => 'integer',
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

    public function productsAsSupplier(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function productsAsManufacturer(): HasMany
    {
        return $this->hasMany(Product::class, 'manufacturer_id');
    }

    public function productsAsImporter(): HasMany
    {
        return $this->hasMany(Product::class, 'importer_id');
    }

    /**
     * Dynamic products() based on partner type.
     */
    public function products(): HasMany
    {
        return match ($this->type) {
            self::TYPE_SUPPLIER => $this->productsAsSupplier(),
            self::TYPE_MANUFACTURER => $this->productsAsManufacturer(),
            self::TYPE_IMPORTER => $this->productsAsImporter(),
            default => $this->productsAsManufacturer(),
        };
    }

    /**
     * Backward compat: PendingProduct uses manufacturer_id FK.
     */
    public function pendingProducts(): HasMany
    {
        return $this->hasMany(PendingProduct::class, 'manufacturer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-generate code from name if not provided.
     */
    public function code(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value
                ? Str::slug($value, '_')
                : Str::slug($this->name ?? '', '_')
        );
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = $this->name;
                if (!$this->is_active) {
                    $name .= ' (Nieaktywna)';
                }
                return $name;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SUPPLIER);
    }

    public function scopeManufacturers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_MANUFACTURER);
    }

    public function scopeImporters(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IMPORTER);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', Str::slug($code, '_'));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | TYPE HELPERS
    |--------------------------------------------------------------------------
    */

    public function isSupplier(): bool
    {
        return $this->type === self::TYPE_SUPPLIER;
    }

    public function isManufacturer(): bool
    {
        return $this->type === self::TYPE_MANUFACTURER;
    }

    public function isImporter(): bool
    {
        return $this->type === self::TYPE_IMPORTER;
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Whether this type syncs to PrestaShop (manufacturer + importer do).
     */
    public function hasPsSync(): bool
    {
        return in_array($this->type, [self::TYPE_MANUFACTURER, self::TYPE_IMPORTER]);
    }

    /**
     * Get the pivot field name for PS entity ID based on type.
     */
    public function getPsEntityField(): ?string
    {
        return match ($this->type) {
            self::TYPE_MANUFACTURER => 'ps_manufacturer_id',
            self::TYPE_IMPORTER, self::TYPE_SUPPLIER => 'ps_supplier_id',
            default => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | CONTACT HELPERS
    |--------------------------------------------------------------------------
    */

    public function hasContactInfo(): bool
    {
        return !empty($this->email)
            || !empty($this->phone)
            || !empty($this->address);
    }

    public function getFullAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            trim(($this->postal_code ?? '') . ' ' . ($this->city ?? '')),
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS
    |--------------------------------------------------------------------------
    */

    public static function findByCode(string $code): ?static
    {
        return static::byCode($code)->active()->first();
    }

    /**
     * Get active partners of given type for dropdown (id + name).
     */
    public static function getForDropdown(string $type): Collection
    {
        return static::active()
            ->byType($type)
            ->ordered()
            ->get(['id', 'name', 'code']);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGO & SEO HELPERS
    |--------------------------------------------------------------------------
    */

    public function hasLogo(): bool
    {
        return !empty($this->logo_path)
            && file_exists(storage_path('app/public/' . $this->logo_path));
    }

    public function getLogoUrl(): ?string
    {
        if (!$this->hasLogo()) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    public function hasSeoData(): bool
    {
        return !empty($this->meta_title)
            || !empty($this->meta_description)
            || !empty($this->meta_keywords);
    }

    public function getSeoCompleteness(): int
    {
        $fields = ['meta_title', 'meta_description', 'meta_keywords', 'short_description'];
        $filled = 0;

        foreach ($fields as $field) {
            if (!empty($this->{$field})) {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC
    |--------------------------------------------------------------------------
    */

    public function canDelete(): bool
    {
        return $this->products()->count() === 0
            && $this->pendingProducts()->count() === 0;
    }
}
