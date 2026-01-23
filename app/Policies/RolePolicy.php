<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Role Policy
 *
 * Policy dla Spatie Permission Role model.
 * Tylko Admin i Manager mogą zarządzać rolami.
 */
class RolePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can create roles.
     * Only Admin can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can update the role.
     * Only Admin can update roles.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can delete the role.
     * Only Admin can delete roles.
     */
    public function delete(User $user, Role $role): bool
    {
        // Prevent deleting system roles
        if ($role->is_system ?? false) {
            return false;
        }

        return $user->hasRole('Admin') && $this->isActiveUser($user);
    }
}
