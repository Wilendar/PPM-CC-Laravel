# ETAP 07g: System Synchronizacji Marek (Manufacturers) z PrestaShop

**Status:** 🛠️ W TRAKCIE IMPLEMENTACJI (FAZA 1-3 UKOŃCZONE)
**Priorytet:** WYSOKI
**Zależności:** ETAP_07 (PrestaShop API), ETAP_07d (Media Sync)
**Szacowana złożoność:** ŚREDNIA

---

## 📋 Cel

Pełna dwukierunkowa synchronizacja marek (producentów) między PPM a PrestaShop, włącznie z:
- Logo/obrazami producentów
- Opisami (description, short_description)
- Polami SEO (meta_title, meta_description, meta_keywords)
- Status aktywności

---

## 📊 Analiza Obecnego Stanu

### Co działa:
- ✅ Model `Manufacturer` z podstawowymi polami
- ✅ Tabela pivot `manufacturer_shop` z mapowaniem ps_manufacturer_id
- ✅ Import nazwy i statusu z PrestaShop
- ✅ UI ManufacturerManager (CRUD, przypisywanie do sklepów)

### Czego brakuje:
- ❌ Upload logo do PrestaShop
- ❌ Pobieranie logo z PrestaShop
- ❌ Pola SEO w modelu
- ❌ Short description
- ❌ Tworzenie nowych producentów w PrestaShop
- ❌ Aktualizacja istniejących producentów w PrestaShop
- ❌ Sync dwukierunkowy z conflict resolution

---

## 🏗️ FAZA 1: Rozszerzenie Modelu Danych

### 1.1 Migracja - nowe pola w `manufacturers`
**Status:** ✅ UKOŃCZONE
**PLIK:** `database/migrations/2025_12_15_100000_add_seo_fields_to_manufacturers.php`

```php
Schema::table('manufacturers', function (Blueprint $table) {
    $table->text('short_description')->nullable()->after('description');
    $table->string('meta_title', 255)->nullable()->after('short_description');
    $table->string('meta_description', 512)->nullable()->after('meta_title');
    $table->string('meta_keywords', 255)->nullable()->after('meta_description');
    $table->string('ps_link_rewrite', 128)->nullable()->after('code');
});
```

### 1.2 Aktualizacja modelu Manufacturer
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Models/Manufacturer.php`

Dodać do `$fillable`:
- `short_description`
- `meta_title`
- `meta_description`
- `meta_keywords`
- `ps_link_rewrite`

### 1.3 Migracja - rozszerzenie `manufacturer_shop` pivot
**Status:** ✅ UKOŃCZONE
**PLIK:** `database/migrations/2025_12_15_100001_add_logo_sync_to_manufacturer_shop.php`

```php
Schema::table('manufacturer_shop', function (Blueprint $table) {
    $table->boolean('logo_synced')->default(false)->after('sync_status');
    $table->timestamp('logo_synced_at')->nullable()->after('logo_synced');
    $table->text('sync_error')->nullable()->after('logo_synced_at');
});
```

---

## 🏗️ FAZA 2: PrestaShop API Client Extensions

### 2.1 Metoda pobierania szczegółów producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 796)

```php
public function getManufacturer(int $manufacturerId): ?array
```

### 2.2 Metoda tworzenia producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 831)

```php
public function createManufacturer(array $manufacturerData): array
```

### 2.3 Metoda aktualizacji producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 870)

```php
public function updateManufacturer(int $manufacturerId, array $manufacturerData): array
```

### 2.4 Metoda upload logo producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 935)

```php
public function uploadManufacturerImage(int $manufacturerId, string $imagePath, string $filename = 'logo.jpg'): bool
```

### 2.5 Metoda pobierania logo producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 999)

```php
public function downloadManufacturerImage(int $manufacturerId): ?string
```

### 2.6 Metoda usuwania producenta
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/PrestaShop8Client.php` (linia 905)

```php
public function deleteManufacturer(int $manufacturerId): bool
```

### 2.7 Dodatkowe metody pomocnicze (BONUS)
**Status:** ✅ UKOŃCZONE
- `getManufacturerImageUrl()` - URL do logo z autentykacją
- `hasManufacturerImage()` - sprawdzenie czy logo istnieje (HEAD request)
- `deleteManufacturerImage()` - usunięcie logo

---

## 🏗️ FAZA 3: Serwis Synchronizacji Producentów

