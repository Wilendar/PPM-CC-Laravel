<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * PPM Base Policy
 * 
 * Podstawowa klasa policy dla wszystkich policies w systemie PPM.
 * Implementuje common authorization logic z respektem dla hierarchii ról.
 * 
 * FAZA A: Spatie Setup + Middleware - Policy Foundation
 * 
 * Hierarchia ról (od najwyższej):
 * 1. Admin - pełny dostęp do wszystkiego
 * 2. Manager - zarządzanie produktami + eksport + import + ERP
 * 3. Editor - edycja opisów/zdjęć + eksport (bez delete)
 * 4. Warehouseman - panel dostaw (bez rezerwacji)
 * 5. Salesperson - rezerwacje z kontenera (bez cen zakupu)
 * 6. Claims - panel reklamacji
 * 7. User - odczyt + wyszukiwarka
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Admin bypass - Admin zawsze ma dostęp do wszystkiego.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    /**
     * Sprawdź czy użytkownik ma wymaganą rolę lub wyższą.
     */
    protected function hasRoleOrHigher(User $user, string $requiredRole): bool
    {
        $roleHierarchy = [
            'Admin' => 7,
            'Manager' => 6,
            'Editor' => 5, 
            'Warehouseman' => 4,
            'Salesperson' => 3,
            'Claims' => 2,
            'User' => 1
        ];

        $userLevel = $user->getHighestRoleLevel();
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Sprawdź czy użytkownik ma którąkolwiek z wymaganych ról.
     */
    protected function hasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Sprawdź czy użytkownik ma wymaganą permission.
     */
    protected function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    /**
     * Sprawdź czy użytkownik może wykonać action na własnych danych.
     */
    protected function canManageOwn(User $user, $resource): bool
    {
        // Jeśli resource ma user_id, sprawdź ownership
        if (isset($resource->user_id)) {
            return $user->id === $resource->user_id;
        }

        // Jeśli resource jest User, sprawdź czy to ten sam user
        if ($resource instanceof User) {
            return $user->id === $resource->id;
        }

        return false;
    }

    /**
     * Sprawdź czy użytkownik jest aktywny.
     */
    protected function isActiveUser(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Common view authorization - wszyscy zalogowani mogą view (chyba że override).
     */
    protected function canView(User $user): bool
    {
        return $this->isActiveUser($user);
    }

    /**
     * Common create authorization - Editor+ mogą tworzyć.
     */
    protected function canCreate(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
    }

    /**
     * Common update authorization - Editor+ mogą edytować.
     */
    protected function canUpdate(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
    }

    /**
     * Common delete authorization - tylko Manager+ mogą usuwać.
     */
    protected function canDelete(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Bulk operations - tylko Manager+ mogą wykonywać bulk operations.
     */
    protected function canBulkAction(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Export authorization - Editor+ mogą eksportować.
     */
    protected function canExport(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Editor') && $this->isActiveUser($user);
    }

    /**
     * Import authorization - tylko Manager+ mogą importować.
     */
    protected function canImport(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * API access authorization.
     */
    protected function canAccessAPI(User $user): bool
    {
        return $user->canAccessAPI() && $this->isActiveUser($user);
    }

    /**
     * Admin panel access authorization.
     */
    protected function canAccessAdmin(User $user): bool
    {
        return $user->canAccessAdmin() && $this->isActiveUser($user);
    }

    /**
     * Manager panel access authorization.
     */
    protected function canAccessManager(User $user): bool
    {
        return $user->canAccessManager() && $this->isActiveUser($user);
    }

    /**
     * Log authorization attempt dla audit trail.
     */
    protected function logAuthAttempt(User $user, string $ability, string $resource, bool $granted): void
    {
        logger('Authorization attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ability' => $ability,
            'resource' => $resource,
            'granted' => $granted,
            'roles' => $user->getRoleNames(),
            'timestamp' => now()
        ]);
    }
}