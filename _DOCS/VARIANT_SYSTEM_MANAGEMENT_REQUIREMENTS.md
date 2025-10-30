# SYSTEM ZARZĄDZANIA WARIANTAMI - WYMAGANIA I SPECYFIKACJA

**Dokument:** Requirements & Architecture Specification
**Projekt:** PPM-CC-Laravel
**Moduł:** System Zarządzania Wariantami (Variant System Management)
**Data utworzenia:** 2025-10-24
**Status:** ✅ APPROVED - Ready for Implementation

---

## 📋 SPIS TREŚCI

1. [Przegląd Koncepcji](#przegląd-koncepcji)
2. [User Stories](#user-stories)
3. [Wireframes & UI Mockups](#wireframes--ui-mockups)
4. [Database Schema](#database-schema)
5. [PrestaShop Integration](#prestashop-integration)
6. [UI/UX Specifications](#uiux-specifications)
7. [Business Logic](#business-logic)
8. [Technical Requirements](#technical-requirements)
9. [Implementation Plan](#implementation-plan)

---

## 🎯 PRZEGLĄD KONCEPCJI

### Problem Statement

**BŁĘDNA implementacja (ETAP_05b FAZA 1-3):**
- ❌ `/admin/variants` pokazywał listę ProductVariant records (duplikat ProductList)
- ❌ Auto-generate variants w panelu zarządzania (niewłaściwe miejsce)
- ❌ Bulk operations na wariantach produktów (powinno być w ProductList)

### Correct Concept

**Panel `/admin/variants` = System Zarządzania Definicjami Wariantów**

**CO TO JEST:**
- Centralny panel definiowania **GRUP WARIANTÓW** (np. Kolor, Rozmiar, Materiał)
- Zarządzanie **WARTOŚCIAMI** tych grup (np. Czerwony, Niebieski dla grupy Kolor)
- Weryfikacja **ZGODNOŚCI** z PrestaShop stores
- Statystyki **UŻYCIA** w produktach PPM

**CO TO NIE JEST:**
- ❌ Lista konkretnych wariantów produktów (to jest w ProductList)
- ❌ Auto-generator wariantów (to jest w ProductForm)
- ❌ Bulk edit wariantów produktów (to jest w ProductList)

### Key Concepts

**1. GRUPA WARIANTÓW (AttributeType)**
```
Przykład: "Kolor"
- Code: "color"
- Type: "color" (używa color picker)
- Icon: "palette"
- Wartości: [Czerwony, Niebieski, Zielony, ...]
```

**2. WARTOŚĆ GRUPY (AttributeValue)**
```
Przykład: "Czerwony"
- Code: "red"
- Label PPM: "Czerwony"
- Color (tylko dla type=color): "#ff0000"
- PrestaShop mapping:
  - Sklep A: "Czerwony" (zsynchronizowane)
  - Sklep B: "Red" (zsynchronizowane)
  - Sklep C: brak (niezdefiniowane)
```

**3. UŻYCIE W PRODUKTACH**
```
Ile produktów w PPM używa:
- Grupy "Kolor": 45 produktów
- Wartości "Czerwony": 12 produktów
```

**4. PRESTASHOP SYNC STATUS**
```
Weryfikacja czy:
- Grupa istnieje w PS: ps_attribute_group (✅/❌)
- Wartość istnieje w PS: ps_attribute (✅/❌)
- Labele są zgodne: compare PPM vs PS
```

---

## 👤 USER STORIES

### US-1: Operator Definiuje Grupę Wariantów

**Jako:** Operator systemu
**Chcę:** Stworzyć nową grupę wariantów (np. "Kolor")
**Aby:** Móc później przypisywać produktom konkretne kolory

**Acceptance Criteria:**
- [ ] Mogę otworzyć formularz tworzenia grupy
- [ ] Podaję nazwę (np. "Kolor")
- [ ] Wybieram typ (dropdown: color, text, number)
- [ ] Jeśli type=color → automatycznie dodaje color picker support
- [ ] Wybieram ikonę z listy (np. palette, ruler, fabric)
- [ ] Zapisuję grupę
- [ ] Grupa pojawia się na głównej liście

---

### US-2: Operator Zarządza Wartościami Grupy

**Jako:** Operator systemu
**Chcę:** Dodać wartości do grupy "Kolor" (np. Czerwony, Niebieski)
**Aby:** Produkty mogły mieć te konkretne kolory

**Acceptance Criteria:**
- [ ] Klikam "Zarządzaj Wartościami" przy grupie "Kolor"
- [ ] Widzę listę istniejących wartości
- [ ] Mogę dodać nową wartość:
  - [ ] Podaję code (np. "red")
  - [ ] Podaję label (np. "Czerwony")
  - [ ] Jeśli type=color → wybieram kolor z pełnej palety (wheel picker)
  - [ ] Widzę live preview koloru (#ff0000 + color swatch)
- [ ] Mogę edytować istniejące wartości
- [ ] Mogę usunąć wartość (jeśli nie jest używana)
- [ ] Widzę ostrzeżenie przed usunięciem jeśli wartość jest używana

---

### US-3: Operator Widzi Statystyki Użycia

**Jako:** Operator systemu
**Chcę:** Wiedzieć ile produktów używa grupy/wartości
**Aby:** Ocenić popularność i bezpieczeństwo usunięcia

**Acceptance Criteria:**
- [ ] Dla każdej grupy widzę: "45 produktów używa tej grupy"
- [ ] Dla każdej wartości widzę: "12 produktów używa tej wartości"
- [ ] Mogę kliknąć na liczbę → otworzy się lista produktów
- [ ] Przed usunięciem widzę ostrzeżenie z liczbą affected produktów

---

### US-4: Operator Weryfikuje PrestaShop Sync

**Jako:** Operator systemu
**Chcę:** Zobaczyć status synchronizacji z PrestaShopami
**Aby:** Wiedzieć czy wartości są dostępne we wszystkich sklepach

**Acceptance Criteria:**
- [ ] Dla każdej wartości widzę listę podłączonych PrestaShopów
- [ ] Status synchronizacji:
  - [ ] ✅ Zsynchronizowane (label z PS: "Czerwony")
  - [ ] ⚠️ Brak wartości na PS (niezdefiniowane)
  - [ ] ❌ Konflikt (różne labele: PPM="Czerwony" vs PS="Red")
- [ ] Mogę kliknąć na sklep → zobaczyć szczegóły (attribute_id, attribute_group_id)
- [ ] Mogę zainicjować synchronizację (przycisk "Synchronizuj z PS")

---

### US-5: Operator Używa Wartości w ProductForm

**Jako:** Operator systemu
**Chcę:** Przy tworzeniu produktu wybrać warianty z centralnej listy
**Aby:** Produkty były spójne z systemem i PrestaShopami

**Acceptance Criteria:**
- [ ] W ProductForm tab "Warianty"
- [ ] Wybieram grupę wariantów: dropdown z lista z `/admin/variants`
- [ ] Wybieram wartości: checkboxes z lista z `/admin/variants`
- [ ] Widzę color preview dla wartości typu color
- [ ] Klikam "Generuj Warianty" → system tworzy wszystkie kombinacje
- [ ] Wartości są spójne z PrestaShopami (automatyczne mapowanie)

---

## 🎨 WIREFRAMES & UI MOCKUPS

### Screen 1: Główny Panel Grup Wariantów (`/admin/variants`)

```
┌─────────────────────────────────────────────────────────────────────┐
│ ZARZĄDZANIE WARIANTAMI                                              │
├─────────────────────────────────────────────────────────────────────┤
│ [+ Dodaj Grupę Wariantów]                                    [🔍 ]  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│ ┌──────────────────────────────────────────────────────────────┐   │
│ │ 🎨 Kolor (color)                                              │   │
│ │ ──────────────────────────────────────────────────────────────│   │
│ │ Typ: Color Picker                                             │   │
│ │ Wartości: 12                                                  │   │
│ │ Produkty w PPM: 45                                            │   │
│ │                                                               │   │
│ │ PrestaShopy:                                                  │   │
│ │  ✅ Sklep A (12/12 wartości)                                 │   │
│ │  ✅ Sklep B (12/12 wartości)                                 │   │
│ │  ⚠️ Sklep C (8/12 wartości)                                  │   │
│ │                                                               │   │
│ │ [Zarządzaj Wartościami] [Edytuj] [Usuń]                      │   │
│ └──────────────────────────────────────────────────────────────┘   │
│                                                                       │
│ ┌──────────────────────────────────────────────────────────────┐   │
│ │ 📏 Rozmiar (size)                                             │   │
│ │ ──────────────────────────────────────────────────────────────│   │
│ │ Typ: Text                                                     │   │
│ │ Wartości: 8                                                   │   │
│ │ Produkty w PPM: 32                                            │   │
│ │                                                               │   │
│ │ PrestaShopy:                                                  │   │
│ │  ✅ Sklep A (8/8 wartości)                                   │   │
│ │  ✅ Sklep B (8/8 wartości)                                   │   │
│ │  ✅ Sklep C (8/8 wartości)                                   │   │
│ │                                                               │   │
│ │ [Zarządzaj Wartościami] [Edytuj] [Usuń]                      │   │
│ └──────────────────────────────────────────────────────────────┘   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

**Elementy UI:**
- Cards layout (responsive: 3/2/1 columns)
- Status badges dla PrestaShopów (✅/⚠️/❌)
- Action buttons: Zarządzaj Wartościami, Edytuj, Usuń
- Search/filter box (góra)
- Add button (prominent, top right)

---

### Screen 2: Modal Tworzenia Grupy

```
┌─────────────────────────────────────────────────────────────────┐
│ DODAJ GRUPĘ WARIANTÓW                                      [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│ Nazwa Grupy:                                                      │
│ [________________________]                                        │
│                                                                   │
│ Code (unikalny identyfikator):                                   │
│ [________________________]  [🔄 Auto-generate z nazwy]           │
│                                                                   │
│ Typ:                                                              │
│ ( ) Color Picker  - dla kolorów z paletą                         │
│ ( ) Text          - dla wartości tekstowych                      │
│ ( ) Number        - dla wartości numerycznych                    │
│                                                                   │
│ Ikona:                                                            │
│ [Dropdown: 🎨 Palette, 📏 Ruler, 🧵 Fabric, ...]               │
│                                                                   │
│ Kolejność wyświetlania:                                           │
│ [___] (liczba, domyślnie auto-increment)                          │
│                                                                   │
│                                            [Anuluj]  [Zapisz]     │
└─────────────────────────────────────────────────────────────────┘
```

---

### Screen 3: Modal Zarządzania Wartościami

```
┌───────────────────────────────────────────────────────────────────────┐
│ WARTOŚCI GRUPY: Kolor                                            [X]  │
├───────────────────────────────────────────────────────────────────────┤
│ [+ Dodaj Wartość]                                            [🔍 ]    │
├───────────────────────────────────────────────────────────────────────┤
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────┐   │
│ │ 🔴 #ff0000  Czerwony                                            │   │
│ │ ─────────────────────────────────────────────────────────────────│  │
│ │ Code: red                                                        │   │
│ │ Produkty w PPM: 12                                               │   │
│ │                                                                  │   │
│ │ PrestaShopy:                                                     │   │
│ │  ✅ Sklep A: "Czerwony" (ID: 25)                               │   │
│ │  ✅ Sklep B: "Red" (ID: 14) ⚠️ Różny label                    │   │
│ │  ❌ Sklep C: brak wartości                                     │   │
│ │                                                                  │   │
│ │ [Edytuj] [Usuń] [Synchronizuj z PS]                             │   │
│ └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│ ┌─────────────────────────────────────────────────────────────────┐   │
│ │ 🔵 #0000ff  Niebieski                                           │   │
│ │ ─────────────────────────────────────────────────────────────────│  │
│ │ Code: blue                                                       │   │
│ │ Produkty w PPM: 8                                                │   │
│ │                                                                  │   │
│ │ PrestaShopy:                                                     │   │
│ │  ✅ Sklep A: "Niebieski" (ID: 26)                              │   │
│ │  ✅ Sklep B: "Blue" (ID: 15)                                   │   │
│ │  ✅ Sklep C: "Niebieski" (ID: 42)                              │   │
│ │                                                                  │   │
│ │ [Edytuj] [Usuń] [Synchronizuj z PS]                             │   │
│ └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└───────────────────────────────────────────────────────────────────────┘
```

**Elementy UI:**
- Color swatch (dla type=color)
- Hex code display
- PrestaShop mapping list z statusami
- Warning badges dla konfliktów
- Sync button per wartość

---

### Screen 4: Modal Dodawania/Edycji Wartości (Type: Color)

```
┌─────────────────────────────────────────────────────────────────┐
│ DODAJ WARTOŚĆ: Kolor                                       [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│ Code:                                                             │
│ [________________________]                                        │
│                                                                   │
│ Label:                                                            │
│ [________________________]                                        │
│                                                                   │
│ Kolor (Hex):                                                      │
│ ┌─────────────────┬─────────────────────────────────────┐        │
│ │                 │                                       │        │
│ │   [COLOR]       │  ┌──────────────────────────┐       │        │
│ │   WHEEL         │  │                          │       │        │
│ │   PICKER        │  │  SATURATION/LIGHTNESS    │       │        │
│ │   (Hue Ring)    │  │  SQUARE                  │       │        │
│ │                 │  │                          │       │        │
│ │   🔴           │  │         ╳ Selected       │       │        │
│ │  🟠 🟡        │  │                          │       │        │
│ │ 🟢   🔵       │  │                          │       │        │
│ │  🟣 🟤        │  │                          │       │        │
│ │                 │  └──────────────────────────┘       │        │
│ └─────────────────┴─────────────────────────────────────┘        │
│                                                                   │
│ Hex Input: [#_______]  [🎨 Użyj Pickera]                        │
│                                                                   │
│ Preview: ████████ #ff0000 Czerwony                               │
│                                                                   │
│ Kolejność wyświetlania:                                           │
│ [___] (liczba, domyślnie auto-increment)                          │
│                                                                   │
│ ⚠️ PrestaShop sync: Wartość zostanie automatycznie              │
│    zsynchronizowana z podłączonymi sklepami przy zapisie.        │
│                                                                   │
│                                            [Anuluj]  [Zapisz]     │
└─────────────────────────────────────────────────────────────────┘
```

**Elementy UI Color Picker:**
- **Hue Ring** (color wheel) - wybór podstawowego koloru
- **Saturation/Lightness Square** - fine-tuning odcienia
- **Hex Input** - manualny input (#ffffff format)
- **Live Preview** - color swatch + hex + label
- **PrestaShop format compliance** - garantuje format #ffffff

---

### Screen 5: Lista Produktów Używających Wartości

```
┌─────────────────────────────────────────────────────────────────┐
│ PRODUKTY UŻYWAJĄCE: Czerwony (12)                          [X]  │
├─────────────────────────────────────────────────────────────────┤
│ [🔍 Szukaj...]                                                   │
├─────────────────────────────────────────────────────────────────┤
│ SKU           Nazwa Produktu                    Warianty         │
│ ─────────────────────────────────────────────────────────────────│
│ ABC123        Koszulka Polo                     3 (S,M,L)       │
│ ABC456        T-shirt Basic                     2 (M,L)         │
│ DEF789        Bluza z kapturem                  4 (XS,S,M,L)    │
│ ...                                                              │
│                                                                   │
│                                            [Zamknij]              │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ DATABASE SCHEMA

### Istniejące Tabele (ETAP_05b FAZA 2.1)

**✅ DOBRE - Użyjemy:**

```sql
-- Grupy wariantów
attribute_types:
  id
  name              -- "Kolor"
  code              -- "color"
  type              -- "color" | "text" | "number"
  icon              -- "palette"
  position          -- INT (sorting order)
  is_active         -- BOOLEAN
  created_at
  updated_at

-- Wartości grup
attribute_values:
  id
  attribute_type_id -- FK → attribute_types.id
  code              -- "red"
  label             -- "Czerwony"
  color_hex         -- "#ff0000" (tylko dla type=color)
  position          -- INT (sorting order)
  is_active         -- BOOLEAN
  created_at
  updated_at

  UNIQUE (attribute_type_id, code)
```

### Nowe Tabele - PrestaShop Mapping

**WYMAGANE:**

```sql
-- PrestaShop Attribute Group Mapping
prestashop_attribute_group_mapping:
  id
  attribute_type_id           -- FK → attribute_types.id
  shop_id                     -- FK → shops.id
  prestashop_attribute_group_id -- INT (ps_attribute_group.id_attribute_group)
  prestashop_label            -- VARCHAR (public_name from PS)
  is_synced                   -- BOOLEAN
  last_synced_at              -- TIMESTAMP
  sync_status                 -- ENUM: 'synced', 'pending', 'conflict', 'missing'
  sync_notes                  -- TEXT (error messages, warnings)
  created_at
  updated_at

  UNIQUE (attribute_type_id, shop_id)

-- PrestaShop Attribute Value Mapping
prestashop_attribute_value_mapping:
  id
  attribute_value_id          -- FK → attribute_values.id
  shop_id                     -- FK → shops.id
  prestashop_attribute_id     -- INT (ps_attribute.id_attribute)
  prestashop_label            -- VARCHAR (name from PS)
  prestashop_color            -- VARCHAR (color from PS, dla type=color)
  is_synced                   -- BOOLEAN
  last_synced_at              -- TIMESTAMP
  sync_status                 -- ENUM: 'synced', 'conflict', 'missing'
  sync_notes                  -- TEXT
  created_at
  updated_at

  UNIQUE (attribute_value_id, shop_id)
```

### Tabele PrestaShop (Reference Only)

**DO ODCZYTU PRZEZ API:**

```sql
-- ps_attribute_group (PrestaShop DB)
ps_attribute_group:
  id_attribute_group    -- Primary Key
  is_color_group        -- BOOLEAN (1 if color picker)
  group_type            -- ENUM: 'select', 'radio', 'color'
  position              -- INT

-- ps_attribute_group_lang
ps_attribute_group_lang:
  id_attribute_group    -- FK
  id_lang               -- FK (language ID)
  name                  -- VARCHAR (e.g. "Kolor")
  public_name           -- VARCHAR (e.g. "Kolor")

-- ps_attribute
ps_attribute:
  id_attribute          -- Primary Key
  id_attribute_group    -- FK → ps_attribute_group.id_attribute_group
  color                 -- VARCHAR (#ffffff format)
  position              -- INT

-- ps_attribute_lang
ps_attribute_lang:
  id_attribute          -- FK
  id_lang               -- FK
  name                  -- VARCHAR (e.g. "Czerwony")
```

---

## 🔗 PRESTASHOP INTEGRATION

### Integration Flow

```
PPM AttributeType → PrestaShop ps_attribute_group
PPM AttributeValue → PrestaShop ps_attribute
```

### Synchronization Logic

**1. TWORZENIE GRUPY w PPM:**
```php
1. Operator tworzy AttributeType "Kolor"
2. System dla każdego podłączonego Shop:
   a. Query PS API: GET /api/attribute_groups (search by name)
   b. If exists:
      - Zapisz mapping (prestashop_attribute_group_mapping)
      - Status: 'synced'
   c. If not exists:
      - Status: 'missing'
      - Operator może zainicjować create w PS
```

**2. TWORZENIE WARTOŚCI w PPM:**
```php
1. Operator tworzy AttributeValue "Czerwony" (#ff0000)
2. System dla każdego podłączonego Shop:
   a. Query PS API: GET /api/attributes (filter by group + color)
   b. If exists with matching color:
      - Zapisz mapping (prestashop_attribute_value_mapping)
      - Status: 'synced'
   c. If exists but different color/label:
      - Status: 'conflict'
      - Warn operator
   d. If not exists:
      - Status: 'missing'
      - Operator może zainicjować create w PS
```

**3. VERIFY SYNC:**
```php
Okresowa weryfikacja (cron job co 1h):
1. Dla każdego mapping:
   a. Query PS API current state
   b. Compare z PPM state
   c. Update sync_status if changed
   d. Log conflicts/issues
```

### PrestaShop API Endpoints

**REQUIRED API CALLS:**

```php
// Read attribute groups
GET /api/attribute_groups?display=full
GET /api/attribute_groups/{id}

// Create attribute group
POST /api/attribute_groups
Body:
<prestashop>
  <attribute_group>
    <is_color_group>1</is_color_group>
    <group_type>color</group_type>
    <name><language id="1">Kolor</language></name>
    <public_name><language id="1">Kolor</language></name>
  </attribute_group>
</prestashop>

// Read attributes
GET /api/attributes?display=full&filter[id_attribute_group]={group_id}
GET /api/attributes/{id}

// Create attribute
POST /api/attributes
Body:
<prestashop>
  <attribute>
    <id_attribute_group>25</id_attribute_group>
    <color>#ff0000</color>
    <name><language id="1">Czerwony</language></name>
  </attribute>
</prestashop>
```

---

## 🎨 UI/UX SPECIFICATIONS

### Design System

**REUSE EXISTING:**
- Enterprise card components (`.enterprise-card`)
- Button styles (`.btn-enterprise-*`)
- Color palette (var(--color-primary))
- Typography (Inter font)
- Layout grid (CSS Grid)

**NEW COMPONENTS:**

#### 1. Color Picker Component

**Library:** [react-colorful](https://www.npmjs.com/package/react-colorful) lub [vue-color-kit](https://github.com/anish2690/vue-color-kit)

**Requirements:**
- Wheel/ring picker dla Hue selection
- Saturation/Lightness square
- Hex input (#ffffff format)
- Live preview swatch
- PrestaShop format compliance (#ffffff, not rgb())

**Implementation:**
```blade
<div class="color-picker-container">
    <!-- Alpine.js + Livewire integration -->
    <div x-data="colorPicker()"
         x-init="init()"
         wire:ignore>
        <!-- Color wheel -->
        <div class="color-wheel"
             @click="selectHue($event)">
            <!-- SVG circle with gradient -->
        </div>

        <!-- Saturation/Lightness square -->
        <div class="color-square"
             @click="selectShade($event)">
            <!-- Canvas with gradient -->
        </div>

        <!-- Hex input -->
        <input type="text"
               x-model="hexValue"
               wire:model.live="color_hex"
               pattern="^#[0-9A-Fa-f]{6}$"
               maxlength="7">

        <!-- Preview -->
        <div class="color-preview"
             :style="'background-color: ' + hexValue">
        </div>
    </div>
</div>
```

#### 2. PrestaShop Sync Status Badge

```blade
<span class="ps-sync-badge ps-sync-{{ $status }}">
    @if($status === 'synced')
        ✅ Zsynchronizowane
    @elseif($status === 'conflict')
        ⚠️ Konflikt
    @elseif($status === 'missing')
        ❌ Brak w PS
    @elseif($status === 'pending')
        🔄 Oczekuje
    @endif
</span>
```

**CSS:**
```css
.ps-sync-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.ps-sync-synced {
    background: var(--color-success-bg);
    color: var(--color-success-text);
    border: 1px solid var(--color-success-border);
}

.ps-sync-conflict {
    background: var(--color-warning-bg);
    color: var(--color-warning-text);
    border: 1px solid var(--color-warning-border);
}

.ps-sync-missing {
    background: var(--color-error-bg);
    color: var(--color-error-text);
    border: 1px solid var(--color-error-border);
}
```

### Responsive Design

**Breakpoints:**
- Desktop (>1024px): 3 columns grid
- Tablet (768-1024px): 2 columns grid
- Mobile (<768px): 1 column, stack layout

**Mobile-specific:**
- Color picker adapts to smaller screen
- PrestaShop list stacks vertically
- Action buttons stack in column

---

## ⚙️ BUSINESS LOGIC

### Create Attribute Type

```php
Service: AttributeManager::createAttributeType(array $data)

Flow:
1. Validate input (name, code unique, type in enum)
2. Create AttributeType record
3. For each connected Shop:
   a. Check if group exists in PS (API call)
   b. Create prestashop_attribute_group_mapping
   c. Set sync_status based on PS response
4. Return AttributeType with mapping status
5. Dispatch event: AttributeTypeCreated
```

### Create Attribute Value

```php
Service: AttributeManager::createAttributeValue(array $data)

Flow:
1. Validate input (code unique per type, color_hex format)
2. Validate color_hex if type=color (#ffffff pattern)
3. Create AttributeValue record
4. For each connected Shop:
   a. Get PS attribute_group_id from mapping
   b. Check if attribute exists in PS (API call)
   c. If type=color: verify color match
   d. Create prestashop_attribute_value_mapping
   e. Set sync_status
5. Return AttributeValue with mapping status
6. Dispatch event: AttributeValueCreated
```

### Sync with PrestaShop

```php
Service: PrestaShopSyncService::syncAttributeGroup($attributeTypeId, $shopId)

Flow:
1. Get AttributeType
2. Get Shop credentials
3. Query PS API: GET /api/attribute_groups (search by name)
4. If found:
   a. Update mapping with PS IDs
   b. Status: 'synced'
   c. Sync all values for this group
5. If not found:
   a. Offer to CREATE in PS
   b. POST /api/attribute_groups
   c. Create mapping after success
6. Log sync result
```

### Delete Protection

```php
Service: AttributeManager::deleteAttributeType($attributeType)

Flow:
1. Count products using this type:
   - Query variant_attributes table
   - Count distinct variant_id
2. If count > 0:
   a. Return error with count
   b. Offer "Force Delete" option (cascading)
3. If count = 0:
   a. Delete AttributeType
   b. Cascade delete AttributeValues
   c. Cascade delete PS mappings
   d. Dispatch event: AttributeTypeDeleted
```

---

## 🔧 TECHNICAL REQUIREMENTS

### Livewire Components

**1. AttributeSystemManager** (główny panel)
- Route: `/admin/variants`
- File: `app/Http/Livewire/Admin/Variants/AttributeSystemManager.php`
- Blade: `resources/views/livewire/admin/variants/attribute-system-manager.blade.php`
- Features:
  - Cards grid layout
  - Create/Edit/Delete AttributeType
  - Statystyki użycia
  - PrestaShop sync status
  - Search/filter

**2. AttributeValueManager** (modal zarządzania wartościami)
- Component: `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
- Blade: `resources/views/livewire/admin/variants/attribute-value-manager.blade.php`
- Features:
  - List wartości dla grupy
  - Create/Edit/Delete AttributeValue
  - Color picker (dla type=color)
  - PrestaShop sync per wartość
  - Statystyki produktów

**3. ColorPickerComponent** (standalone color picker)
- Component: `app/Http/Livewire/Admin/Components/ColorPicker.php`
- Blade: `resources/views/livewire/admin/components/color-picker.blade.php`
- Features:
  - Wheel/ring hue selection
  - Saturation/lightness square
  - Hex input with validation
  - Live preview
  - Alpine.js integration

**4. PrestaShopSyncPanel** (panel weryfikacji sync)
- Component: `app/Http/Livewire/Admin/Variants/PrestaShopSyncPanel.php`
- Blade: `resources/views/livewire/admin/variants/prestashop-sync-panel.blade.php`
- Features:
  - Lista wszystkich mappings
  - Status per sklep
  - Bulk sync operations
  - Conflict resolution UI

### Services

**1. AttributeManager** (już istnieje - ETAP_05b FAZA 2.1)
- Rozszerz o PrestaShop sync methods
- Add: syncWithPrestaShop($attributeType, $shop)
- Add: verifyPrestaShopMapping($attributeValue, $shop)

**2. PrestaShopSyncService** (nowy)
- File: `app/Services/PrestaShop/PrestaShopSyncService.php`
- Methods:
  - syncAttributeGroup($attributeTypeId, $shopId)
  - syncAttributeValue($attributeValueId, $shopId)
  - verifySync($attributeTypeId)
  - createAttributeGroupInPS($attributeType, $shop)
  - createAttributeValueInPS($attributeValue, $shop)

**3. PrestaShopApiClient** (już istnieje?)
- Verify endpoint support:
  - GET /api/attribute_groups
  - POST /api/attribute_groups
  - GET /api/attributes
  - POST /api/attributes

### Migrations

**Required:**

```php
// Migration: create_prestashop_attribute_group_mapping_table
Schema::create('prestashop_attribute_group_mapping', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_type_id')->constrained()->onDelete('cascade');
    $table->foreignId('shop_id')->constrained()->onDelete('cascade');
    $table->integer('prestashop_attribute_group_id')->nullable();
    $table->string('prestashop_label')->nullable();
    $table->boolean('is_synced')->default(false);
    $table->timestamp('last_synced_at')->nullable();
    $table->enum('sync_status', ['synced', 'pending', 'conflict', 'missing'])->default('pending');
    $table->text('sync_notes')->nullable();
    $table->timestamps();

    $table->unique(['attribute_type_id', 'shop_id']);
});

// Migration: create_prestashop_attribute_value_mapping_table
Schema::create('prestashop_attribute_value_mapping', function (Blueprint $table) {
    $table->id();
    $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
    $table->foreignId('shop_id')->constrained()->onDelete('cascade');
    $table->integer('prestashop_attribute_id')->nullable();
    $table->string('prestashop_label')->nullable();
    $table->string('prestashop_color', 7)->nullable();
    $table->boolean('is_synced')->default(false);
    $table->timestamp('last_synced_at')->nullable();
    $table->enum('sync_status', ['synced', 'conflict', 'missing'])->default('pending');
    $table->text('sync_notes')->nullable();
    $table->timestamps();

    $table->unique(['attribute_value_id', 'shop_id']);
});
```

### Events

```php
// app/Events/AttributeTypeCreated.php
class AttributeTypeCreated {
    public AttributeType $attributeType;
}

// app/Events/AttributeValueCreated.php
class AttributeValueCreated {
    public AttributeValue $attributeValue;
}

// app/Listeners/SyncNewAttributeWithPrestaShops.php
class SyncNewAttributeWithPrestaShops {
    public function handle(AttributeTypeCreated $event) {
        // Auto-sync with all connected shops
    }
}
```

### Jobs

```php
// app/Jobs/SyncAttributeWithPrestaShop.php
class SyncAttributeWithPrestaShop implements ShouldQueue {
    public function handle(AttributeType $attributeType, Shop $shop) {
        // Background sync job
    }
}

// app/Jobs/VerifyAllAttributeSync.php
class VerifyAllAttributeSync implements ShouldQueue {
    public function handle() {
        // Periodic verification (cron: hourly)
    }
}
```

---

## 📝 IMPLEMENTATION PLAN

### Phase 0: Cleanup & Preparation (2h)

**Zadania:**
1. ✅ Backup istniejącego kodu (ETAP_05b FAZA 1-3)
2. ❌ Usuń niepotrzebne komponenty:
   - `VariantManagement.php` (stary koncept)
   - `BulkPricesModal.php` (przeniesione do ProductList)
   - `BulkStockModal.php` (przeniesione do ProductList)
   - `BulkImagesModal.php` (przeniesione do ProductList)
3. ✅ Zachowaj dobre komponenty:
   - `AttributeTypeManager` (podstawa, do rozbudowy)
   - `AttributeValueManager` (podstawa, do rozbudowy)
   - `AttributeManager` service (do rozbudowy)

**Deliverables:**
- Czysty codebase gotowy na nową implementację
- Backup w `_BACKUP/etap05b_old_implementation/`

---

### Phase 1: Database Schema (3-4h)

**Zadania:**
1. Stwórz migrations dla PrestaShop mapping tables
2. Update seeders (AttributeTypeSeeder, AttributeValueSeeder)
3. Wykonaj migrations na produkcji (backup DB first!)
4. Verify schema integrity

**Deliverables:**
- `database/migrations/2025_10_24_*_create_prestashop_mappings.php`
- Updated seeders
- Schema deployed na production

---

### Phase 2: PrestaShop Integration Layer (8-10h)

**Zadania:**
1. Stwórz `PrestaShopSyncService`
2. Implement API methods (attribute_groups, attributes)
3. Implement sync logic (create, verify, update)
4. Add error handling + logging
5. Unit tests dla service

**Deliverables:**
- `app/Services/PrestaShop/PrestaShopSyncService.php`
- Tests: `tests/Unit/Services/PrestaShopSyncServiceTest.php`

---

### Phase 3: Color Picker Component (6-8h)

**Zadania:**
1. Research color picker libraries (Alpine.js compatible)
2. Implement `ColorPickerComponent`
3. Integrate z Livewire (wire:model)
4. Add hex validation
5. Add PrestaShop format compliance (#ffffff)
6. CSS styling (enterprise theme)

**Deliverables:**
- `app/Http/Livewire/Admin/Components/ColorPicker.php`
- `resources/views/livewire/admin/components/color-picker.blade.php`
- `resources/css/admin/color-picker.css`

---

### Phase 4: AttributeSystemManager (10-12h)

**Zadania:**
1. Refactor `AttributeTypeManager` → `AttributeSystemManager`
2. Add cards grid layout
3. Add PrestaShop sync status display
4. Add statistics (produkty w PPM, sync status)
5. Implement Create/Edit/Delete modals
6. Add search/filter functionality
7. Frontend verification

**Deliverables:**
- `app/Http/Livewire/Admin/Variants/AttributeSystemManager.php`
- `resources/views/livewire/admin/variants/attribute-system-manager.blade.php`
- Updated CSS
- Screenshots verification

---

### Phase 5: AttributeValueManager Enhancement (8-10h)

**Zadania:**
1. Refactor istniejącego `AttributeValueManager`
2. Integrate ColorPickerComponent
3. Add PrestaShop sync panel per wartość
4. Add produkty używające wartości (modal/list)
5. Add sync operations (verify, create in PS)
6. Frontend verification

**Deliverables:**
- Updated `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
- Updated blade template
- Screenshots verification

---

### Phase 6: PrestaShop Sync Panel (6-8h)

**Zadania:**
1. Stwórz `PrestaShopSyncPanel` component
2. List wszystkich mappings (grupy + wartości)
3. Status indicators per sklep
4. Bulk sync operations
5. Conflict resolution UI
6. Frontend verification

**Deliverables:**
- `app/Http/Livewire/Admin/Variants/PrestaShopSyncPanel.php`
- Blade template
- Screenshots

---

### Phase 7: Integration & Testing (8-10h)

**Zadania:**
1. Integration tests (E2E workflow)
2. Browser tests (Dusk)
3. PrestaShop API mocks/stubs (testing)
4. Production deployment test
5. User acceptance testing
6. Performance optimization

**Deliverables:**
- Tests: `tests/Feature/VariantSystemTest.php`
- Tests: `tests/Browser/VariantSystemTest.php`
- Performance report

---

### Phase 8: Documentation & Deployment (4-6h)

**Zadania:**
1. Update CLAUDE.md
2. Create user guide (`_DOCS/VARIANT_SYSTEM_USER_GUIDE.md`)
3. Create admin documentation
4. Final deployment na production
5. Verification (screenshots, testing)
6. Agent report

**Deliverables:**
- Updated documentation
- Deployment complete
- Agent report w `_AGENT_REPORTS/`

---

## 📊 EFFORT ESTIMATION

| Phase | Estimated Hours | Agent(s) |
|-------|----------------|----------|
| Phase 0: Cleanup | 2h | general-purpose |
| Phase 1: Database | 3-4h | laravel-expert |
| Phase 2: PS Integration | 8-10h | prestashop-api-expert |
| Phase 3: Color Picker | 6-8h | frontend-specialist |
| Phase 4: SystemManager | 10-12h | livewire-specialist |
| Phase 5: ValueManager | 8-10h | livewire-specialist |
| Phase 6: SyncPanel | 6-8h | livewire-specialist |
| Phase 7: Testing | 8-10h | debugger + general |
| Phase 8: Documentation | 4-6h | documentation-reader |
| **TOTAL** | **55-70h** | **7-9 days** |

**Buffer:** +20% dla unforeseens = **66-84h** (8-11 dni roboczych)

---

## ✅ ACCEPTANCE CRITERIA

### AC-1: Operator może zarządzać grupami wariantów
- [ ] Może stworzyć nową grupę (nazwa, code, type, icon)
- [ ] Może edytować istniejącą grupę
- [ ] Może usunąć grupę (z ostrzeżeniem jeśli używana)
- [ ] Widzi statystyki użycia (produkty w PPM)

### AC-2: Operator może zarządzać wartościami grup
- [ ] Może dodać wartość do grupy
- [ ] Może edytować wartość
- [ ] Może usunąć wartość (z ostrzeżeniem)
- [ ] Dla type=color: używa color picker z pełną paletą
- [ ] Dla type=color: format hex (#ffffff)

### AC-3: Weryfikacja PrestaShop sync
- [ ] Widzi listę podłączonych PrestaShopów
- [ ] Widzi status sync per sklep (✅/⚠️/❌)
- [ ] Widzi labele z PrestaShopa
- [ ] Może zainicjować synchronizację
- [ ] Może zobaczyć szczegóły konfliktu

### AC-4: Statystyki użycia
- [ ] Widzi ile produktów używa grupy
- [ ] Widzi ile produktów używa wartości
- [ ] Może kliknąć na liczbę → lista produktów
- [ ] Lista produktów zawiera SKU, nazwę, liczbę wariantów

### AC-5: Integration z ProductForm
- [ ] W ProductForm wybiera grupy z listy z `/admin/variants`
- [ ] W ProductForm wybiera wartości z listy z `/admin/variants`
- [ ] Wartości są spójne z PrestaShopami (auto-mapping)

### AC-6: Performance & UX
- [ ] Panel ładuje się < 2s
- [ ] Color picker responsywny (mobile/desktop)
- [ ] PrestaShop sync async (nie blokuje UI)
- [ ] Error messages informatywne
- [ ] Success messages z konfirmacją

---

## 🚀 NEXT STEPS

**IMMEDIATE:**
1. ✅ Dokument wymagań zatwierdzony przez użytkownika
2. ⏳ Database schema migration (Phase 1)
3. ⏳ PrestaShop sync layer (Phase 2)
4. ⏳ Color picker component (Phase 3)

**DELEGATION:**
1. architect agent - review planu, architectural decisions
2. laravel-expert - database migrations (Phase 1)
3. prestashop-api-expert - sync service (Phase 2)
4. frontend-specialist - color picker (Phase 3)
5. livewire-specialist - managers components (Phase 4-6)

---

**Document Version:** 1.0
**Last Updated:** 2025-10-24
**Author:** Claude Code + User Specification
**Status:** ✅ READY FOR IMPLEMENTATION
