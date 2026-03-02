# PPM - Integration Labels Documentation

> **Wersja:** 2.0.0
> **Data:** 2026-02-13
> **Status:** Production Ready
> **Changelog:** Przebudowa dokumentacji do standardu projektu PPM; pelna analiza kodu zrodlowego

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura Plikow](#2-architektura-plikow)
3. [Schema Bazy Danych](#3-schema-bazy-danych)
4. [Modele i Accessory](#4-modele-i-accessory)
5. [Paleta Kolorow i Ikon](#5-paleta-kolorow-i-ikon)
6. [Komponent integration-status-badge](#6-komponent-integration-status-badge)
7. [Sync Job Status Display](#7-sync-job-status-display)
8. [ProductStatusDTO - Warstwa Danych](#8-productstatusdto---warstwa-danych)
9. [Cache Invalidation](#9-cache-invalidation)
10. [Miejsca Uzycia w Kodzie](#10-miejsca-uzycia-w-kodzie)
11. [Wzorce Implementacji](#11-wzorce-implementacji)
12. [UI Konfiguracji](#12-ui-konfiguracji)
13. [Troubleshooting](#13-troubleshooting)
14. [Changelog](#14-changelog)

---

## 1. Overview

### 1.1 Opis modulu

System Integration Labels umozliwia personalizacje wygladu etykiet (badges) dla integracji ERP i sklepow PrestaShop w calej aplikacji PPM. Kazda integracja moze miec wlasny kolor hex i ikone SVG, ktore sa wyswietlane w kolumnie statusu listy produktow, panelu skanowania, formularzach edycji i modalach importu.

Kolory i ikony sa przechowywane w bazie danych na poziomie `erp_connections` i `prestashop_shops`, z automatycznym fallback do wartosci domyslnych per typ integracji. System obejmuje rowniez automatyczna invalidacje cache przy zmianie labeli oraz wyswietlanie statusu aktywnych jobow synchronizacji (pending/running).

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Tabele DB z label columns | 2 (`erp_connections`, `prestashop_shops`) |
| Modele z accessorami | 2 (`ERPConnection`, `PrestaShopShop`) |
| Blade Components | 2 (`integration-status-badge`, `product-status-popover`) |
| DTO | 1 (`ProductStatusDTO`) |
| Observer | 1 (`ProductStatusCacheObserver`) |
| Service | 1 (`ProductStatusAggregator`) |
| Migracje | 1 |
| Dostepne kolory | 14 |
| Dostepne ikony ERP | 12 |
| Dostepne ikony Shop | 12 |
| Miejsca uzycia w kodzie | 15+ plikow |
| Skill | 1 (`integration-labels`) |

### 1.3 Kluczowe funkcjonalnosci

- **Custom Colors** - 14 predefiniowanych kolorow hex do wyboru per integracja
- **Custom Icons** - 12 ikon SVG per typ (ERP/Shop) z podgledem w czasie rzeczywistym
- **Fallback System** - accessory z automatycznym fallback do domyslnych wartosci per erp_type
- **Sync Status Display** - dynamiczne badges pending/running z animacja spinner
- **Cache Invalidation** - automatyczne czyszczenie cache ProductStatusAggregator przy zmianie labeli
- **Eager Loading** - obowiazkowe ladowanie `label_color,label_icon` we wszystkich relacjach
- **Serialization** - label data wlaczane do JSON w Jobs (Scan, BulkSync)

---

## 2. Architektura Plikow

### 2.1 Backend - Modele i Logika

| Plik | Linie* | Opis |
|------|--------|------|
| `app/Models/ERPConnection.php` | ~799 | Stale LABEL_COLORS/LABEL_ICONS, accessory, getAvailableLabelColors/Icons |
| `app/Models/PrestaShopShop.php` | ~1077 | DEFAULT_LABEL_COLOR/ICON, accessory, getAvailableLabelColors/Icons |
| `app/DTOs/ProductStatusDTO.php` | ~461 | connectedShops/connectedErps z color/icon/syncStatus |
| `app/Observers/ProductStatusCacheObserver.php` | ~217 | shopUpdated(), erpConnectionUpdated() |
| `app/Services/Product/ProductStatusAggregator.php` | ~940 | invalidateCacheForShop(), invalidateCacheForErp() |
| `app/Providers/AppServiceProvider.php` | ~81 | Rejestracja observerow PrestaShopShop::updated, ERPConnection::updated |

### 2.2 Backend - Livewire i Jobs

| Plik | Opis |
|------|------|
| `app/Http/Livewire/Admin/ERP/ERPManager.php` | Formularz connectionForm z label_color/label_icon (krok 4) |
| `app/Http/Livewire/Admin/Shops/AddShop.php` | Formularz z labelColor/labelIcon properties |
| `app/Http/Livewire/Products/Listing/ProductList.php` | Eager loading z label fields |
| `app/Http/Livewire/Products/Import/Modals/DescriptionModal.php` | Ladowanie shopow z label_color/label_icon |
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerProductsTrait.php` | Eager loading w liscie produktow dostawcy |
| `app/Jobs/Scan/ScanProductLinksJob.php` | Serializacja label_color/label_icon do JSON |
| `app/Jobs/Scan/ScanMissingInSourceJob.php` | Eager loading z label fields |
| `app/Jobs/VisualEditor/BulkSyncDescriptionsJob.php` | Ladowanie shopow z label fields |

### 2.3 Frontend - Blade Views

| Plik | Opis |
|------|------|
| `resources/views/components/integration-status-badge.blade.php` | Glowny komponent badge (OK/issues/syncing) |
| `resources/views/components/product-status-popover.blade.php` | Popover ze szczegolami problemow per integracja |
| `resources/views/livewire/products/listing/partials/status-column.blade.php` | Kolumna statusu w ProductList |
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | UI konfiguracji labeli ERP (krok 4) |
| `resources/views/livewire/admin/scan/partials/results-table.blade.php` | Badges w wynikach skanowania |
| `resources/views/livewire/products/import/modals/description-modal.blade.php` | Badges shopow w modalu opisow |

### 2.4 Migracje i Skill

| Plik | Opis |
|------|------|
| `database/migrations/2026_02_03_150000_add_label_customization_to_integrations.php` | Dodanie kolumn label_color/label_icon |
| `.claude/skills/integration-labels/SKILL.md` | Skill wymuszajacy prawidlowe uzycie labels |

---

## 3. Schema Bazy Danych

### 3.1 Tabela: `erp_connections`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `label_color` | VARCHAR(7) | YES | NULL | Kolor hex badge (#RRGGBB) |
| `label_icon` | VARCHAR(50) | YES | NULL | Nazwa ikony SVG |

### 3.2 Tabela: `prestashop_shops`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `label_color` | VARCHAR(7) | YES | NULL | Kolor hex badge (#RRGGBB) |
| `label_icon` | VARCHAR(50) | YES | NULL | Nazwa ikony SVG |

### 3.3 Migracja

```php
// database/migrations/2026_02_03_150000_add_label_customization_to_integrations.php

Schema::table('erp_connections', function (Blueprint $table) {
    $table->string('label_color', 7)->nullable()->after('is_active')
        ->comment('Hex color for label badge (e.g., #f97316)');
    $table->string('label_icon', 50)->nullable()->after('label_color')
        ->comment('Icon name for label (e.g., database, cloud, server)');
});

Schema::table('prestashop_shops', function (Blueprint $table) {
    $table->string('label_color', 7)->nullable()->after('is_active')
        ->comment('Hex color for label badge (e.g., #06b6d4)');
    $table->string('label_icon', 50)->nullable()->after('label_color')
        ->comment('Icon name for label (e.g., shopping-cart, store)');
});
```

---

## 4. Modele i Accessory

### 4.1 ERPConnection Model

**Lokalizacja:** `app/Models/ERPConnection.php`

#### Stale domyslnych kolorow

```php
public const LABEL_COLORS = [
    self::ERP_BASELINKER => '#f97316', // orange-500
    self::ERP_SUBIEKT_GT => '#ea580c', // orange-600
    self::ERP_DYNAMICS   => '#c2410c', // orange-700
    self::ERP_INSERT     => '#9a3412', // orange-800
    self::ERP_CUSTOM     => '#78350f', // orange-900
];
```

#### Stale domyslnych ikon

```php
public const LABEL_ICONS = [
    self::ERP_BASELINKER => 'link',
    self::ERP_SUBIEKT_GT => 'database',
    self::ERP_DYNAMICS   => 'cloud',
    self::ERP_INSERT     => 'server',
    self::ERP_CUSTOM     => 'cog',
];
```

#### Accessory z fallback

```php
public function getLabelColorAttribute(): string
{
    return $this->attributes['label_color']
        ?? self::LABEL_COLORS[$this->erp_type]
        ?? '#f97316';
}

public function getLabelIconAttribute(): string
{
    return $this->attributes['label_icon']
        ?? self::LABEL_ICONS[$this->erp_type]
        ?? 'database';
}
```

#### Computed attributes

```php
// CSS inline styles dla badge
public function getLabelBadgeClassesAttribute(): string
{
    $color = $this->label_color;
    return "background-color: {$color}20; color: {$color}; border-color: {$color}50;";
}

// Dane do wyswietlania w komponentach
public function getLabelDataAttribute(): array
{
    return [
        'name' => $this->instance_name,
        'color' => $this->label_color,
        'icon' => $this->label_icon,
        'erp_type' => $this->erp_type,
    ];
}
```

### 4.2 PrestaShopShop Model

**Lokalizacja:** `app/Models/PrestaShopShop.php`

#### Stale domyslne

```php
public const DEFAULT_LABEL_COLOR = '#06b6d4'; // cyan-500
public const DEFAULT_LABEL_ICON = 'shopping-cart';
```

#### Accessory z fallback

```php
public function getLabelColorAttribute(): string
{
    return $this->attributes['label_color'] ?? self::DEFAULT_LABEL_COLOR;
}

public function getLabelIconAttribute(): string
{
    return $this->attributes['label_icon'] ?? self::DEFAULT_LABEL_ICON;
}
```

#### Computed attributes

```php
public function getLabelBadgeStyleAttribute(): string
{
    $color = $this->label_color;
    return "background-color: {$color}20; color: {$color}; border-color: {$color}50;";
}

public function getLabelDataAttribute(): array
{
    return [
        'name' => $this->name,
        'color' => $this->label_color,
        'icon' => $this->label_icon,
        'url' => $this->url,
    ];
}
```

---

## 5. Paleta Kolorow i Ikon

### 5.1 Dostepne kolory (wspolne dla ERP i Shop)

Metody `getAvailableLabelColors()` na obu modelach zwracaja identyczny zestaw:

| Hex | Nazwa PL |
|-----|----------|
| `#ef4444` | Czerwony |
| `#f97316` | Pomaranczowy |
| `#f59e0b` | Bursztynowy |
| `#eab308` | Zolty |
| `#84cc16` | Limonkowy |
| `#22c55e` | Zielony |
| `#14b8a6` | Morski |
| `#06b6d4` | Cyjan |
| `#3b82f6` | Niebieski |
| `#6366f1` | Indygo |
| `#8b5cf6` | Fioletowy |
| `#d946ef` | Magenta |
| `#ec4899` | Rozowy |
| `#64748b` | Szary |

### 5.2 Ikony ERP (`ERPConnection::getAvailableLabelIcons()`)

| Klucz | Nazwa PL | Default dla |
|-------|----------|-------------|
| `database` | Baza danych | Subiekt GT |
| `cloud` | Chmura | Microsoft Dynamics |
| `server` | Serwer | InsERT |
| `link` | Link | BaseLinker |
| `cog` | Zebatka | Customowy |
| `cube` | Kostka | - |
| `archive` | Archiwum | - |
| `folder` | Folder | - |
| `shopping-cart` | Koszyk | - |
| `tag` | Etykieta | - |
| `briefcase` | Teczka | - |
| `building` | Budynek | - |

### 5.3 Ikony PrestaShop Shop (`PrestaShopShop::getAvailableLabelIcons()`)

| Klucz | Nazwa PL | Default |
|-------|----------|---------|
| `shopping-cart` | Koszyk | TAK |
| `store` | Sklep | - |
| `globe` | Globus | - |
| `tag` | Etykieta | - |
| `credit-card` | Karta | - |
| `truck` | Ciezarowka | - |
| `box` | Paczka | - |
| `star` | Gwiazdka | - |
| `heart` | Serce | - |
| `lightning-bolt` | Blyskawica | - |
| `sparkles` | Iskierki | - |
| `badge-check` | Odznaka | - |

---

## 6. Komponent integration-status-badge

**Lokalizacja:** `resources/views/components/integration-status-badge.blade.php`

### 6.1 Props

| Prop | Typ | Default | Opis |
|------|-----|---------|------|
| `name` | string | required | Nazwa integracji (shop/ERP instance name) |
| `color` | string | required | Kolor hex z label_color (#RRGGBB) |
| `icon` | string | required | Nazwa ikony z label_icon |
| `hasIssues` | bool | `true` | Czy integracja ma problemy |
| `issues` | array | `[]` | Tablica typow problemow |
| `type` | string | `'shop'` | Typ: `'shop'` lub `'erp'` |
| `syncStatus` | string/null | `null` | Status synca: `'pending'`, `'running'`, `null` |

### 6.2 Stany wizualne

| Stan | Kolor badge | Ikona statusu | Warunek |
|------|-------------|---------------|---------|
| **Syncing (pending)** | Amber (`#f59e0b`) | Spinner (zegar) | `syncStatus === 'pending'` |
| **Syncing (running)** | Amber (`#f59e0b`) | Spinner (strzalki) | `syncStatus === 'running'` |
| **Issues** | Integration color | Liczba problemow | `hasIssues && count(issues) > 0` |
| **OK** | Integration color (jasniejsze) | Checkmark | `!hasIssues` |

### 6.3 Priorytet wyswietlania

```
Syncing (pending/running) > Issues > OK
```

### 6.4 Technika kolorowania badge

```blade
{{-- Tlo: kolor + 15-30% opacity (hex alpha suffix) --}}
style="background-color: {{ $color }}{{ $bgOpacity }}; color: {{ $color }}; border: 1px solid {{ $color }}{{ $borderOpacity }};"
```

Wartosci opacity:
- **Syncing:** bg=25, border=50
- **Issues:** bg=30, border=60
- **OK:** bg=15, border=40

### 6.5 Przyklad uzycia

```blade
<x-integration-status-badge
    :name="$shopInfo['name']"
    :color="'#' . ltrim($shopInfo['color'], '#')"
    :icon="$shopInfo['icon']"
    :hasIssues="$shopInfo['hasIssues']"
    :issues="$status->shopIssues[$shopId] ?? []"
    :syncStatus="$shopInfo['syncStatus'] ?? null"
    type="shop"
/>
```

---

## 7. Sync Job Status Display

### 7.1 Stale w ProductStatusDTO

```php
public const SYNC_STATUS_PENDING = 'pending';
public const SYNC_STATUS_RUNNING = 'running';
public const SYNC_STATUS_NONE = null;
```

### 7.2 Zrodlo danych

`ProductStatusAggregator::getActiveSyncJobsForProduct()` sprawdza tabele `sync_jobs`:
- `source_id` = product_id
- `source_type` = `'ppm'`
- `status` IN (`'pending'`, `'running'`)
- `target_type` = `'prestashop'` (dla shopow) lub ERP type (dla ERP)
- `target_id` = shop_id lub erp_connection_id

### 7.3 Tooltip labels

| syncStatus | Tooltip |
|------------|---------|
| `'pending'` | `Oczekuje na synchronizacje` |
| `'running'` | `Synchronizacja w toku` |

---

## 8. ProductStatusDTO - Warstwa Danych

**Lokalizacja:** `app/DTOs/ProductStatusDTO.php`

### 8.1 Pola z danymi integracji

```php
/**
 * @var array<int, array{name: string, color: string, icon: string, hasIssues: bool, syncStatus: string|null}>
 */
public array $connectedShops = [];
public array $connectedErps = [];
```

### 8.2 Metody dodawania integracji

```php
public function addConnectedShop(
    int $shopId,
    string $name,
    string $color = '06b6d4',
    string $icon = 'shopping-cart',
    ?string $syncStatus = null
): self;

public function addConnectedErp(
    int $erpId,
    string $name,
    string $color = 'f97316',
    string $icon = 'database',
    ?string $syncStatus = null
): self;
```

### 8.3 Metody pomocnicze

| Metoda | Opis |
|--------|------|
| `finalizeConnectedIntegrations()` | Aktualizuje flagi hasIssues po dodaniu wszystkich issues |
| `hasConnectedIntegrations()` | Czy produkt ma jakiekolwiek polaczenia |
| `hasActiveSyncJob()` | Czy jakakolwiek integracja ma aktywny job sync |
| `getShopsWithoutIssues()` | Sklepy bez problemow |
| `getErpsWithoutIssues()` | ERP bez problemow |

---

## 9. Cache Invalidation

### 9.1 Mechanizm

System automatycznie invaliduje cache `ProductStatusAggregator` przy zmianie `label_color` lub `label_icon`.

### 9.2 Przeplyw

```
1. User zmienia label_color/label_icon w ERPManager lub AddShop
2. Model::save() triggeruje event `updated`
3. AppServiceProvider (linia 79-80) deleguje do ProductStatusCacheObserver
4. Observer::shopUpdated() / erpConnectionUpdated() sprawdza wasChanged(['label_color', 'label_icon'])
5. Jesli zmienione -> ProductStatusAggregator::invalidateCacheForShop()/invalidateCacheForErp()
6. Metody znajduja wszystkie produkty powiazane z dana integracja i invaliduja ich cache
```

### 9.3 Rejestracja observerow

```php
// app/Providers/AppServiceProvider.php
PrestaShopShop::updated(fn(PrestaShopShop $s) => $observer->shopUpdated($s));
ERPConnection::updated(fn(ERPConnection $e) => $observer->erpConnectionUpdated($e));
```

### 9.4 Observer - logika warunkowa

```php
// app/Observers/ProductStatusCacheObserver.php
public function shopUpdated(PrestaShopShop $shop): void
{
    if (!$shop->wasChanged(['label_color', 'label_icon'])) {
        return; // Tylko label changes triggeruja invalidacje
    }
    $aggregator = app(ProductStatusAggregator::class);
    $count = $aggregator->invalidateCacheForShop($shop->id);
}
```

---

## 10. Miejsca Uzycia w Kodzie

### 10.1 Eager Loading (OBOWIAZKOWE)

Kazde miejsce ladujace dane integracji MUSI zawierac `label_color,label_icon`:

```php
// Wzorzec - ZAWSZE stosowac
$product->load([
    'erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon',
    'shopData.shop:id,name,label_color,label_icon',
]);
```

**Pliki z eager loading:**

| Plik | Linia* |
|------|--------|
| `app/Http/Livewire/Products/Listing/ProductList.php` | ~643, ~860-863 |
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerProductsTrait.php` | ~77-78, ~284-285 |
| `app/Jobs/Scan/ScanMissingInSourceJob.php` | eager load w handle() |
| `app/Jobs/VisualEditor/BulkSyncDescriptionsJob.php` | ~122 |

### 10.2 Serializacja do JSON

W Jobs skanowania label data jest serializowana do wynikow:

```php
$links['erp'][] = [
    'connection_id' => $erpData->erp_connection_id,
    'connection_name' => $erpData->erpConnection->instance_name,
    'label_color' => $erpData->erpConnection->label_color,  // MANDATORY
    'label_icon' => $erpData->erpConnection->label_icon,     // MANDATORY
];
```

### 10.3 ProductStatusAggregator

Serwis uzywa label data przy budowaniu DTO:

```php
$status->addConnectedShop(
    $shopData->shop_id,
    $shop->name,
    $shop->label_color ?? '06b6d4',
    $shop->label_icon ?? 'shopping-cart',
    $syncStatus
);
```

### 10.4 Blade Views

| View | Uzycie |
|------|--------|
| `status-column.blade.php` | `<x-integration-status-badge>` z connectedShops/connectedErps |
| `product-status-popover.blade.php` | Kolor dot per shop/ERP w popover |
| `results-table.blade.php` | Badges z JSON `label_color` w wynikach skanowania |
| `erp-manager.blade.php` | Color picker, icon picker, preview badge |
| `description-modal.blade.php` | Badges shopow w modalu importu |

---

## 11. Wzorce Implementacji

### 11.1 Wyswietlanie Badge (Blade)

```blade
@php
    $color = $erpData->erpConnection->label_color;
@endphp
<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
      style="background-color: {{ $color }}20; color: {{ $color }}; border: 1px solid {{ $color }}50;">
    {{ $erpData->erpConnection->instance_name }}
</span>
```

**Wyjasnienie hex alpha:**
- `{{ $color }}20` - tlo z ~12% opacity
- `{{ $color }}` - kolor tekstu (pelna saturacja)
- `{{ $color }}50` - ramka z ~31% opacity

### 11.2 Wyswietlanie z JSON data (scan results)

```blade
@php
    $erpColor = $erp['label_color'] ?? '#f97316'; // ZAWSZE z fallback!
@endphp
<span style="background-color: {{ $erpColor }}20; color: {{ $erpColor }};">
    {{ $erp['connection_name'] }}
</span>
```

### 11.3 Przygotowanie danych w Job/Service

```php
protected function getProductLinksData(Product $product): array
{
    $links = ['erp' => [], 'shops' => []];

    foreach ($product->erpData as $erpData) {
        $links['erp'][] = [
            'connection_id' => $erpData->erp_connection_id,
            'connection_name' => $erpData->erpConnection->instance_name,
            'external_id' => $erpData->external_id,
            'label_color' => $erpData->erpConnection->label_color,
            'label_icon' => $erpData->erpConnection->label_icon,
        ];
    }

    foreach ($product->shopData as $shopData) {
        $links['shops'][] = [
            'shop_id' => $shopData->shop_id,
            'shop_name' => $shopData->shop->name,
            'external_id' => $shopData->external_id,
            'label_color' => $shopData->shop->label_color,
            'label_icon' => $shopData->shop->label_icon,
        ];
    }

    return $links;
}
```

---

## 12. UI Konfiguracji

### 12.1 ERP Manager - Krok 4: Label Settings

**Plik:** `resources/views/livewire/admin/erp/erp-manager.blade.php`

**Elementy UI:**
1. **Color picker** - siatka predefiniowanych kolorow z `getAvailableLabelColors()`
2. **Icon picker** - dropdown select z `getAvailableLabelIcons()`
3. **Live preview** - podglad badge w czasie rzeczywistym

**Livewire Properties (ERPManager):**

```php
// Wewnatrz connectionForm array
public array $connectionForm = [
    // ...
    'label_color' => null,
    'label_icon' => null,
];
```

**Akcja wyboru koloru:**

```blade
wire:click="$set('connectionForm.label_color', '{{ $color }}')"
```

**Akcja wyboru ikony:**

```blade
<select wire:model.live="connectionForm.label_icon">
```

### 12.2 Add Shop

**Plik:** `app/Http/Livewire/Admin/Shops/AddShop.php`

**Livewire Properties:**

```php
public ?string $labelColor = null;
public ?string $labelIcon = null;
```

**Zapis:**

```php
$shopData = [
    // ...
    'label_color' => $this->labelColor,
    'label_icon' => $this->labelIcon,
];
```

---

## 13. Troubleshooting

### Labels nie zapisuja sie

1. Sprawdz czy migracja zostala uruchomiona:
   ```bash
   php artisan migrate:status | grep label
   ```
2. Sprawdz czy kolumny istnieja:
   ```sql
   DESCRIBE erp_connections;
   DESCRIBE prestashop_shops;
   ```
3. Sprawdz czy `label_color` i `label_icon` sa w tablicy `$fillable` modelu.

### Labels nie wyswietlaja sie

1. Sprawdz eager loading - musi zawierac `label_color,label_icon` w select columns.
2. Sprawdz czy uzywasz accessorow (`$connection->label_color`) a nie raw attributes.
3. Sprawdz czy komponent `<x-integration-status-badge>` otrzymuje kolor z prefixem `#`.

### Fallback nie dziala

1. Sprawdz czy accessor `getLabelColorAttribute()` jest zdefiniowany w modelu.
2. Sprawdz czy `LABEL_COLORS` constant zawiera klucz dla danego `erp_type`.
3. Dla PrestaShopShop sprawdz stala `DEFAULT_LABEL_COLOR`.

### Labels nie aktualizuja sie po zmianie

Od v1.1.0 cache jest automatycznie invalidowany. Jesli problem wystepuje:
1. Sprawdz czy observer jest zarejestrowany w `AppServiceProvider`.
2. Sprawdz logi: `ProductStatusCacheObserver: Shop label changed`.
3. Recznie wyczysc cache: `php artisan cache:clear`.

### Badge nie pokazuje sync status

1. Sprawdz czy `syncStatus` prop jest przekazywany do komponentu.
2. Sprawdz czy `ProductStatusAggregator::getActiveSyncJobsForProduct()` zwraca dane.
3. Sprawdz tabele `sync_jobs` - czy sa rekordy ze statusem `pending`/`running`.

### Kolor badge jest bledny

1. Upewnij sie ze kolor zawiera prefix `#` - komponent oczekuje formatu `#RRGGBB`.
2. W `status-column.blade.php` uzywany jest `ltrim($color, '#')` + dodawanie `#` - sprawdz czy dane w DTO nie maja podwojnego `#`.

---

## 14. Changelog

### v2.0.0 (2026-02-13)
- **Dokumentacja** - Pelna przebudowa do standardu projektu PPM
- Analiza wszystkich 15+ plikow zrodlowych
- Numerowany ToC z anchor linkami
- Sekcja statystyk, architektura plikow, schema DB
- Rozbudowane wzorce implementacji
- Sekcja troubleshooting z 6 scenariuszami

### v1.2.0 (2026-02-05)
- **Sync Job Status Display** - Badges wyswietlaja aktywny status sync (pending/running)
- Nowy prop `syncStatus` w komponencie `integration-status-badge`
- Zolte/amber badges z animacja spinner dla synchronizujacych integracji
- `ProductStatusDTO` rozszerzony o pole `syncStatus` i stale `SYNC_STATUS_*`
- `ProductStatusAggregator::getActiveSyncJobsForProduct()` - sprawdzanie aktywnych jobow
- Tooltip ze statusem sync
- Stan syncing ma priorytet wizualny nad stanem issues

### v1.1.0 (2026-02-05)
- **Automatyczna invalidacja cache** przy zmianie label_color lub label_icon
- Nowe metody w ProductStatusAggregator: `invalidateCacheForShop()`, `invalidateCacheForErp()`
- Nowe metody observera: `shopUpdated()`, `erpConnectionUpdated()`
- Brak koniecznosci recznego `cache:clear` po zmianach labeli

### v1.0.0 (2026-02-03)
- Poczatkowa implementacja
- Migracja dodajaca kolumny label_color i label_icon
- Accessory z fallback w ERPConnection i PrestaShopShop
- UI konfiguracji w ERPManager (krok 4)
- Integracja z Scan Products results table
- Skill `integration-labels` utworzony
