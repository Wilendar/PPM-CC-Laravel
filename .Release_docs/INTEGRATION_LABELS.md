# Integration Labels - Customizable Colors and Icons

## Overview

System umożliwia personalizację wyglądu etykiet (labels) dla integracji ERP i sklepów PrestaShop.
Każda integracja może mieć własny kolor i ikonę, które są wyświetlane w całej aplikacji.

**Data implementacji:** 2026-02-03
**Wersja:** 1.0.0
**ETAP:** ETAP_10 - Product Scan System

---

## Struktura Bazy Danych

### Tabela: `erp_connections`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `label_color` | VARCHAR(7) | YES | NULL | Kolor hex (#RRGGBB) |
| `label_icon` | VARCHAR(50) | YES | NULL | Nazwa ikony |

### Tabela: `prestashop_shops`

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `label_color` | VARCHAR(7) | YES | NULL | Kolor hex (#RRGGBB) |
| `label_icon` | VARCHAR(50) | YES | NULL | Nazwa ikony |

### Migracja

```php
// database/migrations/2026_02_03_XXXXXX_add_label_columns_to_erp_and_shops.php
Schema::table('erp_connections', function (Blueprint $table) {
    $table->string('label_color', 7)->nullable()->after('connection_config');
    $table->string('label_icon', 50)->nullable()->after('label_color');
});

Schema::table('prestashop_shops', function (Blueprint $table) {
    $table->string('label_color', 7)->nullable()->after('is_active');
    $table->string('label_icon', 50)->nullable()->after('label_color');
});
```

---

## Modele i Accessory

### ERPConnection Model

**Lokalizacja:** `app/Models/ERPConnection.php`

```php
// Default colors per ERP type
const LABEL_COLORS = [
    'subiekt_gt' => '#f97316',  // Orange
    'baselinker' => '#22c55e',  // Green
    'dynamics' => '#3b82f6',     // Blue
    'other' => '#6b7280',        // Gray
];

// Default icons per ERP type
const LABEL_ICONS = [
    'subiekt_gt' => 'database',
    'baselinker' => 'shopping-bag',
    'dynamics' => 'cube',
    'other' => 'cog',
];

// Accessor with fallback
public function getLabelColorAttribute(): string
{
    return $this->attributes['label_color']
        ?? self::LABEL_COLORS[$this->erp_type]
        ?? self::LABEL_COLORS['other'];
}

public function getLabelIconAttribute(): string
{
    return $this->attributes['label_icon']
        ?? self::LABEL_ICONS[$this->erp_type]
        ?? self::LABEL_ICONS['other'];
}
```

### PrestaShopShop Model

**Lokalizacja:** `app/Models/PrestaShopShop.php`

```php
const DEFAULT_LABEL_COLOR = '#06b6d4';  // Cyan
const DEFAULT_LABEL_ICON = 'shopping-cart';

public function getLabelColorAttribute(): string
{
    return $this->attributes['label_color'] ?? self::DEFAULT_LABEL_COLOR;
}

public function getLabelIconAttribute(): string
{
    return $this->attributes['label_icon'] ?? self::DEFAULT_LABEL_ICON;
}
```

---

## Dostępne Kolory

### ERP Connections

| Nazwa | Hex | Preview |
|-------|-----|---------|
| Orange | #f97316 | ![#f97316](https://via.placeholder.com/20/f97316/f97316) |
| Green | #22c55e | ![#22c55e](https://via.placeholder.com/20/22c55e/22c55e) |
| Blue | #3b82f6 | ![#3b82f6](https://via.placeholder.com/20/3b82f6/3b82f6) |
| Purple | #a855f7 | ![#a855f7](https://via.placeholder.com/20/a855f7/a855f7) |
| Rose | #f43f5e | ![#f43f5e](https://via.placeholder.com/20/f43f5e/f43f5e) |
| Cyan | #06b6d4 | ![#06b6d4](https://via.placeholder.com/20/06b6d4/06b6d4) |
| Amber | #f59e0b | ![#f59e0b](https://via.placeholder.com/20/f59e0b/f59e0b) |
| Lime | #84cc16 | ![#84cc16](https://via.placeholder.com/20/84cc16/84cc16) |
| Gray | #6b7280 | ![#6b7280](https://via.placeholder.com/20/6b7280/6b7280) |

### PrestaShop Shops

Domyślnie: **Cyan (#06b6d4)** - wyróżnia sklepy od integracji ERP.

---

## Dostępne Ikony

| Nazwa | Opis | Używana dla |
|-------|------|-------------|
| `database` | Baza danych | Subiekt GT |
| `shopping-bag` | Torba zakupowa | Baselinker |
| `cube` | Sześcian | Microsoft Dynamics |
| `shopping-cart` | Koszyk | PrestaShop |
| `cog` | Koło zębate | Inne |
| `globe` | Globus | Międzynarodowe |
| `server` | Serwer | Techniczne |
| `chart-bar` | Wykres | Analityka |
| `lightning-bolt` | Błyskawica | Szybkie |
| `link` | Link | Połączenia |
| `cloud` | Chmura | Cloud services |

---

## Miejsca Użycia Labels

### 1. Panel ERP Manager (`/admin/integrations`)

**Plik:** `resources/views/livewire/admin/erp/erp-manager.blade.php`

- Lista integracji - badge z kolorem i ikoną
- Modal edycji - krok 4 "Label Settings"
- Podgląd etykiety w czasie rzeczywistym

### 2. Scan Products Panel (`/admin/scan-products`)

**Plik:** `resources/views/livewire/admin/scan/partials/results-table.blade.php`

- Kolumna "Powiązania" - dynamiczne badges ERP i Shop

### 3. Jobs - ScanProductLinksJob

**Plik:** `app/Jobs/Scan/ScanProductLinksJob.php`

- Metoda `getProductLinksData()` - serializacja label_color i label_icon

### 4. Jobs - ScanMissingInSourceJob

**Plik:** `app/Jobs/Scan/ScanMissingInSourceJob.php`

- Eager loading z label fields
- Metoda `getProductLinksData()`

### 5. Product Form - ERP Tab (planowane)

**Plik:** `resources/views/livewire/products/management/tabs/erp-tab.blade.php`

- Wyświetlanie powiązań z dynamicznymi kolorami

### 6. Shops Panel (`/admin/shops`)

**Plik:** `resources/views/livewire/admin/shops/shop-panel.blade.php`

- Lista sklepów z kolorowymi badges

---

## Wzorzec Blade - Wyświetlanie Badge

```blade
@php
    $color = $erpData->erpConnection->label_color;
@endphp
<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs"
      style="background-color: {{ $color }}20; color: {{ $color }}; border: 1px solid {{ $color }}50;">
    {{ $erpData->erpConnection->instance_name }}
</span>
```

**Wyjaśnienie:**
- `{{ $color }}20` - kolor tła z 20% opacity (hex alpha)
- `{{ $color }}` - kolor tekstu (pełna saturacja)
- `{{ $color }}50` - kolor ramki z 50% opacity

---

## Sync Job Status Display (v1.2.0)

### Stany Badge

| Stan | Kolor | Ikona | Opis |
|------|-------|-------|------|
| OK | Integration color | ✓ Checkmark | Brak problemów |
| Issues | Integration color | Liczba | Liczba problemów do rozwiązania |
| Pending | Amber (#f59e0b) | ⏳ Spinner | Oczekuje na synchronizację |
| Running | Amber (#f59e0b) | 🔄 Rotating | Synchronizacja w toku |

### Priorytet wyświetlania

Sync status ma priorytet nad issues:
```
Syncing (pending/running) > Issues > OK
```

### Użycie komponentu z syncStatus

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

### Dostępne wartości syncStatus

| Wartość | Stała | Opis |
|---------|-------|------|
| `null` | `ProductStatusDTO::SYNC_STATUS_NONE` | Brak aktywnego joba |
| `'pending'` | `ProductStatusDTO::SYNC_STATUS_PENDING` | Job oczekuje w kolejce |
| `'running'` | `ProductStatusDTO::SYNC_STATUS_RUNNING` | Job jest wykonywany |

### Źródło danych

`ProductStatusAggregator::getActiveSyncJobsForProduct()` sprawdza tabelę `sync_jobs`:
- `source_id` = product_id
- `source_type` = 'ppm'
- `status` IN ('pending', 'running')
- `target_type` = 'prestashop' (dla sklepów) lub ERP type (dla ERP)
- `target_id` = shop_id lub erp_connection_id

---

## Wzorzec Eager Loading

```php
// ZAWSZE ładuj label fields!
$product->load([
    'erpData.erpConnection:id,instance_name,erp_type,label_color,label_icon',
    'shopData.shop:id,name,label_color,label_icon',
]);
```

---

## Wzorzec Serializacji (JSON)

```php
protected function getProductLinksData(Product $product): array
{
    $links = ['erp' => [], 'shops' => []];

    foreach ($product->erpData as $erpData) {
        $links['erp'][] = [
            'connection_id' => $erpData->erp_connection_id,
            'connection_name' => $erpData->erpConnection->instance_name,
            'external_id' => $erpData->external_id,
            'label_color' => $erpData->erpConnection->label_color,  // MANDATORY!
            'label_icon' => $erpData->erpConnection->label_icon,    // MANDATORY!
        ];
    }

    foreach ($product->shopData as $shopData) {
        $links['shops'][] = [
            'shop_id' => $shopData->shop_id,
            'shop_name' => $shopData->shop->name,
            'external_id' => $shopData->external_id,
            'label_color' => $shopData->shop->label_color,  // MANDATORY!
            'label_icon' => $shopData->shop->label_icon,    // MANDATORY!
        ];
    }

    return $links;
}
```

---

## UI Konfiguracji

### ERP Manager - Krok 4: Label Settings

**Elementy:**
1. Color picker z predefiniowanymi kolorami
2. Icon picker z predefiniowanymi ikonami
3. Preview etykiety w czasie rzeczywistym

**Livewire Properties:**
```php
public ?string $connectionLabelColor = null;
public ?string $connectionLabelIcon = null;
```

**Metody:**
```php
public function setLabelColor(string $color): void
{
    $this->connectionLabelColor = $color;
}

public function setLabelIcon(string $icon): void
{
    $this->connectionLabelIcon = $icon;
}
```

---

## Skill Reference

**Skill:** `integration-labels`
**Lokalizacja:** `.claude/skills/integration-labels/SKILL.md`

Skill wymusza:
- Zakaz hardcodowania nazw integracji
- Zawsze eager loading z label fields
- Używanie accessorów z fallback

---

## Troubleshooting

### Labels nie zapisują się

1. Sprawdź czy migracja została uruchomiona:
   ```bash
   php artisan migrate:status | grep label
   ```

2. Sprawdź czy kolumny istnieją:
   ```sql
   DESCRIBE erp_connections;
   DESCRIBE prestashop_shops;
   ```

### Labels nie wyświetlają się

1. Sprawdź eager loading - musi zawierać `label_color,label_icon`
2. Sprawdź czy używasz accessorów (`$connection->label_color`) a nie raw attributes

### Fallback nie działa

1. Sprawdź czy accessor jest zdefiniowany w modelu
2. Sprawdź czy `LABEL_COLORS` constant zawiera klucz dla danego `erp_type`

### Labels nie aktualizują się po zmianie

**Rozwiązanie:** Od v1.1.0 cache jest automatycznie invalidowany gdy zmienia się `label_color` lub `label_icon` w integracji. Jeśli problem występuje na starszej wersji:
```bash
php artisan cache:clear
```

---

## Cache Invalidation (v1.1.0)

System automatycznie invaliduje cache `ProductStatusAggregator` gdy zmienia się `label_color` lub `label_icon` w:
- `PrestaShopShop` (sklepy)
- `ERPConnection` (integracje ERP)

**Mechanizm:**
1. `ProductStatusCacheObserver` nasłuchuje na event `updated` dla obu modeli
2. Sprawdza czy zmienił się `label_color` lub `label_icon` (`wasChanged()`)
3. Jeśli tak - wywołuje `ProductStatusAggregator::invalidateCacheForShop()` lub `invalidateCacheForErp()`
4. Te metody znajdują wszystkie produkty powiązane z daną integracją i invalidują ich cache

**Pliki:**
- `app/Observers/ProductStatusCacheObserver.php` - observer z metodami `shopUpdated()` i `erpConnectionUpdated()`
- `app/Services/Product/ProductStatusAggregator.php` - metody `invalidateCacheForShop()` i `invalidateCacheForErp()`
- `app/Providers/AppServiceProvider.php` - rejestracja observerów

---

## Changelog

### v1.2.0 (2026-02-05)
- **Sync Job Status Display** - Badges now show active sync status (pending/running)
- New `syncStatus` parameter in `integration-status-badge` component
- Yellow/amber colored badges with spinner for syncing integrations
- `ProductStatusDTO` extended with `syncStatus` field and `SYNC_STATUS_*` constants
- `ProductStatusAggregator::getActiveSyncJobsForProduct()` - checks for pending/running jobs
- Tooltip shows sync status: "Oczekuje na synchronizację" / "Synchronizacja w toku"
- Syncing state takes visual priority over issues state

### v1.1.0 (2026-02-05)
- Automatic cache invalidation when label_color or label_icon changes
- New methods in ProductStatusAggregator: `invalidateCacheForShop()`, `invalidateCacheForErp()`
- New observer methods: `shopUpdated()`, `erpConnectionUpdated()`
- No more manual `cache:clear` needed after label changes

### v1.0.0 (2026-02-03)
- Initial implementation
- Migration for label_color and label_icon columns
- Accessors with fallback in ERPConnection and PrestaShopShop models
- UI configuration in ERPManager (step 4)
- Integration with Scan Products results table
- Skill "integration-labels" created
- Documentation created

---

## Powiązane Pliki

| Plik | Opis |
|------|------|
| `app/Models/ERPConnection.php` | Model z accessorami |
| `app/Models/PrestaShopShop.php` | Model z accessorami |
| `app/Models/SyncJob.php` | Model jobów synchronizacji |
| `app/DTOs/ProductStatusDTO.php` | DTO z syncStatus i stałymi |
| `app/Services/Product/ProductStatusAggregator.php` | Agregator statusów + getActiveSyncJobsForProduct() |
| `app/Http/Livewire/Admin/ERP/ERPManager.php` | UI konfiguracji |
| `resources/views/components/integration-status-badge.blade.php` | Komponent badge z syncStatus |
| `resources/views/livewire/products/listing/partials/status-column.blade.php` | Kolumna statusu w ProductList |
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | Blade view |
| `app/Jobs/Scan/ScanProductLinksJob.php` | Job z serializacją |
| `app/Jobs/Scan/ScanMissingInSourceJob.php` | Job z eager loading |
| `resources/views/livewire/admin/scan/partials/results-table.blade.php` | Wyświetlanie badges |
| `.claude/skills/integration-labels/SKILL.md` | Skill dokumentacja |
