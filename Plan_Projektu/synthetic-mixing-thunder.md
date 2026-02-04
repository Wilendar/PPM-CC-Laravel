# PLAN: System Statusów Zgodności Danych w Product List

**Data:** 2026-02-04
**ETAP:** Product List Enhancement
**Status:** ✅ Ukończone (2026-02-04)

---

## 1. CEL

Rozbudowa kolumny statusu w Product List (/admin/products) o szczegółowe informacje o rozbieżnościach danych między PPM a integracjami (sklepy PrestaShop, systemy ERP).

**Korzyść:** Użytkownik na pierwszy rzut oka widzi czy produkt wymaga uwagi ZANIM wejdzie w szczegóły.

---

## 2. STATUSY DO MONITOROWANIA

### 2.1 Rozbieżności per integracja (Shop/ERP)

| Status | Opis | Monitorowane pola |
|--------|------|-------------------|
| **Informacje podstawowe** | Różnica w podstawowych danych | name, manufacturer, tax_rate, is_active |
| **Opisy** | Różnica w opisach | short_description, long_description |
| **Właściwości fizyczne** | Różnica w wymiarach/wadze | weight, height, width, length |
| **Atrybuty** | TYLKO dla product_type "Pojazd" | attributes mapping |
| **Dopasowania** | TYLKO dla product_type "Część zamienna" | compatibility data |
| **Zdjęcia** | Brak przypisania do integracji | media prestashop_mapping |

**Pola ignorowane:**
- Informacje podstawowe: supplier_code, ean, sort_order, categories
- Opisy: meta_title, meta_description (SEO)

### 2.2 Statusy globalne (produkt)

| Status | Warunek |
|--------|---------|
| **Cena 0,00** | Cena = 0 w aktywnej grupie cenowej (PriceGroup.is_active=true) |
| **Poniżej stanu min** | available_quantity < minimum_stock w domyślnym magazynie |
| **Brak zdjęć** | Product bez żadnych aktywnych media |
| **Brak w PrestaShop** | Produkt bez powiązania z żadnym sklepem |

### 2.3 Statusy wariantów

| Status | Warunek |
|--------|---------|
| **Wariant bez zdjęć** | Wariant bez przypisanych images |
| **Wariant cena 0** | Wariant z ceną 0 w aktywnej grupie |
| **Wariant poniżej min** | Wariant z available < minimum w domyślnym magazynie |

---

## 3. ARCHITEKTURA ROZWIĄZANIA

### 3.1 Nowy Service: `ProductStatusAggregator`

**Lokalizacja:** `app/Services/Product/ProductStatusAggregator.php`

**Odpowiedzialności:**
- Agregacja wszystkich statusów per produkt
- Batch processing dla wydajności
- Cache management
- Reuse logiki porównania z ProductForm

```
Product → ProductStatusAggregator → ProductStatusDTO → Blade Component
```

### 3.2 DTO: `ProductStatusDTO`

**Lokalizacja:** `app/DTOs/ProductStatusDTO.php`

Struktura:
- `globalIssues[]` - cena 0, stan min, brak zdjęć, brak w PS
- `shopIssues[shop_id => ['basic', 'desc', 'physical', 'images']]`
- `erpIssues[erp_id => ['basic', 'desc', 'physical']]`
- `variantIssues[variant_id => ['no_images', 'zero_price', 'low_stock']]`
- Helper methods: `hasAnyIssues()`, `getSeverity()`, `getIssueCount()`

### 3.3 Config: `config/product-status.php`

Konfiguracja:
- Ignorowane pola per grupa
- Włączenie/wyłączenie conditional checks (atrybuty, dopasowania)
- Cache TTL
- Product type slugs dla conditional checks

---

## 4. UI - KOMPAKTOWA KOLUMNA STATUSU

### 4.1 Struktura wizualna

```
┌─────────────────────────────────────────┐
│ [🔴] [⚠️] [📦3]  [🛒 2] [⚙️ 1]          │
│  ↑    ↑    ↑      ↑      ↑              │
│  │    │    │      │      └─ ERP issues  │
│  │    │    │      └─ Shop issues        │
│  │    │    └─ Variant issues            │
│  │    └─ Global warnings                │
│  └─ Critical (cena 0, brak w PS)        │
└─────────────────────────────────────────┘
```

### 4.2 Ikony statusów globalnych

