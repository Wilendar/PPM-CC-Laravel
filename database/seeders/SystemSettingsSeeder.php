<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Ustawienia ogólne
        $this->seedGeneralSettings();
        
        // Ustawienia bezpieczeństwa
        $this->seedSecuritySettings();
        
        // Ustawienia produktów
        $this->seedProductSettings();
        
        // Ustawienia email
        $this->seedEmailSettings();
        
        // Ustawienia integracji
        $this->seedIntegrationSettings();
        
        // Ustawienia backupu
        $this->seedBackupSettings();
        
        // Ustawienia UI
        $this->seedUISettings();
    }

    /**
     * Ustawienia ogólne
     */
    private function seedGeneralSettings(): void
    {
        $settings = [
            'company_name' => [
                'value' => 'MPP TRADE',
                'type' => 'string',
                'description' => 'Nazwa firmy wyświetlana w aplikacji'
            ],
            'timezone' => [
                'value' => 'Europe/Warsaw',
                'type' => 'string',
                'description' => 'Domyślna strefa czasowa'
            ],
            'currency' => [
                'value' => 'PLN',
                'type' => 'string',
                'description' => 'Domyślna waluta'
            ],
            'language' => [
                'value' => 'pl',
                'type' => 'string',
                'description' => 'Domyślny język aplikacji'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'general',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia bezpieczeństwa
     */
    private function seedSecuritySettings(): void
    {
        $settings = [
            'password_min_length' => [
                'value' => 8,
                'type' => 'integer',
                'description' => 'Minimalna długość hasła'
            ],
            'password_require_uppercase' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Wymagaj wielkich liter w haśle'
            ],
            'password_require_numbers' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Wymagaj cyfr w haśle'
            ],
            'password_require_symbols' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Wymagaj symboli w haśle'
            ],
            'session_timeout' => [
                'value' => 7200,
                'type' => 'integer',
                'description' => 'Czas wygaśnięcia sesji w sekundach'
            ],
            'max_login_attempts' => [
                'value' => 5,
                'type' => 'integer',
                'description' => 'Maksymalna liczba prób logowania'
            ],
            'lockout_duration' => [
                'value' => 300,
                'type' => 'integer',
                'description' => 'Czas blokady po przekroczeniu limitu prób'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'security',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia produktów
     */
    private function seedProductSettings(): void
    {
        $settings = [
            'default_tax_rate' => [
                'value' => 23,
                'type' => 'integer',
                'description' => 'Domyślna stawka VAT (%)'
            ],
            'sku_generation_pattern' => [
                'value' => 'AUTO-{number}',
                'type' => 'string',
                'description' => 'Szablon generowania SKU'
            ],
            'image_max_size_mb' => [
                'value' => 10,
                'type' => 'integer',
                'description' => 'Maksymalny rozmiar zdjęcia w MB'
            ],
            'image_max_count' => [
                'value' => 20,
                'type' => 'integer',
                'description' => 'Maksymalna liczba zdjęć na produkt'
            ],
            'max_category_depth' => [
                'value' => 5,
                'type' => 'integer',
                'description' => 'Maksymalna głębokość kategorii'
            ],
            'auto_categorization' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Automatyczne przypisywanie kategorii'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'product',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia email
     */
    private function seedEmailSettings(): void
    {
        $settings = [
            'smtp_host' => [
                'value' => null,
                'type' => 'string',
                'description' => 'Serwer SMTP'
            ],
            'smtp_port' => [
                'value' => 587,
                'type' => 'integer',
                'description' => 'Port SMTP'
            ],
            'smtp_username' => [
                'value' => null,
                'type' => 'string',
                'description' => 'Nazwa użytkownika SMTP'
            ],
            'smtp_password' => [
                'value' => null,
                'type' => 'string',
                'description' => 'Hasło SMTP'
            ],
            'from_email' => [
                'value' => 'noreply@mpptrade.pl',
                'type' => 'email',
                'description' => 'Adres nadawcy'
            ],
            'from_name' => [
                'value' => 'PPM System',
                'type' => 'string',
                'description' => 'Nazwa nadawcy'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'email',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia integracji
     */
    private function seedIntegrationSettings(): void
    {
        $settings = [
            'sync_frequency' => [
                'value' => 'hourly',
                'type' => 'string',
                'description' => 'Częstotliwość synchronizacji'
            ],
            'conflict_resolution' => [
                'value' => 'manual',
                'type' => 'string',
                'description' => 'Sposób rozwiązywania konfliktów'
            ],
            'auto_retry_failed' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Automatyczne ponawianie nieudanych synchronizacji'
            ],
            'max_retry_attempts' => [
                'value' => 3,
                'type' => 'integer',
                'description' => 'Maksymalna liczba prób ponowień'
            ],
            'backup_before_sync' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Tworzenie backupu przed synchronizacją'
            ],
            'webhook_enabled' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Włączenie webhooków'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'integration',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia backupu
     */
    private function seedBackupSettings(): void
    {
        $settings = [
            'backup_frequency' => [
                'value' => 'daily',
                'type' => 'string',
                'description' => 'Częstotliwość automatycznych backupów'
            ],
            'backup_retention_days' => [
                'value' => 30,
                'type' => 'integer',
                'description' => 'Okres przechowywania backupów w dniach'
            ],
            'backup_compress' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Kompresja backupów'
            ],
            'backup_encrypt' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Szyfrowanie backupów'
            ],
            'backup_include_logs' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Uwzględnienie plików logów w backupie'
            ],
            'backup_auto_cleanup' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Automatyczne usuwanie starych backupów'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'backup',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }

    /**
     * Ustawienia UI
     */
    private function seedUISettings(): void
    {
        $settings = [
            'default_theme' => [
                'value' => 'light',
                'type' => 'string',
                'description' => 'Domyślny motyw interfejsu'
            ],
            'items_per_page' => [
                'value' => 25,
                'type' => 'integer',
                'description' => 'Liczba elementów na stronę'
            ],
            'dashboard_refresh_interval' => [
                'value' => 30,
                'type' => 'integer',
                'description' => 'Interwał odświeżania dashboard w sekundach'
            ],
        ];

        foreach ($settings as $key => $config) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'category' => 'ui',
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                ]
            );
        }
    }
}