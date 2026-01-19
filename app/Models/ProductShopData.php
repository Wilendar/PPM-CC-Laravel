<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Casts\CategoryMappingsCast;

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
        'tax_rate_override',  // FAZA 5.3: Per-shop tax rate override (NULL = use products.tax_rate)

        // Product status & variants
        'is_active',
        'is_variant_master',
        'sort_order',

        // Shop-specific mappings (JSON fields)
        'category_mappings',
        'attribute_mappings',
        'image_settings',

        // Synchronization control - CONSOLIDATED 2025-10-13
        'prestashop_product_id',     // PrestaShop external ID (migrated from external_id)
        'sync_status',
        'pending_fields',            // Field-Level Pending Tracking (2025-11-07) - JSON array of pending field names
        'sync_direction',
        'last_sync_at',
        'last_success_sync_at',
        'last_pulled_at',            // ETAP_13 (2025-11-06): PrestaShop → PPM pull timestamp
        'last_push_at',              // ETAP_13 (2025-11-17): PPM → PrestaShop push timestamp
        'prestashop_updated_at',     // 2026-01-19: Cached PrestaShop date_upd for change detection
        'last_sync_hash',
        'checksum',
        'error_message',             // Migrated from sync_errors (JSON → TEXT)
        'conflict_data',
        'conflict_detected_at',
        'retry_count',
        'max_retries',
        'priority',

        // Publishing control
        'is_published',
        'published_at',
        'unpublished_at',

        // External system reference
        'external_reference',

        // Validation fields (2025-11-13)
        'validation_warnings',
        'has_validation_warnings',
        'validation_checked_at',

        // Conflict resolution (PROBLEM #9.3 - 2025-11-13)
        'conflict_log',
        'has_conflicts',
        'conflicts_detected_at',
    ];

    protected $casts = [
        // JSON fields with custom casts
        'category_mappings' => CategoryMappingsCast::class,  // REFACTORED 2025-11-18: Option A architecture
        'attribute_mappings' => 'array',
        'image_settings' => 'array',
        'conflict_data' => 'array',
        'pending_fields' => 'array',  // Field-Level Pending Tracking (2025-11-07)
        'validation_warnings' => 'array',  // Validation warnings (2025-11-13)
        'conflict_log' => 'array',  // Conflict resolution log (PROBLEM #9.3 - 2025-11-13)

        // Datetime fields
        'last_sync_at' => 'datetime',
        'last_success_sync_at' => 'datetime',
        'last_pulled_at' => 'datetime',          // ETAP_13 (2025-11-06)
        'last_push_at' => 'datetime',            // ETAP_13 (2025-11-17)
        'prestashop_updated_at' => 'datetime',   // 2026-01-19: Change detection
        'conflict_detected_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'validation_checked_at' => 'datetime',  // Validation timestamp (2025-11-13)
        'conflicts_detected_at' => 'datetime',  // Conflict detection timestamp (PROBLEM #9.3 - 2025-11-13)

        // Boolean fields - ENHANCEMENT 2025-09-19
        'is_published' => 'boolean',
        'is_active' => 'boolean',
        'is_variant_master' => 'boolean',
        'has_validation_warnings' => 'boolean',  // Validation warnings flag (2025-11-13)
        'has_conflicts' => 'boolean',  // Conflict resolution flag (PROBLEM #9.3 - 2025-11-13)

        // Numeric fields with precision
        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_rate_override' => 'decimal:2',  // FAZA 5.3: Per-shop tax rate override
        'sort_order' => 'integer',
        'product_type_id' => 'integer',

        // Sync tracking fields - CONSOLIDATED 2025-10-13
        'prestashop_product_id' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'priority' => 'integer',
    ];

    protected $dates = [
        'last_sync_at',
        'last_success_sync_at',
        'last_pulled_at',          // ETAP_13 (2025-11-06)
        'last_push_at',            // ETAP_13 (2025-11-17)
        'prestashop_updated_at',   // 2026-01-19: Change detection
        'conflict_detected_at',
        'published_at',
        'unpublished_at',
        'validation_checked_at',
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
    const STATUS_SYNCING = 'syncing';      // W trakcie synchronizacji
    const STATUS_SYNCED = 'synced';        // Zsynchronizowane pomyślnie
    const STATUS_ERROR = 'error';          // Błąd synchronizacji
    const STATUS_CONFLICT = 'conflict';    // Konflikt danych (wymaga interwencji)
    const STATUS_DISABLED = 'disabled';    // Synchronizacja wyłączona

    // SYNC DIRECTION CONSTANTS - CONSOLIDATED 2025-10-13
    const DIRECTION_PPM_TO_PS = 'ppm_to_ps';           // PPM → PrestaShop
    const DIRECTION_PS_TO_PPM = 'ps_to_ppm';           // PrestaShop → PPM
    const DIRECTION_BIDIRECTIONAL = 'bidirectional';   // Dwukierunkowa

    // PRIORITY CONSTANTS - CONSOLIDATED 2025-10-13
    const PRIORITY_HIGHEST = 1;    // Najwyższy priorytet
    const PRIORITY_NORMAL = 5;     // Normalny priorytet (default)
    const PRIORITY_LOWEST = 10;    // Najniższy priorytet

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Oczekuje synchronizacji',
            self::STATUS_SYNCING => 'W trakcie synchronizacji',
            self::STATUS_SYNCED => 'Zsynchronizowane',
            self::STATUS_ERROR => 'Błąd synchronizacji',
            self::STATUS_CONFLICT => 'Konflikt danych',
            self::STATUS_DISABLED => 'Wyłączone',
        ];
    }

    public static function getAvailableDirections(): array
    {
        return [
            self::DIRECTION_PPM_TO_PS => 'PPM → PrestaShop',
            self::DIRECTION_PS_TO_PPM => 'PrestaShop → PPM',
            self::DIRECTION_BIDIRECTIONAL => 'Dwukierunkowa',
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

    // ADDITIONAL SCOPES - CONSOLIDATED 2025-10-13

    /**
     * Scope to filter pending products
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter syncing products
     */
    public function scopeSyncing($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCING);
    }

    /**
     * Scope to filter synced products
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCED);
    }

    /**
     * Scope to filter products with errors
     */
    public function scopeError($query)
    {
        return $query->where('sync_status', self::STATUS_ERROR);
    }

    /**
     * Scope to filter products with conflicts
     */
    public function scopeConflict($query)
    {
        return $query->where('sync_status', self::STATUS_CONFLICT);
    }

    /**
     * Scope to filter disabled products
     */
    public function scopeDisabled($query)
    {
        return $query->where('sync_status', self::STATUS_DISABLED);
    }

    /**
     * Scope to filter by sync direction
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('sync_direction', $direction);
    }

    /**
     * Scope to filter by priority
     */
    public function scopeByPriority($query, int $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter high priority products (1-3)
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '<=', 3);
    }

    /**
     * Scope to filter products that need retry
     */
    public function scopeNeedsRetry($query)
    {
        return $query->where('sync_status', self::STATUS_ERROR)
                    ->whereColumn('retry_count', '<', 'max_retries');
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

    // ADDITIONAL HELPERS - CONSOLIDATED 2025-10-13

    /**
     * Check if product is pending sync
     */
    public function isPending(): bool
    {
        return $this->sync_status === self::STATUS_PENDING;
    }

    /**
     * Check if product is currently syncing
     */
    public function isSyncing(): bool
    {
        return $this->sync_status === self::STATUS_SYNCING;
    }

    /**
     * Check if sync is disabled
     */
    public function isDisabled(): bool
    {
        return $this->sync_status === self::STATUS_DISABLED;
    }

    /**
     * Check if product can retry sync
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    /**
     * Check if max retries exceeded
     */
    public function maxRetriesExceeded(): bool
    {
        return $this->retry_count >= $this->max_retries;
    }

    // ==========================================
    // SYNC MANAGEMENT METHODS
    // ==========================================

    /**
     * Mark as successfully synced
     * UPDATED 2025-10-13: Added last_success_sync_at, checksum, prestashop_product_id, retry_count reset
     */
    public function markAsSynced(?int $prestashopProductId = null, ?string $checksum = null): self
    {
        $now = Carbon::now();
        $hash = $checksum ?? $this->generateDataHash();

        $updateData = [
            'sync_status' => self::STATUS_SYNCED,
            'last_sync_at' => $now,
            'last_success_sync_at' => $now,
            'last_sync_hash' => $hash,
            'checksum' => $hash,
            'error_message' => null,
            'conflict_data' => null,
            'conflict_detected_at' => null,
            'retry_count' => 0,
            'pending_fields' => null, // FEATURE (2025-11-07): Clear field-level tracking after successful sync
        ];

        if ($prestashopProductId !== null) {
            $updateData['prestashop_product_id'] = $prestashopProductId;
        }

        $this->update($updateData);

        Log::info('ProductShopData marked as synced', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'prestashop_product_id' => $this->prestashop_product_id,
            'sync_hash' => $this->last_sync_hash
        ]);

        return $this;
    }

    /**
     * Mark as sync error
     * UPDATED 2025-10-13: Changed sync_errors (JSON) to error_message (TEXT), added retry_count increment
     */
    public function markAsError(string $errorMessage): self
    {
        $this->update([
            'sync_status' => self::STATUS_ERROR,
            'error_message' => $errorMessage,
            'last_sync_at' => Carbon::now(),
            'retry_count' => $this->retry_count + 1,
        ]);

        Log::warning('ProductShopData marked as error', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count
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

    // ADDITIONAL SYNC MANAGEMENT - CONSOLIDATED 2025-10-13

    /**
     * Mark as currently syncing
     */
    public function markSyncing(): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_SYNCING,
            'last_sync_at' => Carbon::now(),
        ]);
    }

    /**
     * Reset retry count (after successful sync or manual reset)
     */
    public function resetRetryCount(): bool
    {
        return $this->update([
            'retry_count' => 0,
        ]);
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
     * ENHANCEMENT 2025-11-14: Include tax_rate_override (FAZA 5.3)
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
            'tax_rate_override' => $this->tax_rate_override,  // FAZA 5.3: Include override in checksum

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
     * UPDATED 2025-10-13: Added STATUS_SYNCING icon
     */
    public function getSyncStatusWithIcon(): string
    {
        $icons = [
            self::STATUS_PENDING => '🔄',
            self::STATUS_SYNCING => '⏳',
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

    // ==========================================
    // DATE_UPD OPTIMIZATION HELPERS (2026-01-19)
    // ==========================================

    /**
     * Check if product needs re-pull based on PrestaShop date_upd
     *
     * OPTIMIZATION: Skip full data fetch if product unchanged in PrestaShop
     *
     * Compares cached prestashop_updated_at with fresh date_upd from API.
     * Returns true if:
     * - Never pulled before (no prestashop_updated_at or last_pulled_at)
     * - PrestaShop date_upd is newer than cached timestamp
     *
     * @param string|int $psDateUpd Fresh date_upd from PrestaShop (timestamp or datetime string)
     * @return bool True if product needs full re-pull, false if can be skipped
     */
    public function needsRePull($psDateUpd): bool
    {
        // Never pulled - always needs pull
        if (!$this->prestashop_updated_at || !$this->last_pulled_at) {
            return true;
        }

        // Convert PS date_upd to timestamp for comparison
        $psTimestamp = is_numeric($psDateUpd) ? (int)$psDateUpd : strtotime($psDateUpd);

        // If PS timestamp is newer than our cached timestamp - needs re-pull
        return $psTimestamp > $this->prestashop_updated_at->timestamp;
    }

    /**
     * Get time since last PrestaShop update
     *
     * @return string Human-readable timestamp or "Unknown"
     */
    public function getTimeSincePrestaShopUpdate(): string
    {
        if (!$this->prestashop_updated_at) {
            return 'Unknown';
        }

        return $this->prestashop_updated_at->diffForHumans();
    }

    // ==========================================
    // ETAP_13 HELPER METHODS (2025-11-17)
    // ==========================================

    /**
     * Get time since last pull (PrestaShop → PPM)
     *
     * ETAP_13: Sync Panel UX Refactoring
     *
     * @return string Human-readable timestamp or "Nigdy"
     */
    public function getTimeSinceLastPull(): string
    {
        if (!$this->last_pulled_at) {
            return 'Nigdy';
        }

        return $this->last_pulled_at->diffForHumans();
    }

    /**
     * Get time since last push (PPM → PrestaShop)
     *
     * ETAP_13: Sync Panel UX Refactoring
     *
     * @return string Human-readable timestamp or "Nigdy"
     */
    public function getTimeSinceLastPush(): string
    {
        if (!$this->last_push_at) {
            return 'Nigdy';
        }

        return $this->last_push_at->diffForHumans();
    }

    // ==========================================
    // TAX RATE HELPERS (FAZA 5.3 - 2025-11-14)
    // ==========================================

    /**
     * Get effective tax rate (override or default)
     *
     * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
     *
     * Priority:
     * 1. tax_rate_override (shop-specific override)
     * 2. product->tax_rate (global product default)
     * 3. 23.00 (Poland standard VAT as fallback)
     *
     * @return float Effective tax rate for this shop
     */
    public function getEffectiveTaxRate(): float
    {
        // Priority: Override → Product Default → Fallback
        return $this->tax_rate_override ?? $this->product->tax_rate ?? 23.00;
    }

    /**
     * Check if shop has tax rate override
     *
     * @return bool True if override is set
     */
    public function hasTaxRateOverride(): bool
    {
        return $this->tax_rate_override !== null;
    }

    /**
     * Get tax rate source description (for UI display)
     *
     * @return string Source description
     */
    public function getTaxRateSource(): string
    {
        if ($this->tax_rate_override !== null) {
            return 'Nadpisany dla sklepu (' . $this->tax_rate_override . '%)';
        }

        if ($this->product && $this->product->tax_rate !== null) {
            return 'Domyślny PPM (' . $this->product->tax_rate . '%)';
        }

        return 'Fallback (23.00%)';
    }

    /**
     * Get tax rate source type (for programmatic use)
     *
     * @return string 'shop_override' | 'product_default' | 'system_fallback'
     */
    public function getTaxRateSourceType(): string
    {
        if ($this->tax_rate_override !== null) {
            return 'shop_override';
        }

        if ($this->product && $this->product->tax_rate !== null) {
            return 'product_default';
        }

        return 'system_fallback';
    }

    /**
     * Check if tax rate matches PrestaShop tax rule group mapping
     *
     * Validation helper - checks if current effective rate matches
     * any of the mapped tax rule groups for this shop.
     *
     * @return bool True if rate is mapped in shop settings
     */
    public function taxRateMatchesPrestaShopMapping(): bool
    {
        $effectiveRate = $this->getEffectiveTaxRate();
        $shop = $this->shop;

        if (!$shop) {
            return false;
        }

        // Check against all mapped tax rule groups (23, 8, 5, 0)
        $mappedRates = [
            $shop->tax_rules_group_id_23 ? 23.00 : null,
            $shop->tax_rules_group_id_8 ? 8.00 : null,
            $shop->tax_rules_group_id_5 ? 5.00 : null,
            $shop->tax_rules_group_id_0 ? 0.00 : null,
        ];

        // Filter out null values (unmapped rates)
        $mappedRates = array_filter($mappedRates, fn($rate) => $rate !== null);

        // Float comparison with precision tolerance
        return in_array(
            round($effectiveRate, 2),
            array_map(fn($rate) => round($rate, 2), $mappedRates),
            true
        );
    }

    /**
     * Get validation warning for tax rate mismatch
     *
     * @return string|null Warning message or null if valid
     */
    public function getTaxRateValidationWarning(): ?string
    {
        if (!$this->taxRateMatchesPrestaShopMapping()) {
            $effectiveRate = $this->getEffectiveTaxRate();
            $shop = $this->shop;

            if (!$shop) {
                return 'Sklep nie istnieje - nie można zweryfikować stawki VAT';
            }

            // Get available rates for this shop
            $availableRates = [];
            if ($shop->tax_rules_group_id_23) $availableRates[] = '23%';
            if ($shop->tax_rules_group_id_8) $availableRates[] = '8%';
            if ($shop->tax_rules_group_id_5) $availableRates[] = '5%';
            if ($shop->tax_rules_group_id_0) $availableRates[] = '0%';

            $ratesString = !empty($availableRates)
                ? implode(', ', $availableRates)
                : 'brak zmapowanych stawek';

            return "Stawka VAT {$effectiveRate}% nie jest zmapowana w ustawieniach sklepu PrestaShop. Dostępne stawki: {$ratesString}";
        }

        return null;
    }

    // ==========================================
    // CATEGORY MAPPINGS HELPERS (ARCHITECTURE REFACTORING 2025-11-18)
    // ==========================================

    /**
     * Get category_mappings UI section
     *
     * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0
     *
     * Returns UI-specific data for Livewire components:
     * - selected: Array of PPM category IDs
     * - primary: Primary category ID (or null)
     *
     * @return array UI section (selected + primary)
     */
    public function getCategoryMappingsUi(): array
    {
        $mappings = $this->category_mappings ?? [];

        return [
            'selected' => $mappings['ui']['selected'] ?? [],
            'primary' => $mappings['ui']['primary'] ?? null,
        ];
    }

    /**
     * Get PrestaShop category IDs list (for sync operations)
     *
     * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0
     *
     * Returns array of PrestaShop category IDs from mappings section.
     * Filters out placeholder values (0 = not mapped yet).
     *
     * @return array Array of PrestaShop category IDs
     */
    public function getCategoryMappingsList(): array
    {
        $mappings = $this->category_mappings ?? [];
        $prestashopIds = array_values($mappings['mappings'] ?? []);

        // Filter out placeholders (0 = not mapped)
        return array_values(array_filter($prestashopIds, fn($id) => $id > 0));
    }

    /**
     * Check if product has valid category mappings
     *
     * Valid = at least one mapping with PrestaShop ID > 0
     *
     * @return bool True if has valid mappings
     */
    public function hasCategoryMappings(): bool
    {
        $list = $this->getCategoryMappingsList();
        return !empty($list);
    }

    /**
     * Get primary PrestaShop category ID
     *
     * Resolves primary category from UI to PrestaShop ID via mappings
     *
     * @return int|null Primary PrestaShop category ID or null
     */
    public function getPrimaryCategoryId(): ?int
    {
        $mappings = $this->category_mappings ?? [];
        $primaryPpmId = $mappings['ui']['primary'] ?? null;

        if ($primaryPpmId === null) {
            return null;
        }

        // Lookup in mappings
        $prestashopId = $mappings['mappings'][(string) $primaryPpmId] ?? null;

        // Filter out placeholder (0 = not mapped)
        if ($prestashopId === 0 || $prestashopId === null) {
            return null;
        }

        return $prestashopId;
    }

    /**
     * Get count of unmapped categories
     *
     * Counts categories in UI.selected that don't have valid PrestaShop mapping
     *
     * @return int Count of unmapped categories
     */
    public function getUnmappedCategoriesCount(): int
    {
        $mappings = $this->category_mappings ?? [];
        $selected = $mappings['ui']['selected'] ?? [];
        $prestashopMappings = $mappings['mappings'] ?? [];

        $unmappedCount = 0;

        foreach ($selected as $ppmId) {
            $prestashopId = $prestashopMappings[(string) $ppmId] ?? 0;

            if ($prestashopId === 0) {
                $unmappedCount++;
            }
        }

        return $unmappedCount;
    }

    /**
     * Get category mappings source
     *
     * Returns metadata.source indicating how mappings were created
     *
     * @return string Source identifier ('manual', 'pull', 'sync', 'migration', 'empty')
     */
    public function getCategoryMappingsSource(): string
    {
        $mappings = $this->category_mappings ?? [];
        return $mappings['metadata']['source'] ?? 'unknown';
    }

    /**
     * Get category mappings last updated timestamp
     *
     * @return \Carbon\Carbon|null Last updated timestamp or null
     */
    public function getCategoryMappingsLastUpdated(): ?\Carbon\Carbon
    {
        $mappings = $this->category_mappings ?? [];
        $timestamp = $mappings['metadata']['last_updated'] ?? null;

        if ($timestamp === null) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}