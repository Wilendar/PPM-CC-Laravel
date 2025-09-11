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
        // Wszyscy zalogowani mogą browse products
        $canView = $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'viewAny', 'Product', $canView);
        return $canView;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, $product = null): bool
    {
        // Wszyscy zalogowani mogą view products
        $canView = $this->isActiveUser($user);
        
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
        // Manager+ mogą tworzyć produkty
        $canCreate = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'create', 'Product', $canCreate);
        return $canCreate;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, $product = null): bool
    {
        // Editor+ mogą edytować produkty
        $canUpdate = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
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
        // Tylko Manager+ mogą usuwać produkty
        $canDelete = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        $canRestore = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        // Permanent delete - tylko Admin
        $canForceDelete = $this->canAccessAdmin($user);
        
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
        // Manager+ mogą zarządzać cenami
        $canManagePrices = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        // Tylko Manager+ mogą view ceny zakupu (Salesperson nie może)
        $canViewPurchasePrices = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        // Warehouseman+ mogą zarządzać stanem magazynowym
        $canManageStock = $this->hasRoleOrHigher($user, 'Warehouseman') && $this->isActiveUser($user);
        
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
        // Editor+ mogą zarządzać mediami
        $canManageMedia = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
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
        // Manager+ mogą sync do Prestashop
        $canSync = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        // Manager+ mogą sync do ERP
        $canSyncERP = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        $canBulk = $this->canBulkAction($user);
        $this->logAuthAttempt($user, 'bulkOperations', 'Product', $canBulk);
        return $canBulk;
    }

    /**
     * Determine whether the user can export products.
     */
    public function export(User $user): bool
    {
        // Editor+ mogą eksportować
        $canExport = $this->canExport($user);
        $this->logAuthAttempt($user, 'export', 'Product', $canExport);
        return $canExport;
    }

    /**
     * Determine whether the user can import products.
     */
    public function import(User $user): bool
    {
        // Manager+ mogą importować
        $canImport = $this->canImport($user);
        $this->logAuthAttempt($user, 'import', 'Product', $canImport);
        return $canImport;
    }

    /**
     * Determine whether the user can manage product categories.
     */
    public function manageCategories(User $user, $product = null): bool
    {
        // Editor+ mogą zarządzać kategoriami produktów
        $canManageCategories = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
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
        // Manager+ mogą zarządzać wariantami
        $canManageVariants = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
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
        // Editor+ mogą zarządzać atrybutami
        $canManageAttributes = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
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
        // Salesperson+ mogą robić rezerwacje z kontenera
        $canReserve = $this->hasRoleOrHigher($user, 'Salesperson') && $this->isActiveUser($user);
        
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
        // Claims+ mogą access produkty dla reklamacji
        $canAccessClaims = $this->hasRoleOrHigher($user, 'Claims') && $this->isActiveUser($user);
        
        if ($product) {
            $this->logAuthAttempt($user, 'accessForClaims', "Product:{$product->sku}", $canAccessClaims);
        }
        
        return $canAccessClaims;
    }
}