### 3.1 ManufacturerSyncService - główny serwis
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/ManufacturerSyncService.php`

Zaimplementowane metody:
- `importFromPrestaShop()` - import producentów z PrestaShop
- `syncToPrestaShop()` - sync pojedynczego producenta PPM → PrestaShop
- `syncLogoToPrestaShop()` - upload logo do PrestaShop
- `importLogoFromPrestaShop()` - pobieranie logo z PrestaShop
- `bulkSyncToShop()` - bulk sync wielu producentów
- `getSyncSummary()` - podsumowanie statusów sync
- `needsSync()` - sprawdzenie czy producent wymaga synchronizacji

### 3.2 ManufacturerTransformer - transformacja danych
**Status:** ✅ UKOŃCZONE
**PLIK:** `app/Services/PrestaShop/ManufacturerTransformer.php`

Zaimplementowane metody:
- `transformForPrestaShop()` - PPM → PrestaShop format z multilang
- `transformFromPrestaShop()` - PrestaShop → PPM format
- `buildMultilangField()` - budowanie struktury multilang
- `extractMultilangValue()` - ekstrakcja wartości z multilang
- `mergeForUpdate()` - merge danych dla GET-MODIFY-PUT
- `validateForSync()` - walidacja przed synchronizacją

---

## 🏗️ FAZA 4: Job Queue dla Sync

### 4.1 SyncManufacturerToPrestaShop Job
**Status:** ❌ Nie rozpoczęte
**PLIK:** `app/Jobs/PrestaShop/SyncManufacturerToPrestaShop.php`

```php
class SyncManufacturerToPrestaShop implements ShouldQueue
{
    public function __construct(
        public Manufacturer $manufacturer,
        public PrestaShopShop $shop,
        public bool $syncLogo = true
    ) {}
}
```

### 4.2 ImportManufacturersFromPrestaShop Job
**Status:** ❌ Nie rozpoczęte
**PLIK:** `app/Jobs/PrestaShop/ImportManufacturersFromPrestaShop.php`

```php
class ImportManufacturersFromPrestaShop implements ShouldQueue
{
    public function __construct(
        public PrestaShopShop $shop,
        public bool $includeImages = false,
        public ?int $progressId = null
    ) {}
}
```

### 4.3 SyncManufacturerLogo Job
**Status:** ❌ Nie rozpoczęte
**PLIK:** `app/Jobs/PrestaShop/SyncManufacturerLogo.php`

```php
class SyncManufacturerLogo implements ShouldQueue
{
    public function __construct(
        public Manufacturer $manufacturer,
        public PrestaShopShop $shop,
        public string $direction = 'push' // 'push' | 'pull'
    ) {}
}
```

---

## 🏗️ FAZA 5: UI - Rozszerzenie ManufacturerManager

### 5.1 Rozszerzenie formularza edycji
**Status:** ❌ Nie rozpoczęte
**PLIK:** `app/Http/Livewire/Admin/Products/ManufacturerManager.php`

Nowe pola w modalu edycji:
- Short description (textarea)
- Meta title (input)
- Meta description (textarea)
- Meta keywords (input)
- Upload logo (file input)
- Podgląd logo

### 5.2 Panel synchronizacji per producent
**Status:** ❌ Nie rozpoczęte
**PLIK:** `resources/views/livewire/admin/products/manufacturer-manager.blade.php`

- Lista sklepów z statusem sync
- Przycisk "Sync do sklepu" per sklep
- Przycisk "Sync logo" per sklep
- Przycisk "Pobierz z PrestaShop"
- Status badge (synced/pending/error)

### 5.3 Bulk operations
**Status:** ❌ Nie rozpoczęte

- Bulk sync wybranych producentów do sklepu
- Bulk import logo z PrestaShop
- Bulk push logo do PrestaShop

### 5.4 Import panel rozszerzenie
**Status:** ❌ Nie rozpoczęte

- Checkbox "Importuj logo producentów"
- Progress bar dla importu
- Podsumowanie zaimportowanych/zaktualizowanych

---

## 🏗️ FAZA 6: Storage i Media Integration

### 6.1 Konfiguracja storage dla logo
**Status:** ❌ Nie rozpoczęte
**PLIK:** `config/filesystems.php`

```php
'manufacturer_logos' => [
    'driver' => 'local',
    'root' => storage_path('app/public/manufacturers'),
    'url' => env('APP_URL').'/storage/manufacturers',
    'visibility' => 'public',
],
```

### 6.2 ManufacturerLogoService
**Status:** ❌ Nie rozpoczęte
**PLIK:** `app/Services/ManufacturerLogoService.php`

```php
class ManufacturerLogoService
{
    public function store(UploadedFile $file, Manufacturer $manufacturer): string;
    public function delete(Manufacturer $manufacturer): bool;
    public function getUrl(Manufacturer $manufacturer): ?string;
    public function resize(string $path, int $width, int $height): string;
}
```

---

## 🏗️ FAZA 7: Testy i Dokumentacja

### 7.1 Unit Tests
**Status:** ❌ Nie rozpoczęte

- `tests/Unit/Services/ManufacturerSyncServiceTest.php`
- `tests/Unit/Services/ManufacturerTransformerTest.php`

### 7.2 Feature Tests
**Status:** ❌ Nie rozpoczęte

- `tests/Feature/ManufacturerSyncTest.php`
- `tests/Feature/ManufacturerLogoSyncTest.php`

### 7.3 Dokumentacja
**Status:** ❌ Nie rozpoczęte

- `_DOCS/MANUFACTURER_SYNC_GUIDE.md`

---

## 📋 TODO Summary

### FAZA 1: Model (Priorytet: KRYTYCZNY)
- [ ] 1.1: Migracja - nowe pola SEO w manufacturers
- [ ] 1.2: Update Manufacturer model ($fillable)
- [ ] 1.3: Migracja - rozszerzenie pivot table

### FAZA 2: API Client (Priorytet: WYSOKI)
- [ ] 2.1: getManufacturer() method
- [ ] 2.2: createManufacturer() method
- [ ] 2.3: updateManufacturer() method
- [ ] 2.4: uploadManufacturerImage() method
- [ ] 2.5: downloadManufacturerImage() method
- [ ] 2.6: deleteManufacturer() method

### FAZA 3: Sync Service (Priorytet: WYSOKI)
- [ ] 3.1: ManufacturerSyncService
- [ ] 3.2: ManufacturerTransformer

### FAZA 4: Jobs (Priorytet: ŚREDNI)
- [ ] 4.1: SyncManufacturerToPrestaShop Job
- [ ] 4.2: ImportManufacturersFromPrestaShop Job
- [ ] 4.3: SyncManufacturerLogo Job

### FAZA 5: UI (Priorytet: ŚREDNI)
- [ ] 5.1: Rozszerzenie formularza edycji
- [ ] 5.2: Panel synchronizacji per producent
- [ ] 5.3: Bulk operations
- [ ] 5.4: Import panel rozszerzenie

### FAZA 6: Storage (Priorytet: ŚREDNI)
- [ ] 6.1: Konfiguracja storage
- [ ] 6.2: ManufacturerLogoService

### FAZA 7: Testy (Priorytet: NISKI)
- [ ] 7.1: Unit Tests
- [ ] 7.2: Feature Tests
- [ ] 7.3: Dokumentacja

---

## 🔗 Powiązane pliki

| Typ | Ścieżka |
|-----|---------|
| Model | `app/Models/Manufacturer.php` |
| API Client | `app/Services/PrestaShop/PrestaShop8Client.php` |
| Livewire | `app/Http/Livewire/Admin/Products/ManufacturerManager.php` |
| Blade | `resources/views/livewire/admin/products/manufacturer-manager.blade.php` |
| Migration | `database/migrations/*_create_manufacturers_table.php` |
| Pivot Migration | `database/migrations/*_create_manufacturer_shop_table.php` |

---

## 📝 Notatki techniczne

### PrestaShop API Endpoints
```
GET    /api/manufacturers              - Lista producentów
GET    /api/manufacturers/{id}         - Szczegóły producenta
POST   /api/manufacturers              - Utworzenie producenta
PUT    /api/manufacturers/{id}         - Aktualizacja producenta
DELETE /api/manufacturers/{id}         - Usunięcie producenta

GET    /api/images/manufacturers/{id}  - Pobranie logo
POST   /api/images/manufacturers/{id}  - Upload logo
```

### Multilang format dla PrestaShop
```php
[
    'description' => [
        'language' => [
            ['@attributes' => ['id' => 1], '#' => 'Opis PL'],
            ['@attributes' => ['id' => 2], '#' => 'Description EN'],
        ]
    ]
]
```

### Wymagane nagłówki dla image upload
```
Content-Type: multipart/form-data
Authorization: Basic base64(API_KEY:)
```
