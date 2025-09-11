<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * FAZA D: Integration & System Tables
     * 5.1.1 & 5.1.2 Users + Roles seeder
     * 
     * Tworzy użytkowników testowych dla każdej roli PPM:
     * - Admin production user (admin@ppm.mpptrade.pl)
     * - 7 użytkowników testowych dla każdej roli
     * - Kompletne dane profilu z preferencjami
     * - Przypisanie ról i uprawnień
     */
    public function run(): void
    {
        // === ADMIN PRODUCTION USER ===
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@ppm.mpptrade.pl'],
            [
                'name' => 'PPM Administrator',
                'first_name' => 'PPM',
                'last_name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make('PPM_Admin_2024!'),
                'phone' => '+48123456789',
                'company' => 'MPP TRADE',
                'position' => 'System Administrator',
                'is_active' => true,
                'preferred_language' => 'pl',
                'timezone' => 'Europe/Warsaw',
                'date_format' => 'Y-m-d',
                'ui_preferences' => json_encode([
                    'theme' => 'light',
                    'sidebar_collapsed' => false,
                    'products_per_page' => 50,
                    'default_price_group' => 'retail',
                    'default_warehouse' => 'mpptrade'
                ]),
                'notification_settings' => json_encode([
                    'email_notifications' => true,
                    'sync_notifications' => true,
                    'stock_alerts' => true,
                    'import_notifications' => true
                ])
            ]
        );
        
        // Przypisz rolę Admin
        $adminUser->assignRole('Admin');
        $this->command->info("✅ Admin user: {$adminUser->email} (password: PPM_Admin_2024!)");
        
        // === TEST USERS DLA KAŻDEJ ROLI ===
        $testUsers = [
            [
                'email' => 'manager@ppm.mpptrade.pl',
                'name' => 'Jan Kowalski',
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'role' => 'Manager',
                'position' => 'Product Manager',
                'password' => 'Manager_2024!'
            ],
            [
                'email' => 'editor@ppm.mpptrade.pl', 
                'name' => 'Anna Nowak',
                'first_name' => 'Anna',
                'last_name' => 'Nowak',
                'role' => 'Editor',
                'position' => 'Content Editor',
                'password' => 'Editor_2024!'
            ],
            [
                'email' => 'warehouse@ppm.mpptrade.pl',
                'name' => 'Piotr Magazynier',
                'first_name' => 'Piotr',
                'last_name' => 'Magazynier',
                'role' => 'Warehouseman',
                'position' => 'Warehouse Manager',
                'password' => 'Warehouse_2024!'
            ],
            [
                'email' => 'sales@ppm.mpptrade.pl',
                'name' => 'Maria Sprzedawca',
                'first_name' => 'Maria',
                'last_name' => 'Sprzedawca',
                'role' => 'Salesperson',
                'position' => 'Sales Representative',
                'password' => 'Sales_2024!'
            ],
            [
                'email' => 'claims@ppm.mpptrade.pl',
                'name' => 'Tomasz Reklamacja',
                'first_name' => 'Tomasz',
                'last_name' => 'Reklamacja',
                'role' => 'Claims',
                'position' => 'Claims Specialist',
                'password' => 'Claims_2024!'
            ],
            [
                'email' => 'user@ppm.mpptrade.pl',
                'name' => 'Jan Użytkownik',
                'first_name' => 'Jan',
                'last_name' => 'Użytkownik', 
                'role' => 'User',
                'position' => 'Regular User',
                'password' => 'User_2024!'
            ]
        ];
        
        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($userData['password']),
                    'phone' => '+48' . rand(100000000, 999999999),
                    'company' => 'MPP TRADE',
                    'position' => $userData['position'],
                    'is_active' => true,
                    'preferred_language' => 'pl',
                    'timezone' => 'Europe/Warsaw',
                    'date_format' => 'Y-m-d',
                    'ui_preferences' => json_encode($this->getDefaultUIPreferences($userData['role'])),
                    'notification_settings' => json_encode($this->getDefaultNotificationSettings($userData['role']))
                ]
            );
            
            // Przypisz odpowiednią rolę
            $user->assignRole($userData['role']);
            
            $this->command->info("✅ {$userData['role']} user: {$user->email} (password: {$userData['password']})");
        }
        
        $this->command->info('✅ Utworzono ' . (count($testUsers) + 1) . ' użytkowników testowych');
    }
    
    /**
     * Domyślne preferencje UI dla różnych ról
     */
    private function getDefaultUIPreferences(string $role): array
    {
        $basePreferences = [
            'theme' => 'light',
            'sidebar_collapsed' => false,
            'products_per_page' => 25,
            'default_price_group' => 'retail',
            'default_warehouse' => 'mpptrade'
        ];
        
        switch ($role) {
            case 'Manager':
                return array_merge($basePreferences, [
                    'products_per_page' => 50,
                    'show_cost_prices' => true,
                    'default_price_group' => 'dealer_std'
                ]);
                
            case 'Editor':
                return array_merge($basePreferences, [
                    'products_per_page' => 25,
                    'show_image_tools' => true,
                    'auto_save_content' => true
                ]);
                
            case 'Warehouseman':
                return array_merge($basePreferences, [
                    'default_view' => 'stock',
                    'show_locations' => true,
                    'products_per_page' => 100
                ]);
                
            case 'Salesperson':
                return array_merge($basePreferences, [
                    'show_reservation_tools' => true,
                    'default_price_group' => 'dealer_std',
                    'hide_cost_prices' => true
                ]);
                
            case 'Claims':
                return array_merge($basePreferences, [
                    'default_view' => 'claims',
                    'show_order_history' => true
                ]);
                
            default:
                return $basePreferences;
        }
    }
    
    /**
     * Domyślne ustawienia powiadomień dla różnych ról
     */
    private function getDefaultNotificationSettings(string $role): array
    {
        $baseSettings = [
            'email_notifications' => false,
            'sync_notifications' => false,
            'stock_alerts' => false,
            'import_notifications' => false
        ];
        
        switch ($role) {
            case 'Manager':
                return [
                    'email_notifications' => true,
                    'sync_notifications' => true,
                    'stock_alerts' => true,
                    'import_notifications' => true,
                    'price_change_alerts' => true
                ];
                
            case 'Warehouseman':
                return array_merge($baseSettings, [
                    'stock_alerts' => true,
                    'delivery_notifications' => true
                ]);
                
            case 'Salesperson':
                return array_merge($baseSettings, [
                    'order_notifications' => true,
                    'reservation_alerts' => true
                ]);
                
            case 'Claims':
                return array_merge($baseSettings, [
                    'claim_notifications' => true,
                    'email_notifications' => true
                ]);
                
            default:
                return $baseSettings;
        }
    }
}