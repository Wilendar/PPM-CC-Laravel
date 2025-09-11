<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM User Policy
 * 
 * Policy dla zarządzania użytkownikami w systemie PPM.
 * Implementuje authorization logic dla user management operations.
 * 
 * FAZA A: Spatie Setup + Middleware - User Management Policy
 * 
 * Permissions:
 * - viewAny: Zobacz listę użytkowników (Admin only)
 * - view: Zobacz konkretnego użytkownika (Admin + own profile)
 * - create: Tworzenie nowych użytkowników (Admin only)
 * - update: Edycja użytkowników (Admin + own profile partial)
 * - delete: Usuwanie użytkowników (Admin only)
 * - restore: Przywracanie użytkowników (Admin only) - future soft deletes
 * - forceDelete: Permanent deletion (Admin only) - future soft deletes
 * - assignRole: Przypisywanie ról (Admin only)
 * - removeRole: Usuwanie ról (Admin only)
 */
class UserPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $canView = $this->canAccessAdmin($user);
        $this->logAuthAttempt($user, 'viewAny', 'User', $canView);
        return $canView;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admin może view wszystkich
        // Wszyscy mogą view swój własny profil
        $canView = $this->canAccessAdmin($user) || $this->canManageOwn($user, $model);
        
        $this->logAuthAttempt($user, 'view', "User:{$model->id}", $canView);
        return $canView;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $canCreate = $this->canAccessAdmin($user);
        $this->logAuthAttempt($user, 'create', 'User', $canCreate);
        return $canCreate;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin może update wszystkich
        // Użytkownicy mogą update swój własny profil (ograniczone pola)
        $canUpdate = $this->canAccessAdmin($user) || $this->canManageOwn($user, $model);
        
        $this->logAuthAttempt($user, 'update', "User:{$model->id}", $canUpdate);
        return $canUpdate;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Tylko Admin może usuwać użytkowników
        // Nie można usunąć samego siebie
        $canDelete = $this->canAccessAdmin($user) && $user->id !== $model->id;
        
        $this->logAuthAttempt($user, 'delete', "User:{$model->id}", $canDelete);
        return $canDelete;
    }

    /**
     * Determine whether the user can restore the model.
     * Future implementation - soft deletes
     */
    public function restore(User $user, User $model): bool
    {
        $canRestore = $this->canAccessAdmin($user);
        $this->logAuthAttempt($user, 'restore', "User:{$model->id}", $canRestore);
        return $canRestore;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Future implementation - soft deletes
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Permanent delete - tylko Admin i nie samego siebie
        $canForceDelete = $this->canAccessAdmin($user) && $user->id !== $model->id;
        
        $this->logAuthAttempt($user, 'forceDelete', "User:{$model->id}", $canForceDelete);
        return $canForceDelete;
    }

    /**
     * Determine whether the user can assign roles to the model.
     */
    public function assignRole(User $user, User $model): bool
    {
        // Tylko Admin może przypisywać role
        // Nie można zmieniać ról samemu sobie (security)
        $canAssignRole = $this->canAccessAdmin($user) && $user->id !== $model->id;
        
        $this->logAuthAttempt($user, 'assignRole', "User:{$model->id}", $canAssignRole);
        return $canAssignRole;
    }

    /**
     * Determine whether the user can remove roles from the model.
     */
    public function removeRole(User $user, User $model): bool
    {
        // Tylko Admin może usuwać role
        // Nie można usuwać ról samemu sobie (security)
        $canRemoveRole = $this->canAccessAdmin($user) && $user->id !== $model->id;
        
        $this->logAuthAttempt($user, 'removeRole', "User:{$model->id}", $canRemoveRole);
        return $canRemoveRole;
    }

    /**
     * Determine whether the user can update profile fields.
     * Różne poziomy dostępu do różnych pól profilu.
     */
    public function updateProfile(User $user, User $model, array $fields = []): bool
    {
        // Admin może edytować wszystkie pola wszystkich użytkowników
        if ($this->canAccessAdmin($user)) {
            return true;
        }

        // Użytkownik może edytować swój własny profil
        if ($this->canManageOwn($user, $model)) {
            // Sprawdź czy nie próbuje edytować restricted fields
            $restrictedFields = ['is_active', 'email_verified_at'];
            $hasRestrictedFields = !empty(array_intersect($fields, $restrictedFields));
            
            $canUpdate = !$hasRestrictedFields;
            $this->logAuthAttempt($user, 'updateProfile', "User:{$model->id}", $canUpdate);
            return $canUpdate;
        }

        return false;
    }

    /**
     * Determine whether the user can impersonate the model.
     * Admin feature dla debugging/support.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Tylko Admin może impersonate
        // Nie można impersonate samego siebie
        $canImpersonate = $this->canAccessAdmin($user) && $user->id !== $model->id;
        
        $this->logAuthAttempt($user, 'impersonate', "User:{$model->id}", $canImpersonate);
        return $canImpersonate;
    }

    /**
     * Determine whether the user can view audit logs for the model.
     */
    public function viewAuditLogs(User $user, User $model): bool
    {
        // Admin może view wszystkie audit logi
        // Użytkownicy mogą view swoje własne audit logi
        $canViewAuditLogs = $this->canAccessAdmin($user) || $this->canManageOwn($user, $model);
        
        $this->logAuthAttempt($user, 'viewAuditLogs', "User:{$model->id}", $canViewAuditLogs);
        return $canViewAuditLogs;
    }

    /**
     * Determine whether the user can export user data.
     */
    public function exportUsers(User $user): bool
    {
        $canExport = $this->canAccessAdmin($user);
        $this->logAuthAttempt($user, 'exportUsers', 'User', $canExport);
        return $canExport;
    }

    /**
     * Determine whether the user can import user data.
     */
    public function importUsers(User $user): bool
    {
        $canImport = $this->canAccessAdmin($user);
        $this->logAuthAttempt($user, 'importUsers', 'User', $canImport);
        return $canImport;
    }
}