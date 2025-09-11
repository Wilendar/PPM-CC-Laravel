<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SettingsService
{
    /**
     * Cache prefix dla settings
     */
    private const CACHE_PREFIX = 'settings:';
    
    /**
     * Czas cache'owania (30 minut)
     */
    private const CACHE_TTL = 1800;

    /**
     * Pobierz wartość ustawienia z cache
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            return SystemSetting::get($key, $default);
        });
    }

    /**
     * Ustaw wartość ustawienia i usuń z cache
     */
    public function set(string $key, $value, string $category = 'general', string $type = 'string', string $description = null): SystemSetting
    {
        $setting = SystemSetting::set($key, $value, $category, $type, $description);
        
        // Usuń z cache
        $cacheKey = self::CACHE_PREFIX . $key;
        Cache::forget($cacheKey);
        
        // Usuń też cache dla całej kategorii
        $this->clearCategoryCache($category);
        
        return $setting;
    }

    /**
     * Pobierz wszystkie ustawienia z kategorii
     */
    public function getCategory(string $category): array
    {
        $cacheKey = self::CACHE_PREFIX . 'category:' . $category;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            return SystemSetting::getCategory($category);
        });
    }

    /**
     * Ustaw wiele ustawień naraz
     */
    public function setMultiple(array $settings, string $category = 'general'): void
    {
        foreach ($settings as $key => $config) {
            if (is_array($config)) {
                $this->set(
                    $key, 
                    $config['value'], 
                    $config['category'] ?? $category,
                    $config['type'] ?? 'string',
                    $config['description'] ?? null
                );
            } else {
                $this->set($key, $config, $category);
            }
        }
    }

    /**
     * Waliduj ustawienia
     */
    public function validate(array $settings): array
    {
        $errors = [];
        
        foreach ($settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            
            if ($setting && !$setting->validateValue($value)) {
                $errors[$key] = "Nieprawidłowa wartość dla ustawienia {$key}";
            }
        }
        
        return $errors;
    }

    /**
     * Obsługa uploadu pliku dla ustawienia
     */
    public function handleFileUpload(string $key, UploadedFile $file, string $directory = 'settings'): string
    {
        $filename = $key . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Usuń stary plik jeśli istnieje
        $oldPath = $this->get($key);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        
        $this->set($key, $path, 'general', 'file');
        
        return $path;
    }

    /**
     * Pobierz URL do pliku ustawienia
     */
    public function getFileUrl(string $key): ?string
    {
        $path = $this->get($key);
        
        if (!$path) {
            return null;
        }
        
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        
        return null;
    }

    /**
     * Wyczyść cache kategorii
     */
    public function clearCategoryCache(string $category): void
    {
        $cacheKey = self::CACHE_PREFIX . 'category:' . $category;
        Cache::forget($cacheKey);
    }

    /**
     * Wyczyść cały cache ustawień
     */
    public function clearAllCache(): void
    {
        // W rzeczywistej implementacji można by użyć tagów cache
        // Tu musimy usunąć klucze według prefixu
        $keys = Cache::getStore()->getKeys();
        
        foreach ($keys as $key) {
            if (str_starts_with($key, self::CACHE_PREFIX)) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Pobierz ustawienia dla konfiguracji aplikacji
     */
    public function getAppSettings(): array
    {
        return [
            'company_name' => $this->get('company_name', 'PPM-CC-Laravel'),
            'company_logo' => $this->getFileUrl('company_logo'),
            'timezone' => $this->get('timezone', 'Europe/Warsaw'),
            'currency' => $this->get('currency', 'PLN'),
            'language' => $this->get('language', 'pl'),
            'theme' => $this->get('theme', 'light'),
        ];
    }

    /**
     * Pobierz ustawienia SMTP
     */
    public function getEmailSettings(): array
    {
        return [
            'smtp_host' => $this->get('smtp_host'),
            'smtp_port' => $this->get('smtp_port', 587),
            'smtp_username' => $this->get('smtp_username'),
            'smtp_password' => $this->get('smtp_password'),
            'smtp_encryption' => $this->get('smtp_encryption', 'tls'),
            'from_email' => $this->get('from_email'),
            'from_name' => $this->get('from_name'),
        ];
    }

    /**
     * Pobierz ustawienia bezpieczeństwa
     */
    public function getSecuritySettings(): array
    {
        return [
            'password_min_length' => $this->get('password_min_length', 8),
            'password_require_uppercase' => $this->get('password_require_uppercase', true),
            'password_require_numbers' => $this->get('password_require_numbers', true),
            'password_require_symbols' => $this->get('password_require_symbols', false),
            'session_timeout' => $this->get('session_timeout', 7200), // 2 godziny
            'max_login_attempts' => $this->get('max_login_attempts', 5),
            'lockout_duration' => $this->get('lockout_duration', 300), // 5 minut
            'two_factor_enabled' => $this->get('two_factor_enabled', false),
        ];
    }

    /**
     * Pobierz ustawienia produktów
     */
    public function getProductSettings(): array
    {
        return [
            'default_tax_rate' => $this->get('default_tax_rate', 23),
            'sku_generation_pattern' => $this->get('sku_generation_pattern', 'AUTO-{number}'),
            'image_max_size_mb' => $this->get('image_max_size_mb', 10),
            'image_max_count' => $this->get('image_max_count', 20),
            'required_fields' => $this->get('required_fields', ['name', 'category']),
            'auto_categorization' => $this->get('auto_categorization', false),
            'max_category_depth' => $this->get('max_category_depth', 5),
            'seo_auto_generate' => $this->get('seo_auto_generate', true),
        ];
    }

    /**
     * Pobierz ustawienia integracji
     */
    public function getIntegrationSettings(): array
    {
        return [
            'sync_frequency' => $this->get('sync_frequency', 'hourly'),
            'conflict_resolution' => $this->get('conflict_resolution', 'manual'),
            'auto_retry_failed' => $this->get('auto_retry_failed', true),
            'max_retry_attempts' => $this->get('max_retry_attempts', 3),
            'backup_before_sync' => $this->get('backup_before_sync', false),
            'webhook_enabled' => $this->get('webhook_enabled', false),
            'webhook_secret' => $this->get('webhook_secret'),
        ];
    }

    /**
     * Export ustawień do pliku JSON
     */
    public function exportSettings(array $categories = []): array
    {
        $query = SystemSetting::with('creator', 'updater');
        
        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }
        
        $settings = $query->get();
        
        $export = [];
        foreach ($settings as $setting) {
            $export[$setting->key] = [
                'category' => $setting->category,
                'value' => $setting->is_encrypted ? '***ENCRYPTED***' : $setting->value,
                'type' => $setting->type,
                'description' => $setting->description,
                'created_at' => $setting->created_at->toISOString(),
                'updated_at' => $setting->updated_at->toISOString(),
            ];
        }
        
        return $export;
    }

    /**
     * Import ustawień z pliku JSON (tylko dla nieszyfrowanych)
     */
    public function importSettings(array $settings): array
    {
        $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];
        
        foreach ($settings as $key => $config) {
            try {
                // Nie importujemy ustawień szyfrowanych ze względów bezpieczeństwa
                if (isset($config['value']) && $config['value'] === '***ENCRYPTED***') {
                    $results['skipped']++;
                    continue;
                }
                
                $this->set(
                    $key,
                    $config['value'],
                    $config['category'] ?? 'general',
                    $config['type'] ?? 'string',
                    $config['description'] ?? null
                );
                
                $results['imported']++;
            } catch (\Exception $e) {
                $results['errors'][$key] = $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Reset ustawienia do wartości domyślnych
     */
    public function resetToDefaults(string $category = null): void
    {
        $query = SystemSetting::query();
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $settings = $query->get();
        
        foreach ($settings as $setting) {
            $defaultValue = $this->getDefaultValue($setting->key);
            
            if ($defaultValue !== null) {
                $setting->update(['value' => $defaultValue]);
                
                // Usuń z cache
                $cacheKey = self::CACHE_PREFIX . $setting->key;
                Cache::forget($cacheKey);
            }
        }
        
        if ($category) {
            $this->clearCategoryCache($category);
        } else {
            $this->clearAllCache();
        }
    }

    /**
     * Pobierz domyślną wartość dla klucza
     */
    private function getDefaultValue(string $key)
    {
        $defaults = [
            'company_name' => 'PPM-CC-Laravel',
            'timezone' => 'Europe/Warsaw',
            'currency' => 'PLN',
            'language' => 'pl',
            'theme' => 'light',
            'password_min_length' => 8,
            'session_timeout' => 7200,
            'max_login_attempts' => 5,
            'default_tax_rate' => 23,
            'image_max_size_mb' => 10,
            'sync_frequency' => 'hourly',
        ];
        
        return $defaults[$key] ?? null;
    }
}