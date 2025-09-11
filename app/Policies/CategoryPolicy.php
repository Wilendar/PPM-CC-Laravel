<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM Category Policy
 * 
 * Policy dla zarządzania kategoriami w systemie PPM.
 * Implementuje authorization logic dla 5-poziomowej struktury kategorii.
 * 
 * FAZA A: Spatie Setup + Middleware - Category Management Policy
 * 
 * Kategorie w PPM:
 * - 5 poziomów zagnieżdżenia (Kategoria → Kategoria4)  
 * - Multi-store support (różne kategorie per sklep)
 * - Mapowanie do Prestashop categories
 * - Hierarchical structure management
 * 
 * Permissions per Role:
 * - Admin: Wszystko
 * - Manager: CRUD + bulk operations + sync
 * - Editor: View + Edit (no delete, no bulk, no sync)
 * - Others: View only
 */
class CategoryPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        // Wszyscy zalogowani mogą browse categories
        $canView = $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'viewAny', 'Category', $canView);
        return $canView;
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, $category = null): bool
    {
        // Wszyscy zalogowani mogą view categories
        $canView = $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'view', "Category:{$category->id}", $canView);
        }
        
        return $canView;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        // Manager+ mogą tworzyć kategorie
        $canCreate = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'create', 'Category', $canCreate);
        return $canCreate;
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, $category = null): bool
    {
        // Editor+ mogą edytować kategorie
        $canUpdate = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'update', "Category:{$category->id}", $canUpdate);
        }
        
        return $canUpdate;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, $category = null): bool
    {
        // Tylko Manager+ mogą usuwać kategorie
        $canDelete = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'delete', "Category:{$category->id}", $canDelete);
        }
        
        return $canDelete;
    }

    /**
     * Determine whether the user can restore the category.
     */
    public function restore(User $user, $category = null): bool
    {
        $canRestore = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'restore', "Category:{$category->id}", $canRestore);
        }
        
        return $canRestore;
    }

    /**
     * Determine whether the user can permanently delete the category.
     */
    public function forceDelete(User $user, $category = null): bool
    {
        // Permanent delete - tylko Admin
        $canForceDelete = $this->canAccessAdmin($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'forceDelete', "Category:{$category->id}", $canForceDelete);
        }
        
        return $canForceDelete;
    }

    /**
     * Determine whether the user can manage category hierarchy.
     */
    public function manageHierarchy(User $user, $category = null): bool
    {
        // Manager+ mogą zarządzać hierarchią (parent/child relationships)
        $canManageHierarchy = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'manageHierarchy', "Category:{$category->id}", $canManageHierarchy);
        }
        
        return $canManageHierarchy;
    }

    /**
     * Determine whether the user can move categories.
     */
    public function moveCategory(User $user, $category = null): bool
    {
        // Manager+ mogą przenosić kategorie between levels
        $canMove = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'moveCategory', "Category:{$category->id}", $canMove);
        }
        
        return $canMove;
    }

    /**
     * Determine whether the user can sync category to Prestashop.
     */
    public function syncToPrestashop(User $user, $category = null): bool
    {
        // Manager+ mogą sync categories do Prestashop
        $canSync = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'syncToPrestashop', "Category:{$category->id}", $canSync);
        }
        
        return $canSync;
    }

    /**
     * Determine whether the user can manage category mappings.
     */
    public function manageMappings(User $user, $category = null): bool
    {
        // Manager+ mogą zarządzać mapowaniem kategorii do sklepów
        $canManageMappings = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'manageMappings', "Category:{$category->id}", $canManageMappings);
        }
        
        return $canManageMappings;
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): bool
    {
        $canBulk = $this->canBulkAction($user);
        $this->logAuthAttempt($user, 'bulkOperations', 'Category', $canBulk);
        return $canBulk;
    }

    /**
     * Determine whether the user can export categories.
     */
    public function export(User $user): bool
    {
        // Editor+ mogą eksportować kategorie
        $canExport = $this->canExport($user);
        $this->logAuthAttempt($user, 'export', 'Category', $canExport);
        return $canExport;
    }

    /**
     * Determine whether the user can import categories.
     */
    public function import(User $user): bool
    {
        // Manager+ mogą importować kategorie
        $canImport = $this->canImport($user);
        $this->logAuthAttempt($user, 'import', 'Category', $canImport);
        return $canImport;
    }

    /**
     * Determine whether the user can create subcategories.
     */
    public function createSubcategory(User $user, $parentCategory = null): bool
    {
        // Manager+ mogą tworzyć subcategories
        $canCreateSub = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($parentCategory) {
            $this->logAuthAttempt($user, 'createSubcategory', "Category:{$parentCategory->id}", $canCreateSub);
        }
        
        return $canCreateSub;
    }

    /**
     * Determine whether the user can manage category SEO.
     */
    public function manageSEO(User $user, $category = null): bool
    {
        // Editor+ mogą zarządzać SEO categories (meta descriptions, URLs)
        $canManageSEO = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'manageSEO', "Category:{$category->id}", $canManageSEO);
        }
        
        return $canManageSEO;
    }

    /**
     * Determine whether the user can manage category visibility.
     */
    public function manageVisibility(User $user, $category = null): bool
    {
        // Manager+ mogą zarządzać visibility categories per shop
        $canManageVisibility = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'manageVisibility', "Category:{$category->id}", $canManageVisibility);
        }
        
        return $canManageVisibility;
    }

    /**
     * Determine whether the user can view category statistics.
     */
    public function viewStatistics(User $user, $category = null): bool
    {
        // Editor+ mogą view statistics categories
        $canViewStats = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'viewStatistics', "Category:{$category->id}", $canViewStats);
        }
        
        return $canViewStats;
    }

    /**
     * Determine whether the user can assign products to categories.
     */
    public function assignProducts(User $user, $category = null): bool
    {
        // Editor+ mogą przypisywać produkty do kategorii
        $canAssignProducts = $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
        
        if ($category) {
            $this->logAuthAttempt($user, 'assignProducts', "Category:{$category->id}", $canAssignProducts);
        }
        
        return $canAssignProducts;
    }
}