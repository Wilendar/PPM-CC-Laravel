<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Test Manager',
                'email' => 'manager@test.ppm',
                'role' => 'Manager',
            ],
            [
                'name' => 'Test Editor',
                'email' => 'editor@test.ppm',
                'role' => 'Editor',
            ],
            [
                'name' => 'Test Warehouseman',
                'email' => 'warehouse@test.ppm',
                'role' => 'Warehouseman',
            ],
            [
                'name' => 'Test Salesperson',
                'email' => 'sales@test.ppm',
                'role' => 'Salesperson',
            ],
            [
                'name' => 'Test Claims',
                'email' => 'claims@test.ppm',
                'role' => 'Claims',
            ],
            [
                'name' => 'Test User',
                'email' => 'user@test.ppm',
                'role' => 'User',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('Test123!PPM'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Sync role (remove old roles, assign new)
            $user->syncRoles([$account['role']]);

            $this->command->info("Created/updated: {$account['email']} with role {$account['role']}");
        }
    }
}
