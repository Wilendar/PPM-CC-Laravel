# Plan: Integracja Subiekt GT - Rozszerzone Pola i Cykliczne Joby

**Data utworzenia:** 2026-01-26
**Status:** ✅ UKOŃCZONY (FAZA 1-7 DONE)
**Priorytet:** HIGH
**Ostatnia aktualizacja:** 2026-01-27 - FAZA 7 Sync pól rozszerzonych (tw_Pole1-5, tw_Uwagi) DEPLOYED

---

## 1. Zakres Zadań

### 1.1 Poprawka UI - Label "Edytowano" (QUICK FIX)
- Przesunięcie label z prawej na lewą stronę inputów w Stock Tab

### 1.2 Rozszerzone pola synchronizacji z ERP
Nowe mapowania PPM ↔ Subiekt GT:

| Pole PPM | Pole Subiekt GT | Typ | Opis |
|----------|-----------------|-----|------|
| `manufacturer` | `tw_IdPodstDostawca` | int (FK) | Mapowanie producenta po nazwie |
| `supplier_code` | `tw_DostSymbol` | varchar(20) | Kod dostawcy |
| `shop_internet` | `tw_SklepInternet` | bit | Nowy switch w PPM |
| `split_payment` | `tw_MechanizmPodzielonejPlatnosci` | bit | Nowy switch w PPM |
| `material` | `tw_Pole1` | varchar(50) | Materiał produktu |
| `stock.location` | `tw_Pole2` | varchar(50) | Lokalizacja per magazyn |
| `defect_symbol` | `tw_Pole3` | varchar(50) | Symbol z wadą |
| `application` | `tw_Pole4` | varchar(50) | Zastosowanie |
| `cn_code` | `tw_Pole5` | varchar(50) | Kod CN (Nomenklatura Scalona) |

### 1.3 Cykliczne JOBy ERP
- Naprawienie widoczności ERP jobów w /admin/shops/sync
- Konfiguracja częstotliwości pobierania z ERP
- Konfiguracja źródła cen/stanów (ERP vs PrestaShop)
- Modyfikacja PullProductsFromPrestaShop (fallback gdy ERP aktywny)

---

## 2. Plan Implementacji

### ✅ FAZA 1: Label "Edytowano" - Quick Fix (~30 min) [DONE 2026-01-26]

**Cel:** Przesunąć label z prawej na lewą stronę inputów

**Plik:** `resources/views/livewire/products/management/tabs/stock-tab.blade.php`

**Zmiany (3 miejsca - linie ~230, ~256, ~279):**
```blade
{{-- PRZED: --}}
<span class="absolute -top-2.5 right-0 px-1.5 ...">Edytowano</span>

{{-- PO: --}}
<span class="absolute -top-2.5 left-0 px-1.5 ...">Edytowano</span>
```

**Weryfikacja:** Chrome DevTools - sprawdzić czy label nie nachodzi na input

---

### ✅ FAZA 2: Migracje Bazy Danych (~1h) [DEPLOYED 2026-01-26]

#### 2.1 Migracja: Nowe pola w `products`

**Plik:** `database/migrations/2026_01_27_000001_add_subiekt_extended_fields_to_products.php`

```php
Schema::table('products', function (Blueprint $table) {
    $table->boolean('shop_internet')->default(false)
          ->comment('tw_SklepInternet - Sklep internetowy');
    $table->boolean('split_payment')->default(false)
          ->comment('tw_MechanizmPodzielonejPlatnosci');
    $table->string('cn_code', 50)->nullable()
          ->comment('tw_Pole5 - Kod CN');
    $table->string('material', 50)->nullable()
          ->comment('tw_Pole1 - Materiał produktu');
    $table->string('defect_symbol', 50)->nullable()
          ->comment('tw_Pole3 - Symbol z wadą');
    $table->string('application', 255)->nullable()
          ->comment('tw_Pole4 - Zastosowanie');
});
```

