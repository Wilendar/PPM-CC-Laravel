<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SystemSetting extends Model
{
    /**
     * Tabela dla ustawień systemowych
     */
    protected $table = 'system_settings';

    /**
     * Pola masowo przypisywalne
     */
    protected $fillable = [
        'category',      // general, security, product, integration
        'key',           // unique key dla ustawienia
        'value',         // wartość ustawienia (JSON lub string)
        'type',          // string, integer, boolean, json, file
        'description',   // opis ustawienia
        'is_encrypted',  // czy wartość jest szyfrowana
        'created_by',    // kto utworzył ustawienie
        'updated_by',    // kto ostatnio aktualizował
    ];

    /**
     * Ukryte pola w serializacji
     */
    protected $hidden = [
        'is_encrypted',
    ];

    /**
     * Kastowanie typów
     */
    protected $casts = [
        'value' => 'json',
        'is_encrypted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method - automatyczne kastowanie wartości
     */
    protected static function boot()
    {
        parent::boot();

        // Automatyczne szyfrowanie sensitive values
        static::creating(function ($setting) {
            if ($setting->shouldEncrypt()) {
                $setting->value = encrypt($setting->value);
                $setting->is_encrypted = true;
            }
        });

        static::updating(function ($setting) {
            if ($setting->isDirty('value') && $setting->shouldEncrypt()) {
                $setting->value = encrypt($setting->value);
                $setting->is_encrypted = true;
            }
        });
    }

    /**
     * Accessor dla value - automatyczne deszyfrowanie
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted) {
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return json_decode($value, true) ?? $value;
    }

    /**
     * Mutator dla value - przygotowanie do zapisu
     */
    public function setValueAttribute($value)
    {
        // Nie robimy nic tutaj - logika w boot()
        $this->attributes['value'] = is_string($value) ? $value : json_encode($value);
    }

    /**
     * Określa czy dane ustawienie powinno być szyfrowane
     */
    private function shouldEncrypt(): bool
    {
        $encryptedKeys = [
            'smtp_password',
            'api_keys',
            'oauth_secrets',
            'backup_encryption_key',
            'database_passwords',
            'erp_credentials'
        ];

        return in_array($this->key, $encryptedKeys) || 
               str_contains($this->key, 'password') ||
               str_contains($this->key, 'secret') ||
               str_contains($this->key, 'key') ||
               str_contains($this->key, 'token');
    }

    /**
     * Relacja do użytkownika który utworzył
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacja do użytkownika który aktualizował
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope - ustawienia z kategorii
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope - ustawienia publiczne (nieszyfrowane)
     */
    public function scopePublic($query)
    {
        return $query->where('is_encrypted', false);
    }

    /**
     * Helper - pobierz wartość ustawienia
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper - ustaw wartość ustawienia
     */
    public static function set(string $key, $value, string $category = 'general', string $type = 'string', string $description = null): SystemSetting
    {
        $userId = auth()->id();
        
        return static::updateOrCreate(
            ['key' => $key],
            [
                'category' => $category,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Helper - pobierz wszystkie ustawienia z kategorii
     */
    public static function getCategory(string $category): array
    {
        return static::where('category', $category)
                    ->pluck('value', 'key')
                    ->toArray();
    }

    /**
     * Walidacja wartości według typu
     */
    public function validateValue($value): bool
    {
        switch ($this->type) {
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']);
            
            case 'integer':
                return is_numeric($value);
            
            case 'json':
                return is_array($value) || is_object($value);
            
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            
            case 'file':
                return is_string($value) && file_exists(storage_path($value));
            
            default:
                return true;
        }
    }

    /**
     * Formatowanie wartości dla wyświetlenia
     */
    public function getDisplayValue()
    {
        if ($this->is_encrypted) {
            return '***ENCRYPTED***';
        }

        switch ($this->type) {
            case 'boolean':
                return $this->value ? 'Tak' : 'Nie';
            
            case 'json':
                return json_encode($this->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            case 'file':
                return basename($this->value);
            
            default:
                return $this->value;
        }
    }

    /**
     * Kategorie systemowych ustawień
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'Ogólne ustawienia aplikacji',
            'security' => 'Bezpieczeństwo i dostęp',
            'product' => 'Zarządzanie produktami',
            'integration' => 'Integracje zewnętrzne',
            'email' => 'Konfiguracja poczty',
            'backup' => 'System kopii zapasowych',
            'maintenance' => 'Konserwacja systemu',
            'ui' => 'Interfejs użytkownika',
        ];
    }
}