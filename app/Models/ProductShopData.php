<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProductShopData Model
 *
 * FAZA 1.5: Multi-Store Synchronization System
 *
 * Model przechowuje dane produktów specyficzne dla każdego sklepu PrestaShop.
 * Każdy produkt może mieć różne dane per sklep (nazwa, opisy, kategorie, zdjęcia)
 * podczas gdy wspólne dane biznesowe (SKU, ceny, stany) pozostają w tabeli products.
 *
 * Features:
 * - Per-shop product data customization
 * - Sync status tracking per shop
 * - Conflict detection and resolution
 * - Publishing control per shop
 * - Performance optimized dla 100K+ products x shops
 *
 * @package App\Models
 * @version 1.0
 * @since FAZA 1.5 - Multi-Store System
 */
class ProductShopData extends Model
{
    use HasFactory;

    protected $table = 'product_shop_data';

    protected $fillable = [
        // Basic identification
        'product_id',
        'shop_id',

        // All product fields (can override defaults) - ENHANCEMENT 2025-09-19
        'sku',
        'name',
        'slug',
        'short_description',
        'long_description',
        'meta_title',
        'meta_description',

        // Product classification
        'product_type_id',
        'manufacturer',
        'supplier_code',

        // Physical properties
        'weight',
        'height',
        'width',
        'length',
        'ean',
        'tax_rate',

        // Product status & variants
        'is_active',
        'is_variant_master',
        'sort_order',

        // Shop-specific mappings (JSON fields)
        'category_mappings',
        'attribute_mappings',
        'image_settings',

        // Synchronization control
        'sync_status',
        'last_sync_at',
        'last_sync_hash',
        'sync_errors',
        'conflict_data',
        'conflict_detected_at',

        // Publishing control
        'is_published',
        'published_at',
        'unpublished_at',

        // External system reference
        'external_id',
        'external_reference',
    ];

    protected $casts = [
        // JSON fields
        'category_mappings' => 'array',
        'attribute_mappings' => 'array',
        'image_settings' => 'array',
        'sync_errors' => 'array',
        'conflict_data' => 'array',

        // Datetime fields
        'last_sync_at' => 'datetime',
        'conflict_detected_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',

        // Boolean fields - ENHANCEMENT 2025-09-19
        'is_published' => 'boolean',
        'is_active' => 'boolean',
        'is_variant_master' => 'boolean',

        // Numeric fields with precision
        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'sort_order' => 'integer',
        'product_type_id' => 'integer',
    ];

    protected $dates = [
        'last_sync_at',
        'conflict_detected_at',
        'published_at',
        'unpublished_at',
        'created_at',
        'updated_at',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the product that owns this shop data
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the PrestaShop shop associated with this data
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Get the product type associated with this shop data
     * ENHANCEMENT 2025-09-19: Support for shop-specific product types
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    // ==========================================
    // SYNC STATUS CONSTANTS
    // ==========================================

    const STATUS_PENDING = 'pending';      // Oczekuje na synchronizację
    const STATUS_SYNCED = 'synced';        // Zsynchronizowane pomyślnie
    const STATUS_ERROR = 'error';          // Błąd synchronizacji
    const STATUS_CONFLICT = 'conflict';    // Konflikt danych (wymaga interwencji)
    const STATUS_DISABLED = 'disabled';    // Synchronizacja wyłączona

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Oczekuje synchronizacji',
            self::STATUS_SYNCED => 'Zsynchronizowane',
            self::STATUS_ERROR => 'Błąd synchronizacji',
            self::STATUS_CONFLICT => 'Konflikt danych',
            self::STATUS_DISABLED => 'Wyłączone',
        ];
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to filter by sync status
     */
    public function scopeWithSyncStatus($query, string $status)
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope to filter published products
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to filter unpublished products
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope to filter products needing sync
     */
    public function scopeNeedingSync($query)
    {
        return $query->whereIn('sync_status', [
            self::STATUS_PENDING,
            self::STATUS_ERROR,
            self::STATUS_CONFLICT
        ]);
    }

    /**
     * Scope to filter by shop
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope to filter products with conflicts
     */
    public function scopeWithConflicts($query)
    {
        return $query->where('sync_status', self::STATUS_CONFLICT)
                    ->whereNotNull('conflict_detected_at');
    }

    // ==========================================
    // SYNC STATUS HELPERS
    // ==========================================

    /**
     * Check if product is synced with shop
     */
    public function isSynced(): bool
    {
        return $this->sync_status === self::STATUS_SYNCED;
    }

    /**
     * Check if product has sync error
     */
    public function hasError(): bool
    {
        return $this->sync_status === self::STATUS_ERROR;
    }

    /**
     * Check if product has conflicts
     */
    public function hasConflict(): bool
    {
        return $this->sync_status === self::STATUS_CONFLICT;
    }

    /**
     * Check if sync is disabled
     */
    public function isSyncDisabled(): bool
    {
        return $this->sync_status === self::STATUS_DISABLED;
    }

    /**
     * Check if product needs synchronization
     */
    public function needsSync(): bool
    {
        return in_array($this->sync_status, [
            self::STATUS_PENDING,
            self::STATUS_ERROR,
            self::STATUS_CONFLICT
        ]);
    }

