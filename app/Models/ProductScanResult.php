<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProductScanResult Model
 *
 * ETAP_10: Product Scan System - Individual Scan Results
 *
 * Model przechowuje wynik skanowania pojedynczego produktu.
 * Obsluguje statusy dopasowania, rozwiazywanie konfliktow i tworzenie linkow.
 *
 * Features:
 * - Match status: matched, unmatched, conflict, multiple
 * - Resolution workflow: pending, linked, created, ignored, error
 * - Source data caching dla diff comparison
 * - Automatic linking do Product/PendingProduct
 *
 * @package App\Models
 * @version 1.0
 * @since ETAP_10 - Product Scan System
 */
class ProductScanResult extends Model
{
    use HasFactory;

    protected $table = 'product_scan_results';

    /**
     * Disable updated_at timestamp (only created_at needed)
     */
    public const UPDATED_AT = null;

    // ==========================================
    // MATCH STATUS CONSTANTS
    // ==========================================

    /** Produkt dopasowany - znaleziono w PPM i zrodle */
    public const MATCH_MATCHED = 'matched';

    /** Produkt niedopasowany - nie znaleziono odpowiednika */
    public const MATCH_UNMATCHED = 'unmatched';

    /** Konflikt - roznice w danych miedzy PPM a zrodlem */
    public const MATCH_CONFLICT = 'conflict';

    /** Wielokrotne dopasowanie - wiele produktow z tym samym SKU */
    public const MATCH_MULTIPLE = 'multiple';

    /** Produkt juz powiazany z tym zrodlem */
    public const MATCH_ALREADY_LINKED = 'already_linked';

    // ==========================================
    // RESOLUTION STATUS CONSTANTS
    // ==========================================

    /** Oczekuje na dzialanie uzytkownika */
    public const RESOLUTION_PENDING = 'pending';

    /** Polaczony z istniejacym produktem PPM */
    public const RESOLUTION_LINKED = 'linked';

    /** Utworzony jako nowy PendingProduct */
    public const RESOLUTION_CREATED = 'created';

    /** Zignorowany przez uzytkownika */
    public const RESOLUTION_IGNORED = 'ignored';

    /** Blad podczas rozwiazywania */
    public const RESOLUTION_ERROR = 'error';

    // ==========================================
    // FILLABLE & CASTS
    // ==========================================

    protected $fillable = [
        // Session reference
        'scan_session_id',

        // Product identification
        'sku',
        'external_id',
        'name',

        // Match result
        'match_status',
        'ppm_product_id',
        'external_source_type',
        'external_source_id',

        // Data snapshots
        'source_data',
        'ppm_data',
        'diff_data',

        // Resolution tracking
        'resolution_status',
        'resolution_reason',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        // JSON fields
        'source_data' => 'array',
        'ppm_data' => 'array',
        'diff_data' => 'array',

        // Datetime fields
        'resolved_at' => 'datetime',

        // Integer fields
        'scan_session_id' => 'integer',
        'ppm_product_id' => 'integer',
        'external_source_id' => 'integer',
        'resolved_by' => 'integer',
    ];

    // ==========================================
    // STATIC METHODS - Available Options
    // ==========================================

    /**
     * Get available match statuses for UI
     *
     * @return array<string, string>
     */
    public static function getAvailableMatchStatuses(): array
    {
        return [
            self::MATCH_MATCHED => 'Dopasowany',
            self::MATCH_UNMATCHED => 'Brak',
            self::MATCH_CONFLICT => 'Niepowiązany',
            self::MATCH_MULTIPLE => 'Wiele dopasowań',
            self::MATCH_ALREADY_LINKED => 'Powiązany',
        ];
    }

    /**
     * Get available resolution statuses for UI
     *
     * @return array<string, string>
     */
    public static function getAvailableResolutions(): array
    {
        return [
            self::RESOLUTION_PENDING => 'Oczekuje',
            self::RESOLUTION_LINKED => 'Polaczony',
            self::RESOLUTION_CREATED => 'Utworzony',
            self::RESOLUTION_IGNORED => 'Zignorowany',
            self::RESOLUTION_ERROR => 'Blad',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the scan session this result belongs to
     */
    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ProductScanSession::class, 'scan_session_id');
    }

