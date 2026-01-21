<?php

namespace Database\Seeders;

use App\Models\PasswordPolicy;
use Illuminate\Database\Seeder;

/**
 * ETAP_04 FAZA A: Password Policy Seeder
 *
 * Creates default password policies for the system.
 */
class PasswordPolicySeeder extends Seeder
{
    public function run(): void
    {
        // Standard policy (default)
        PasswordPolicy::updateOrCreate(
            ['name' => 'Standard'],
            [
                'description' => 'Standardowa polityka hasel dla wszystkich uzytkownikow',
                'min_length' => 8,
                'max_length' => 128,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
                'expire_days' => 0, // Never expires
                'warning_days_before_expire' => 7,
                'history_count' => 3,
                'lockout_attempts' => 5,
                'lockout_duration_minutes' => 30,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // High security policy for Admins
        PasswordPolicy::updateOrCreate(
            ['name' => 'Wysokie bezpieczenstwo (Admini)'],
            [
                'description' => 'Polityka wysokiego bezpieczenstwa dla administratorow systemu',
                'min_length' => 12,
                'max_length' => 128,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'expire_days' => 90,
                'warning_days_before_expire' => 14,
                'history_count' => 5,
                'lockout_attempts' => 3,
                'lockout_duration_minutes' => 60,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // Relaxed policy for API/service accounts
        PasswordPolicy::updateOrCreate(
            ['name' => 'Konta serwisowe'],
            [
                'description' => 'Uproszczona polityka dla kont serwisowych i API',
                'min_length' => 16,
                'max_length' => 256,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
                'expire_days' => 0, // Never expires (use API key rotation instead)
                'warning_days_before_expire' => 0,
                'history_count' => 0,
                'lockout_attempts' => 10,
                'lockout_duration_minutes' => 5,
                'is_default' => false,
                'is_active' => true,
            ]
        );
    }
}
