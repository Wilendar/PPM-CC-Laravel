<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM Product Policy
 *
 * Policy dla zarządzania produktami w systemie PPM.
 * Implementuje complex authorization logic dla product operations.
 *
 * FAZA A: Spatie Setup + Middleware - Product Management Policy
 *
 * Permissions per Role:
 * - Admin: Wszystko
 * - Manager: CRUD + bulk operations + sync
 * - Editor: View + Edit (no delete, no bulk, no sync)
 * - Warehouseman: View + stock management
 * - Salesperson: View + pricing (bez cen zakupu)
 * - Claims: View (dla reklamacji)
 * - User: View only (search/browse)
 *
 * Special considerations:
 * - SKU jako primary key zamiast ID
 * - Multi-store product variations
 * - Stock management per warehouse
 * - Price groups visibility
 * - Integration sync permissions
 */
class ProductPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        $canView = $this->checkPermission($user, 'products.read');
        $this->logAuthAttempt($user, 'viewAny', 'Product', $canView);
        return $canView;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, $product = null): bool
    {
        $canView = $this->checkPermission($user, 'products.read');

        if ($product) {
            $this->logAuthAttempt($user, 'view', "Product:{$product->sku}", $canView);
        }

        return $canView;
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        $canCreate = $this->checkPermission($user, 'products.create');
        $this->logAuthAttempt($user, 'create', 'Product', $canCreate);
        return $canCreate;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, $product = null): bool
    {
        $canUpdate = $this->checkPermission($user, 'products.update');

        if ($product) {
            $this->logAuthAttempt($user, 'update', "Product:{$product->sku}", $canUpdate);
        }

        return $canUpdate;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, $product = null): bool
    {
        $canDelete = $this->checkPermission($user, 'products.delete');

        if ($product) {
            $this->logAuthAttempt($user, 'delete', "Product:{$product->sku}", $canDelete);
        }

        return $canDelete;
    }

    /**
     * Determine whether the user can restore the product.
     */
    public function restore(User $user, $product = null): bool
    {
        $canRestore = $this->checkPermission($user, 'products.update');

        if ($product) {
            $this->logAuthAttempt($user, 'restore', "Product:{$product->sku}", $canRestore);
        }

        return $canRestore;
    }

    /**
     * Determine whether the user can permanently delete the product.
     */
    public function forceDelete(User $user, $product = null): bool
    {
        $canForceDelete = $this->checkPermission($user, 'products.delete');

        if ($product) {
            $this->logAuthAttempt($user, 'forceDelete', "Product:{$product->sku}", $canForceDelete);
        }

        return $canForceDelete;
    }

    /**
     * Determine whether the user can manage product prices.
     */
    public function managePrices(User $user, $product = null): bool
    {
        $canManagePrices = $this->checkPermission($user, 'prices.update');

        if ($product) {
            $this->logAuthAttempt($user, 'managePrices', "Product:{$product->sku}", $canManagePrices);
        }

        return $canManagePrices;
    }

    /**
     * Determine whether the user can view purchase prices.
     */
    public function viewPurchasePrices(User $user, $product = null): bool
    {
        $canViewPurchasePrices = $this->checkPermission($user, 'prices.cost');

        if ($product) {
            $this->logAuthAttempt($user, 'viewPurchasePrices', "Product:{$product->sku}", $canViewPurchasePrices);
        }

        return $canViewPurchasePrices;
    }

    /**
     * Determine whether the user can manage product stock.
     */
    public function manageStock(User $user, $product = null): bool
    {
        $canManageStock = $this->checkPermission($user, 'stock.update');

        if ($product) {
            $this->logAuthAttempt($user, 'manageStock', "Product:{$product->sku}", $canManageStock);
        }

        return $canManageStock;
    }

    /**
     * Determine whether the user can manage product media.
     */
    public function manageMedia(User $user, $product = null): bool
    {
        $canManageMedia = $this->checkPermission($user, 'media.update');

        if ($product) {
            $this->logAuthAttempt($user, 'manageMedia', "Product:{$product->sku}", $canManageMedia);
        }

        return $canManageMedia;
    }

    /**
     * Determine whether the user can sync product to Prestashop.
     */
    public function syncToPrestashop(User $user, $product = null): bool
    {
        $canSync = $this->checkPermission($user, 'shops.sync');

        if ($product) {
            $this->logAuthAttempt($user, 'syncToPrestashop', "Product:{$product->sku}", $canSync);
        }

        return $canSync;
    }

    /**
     * Determine whether the user can sync product to ERP.
     */
    public function syncToERP(User $user, $product = null): bool
    {
        $canSyncERP = $this->checkPermission($user, 'integrations.sync');

        if ($product) {
            $this->logAuthAttempt($user, 'syncToERP', "Product:{$product->sku}", $canSyncERP);
        }

        return $canSyncERP;
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): bool
    {
        $canBulk = $this->checkPermission($user, 'products.delete');
        $this->logAuthAttempt($user, 'bulkOperations', 'Product', $canBulk);
        return $canBulk;
    }

    /**
     * Determine whether the user can export products.
     */
    public function export(User $user): bool
    {
        $canExport = $this->checkPermission($user, 'products.export');
        $this->logAuthAttempt($user, 'export', 'Product', $canExport);
        return $canExport;
    }

    /**
     * Determine whether the user can import products.
     */
    public function import(User $user): bool
    {
        $canImport = $this->checkPermission($user, 'products.import');
        $this->logAuthAttempt($user, 'import', 'Product', $canImport);
        return $canImport;
    }

    /**
     * Determine whether the user can manage product categories.
     */
    public function manageCategories(User $user, $product = null): bool
    {
        $canManageCategories = $this->checkPermission($user, 'categories.update');

        if ($product) {
            $this->logAuthAttempt($user, 'manageCategories', "Product:{$product->sku}", $canManageCategories);
        }

        return $canManageCategories;
    }

    /**
     * Determine whether the user can manage product variants.
     */
    public function manageVariants(User $user, $product = null): bool
    {
        $canManageVariants = $this->checkPermission($user, 'products.variants');

        if ($product) {
            $this->logAuthAttempt($user, 'manageVariants', "Product:{$product->sku}", $canManageVariants);
        }

        return $canManageVariants;
    }

    /**
     * Determine whether the user can manage product attributes.
     */
    public function manageAttributes(User $user, $product = null): bool
    {
        $canManageAttributes = $this->checkPermission($user, 'products.update');

        if ($product) {
            $this->logAuthAttempt($user, 'manageAttributes', "Product:{$product->sku}", $canManageAttributes);
        }

        return $canManageAttributes;
    }

    /**
     * Determine whether the user can make container reservations.
     */
    public function makeReservations(User $user, $product = null): bool
    {
        $canReserve = $this->checkPermission($user, 'orders.reservations');

        if ($product) {
            $this->logAuthAttempt($user, 'makeReservations', "Product:{$product->sku}", $canReserve);
        }

        return $canReserve;
    }

    /**
     * Determine whether the user can access product for claims.
     */
    public function accessForClaims(User $user, $product = null): bool
    {
        $canAccessClaims = $this->checkPermission($user, 'claims.read');

        if ($product) {
            $this->logAuthAttempt($user, 'accessForClaims', "Product:{$product->sku}", $canAccessClaims);
        }

        return $canAccessClaims;
    }
}
