<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM PrestaShop Shop Policy
 *
 * Policy dla zarządzania sklepami PrestaShop.
 */
class PrestaShopShopPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'admin.shops.view');
    }

    public function view(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'admin.shops.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'admin.shops.create');
    }

    public function update(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'admin.shops.edit');
    }

    public function delete(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'admin.shops.delete');
    }

    public function sync(User $user): bool
    {
        return $this->hasPermission($user, 'admin.shops.sync');
    }
}
