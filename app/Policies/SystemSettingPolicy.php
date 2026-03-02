<?php

namespace App\Policies;

use App\Models\User;

/**
 * PPM System Setting Policy
 *
 * Policy dla zarządzania ustawieniami systemowymi.
 * Tylko Admin ma dostęp do system settings.
 */
class SystemSettingPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'admin.settings.manage');
    }

    public function view(User $user, $setting): bool
    {
        return $this->hasPermission($user, 'admin.settings.manage');
    }

    public function update(User $user, $setting): bool
    {
        return $this->hasPermission($user, 'admin.settings.manage');
    }
}
