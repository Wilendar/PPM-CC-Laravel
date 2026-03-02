# PPM - Vehicle Features Panel Documentation

> **Wersja:** 1.0
> **Data:** 2026-02-09
> **Status:** Production Ready
> **Modul:** Cechy Pojazdow (`/admin/features/vehicles`)

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura plikow](#2-architektura-plikow)
3. [Baza danych](#3-baza-danych)
4. [UI - Trzy taby](#4-ui---trzy-taby)
5. [CRUD Operations](#5-crud-operations)
6. [Drag and Drop System](#6-drag-and-drop-system)
7. [Icon Picker System](#7-icon-picker-system)
8. [Integracja z ProductForm](#8-integracja-z-productform)
9. [Integracja z PrestaShop](#9-integracja-z-prestashop)
10. [Bulk Assign System](#10-bulk-assign-system)
11. [Serwisy](#11-serwisy)
12. [Alpine.js Components](#12-alpinejs-components)
13. [CSS Architecture](#13-css-architecture)
14. [Permissions i Security](#14-permissions-i-security)
15. [Diagramy](#15-diagramy)

---

## 1. Overview

### 1.1 Opis modulu

Panel Cechy Pojazdow to centralny modul zarzadzania biblioteka cech motoryzacyjnych w systemie PPM. Umozliwia definiowanie grup cech (np. Silnik, Wymiary, Hamulce), typow cech (np. Moc, Pojemnosc, Masa), predefiniowanych wartosci, szablonow cech oraz masowe przypisywanie cech do produktow.

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Livewire Components | 4 (1 main + 3 taby) |
| Modele | 5 |
| Serwisy | 2 |
| Joby | 1 |
| Blade Views | 4 |
| CSS | 1 plik (1275 linii) |
| Tabele DB | 6 (+1 mapping) |
| Linie kodu (backend) | ~5000 |
| Linie kodu (frontend) | ~2900 |

### 1.3 Glowne funkcjonalnosci

- **Przegladarka Cech** - 3-kolumnowy browser (Grupy -> Typy -> Produkty)
- **Biblioteka Cech** - Accordion tree z CRUD dla grup i typow cech
- **Szablony Cech** - Card grid z tworzeniem/edycja/duplikowaniem szablonow
- **Drag & Drop** - Przenoszenie cech miedzy grupami z insert line indicators
- **Bulk Assign** - Masowe przypisywanie szablonow z job progress tracking
- **Icon Picker** - Grid dropdown z 50+ ikonami motoryzacyjnymi w 9 kategoriach
- **PrestaShop Sync** - Mapowanie cech PPM <-> PS z auto-create values
- **ProductForm Integration** - Zakladka Atrybuty w formularzu produktu

---

## 2. Architektura plikow

### 2.1 Livewire Components

| Komponent | Plik | Linie | Przeznaczenie |
|-----------|------|-------|---------------|
| VehicleFeatureManagement | `app/Http/Livewire/Admin/Features/VehicleFeatureManagement.php` | 1435 | Glowny komponent strony |
| FeatureBrowserTab | `app/Http/Livewire/Admin/Features/Tabs/FeatureBrowserTab.php` | 325 | Tab: Przegladarka Cech |
| FeatureLibraryTab | `app/Http/Livewire/Admin/Features/Tabs/FeatureLibraryTab.php` | 550 | Tab: Biblioteka Cech |
| FeatureTemplatesTab | `app/Http/Livewire/Admin/Features/Tabs/FeatureTemplatesTab.php` | 463 | Tab: Szablony Cech |

### 2.2 Modele

| Model | Plik | Linie | Tabela |
|-------|------|-------|--------|
| FeatureGroup | `app/Models/FeatureGroup.php` | 374 | `feature_groups` |
| FeatureType | `app/Models/FeatureType.php` | 363 | `feature_types` |
| FeatureValue | `app/Models/FeatureValue.php` | 109 | `feature_values` |
| FeatureTemplate | `app/Models/FeatureTemplate.php` | 119 | `feature_templates` |
| ProductFeature | `app/Models/ProductFeature.php` | 147 | `product_features` |
| PrestashopFeatureMapping | `app/Models/PrestashopFeatureMapping.php` | 301 | `prestashop_feature_mappings` |

### 2.3 Serwisy

| Serwis | Plik | Linie | Przeznaczenie |
|--------|------|-------|---------------|
| FeatureManager | `app/Services/Product/FeatureManager.php` | 369 | Business logic CRUD |
| FeatureUsageService | `app/Services/Product/FeatureUsageService.php` | 285 | Statystyki uzycia |

### 2.4 Joby

| Job | Plik | Linie | Przeznaczenie |
|-----|------|-------|---------------|
| BulkAssignFeaturesJob | `app/Jobs/Features/BulkAssignFeaturesJob.php` | 338 | Masowe przypisywanie szablonow |

### 2.5 Blade Views

| Widok | Plik | Linie |
|-------|------|-------|
| Main | `resources/views/livewire/admin/features/vehicle-feature-management.blade.php` | 591 |
| Browser Tab | `resources/views/livewire/admin/features/tabs/feature-browser-tab.blade.php` | 257 |
| Library Tab | `resources/views/livewire/admin/features/tabs/feature-library-tab.blade.php` | 471 |
| Templates Tab | `resources/views/livewire/admin/features/tabs/feature-templates-tab.blade.php` | 309 |

### 2.6 CSS

| Plik | Linie | Przeznaczenie |
|------|-------|---------------|
| `resources/css/admin/feature-browser.css` | 1275 | Wszystkie style panelu |

### 2.7 Controller i Route

```php
// app/Http/Controllers/Admin/VehicleFeatureController.php (45 lines)
// Route: /admin/features/vehicles
Route::get('/features/vehicles', [VehicleFeatureController::class, 'index'])
    ->name('admin.features.vehicles.index');
```

---

## 3. Baza danych

### 3.1 Tabela: feature_groups

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `code` | varchar(50) | NO | - | Unikalny kod (UNIQUE) |
| `name` | varchar(100) | NO | - | Nazwa angielska |
| `name_pl` | varchar(100) | YES | NULL | Nazwa polska (wyswietlana) |
| `icon` | varchar(50) | YES | NULL | Kod ikony (engine, ruler, etc.) |
| `color` | varchar(20) | YES | NULL | Kolor Tailwind (orange, blue) |
| `sort_order` | int | NO | 0 | Kolejnosc wyswietlania |
| `vehicle_type_filter` | varchar(50) | YES | NULL | elektryczne / spalinowe / null |
| `description` | text | YES | NULL | Opis grupy |
| `is_active` | tinyint(1) | NO | 1 | Flaga aktywnosci |
| `is_collapsible` | tinyint(1) | NO | 1 | Accordion behavior |

**Indeksy:** `idx_fg_code` (UNIQUE), `idx_fg_sort`, `idx_fg_vehicle_type`, `idx_fg_active`

### 3.2 Tabela: feature_types

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `name` | varchar(100) | NO | - | Nazwa cechy |
| `code` | varchar(50) | NO | - | Unikalny kod (UNIQUE) |
| `value_type` | enum | NO | 'text' | text / number / bool / select |
| `unit` | varchar(20) | YES | NULL | Jednostka (kW, kg, mm) |
| `feature_group_id` | bigint | YES | NULL | FK do feature_groups |
| `input_placeholder` | varchar(255) | YES | NULL | Placeholder dla inputa |
| `validation_rules` | json | YES | NULL | Reguly walidacji |
| `conditional_group` | varchar(50) | YES | NULL | elektryczne / spalinowe |
| `excel_column` | varchar(10) | YES | NULL | Kolumna Excel (import) |
| `prestashop_name` | varchar(255) | YES | NULL | Nazwa w PrestaShop |
| `position` | int | NO | 0 | Kolejnosc w grupie |
| `is_active` | tinyint(1) | NO | 1 | Flaga aktywnosci |

**Indeksy:** `idx_feature_type_code` (UNIQUE), `idx_feature_type_active`
**FK:** `feature_group_id` -> `feature_groups.id` (SET NULL ON DELETE)

### 3.3 Tabela: feature_values

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `feature_type_id` | bigint | NO | - | FK do feature_types |
| `value` | varchar(255) | NO | - | Wartosc |
| `display_value` | varchar(255) | YES | NULL | Wartosc wyswietlana |
| `position` | int | NO | 0 | Kolejnosc |

**FK:** `feature_type_id` -> `feature_types.id` (CASCADE DELETE)

### 3.4 Tabela: feature_templates

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `name` | varchar(255) | NO | - | Nazwa szablonu |
| `description` | text | YES | NULL | Opis |
| `features` | json | NO | - | Array definicji cech |
| `is_predefined` | tinyint(1) | NO | 0 | Systemowy (nie do usuwania) |
| `is_active` | tinyint(1) | NO | 1 | Aktywnosc |

**Predefiniowane szablony:** ID 1 (Pojazdy elektryczne, 26 cech), ID 2 (Pojazdy spalinowe, 24 cechy)

**Format JSON `features`:**

```json
[
    {
        "feature_type_id": 5,
        "name": "Moc",
        "code": "power",
        "type": "number",
        "default": null,
        "required": false
    }
]
```

### 3.5 Tabela: product_features

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `product_id` | bigint | NO | - | FK do products |
| `feature_type_id` | bigint | NO | - | FK do feature_types |
| `feature_value_id` | bigint | YES | NULL | FK do feature_values (typ select) |
| `custom_value` | text | YES | NULL | Wartosc custom (typ text/number/bool) |

**UNIQUE:** `(product_id, feature_type_id)` - jedna cecha per produkt
**FK:** `product_id` -> CASCADE, `feature_type_id` -> CASCADE, `feature_value_id` -> SET NULL

### 3.6 Tabela: prestashop_feature_mappings

| Kolumna | Typ | Nullable | Default | Opis |
|---------|-----|----------|---------|------|
| `id` | bigint | NO | auto | PK |
| `feature_type_id` | bigint | NO | - | FK do feature_types |
| `shop_id` | bigint | NO | - | FK do prestashop_shops |
| `prestashop_feature_id` | int | NO | - | id_feature w PrestaShop |
| `sync_direction` | enum | NO | 'both' | both / ppm_to_ps / ps_to_ppm |
| `auto_create_values` | tinyint(1) | NO | 1 | Auto-tworzenie wartosci w PS |
| `is_active` | tinyint(1) | NO | 1 | Aktywnosc |
| `last_synced_at` | timestamp | YES | NULL | Ostatnia synchronizacja |
| `sync_count` | int | NO | 0 | Licznik syncow |

### 3.7 Relacje miedzy tabelami

```
feature_groups
    |
    |--- 1:N ---> feature_types
                      |
                      |--- 1:N ---> feature_values (typ select)
                      |
                      |--- 1:N ---> product_features
                      |                 |
                      |                 |--- N:1 ---> products
                      |                 |
                      |                 |--- N:1 ---> feature_values (nullable)
                      |
                      |--- 1:N ---> prestashop_feature_mappings
                                        |
                                        |--- N:1 ---> prestashop_shops

feature_templates (standalone, JSON features array)
```

---

## 4. UI - Trzy taby

### 4.1 Przegladarka Cech (FeatureBrowserTab)

**Layout:** 3-kolumnowy grid (`320px 1fr 1fr`)

| Kolumna | Zawartosc | Interakcja |
|---------|-----------|------------|
| Lewa (320px) | Lista grup z ikonami i licznikami | Klikniecie = selectGroup() |
| Srodkowa | Typy cech LUB wartosci (po wyborze cechy) | Klikniecie typu = selectFeatureType(), checkboxy wartosci = toggleValue() |
| Prawa | Produkty uzywajace wybranej wartosci | Klikniecie = goToProduct() (nawigacja do ProductForm) |

**Computed Properties:**

| Property | Opis |
|----------|------|
| `groups()` | Grupy z liczbami cech |
| `featureTypes()` | Typy cech dla wybranej grupy |
| `featureValues()` | Predefiniowane wartosci dla wybranego typu |
| `customValues()` | Custom wartosci (unikalne z product_features) |
| `products()` | Produkty uzywajace wybranych wartosci |

### 4.2 Biblioteka Cech (FeatureLibraryTab)

**Layout:** Accordion tree - grupy rozwijane z cechami inline

**Funkcje:**
- Rozwin/Zwin wszystko (expandAll/collapseAll)
- CRUD grup cech (modal z Icon Picker)
- CRUD typow cech (modal)
- Drag & Drop cech miedzy grupami (patrz sekcja 6)
- Hover actions (edycja, usuwanie)

**Kluczowe metody:**

| Metoda | Opis |
|--------|------|
| `toggleGroup(int $groupId)` | Rozwin/zwin grupe |
| `moveFeatureToGroup(int $featureId, int $targetGroupId, ?int $beforeFeatureId)` | Przenies ceche (DnD) |
| `updateFeatureOrder(int $groupId, array $featureIds)` | Zmien kolejnosc cech |
| `saveFeatureGroup()` | Utworz/edytuj grupe |
| `saveFeatureType()` | Utworz/edytuj ceche |

### 4.3 Szablony Cech (FeatureTemplatesTab)

**Layout:** Responsive card grid (`repeat(auto-fill, minmax(320px, 1fr))`)

**Funkcje:**
- Filtrowanie: Wszystkie / Predefiniowane / Wlasne
- Tworzenie/edycja szablonow (modal z wire:sortable)
- Duplikowanie szablonow
- Expandable preview na kartach
- Bulk Assign wizard (patrz sekcja 10)
- Job progress tracking (wire:poll.2s)

**Kluczowe metody:**

| Metoda | Opis |
|--------|------|
| `openTemplateModal()` | Nowy szablon |
| `editTemplate(int $id)` | Edycja szablonu |
| `duplicateTemplate(int $id)` | Duplikuj szablon |
| `deleteTemplate(int $id)` | Usun (tylko custom) |
| `reorderTemplateFeatures(array $newOrder)` | DnD reorder cech w szablonie |
| `bulkAssign()` | Dispatch BulkAssignFeaturesJob |

---

## 5. CRUD Operations

### 5.1 Feature Groups

**Pola formularza:**

| Pole | Typ | Wymagane | Opis |
|------|-----|----------|------|
| name | text | Tak | Nazwa grupy |
| code | text | Tak | Unikatowy kod |
| icon | Icon Picker | Nie | Ikona z 50+ opcji (patrz sekcja 7) |
| color | select | Nie | Kolor (orange, blue, green, ...) |
| sort_order | number | Tak | Kolejnosc (min 0) |
| vehicle_type_filter | select | Nie | Filtr: elektryczne / spalinowe / wszystkie |

**Walidacja:**

```php
'featureGroupName' => 'required|string|max:255',
'featureGroupCode' => 'required|string|max:100',
'featureGroupSortOrder' => 'required|integer|min:0',
```

**Usuwanie:** Blokowane jesli grupa zawiera cechy (`featureTypes()->count() > 0`).

### 5.2 Feature Types

**Pola formularza:**

| Pole | Typ | Wymagane | Opis |
|------|-----|----------|------|
| name | text | Tak | Nazwa cechy |
| code | text | Tak | Unikatowy kod |
| value_type | select | Tak | text / number / bool / select |
| unit | text | Nie | Jednostka (kW, kg, mm) |
| group | select | Nie | Grupa cechy |
| placeholder | text | Nie | Podpowiedz w inpucie |
| conditional | select | Nie | Warunkowa: elektryczne / spalinowe |

**Walidacja:**

```php
'featureTypeName' => 'required|string|max:255',
'featureTypeCode' => 'required|string|max:100',
'featureTypeValueType' => 'required|in:text,number,bool,select',
'featureTypeGroupId' => 'nullable|exists:feature_groups,id',
```

**Usuwanie:** Blokowane jesli cecha jest uzywana przez produkty (`productFeatures()->count() > 0`).

### 5.3 Feature Values

Wartosci predefiniowane dla cech typu `select`. Zarzadzane w ramach edycji FeatureType.

### 5.4 Feature Templates

**Pola:** name (text, wymagane), features (array, dynamicznie dodawane/usuwane wiersze).

**Ochrona:** Predefiniowane szablony (is_predefined=1) nie moga byc edytowane ani usuwane.

---

## 6. Drag and Drop System

### 6.1 Cross-group feature move (Biblioteka Cech)

**Technologia:** Native HTML5 Drag & Drop API + Alpine.js

**Alpine.js component (`featureDragDrop`):**

```javascript
// Dane stanu
draggedFeatureId: null,        // ID przeciaganej cechy
draggedFromGroupId: null,      // ID grupy zrodlowej
dropTargetGroupId: null,       // ID grupy docelowej (podswietlenie)
insertBeforeId: null,          // ID cechy przed ktora wstawic (lub null = koniec)
expandTimer: null,             // Timer auto-expand zwiniętej grupy
```

**Metody:**

| Metoda | Trigger | Opis |
|--------|---------|------|
| `startDrag(event, featureId, groupId)` | `@dragstart` | Rozpoczecie przeciagania |
| `endDrag()` | `@dragend` | Zakonczenie (cleanup) |
| `highlightGroup(event, groupId, isExpanded)` | `@dragover` na group header | Podswietlenie grupy + auto-expand po 500ms |
| `unhighlightGroup(groupId)` | `@dragleave` | Usuniecie podswietlenia |
| `onFeatureDragOver(event, featureId, groupId)` | `@dragover` na feature item | Kalkulacja pozycji (gorna/dolna polowa) + insert line |
| `clearInsertLine()` | `@dragleave` na feature item | Usuniecie insert line |
| `dropOnFeature(event, featureId, groupId)` | `@drop` na feature item | Drop z precyzyjna pozycja |
| `dropOnGroup(event, targetGroupId)` | `@drop` na group header/container | Drop na koniec grupy |

**Insert line:** Kursor w gornej polowie wiersza = linia nad wierszem (`--insert-above`), dolna polowa = linia pod (`--insert-below`).

**Auto-expand:** Gdy uzytkownik przeciaga ceche nad zwinietna grupe, po 500ms hoveru grupa automatycznie sie rozwija.

**Backend:**

```php
public function moveFeatureToGroup(
    int $featureId,
    int $targetGroupId,
    ?int $beforeFeatureId = null
): void
```

Logika:
1. Pobierz ordered features w grupie docelowej (bez przenoszonej)
2. Oblicz insert index na podstawie `$beforeFeatureId`
3. `array_splice()` wstaw przenoszona ceche w odpowiednie miejsce
4. Zaktualizuj `feature_group_id` przenoszonej cechy
5. Reindexuj pozycje w grupie docelowej
6. Reindexuj pozycje w starej grupie (jesli cross-group move)
7. Auto-expand grupe docelowa

### 6.2 Template feature reorder (Szablony Cech)

**Technologia:** `wire:sortable` (Livewire SortableJS)

```blade
<div wire:sortable="reorderTemplateFeatures"
     wire:sortable.options="{ animation: 150, ghostClass: 'template-sortable-ghost',
                              handle: '.template-drag-handle' }">
    @foreach($templateFeatures as $index => $feature)
        <div wire:sortable.item="{{ $index }}" wire:key="tpl-feat-{{ $index }}">
            <span class="template-drag-handle" wire:sortable.handle>&#9776;</span>
            {{-- inputs --}}
        </div>
    @endforeach
</div>
```

### 6.3 CSS Classes

| Klasa | Opis |
|-------|------|
| `.feature-drag-handle` | Uchwyt (opacity: 0, widoczny na hover) |
| `.feature-tree__feature-item--dragging` | Przeciagany element (opacity: 0.4) |
| `.feature-tree__group-header--drop-target` | Podswietlenie grupy (blue outline) |
| `.feature-tree__features--drop-target` | Podswietlenie kontenera cech |
| `.feature-tree__feature-item--insert-above` | Niebieska linia nad wierszem |
| `.feature-tree__feature-item--insert-below` | Niebieska linia pod wierszem |
| `.template-drag-handle` | Uchwyt w szablonie |
| `.template-sortable-ghost` | Ghost SortableJS |
| `body.dragging-feature` | Globalny cursor: grabbing |

---

## 7. Icon Picker System

### 7.1 Centralna mapa ikon

**Lokalizacja:** `FeatureGroup::getIconMap()` (statyczna metoda)

**Format:**

```php
public static function getIconMap(): array
{
    return [
        'engine' => ['entity' => '&#9881;', 'label' => 'Silnik', 'category' => 'glowne'],
        'car' => ['entity' => '&#128663;', 'label' => 'Samochod', 'category' => 'glowne'],
        // ... 50+ ikon
    ];
}
```

### 7.2 Kategorie ikon

| Kategoria | Klucz | Przykladowe ikony |
|-----------|-------|-------------------|
| Glowne | `glowne` | engine, car, motorcycle, gear, fuel, electric |
| Naped | `naped` | transmission, chain, clutch, differential, axle |
| Podwozie | `podwozie` | wheel, tire, brake, suspension, fork, shock, frame, steering |
| Elektryka | `elektryka` | battery, light, plug, wiring, sensor, ecu, ignition, controller |
| Czesci silnika | `silnik` | piston, carburetor, turbo, exhaust, radiator, oil, filter |
| Wymiary | `wymiary` | ruler, weight, speed, power, volume, range |
| Nadwozie | `nadwozie` | seat, mirror, bumper |
| Narzedzia | `narzedzia` | wrench, bolt |
| Inne | `inne` | document, info, certificate, tag |

### 7.3 Grid Icon Picker (Alpine.js)

**UI:** Przycisk z aktualna ikona -> klik otwiera dropdown -> taby kategorii -> 5-kolumnowy grid -> klik ikony = wybor + zamkniecie.

**Kluczowe elementy:**
- `$wire.entangle('featureGroupIcon')` - bidirectional sync z Livewire
- `wire:key="icon-picker-{{ $editingFeatureGroupId ?? 'new' }}"` - wymuszenie rerendering
- Dropdown z `x-transition` animacja
- Scrollowalna siatka z max-height: 16rem

**Uzycie w Blade (render ikon w drzewie):**

```blade
@php $iconMap = \App\Models\FeatureGroup::getIconMap(); @endphp
{!! $iconMap[$group['icon']]['entity'] ?? '&#128193;' !!}
```

---

## 8. Integracja z ProductForm

### 8.1 Trait ProductFormFeatures

**Plik:** `app/Http/Livewire/Products/Management/Traits/ProductFormFeatures.php` (~1225 linii)

**Properties:**

| Property | Typ | Opis |
|----------|-----|------|
| `$productFeatures` | array | Aktywne cechy produktu `[{id, feature_type_id, value}]` |
| `$defaultProductFeatures` | array | Snapshot domyslny (do porownania) |
| `$shopProductFeatures` | array | Cechy per-shop `[shopId => [feature_type_id => value]]` |

### 8.2 Kluczowe metody

| Metoda | Opis |
|--------|------|
| `loadProductFeatures()` | Laduje cechy z DB przy edycji (wywolywane w mount()) |
| `saveProductFeatures()` | Zapisuje cechy do product_features (wywolywane w save()) |
| `saveShopFeatures(int $shopId)` | Zapisuje cechy per-shop do ProductShopData.attribute_mappings |
| `addProductFeature(int $featureTypeId)` | Dodaje ceche do produktu |
| `removeProductFeature(int $featureTypeId)` | Usuwa ceche |
| `applyFeatureTemplate(int $templateId)` | Stosuje szablon (dodaje wszystkie cechy z szablonu) |
| `getProductFeaturesForPrestaShop(int $shopId)` | Przygotowuje cechy do syncu PS |

### 8.3 Flow: load -> edit -> save

```
ProductForm::mount()
    -> loadProductFeatures()
    -> $productFeatures populated from product_features table

User: Zakladka Atrybuty
    -> addProductFeature(typeId)           // Przycisk "Dodaj ceche"
    -> applyFeatureTemplate(templateId)     // Dropdown "Zastosuj szablon"
    -> wire:model="productFeatures.X.value" // Edycja wartosci
    -> removeProductFeature(typeId)         // Przycisk "X"

ProductForm::save()
    -> saveProductFeatures()
    -> ProductFeature::updateOrCreate() per cecha
    -> (jesli shop context) saveShopFeatures(shopId)
```

### 8.4 Per-shop features (OPCJA B)

Kazdy sklep moze miec wlasne wartosci cech, przechowywane w `ProductShopData.attribute_mappings`:

```json
{
    "features": {
        "5": "1000W",
        "8": "50km/h",
        "12": true
    },
    "features_updated_at": "2025-12-03T10:30:00Z"
}
```

**Priorytet syncu:** Per-shop features > Global features (product_features)

### 8.5 Feature status comparison

| Status | Opis | CSS |
|--------|------|-----|
| `default` | Wartosc domyslna (bez zmian) | Brak |
| `inherited` | Odziedziczona z globalnych | Fioletowa obwodka |
| `same` | Taka sama jak global | Brak |
| `different` | Rozni sie od global | Pomaranczowa obwodka |

---

## 9. Integracja z PrestaShop

### 9.1 PrestashopFeatureMapping

Mapowanie cech PPM na cechy PrestaShop (1:1 per shop):

```
FeatureType (PPM)  <--->  PrestashopFeatureMapping  <--->  ps_feature (PrestaShop)
```

**Wlasciwosci mapowania:**

| Pole | Opis |
|------|------|
| `feature_type_id` | FK do FeatureType w PPM |
| `shop_id` | FK do PrestaShopShop |
| `prestashop_feature_id` | id_feature w PrestaShop |
| `sync_direction` | both / ppm_to_ps / ps_to_ppm |
| `auto_create_values` | Automatyczne tworzenie wartosci w PS |

### 9.2 Sync Flow: PPM -> PrestaShop

```
SyncProductToPrestaShop (Job)
    -> FeatureTransformer::buildProductFeaturesAssociations(productId, shop)
        -> Load features: ProductShopData.attribute_mappings.features (priorytet)
        -> Fallback: product_features table
        -> foreach feature:
            -> Get PrestashopFeatureMapping
            -> FeatureValueMapper::getOrCreateFeatureValueId(value)
            -> Build: [{ id: ps_feature_id, id_feature_value: ps_value_id }]
    -> PrestaShop8Client::updateProduct(associations)
```

### 9.3 Serwisy sync

| Serwis | Plik | Opis |
|--------|------|------|
| PrestaShopFeatureSyncService | `app/Services/PrestaShop/` | Koordynator syncu |
| FeatureTransformer | `app/Services/PrestaShop/Transformers/` | Transformacja danych PPM -> PS |
| FeatureValueMapper | `app/Services/PrestaShop/Mappers/` | Mapowanie wartosci (auto-create) |
| FeatureMappingManager | `app/Services/PrestaShop/` | CRUD mapowania |

---

## 10. Bulk Assign System

### 10.1 BulkAssignFeaturesJob

**Parametry:**

| Parametr | Typ | Opis |
|----------|-----|------|
| `$templateId` | int | Szablon do zastosowania |
| `$scope` | string | `all_vehicles` / `by_category` |
| `$categoryId` | ?int | Filtr kategorii (jesli by_category) |
| `$action` | string | `add_features` / `replace_features` |
| `$jobId` | string | UUID do sledzenia postepu |
| `$userId` | int | Uzytkownik inicjujacy |

**Konfiguracja:** Tries: 3, Timeout: 600s, Queue: default

### 10.2 Scope

- **all_vehicles** - Wszystkie produkty w bazie
- **by_category** - Tylko produkty z wybranej kategorii

### 10.3 Action

- **add_features** - Dodaje brakujace cechy (nie nadpisuje istniejacych)
- **replace_features** - Zastepuje wszystkie cechy szablonowymi

### 10.4 JobProgress tracking

```blade
{{-- UI: Pasek postepu (fixed bottom-right, wire:poll.2s) --}}
@if($activeJobProgressId)
    <div wire:poll.2s="refreshJobProgress" class="fixed bottom-4 right-4 z-50 w-96">
        {{-- Progress bar z procentami --}}
        {{-- Statystyki: processed / total --}}
    </div>
@endif
```

---

## 11. Serwisy

### 11.1 FeatureManager

**Plik:** `app/Services/Product/FeatureManager.php` (369 linii)

| Metoda | Zwraca | Opis |
|--------|--------|------|
| `addFeature(Product, array)` | ProductFeature | Dodaj ceche do produktu |
| `updateFeature(ProductFeature, array)` | ProductFeature | Aktualizuj wartosc |
| `removeFeature(ProductFeature)` | bool | Usun ceche |
| `setFeatures(Product, array)` | Collection | Zastap wszystkie cechy |
| `getGroupedFeatures(Product)` | Collection | Cechy pogrupowane po typach |
| `getFormattedFeatures(Product)` | array | Cechy gotowe do wyswietlenia |
| `copyFeaturesFrom(Product, Product)` | Collection | Kopiuj cechy miedzy produktami |
| `bulkApplyFeatures(Collection, array)` | int | Masowe przypisanie |
| `compareFeatures(Product, Product)` | array | Porownaj cechy 2 produktow |

### 11.2 FeatureUsageService

**Plik:** `app/Services/Product/FeatureUsageService.php` (285 linii)

| Metoda | Opis |
|--------|------|
| `getUsageStatsForGroup(int $groupId)` | Statystyki dla typow w grupie |
| `getUsageStatsForFeatureType(int $typeId)` | Statystyki wartosci |
| `getProductsUsingFeatureValue(int $valueId)` | Produkty z wartoscia |
| `getProductsWithCustomValue(int $typeId)` | Produkty z custom value |
| `countProductsUsingFeatureType(int $typeId)` | Liczba produktow |
| `canDeleteFeatureType(int $typeId)` | Czy mozna usunac ceche |
| `getGroupsWithStats()` | Grupy ze statystykami (lewa kolumna browsera) |
| `getCustomValuesStats(int $typeId)` | Statystyki custom values |

---

## 12. Alpine.js Components

### 12.1 featureDragDrop

**Lokalizacja:** `feature-library-tab.blade.php` linie 24-124

**Stan:** `draggedFeatureId`, `draggedFromGroupId`, `dropTargetGroupId`, `insertBeforeId`, `expandTimer`

**Komunikacja z Livewire:** `$wire.moveFeatureToGroup(movedId, targetGroupId, beforeId)`

### 12.2 iconPicker

**Lokalizacja:** `feature-library-tab.blade.php` linie 354-415

**Stan:** `open`, `selected` (entangled), `activeCategory`, `icons`, `categories`

**Komunikacja z Livewire:** `$wire.entangle('featureGroupIcon')` - bidirectional

### 12.3 templateCardPreview

**Lokalizacja:** `feature-templates-tab.blade.php` linia 23

**Stan:** `showPreview: false` - toggle rozwijania karty szablonu

---

## 13. CSS Architecture

### 13.1 Grupy klas CSS

**Plik:** `resources/css/admin/feature-browser.css` (1275 linii)

| Grupa | Prefix | Linie | Opis |
|-------|--------|-------|------|
| Browser Layout | `.feature-browser__*` | 1-640 | 3-kolumnowy browser |
| Tree View | `.feature-tree__*` | 714-860 | Accordion tree (library) |
| Template Grid | `.template-card-v2*`, `.template-grid` | 866-1012 | Card grid (templates) |
| DnD Library | `.feature-drag-handle`, `--insert-*`, `--drop-target` | 1037-1133 | Drag & drop cross-group |
| DnD Templates | `.template-drag-handle`, `.template-sortable-ghost` | 1138-1167 | Wire:sortable |
| Icon Picker | `.icon-picker-*` | 1172-1275 | Grid dropdown |

### 13.2 Responsive breakpoints

| Breakpoint | Zmiana |
|------------|--------|
| <= 1024px | Template grid: 2 kolumny |
| <= 768px | Template grid: 1 kolumna, Tree padding: 2rem |

### 13.3 Dark theme compliance

Wszystkie klasy uzywaja:
- `var(--mpp-primary)` / `var(--mpp-primary-rgb)` dla brand color
- `var(--bg-card)`, `var(--border-color)`, `var(--text-muted)` dla tla/obwodek/tekstu
- Brak `dark:` prefix (admin = staly dark mode)
- Brak inline styles w Blade

---

## 14. Permissions i Security

### 14.1 Route middleware

```php
Route::get('/features/vehicles', [VehicleFeatureController::class, 'index'])
    ->name('admin.features.vehicles.index')
    ->withoutMiddleware(['auth']); // DEVELOPMENT: Auth disabled
```

**UWAGA:** W produkcji nalezy wlaczyc middleware auth i dodac explicit permission gate.

### 14.2 Walidacje

- Feature Group: name (required, max:255), code (required, max:100), sort_order (required, int, min:0)
- Feature Type: name (required, max:255), code (required, max:100), value_type (required, in:text,number,bool,select)
- Templates: Predefiniowane szablony chronione (is_predefined=1)

### 14.3 Cascade delete behavior

| Zrodlo | Cel | Behavior |
|--------|-----|----------|
| feature_groups | feature_types | SET NULL (cechy pozostaja bez grupy) |
| feature_types | feature_values | CASCADE (wartosci usuwane) |
| feature_types | product_features | CASCADE (przypisania usuwane) |
| products | product_features | CASCADE |
| feature_values | product_features.feature_value_id | SET NULL |

---

## 15. Diagramy

### 15.1 Component Hierarchy

```
/admin/features/vehicles
    |
    +-- VehicleFeatureManagement.php (main)
         |
         +-- FeatureBrowserTab.php
         |      +-- FeatureUsageService
         |
         +-- FeatureLibraryTab.php
         |      +-- FeatureGroup model
         |      +-- FeatureType model
         |
         +-- FeatureTemplatesTab.php
                +-- FeatureTemplate model
                +-- BulkAssignFeaturesJob
                       +-- FeatureManager service
                       +-- JobProgressService
```

### 15.2 Data Flow: User -> DB -> PrestaShop

```
[Admin Panel: Cechy Pojazdow]
    |
    | CRUD operations (Livewire)
    v
[feature_groups] <-- [feature_types] <-- [feature_values]
                          |
                          | (FK)
                          v
                    [product_features] <-- [ProductForm: Attributes Tab]
                          |                       |
                          |                       | applyFeatureTemplate()
                          |                       v
                          |               [feature_templates]
                          |
                          | (sync)
                          v
                [prestashop_feature_mappings]
                          |
                          | FeatureTransformer + FeatureValueMapper
                          v
                   [PrestaShop API]
                          |
                          v
                [ps_feature + ps_feature_value + ps_product_feature]
```

### 15.3 Bulk Assign Flow

```
[Admin: Szablony Cech]
    |
    | bulkAssign() -> Dispatch
    v
[BulkAssignFeaturesJob] (Queue)
    |
    | FeatureManager::bulkApplyFeatures()
    |
    +-- scope: all_vehicles -> Product::all()
    +-- scope: by_category  -> Product::where(category_id)
    |
    | foreach product:
    |   action: add_features     -> merge (keep existing)
    |   action: replace_features -> delete + create
    |
    +-- Update JobProgress (polled by wire:poll.2s)
    v
[Completed: N products updated]
```
