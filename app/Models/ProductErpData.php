<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProductErpData Model
 *
 * ETAP_08.3: ERP Tab in ProductForm (Shop-Tab Pattern)
 *
 * Model przechowuje dane produktow specyficzne dla kazdego systemu ERP.
 * Kazdy produkt moze miec rozne dane per ERP connection (nazwa, opisy, mapowania)
 * podczas gdy wspolne dane biznesowe (SKU, ceny, stany) pozostaja w tabeli products.
 *
 * Analogiczna struktura do ProductShopData dla spojnosci UX.
 *
 * Features:
 * - Per-ERP product data customization
 * - Sync status tracking per ERP connection
 * - Conflict detection and resolution
 * - Bidirectional sync support (PPM <-> ERP)
 * - External data caching for comparison
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_08.3 - ERP Tab Implementation (Shop-Tab Pattern)
 */
class ProductErpData extends Model
{
    use HasFactory;

    protected $table = 'product_erp_data';

    protected $fillable = [
        // Basic identification
        'product_id',
        'erp_connection_id',

        // All product fields (can override defaults)
        'sku',
        'name',
        'ean',
        'manufacturer',
        'supplier_code',
        'short_description',
        'long_description',
        'meta_title',
        'meta_description',

        // Physical properties
        'weight',
        'height',
        'width',
        'length',
        'tax_rate',

        // Product status
        'is_active',

        // ERP-specific mappings (JSON fields)
        'category_mappings',
        'attribute_mappings',
        'price_mappings',
        'warehouse_mappings',
        'variant_mappings',
        'image_mappings',

        // Synchronization control
        'external_id',
        'sync_status',
        'pending_fields',
        'sync_direction',

        // Conflict tracking
        'conflict_data',
        'has_conflicts',
        'conflicts_detected_at',

        // Synchronization timestamps
        'last_sync_at',
        'last_push_at',
        'last_pull_at',
        'last_sync_hash',

        // Error handling
        'error_message',
        'retry_count',
        'max_retries',

        // External data cache
        'external_data',
    ];

