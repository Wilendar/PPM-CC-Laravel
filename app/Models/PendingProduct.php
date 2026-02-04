<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * PendingProduct Model
 *
 * ETAP_06 Import/Export - System IMPORT DO PPM
 *
 * Reprezentuje produkt w stanie DRAFT - zaimportowany ale jeszcze
 * nie opublikowany do glownej tabeli products.
 *
 * Produkty z tej tabeli NIE pojawiaja sie w ProductList dopoki
 * nie zostana opublikowane (przeniesione do tabeli products).
 *
 * @property int $id
 * @property string|null $sku
 * @property string|null $name
 * @property string|null $slug
 * @property int|null $product_type_id
 * @property string|null $manufacturer
 * @property int|null $manufacturer_id
 * @property int|null $supplier_id
 * @property int|null $importer_id
 * @property string|null $supplier_code
 * @property string|null $ean
 * @property array|null $category_ids
 * @property array|null $temp_media_paths
 * @property int $primary_media_index
 * @property array|null $shop_ids
 * @property array|null $shop_categories
 * @property float|null $weight
 * @property float|null $height
 * @property float|null $width
 * @property float|null $length
 * @property float $tax_rate
 * @property string|null $short_description
 * @property string|null $long_description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array|null $meta_keywords
 * @property array|null $variant_data
 * @property array|null $compatibility_data
 * @property array|null $feature_data
 * @property float|null $base_price
 * @property float|null $purchase_price
 * @property array|null $completion_status
 * @property int $completion_percentage
 * @property bool $is_ready_for_publish
 * @property bool $skip_features
 * @property bool $skip_compatibility
 * @property bool $skip_images
 * @property bool $skip_descriptions
 * @property array|null $skip_history
 * @property int|null $import_session_id
 * @property int $imported_by
 * @property \Carbon\Carbon|null $imported_at
 * @property string|null $cn_code
 * @property string|null $material
 * @property string|null $defect_symbol
 * @property string|null $application
 * @property bool $split_payment
 * @property bool $shop_internet
 * @property bool $is_variant_master
 * @property array|null $price_data
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon|null $scheduled_publish_at
 * @property array|null $publication_targets
 * @property string $publish_status
 * @property int|null $published_as_product_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read ImportSession|null $importSession
 * @property-read User $importer
 * @property-read ProductType|null $productType
 * @property-read Manufacturer|null $manufacturer
 * @property-read Product|null $publishedProduct
 * @property-read \Illuminate\Database\Eloquent\Collection|PublishHistory[] $publishHistory
 *
 * @package App\Models
 * @since 2025-12-08
 */
class PendingProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'pending_products';

    /**
     * Required fields for publication
     * FAZA 9.1: publication_targets replaces shop_ids
     */
    public const REQUIRED_FIELDS = [
        'sku',
        'name',
        'manufacturer_id',
        'category_ids',
        'product_type_id',
        'publication_targets',
    ];

    /**
     * Optional fields (improve completion but not required)
     * FAZA 6.5.5: Removed 'manufacturer' (now REQUIRED)
     */
    public const OPTIONAL_FIELDS = [
        'temp_media_paths',
        'short_description',
        'long_description',
        'base_price',
        'variant_data',
        'compatibility_data',
        'feature_data',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sku',
        'name',
        'slug',
        'product_type_id',
        'manufacturer',
        'manufacturer_id',
        'supplier_id',
        'importer_id',
        'supplier_code',
        'ean',
        'category_ids',
        'temp_media_paths',
        'primary_media_index',
        'shop_ids',
        'shop_categories',
        'weight',
        'height',
        'width',
        'length',
        'tax_rate',
        'short_description',
        'long_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'variant_data',
        'compatibility_data',
        'feature_data',
        'base_price',
        'purchase_price',
        'completion_status',
        'completion_percentage',
        'is_ready_for_publish',
        'skip_features',
        'skip_compatibility',
        'skip_images',
        'skip_descriptions',
        'skip_history',
        'cn_code',
        'material',
        'defect_symbol',
        'application',
        'split_payment',
        'shop_internet',
        'is_variant_master',
        'price_data',
        'scheduled_publish_at',
        'publication_targets',
        'publish_status',
        'import_session_id',
        'imported_by',
        'imported_at',
        'published_at',
        'published_as_product_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'category_ids' => 'array',
            'temp_media_paths' => 'array',
            'shop_ids' => 'array',
            'shop_categories' => 'array',
            'meta_keywords' => 'array',
            'variant_data' => 'array',
            'compatibility_data' => 'array',
            'feature_data' => 'array',
            'completion_status' => 'array',
            'skip_features' => 'boolean',
            'skip_compatibility' => 'boolean',
            'skip_images' => 'boolean',
            'skip_descriptions' => 'boolean',
            'skip_history' => 'array',
            'price_data' => 'array',
            'publication_targets' => 'array',
            'split_payment' => 'boolean',
            'shop_internet' => 'boolean',
            'is_variant_master' => 'boolean',
            'scheduled_publish_at' => 'datetime',
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'base_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'primary_media_index' => 'integer',
            'completion_percentage' => 'integer',
            'is_ready_for_publish' => 'boolean',
            'imported_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate slug from name
        static::saving(function (PendingProduct $product) {
            if ($product->name && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }

            // Recalculate completion on every save
            $product->recalculateCompletion();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Import session this product belongs to
     */
    public function importSession(): BelongsTo
    {
        return $this->belongsTo(ImportSession::class, 'import_session_id');
    }

    /**
     * User who imported this product
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Product type
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    /**
     * Manufacturer (brand) - points to BusinessPartner
     */
    public function manufacturerRelation(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'manufacturer_id');
    }

    /**
     * Supplier (dostawca) - points to BusinessPartner
     */
    public function supplierRelation(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'supplier_id');
    }

    /**
     * Importer - points to BusinessPartner
     */
    public function importerRelation(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'importer_id');
    }

    /**
     * Published product (after publication)
     */
    public function publishedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'published_as_product_id');
    }

    /**
     * Publication history records
     */
    public function publishHistory(): HasMany
    {
        return $this->hasMany(PublishHistory::class, 'pending_product_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Incomplete products (not ready for publish)
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_ready_for_publish', false);
    }

    /**
     * Scope: Ready for publication
     */
    public function scopeReadyForPublish(Builder $query): Builder
    {
        return $query->where('is_ready_for_publish', true);
    }

    /**
     * Scope: Not yet published
     */
    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->whereNull('published_at');
    }

    /**
     * Scope: Already published
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope: By import session
     */
    public function scopeBySession(Builder $query, int $sessionId): Builder
    {
        return $query->where('import_session_id', $sessionId);
    }

    /**
     * Scope: By importer user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('imported_by', $userId);
    }

    /**
     * Scope: By completion percentage range
     */
    public function scopeByCompletion(Builder $query, int $min, int $max = 100): Builder
    {
        return $query->whereBetween('completion_percentage', [$min, $max]);
    }

    /**
     * Scope: Fully complete (100% completion_percentage)
     *
     * Uzywane w statystykach panelu importu.
     * "Gotowe" = completion_percentage == 100
     */
    public function scopeFullyComplete(Builder $query): Builder
    {
        return $query->where('completion_percentage', 100);
    }

    /**
     * Scope: Partially complete (< 100% completion_percentage)
     *
     * Uzywane w statystykach panelu importu.
     * "Niekompletne" = completion_percentage < 100
     */
    public function scopePartiallyComplete(Builder $query): Builder
    {
        return $query->where('completion_percentage', '<', 100);
    }

    /**
     * Scope: With minimum completion
     */
    public function scopeMinCompletion(Builder $query, int $min): Builder
    {
        return $query->where('completion_percentage', '>=', $min);
    }

    /**
     * Scope: By product type
     */
    public function scopeByProductType(Builder $query, int $typeId): Builder
    {
        return $query->where('product_type_id', $typeId);
    }

    /**
     * Scope: Has images
     */
    public function scopeHasImages(Builder $query): Builder
    {
        return $query->whereNotNull('temp_media_paths')
                     ->whereRaw('JSON_LENGTH(temp_media_paths) > 0');
    }

    /**
     * Scope: Missing images
     */
    public function scopeMissingImages(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('temp_media_paths')
              ->orWhereRaw('JSON_LENGTH(temp_media_paths) = 0');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETION CALCULATION
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate completion status and percentage
     *
     * Uwzglednia:
     * - Skip flags (skip_features, skip_compatibility, skip_images)
     * - Typ produktu (Pojazd = cechy, Czesc zamiennicza = dopasowania)
     * - Warianty NIE wplywaja na progress
     */
    public function recalculateCompletion(): void
    {
        $status = [];
        $requiredComplete = 0;
        $optionalComplete = 0;

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            $isComplete = $this->isFieldComplete($field);
            $status[$field] = $isComplete;
            if ($isComplete) {
                $requiredComplete++;
            }
        }

        // Get dynamic optional fields based on product type
        $optionalFields = $this->getOptionalFieldsForType();

        // Check optional fields with skip flag logic
        foreach ($optionalFields as $field) {
            $isComplete = $this->isFieldCompleteWithSkip($field);
            $status[$field] = $isComplete;
            if ($isComplete) {
                $optionalComplete++;
            }
        }

        // Calculate percentage
        // Required fields = 80% weight, optional = 20% weight
        $requiredPercentage = (count(self::REQUIRED_FIELDS) > 0)
            ? ($requiredComplete / count(self::REQUIRED_FIELDS)) * 80
            : 80;

        $optionalPercentage = (count($optionalFields) > 0)
            ? ($optionalComplete / count($optionalFields)) * 20
            : 0;

        $totalPercentage = (int) round($requiredPercentage + $optionalPercentage);

        // Is ready for publish = all required fields complete
        $isReady = $requiredComplete === count(self::REQUIRED_FIELDS);

        $this->completion_status = $status;
        $this->completion_percentage = $totalPercentage;
        $this->is_ready_for_publish = $isReady;
    }

    /**
     * Get optional fields based on product type
     *
     * FAZA 6.5.5: Opisy (short/long_description) sa teraz OPCJONALNE
     * i wplywaja na completion % (20% wagi)
     *
     * - Pojazd: cechy (feature_data), zdjecia, opisy
     * - Czesc zamiennicza: dopasowania (compatibility_data), zdjecia, opisy
     * - Inne: zdjecia, opisy
     * - Warianty sa zawsze wylaczone z progress
     */
    protected function getOptionalFieldsForType(): array
    {
        $productTypeSlug = $this->productType?->slug ?? null;

        // FAZA 6.5.5: Opisy i zdjecia dla wszystkich typow
        $fields = [
            'temp_media_paths',    // Zdjecia - dla wszystkich typow
            'short_description',   // Krotki opis - dla wszystkich typow
            'long_description',    // Pelny opis - dla wszystkich typow
        ];

        // Add type-specific fields
        if ($productTypeSlug === 'pojazd') {
            $fields[] = 'feature_data';  // Cechy dla Pojazdu
        } elseif ($productTypeSlug === 'czesc-zamienna') {
            $fields[] = 'compatibility_data';  // Dopasowania dla Czesci zamienniczej
        }

        return $fields;
    }

    /**
     * Check if field is complete, considering skip flags
     * FAZA 6.5.4: Added skip_descriptions support for short/long_description
     */
    protected function isFieldCompleteWithSkip(string $field): bool
    {
        // Check skip flags first
        if ($field === 'feature_data' && $this->skip_features) {
            return true;
        }
        if ($field === 'compatibility_data' && $this->skip_compatibility) {
            return true;
        }
        if ($field === 'temp_media_paths' && $this->skip_images) {
            return true;
        }
        // FAZA 6.5.4: Skip descriptions treats short/long as complete
        if (($field === 'short_description' || $field === 'long_description') && $this->skip_descriptions) {
            return true;
        }

        return $this->isFieldComplete($field);
    }

    /**
     * Check if a specific field is complete
     *
     * Special handling for nested data structures:
     * - temp_media_paths: check images array inside
     * - compatibility_data: check compatibilities array inside
     * - feature_data: check features array inside
     * - publication_targets: check erp_connections or prestashop_shops (+ legacy erp_primary)
     * - price_data: check groups array inside
     */
    protected function isFieldComplete(string $field): bool
    {
        $value = $this->getAttribute($field);

        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value)) {
            // Empty array = not complete
            if (empty($value)) {
                return false;
            }

            // Special handling for nested structures
            if ($field === 'temp_media_paths') {
                $images = $value['images'] ?? [];
                return !empty($images);
            }

            if ($field === 'compatibility_data') {
                $compatibilities = $value['compatibilities'] ?? [];
                return !empty($compatibilities);
            }

            if ($field === 'feature_data') {
                $features = $value['features'] ?? [];
                return !empty($features);
            }

            // GRUPA D: publication_targets - needs erp_connections or prestashop_shops
            // Backward compat: also accepts legacy erp_primary boolean
            if ($field === 'publication_targets') {
                $hasErpConnections = !empty($value['erp_connections']);
                $hasLegacyErp = !empty($value['erp_primary']);
                $hasShops = !empty($value['prestashop_shops']);
                return $hasErpConnections || $hasLegacyErp || $hasShops;
            }

            // FAZA 9.1: price_data - needs groups with at least one price
            if ($field === 'price_data') {
                $groups = $value['groups'] ?? [];
                return !empty($groups);
            }
        }

        return true;
    }

    /**
     * Get missing required fields
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!$this->isFieldComplete($field)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Get completion details for UI
     */
    public function getCompletionDetails(): array
    {
        $status = $this->completion_status ?? [];
        $missing = [];
        $complete = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if ($status[$field] ?? false) {
                $complete[] = $this->getFieldLabel($field);
            } else {
                $missing[] = $this->getFieldLabel($field);
            }
        }

        return [
            'percentage' => $this->completion_percentage,
            'is_ready' => $this->is_ready_for_publish,
            'missing_required' => $missing,
            'complete' => $complete,
            'missing_optional' => array_filter(
                self::OPTIONAL_FIELDS,
                fn($f) => !($status[$f] ?? false)
            ),
        ];
    }

    /**
     * Get human-readable field label
     */
    protected function getFieldLabel(string $field): string
    {
        return match ($field) {
            'sku' => 'SKU',
            'name' => 'Nazwa',
            'category_ids' => 'Kategorie',
            'product_type_id' => 'Typ produktu',
            'shop_ids' => 'Sklepy',
            'publication_targets' => 'Publikacja',
            'temp_media_paths' => 'Zdjecia',
            'short_description' => 'Krotki opis',
            'long_description' => 'Pelny opis',
            'manufacturer' => 'Producent',
            'manufacturer_id' => 'Marka',
            'base_price' => 'Cena bazowa',
            'price_data' => 'Ceny grup cenowych',
            'variant_data' => 'Warianty',
            'compatibility_data' => 'Dopasowania',
            'feature_data' => 'Cechy',
            'cn_code' => 'Kod CN',
            'material' => 'Material',
            'defect_symbol' => 'Symbol z wada',
            'application' => 'Zastosowanie',
            default => $field,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLICATION
    |--------------------------------------------------------------------------
    */

    /**
     * Check if product can be published
     * FAZA 9.1: Added publish_status check
     */
    public function canPublish(): bool
    {
        return $this->is_ready_for_publish
            && is_null($this->published_at)
            && in_array($this->publish_status ?? 'draft', ['draft', 'scheduled', 'failed']);
    }

    /**
     * Get validation errors for publication
     */
    public function getPublishValidationErrors(): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!$this->isFieldComplete($field)) {
                $errors[] = "Brak wymaganego pola: {$this->getFieldLabel($field)}";
            }
        }

        // Check SKU uniqueness in products table
        if ($this->sku && Product::where('sku', $this->sku)->exists()) {
            $errors[] = "SKU '{$this->sku}' juz istnieje w bazie produktow";
        }

        return $errors;
    }

    /**
     * Mark as published
     */
    public function markAsPublished(Product $product): void
    {
        $this->update([
            'published_at' => now(),
            'published_as_product_id' => $product->id,
        ]);
    }

    /**
     * Check if already published
     */
    public function isPublished(): bool
    {
        return !is_null($this->published_at);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get category count
     */
    public function getCategoryCountAttribute(): int
    {
        return count($this->category_ids ?? []);
    }

    /**
     * Get shop count
     */
    public function getShopCountAttribute(): int
    {
        return count($this->shop_ids ?? []);
    }

    /**
     * Get media count
     */
    public function getMediaCountAttribute(): int
    {
        return count($this->temp_media_paths ?? []);
    }

    /**
     * Get variant count
     */
    public function getVariantCountAttribute(): int
    {
        $data = $this->variant_data ?? [];
        return count($data['variants'] ?? []);
    }

    /**
     * Check if has variants
     */
    public function getHasVariantsAttribute(): bool
    {
        return $this->variant_count > 0;
    }

    /**
     * Get primary image path
     */
    public function getPrimaryImagePathAttribute(): ?string
    {
        $paths = $this->temp_media_paths ?? [];
        $index = $this->primary_media_index ?? 0;

        return $paths[$index] ?? ($paths[0] ?? null);
    }

    /**
     * Get completion color for UI
     */
    public function getCompletionColorAttribute(): string
    {
        $percentage = $this->completion_percentage;

        if ($percentage >= 100) return 'green';
        if ($percentage >= 80) return 'blue';
        if ($percentage >= 60) return 'yellow';
        if ($percentage >= 40) return 'orange';
        return 'red';
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isPublished()) {
            return 'Opublikowany';
        }

        if ($this->is_ready_for_publish) {
            return 'Gotowy';
        }

        return 'Niekompletny';
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get categories as collection
     */
    public function getCategories(): \Illuminate\Support\Collection
    {
        $ids = $this->category_ids ?? [];
        return Category::whereIn('id', $ids)->get();
    }

    /**
     * Get shops as collection
     */
    public function getShops(): \Illuminate\Support\Collection
    {
        $ids = $this->shop_ids ?? [];
        return PrestaShopShop::whereIn('id', $ids)->get();
    }

    /**
     * Update categories
     */
    public function setCategories(array $categoryIds): void
    {
        $this->update(['category_ids' => array_values(array_unique($categoryIds))]);
    }

    /**
     * Add category
     */
    public function addCategory(int $categoryId): void
    {
        $ids = $this->category_ids ?? [];
        if (!in_array($categoryId, $ids)) {
            $ids[] = $categoryId;
            $this->update(['category_ids' => $ids]);
        }
    }

    /**
     * Remove category
     */
    public function removeCategory(int $categoryId): void
    {
        $ids = $this->category_ids ?? [];
        $ids = array_values(array_filter($ids, fn($id) => $id !== $categoryId));
        $this->update(['category_ids' => $ids]);
    }

    /**
     * Update shops
     */
    public function setShops(array $shopIds): void
    {
        $this->update(['shop_ids' => array_values(array_unique($shopIds))]);
    }

    /**
     * Add shop
     */
    public function addShop(int $shopId): void
    {
        $ids = $this->shop_ids ?? [];
        if (!in_array($shopId, $ids)) {
            $ids[] = $shopId;
            $this->update(['shop_ids' => $ids]);
        }
    }

    /**
     * Remove shop
     */
    public function removeShop(int $shopId): void
    {
        $ids = $this->shop_ids ?? [];
        $ids = array_values(array_filter($ids, fn($id) => $id !== $shopId));
        $this->update(['shop_ids' => $ids]);
    }

    /**
     * Get normalized SKU (for matching)
     */
    public function getNormalizedSkuAttribute(): ?string
    {
        if (!$this->sku) {
            return null;
        }

        return strtoupper(trim(preg_replace('/[\s\-_]+/', '', $this->sku)));
    }

    /*
    |--------------------------------------------------------------------------
    | SKIP FLAGS (Brak X)
    |--------------------------------------------------------------------------
    */

    /**
     * Set skip flag with history tracking
     *
     * @param string $flag One of: skip_features, skip_compatibility, skip_images
     * @param bool $value
     * @param int|null $userId User who set the flag
     * @param string|null $userName User name for display
     */
    public function setSkipFlag(string $flag, bool $value, ?int $userId = null, ?string $userName = null): void
    {
        $validFlags = ['skip_features', 'skip_compatibility', 'skip_images'];

        if (!in_array($flag, $validFlags)) {
            return;
        }

        $history = $this->skip_history ?? [];

        if ($value) {
            // Setting flag - record history
            $history[$flag] = [
                'set_at' => now()->toIso8601String(),
                'set_by' => $userId ?? auth()->id(),
                'set_by_name' => $userName ?? auth()->user()?->name ?? 'System',
            ];
        } else {
            // Clearing flag - remove history entry
            $history[$flag] = null;
        }

        $this->update([
            $flag => $value,
            'skip_history' => $history,
        ]);

        // Recalculate completion after skip flag change
        $this->recalculateCompletion();
    }

    /**
     * Toggle skip features flag
     */
    public function toggleSkipFeatures(?int $userId = null, ?string $userName = null): void
    {
        $this->setSkipFlag('skip_features', !$this->skip_features, $userId, $userName);
    }

    /**
     * Toggle skip compatibility flag
     */
    public function toggleSkipCompatibility(?int $userId = null, ?string $userName = null): void
    {
        $this->setSkipFlag('skip_compatibility', !$this->skip_compatibility, $userId, $userName);
    }

    /**
     * Toggle skip images flag
     */
    public function toggleSkipImages(?int $userId = null, ?string $userName = null): void
    {
        $this->setSkipFlag('skip_images', !$this->skip_images, $userId, $userName);
    }

    /**
     * Get skip history for a specific flag
     */
    public function getSkipHistory(string $flag): ?array
    {
        return $this->skip_history[$flag] ?? null;
    }

    /**
     * Check if product type shows features (Pojazd)
     */
    public function showsFeatures(): bool
    {
        return $this->productType?->slug === 'pojazd';
    }

    /**
     * Check if product type shows compatibility (Czesc zamiennicza)
     */
    public function showsCompatibility(): bool
    {
        return $this->productType?->slug === 'czesc-zamienna';
    }
}
