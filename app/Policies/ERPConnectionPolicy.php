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
        return $this->hasPermission($user, 'admin.erp.view');
    }

    public function view(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'admin.erp.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'admin.erp.manage');
    }

    public function update(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'admin.erp.manage');
    }

    public function delete(User $user, $connection): bool
    {
        return $this->hasPermission($user, 'admin.erp.manage');
    }

    public function sync(User $user): bool
    {
        return $this->hasPermission($user, 'admin.erp.sync');
    }
}
