<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Manufacturer Model - Marki produktów w systemie PPM
 *
 * Business Logic:
 * - Marka (producent) przypisana do wielu sklepów PrestaShop
 * - Każdy sklep może mieć inne ps_manufacturer_id (PrestaShop local ID)
 * - Sync status per shop: pending, synced, error
 * - ETAP 07g: Pełna synchronizacja z logo i polami SEO
 *
 * @property int $id
 * @property string $name Nazwa marki (np. "Moretti", "Junak")
 * @property string $code Unikalny kod (np. "moretti", "junak")
 * @property string|null $ps_link_rewrite SEO-friendly URL slug z PrestaShop
 * @property string|null $description Opis marki (pełny)
 * @property string|null $short_description Krótki opis marki
 * @property string|null $meta_title SEO meta title
 * @property string|null $meta_description SEO meta description
 * @property string|null $meta_keywords SEO meta keywords
 * @property string|null $logo_path Ścieżka do logo
 * @property string|null $website URL strony producenta
 * @property bool $is_active Status aktywności
 * @property int $sort_order Kolejność sortowania
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrestaShopShop[] $shops
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int $products_count
 */
class Manufacturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
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
    ];

    protected function casts(): array
    {
        return [
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
     * PrestaShop shops where this manufacturer is available
     * ETAP 07g: Extended pivot with logo sync tracking
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(PrestaShopShop::class, 'manufacturer_shop', 'manufacturer_id', 'prestashop_shop_id')
            ->withPivot([
                'ps_manufacturer_id',
                'sync_status',
                'last_synced_at',
                'logo_synced',
                'logo_synced_at',
                'sync_error',
            ])
            ->withTimestamps();
    }

    /**
     * Products belonging to this manufacturer
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Pending products belonging to this manufacturer
     */
    public function pendingProducts(): HasMany
    {
        return $this->hasMany(PendingProduct::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-generate code from name if not provided
     */
    public function code(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value ? Str::slug($value, '_') : Str::slug($this->name ?? '', '_')
        );
    }

    /**
     * Display name with status
     */
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
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get manufacturer by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::byCode($code)->active()->first();
    }

    /**
     * Get all active manufacturers for dropdown
     */
    public static function getForDropdown(): \Illuminate\Support\Collection
    {
        return static::active()->ordered()->get(['id', 'name', 'code']);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP SYNC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Assign manufacturer to shop with optional PrestaShop ID
     */
    public function assignToShop(int $shopId, ?int $psManufacturerId = null): void
    {
        $this->shops()->syncWithoutDetaching([
            $shopId => [
                'ps_manufacturer_id' => $psManufacturerId,
                'sync_status' => $psManufacturerId ? 'synced' : 'pending',
                'last_synced_at' => $psManufacturerId ? now() : null,
            ]
        ]);
    }

    /**
     * Remove manufacturer from shop
     */
    public function removeFromShop(int $shopId): void
    {
        $this->shops()->detach($shopId);
    }

    /**
     * Get PrestaShop ID for specific shop
     */
    public function getPsIdForShop(int $shopId): ?int
    {
        $pivot = $this->shops()->where('prestashop_shops.id', $shopId)->first()?->pivot;
        return $pivot?->ps_manufacturer_id;
    }

    /**
     * Update sync status for shop
     */
    public function updateSyncStatus(int $shopId, string $status, ?int $psManufacturerId = null): void
    {
        $data = ['sync_status' => $status];

        if ($psManufacturerId !== null) {
            $data['ps_manufacturer_id'] = $psManufacturerId;
        }

        if ($status === 'synced') {
            $data['last_synced_at'] = now();
        }

        $this->shops()->updateExistingPivot($shopId, $data);
    }

    /**
     * Check if synced to specific shop
     */
    public function isSyncedToShop(int $shopId): bool
    {
        return $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->wherePivot('sync_status', 'synced')
            ->exists();
    }

    /**
     * Get sync summary for all shops
     */
    public function getSyncSummary(): array
    {
        $summary = [
            'total' => $this->shops()->count(),
            'synced' => $this->shops()->wherePivot('sync_status', 'synced')->count(),
            'pending' => $this->shops()->wherePivot('sync_status', 'pending')->count(),
            'error' => $this->shops()->wherePivot('sync_status', 'error')->count(),
            'logo_synced' => $this->shops()->wherePivot('logo_synced', true)->count(),
        ];
        $summary['all_synced'] = $summary['total'] > 0 && $summary['total'] === $summary['synced'];
        $summary['all_logos_synced'] = $summary['total'] > 0 && $summary['total'] === $summary['logo_synced'];
        return $summary;
    }

    /*
    |--------------------------------------------------------------------------
    | LOGO SYNC METHODS (ETAP 07g)
    |--------------------------------------------------------------------------
    */

    /**
     * Update logo sync status for shop
     */
    public function updateLogoSyncStatus(int $shopId, bool $synced, ?string $error = null): void
    {
        $data = [
            'logo_synced' => $synced,
            'sync_error' => $error,
        ];

        if ($synced) {
            $data['logo_synced_at'] = now();
        }

        $this->shops()->updateExistingPivot($shopId, $data);
    }

    /**
     * Check if logo synced to specific shop
     */
    public function isLogoSyncedToShop(int $shopId): bool
    {
        return $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->wherePivot('logo_synced', true)
            ->exists();
    }

    /**
     * Get sync error for specific shop
     */
    public function getSyncErrorForShop(int $shopId): ?string
    {
        $pivot = $this->shops()->where('prestashop_shops.id', $shopId)->first()?->pivot;
        return $pivot?->sync_error;
    }

    /**
     * Clear sync error for shop
     */
    public function clearSyncError(int $shopId): void
    {
        $this->shops()->updateExistingPivot($shopId, ['sync_error' => null]);
    }

    /**
     * Set sync error for shop
     */
    public function setSyncError(int $shopId, string $error): void
    {
        $this->shops()->updateExistingPivot($shopId, [
            'sync_status' => 'error',
            'sync_error' => $error,
        ]);
    }

    /**
     * Get full sync details for specific shop
     */
    public function getSyncDetailsForShop(int $shopId): ?array
    {
        $shop = $this->shops()->where('prestashop_shops.id', $shopId)->first();

        if (!$shop) {
            return null;
        }

        return [
            'shop_id' => $shopId,
            'shop_name' => $shop->name,
            'ps_manufacturer_id' => $shop->pivot->ps_manufacturer_id,
            'sync_status' => $shop->pivot->sync_status,
            'last_synced_at' => $shop->pivot->last_synced_at,
            'logo_synced' => $shop->pivot->logo_synced,
            'logo_synced_at' => $shop->pivot->logo_synced_at,
            'sync_error' => $shop->pivot->sync_error,
        ];
    }

    /**
     * Check if has logo file
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path) && file_exists(storage_path('app/public/' . $this->logo_path));
    }

    /**
     * Get logo URL
     */
    public function getLogoUrl(): ?string
    {
        if (!$this->hasLogo()) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC
    |--------------------------------------------------------------------------
    */

    /**
     * Check if manufacturer can be deleted
     */
    public function canDelete(): bool
    {
        return $this->products()->count() === 0 && $this->pendingProducts()->count() === 0;
    }

    /**
     * Check if has SEO data
     */
    public function hasSeoData(): bool
    {
        return !empty($this->meta_title)
            || !empty($this->meta_description)
            || !empty($this->meta_keywords);
    }

    /**
     * Get SEO completeness percentage
     */
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
}
