<?php

namespace App\Models\Concerns\BusinessPartner;

use App\Models\PrestaShopShop;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasShopSync - Shop synchronization methods for BusinessPartner
 *
 * Handles PrestaShop shop assignment, sync status tracking,
 * logo sync, and error management via pivot table.
 */
trait HasShopSync
{
    /*
    |--------------------------------------------------------------------------
    | SHOP RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    /**
     * PrestaShop shops where this partner is available.
     * Pivot contains ps_manufacturer_id, ps_supplier_id, sync_status, etc.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(
            PrestaShopShop::class,
            'business_partner_shop',
            'business_partner_id',
            'prestashop_shop_id'
        )
            ->withPivot([
                'ps_manufacturer_id',
                'ps_supplier_id',
                'sync_status',
                'last_synced_at',
                'logo_synced',
                'logo_synced_at',
                'sync_error',
            ])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Assign partner to shop with optional PrestaShop ID.
     */
    public function assignToShop(int $shopId, ?int $psId = null): void
    {
        $field = $this->getPsEntityField();
        $pivotData = [
            'sync_status' => $psId ? 'synced' : 'pending',
            'last_synced_at' => $psId ? now() : null,
        ];

        if ($field && $psId !== null) {
            $pivotData[$field] = $psId;
        }

        $this->shops()->syncWithoutDetaching([$shopId => $pivotData]);
    }

    public function removeFromShop(int $shopId): void
    {
        $this->shops()->detach($shopId);
    }

    /*
    |--------------------------------------------------------------------------
    | PS ID LOOKUP
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop ID for specific shop (returns ps_manufacturer_id or ps_supplier_id).
     */
    public function getPsIdForShop(int $shopId): ?int
    {
        $field = $this->getPsEntityField();
        if (!$field) {
            return null;
        }

        $pivot = $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->first()
            ?->pivot;

        return $pivot?->{$field};
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Update sync status for shop.
     */
    public function updateSyncStatus(int $shopId, string $status, ?string $error = null): void
    {
        $data = ['sync_status' => $status];

        if ($status === 'synced') {
            $data['last_synced_at'] = now();
        }

        if ($error !== null) {
            $data['sync_error'] = $error;
        }

        $this->shops()->updateExistingPivot($shopId, $data);
    }

    public function isSyncedToShop(int $shopId): bool
    {
        return $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->wherePivot('sync_status', 'synced')
            ->exists();
    }

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
            'ps_supplier_id' => $shop->pivot->ps_supplier_id,
            'sync_status' => $shop->pivot->sync_status,
            'last_synced_at' => $shop->pivot->last_synced_at,
            'logo_synced' => $shop->pivot->logo_synced,
            'logo_synced_at' => $shop->pivot->logo_synced_at,
            'sync_error' => $shop->pivot->sync_error,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LOGO SYNC
    |--------------------------------------------------------------------------
    */

    /**
     * Update logo sync status for shop.
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

    public function isLogoSyncedToShop(int $shopId): bool
    {
        return $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->wherePivot('logo_synced', true)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | ERROR MANAGEMENT
    |--------------------------------------------------------------------------
    */

    public function getSyncErrorForShop(int $shopId): ?string
    {
        return $this->shops()
            ->where('prestashop_shops.id', $shopId)
            ->first()
            ?->pivot
            ?->sync_error;
    }

    public function clearSyncError(int $shopId): void
    {
        $this->shops()->updateExistingPivot($shopId, ['sync_error' => null]);
    }

    public function setSyncError(int $shopId, string $error): void
    {
        $this->shops()->updateExistingPivot($shopId, [
            'sync_status' => 'error',
            'sync_error' => $error,
        ]);
    }
}