    // ==========================================
    // SYNC MANAGEMENT METHODS
    // ==========================================

    /**
     * Mark as successfully synced
     */
    public function markAsSynced(string $hash = null): self
    {
        $this->update([
            'sync_status' => self::STATUS_SYNCED,
            'last_sync_at' => Carbon::now(),
            'last_sync_hash' => $hash ?? $this->generateDataHash(),
            'sync_errors' => null,
            'conflict_data' => null,
            'conflict_detected_at' => null,
        ]);

        Log::info('ProductShopData marked as synced', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'sync_hash' => $this->last_sync_hash
        ]);

        return $this;
    }

    /**
     * Mark as sync error
     */
    public function markAsError(array $errors): self
    {
        $this->update([
            'sync_status' => self::STATUS_ERROR,
            'sync_errors' => $errors,
            'last_sync_at' => Carbon::now(),
        ]);

        Log::warning('ProductShopData marked as error', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'errors' => $errors
        ]);

        return $this;
    }

    /**
     * Mark as conflict detected
     */
    public function markAsConflict(array $conflictData): self
    {
        $this->update([
            'sync_status' => self::STATUS_CONFLICT,
            'conflict_data' => $conflictData,
            'conflict_detected_at' => Carbon::now(),
        ]);

        Log::warning('ProductShopData conflict detected', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'conflict_data' => $conflictData
        ]);

        return $this;
    }

    /**
     * Mark as pending sync
     */
    public function markAsPending(): self
    {
        $this->update([
            'sync_status' => self::STATUS_PENDING,
            'sync_errors' => null,
            'conflict_data' => null,
            'conflict_detected_at' => null,
        ]);

        return $this;
    }

    /**
     * Disable sync for this shop
     */
    public function disableSync(): self
    {
        $this->update([
            'sync_status' => self::STATUS_DISABLED,
        ]);

        return $this;
    }

    // ==========================================
    // PUBLISHING CONTROL
    // ==========================================

    /**
     * Publish product to shop
     */
    public function publish(): self
    {
        $this->update([
            'is_published' => true,
            'published_at' => Carbon::now(),
            'unpublished_at' => null,
        ]);

        // Mark for sync if not disabled
        if (!$this->isSyncDisabled()) {
            $this->markAsPending();
        }

        return $this;
    }

    /**
     * Unpublish product from shop
     */
    public function unpublish(): self
    {
        $this->update([
            'is_published' => false,
            'unpublished_at' => Carbon::now(),
        ]);

        return $this;
    }

    // ==========================================
    // DATA MANAGEMENT
    // ==========================================

    /**
     * Generate hash of current data for change detection
     * ENHANCEMENT 2025-09-19: Include all product fields
     */
    public function generateDataHash(): string
    {
        $data = [
            // Basic product data
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // Product classification
            'product_type_id' => $this->product_type_id,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,

            // Physical properties
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'ean' => $this->ean,
            'tax_rate' => $this->tax_rate,

            // Status and variants
            'is_active' => $this->is_active,
            'is_variant_master' => $this->is_variant_master,
            'sort_order' => $this->sort_order,

            // Shop-specific mappings
            'category_mappings' => $this->category_mappings,
            'attribute_mappings' => $this->attribute_mappings,
            'image_settings' => $this->image_settings,
            'is_published' => $this->is_published,
        ];

        return md5(json_encode($data));
    }

    /**
     * Check if data has changed since last sync
     */
    public function hasDataChanged(): bool
    {
        if (!$this->last_sync_hash) {
            return true;
        }

        return $this->generateDataHash() !== $this->last_sync_hash;
    }

    /**
     * Get display name (fallback to product name)
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->product->name ?? 'Unnamed Product';
    }

    /**
     * Get sync status with icon for UI
     */
    public function getSyncStatusWithIcon(): string
    {
        $icons = [
            self::STATUS_PENDING => '🔄',
            self::STATUS_SYNCED => '🟢',
            self::STATUS_ERROR => '🔴',
            self::STATUS_CONFLICT => '⚠️',
            self::STATUS_DISABLED => '⏸️',
        ];

        $labels = self::getAvailableStatuses();
        $icon = $icons[$this->sync_status] ?? '❓';
        $label = $labels[$this->sync_status] ?? 'Nieznany';

        return $icon . ' ' . $label;
    }

    // ==========================================
    // VALIDATION HELPERS
    // ==========================================

    /**
     * Validate shop-specific data before sync
     */
    public function validateForSync(): array
    {
        $errors = [];

        // Check if shop exists
        if (!$this->shop) {
            $errors[] = 'Sklep nie istnieje';
        }

        // Check if product exists
        if (!$this->product) {
            $errors[] = 'Produkt nie istnieje';
        }

        // Validate required fields if published
        if ($this->is_published) {
            if (empty($this->getDisplayName())) {
                $errors[] = 'Nazwa produktu jest wymagana dla opublikowanych produktów';
            }
        }

        return $errors;
    }

    /**
     * Get time since last sync in human readable format
     */
    public function getTimeSinceLastSync(): string
    {
        if (!$this->last_sync_at) {
            return 'Nigdy';
        }

        return $this->last_sync_at->diffForHumans();
    }
}