| Ikona | Kolor | Status |
|-------|-------|--------|
| 💰 | Czerwony | Cena 0,00 |
| 📦 | Żółty | Poniżej stanu min |
| 🖼️ | Pomarańcz | Brak zdjęć |
| 🛒 | Szary | Brak w PrestaShop |
| ✓ | Zielony | Wszystko OK |

### 4.3 Badge integracji z problemami

Zgodnie z INTEGRATION_LABELS.md - każda integracja ma swój kolor:
- PrestaShop shops: Cyan (#06b6d4) + ikona shopping-cart
- Subiekt GT: Orange (#f97316) + ikona database
- BaseLinker: Green (#22c55e) + ikona shopping-bag

Badge pokazuje liczbę problemów, tooltip listuje szczegóły.

### 4.4 Popover Component (Alpine.js)

Rozbudowany popover wyświetlający szczegóły problemów:

```blade
<div x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" class="relative">
    <!-- Trigger (ikona/badge) -->
    <button class="...">
        <x-product-status-icon :type="$type" />
    </button>

    <!-- Popover Content -->
    <div x-show="open" x-transition class="absolute z-50 ...">
        <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700 p-3 min-w-[200px]">
            <h4 class="text-sm font-medium text-white mb-2">Problemy z produktem</h4>
            <ul class="space-y-1 text-xs">
                @foreach($issues as $issue)
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $issue['color'] }}"></span>
                        {{ $issue['label'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
```

**Zawartość popover:**
- Lista problemów z ikonami kolorów (czerwony/żółty/pomarańcz)
- Nazwa integracji gdzie występuje problem
- Opcjonalnie: link "Edytuj" do ProductForm

### 4.5 Filtrowanie po statusach

Nowe filtry w panelu filtrów ProductList:

| Filtr | Wartości |
|-------|----------|
| **Status zgodności** | Wszystkie, Tylko z problemami, Zgodne |
| **Typ problemu** | Multi-select: Cena 0, Poniżej stanu min, Brak zdjęć, Rozbieżność danych, Brak w PS |

```php
// ProductList.php - nowe properties
public ?string $dataStatusFilter = null;  // 'all', 'issues', 'ok'
public array $issueTypeFilters = [];      // ['zero_price', 'low_stock', 'no_images', ...]
```

### 4.6 Panel konfiguracji Admin

Nowa sekcja w `/admin/product-parameters` lub `/admin/settings`:

**"Monitorowanie zgodności produktów"**

Checkboxy do włączenia/wyłączenia monitorowania:
- [ ] Informacje podstawowe (name, manufacturer, tax_rate)
- [ ] Opisy produktów
- [ ] Właściwości fizyczne (wymiary, waga)
- [ ] Atrybuty (tylko Pojazd)
- [ ] Dopasowania (tylko Część zamienna)
- [ ] Zdjęcia

**Pola ignorowane:**
- Multi-select: supplier_code, ean, sort_order, meta_title, meta_description, categories

Dane zapisywane w tabeli `settings` lub `product_status_config`

---

## 5. EAGER LOADING - ROZSZERZENIE

Aktualizacja `buildProductQuery()` w ProductList.php:

```php
->with([
    // Existing
    'productType:id,name,slug',
    'shopData:id,product_id,shop_id,sync_status,...,name,short_description,long_description,weight,height,width,length',
    'shopData.shop:id,name,label_color,label_icon',

    // NEW: ERP data for comparison
    'erpData:id,product_id,erp_connection_id,sync_status,...',
    'erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon',

    // NEW: Prices for zero-price check
    'prices:id,product_id,price_group_id,price_net',
    'prices.priceGroup:id,is_active',

    // NEW: Stock for low-stock check
    'stock:id,product_id,warehouse_id,quantity,reserved_quantity,minimum_stock',
    'stock.warehouse:id,is_default',

    // Existing + enhanced
    'media:id,mediable_id,mediable_type,is_primary,is_active,prestashop_mapping',
    'variants:id,product_id,sku,is_active',
    'variants.images:id,variant_id',
    'variants.prices:id,variant_id,price_group_id,price_net',
    'variants.stock:id,variant_id,warehouse_id,quantity,minimum_stock',
])
```

---

## 6. PLIKI DO UTWORZENIA/MODYFIKACJI

### 6.1 Nowe pliki

| Plik | Opis |
|------|------|
| `app/Services/Product/ProductStatusAggregator.php` | Service agregujący statusy |
| `app/DTOs/ProductStatusDTO.php` | Data Transfer Object |
| `app/Models/ProductStatusConfig.php` | Model konfiguracji (opcjonalnie, jeśli nie settings) |
| `database/migrations/xxx_create_product_status_config.php` | Migracja dla konfiguracji |
| `resources/views/components/product-status-icon.blade.php` | Ikona statusu |
| `resources/views/components/integration-status-badge.blade.php` | Badge integracji |
| `resources/views/components/product-status-popover.blade.php` | Popover z listą problemów |
| `resources/views/livewire/products/listing/partials/status-column.blade.php` | Partial kolumny |
| `resources/views/livewire/products/listing/partials/status-filters.blade.php` | Filtry statusów |

### 6.2 Modyfikacje

| Plik | Zmiana |
|------|--------|
| `app/Http/Livewire/Products/Listing/ProductList.php` | Rozszerzenie eager loading, computed property, filtry |
| `resources/views/livewire/products/listing/product-list.blade.php` | ZASTĄPIENIE kolumny PrestaShop Sync nową, filtry |
| `resources/css/admin/components.css` | Style dla statusów i popover |
| `app/Http/Livewire/Admin/ProductParameters.php` | Sekcja konfiguracji monitorowania |
| `resources/views/livewire/admin/product-parameters.blade.php` | UI konfiguracji |

---

## 7. WYDAJNOŚĆ

### 7.1 Cache Strategy

- Cache key: `product_status_{id}_{updated_at_timestamp}`
- TTL: 5 minut (konfigurowalne)
- Invalidacja: Event-driven (Product/ShopData/ErpData/Price/Stock updated)

### 7.2 Batch Processing

- Agregacja statusów dla całej strony produktów naraz
- Unikanie N+1 przez proper eager loading
- Limit relacji do niezbędnych pól (select)

---

## 8. DECYZJE PODJĘTE ✅

| Pytanie | Decyzja |
|---------|---------|
| **Kolumna UI** | ZASTĄPIĆ kolumnę "PrestaShop Sync" |
| **Tooltip** | Rozbudowany popover (Alpine.js) |
| **Filtrowanie** | TAK - dodać filtry statusów |
| **Konfiguracja** | Panel admin (user-configurable)

---

## 9. KOLEJNOŚĆ IMPLEMENTACJI

### Faza 1: Backend Core ✅
1. [x] Wykorzystanie istniejącej tabeli `system_settings` (kategoria: 'product', klucz: 'product_status_config')
2. [x] DTO `ProductStatusDTO` → `app/DTOs/ProductStatusDTO.php`
3. [x] Service `ProductStatusAggregator` → `app/Services/Product/ProductStatusAggregator.php`

### Faza 2: ProductList Integration ✅
4. [x] Rozszerzenie eager loading w `buildProductQuery()` → `ProductList.php`
5. [x] Computed property `productStatuses` w ProductList
6. [x] Properties i metody dla filtrów statusów (`$dataStatusFilter`, `$issueTypeFilters`)

### Faza 3: UI Components ✅
7. [x] Blade component `product-status-icon` → `resources/views/components/product-status-icon.blade.php`
8. [x] Blade component `integration-status-badge` → `resources/views/components/integration-status-badge.blade.php`
9. [x] Blade component `product-status-popover` (Alpine.js) → `resources/views/components/product-status-popover.blade.php`
10. [x] Partial `status-column.blade.php` → `resources/views/livewire/products/listing/partials/status-column.blade.php`
11. [x] Partial `status-filters.blade.php` → `resources/views/livewire/products/listing/partials/status-filters.blade.php`

### Faza 4: View Integration ✅
12. [x] ZASTĄPIENIE kolumny "PrestaShop Sync" w product-list.blade.php → "Zgodność"
13. [x] Dodanie filtrów statusów do panelu filtrów
14. [x] CSS styling (popover, badges, ikony) - wykorzystanie istniejących klas Tailwind

### Faza 5: Admin Configuration ✅
15. [x] Sekcja konfiguracji w ProductParameters → zakładka "Monitorowanie zgodności"
16. [x] Komponent `StatusMonitoringConfig` → `app/Http/Livewire/Admin/Parameters/StatusMonitoringConfig.php`
17. [x] Widok konfiguracji → `resources/views/livewire/admin/parameters/status-monitoring-config.blade.php`

### Faza 6: Optymalizacja ✅
18. [x] Cache dla statusów (z TTL 5 min, klucz z timestamp)
19. [x] Observer `ProductStatusCacheObserver` → `app/Observers/ProductStatusCacheObserver.php`
20. [x] Rejestracja observerów w `AppServiceProvider.php`

---

## 11. ROZSZERZENIE: Wskaźniki Wszystkich Integracji (2026-02-04)

**Status:** 🛠️ W trakcie planowania

### 11.1 Problem

Aktualnie kolumna "Zgodność" pokazuje ikony integracji **TYLKO gdy są problemy**. Jeśli produkt jest podłączony do 3 sklepów PrestaShop i 2 systemów ERP, a wszystko jest OK - widać tylko zielony checkmark ✓.

**Użytkownik nie wie:**
- Do KTÓRYCH integracji jest podłączony produkt
- Czy produkt jest ZSYNCHRONIZOWANY ze wszystkimi sklepami
- Które integracje są aktywne dla danego produktu

### 11.2 Cel

Pokazać **WSZYSTKIE integracje** produktu w kolumnie statusu:
- ✅ Zielone badge = integracja OK (zsynchronizowana, bez problemów)
- ⚠️ Żółte/czerwone badge = integracja z problemami (jak obecnie)

### 11.3 Proponowana Struktura Wizualna

**Opcja A: Kompaktowe ikony (REKOMENDOWANA)**
```
┌────────────────────────────────────────────────────────┐
│ [🛒✓] [🛒✓] [🛒⚠2] [🏢✓] [🏢⚠1]                       │
│   ↑      ↑      ↑      ↑      ↑                        │
│   │      │      │      │      └─ Subiekt GT (1 problem)│
│   │      │      │      └─ BaseLinker OK                │
│   │      │      └─ B2B Test DEV (2 problemy)           │
│   │      └─ Sklep 2 OK                                 │
│   └─ Sklep 1 OK                                        │
└────────────────────────────────────────────────────────┘
```

**Opcja B: Zgrupowane po typie**
```
┌────────────────────────────────────────────────────────┐
│ PS: [✓2] [⚠1]    ERP: [✓1] [⚠1]                        │
│      ↑     ↑           ↑     ↑                         │
│      │     │           │     └─ 1 ERP z problemem      │
│      │     │           └─ 1 ERP OK                     │
│      │     └─ 1 sklep z problemami                     │
│      └─ 2 sklepy OK                                    │
└────────────────────────────────────────────────────────┘
```

### 11.4 Wymagane Zmiany

#### 11.4.1 ProductStatusDTO (rozszerzenie)

```php
// NOWE POLA:
public array $connectedShops = [];    // [shop_id => ['name', 'color', 'icon', 'hasIssues']]
public array $connectedErps = [];     // [erp_id => ['name', 'color', 'icon', 'hasIssues']]

// Helper methods:
public function getShopsWithoutIssues(): array;
public function getErpsWithoutIssues(): array;
public function getAllConnectedIntegrations(): array;
```

#### 11.4.2 ProductStatusAggregator (rozszerzenie)

```php
// W aggregateForProduct():
private function collectConnectedIntegrations(Product $product): void
{
    // Zbierz WSZYSTKIE shopData (nie tylko te z problemami)
    foreach ($product->shopData as $shopData) {
        $this->dto->connectedShops[$shopData->shop_id] = [
            'name' => $shopData->shop->name,
            'color' => $shopData->shop->label_color ?? '06b6d4',
            'icon' => $shopData->shop->label_icon ?? 'shopping-cart',
            'hasIssues' => isset($this->dto->shopIssues[$shopData->shop_id]),
        ];
    }

    // Zbierz WSZYSTKIE erpData
    foreach ($product->erpData as $erpData) {
        $this->dto->connectedErps[$erpData->erp_connection_id] = [
            'name' => $erpData->erpConnection->instance_name,
            'color' => $erpData->erpConnection->label_color ?? 'f97316',
            'icon' => $erpData->erpConnection->label_icon ?? 'database',
            'hasIssues' => isset($this->dto->erpIssues[$erpData->erp_connection_id]),
        ];
    }
}
```

#### 11.4.3 integration-status-badge.blade.php (rozszerzenie)

```blade
@props([
    'type' => 'shop',       // shop | erp
    'name' => '',
    'color' => '06b6d4',
    'icon' => 'shopping-cart',
    'hasIssues' => false,   // NOWE
    'issueCount' => 0,
    'issues' => [],
])

@php
    $bgOpacity = $hasIssues ? '40' : '20';
    $borderClass = $hasIssues ? 'border border-current' : '';
    $statusIcon = $hasIssues ? null : 'check'; // checkmark dla OK
@endphp

<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium {{ $borderClass }}"
      style="background-color: #{{ $color }}{{ $bgOpacity }}; color: #{{ $color }};"
      title="{{ $name }}{{ $hasIssues ? ': ' . implode(', ', $issues) : ' - OK' }}">
    {{-- Ikona integracji --}}
    <x-dynamic-component :component="'heroicon-o-' . $icon" class="w-3 h-3" />

    {{-- Status: checkmark lub liczba problemów --}}
    @if($hasIssues && $issueCount > 0)
        <span class="text-[10px]">{{ $issueCount }}</span>
    @else
        <x-heroicon-o-check class="w-3 h-3" />
    @endif
</span>
```

#### 11.4.4 status-column.blade.php (modyfikacja)

```blade
{{-- NOWA SEKCJA: Wszystkie integracje (OK + problemy) --}}
<div class="flex flex-wrap gap-1">
    {{-- Sklepy PrestaShop --}}
    @foreach($status->connectedShops as $shopId => $shop)
        <x-integration-status-badge
            type="shop"
            :name="$shop['name']"
            :color="$shop['color']"
            :icon="$shop['icon']"
            :hasIssues="$shop['hasIssues']"
            :issueCount="count($status->shopIssues[$shopId] ?? [])"
            :issues="$status->shopIssues[$shopId] ?? []"
        />
    @endforeach

    {{-- Systemy ERP --}}
    @foreach($status->connectedErps as $erpId => $erp)
        <x-integration-status-badge
            type="erp"
            :name="$erp['name']"
            :color="$erp['color']"
            :icon="$erp['icon']"
            :hasIssues="$erp['hasIssues']"
            :issueCount="count($status->erpIssues[$erpId] ?? [])"
            :issues="$status->erpIssues[$erpId] ?? []"
        />
    @endforeach

    {{-- Jeśli brak integracji --}}
    @if(empty($status->connectedShops) && empty($status->connectedErps))
        <span class="text-xs text-gray-500">Brak integracji</span>
    @endif
</div>
```

### 11.5 Pliki do modyfikacji

| Plik | Zmiana |
|------|--------|
| `app/DTOs/ProductStatusDTO.php` | Dodać `connectedShops`, `connectedErps`, helper methods |
| `app/Services/Product/ProductStatusAggregator.php` | Dodać `collectConnectedIntegrations()` |
| `resources/views/components/integration-status-badge.blade.php` | Obsługa stanu "OK" (zielony checkmark) |
| `resources/views/livewire/products/listing/partials/status-column.blade.php` | Wyświetlanie wszystkich integracji |

### 11.6 Weryfikacja

1. **Produkt z 2 sklepami OK + 1 z problemem:**
   - Powinny być widoczne 3 badge'y: 2 zielone z ✓, 1 z liczbą problemów

2. **Produkt bez integracji:**
   - Tekst "Brak integracji"

3. **Produkt ze wszystkimi OK:**
   - Wszystkie badge'y zielone z ✓ (bez dodatkowego globalnego checkmark)

4. **Tooltip na hover:**
   - OK: "Sklep internetowy - OK"
   - Problem: "B2B Test DEV: Dane podstawowe, Opisy"

### 11.7 Kolejność implementacji

1. [ ] 11.7.1: Rozszerzyć `ProductStatusDTO` o nowe pola
2. [ ] 11.7.2: Rozszerzyć `ProductStatusAggregator.aggregateForProduct()`
3. [ ] 11.7.3: Zaktualizować `integration-status-badge.blade.php`
4. [ ] 11.7.4: Zaktualizować `status-column.blade.php`
5. [ ] 11.7.5: Testować w Chrome DevTools na ppm.mpptrade.pl
6. [ ] 11.7.6: Commit & push

---

## 12. REFERENCJE

- **Labele integracji:** `.Release_docs/INTEGRATION_LABELS.md`
- **ProductList:** `app/Http/Livewire/Products/Listing/ProductList.php`
- **ProductForm validation:** `app/Http/Livewire/Products/Management/ProductForm.php` (metody getFieldStatus, getFieldStatusIndicator)
- **Modele:** Product, ProductShopData, ProductErpData, ProductPrice, ProductStock, PriceGroup, Warehouse
