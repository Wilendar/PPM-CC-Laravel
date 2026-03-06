# PPM - System Settings Documentation

> **Wersja:** 1.0.0
> **Data:** 2026-03-06
> **Status:** Production Ready
> **Changelog:** Inicjalna dokumentacja modulu System Settings - centralny panel konfiguracji systemu PPM

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Grupy Ustawien](#4-grupy-ustawien)
5. [Permissions i Security](#5-permissions-i-security)
6. [Email Config Bridge](#6-email-config-bridge)
7. [Data Retention](#7-data-retention)
8. [Szyfrowanie](#8-szyfrowanie)
9. [System Powiadomien](#9-system-powiadomien)
10. [Troubleshooting](#10-troubleshooting)
11. [Changelog](#11-changelog)

---

## 1. Overview

### 1.1 Opis modulu

**SystemSettings** to centralny panel konfiguracji systemu PPM, umozliwiajacy administratorom zarzadzanie wszystkimi parametrami aplikacji z poziomu interfejsu webowego bez koniecznosci edycji plikow `.env` ani kodu zrodlowego. Modul obsluguje 9 kategorii ustawien, automatyczne szyfrowanie wrazliwych wartosci, cache ustawien 30 minut oraz integracje z mechanizmem konfiguracji maila Laravel przez `AppServiceProvider::applyDatabaseMailConfig()`.

Ustawienia kategorii `data_retention` sa obsluzone przez dedykowany sub-komponent `DataRetentionSettings`, ktory zarzadza politykami retencji dla 12 tabel operacyjnych oraz archiwizacja danych przed usunieciem.

**URL Panelu:** `/admin/system-settings`

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Livewire Components | 2 (SystemSettings + DataRetentionSettings) |
| Blade Views | 2 |
| Model | 1 (SystemSetting) |
| Services | 2 (SettingsService, RetentionConfigService) |
| Kategorii ustawien | 9 |
| Kluczy konfiguracyjnych lacznie | ~41 |
| Zaszyfrowanych kluczy | 7 |
| Tabel objetych retencja | 12 |
| Linie kodu (backend) | ~1050 |
| Linie kodu (frontend) | ~215 |

### 1.3 Kluczowe funkcjonalnosci

- **9 kategorii ustawien** - General, Security, Product, Email, Integration, Backup, UI, Data Retention, Maintenance
- **Lewy sidebar z zakladkami** - przelaczanie kategorii przez `switchCategory()` z Livewire
- **Automatyczne szyfrowanie** - 7 wrazliwych kluczy szyfrowanych przez Laravel `encrypt()`/`decrypt()` w boot hooks modelu
- **Cache 30 minut** - `SettingsService` cachuje kazde ustawienie indywidualnie z prefixem `settings:`
- **Email Config Bridge** - `AppServiceProvider::applyDatabaseMailConfig()` nadpisuje konfiguracje maila Laravel ustawieniami z DB przy kazdym starcie aplikacji
- **Test email** - wyslanie testowej wiadomosci na wybrany adres z interfejsu (widoczne tylko w zakladce Email)
- **Sub-komponent Data Retention** - oddzielny komponent `DataRetentionSettings` z `ArchiveService`
- **Reset do domyslnych** - `resetCategoryToDefaults()` przywraca wartosci domyslne dla aktywnej kategorii
- **Walidacja serwerowa** - `buildValidationRules()` generuje reguly walidacji na podstawie metadanych ustawien (min, max, required, type)
- **Upload plikow** - obsluga logo firmy przez `Livewire\WithFileUploads` i `SettingsService::handleFileUpload()`

---

## 2. Architektura Plikow

### 2.1 Livewire Components

| Plik | Linie | Opis |
|------|-------|------|
| `app/Http/Livewire/Admin/Settings/SystemSettings.php` | ~772 | Glowny komponent - 9 kategorii, saveSettings(), testEmailConnection(), resetCategoryToDefaults() |
| `app/Http/Livewire/Admin/Settings/DataRetentionSettings.php` | ~305 | Sub-komponent retencji - saveRetention(), cleanupTable(), archiveAll(), toggleArchive() |

### 2.2 Blade Views

| Plik | Linie | Opis |
|------|-------|------|
| `resources/views/livewire/admin/settings/system-settings.blade.php` | ~215 | Glowny widok - sidebar z zakladkami, renderowanie formularzy per typ, test email |
| `resources/views/livewire/admin/settings/data-retention-settings.blade.php` | - | Widok sub-komponentu retencji (osadzany przez `<livewire:admin.settings.data-retention-settings />`) |

### 2.3 Model

| Plik | Linie | Opis |
|------|-------|------|
| `app/Models/SystemSetting.php` | ~263 | Model Eloquent - boot hooks szyfrowania, accessor/mutator dla value, shouldEncrypt(), getCategories() |

### 2.4 Services

| Plik | Linie | Opis |
|------|-------|------|
| `app/Services/SettingsService.php` | ~358 | Cache wrapper dla SystemSetting - get/set/getCategory, handleFileUpload, importSettings/exportSettings |
| `app/Services/RetentionConfigService.php` | ~90 | Konfiguracja retencji per tabela - priorytet: DB > config > default |
| `app/Services/ArchiveService.php` | - | Archiwizacja danych do plikow JSON.GZ przed usunieciem |

### 2.5 Provider

| Plik | Metoda | Opis |
|------|--------|------|
| `app/Providers/AppServiceProvider.php` | `applyDatabaseMailConfig()` | Czyta SMTP z DB i nadpisuje Config::set() przy boot(); wywoływana w `boot()` |

### 2.6 Config

| Plik | Opis |
|------|------|
| `config/database-cleanup.php` | Domyslne polityki retencji dla 12 tabel - retention_days, date_column, chunk_size, thresholds |

---

## 3. Schema Bazy Danych

### 3.1 Tabela `system_settings`

| Kolumna | Typ | Opis |
|---------|-----|------|
| `id` | bigint UNSIGNED | Primary key, auto-increment |
| `category` | varchar | Kategoria: general, security, product, email, integration, backup, maintenance, data_retention, ui |
| `key` | varchar | Unikalny klucz ustawienia (unique index) |
| `value` | text | Wartosc - plain string, JSON lub cipher text (jesli is_encrypted) |
| `type` | varchar | Typ: string, integer, boolean, json, email, url, file |
| `is_encrypted` | tinyint(1) | Czy wartosc jest zaszyfrowana przez Laravel encrypt() |
| `description` | text nullable | Opis ustawienia |
| `created_by` | bigint UNSIGNED FK | FK -> users.id - kto utworzyl |
| `updated_by` | bigint UNSIGNED FK | FK -> users.id - kto zaktualizowal |
| `created_at` | timestamp | Data utworzenia |
| `updated_at` | timestamp | Data ostatniej aktualizacji |

### 3.2 Relacje

```
system_settings
    |--- N:1 ---> users (created_by)
    |--- N:1 ---> users (updated_by)
```

### 3.3 Scopes modelu

| Scope | Opis |
|-------|------|
| `scopeCategory($query, $category)` | Filtruj po kategorii: `SystemSetting::category('email')->get()` |
| `scopePublic($query)` | Tylko nieszyfrowane: `SystemSetting::public()->get()` |

### 3.4 Static helpers

| Metoda | Opis |
|--------|------|
| `SystemSetting::get($key, $default)` | Pobierz wartosc przez klucz (z accessorem) |
| `SystemSetting::set($key, $value, $category, $type, $description)` | updateOrCreate z auto-assign created_by/updated_by |
| `SystemSetting::getCategory($category)` | Tablica key=>value dla kategorii |
| `SystemSetting::getCategories()` | Tablica wszystkich 9 kategorii (slug => etykieta PL) |

---

## 4. Grupy Ustawien

### 4.1 General (5 ustawien)

Ikona: `fas fa-cogs`

| Klucz | Typ | Domyslna wartosc | Opis |
|-------|-----|-----------------|------|
| `company_name` | string | `MPP TRADE` | Nazwa firmy wyswietlana w aplikacji |
| `company_logo` | file | null | Logo firmy (zalecane 200x80px, PNG/JPG) - przechowywane w storage/public/settings/ |
| `timezone` | select | `Europe/Warsaw` | Strefa czasowa: Europe/Warsaw, Europe/London, America/New_York, Asia/Tokyo |
| `currency` | select | `PLN` | Waluta: PLN, EUR, USD, GBP |
| `language` | select | `pl` | Jezyk: pl (Polski), en (English) |

### 4.2 Security (8 ustawien)

Ikona: `fas fa-shield-alt`

| Klucz | Typ | Domyslna wartosc | Min | Max | Opis |
|-------|-----|-----------------|-----|-----|------|
| `password_min_length` | integer | `8` | 6 | 32 | Minimalna dlugosc hasla |
| `password_require_uppercase` | boolean | `true` | - | - | Wymagaj wielkich liter |
| `password_require_numbers` | boolean | `true` | - | - | Wymagaj cyfr |
| `password_require_symbols` | boolean | `false` | - | - | Wymagaj symboli |
| `session_timeout` | integer | `7200` | 300 | 86400 | Timeout sesji w sekundach (domyslnie 2h) |
| `max_login_attempts` | integer | `5` | 3 | 20 | Max nieudanych prob logowania |
| `lockout_duration` | integer | `300` | 60 | 3600 | Czas blokady po przekroczeniu limitu (sekundy) |
| `dev_auth_bypass` | boolean | `false` | - | - | DEV MODE: wylacz autoryzacje (NIGDY nie wlaczac na produkcji!) |

### 4.3 Product (6 ustawien)

Ikona: `fas fa-box`

| Klucz | Typ | Domyslna wartosc | Min | Max | Opis |
|-------|-----|-----------------|-----|-----|------|
| `default_tax_rate` | integer | `23` | 0 | 100 | Domyslna stawka VAT w procentach |
| `sku_generation_pattern` | string | `AUTO-{number}` | - | - | Szablon SKU, `{number}` = numer sekwencyjny |
| `image_max_size_mb` | integer | `10` | 1 | 50 | Max rozmiar jednego zdjecia produktu (MB) |
| `image_max_count` | integer | `20` | 1 | 50 | Max liczba zdiec na produkt |
| `max_category_depth` | integer | `5` | 3 | 10 | Max glebokosc drzewa kategorii |
| `auto_categorization` | boolean | `false` | - | - | Automatyczne przypisywanie kategorii na podstawie nazwy |

### 4.4 Email (7 ustawien)

Ikona: `fas fa-envelope`

| Klucz | Typ | Domyslna wartosc | Opis |
|-------|-----|-----------------|------|
| `smtp_host` | string | null | Serwer SMTP (np. smtp.gmail.com, smtp.office365.com) |
| `smtp_port` | integer | `587` | Port SMTP (587 STARTTLS, 465 SSL, 25 plain) |
| `smtp_username` | string | null | Login SMTP |
| `smtp_password` | password | null | Haslo SMTP - **SZYFROWANE** w DB, widoczne jako `********` w UI |
| `smtp_encryption` | select | `tls` | Szyfrowanie: tls (STARTTLS), ssl (SSL/TLS), '' (brak) |
| `from_email` | email | null | Adres nadawcy (From) |
| `from_name` | string | null | Nazwa nadawcy |

**Uwaga:** Po zapisaniu ustawien email wywoływane jest `$this->settingsService->clearCategoryCache('email')` aby nowe wartosci zostaly uzyty przez `applyDatabaseMailConfig()` przy nastepnym requeście.

### 4.5 Integration (4 ustawienia)

Ikona: `fas fa-plug`

| Klucz | Typ | Domyslna wartosc | Opis |
|-------|-----|-----------------|------|
| `sync_frequency` | select | `hourly` | Czestotliwosc sync: real_time, every_15min, hourly, daily, manual |
| `conflict_resolution` | select | `manual` | Rozwiazywanie konfliktow: manual, ppm_wins, external_wins, newest_wins |
| `auto_retry_failed` | boolean | `true` | Automatycznie ponow nieudane synchronizacje |
| `max_retry_attempts` | integer | `3` | Max liczba ponowien (1-10) |

### 4.6 Backup (3 ustawienia)

Ikona: `fas fa-database`

| Klucz | Typ | Domyslna wartosc | Min | Max | Opis |
|-------|-----|-----------------|-----|-----|------|
| `backup_frequency` | select | `daily` | Czestotliwosc: daily, weekly, monthly, manual |
| `backup_retention_days` | integer | `30` | 7 | 365 | Liczba dni przechowywania backupow |
| `backup_compress` | boolean | `true` | - | - | Kompresuj backupy (GZIP) |

### 4.7 UI (3 ustawienia)

Ikona: `fas fa-palette`

| Klucz | Typ | Domyslna wartosc | Opis |
|-------|-----|-----------------|------|
| `default_theme` | select | `light` | Motyw: light (Jasny), dark (Ciemny), auto (Automatyczny) |
| `items_per_page` | select | `25` | Elementow na strone: 10, 25, 50, 100 |
| `dashboard_refresh_interval` | select | `30` | Odswiez dashboard co: 0 (wylaczone), 30s, 60s, 300s |

### 4.8 Data Retention (sub-komponent)

Ikona: `fas fa-clock`

Obslugiwana przez dedykowany komponent `DataRetentionSettings`. Ustawienia przechowywane w `system_settings` z kategoria `data_retention` i kluczami postaci `retention.{table}.days`.

Globalne ustawienia retencji:

| Klucz | Typ | Opis |
|-------|-----|------|
| `retention.archive_enabled` | boolean | Czy archiwizowac dane przed usunieciem |
| `retention.archive_retention_days` | integer | Przechowywanie archiwow (30-730 dni, domyslnie 180) |
| `retention.sync_cleanup_enabled` | boolean | Auto-cleanup tabeli sync_jobs |

Per tabela: `retention.{table}.days` (integer).

### 4.9 Maintenance (5 ustawien)

Ikona: `fas fa-wrench`

| Klucz | Typ | Domyslna wartosc | Opis |
|-------|-----|-----------------|------|
| `maintenance_mode` | boolean | `false` | Tryb konserwacji - uzytkownicy widza komunikat serwisowy |
| `maintenance_message` | string | `System jest w trakcie konserwacji. Prosimy o cierpliwosc.` | Tresc komunikatu |
| `maintenance_allowed_ips` | string | `''` | IP z dostepem podczas konserwacji (oddzielone przecinkami) |
| `auto_cleanup_frequency` | select | `weekly` | Czestotliwosc auto-czyszczenia: daily, weekly, monthly, disabled |
| `log_retention_days` | integer | `90` | Przechowywanie logow systemowych (7-365 dni) |

---

## 5. Permissions i Security

### 5.1 Wymagane uprawnienie

```php
// SystemSettings::mount()
$this->authorize('system.manage');

// SystemSettings::saveSettings()
$this->authorize('system.manage');

// SystemSettings::resetCategoryToDefaults()
$this->authorize('system.manage');

// SystemSettings::testEmailConnection()
$this->authorize('system.manage');

// DataRetentionSettings::mount()
$this->authorize('system.manage');
```

### 5.2 Macierz dostepu

| Rola | Dostep do /admin/system-settings | Uprawnienie |
|------|----------------------------------|-------------|
| Admin | Pelny (odczyt + zapis + test email) | `system.manage` |
| Manager | Brak (uprawnienie nie przydzielone domyslnie) | - |
| Edytor | Brak | - |
| Magazyn | Brak | - |
| Handlowy | Brak | - |
| Reklamacje | Brak | - |
| User | Brak | - |

### 5.3 Route

```php
// routes/web.php - w grupie middleware admin
Route::get('/admin/system-settings', \App\Http\Livewire\Admin\Settings\SystemSettings::class)
    ->name('admin.system-settings');
```

### 5.4 Layout

```php
public function render()
{
    return view('livewire.admin.settings.system-settings', [...])
        ->layout('layouts.admin');
}
```

---

## 6. Email Config Bridge

### 6.1 Opis mechanizmu

`AppServiceProvider::applyDatabaseMailConfig()` jest wywolywana w `boot()` przy kazdym starcie aplikacji (kazdym requeście HTTP). Czyta ustawienia SMTP z `system_settings` przez `SystemSetting::get()` (bez cache - bezposrednio z DB) i nadpisuje konfiguracje Laravel przez `Config::set()`.

### 6.2 Diagram przepływu

```
Aplikacja startuje
    |
    v
AppServiceProvider::boot()
    |
    +-> applyDatabaseMailConfig()
            |
            +-- SystemSetting::get('smtp_host')
            |
            +-- jesli smtp_host PUSTE -> return (uzyj .env)
            |
            +-- Config::set('mail.default', 'smtp')
            +-- Config::set('mail.mailers.smtp.host', $smtpHost)
            +-- Config::set('mail.mailers.smtp.port', $port)
            +-- Config::set('mail.mailers.smtp.username', $username)
            +-- Config::set('mail.mailers.smtp.password', $password)
            +-- Config::set('mail.mailers.smtp.encryption', $encryption)
            +-- Config::set('mail.from.address', $fromEmail)
            +-- Config::set('mail.from.name', $fromName)
```

### 6.3 Fallback

Jesli `smtp_host` jest pusty lub null w `system_settings` -> metoda konczy sie (`return`) bez zadnych `Config::set()`. Laravel uzywa wowczas wartosci z `.env` (`MAIL_MAILER`, `MAIL_HOST`, itp.).

Wyjatki (np. baza niedostepna podczas migracji) sa przechwytywane przez `catch(\Exception $e)` i ignorowane - aplikacja startuje z konfiguracjia `.env`.

### 6.4 Test email

Metoda `testEmailConnection()` (publiczna, Livewire):
- Wymaga `authorize('system.manage')`
- Waliduje `testEmailAddress` jako `required|email`
- Wysyla plain-text przez `Mail::raw()` na podany adres odbiorcy
- Uzywane w widoku: pole input `wire:model.defer="testEmailAddress"` + przycisk `wire:click="testEmailConnection"` widoczny tylko przy aktywnej zakladce `email`
- Loguje wynik jako `Log::info()` (sukces) lub `Log::error()` (blad)

### 6.5 Cache invalidation po zapisie SMTP

```php
// W saveSettings() po pomyslnym zapisie kategorii email:
if ($this->activeCategory === 'email') {
    $this->settingsService->clearCategoryCache('email');
}
```

`clearCategoryCache()` usuwa klucz `settings:category:email` z cache. Klucze per-ustawienie (`settings:smtp_host` itp.) sa usuwane indywidualnie w `SettingsService::set()` przy kazdym zapisie.

---

## 7. Data Retention

### 7.1 Sub-komponent DataRetentionSettings

Komponent jest osadzany w `system-settings.blade.php` gdy `$activeCategory === 'data_retention'`:

```blade
@if($activeCategory === 'data_retention')
    <livewire:admin.settings.data-retention-settings />
@endif
```

### 7.2 RetentionConfigService - priorytet konfiguracji

```
1. system_settings DB: retention.{table}.days
2. config/database-cleanup.php: tables.{table}.retention_days
3. Domyslne: 30 dni
```

### 7.3 Tabele objetym retencja (12 tabel)

| Tabela | Retencja domyslna | Kolumna daty | Chunk | Komenda (opcjonalnie) |
|--------|-------------------|-------------|-------|----------------------|
| `telescope_entries` | 2 dni | `created_at` | 10000 | `telescope:prune --hours=48` |
| `price_history` | 90 dni | `created_at` | 5000 | `price-history:cleanup --days=90` |
| `sync_jobs` | 30 dni | `created_at` | 1000 | `sync:cleanup` |
| `sync_logs` | 14 dni | `created_at` | 5000 | - |
| `integration_logs` | 30 dni | `created_at` | 5000 | - |
| `job_progress` | 7 dni | `created_at` | 1000 | `jobs:cleanup-stuck --minutes=10080` |
| `failed_jobs` | 30 dni | `failed_at` | 100 | - |
| `notifications` | 90 dni | `created_at` | 1000 | - |
| `category_preview` | 1 dzien | `created_at` | 1000 | `category-preview:cleanup` |
| `audit_logs` | 90 dni | `created_at` | 5000 | `audit:cleanup --days=90` |
| `product_scan_results` | 30 dni | `created_at` | 1000 | `scan:cleanup --days=30` |
| `product_scan_sessions` | 30 dni | `created_at` | 500 | - |

### 7.4 Progi alertow (MB)

| Tabela | Warning | Critical |
|--------|---------|----------|
| `telescope_entries` | 100 MB | 500 MB |
| `price_history` | 500 MB | 2000 MB |
| `sync_jobs` | 50 MB | 200 MB |
| `audit_logs` | 100 MB | 500 MB |
| `notifications` | 50 MB | 200 MB |

### 7.5 Archiwizacja

- Format: `JSON.GZ` w `storage/app/archives/`
- Retencja archiwow: 180 dni domyslnie (konfigurowalne 30-730 dni)
- Metody: `archiveAll()` archiwizuje wszystkie wlaczone tabele, `downloadArchive()` zwraca Response download, `deleteArchive()` usuwa plik z dysku

### 7.6 Metody DataRetentionSettings

| Metoda | Opis |
|--------|------|
| `loadData()` | Laduje retentionConfig, tableStats (size_mb, row_count), archives (max 20), archiveStats |
| `saveRetention(string $table, int $days)` | Zapisuje dni retencji do system_settings (1-365) |
| `cleanupTable(string $table)` | Wywoluje artisan command (jesli skonfigurowany) lub generyczne DELETE WHERE date < cutoff |
| `archiveAll()` | Archiwizuje i usuwa rekordy ze wszystkich wlaczonych tabel |
| `toggleArchive(bool $enabled)` | Wlacz/wylacz archiwizacje |
| `toggleSyncCleanup(bool $enabled)` | Wlacz/wylacz auto-cleanup sync_jobs |
| `saveArchiveRetention(int $days)` | Zapisz czas przechowywania archiwow (30-730 dni) |
| `downloadArchive(string $filename)` | Pobierz plik archiwum |
| `deleteArchive(string $filename)` | Usun plik archiwum |
| `cleanupOldArchives()` | Usun archiwa starsze niz archive_retention_days |

---

## 8. Szyfrowanie

### 8.1 Lista zaszyfrowanych kluczy (7)

```php
// SystemSetting::shouldEncrypt()
$encryptedKeys = [
    'smtp_password',
    'api_keys',
    'oauth_secrets',
    'backup_encryption_key',
    'database_passwords',
    'erp_credentials',
    'webhook_secret',
];
```

### 8.2 Mechanizm szyfrowania (boot hooks)

```php
// Przy tworzeniu rekordu:
static::creating(function ($setting) {
    if ($setting->shouldEncrypt()) {
        $setting->attributes['value'] = encrypt($rawValue);
        $setting->is_encrypted = true;
    }
});

// Przy aktualizacji (tylko jesli value sie zmienilo):
static::updating(function ($setting) {
    if ($setting->isDirty('value') && $setting->shouldEncrypt()) {
        $setting->attributes['value'] = encrypt($rawValue);
        $setting->is_encrypted = true;
    }
});
```

### 8.3 Deszyfrowanie (accessor)

```php
public function getValueAttribute($value)
{
    if ($this->is_encrypted) {
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            return null;  // Zwroc null jesli deszyfrowanie sie nie powiedzie
        }
    }
    return json_decode($value, true) ?? $value;
}
```

### 8.4 Mutator (przygotowanie do zapisu)

```php
public function setValueAttribute($value)
{
    // Logika szyfrowania jest w boot() - tutaj tylko JSON encode
    $this->attributes['value'] = is_string($value) ? $value : json_encode($value);
}
```

**Krytyczna uwaga:** Cast `'value' => 'json'` zostal celowo usuniety z `$casts` (2025-11-13) aby uniknac konfliktu z customowym accessor/mutator (podwojne kodowanie powodowalo brak persystencji wartosci).

### 8.5 Obsluga hasla SMTP w UI

W `initTempValues()` typ `password` inicjalizuje sie jako pusty string `''` (nie `'********'`). W `saveSettings()` klucz `smtp_password` jest pomijany jesli wartosc to `''` lub `'********'` - oznacza to "nie zmieniaj hasla".

### 8.6 Wyswietlanie zaszyfrowanych wartosci

- W UI: pola password zawsze pokazuja `********` (hardcoded w blade)
- W `getDisplayValue()`: zaszyfrowane pola zwracaja string `'***ENCRYPTED***'`
- W `exportSettings()`: zaszyfrowane wartosci sa eksportowane jako `'***ENCRYPTED***'`, nie importowane przy `importSettings()`

---

## 9. System Powiadomien

### 9.1 Powiadamianie przez Notifications

Modul System Settings nie wysyla powiadomien bezposrednio, ale jest zintegrowany z systemem powiadomien przez:

1. **BackupCompletedNotification** - wysylana po ukonczeniu backupu (konfigurowanego przez ustawienia backup)
2. **Preferencje uzytkownika** - `NotificationPreferences` komponent przechowuje preferencje w `users.notification_settings` (JSON)

### 9.2 Klasy Notification

| Klasa | Trigger | Kategoria preferencji |
|-------|---------|----------------------|
| `ImportProductReadyNotification` | Import gotowy do publikacji | `import_ready` |
| `ImportProductScheduledNotification` | Data publikacji ustawiona | `import_scheduled` |
| `ImportProductPublishedNotification` | Produkt opublikowany | `import_published` |
| `SyncFailedNotification` | Nieudana synchronizacja | `sync_failed` |
| `NewUserPendingNotification` | Nowy uzytkownik czeka na zatwierdzenie | `new_user_pending` |
| `BackupCompletedNotification` | Backup ukonczony | `backup_completed` |
| `LoginFromNewIpNotification` | Logowanie z nowego IP | `login_new_ip` |

### 9.3 Preferencje uzytkownika (NotificationPreferences)

Uzytkownik konfiguruje kanaly powiadomien w `/admin/profile/notifications` (komponent `NotificationPreferences`). Preferencje sa przechowywane w `users.notification_settings` (JSON column) i auto-zapisywane przy kazdej zmianie toggle (Livewire `updated()`).

Klucze preferencji: `{channel}_{category}` gdzie channel = `email` lub `browser`.

Domyslne wartosci:

| Kategoria | Email domyslnie | Browser domyslnie |
|-----------|----------------|------------------|
| product_changes | true | false |
| import_ready | true | true |
| import_scheduled | true | false |
| import_published | true | true |
| sync_status | true | true |
| sync_failed | true | true |
| security_alerts | true | true |
| login_new_ip | true | true |
| system_updates | false | false |
| backup_completed | false | true |
| new_user_pending | true | true |

### 9.4 Widocznosc kategorii powiadomien

Kategorie sa filtrowane przez uprawnienia: `NotificationPreferences::visibleCategories()` sprawdza `$user->can($cat['permission'])`. Admin widzi wszystkie kategorie.

| Kategoria | Wymagane uprawnienie |
|-----------|---------------------|
| product_changes | `products.read` |
| import_* | `import.read` |
| sync_* | `shops.sync` |
| security_alerts | null (wszyscy) |
| login_new_ip | null (wszyscy) |
| system_updates | `system.config` |
| backup_completed | `system.config` |
| new_user_pending | `system.config` |

---

## 10. Troubleshooting

### 10.1 Haslo SMTP nie zapisuje sie (wartosc wraca do poprzedniej)

**Symptom:** Po wpisaniu nowego hasla SMTP i kliknieciu "Zapisz", haslo nie zmienia sie. Wartosci wraca do `********`.

**Przyczyna:** `initTempValues()` ustawia `tempValues['smtp_password'] = ''` (pusty string) dla pol typu `password`. Metoda `saveSettings()` pomija klucz jesli `$value === '********' || $value === ''`. Jesli formularz wysyla puste pole (user nic nie wpisal), haslo nie jest nadpisywane - to zachowanie zgodne z projektem.

**Rozwiazanie:** Upewnij sie ze wpisujesz nowe haslo w pole SMTP Password przed kliknieciem "Zapisz". Pole nie moze byc puste ani zawierac `********`.

### 10.2 Mail driver = log mimo skonfigurowanego SMTP

**Symptom:** Emaile trafi do `storage/logs/laravel.log` zamiast byc wysylane przez SMTP.

**Przyczyna:** `applyDatabaseMailConfig()` nie znalazl `smtp_host` w `system_settings` (wartosc jest null lub pusty string). Laravel uzywa wowczas `MAIL_MAILER` z `.env`, ktory domyslnie ustawiony jest na `log`.

**Rozwiazanie:**
1. Wejdz do `/admin/system-settings` -> zakladka Email
2. Wpisz adres serwera SMTP w pole "Serwer SMTP"
3. Kliknij "Zapisz ustawienia"
4. Sprawdz w DB: `SELECT value FROM system_settings WHERE key = 'smtp_host'`
5. Uzyj "Wyslij test" aby zweryfikowac dzialanie

### 10.3 Wartosc zaszyfrowana zwraca null

**Symptom:** Odczyt zaszyfrowanego ustawienia (np. `smtp_password`) zwraca `null` mimo ze bylo zapisane.

**Przyczyna:** `decrypt()` failuje jesli:
- Wartosc nie jest poprawnym cipher text (np. zostala zapisana przed wlaczeniem szyfrowania jako plain text)
- Zmienilo sie `APP_KEY` w `.env` po zapisaniu wartosci

**Rozwiazanie:**
1. Sprawdz `is_encrypted` flag: `SELECT key, is_encrypted FROM system_settings WHERE key = 'smtp_password'`
2. Jesli `is_encrypted = 0` ale `value` nie jest plain textem - rekord jest uszkodzony, usuń go i zapisz ponownie przez UI
3. Jesli zmienilo sie `APP_KEY` - wszystkie zaszyfrowane wartosci sa nieodwracalnie utracone, nalezy je wpisac ponownie

### 10.4 Kategoria Maintenance pokazuje pusta zawartosc

**Symptom:** Po kliknieciu zakladki "Konserwacja systemu" formularz jest pusty (brak pol).

**Przyczyna (naprawione v1.0.0):** Brak case `'maintenance'` w `getSettingsForCategory()`. Metoda zwracala `[]` (domyslny przypadek switch).

**Weryfikacja:** Sprawdz ze `SystemSettings::getSettingsForCategory()` zawiera `case 'maintenance': return $this->getMaintenanceSettings();`

**Rozwiazanie:** Upewnij sie ze plik `app/Http/Livewire/Admin/Settings/SystemSettings.php` jest aktualny (zawiera case maintenance w switch).

### 10.5 Preselekcja select nie dziala (pokazuje pierwszy element zamiast zapisanej wartosci)

**Symptom:** Po wejsciu w kategorie z polem select (np. timezone, currency), formularz pokazuje pierwszy element listy zamiast aktualnie zapisanej wartosci.

**Przyczyna:** Wartosci z DB moga byc typow int lub bool, podczas gdy HTML option `value` jest zawsze stringiem. Opcja `<option value="25">` nie dopasuje sie do `$tempValues['items_per_page'] = 25` (int).

**Rozwiazanie (zaimplementowane v1.0.0):** `initTempValues()` castuje wartosci select na `(string)`:
```php
} elseif ($type === 'select') {
    $this->tempValues[$key] = (string) $setting['value'];
}
```

### 10.6 Cache ustawien nie odswieza sie po recznej edycji DB

**Symptom:** Po bezposredniej edycji wartosci w tabeli `system_settings` przez SQL, aplikacja nadal uzywa starej wartosci.

**Przyczyna:** `SettingsService` cachuje wartosci przez 30 minut (1800 sekund) z prefiksem `settings:`.

**Rozwiazanie:**
```bash
# Czyszczenie przez artisan (produkcja)
php artisan cache:clear

# Lub reczne przez SettingsService (w tinker)
app(App\Services\SettingsService::class)->clearAllCache();

# Lub czyszczenie konkretnego klucza
Cache::forget('settings:smtp_host');
```

---

## 11. Changelog

### v1.0.0 (2026-03-06)

- **Inicjalna dokumentacja** modulu System Settings
- **9 kategorii ustawien** z lacznie ~41 kluczami konfiguracyjnymi
- **General (5):** company_name, company_logo, timezone, currency, language
- **Security (8):** password_min_length, password_require_uppercase, password_require_numbers, password_require_symbols, session_timeout, max_login_attempts, lockout_duration, dev_auth_bypass
- **Product (6):** default_tax_rate, sku_generation_pattern, image_max_size_mb, image_max_count, max_category_depth, auto_categorization
- **Email (7):** smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name
- **Integration (4):** sync_frequency, conflict_resolution, auto_retry_failed, max_retry_attempts
- **Backup (3):** backup_frequency, backup_retention_days, backup_compress
- **UI (3):** default_theme, items_per_page, dashboard_refresh_interval
- **Maintenance (5):** maintenance_mode, maintenance_message, maintenance_allowed_ips, auto_cleanup_frequency, log_retention_days
- **Data Retention:** sub-komponent DataRetentionSettings z 12 tabelami i ArchiveService
- **Email Config Bridge:** AppServiceProvider::applyDatabaseMailConfig() nadpisuje Config::set() przy boot()
- **Szyfrowanie:** 7 kluczy (smtp_password, api_keys, oauth_secrets, backup_encryption_key, database_passwords, erp_credentials, webhook_secret)
- **Test email:** pole odbiorcy + przycisk "Wyslij test" w zakladce Email
- **Fix:** Case maintenance w getSettingsForCategory() (bylo pominiete)
- **Fix:** initTempValues() castuje select na (string) dla prawidlowej preselekcji HTML
- **Fix:** Wylaczony cast `value => json` z modelu (konflikt z accessor/mutator, 2025-11-13)
- **Fix:** saveSettings() pomija smtp_password jesli '' lub '********' (brak nadpisania hasla)