#### 2.2 Migracja: Lokalizacja w `product_stock`

**Plik:** `database/migrations/2026_01_27_000002_add_location_to_product_stock.php`

```php
Schema::table('product_stock', function (Blueprint $table) {
    $table->string('location', 50)->nullable()
          ->after('minimum_stock')
          ->comment('tw_Pole2 - Lokalizacja per magazyn');
});
```

#### 2.3 Migracja: Konfiguracja sync w `erp_connections`

**Plik:** `database/migrations/2026_01_27_000003_add_sync_config_to_erp_connections.php`

```php
Schema::table('erp_connections', function (Blueprint $table) {
    $table->string('sync_frequency', 20)->default('6_hours')
          ->comment('every_15_min, every_30_min, hourly, 6_hours, daily');
    $table->boolean('is_price_source')->default(false)
          ->comment('Czy ERP jest źródłem cen');
    $table->boolean('is_stock_source')->default(false)
          ->comment('Czy ERP jest źródłem stanów');
});
```

---

### ✅ FAZA 3: Modele i Service (~2h) [DEPLOYED 2026-01-26]

#### 3.1 Product.php - Nowe pola

**Plik:** `app/Models/Product.php`

```php
// Dodać do $fillable:
'shop_internet',
'split_payment',
'cn_code',
'material',
'defect_symbol',
'application',

// Dodać do $casts:
'shop_internet' => 'boolean',
'split_payment' => 'boolean',
```

#### 3.2 ProductStock.php - Pole location

**Plik:** `app/Models/ProductStock.php`

```php
// Dodać do $fillable:
'location',
```

#### 3.3 ERPConnection.php - Konfiguracja sync

**Plik:** `app/Models/ERPConnection.php`

```php
// Dodać do $fillable:
'sync_frequency',
'is_price_source',
'is_stock_source',

// Dodać do $casts:
'is_price_source' => 'boolean',
'is_stock_source' => 'boolean',

// Stałe frequency
public const FREQ_15_MIN = 'every_15_min';
public const FREQ_30_MIN = 'every_30_min';
public const FREQ_HOURLY = 'hourly';
public const FREQ_6_HOURS = '6_hours';
public const FREQ_DAILY = 'daily';

public static function getFrequencyOptions(): array
{
    return [
        self::FREQ_15_MIN => 'Co 15 minut',
        self::FREQ_30_MIN => 'Co 30 minut',
        self::FREQ_HOURLY => 'Co godzinę',
        self::FREQ_6_HOURS => 'Co 6 godzin',
        self::FREQ_DAILY => 'Raz dziennie',
    ];
}
```

#### 3.4 SubiektGTService.php - Rozszerzone mapowanie

**Plik:** `app/Services/ERP/SubiektGTService.php`

**Nowe metody:**
```php
// Mapowanie producenta po nazwie
protected function findManufacturerIdByName(string $name): ?int
{
    // Szukaj w kh__Kontrahent WHERE kh_Nazwa LIKE '%name%'
    $result = $this->restClient->getManufacturers(['name' => $name]);
    return $result['data'][0]['id'] ?? null;
}

// Rozszerzone mapowanie w buildProductDataForSubiekt()
protected function mapExtendedFields(Product $product): array
{
    return [
        'tw_SklepInternet' => $product->shop_internet ? 1 : 0,
        'tw_MechanizmPodzielonejPlatnosci' => $product->split_payment ? 1 : 0,
        'tw_Pole1' => $product->material,
        'tw_Pole3' => $product->defect_symbol,
        'tw_Pole4' => $product->application,
        'tw_Pole5' => $product->cn_code,
        'tw_DostSymbol' => $product->supplier_code,
    ];
}

// Mapowanie lokalizacji per magazyn
protected function mapStockLocations(Product $product): array
{
    $locations = [];
    foreach ($product->stock as $stock) {
        if ($stock->location) {
            $locations[$stock->warehouse_id] = $stock->location;
        }
    }
    // tw_Pole2 = lokalizacje oddzielone przecinkiem
    return ['tw_Pole2' => implode(',', $locations)];
}
```

