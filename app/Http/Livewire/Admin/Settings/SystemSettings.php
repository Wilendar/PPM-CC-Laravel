<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\SystemSetting;
use App\Services\SettingsService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SystemSettings extends Component
{
    use WithFileUploads, AuthorizesRequests;

    /**
     * Aktualna kategoria
     */
    public $activeCategory = 'general';

    /**
     * Ustawienia według kategorii
     */
    public $settings = [];

    /**
     * Temporary values dla formularzy
     */
    public $tempValues = [];

    /**
     * Upload files
     */
    public $uploadFiles = [];

    /**
     * Status i komunikaty
     */
    public $isLoading = false;
    public $message = '';
    public $messageType = '';

    /**
     * Lista kategorii
     */
    protected $categories;

    /**
     * SettingsService
     */
    protected $settingsService;

    public function boot(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function mount()
    {
        $this->authorize('admin.settings.manage');
        $this->categories = SystemSetting::getCategories();
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.admin.settings.system-settings', [
            'categories' => $this->categories,
            'categorySettings' => $this->settings[$this->activeCategory] ?? [],
        ])->layout('layouts.admin');
    }

    /**
     * Zmiana aktywnej kategorii
     */
    public function switchCategory($category)
    {
        if (array_key_exists($category, $this->categories)) {
            $this->activeCategory = $category;
            $this->resetMessages();
        }
    }

    /**
     * Załaduj ustawienia
     */
    public function loadSettings()
    {
        $this->isLoading = true;

        foreach ($this->categories as $category => $label) {
            $this->settings[$category] = $this->getSettingsForCategory($category);
        }

        $this->isLoading = false;
    }

    /**
     * Pobierz ustawienia dla kategorii
     */
    private function getSettingsForCategory($category)
    {
        switch ($category) {
            case 'general':
                return $this->getGeneralSettings();
            case 'security':
                return $this->getSecuritySettings();
            case 'product':
                return $this->getProductSettings();
            case 'email':
                return $this->getEmailSettings();
            case 'integration':
                return $this->getIntegrationSettings();
            case 'backup':
                return $this->getBackupSettings();
            case 'ui':
                return $this->getUISettings();
            default:
                return [];
        }
    }

    /**
     * Ustawienia ogólne
     */
    private function getGeneralSettings()
    {
        return [
            'company_name' => [
                'label' => 'Nazwa firmy',
                'type' => 'string',
                'value' => $this->settingsService->get('company_name', 'MPP TRADE'),
                'description' => 'Nazwa firmy wyświetlana w aplikacji',
                'required' => true,
            ],
            'company_logo' => [
                'label' => 'Logo firmy',
                'type' => 'file',
                'value' => $this->settingsService->getFileUrl('company_logo'),
                'description' => 'Logo firmy (zalecane 200x80px, PNG/JPG)',
                'accept' => 'image/*',
            ],
            'timezone' => [
                'label' => 'Strefa czasowa',
                'type' => 'select',
                'value' => $this->settingsService->get('timezone', 'Europe/Warsaw'),
                'options' => [
                    'Europe/Warsaw' => 'Europa/Warszawa',
                    'Europe/London' => 'Europa/Londyn',
                    'America/New_York' => 'Ameryka/Nowy Jork',
                    'Asia/Tokyo' => 'Azja/Tokio',
                ],
                'required' => true,
            ],
            'currency' => [
                'label' => 'Waluta domyślna',
                'type' => 'select',
                'value' => $this->settingsService->get('currency', 'PLN'),
                'options' => [
                    'PLN' => 'Polski złoty (PLN)',
                    'EUR' => 'Euro (EUR)',
                    'USD' => 'Dolar amerykański (USD)',
                    'GBP' => 'Funt brytyjski (GBP)',
                ],
                'required' => true,
            ],
            'language' => [
                'label' => 'Język domyślny',
                'type' => 'select',
                'value' => $this->settingsService->get('language', 'pl'),
                'options' => [
                    'pl' => 'Polski',
                    'en' => 'English',
                ],
                'required' => true,
            ],
        ];
    }

    /**
     * Ustawienia bezpieczeństwa
     */
    private function getSecuritySettings()
    {
        return [
            'password_min_length' => [
                'label' => 'Minimalna długość hasła',
                'type' => 'integer',
                'value' => $this->settingsService->get('password_min_length', 8),
                'description' => 'Minimalna liczba znaków w haśle',
                'min' => 6,
                'max' => 32,
                'required' => true,
            ],
            'password_require_uppercase' => [
                'label' => 'Wymagaj wielkich liter',
                'type' => 'boolean',
                'value' => $this->settingsService->get('password_require_uppercase', true),
                'description' => 'Hasło musi zawierać przynajmniej jedną wielką literę',
            ],
            'password_require_numbers' => [
                'label' => 'Wymagaj cyfr',
                'type' => 'boolean',
                'value' => $this->settingsService->get('password_require_numbers', true),
                'description' => 'Hasło musi zawierać przynajmniej jedną cyfrę',
            ],
            'password_require_symbols' => [
                'label' => 'Wymagaj symboli',
                'type' => 'boolean',
                'value' => $this->settingsService->get('password_require_symbols', false),
                'description' => 'Hasło musi zawierać przynajmniej jeden symbol',
            ],
            'session_timeout' => [
                'label' => 'Timeout sesji (sekundy)',
                'type' => 'integer',
                'value' => $this->settingsService->get('session_timeout', 7200),
                'description' => 'Czas po którym sesja wygasa (domyślnie 2h)',
                'min' => 300,
                'max' => 86400,
            ],
            'max_login_attempts' => [
                'label' => 'Max prób logowania',
                'type' => 'integer',
                'value' => $this->settingsService->get('max_login_attempts', 5),
                'description' => 'Maksymalna liczba nieudanych prób logowania',
                'min' => 3,
                'max' => 20,
            ],
            'lockout_duration' => [
                'label' => 'Czas blokady (sekundy)',
                'type' => 'integer',
                'value' => $this->settingsService->get('lockout_duration', 300),
                'description' => 'Czas blokady po przekroczeniu limitu prób',
                'min' => 60,
                'max' => 3600,
            ],
            'dev_auth_bypass' => [
                'label' => '⚠️ DEV MODE: Wyłącz autoryzację',
                'type' => 'boolean',
                'value' => $this->settingsService->get('dev_auth_bypass', false),
                'description' => 'UWAGA: Włączenie pozwala na dostęp do panelu admina bez logowania. NIGDY nie włączaj na produkcji!',
            ],
        ];
    }

    /**
     * Ustawienia produktów
     */
    private function getProductSettings()
    {
        return [
            'default_tax_rate' => [
                'label' => 'Domyślna stawka VAT (%)',
                'type' => 'integer',
                'value' => $this->settingsService->get('default_tax_rate', 23),
                'min' => 0,
                'max' => 100,
            ],
            'sku_generation_pattern' => [
                'label' => 'Szablon generowania SKU',
                'type' => 'string',
                'value' => $this->settingsService->get('sku_generation_pattern', 'AUTO-{number}'),
                'description' => 'Użyj {number} dla numeru sekwencyjnego',
            ],
            'image_max_size_mb' => [
                'label' => 'Max rozmiar zdjęcia (MB)',
                'type' => 'integer',
                'value' => $this->settingsService->get('image_max_size_mb', 10),
                'min' => 1,
                'max' => 50,
            ],
            'image_max_count' => [
                'label' => 'Max liczba zdjęć na produkt',
                'type' => 'integer',
                'value' => $this->settingsService->get('image_max_count', 20),
                'min' => 1,
                'max' => 50,
            ],
            'max_category_depth' => [
                'label' => 'Max głębokość kategorii',
                'type' => 'integer',
                'value' => $this->settingsService->get('max_category_depth', 5),
                'min' => 3,
                'max' => 10,
            ],
            'auto_categorization' => [
                'label' => 'Automatyczna kategoryzacja',
                'type' => 'boolean',
                'value' => $this->settingsService->get('auto_categorization', false),
                'description' => 'Automatyczne przypisywanie kategorii na podstawie nazwy',
            ],
        ];
    }

    /**
     * Ustawienia email
     */
    private function getEmailSettings()
    {
        return [
            'smtp_host' => [
                'label' => 'Serwer SMTP',
                'type' => 'string',
                'value' => $this->settingsService->get('smtp_host'),
                'placeholder' => 'smtp.gmail.com',
            ],
            'smtp_port' => [
                'label' => 'Port SMTP',
                'type' => 'integer',
                'value' => $this->settingsService->get('smtp_port', 587),
                'min' => 1,
                'max' => 65535,
            ],
            'smtp_username' => [
                'label' => 'Nazwa użytkownika',
                'type' => 'string',
                'value' => $this->settingsService->get('smtp_username'),
                'placeholder' => 'twoj-email@domain.com',
            ],
            'smtp_password' => [
                'label' => 'Hasło SMTP',
                'type' => 'password',
                'value' => $this->settingsService->get('smtp_password') ? '********' : '',
                'description' => 'Pozostaw puste jeśli nie chcesz zmieniać',
            ],
            'from_email' => [
                'label' => 'Adres nadawcy',
                'type' => 'email',
                'value' => $this->settingsService->get('from_email'),
                'placeholder' => 'noreply@domain.com',
            ],
            'from_name' => [
                'label' => 'Nazwa nadawcy',
                'type' => 'string',
                'value' => $this->settingsService->get('from_name'),
                'placeholder' => 'PPM System',
            ],
        ];
    }

    /**
     * Ustawienia integracji
     */
    private function getIntegrationSettings()
    {
        return [
            'sync_frequency' => [
                'label' => 'Częstotliwość synchronizacji',
                'type' => 'select',
                'value' => $this->settingsService->get('sync_frequency', 'hourly'),
                'options' => [
                    'real_time' => 'W czasie rzeczywistym',
                    'every_15min' => 'Co 15 minut',
                    'hourly' => 'Co godzinę',
                    'daily' => 'Raz dziennie',
                    'manual' => 'Tylko ręczne',
                ],
            ],
            'conflict_resolution' => [
                'label' => 'Rozwiązywanie konfliktów',
                'type' => 'select',
                'value' => $this->settingsService->get('conflict_resolution', 'manual'),
                'options' => [
                    'manual' => 'Ręczne rozwiązywanie',
                    'ppm_wins' => 'PPM ma priorytet',
                    'external_wins' => 'System zewnętrzny ma priorytet',
                    'newest_wins' => 'Nowsza wersja ma priorytet',
                ],
            ],
            'auto_retry_failed' => [
                'label' => 'Automatyczne ponawianie',
                'type' => 'boolean',
                'value' => $this->settingsService->get('auto_retry_failed', true),
                'description' => 'Automatycznie ponów nieudane synchronizacje',
            ],
            'max_retry_attempts' => [
                'label' => 'Max prób ponowień',
                'type' => 'integer',
                'value' => $this->settingsService->get('max_retry_attempts', 3),
                'min' => 1,
                'max' => 10,
            ],
        ];
    }

    /**
     * Ustawienia backup
     */
    private function getBackupSettings()
    {
        return [
            'backup_frequency' => [
                'label' => 'Częstotliwość backupu',
                'type' => 'select',
                'value' => $this->settingsService->get('backup_frequency', 'daily'),
                'options' => [
                    'daily' => 'Codziennie',
                    'weekly' => 'Tygodniowo',
                    'monthly' => 'Miesięcznie',
                    'manual' => 'Tylko ręczne',
                ],
            ],
            'backup_retention_days' => [
                'label' => 'Przechowywanie backupów (dni)',
                'type' => 'integer',
                'value' => $this->settingsService->get('backup_retention_days', 30),
                'min' => 7,
                'max' => 365,
            ],
            'backup_compress' => [
                'label' => 'Kompresja backupów',
                'type' => 'boolean',
                'value' => $this->settingsService->get('backup_compress', true),
                'description' => 'Kompresuj backupy aby zaoszczędzić miejsce',
            ],
        ];
    }

    /**
     * Ustawienia UI
     */
    private function getUISettings()
    {
        return [
            'default_theme' => [
                'label' => 'Domyślny motyw',
                'type' => 'select',
                'value' => $this->settingsService->get('default_theme', 'light'),
                'options' => [
                    'light' => 'Jasny',
                    'dark' => 'Ciemny',
                    'auto' => 'Automatyczny',
                ],
            ],
            'items_per_page' => [
                'label' => 'Elementów na stronę',
                'type' => 'select',
                'value' => $this->settingsService->get('items_per_page', 25),
                'options' => [
                    10 => '10',
                    25 => '25', 
                    50 => '50',
                    100 => '100',
                ],
            ],
            'dashboard_refresh_interval' => [
                'label' => 'Odświeżanie dashboard (sekundy)',
                'type' => 'select',
                'value' => $this->settingsService->get('dashboard_refresh_interval', 30),
                'options' => [
                    0 => 'Wyłączone',
                    30 => '30 sekund',
                    60 => '1 minuta',
                    300 => '5 minut',
                ],
            ],
        ];
    }

    /**
     * Zapisz ustawienia
     */
    public function saveSettings()
    {
        try {
            $this->isLoading = true;
            
            $categorySettings = $this->settings[$this->activeCategory] ?? [];
            $rules = $this->buildValidationRules($categorySettings);
            
            // Walidacja
            $validator = Validator::make($this->tempValues, $rules);
            if ($validator->fails()) {
                $this->showMessage('Błędy walidacji: ' . $validator->errors()->first(), 'error');
                return;
            }

            // Zapisz ustawienia
            foreach ($this->tempValues as $key => $value) {
                if ($value !== null && $value !== '') {
                    // Specjalna obsługa hasła SMTP
                    if ($key === 'smtp_password' && $value === '********') {
                        continue; // Nie zmieniaj hasła jeśli nie wprowadzono nowego
                    }
                    
                    $settingConfig = $categorySettings[$key] ?? [];
                    $type = $settingConfig['type'] ?? 'string';
                    
                    $this->settingsService->set($key, $value, $this->activeCategory, $type);
                }
            }

            // Obsługa uploadów plików
            foreach ($this->uploadFiles as $key => $file) {
                if ($file) {
                    $this->settingsService->handleFileUpload($key, $file);
                }
            }

            $this->showMessage('Ustawienia zostały zapisane pomyślnie', 'success');
            $this->loadSettings(); // Odśwież dane
            $this->tempValues = []; // Wyczyść tymczasowe wartości
            $this->uploadFiles = [];

        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas zapisywania: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Resetuj ustawienia kategorii do domyślnych
     */
    public function resetCategoryToDefaults()
    {
        try {
            $this->settingsService->resetToDefaults($this->activeCategory);
            $this->loadSettings();
            $this->showMessage('Ustawienia zostały przywrócone do wartości domyślnych', 'success');
        } catch (\Exception $e) {
            $this->showMessage('Błąd podczas resetowania: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Test połączenia email
     */
    public function testEmailConnection()
    {
        try {
            // Wysyłanie testowego emaila
            $settings = $this->settingsService->getEmailSettings();
            
            // Tutaj można dodać logikę testowania SMTP
            $this->showMessage('Test połączenia email - funkcja w development', 'info');
            
        } catch (\Exception $e) {
            $this->showMessage('Błąd połączenia: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Buduj reguły walidacji
     */
    private function buildValidationRules($settings)
    {
        $rules = [];
        
        foreach ($settings as $key => $config) {
            $rule = [];
            
            if ($config['required'] ?? false) {
                $rule[] = 'required';
            }
            
            switch ($config['type']) {
                case 'integer':
                    $rule[] = 'integer';
                    if (isset($config['min'])) $rule[] = 'min:' . $config['min'];
                    if (isset($config['max'])) $rule[] = 'max:' . $config['max'];
                    break;
                    
                case 'email':
                    $rule[] = 'email';
                    break;
                    
                case 'boolean':
                    $rule[] = 'boolean';
                    break;
                    
                case 'string':
                default:
                    $rule[] = 'string';
                    if (isset($config['max'])) $rule[] = 'max:' . $config['max'];
                    break;
            }
            
            if (!empty($rule)) {
                $rules["tempValues.{$key}"] = $rule;
            }
        }
        
        return $rules;
    }

    /**
     * Pokaż wiadomość
     */
    private function showMessage($message, $type = 'info')
    {
        $this->message = $message;
        $this->messageType = $type;
        
        // Auto-hide po 5 sekundach
        $this->dispatch('messageShown');
    }

    /**
     * Resetuj wiadomości
     */
    public function resetMessages()
    {
        $this->message = '';
        $this->messageType = '';
    }

    /**
     * Aktualizuj wartość tymczasową
     */
    public function updatedTempValues($value, $key)
    {
        $this->resetMessages();
    }
}