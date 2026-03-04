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
        return $this->hasPermission($user, 'shops.read');
    }

    public function view(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'shops.read');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'shops.create');
    }

    public function update(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'shops.update');
    }

    public function delete(User $user, $shop): bool
    {
        return $this->hasPermission($user, 'shops.delete');
    }

    public function sync(User $user): bool
    {
        return $this->hasPermission($user, 'shops.sync');
    }
}