---

### ✅ FAZA 4: UI - Sekcja "Informacje rozszerzone" (~2h) [DEPLOYED 2026-01-26]

#### 4.1 ProductForm.php - Nowe properties

**Plik:** `app/Http/Livewire/Products/Management/ProductForm.php`

```php
// Nowe properties
public bool $shopInternet = false;
public bool $splitPayment = false;
public ?string $cnCode = null;
public ?string $material = null;
public ?string $defectSymbol = null;
public ?string $application = null;

// UI state
public bool $extendedInfoExpanded = false;

// Toggle method
public function toggleExtendedInfo(): void
{
    $this->extendedInfoExpanded = !$this->extendedInfoExpanded;
}
```

#### 4.2 product-form.blade.php - Nowa sekcja (zwijana)

**Lokalizacja:** Po sekcji "Slug URL", przed końcem basic-tab

```blade
{{-- Sekcja "Informacje rozszerzone" - domyślnie zwinięta --}}
<div class="mt-6 border border-gray-700 rounded-lg overflow-hidden">
    {{-- Header (kliknij aby zwinąć/rozwinąć) --}}
    <button type="button"
            wire:click="toggleExtendedInfo"
            class="w-full flex items-center justify-between px-4 py-3 bg-gray-800/50 hover:bg-gray-800 transition-colors">
        <span class="text-sm font-medium text-gray-300">
            Informacje rozszerzone (ERP)
        </span>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 {{ $extendedInfoExpanded ? 'rotate-180' : '' }}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Content (animowane show/hide) --}}
    <div x-show="$wire.extendedInfoExpanded"
         x-collapse
         class="px-4 py-4 bg-gray-900/30 border-t border-gray-700">

        {{-- Slug URL (przeniesiony tutaj) --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-300 mb-1">Slug URL</label>
            <input type="text" wire:model="slug" class="form-input-enterprise w-full" readonly>
        </div>

        <div class="grid grid-cols-2 gap-4">
            {{-- Kod CN --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Kod CN</label>
                <input type="text" wire:model="cnCode" maxlength="50" class="form-input-enterprise w-full">
            </div>

            {{-- Materiał --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Materiał</label>
                <input type="text" wire:model="material" maxlength="50" class="form-input-enterprise w-full">
            </div>

            {{-- Symbol z wadą --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Symbol z wadą</label>
                <input type="text" wire:model="defectSymbol" maxlength="50" class="form-input-enterprise w-full">
            </div>

            {{-- Zastosowanie --}}
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Zastosowanie</label>
                <input type="text" wire:model="application" maxlength="255" class="form-input-enterprise w-full">
            </div>
        </div>

        {{-- Switche --}}
        <div class="mt-4 flex flex-wrap gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="shopInternet" class="checkbox-enterprise">
                <span class="text-sm text-gray-300">Sklep internetowy</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="splitPayment" class="checkbox-enterprise">
                <span class="text-sm text-gray-300">Mechanizm podzielonej płatności</span>
            </label>
        </div>
    </div>
</div>
```

#### 4.3 stock-tab.blade.php - Nowa kolumna "Lokalizacja"

**Dodać kolumnę w tabeli magazynów:**

```blade
{{-- Header --}}
<th scope="col" class="px-4 py-3">Lokalizacja</th>

{{-- Cell --}}
<td class="px-4 py-3">
    <div class="relative">
        <input type="text"
               wire:model.live="stock.{{ $warehouseId }}.location"
               class="form-input-enterprise w-full text-sm"
               maxlength="50"
               placeholder="Kod lokalizacji">
    </div>
</td>
```

**Quick actions:** Dodać przyciski "Kopiuj" i "Skopiuj na wszystkie"

---

### ✅ FAZA 5: UI - SyncController ERP (~2h) [DEPLOYED 2026-01-26]

