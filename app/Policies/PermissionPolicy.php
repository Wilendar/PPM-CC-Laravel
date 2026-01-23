<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Permission Policy
 *
 * Policy dla Spatie Permission model.
 * Tylko Admin może zarządzać uprawnieniami.
 */
class PermissionPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can manage permissions.
     * This is used by PermissionMatrix.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can create permissions.
     * Only Admin can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can update the permission.
     * Only Admin can update permissions.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can delete the permission.
     * Only Admin can delete permissions.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }
}