    /**
     * Get the PPM product if linked
     */
    public function ppmProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ppm_product_id');
    }

    /**
     * Get the user who resolved this result
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to get matched results
     */
    public function scopeMatched($query)
    {
        return $query->where('match_status', self::MATCH_MATCHED);
    }

    /**
     * Scope to get unmatched results
     */
    public function scopeUnmatched($query)
    {
        return $query->where('match_status', self::MATCH_UNMATCHED);
    }

    /**
     * Scope to get conflict results
     */
    public function scopeConflict($query)
    {
        return $query->where('match_status', self::MATCH_CONFLICT);
    }

    /**
     * Scope to get pending resolution results
     */
    public function scopePending($query)
    {
        return $query->where('resolution_status', self::RESOLUTION_PENDING);
    }

    /**
     * Scope to get resolved results
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('resolution_status', [
            self::RESOLUTION_LINKED,
            self::RESOLUTION_CREATED,
            self::RESOLUTION_IGNORED,
        ]);
    }

    /**
     * Scope to get linked results
     */
    public function scopeLinked($query)
    {
        return $query->where('resolution_status', self::RESOLUTION_LINKED);
    }

    /**
     * Scope to get results by SKU
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    // ==========================================
    // STATUS HELPERS
    // ==========================================

    /**
     * Check if result is matched
     */
    public function isMatched(): bool
    {
        return $this->match_status === self::MATCH_MATCHED;
    }

    /**
     * Check if result is unmatched
     */
    public function isUnmatched(): bool
    {
        return $this->match_status === self::MATCH_UNMATCHED;
    }

    /**
     * Check if result has conflict
     */
    public function hasConflict(): bool
    {
        return $this->match_status === self::MATCH_CONFLICT;
    }

    /**
     * Check if result has multiple matches
     */
    public function hasMultipleMatches(): bool
    {
        return $this->match_status === self::MATCH_MULTIPLE;
    }

    /**
     * Check if result is already linked to source
     */
    public function isAlreadyLinked(): bool
    {
        return $this->match_status === self::MATCH_ALREADY_LINKED;
    }

    /**
     * Check if resolution is pending
     */
    public function isPending(): bool
    {
        return $this->resolution_status === self::RESOLUTION_PENDING;
    }

    /**
     * Check if result is resolved
     */
    public function isResolved(): bool
    {
        return in_array($this->resolution_status, [
            self::RESOLUTION_LINKED,
            self::RESOLUTION_CREATED,
            self::RESOLUTION_IGNORED,
        ]);
    }

    /**
     * Check if result is linked
     */
    public function isLinked(): bool
    {
        return $this->resolution_status === self::RESOLUTION_LINKED;
    }

    // ==========================================
    // RESOLUTION METHODS
    // ==========================================

    /**
     * Link this result to an existing PPM product
     *
     * Creates ProductErpData or ProductShopData record based on source type.
     *
     * @param int $productId PPM Product ID to link to
     * @param int|null $userId User who performed the action
     * @return bool Success status
     */
    public function linkToProduct(int $productId, ?int $userId = null): bool
    {
        try {
            $session = $this->scanSession;

            if (!$session) {
                Log::warning('ProductScanResult::linkToProduct - Session not found', [
                    'result_id' => $this->id,
                ]);
                return false;
            }

            // Create appropriate link based on source type
            if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
                // Create ProductShopData link
                ProductShopData::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'shop_id' => $session->source_id,
                    ],
                    [
                        'external_id' => $this->external_id,
                        'sync_status' => 'synced',
                        'last_sync_at' => Carbon::now(),
                    ]
                );
            } else {
                // Create ProductErpData link for ERP sources
                ProductErpData::updateOrCreate(
                    [
                        'product_id' => $productId,
                        'erp_connection_id' => $session->source_id,
                    ],
                    [
                        'external_id' => $this->external_id,
                        'sku' => $this->sku,
                        'name' => $this->name,
                        'sync_status' => ProductErpData::STATUS_SYNCED,
                        'last_sync_at' => Carbon::now(),
                        'external_data' => $this->source_data,
                    ]
                );
            }

            // Update result status
            $this->update([
                'ppm_product_id' => $productId,
                'resolution_status' => self::RESOLUTION_LINKED,
                'resolved_at' => Carbon::now(),
                'resolved_by' => $userId,
            ]);

            Log::info('ProductScanResult linked to product', [
                'result_id' => $this->id,
                'product_id' => $productId,
                'sku' => $this->sku,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ProductScanResult::linkToProduct failed', [
                'result_id' => $this->id,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            $this->update([
                'resolution_status' => self::RESOLUTION_ERROR,
                'resolution_reason' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create this result as a new PendingProduct
     *
     * @param int|null $userId User who performed the action
     * @return PendingProduct|null Created PendingProduct or null on failure
     */
    public function createAsPendingProduct(?int $userId = null): ?PendingProduct
    {
        try {
            $sourceData = $this->source_data ?? [];

            // Create PendingProduct from scan result
            // Using correct fields from PendingProduct model
            $pendingProduct = PendingProduct::create([
                'sku' => $this->sku,
                'name' => $this->name ?? $sourceData['name'] ?? 'Unnamed Product',
                'slug' => \Str::slug($this->name ?? $this->sku ?? 'unnamed-product'),
                'imported_by' => $userId,
                'imported_at' => Carbon::now(),
                'completion_status' => [
                    'sku' => true,
                    'name' => true,
                    'manufacturer_id' => false,
                    'category_ids' => false,
                    'product_type_id' => false,
                    'publication_targets' => false,
                ],
                'completion_percentage' => 27, // 2 of 6 required fields (sku, name)
                'is_ready_for_publish' => false,
            ]);

            // Update result status
            $this->update([
                'resolution_status' => self::RESOLUTION_CREATED,
                'resolved_at' => Carbon::now(),
                'resolved_by' => $userId,
            ]);

            Log::info('ProductScanResult created as PendingProduct', [
                'result_id' => $this->id,
                'pending_product_id' => $pendingProduct->id,
                'sku' => $this->sku,
            ]);

            return $pendingProduct;
        } catch (\Exception $e) {
            Log::error('ProductScanResult::createAsPendingProduct failed', [
                'result_id' => $this->id,
                'sku' => $this->sku,
                'error' => $e->getMessage(),
            ]);

            // Don't try to update resolution_reason if column doesn't exist
            try {
                $this->update([
                    'resolution_status' => self::RESOLUTION_ERROR,
                ]);
            } catch (\Exception $updateException) {
                Log::warning('Failed to update resolution status', [
                    'result_id' => $this->id,
                    'error' => $updateException->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Mark this result as ignored
     *
     * @param string|null $reason Reason for ignoring
     * @param int|null $userId User who performed the action
     */
    public function markAsIgnored(?string $reason = null, ?int $userId = null): self
    {
        $this->update([
            'resolution_status' => self::RESOLUTION_IGNORED,
            'resolution_reason' => $reason,
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId,
        ]);

        Log::info('ProductScanResult marked as ignored', [
            'result_id' => $this->id,
            'sku' => $this->sku,
            'reason' => $reason,
        ]);

        return $this;
    }

    // ==========================================
    // DIFF HELPERS
    // ==========================================

    /**
     * Get diff between source and PPM data
     *
     * @return array Diff data with fields: changed, added, removed
     */
    public function getDiff(): array
    {
        if (!empty($this->diff_data)) {
            return $this->diff_data;
        }

        $sourceData = $this->source_data ?? [];
        $ppmData = $this->ppm_data ?? [];

        $diff = [
            'changed' => [],
            'added' => [],
            'removed' => [],
        ];

        // Find changed and added fields
        foreach ($sourceData as $key => $sourceValue) {
            if (!array_key_exists($key, $ppmData)) {
                $diff['added'][$key] = $sourceValue;
            } elseif ($ppmData[$key] !== $sourceValue) {
                $diff['changed'][$key] = [
                    'source' => $sourceValue,
                    'ppm' => $ppmData[$key],
                ];
            }
        }

        // Find removed fields (in PPM but not in source)
        foreach ($ppmData as $key => $ppmValue) {
            if (!array_key_exists($key, $sourceData)) {
                $diff['removed'][$key] = $ppmValue;
            }
        }

        return $diff;
    }

    /**
     * Check if there are differences between source and PPM
     */
    public function hasDifferences(): bool
    {
        $diff = $this->getDiff();

        return !empty($diff['changed'])
            || !empty($diff['added'])
            || !empty($diff['removed']);
    }

    /**
     * Get count of differences
     */
    public function getDifferenceCount(): int
    {
        $diff = $this->getDiff();

        return count($diff['changed'] ?? [])
            + count($diff['added'] ?? [])
            + count($diff['removed'] ?? []);
    }

    // ==========================================
    // DISPLAY HELPERS
    // ==========================================

    /**
     * Get match status label for UI
     */
    public function getMatchStatusLabel(): string
    {
        return self::getAvailableMatchStatuses()[$this->match_status] ?? $this->match_status;
    }

    /**
     * Get resolution status label for UI
     */
    public function getResolutionStatusLabel(): string
    {
        return self::getAvailableResolutions()[$this->resolution_status] ?? $this->resolution_status;
    }

    /**
     * Get match status badge CSS class
     */
    public function getMatchStatusBadgeClass(): string
    {
        return match ($this->match_status) {
            self::MATCH_MATCHED => 'badge-success',
            self::MATCH_UNMATCHED => 'badge-warning',
            self::MATCH_CONFLICT => 'badge-danger',
            self::MATCH_MULTIPLE => 'badge-info',
            self::MATCH_ALREADY_LINKED => 'badge-primary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get resolution status badge CSS class
     */
    public function getResolutionBadgeClass(): string
    {
        return match ($this->resolution_status) {
            self::RESOLUTION_PENDING => 'badge-warning',
            self::RESOLUTION_LINKED => 'badge-success',
            self::RESOLUTION_CREATED => 'badge-info',
            self::RESOLUTION_IGNORED => 'badge-secondary',
            self::RESOLUTION_ERROR => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Get display name (SKU + Name)
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return "{$this->sku} - {$this->name}";
        }

        return $this->sku ?? 'Unknown Product';
    }
}