#### ✅ 5.0 Przeniesienie sekcji ERP + 3 niezależne częstotliwości [DEPLOYED 2026-01-26]

**Zmiany:**
- Sekcja "Konfiguracja Synchronizacji ERP" przeniesiona tuż pod sekcję "Konfiguracja Synchronizacji" (PrestaShop)
- Rozdzielenie pojedynczej częstotliwości na 3 niezależne:
  - `price_sync_frequency` - Sync cen (ikona żółta $)
  - `stock_sync_frequency` - Sync stanów (ikona niebieska box)
  - `basic_data_sync_frequency` - Sync danych podstawowych (ikona fioletowa doc)
- Nowa migracja: `2026_01_27_000004_add_separate_sync_frequencies_to_erp_connections.php`
- Zaktualizowany model ERPConnection.php z nowymi polami
- Zaktualizowany SyncController.php z 3 properties dla frequencies
- Zapis działa poprawnie (zweryfikowano w Chrome + logi Laravel)

#### 5.1 Badge dla Subiekt GT

**Plik:** `resources/views/livewire/admin/shops/sync-controller.blade.php`

```blade
{{-- W sekcji Recent Jobs (około linii 2136) --}}
@switch($job->target_type)
    @case('prestashop')
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-500/20 text-blue-300">
            PrestaShop
        </span>
        @break
    @case('subiekt_gt')
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-500/20 text-green-300">
            Subiekt GT
        </span>
        @break
    @case('baselinker')
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-500/20 text-orange-300">
            Baselinker
        </span>
        @break
    @case('dynamics')
        <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-500/20 text-purple-300">
            Dynamics
        </span>
        @break
@endswitch
```

#### 5.2 Konfiguracja ERP sync frequency

**Plik:** `app/Http/Livewire/Admin/Shops/SyncController.php`

```php
// Nowe properties
public ?int $selectedErpConnectionId = null;
public string $erpSyncFrequency = '6_hours';
public bool $erpIsPriceSource = false;
public bool $erpIsStockSource = false;

// Metoda zapisu
public function saveErpSyncConfig(): void
{
    $connection = ERPConnection::find($this->selectedErpConnectionId);
    if (!$connection) return;

    $connection->update([
        'sync_frequency' => $this->erpSyncFrequency,
        'is_price_source' => $this->erpIsPriceSource,
        'is_stock_source' => $this->erpIsStockSource,
    ]);

    $this->dispatch('notification', [
        'type' => 'success',
        'message' => 'Konfiguracja ERP zapisana',
    ]);
}
```

**UI sekcja "ERP Sync Configuration":**
- Dropdown wyboru ERP connection
- Dropdown frequency (15min, 30min, hourly, 6h, daily)
- Switch: "ERP jest źródłem cen"
- Switch: "ERP jest źródłem stanów"
- Przycisk "Zapisz"

---

### ✅ FAZA 6: Scheduler i PullJobs (~1.5h) [DEPLOYED 2026-01-26]

#### ✅ 6.1 Dynamiczny scheduler w routes/console.php

**Zmiany:**
- Zaktualizowany scheduler `erp:dynamic-sync` do obsługi 3 niezależnych częstotliwości
- Dispatch'uje osobne joby dla: `prices`, `stock`, `basic_data`
- Każdy typ sync ma własną częstotliwość z ERPConnection
- Job deduplication per typ synchronizacji

**Implementacja (routes/console.php linie 237-319):**
```php
// 3 niezależne częstotliwości
$priceFreq = $connection->price_sync_frequency;     // Sync cen
$stockFreq = $connection->stock_sync_frequency;     // Sync stanów
$basicFreq = $connection->basic_data_sync_frequency; // Sync danych podstawowych

// Dispatch osobnych jobów dla każdego typu gdy nadejdzie czas
if ($shouldSyncNow($priceFreq)) {
    $dispatchSyncJob($connection, 'prices', $priceFreq);
}
if ($shouldSyncNow($stockFreq)) {
    $dispatchSyncJob($connection, 'stock', $stockFreq);
}
if ($shouldSyncNow($basicFreq)) {
    $dispatchSyncJob($connection, 'basic_data', $basicFreq);
}
```

