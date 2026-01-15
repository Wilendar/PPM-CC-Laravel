<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttributeValuePsMapping Model
 *
 * Maps PPM AttributeValue → PrestaShop ps_attribute per shop
 * Tracks synchronization status per shop per value
 *
 * ETAP_05b FAZA 5 - Panel Masowego Zarzadzania Wariantami
 *
 * RELATIONSHIPS:
 * - belongs to AttributeValue
 * - belongs to PrestaShopShop
 *
 * SYNC STATUSES:
 * - synced: Attribute value exists and is synchronized
 * - conflict: Mismatch (label or color differs)
 * - missing: Attribute value doesn't exist in PrestaShop
 * - pending: Waiting for sync verification (default)
 *
 * @property int $id
 * @property int $attribute_value_id FK to attribute_values
 * @property int $prestashop_shop_id FK to prestashop_shops
 * @property int|null $prestashop_attribute_id PrestaShop ps_attribute.id_attribute
 * @property string|null $prestashop_label Label from PrestaShop
 * @property string|null $prestashop_color Color from PrestaShop (#ffffff format)
 * @property bool $is_synced Whether synchronized
 * @property string $sync_status Current sync status
 * @property \Illuminate\Support\Carbon|null $last_synced_at Last sync timestamp
 * @property string|null $sync_notes Error messages, sync details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @package App\Models
 * @version 1.0
 * @since 2025-12-11
 */
class AttributeValuePsMapping extends Model
{
    protected $table = 'prestashop_attribute_value_mapping';

    protected $fillable = [
        'attribute_value_id',
        'prestashop_shop_id',
        'prestashop_attribute_id',
        'prestashop_label',
        'prestashop_color',
        'is_synced',
        'sync_status',
        'last_synced_at',
        'sync_notes',
    ];

    protected $casts = [
        'attribute_value_id' => 'integer',
        'prestashop_shop_id' => 'integer',
        'prestashop_attribute_id' => 'integer',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The attribute value this mapping belongs to
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }

    /**
     * The PrestaShop shop this mapping belongs to
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'prestashop_shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only synced mappings
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope: Only pending mappings
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Scope: Only missing mappings
     */
    public function scopeMissing($query)
    {
        return $query->where('sync_status', 'missing');
    }

    /**
     * Scope: Only conflict mappings
     */
    public function scopeConflict($query)
    {
        return $query->where('sync_status', 'conflict');
    }

    /**
     * Scope: Filter by shop
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('prestashop_shop_id', $shopId);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if mapping is synchronized
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced' && $this->is_synced;
    }

    /**
     * Check if mapping has conflict
     */
    public function hasConflict(): bool
    {
        return $this->sync_status === 'conflict';
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->sync_status) {
            'synced' => 'bg-green-500/20 text-green-400 border-green-500/30',
            'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
            'conflict' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
            'missing' => 'bg-red-500/20 text-red-400 border-red-500/30',
            default => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
        };
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIcon(): string
    {
        return match ($this->sync_status) {
            'synced' => '✅',
            'pending' => '⚠️',
            'conflict' => '⚡',
            'missing' => '❌',
            default => '❓',
        };
    }
}
