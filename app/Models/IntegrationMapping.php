<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * IntegrationMapping Model - FAZA C: Universal Integration Mapping System
 * 
 * Mapuje obiekty PPM na systemy zewnętrzne dla aplikacji PPM-CC-Laravel:
 * - PrestaShop (produkty, kategorie, ceny, magazyny) - Multi-store support
 * - Baselinker (produkty, zamówienia, stany magazynowe)
 * - Subiekt GT (towary, kontrahenci, dokumenty handlowe)
 * - Microsoft Dynamics (entities, business data)
 * - Custom integrations (API endpoints, webhooks)
 * 
 * Enterprise integration features:
 * - Polymorphic mapping dla uniwersalności
 * - Multi-store PrestaShop support z integration_identifier
 * - Bi-directional sync control i conflict detection
 * - Complete external data storage w JSONB
 * - Advanced error handling i retry mechanisms
 * - Version control dla conflict resolution
 * - Scheduled sync support
 * 
 * Performance optimizations:
 * - Strategic indexing dla sync operations
 * - Efficient external ID lookups
 * - Bulk mapping operations support
 * - Optimized conflict queries
 * 
 * @property int $id
 * @property string $mappable_type Product|Category|PriceGroup|Warehouse|User
 * @property int $mappable_id ID obiektu w systemie PPM
 * @property string $integration_type prestashop|baselinker|subiekt_gt|dynamics|custom
 * @property string $integration_identifier Identyfikator systemu (shop_id, instance_name)
 * @property string $external_id ID w systemie zewnętrznym
 * @property string|null $external_reference Dodatkowa referencja (SKU, code)
 * @property array|null $external_data Pełne dane z systemu zewnętrznego
 * @property string $sync_status pending|synced|error|conflict|disabled
 * @property string $sync_direction both|to_external|from_external|disabled
 * @property \Carbon\Carbon|null $last_sync_at Ostatnia synchronizacja
 * @property \Carbon\Carbon|null $next_sync_at Następna zaplanowana synchronizacja
 * @property string|null $error_message Szczegóły ostatniego błędu
 * @property int $error_count Liczba błędów z rzędu
 * @property array|null $conflict_data Dane konfliktu do rozwiązania
 * @property \Carbon\Carbon|null $conflict_detected_at Kiedy wykryto konflikt
 * @property string|null $ppm_version_hash Hash wersji danych w PPM
 * @property string|null $external_version_hash Hash wersji danych zewnętrznych
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read \App\Models\Product|\App\Models\Category|\App\Models\PriceGroup|\App\Models\Warehouse|\App\Models\User $mappable
 * @property-read bool $is_synced
 * @property-read bool $has_error
 * @property-read bool $has_conflict
 * @property-read bool $needs_sync
 * @property-read string $sync_status_label
 * @property-read array $external_data_parsed
 * 
 * @method static \Illuminate\Database\Eloquent\Builder forIntegration(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder prestashop()
 * @method static \Illuminate\Database\Eloquent\Builder baselinker()
 * @method static \Illuminate\Database\Eloquent\Builder synced()
 * @method static \Illuminate\Database\Eloquent\Builder needsSync()
 * @method static \Illuminate\Database\Eloquent\Builder withErrors()
 * @method static \Illuminate\Database\Eloquent\Builder withConflicts()
 * @method static \Illuminate\Database\Eloquent\Builder scheduled()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA C - Media & Relations Implementation
 */
class IntegrationMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'mappable_type',
        'mappable_id',
        'integration_type',
        'integration_identifier',
        'external_id',
        'external_reference',
        'external_data',
        'sync_status',
        'sync_direction',
        'last_sync_at',
        'next_sync_at',
        'error_message',
        'error_count',
        'conflict_data',
        'conflict_detected_at',
        'ppm_version_hash',
        'external_version_hash',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'error_message',        // Hide sensitive error details
        'ppm_version_hash',     // Internal versioning
        'external_version_hash', // Internal versioning
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mappable_id' => 'integer',
            'error_count' => 'integer',
            'external_data' => 'array',
            'conflict_data' => 'array',
            'last_sync_at' => 'datetime',
            'next_sync_at' => 'datetime',
            'conflict_detected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-generation version hashes i next sync scheduling
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generate version hashes on create/update
        static::saving(function ($mapping) {
            if ($mapping->isDirty(['external_data'])) {
                $mapping->external_version_hash = $mapping->generateExternalHash();
            }
        });

        // Schedule next sync after successful sync
        static::saved(function ($mapping) {
            if ($mapping->sync_status === 'synced' && $mapping->next_sync_at === null) {
                $mapping->scheduleNextSync();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the parent mappable model (Product, Category, etc.).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function mappable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Check if mapping is successfully synced
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isSynced(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->sync_status === 'synced'
        );
    }

    /**
     * Check if mapping has sync errors
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasError(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->sync_status === 'error'
        );
    }

    /**
     * Check if mapping has conflicts
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function hasConflict(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->sync_status === 'conflict'
        );
    }

    /**
     * Check if mapping needs synchronization
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function needsSync(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->sync_status, ['pending', 'error']) 
                              || ($this->next_sync_at && $this->next_sync_at->isPast())
        );
    }

    /**
     * Get human-readable sync status
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function syncStatusLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return match ($this->sync_status) {
                    'pending' => 'Oczekuje synchronizacji',
                    'synced' => 'Zsynchronizowane',
                    'error' => 'Błąd synchronizacji',
                    'conflict' => 'Konflikt danych',
                    'disabled' => 'Synchronizacja wyłączona',
                    default => 'Nieznany status',
                };
            }
        );
    }

    /**
     * Get parsed external data with fallbacks
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function externalDataParsed(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (empty($this->external_data) || !is_array($this->external_data)) {
                    return [];
                }

                // Ensure consistent structure
                return array_merge([
                    'id' => $this->external_id,
                    'reference' => $this->external_reference,
                    'last_modified' => null,
                    'checksum' => null,
                    'metadata' => [],
                ], $this->external_data);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by integration type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForIntegration(Builder $query, string $type): Builder
    {
        return $query->where('integration_type', $type);
    }

    /**
     * Scope: PrestaShop mappings only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrestashop(Builder $query): Builder
    {
        return $query->where('integration_type', 'prestashop');
    }

    /**
     * Scope: Baselinker mappings only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBaselinker(Builder $query): Builder
    {
        return $query->where('integration_type', 'baselinker');
    }

    /**
     * Scope: Successfully synced mappings
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSynced(Builder $query): Builder
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope: Mappings that need synchronization
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsSync(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('sync_status', ['pending', 'error'])
              ->orWhere(function ($q2) {
                  $q2->where('next_sync_at', '<=', now())
                     ->whereNotNull('next_sync_at');
              });
        });
    }

    /**
     * Scope: Mappings with sync errors
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->where('sync_status', 'error');
    }

    /**
     * Scope: Mappings with conflicts
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithConflicts(Builder $query): Builder
    {
        return $query->where('sync_status', 'conflict');
    }

    /**
     * Scope: Mappings scheduled for sync
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('next_sync_at')
                    ->where('next_sync_at', '<=', now());
    }

    /**
     * Scope: Filter by mappable type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMappableType(Builder $query, string $type): Builder
    {
        return $query->where('mappable_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Mark mapping as successfully synced
     *
     * @param array $externalData
     * @return bool
     */
    public function markAsSynced(array $externalData = []): bool
    {
        $this->sync_status = 'synced';
        $this->last_sync_at = now();
        $this->error_message = null;
        $this->error_count = 0;
        
        if (!empty($externalData)) {
            $this->external_data = array_merge($this->external_data ?? [], $externalData);
        }
        
        $this->scheduleNextSync();
        
        return $this->save();
    }

    /**
     * Mark mapping as failed with error
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->sync_status = 'error';
        $this->error_message = $errorMessage;
        $this->error_count++;
        
        // Schedule retry based on error count (exponential backoff)
        $retryMinutes = min(60 * pow(2, $this->error_count), 1440); // Max 24 hours
        $this->next_sync_at = now()->addMinutes($retryMinutes);
        
        return $this->save();
    }

    /**
     * Mark mapping as conflict (requires manual resolution)
     *
     * @param array $conflictData
     * @return bool
     */
    public function markAsConflict(array $conflictData): bool
    {
        $this->sync_status = 'conflict';
        $this->conflict_data = $conflictData;
        $this->conflict_detected_at = now();
        $this->next_sync_at = null; // Stop automatic sync
        
        return $this->save();
    }

    /**
     * Resolve conflict and resume sync
     *
     * @param string $resolution
     * @param array $resolvedData
     * @return bool
     */
    public function resolveConflict(string $resolution, array $resolvedData = []): bool
    {
        $this->sync_status = 'pending';
        
        // Store resolution for audit
        $conflictResolution = [
            'resolved_at' => now()->toISOString(),
            'resolution_type' => $resolution,
            'resolved_data' => $resolvedData,
            'original_conflict' => $this->conflict_data,
        ];
        
        $this->conflict_data = $conflictResolution;
        $this->conflict_detected_at = null;
        $this->next_sync_at = now(); // Immediate sync
        
        return $this->save();
    }

    /**
     * Get external data for specific key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getExternalData(string $key, mixed $default = null): mixed
    {
        $data = $this->external_data_parsed;
        
        return data_get($data, $key, $default);
    }

    /**
     * Set external data for specific key
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setExternalData(string $key, mixed $value): bool
    {
        $data = $this->external_data ?? [];
        data_set($data, $key, $value);
        
        $this->external_data = $data;
        
        return $this->save();
    }

    /**
     * Generate hash for external data (for conflict detection)
     *
     * @return string
     */
    private function generateExternalHash(): string
    {
        $data = $this->external_data ?? [];
        
        // Remove timestamp fields that change frequently
        unset($data['last_sync'], $data['timestamp'], $data['synced_at']);
        
        return hash('sha256', json_encode($data));
    }

    /**
     * Schedule next synchronization
     *
     * @param int $intervalMinutes
     * @return void
     */
    private function scheduleNextSync(int $intervalMinutes = null): void
    {
        if ($this->sync_direction === 'disabled') {
            $this->next_sync_at = null;
            return;
        }
        
        // Default intervals based on integration type
        $defaultIntervals = [
            'prestashop' => 60,    // 1 hour
            'baselinker' => 30,    // 30 minutes
            'subiekt_gt' => 120,   // 2 hours
            'dynamics' => 240,     // 4 hours
            'custom' => 60,        // 1 hour
        ];
        
        $interval = $intervalMinutes ?? $defaultIntervals[$this->integration_type] ?? 60;
        $this->next_sync_at = now()->addMinutes($interval);
    }

    /**
     * Check if sync is allowed based on direction
     *
     * @param string $direction 'to_external'|'from_external'
     * @return bool
     */
    public function isSyncAllowed(string $direction): bool
    {
        return $this->sync_direction === 'both' || $this->sync_direction === $direction;
    }

    /**
     * Get sync errors count for this mapping
     *
     * @return int
     */
    public function getSyncErrorsCount(): int
    {
        return $this->error_count;
    }

    /**
     * Reset error count (after successful sync)
     *
     * @return bool
     */
    public function resetErrorCount(): bool
    {
        $this->error_count = 0;
        $this->error_message = null;
        
        return $this->save();
    }

    /**
     * Get time since last successful sync
     *
     * @return \Carbon\Carbon|null
     */
    public function getTimeSinceLastSync(): ?Carbon
    {
        return $this->last_sync_at;
    }

    /**
     * Check if mapping is stale (not synced for too long)
     *
     * @param int $hoursThreshold
     * @return bool
     */
    public function isStale(int $hoursThreshold = 24): bool
    {
        if (!$this->last_sync_at) {
            return true; // Never synced
        }
        
        return $this->last_sync_at->diffInHours(now()) > $hoursThreshold;
    }

    /**
     * Create mapping from external system lookup
     *
     * @param string $integrationType
     * @param string $externalId
     * @return static|null
     */
    public static function findByExternalId(string $integrationType, string $externalId): ?static
    {
        return static::where('integration_type', $integrationType)
                    ->where('external_id', $externalId)
                    ->first();
    }

    /**
     * Bulk update sync status for multiple mappings
     *
     * @param array $mappingIds
     * @param string $status
     * @param string|null $errorMessage
     * @return int
     */
    public static function bulkUpdateSyncStatus(array $mappingIds, string $status, ?string $errorMessage = null): int
    {
        $updateData = [
            'sync_status' => $status,
            'updated_at' => now(),
        ];
        
        if ($status === 'synced') {
            $updateData['last_sync_at'] = now();
            $updateData['error_message'] = null;
            $updateData['error_count'] = 0;
        } elseif ($status === 'error' && $errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }
        
        return static::whereIn('id', $mappingIds)->update($updateData);
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return 'integration_mappings';
    }
}