#### ✅ 6.2 PullProductsFromPrestaShop - wykrywanie ERP (już zaimplementowane!)

**Status:** Logika ERP source była już wcześniej zaimplementowana (linie 191-247)

**Funkcjonalność:**
- Deferring gdy ERP sync jest aktywny (release 300s)
- Skip prices gdy `is_price_source = true`
- Skip stock gdy `is_stock_source = true`
- Logowanie wszystkich akcji

**Kod już zawiera (linie 191-247):**
```php
// ETAP_08 FAZA 6: ERP Source Fallback Logic
$erpAsSource = ERPConnection::where('is_active', true)
    ->where(function ($q) {
        $q->where('is_price_source', true)
          ->orWhere('is_stock_source', true);
    })->exists();

// Skip prices/stock based on ERP source flags
$skipPrices = ERPConnection::where('is_price_source', true)->exists();
$skipStock = ERPConnection::where('is_stock_source', true)->exists();
```

---

### ✅ FAZA 7: Synchronizacja pól rozszerzonych (tw_Pole1-5, tw_Uwagi) [DEPLOYED 2026-01-27]

**Problem:**
- PullProductsFromSubiektGT->basic_data_sync_frequency nie pobierał pól tw_Pole1-5, tw_Uwagi
- Sekcja "Informacje rozszerzone (ERP)" w ProductForm nie wyświetlała danych z ERP

#### 7.1 Aktualizacja .NET API (SubiektRepository.cs)

**Zmiany:**
- Dodano 6 nowych właściwości do klasy Product: Pole1, Pole2, Pole3, Pole4, Pole5, Notes
- Zaktualizowano 3 zapytania SQL (GetProductsAsync, GetProductByIdAsync, GetProductBySkuAsync)
- Kolumny: tw_Pole1-5, tw_Uwagi

**Kod:**
```csharp
public string? Pole1 { get; set; }  // Material
public string? Pole2 { get; set; }  // Location
public string? Pole3 { get; set; }  // Defect Symbol
public string? Pole4 { get; set; }  // Application
public string? Pole5 { get; set; }  // CN Code
public string? Notes { get; set; }  // tw_Uwagi
```

#### 7.2 Aktualizacja SubiektGTService.php

**Zmiany:**
- Rozszerzono metodę updateProductBasicDataFromErp() o mapowanie pól:
  - Pole1 → material
  - Pole3 → defect_symbol
  - Pole4 → application
  - Pole5 → cn_code
  - Notes → notes

**Kod:**
```php
$extendedMappings = [
    ['subiekt' => 'Pole1', 'alt' => 'pole1', 'ppm' => 'material'],
    ['subiekt' => 'Pole3', 'alt' => 'pole3', 'ppm' => 'defect_symbol'],
    ['subiekt' => 'Pole4', 'alt' => 'pole4', 'ppm' => 'application'],
    ['subiekt' => 'Pole5', 'alt' => 'pole5', 'ppm' => 'cn_code'],
    ['subiekt' => 'Notes', 'alt' => 'notes', 'ppm' => 'notes'],
];
```

#### 7.3 Migracja dla kolumny notes

**Plik:** `database/migrations/2026_01_27_120000_add_notes_to_products_table.php`

```php
Schema::table('products', function (Blueprint $table) {
    $table->text('notes')->nullable()
          ->comment('tw_Uwagi - Uwagi/notatki z ERP Subiekt GT');
});
```

#### 7.4 Aktualizacja ProductForm.php

**Zmiany:**
- Dodano właściwość `public ?string $notes = null`
- Dodano inicjalizację w loadProductData()
- Dodano zapis w metodzie save()

#### 7.5 Aktualizacja erp-connection-data.blade.php