    protected $casts = [
        // JSON fields
        'category_mappings' => 'array',
        'attribute_mappings' => 'array',
        'price_mappings' => 'array',
        'warehouse_mappings' => 'array',
        'variant_mappings' => 'array',
        'image_mappings' => 'array',
        'pending_fields' => 'array',
        'conflict_data' => 'array',
        'external_data' => 'array',

        // Datetime fields
        'last_sync_at' => 'datetime',
        'last_push_at' => 'datetime',
        'last_pull_at' => 'datetime',
        'conflicts_detected_at' => 'datetime',

        // Boolean fields
        'is_active' => 'boolean',
        'has_conflicts' => 'boolean',

        // Numeric fields
        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    // ==========================================
    // SYNC STATUS CONSTANTS
    // ==========================================

    const STATUS_PENDING = 'pending';      // Oczekuje na synchronizacje
    const STATUS_SYNCING = 'syncing';      // W trakcie synchronizacji
    const STATUS_SYNCED = 'synced';        // Zsynchronizowane pomyslnie
    const STATUS_ERROR = 'error';          // Blad synchronizacji
    const STATUS_CONFLICT = 'conflict';    // Konflikt danych (wymaga interwencji)
    const STATUS_DISABLED = 'disabled';    // Synchronizacja wylaczona

    // SYNC DIRECTION CONSTANTS
    const DIRECTION_PPM_TO_ERP = 'ppm_to_erp';           // PPM -> ERP
    const DIRECTION_ERP_TO_PPM = 'erp_to_ppm';           // ERP -> PPM
    const DIRECTION_BIDIRECTIONAL = 'bidirectional';    // Dwukierunkowa

    /**
     * Get available sync statuses for UI
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Oczekuje synchronizacji',
            self::STATUS_SYNCING => 'W trakcie synchronizacji',
            self::STATUS_SYNCED => 'Zsynchronizowane',
            self::STATUS_ERROR => 'Blad synchronizacji',
            self::STATUS_CONFLICT => 'Konflikt danych',
            self::STATUS_DISABLED => 'Wylaczone',
        ];
    }

    /**
     * Get available sync directions for UI
     */
    public static function getAvailableDirections(): array
    {
        return [
            self::DIRECTION_PPM_TO_ERP => 'PPM -> ERP',
            self::DIRECTION_ERP_TO_PPM => 'ERP -> PPM',
            self::DIRECTION_BIDIRECTIONAL => 'Dwukierunkowa',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the product that owns this ERP data
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the ERP connection associated with this data
     */
    public function erpConnection(): BelongsTo
    {
        return $this->belongsTo(ERPConnection::class, 'erp_connection_id');
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
     * Scope to filter by ERP connection
     */
    public function scopeForErpConnection($query, int $erpConnectionId)
    {
        return $query->where('erp_connection_id', $erpConnectionId);
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
     * Scope to filter products with conflicts
     */
    public function scopeWithConflicts($query)
    {
        return $query->where('has_conflicts', true)
                    ->orWhere('sync_status', self::STATUS_CONFLICT);
    }

    /**
     * Scope to filter pending products
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::STATUS_PENDING);
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
     * Scope to filter by sync direction
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('sync_direction', $direction);
    }

    // ==========================================
    // SYNC STATUS HELPERS
    // ==========================================

    /**
     * Check if product is synced with ERP
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
        return $this->sync_status === self::STATUS_CONFLICT || $this->has_conflicts;
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
     * Check if can retry sync
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries;
    }

    // ==========================================
    // SYNC MANAGEMENT METHODS
    // ==========================================

    /**
     * Mark as successfully synced
     *
     * @param string|null $externalId External ID from ERP (e.g., Baselinker product ID)
     * @param string|null $checksum Data checksum
     * @return self
     */
    public function markAsSynced(?string $externalId = null, ?string $checksum = null): self
    {
        $now = Carbon::now();
        $hash = $checksum ?? $this->generateDataHash();

        $updateData = [
            'sync_status' => self::STATUS_SYNCED,
            'last_sync_at' => $now,
            'last_push_at' => $now, // ETAP_08: Track PPM -> ERP push timestamp
            'last_sync_hash' => $hash,
            'error_message' => null,
            'conflict_data' => null,
            'has_conflicts' => false,
            'conflicts_detected_at' => null,
            'retry_count' => 0,
            'pending_fields' => null, // Clear field-level tracking after successful sync
        ];

        if ($externalId !== null) {
            $updateData['external_id'] = $externalId;
        }

        $this->update($updateData);

        Log::info('ProductErpData marked as synced', [
            'product_id' => $this->product_id,
            'erp_connection_id' => $this->erp_connection_id,
            'external_id' => $this->external_id,
            'sync_hash' => $this->last_sync_hash,
        ]);

        return $this;
    }

    /**
     * Mark as sync error
     *
     * @param string $errorMessage Error message
     * @return self
     */
    public function markAsError(string $errorMessage): self
    {
        $this->update([
            'sync_status' => self::STATUS_ERROR,
            'error_message' => $errorMessage,
            'last_sync_at' => Carbon::now(),
            'retry_count' => $this->retry_count + 1,
        ]);

        Log::warning('ProductErpData marked as error', [
            'product_id' => $this->product_id,
            'erp_connection_id' => $this->erp_connection_id,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count,
        ]);

        return $this;
    }

    /**
     * Mark as conflict detected
     *
     * @param array $conflictData Conflict details
     * @return self
     */
    public function markAsConflict(array $conflictData): self
    {
        $this->update([
            'sync_status' => self::STATUS_CONFLICT,
            'conflict_data' => $conflictData,
            'has_conflicts' => true,
            'conflicts_detected_at' => Carbon::now(),
        ]);

        Log::warning('ProductErpData conflict detected', [
            'product_id' => $this->product_id,
            'erp_connection_id' => $this->erp_connection_id,
            'conflict_data' => $conflictData,
        ]);

        return $this;
    }

    /**
     * Mark as pending sync
     *
     * @param array|null $pendingFields Specific fields pending sync
     * @return self
     */
    public function markAsPending(?array $pendingFields = null): self
    {
        $updateData = [
            'sync_status' => self::STATUS_PENDING,
            'error_message' => null,
        ];

        if ($pendingFields !== null) {
            // Merge with existing pending fields
            $existing = $this->pending_fields ?? [];
            $merged = array_unique(array_merge($existing, $pendingFields));
            $updateData['pending_fields'] = array_values($merged);
        }

        $this->update($updateData);

        return $this;
    }

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
     * Mark last push (PPM -> ERP)
     */
    public function markPushed(): self
    {
        $this->update([
            'last_push_at' => Carbon::now(),
            'last_sync_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Mark last pull (ERP -> PPM)
     */
    public function markPulled(): self
    {
        $this->update([
            'last_pull_at' => Carbon::now(),
            'last_sync_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Disable sync for this ERP
     */
    public function disableSync(): self
    {
        $this->update([
            'sync_status' => self::STATUS_DISABLED,
        ]);

        return $this;
    }

    /**
     * Reset retry count
     */
    public function resetRetryCount(): bool
    {
        return $this->update([
            'retry_count' => 0,
        ]);
    }

    // ==========================================
    // DATA MANAGEMENT
    // ==========================================

    /**
     * Generate hash of current data for change detection
     */
    public function generateDataHash(): string
    {
        $data = [
            'sku' => $this->sku,
            'name' => $this->name,
            'ean' => $this->ean,
            'manufacturer' => $this->manufacturer,
            'supplier_code' => $this->supplier_code,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'weight' => $this->weight,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
            'category_mappings' => $this->category_mappings,
            'attribute_mappings' => $this->attribute_mappings,
            'price_mappings' => $this->price_mappings,
            'warehouse_mappings' => $this->warehouse_mappings,
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
            self::STATUS_SYNCING => '⏳',
            self::STATUS_SYNCED => '✓',
            self::STATUS_ERROR => '✗',
            self::STATUS_CONFLICT => '⚠',
            self::STATUS_DISABLED => '⏸',
        ];

        $labels = self::getAvailableStatuses();
        $icon = $icons[$this->sync_status] ?? '?';
        $label = $labels[$this->sync_status] ?? 'Nieznany';

        return $icon . ' ' . $label;
    }

    // ==========================================
    // TIME HELPERS
    // ==========================================

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

    /**
     * Get time since last pull (ERP -> PPM)
     */
    public function getTimeSinceLastPull(): string
    {
        if (!$this->last_pull_at) {
            return 'Nigdy';
        }

        return $this->last_pull_at->diffForHumans();
    }

    /**
     * Get time since last push (PPM -> ERP)
     */
    public function getTimeSinceLastPush(): string
    {
        if (!$this->last_push_at) {
            return 'Nigdy';
        }

        return $this->last_push_at->diffForHumans();
    }

    // ==========================================
    // VALIDATION HELPERS
    // ==========================================

    /**
     * Validate ERP-specific data before sync
     */
    public function validateForSync(): array
    {
        $errors = [];

        // Check if ERP connection exists
        if (!$this->erpConnection) {
            $errors[] = 'Polaczenie ERP nie istnieje';
        }

        // Check if product exists
        if (!$this->product) {
            $errors[] = 'Produkt nie istnieje';
        }

        // Check required fields based on ERP type
        if ($this->erpConnection) {
            switch ($this->erpConnection->erp_type) {
                case ERPConnection::ERP_BASELINKER:
                    // Baselinker requires SKU or name
                    if (empty($this->getDisplayName()) && empty($this->sku)) {
                        $errors[] = 'Baselinker wymaga nazwy lub SKU produktu';
                    }
                    break;
            }
        }

        return $errors;
    }

    // ==========================================
    // EXTERNAL DATA HELPERS
    // ==========================================

    /**
     * Get value from external_data cache
     *
     * @param string $field Field name (supports dot notation like 'text_fields.name')
     * @return mixed
     */
    public function getExternalValue(string $field): mixed
    {
        $data = $this->external_data ?? [];

        // Support dot notation
        $keys = explode('.', $field);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Update external data cache
     *
     * @param array $data Data from ERP
     * @return self
     */
    public function updateExternalData(array $data): self
    {
        $this->update([
            'external_data' => $data,
        ]);

        return $this;
    }
}
