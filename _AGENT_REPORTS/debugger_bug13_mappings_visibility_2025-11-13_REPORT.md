# RAPORT PRACY AGENTA: debugger
**Data**: 2025-11-13
**Agent**: debugger (Expert Debugger)
**Zadanie**: Diagnoza braku widoczności liczników mapowań na liście sklepów w `/admin/shops`

---

## PODSUMOWANIE

**STATUS**: ✅ ROOT CAUSE ZIDENTYFIKOWANY

**PROBLEM**: Liczniki mapowań (Ceny + Magazyny) nie są widoczne na liście sklepów pomimo prawidłowego deployment frontendu.

**ROOT CAUSE**: **ARCHITEKTURALNA ROZBIEŻNOŚĆ** - Mapowania są zapisywane w ODDZIELNYCH TABELACH (`prestashop_shop_price_mappings`, `prestashop_shop_warehouse_mappings`), ale Blade template próbuje czytać z pustych kolumn JSON w tabeli `prestashop_shops`.

---

## DIAGNOZA KROK PO KROKU

### 1. Weryfikacja Blade Template

**Plik**: `resources/views/livewire/admin/shops/shop-manager.blade.php`

**Składnia (linie 474, 485)**:
```blade
{{ is_array($shop->price_group_mappings) ? count($shop->price_group_mappings) : 0 }}
{{ is_array($shop->warehouse_mappings) ? count($shop->warehouse_mappings) : 0 }}
```

**Status**: ✅ PRAWIDŁOWA - bezpieczny odczyt z array z fallback na 0

---

### 2. Weryfikacja Model Casting

**Plik**: `app/Models/PrestaShopShop.php` (linie 130-153)

**Casts**:
```php
protected $casts = [
    'price_group_mappings' => 'array',
    'warehouse_mappings' => 'array',
    // ... inne casts
];
```

**Status**: ✅ PRAWIDŁOWE - kolumny JSON castowane na array

---

### 3. Weryfikacja Danych w Bazie Produkcyjnej

**Komenda**:
```powershell
php artisan tinker --execute="
    \$shop = \App\Models\PrestaShopShop::first();
    echo 'Price mappings (type): ' . gettype(\$shop->price_group_mappings) . '\n';
    echo 'Count: ' . (is_array(\$shop->price_group_mappings) ? count(\$shop->price_group_mappings) : 0) . '\n';
"
```

**Wynik**:
```
Shop: B2B Test DEV (ID: 1)
Price mappings (type): array
Count: 0
Warehouse mappings (type): array
Count: 0
```

**Status**: ❌ KOLUMNY JSON SĄ PUSTE (empty array)

---

### 4. Odkrycie Rzeczywistej Lokalizacji Danych

**Analiza `AddShop.php` (linia 756)**:
```php
$this->savePriceMappings($shop->id);
```

**Metoda `savePriceMappings()` (linie 614-649)**:
```php
protected function savePriceMappings(int $shopId)
{
    // Delete existing mappings
    \DB::table('prestashop_shop_price_mappings')
        ->where('prestashop_shop_id', $shopId)
        ->delete();

    // Insert new mappings
    foreach ($this->priceGroupMappings as $psGroupId => $ppmGroupName) {
        if (!empty($ppmGroupName)) {
            \DB::table('prestashop_shop_price_mappings')->insert([
                'prestashop_shop_id' => $shopId,
                'prestashop_price_group_id' => $psGroupId,
                'prestashop_price_group_name' => $psGroupName,
                'ppm_price_group_name' => $ppmGroupName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

**KRYTYCZNE ODKRYCIE**: Mapowania są zapisywane do **ODDZIELNEJ TABELI** `prestashop_shop_price_mappings`, a NIE do kolumny JSON `prestashop_shops.price_group_mappings`!

---

### 5. Weryfikacja Rzeczywistych Danych w Tabeli Mappings

**Komenda**:
```powershell
php artisan tinker --execute="
    \$mappings = \DB::table('prestashop_shop_price_mappings')
        ->where('prestashop_shop_id', 1)
        ->get();
    echo 'Mappings count: ' . \$mappings->count() . '\n';
"
```

**Wynik**:
```
Mappings count: 9
  - PS Group 1 (➖Odwiedzający) -> Detaliczna
  - PS Group 2 (➖Gość) -> Detaliczna
  - PS Group 3 (➖Klient) -> Detaliczna
  - PS Group 7 (👀 Dealer Standard) -> Dealer Standard
  - PS Group 8 (👀 Dealer Premium) -> Dealer Premium
  - PS Group 31 (👀 Szkółka-Komis-Drop) -> Szkółka-Komis-Drop
  - PS Group 35 (👀 Warsztat) -> Warsztat
  - PS Group 37 (♾️ MPP) -> Pracownik
  - PS Group 39 (👀Warsztat Premium) -> Warsztat Premium