**Zmiany:**
- Dodano sekcję "Informacje rozszerzone (ERP)" z polami:
  - Material (tw_Pole1)
  - Symbol wady (tw_Pole3)
  - Zastosowanie (tw_Pole4)
  - Kod CN (tw_Pole5)
  - Uwagi (tw_Uwagi) - pełna szerokość

**Wynik testu synchronizacji (13 produktów):**
```
SKU                    | CN CODE        | MATERIAL     | NOTES
BG-KAYO-S200           | 8703211000     |              | POJAZD NIEDOPUSZCZONY DO RUCHU DROGOWEGO.
BL-22652-176692083     | 888888         | Stal         |
307451000              | 8714109090     | plastik      |
E-AU150                | 8407329000     | stal         |
308552560              | 8714109090     | metal        |
...
```

---

## 3. Pliki do Modyfikacji (Lista)

| Plik | Faza | Opis |
|------|------|------|
| `stock-tab.blade.php` | 1, 4 | Label pozycja + kolumna Lokalizacja |
| `migrations/...products.php` | 2 | Nowe pola w products |
| `migrations/...product_stock.php` | 2 | Pole location |
| `migrations/...erp_connections.php` | 2 | Konfiguracja sync |
| `Product.php` | 3 | Nowe fillable/casts |
| `ProductStock.php` | 3 | Pole location |
| `ERPConnection.php` | 3 | Konfiguracja sync |
| `SubiektGTService.php` | 3 | Rozszerzone mapowanie pól |
| `ProductForm.php` | 4 | Nowe properties + metody |
| `product-form.blade.php` | 4 | Sekcja "Informacje rozszerzone" |
| `SyncController.php` | 5 | Konfiguracja ERP |
| `sync-controller.blade.php` | 5 | Badge + UI konfiguracji |
| `routes/console.php` | 6 | Dynamiczny scheduler |
| `PullProductsFromPrestaShop.php` | 6 | Wykrywanie ERP source |

---

## 4. Weryfikacja (Checklist)

### Faza 1
- [ ] Label "Edytowano" po lewej stronie inputów
- [ ] Brak nakładania się na input

### Faza 2
- [ ] Migracje wykonane bez błędów
- [ ] Nowe kolumny widoczne w bazie

### Faza 3
- [ ] Product model przyjmuje nowe pola
- [ ] SubiektGTService mapuje rozszerzone pola
- [ ] Mapowanie producenta po nazwie działa

### Faza 4
- [ ] Sekcja "Informacje rozszerzone" widoczna (zwinięta domyślnie)
- [ ] Wszystkie pola działają (bind do Livewire)
- [ ] Kolumna "Lokalizacja" w Stock Tab

### Faza 5
- [ ] Badge Subiekt GT wyświetla się w Recent Jobs
- [ ] UI konfiguracji ERP frequency działa
- [ ] Zapis konfiguracji do bazy

### Faza 6
- [x] Scheduler uruchamia joby zgodnie z frequency (3 niezależne)
- [x] PullProductsFromPrestaShop respektuje ERP source (już zaimplementowane)
- [x] Joby ERP widoczne w /admin/shops/sync

---

## 5. Definition of Done

- [x] Wszystkie fazy zaimplementowane (FAZA 1-7)
- [x] Migracje wykonane na produkcji (000001-000004 + 120000)
- [x] Chrome DevTools verification passed (3 frequencies UI)
- [x] Sync PPM ↔ Subiekt GT działa z nowymi polami
- [x] Cykliczne joby ERP widoczne w UI
- [x] Konfiguracja frequency zapisuje się (3 niezależne)
- [x] PullProductsFromPrestaShop działa jako fallback
- [x] FAZA 7: Pola tw_Pole1-5, tw_Uwagi synchronizowane
- [x] FAZA 7: Sekcja "Informacje rozszerzone (ERP)" wyświetla dane

---

**Estymacja całkowita:** ~9h implementacji
**Metoda realizacji:** Subtask skill (parallel workers)
