<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM ERP Connection Policy
 *
 * Policy dla zarządzania połączeniami ERP (BaseLinker, Subiekt GT, Dynamics).
 */
class ERPConnectionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'integrations.read');
    }

    public function view(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'integrations.read');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'integrations.config');
    }

    public function update(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'integrations.config');
    }

    public function delete(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'integrations.config');
    }

    public function sync(User $user): bool
    {
        return $this->hasPermission($user, 'integrations.sync');
    }
}