```

**Status**: ✅ DANE ISTNIEJĄ - 9 mapowań cen, prawdopodobnie podobnie magazyny

---

## ROOT CAUSE ANALYSIS

### Architektura Danych

**OBECNA IMPLEMENTACJA** (backend):
- Mapowania zapisywane do tabel:
  - `prestashop_shop_price_mappings` (9 records)
  - `prestashop_shop_warehouse_mappings` (prawdopodobnie N records)
- Kolumny JSON w `prestashop_shops` pozostają puste:
  - `price_group_mappings` = `[]`
  - `warehouse_mappings` = `[]`

**FRONTEND EXPECTATION** (Blade):
- Próbuje czytać z kolumn JSON:
  - `$shop->price_group_mappings` (empty array)
  - `$shop->warehouse_mappings` (empty array)
- Wyświetla `count() = 0`

### Diagram Rozbieżności

```
[AddShop Component]
       ↓ saveShop()
       ↓ savePriceMappings()
       ↓
[prestashop_shop_price_mappings TABLE] ← ✅ DATA HERE (9 records)
       ↑
       X NOT SYNCED
       ↓
[prestashop_shops.price_group_mappings JSON] ← ❌ EMPTY ARRAY
       ↑
       ↓ read by
[Blade Template] → displays "0"
```

---

## MOŻLIWE ROZWIĄZANIA

### Opcja A: Sync JSON Columns (RECOMMENDED)

**OPIS**: Po zapisaniu mapowań do tabel, zsynchronizuj liczniki do kolumn JSON

**IMPLEMENTACJA**:
```php
// app/Http/Livewire/Admin/Shops/AddShop.php
protected function savePriceMappings(int $shopId)
{
    // Existing code...
    \DB::table('prestashop_shop_price_mappings')->insert([...]);

    // NEW: Sync count to JSON column
    $count = \DB::table('prestashop_shop_price_mappings')
        ->where('prestashop_shop_id', $shopId)
        ->count();

    PrestaShopShop::where('id', $shopId)->update([
        'price_group_mappings' => array_fill(0, $count, ['synced' => true])
    ]);
}
```

**PROS**:
- Minimal changes (tylko backend update)
- Frontend pozostaje bez zmian
- Fast display (no query per shop)

**CONS**:
- JSON column zawiera "dummy data" tylko dla count
- Redundant storage (data w 2 miejscach)

---

### Opcja B: Computed Attribute (CLEAN ARCHITECTURE)

**OPIS**: Dodaj computed attributes do modelu, które liczą z relacji

**IMPLEMENTACJA**:
```php
// app/Models/PrestaShopShop.php

/**
 * Get price group mappings relation
 */
public function priceGroupMappings(): HasMany
{
    return $this->hasMany(PriceGroupMapping::class, 'prestashop_shop_id');
}

/**
 * Get warehouse mappings relation
 */
public function warehouseMappings(): HasMany
{
    return $this->hasMany(WarehouseMapping::class, 'prestashop_shop_id');
}

/**
 * Computed: Price mappings count
 */
public function getPriceMappingsCountAttribute(): int
{
    return $this->priceGroupMappings()->count();
}

/**
 * Computed: Warehouse mappings count
 */
public function getWarehouseMappingsCountAttribute(): int
{
    return $this->warehouseMappings()->count();
}
```

**Blade Update**:
```blade
<!-- OLD -->
{{ is_array($shop->price_group_mappings) ? count($shop->price_group_mappings) : 0 }}

<!-- NEW -->
{{ $shop->price_mappings_count }}
```

**PROS**:
- Clean architecture (single source of truth)
- No data redundancy
- Maintainable

**CONS**:
- N+1 query problem (bez eager loading)
- Wymaga zmian w Blade template

---

### Opcja C: Eager Loading with Counts (PERFORMANCE)

**OPIS**: Load counts w query zamiast computed attributes

**IMPLEMENTACJA**:
```php
// app/Http/Livewire/Admin/Shops/ShopManager.php (render method)

public function render()
{
    $shops = PrestaShopShop::withCount([
        'priceGroupMappings',
        'warehouseMappings'
    ])->get();

    return view('livewire.admin.shops.shop-manager', [
        'shops' => $shops
    ]);
}
```

**Blade Update**:
```blade
{{ $shop->price_group_mappings_count }}
{{ $shop->warehouse_mappings_count }}
```

**PROS**:
- BEST PERFORMANCE (single query)
- Clean architecture
- No N+1 problem

**CONS**:
- Wymaga zmian w controller + Blade
- Musi zdefiniować relacje w model

---

## REKOMENDACJA

### 🏆 OPCJA C: Eager Loading with Counts

**UZASADNIENIE**:
1. ✅ **PERFORMANCE** - Single query z LEFT JOIN, no N+1
2. ✅ **CLEAN** - Single source of truth (table mappings)
3. ✅ **MAINTAINABLE** - Standard Laravel pattern (withCount)
4. ✅ **SCALABLE** - Works with pagination
5. ✅ **TYPE-SAFE** - Integer counts (not array)

**IMPLEMENTACJA (3 kroki)**:

**KROK 1**: Dodaj relacje do model
**KROK 2**: Update render() w ShopManager
**KROK 3**: Update Blade template

---

## NASTĘPNE KROKI

### Dla laravel-expert:

1. **Dodaj relacje do `PrestaShopShop` model**:
   ```php
   public function priceGroupMappings(): HasMany
   {
       return $this->hasMany(
           \App\Models\PriceGroupMapping::class,  // Create if not exists
           'prestashop_shop_id',
           'id'
       );
   }

   public function warehouseMappings(): HasMany
   {
       return $this->hasMany(
           \App\Models\WarehouseMapping::class,  // Create if not exists
           'prestashop_shop_id',
           'id'
       );
   }
   ```

2. **Utwórz Eloquent models dla mappings tabel**:
   - `app/Models/PriceGroupMapping.php`
   - `app/Models/WarehouseMapping.php`

3. **Update `ShopManager::render()`**:
   ```php
   public function render()
   {
       $shops = PrestaShopShop::query()
           ->withCount(['priceGroupMappings', 'warehouseMappings'])
           ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
           ->orderBy($this->sortBy, $this->sortDirection)
           ->get();

       return view('livewire.admin.shops.shop-manager', compact('shops'));
   }
   ```

### Dla frontend-specialist:

4. **Update Blade template** (linie 474, 485):
   ```blade
   <!-- OLD -->
   {{ is_array($shop->price_group_mappings) ? count($shop->price_group_mappings) : 0 }}

   <!-- NEW -->
   {{ $shop->price_group_mappings_count ?? 0 }}
   ```

### Dla deployment-specialist:

5. **Deploy changes**:
   - Upload updated files
   - Clear cache: `php artisan view:clear && cache:clear`
   - Verify on production

---

## PLIKI DIAGNOSTYCZNE

📁 Utworzone skrypty diagnostyczne:
- `_TEMP/diagnose_mappings_visibility.php` - Diagnostyka mapowań (lokalna)
- `_TEMP/check_mappings_production.ps1` - Diagnostyka produkcyjna
- `_TEMP/check_integration_mappings.ps1` - Check IntegrationMapping table
- `_TEMP/check_price_mappings_table.ps1` - Check prestashop_shop_price_mappings

---

## WERYFIKACJA PO FIX

```powershell
# 1. SSH to production
plink ... "cd domains/.../public_html && php artisan tinker --execute=\"
    \$shop = \App\Models\PrestaShopShop::withCount(['priceGroupMappings', 'warehouseMappings'])->first();
    echo 'Price mappings count: ' . \$shop->price_group_mappings_count . '\n';
    echo 'Warehouse mappings count: ' . \$shop->warehouse_mappings_count . '\n';
\""

# Expected output: Price mappings count: 9

# 2. Browser verification
# Visit https://ppm.mpptrade.pl/admin/shops
# Should see badges: "Ceny: 9" + "Magazyny: X"
```

---

## LOGI DIAGNOSTYCZNE

### Produkcja - Kolumny JSON (PUSTE)
```
Shop: B2B Test DEV (ID: 1)
Price mappings (type): array
Count: 0
Warehouse mappings (type): array
Count: 0
```

### Produkcja - Tabela Mappings (DANE ISTNIEJĄ)
```
Mappings count: 9
  - PS Group 1 (➖Odwiedzający) -> Detaliczna
  - PS Group 2 (➖Gość) -> Detaliczna
  - PS Group 3 (➖Klient) -> Detaliczna
  - PS Group 7 (👀 Dealer Standard) -> Dealer Standard
  - PS Group 8 (👀 Dealer Premium) -> Dealer Premium
  - PS Group 31 (👀 Szkółka-Komis-Drop) -> Szkółka-Komis-Drop
  - PS Group 35 (👀 Warsztat) -> Warsztat
  - PS Group 37 (♾️ MPP) -> Pracownik
  - PS Group 39 (👀Warsztat Premium) -> Warsztat Premium
```

---

## PODSUMOWANIE TECHNICZNE

**PROBLEM**: Architectural mismatch między storage (separate tables) i display (JSON columns)

**ROZWIĄZANIE**: Eager loading z Laravel `withCount()` dla optymalnej performance

**IMPACT**: Minimal (3 pliki zmian), High readability, Best performance

**ESTYMACJA**: 30 minut implementacji + 15 minut testów

---

**Raport wygenerowany**: 2025-11-13
**Agent**: debugger (Expert Software Debugger)
**Status**: ✅ ROOT CAUSE IDENTIFIED + SOLUTION RECOMMENDED
