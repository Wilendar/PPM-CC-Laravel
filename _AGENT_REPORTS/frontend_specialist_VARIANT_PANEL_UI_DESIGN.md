# RAPORT PRACY AGENTA: frontend-specialist
**Data**: 2025-12-11 (aktualnie)
**Agent**: frontend-specialist
**Zadanie**: Projekt UI/UX dla panelu zarzadzania wariantami (AttributeValueManager)

---

## 1. ANALIZA OBECNEGO STANU

### 1.1 Architektura Komponentow

```
AttributeSystemManager (attribute-system-manager.blade.php)
    |
    +-- Grid 3-kolumnowy z kartami AttributeType
    |       |-- Header (nazwa, kod, status)
    |       |-- PrestaShop Sync Status
    |       |-- Stats (wartosci, produkty, display)
    |       +-- Actions (Edytuj, Wartosci, Usun)
    |
    +-- AttributeValueManager (modal, embeded component)
            |-- Header z search/filter
            |-- Tabela wartosci
            |-- value-edit-form (partial)
            |-- products-modal (partial)
            +-- sync-modal (partial)
```

### 1.2 Pliki Zrodlowe
| Plik | Linie | Opis |
|------|-------|------|
| `attribute-system-manager.blade.php` | 447 | Glowny panel z kartami grup |
| `attribute-value-manager.blade.php` | 222 | Modal zarzadzania wartosciami |
| `AttributeValueManager.php` | 276 | Livewire component |
| `partials/value-edit-form.blade.php` | 117 | Formularz edycji wartosci |
| `partials/products-modal.blade.php` | 91 | Modal produktow uzywajacych wartosci |
| `partials/sync-modal.blade.php` | - | Modal synchronizacji PS |

---

## 2. ZIDENTYFIKOWANE PROBLEMY UI/UX

### 2.1 Problemy Hierarchii Wizualnej

| Problem | Opis | Wplyw |
|---------|------|-------|
| **P1: Plaska struktura kart** | Wszystkie karty AttributeType wygladaja identycznie - brak wyroznienia waznych grup | Uzytkownik nie wie ktore grupy sa krytyczne |
| **P2: Brak drill-down** | Klikniecie "Wartosci" otwiera osobny modal bez kontekstu wizualnego | Utrata orientacji w hierarchii |
| **P3: Modal jako glowny UI** | Cala praca z wartosciami odbywa sie w modalu - ograniczona przestrzen | Niekomfortowa praca z duza iloscia danych |
| **P4: Brak wizualnej korelacji** | Statystyki (produkty, wartosci) to tylko liczby - brak wizualizacji | Trudnosc w ocenie skali uzycia |

### 2.2 Problemy UX

| Problem | Opis | Rozwiazanie |
|---------|------|-------------|
| **U1: Zbyt wiele klikniec** | Aby zobaczyc produkty uzywajace wartosci: karta -> modal wartosci -> modal produktow | 3 poziomy zagniezdzen |
| **U2: Brak preview kolorow** | W siatce kart nie widac kolorow - dopiero w modalu | Brak szybkiego przegladu |
| **U3: Akcje rozproszone** | Bulk actions tylko w modalu wartosci, brak na poziomie grup | Nieefektywna praca masowa |
| **U4: Brak wizualnego feedbacku** | Stan synchronizacji PS to tylko badge'e - brak timeline/logow | Niepewnosc co do stanu sync |

### 2.3 Problemy Przestrzeni

```
+---------------------------------------------------+
| OBECNY LAYOUT                                     |
+---------------------------------------------------+
| [Karta 1]    [Karta 2]    [Karta 3]              | <- Karty zajmuja 100% szerokosci
| [Karta 4]    [Karta 5]    [Karta 6]              |
+---------------------------------------------------+
      |
      v (klik "Wartosci")
+---------------------------------------------------+
| MODAL (max-w-5xl, ~90vh)                          |
| +-----------------------------------------------+ |
| | Tabela wartosci + akcje                       | |
| | (scroll wewnetrzny)                           | |
| +-----------------------------------------------+ |
+---------------------------------------------------+

PROBLEM: Modal ogranicza przestrzen robocza
```

---

## 3. PROPONOWANY NOWY LAYOUT

### 3.1 Koncepcja: Master-Detail Split Panel

**Zalozenie**: Zamiast modali - dedykowana strona z trwalym layoutem split-panel

```
+--------------------------------------------------------------------------------+
|                    SYSTEM ATRYBUTOW (WARIANTOW)                                |
+--------------------------------------------------------------------------------+
|                                                                                |
| +--- MASTER PANEL (280px fixed) ---+  +--- DETAIL PANEL (flex-1) -----------+ |
| |                                  |  |                                      | |
| | [Search input]                   |  | +----------------------------------+ | |
| |                                  |  | | HEADER: Kolor (color)            | | |
| | GRUPY ATRYBUTOW                  |  | | Display: Color | 12 wartosci     | | |
| | +------------------------------+ |  | | PS: [B2B] [B2C]                  | | |
| | | [color] Kolor        12 >    | |  | +----------------------------------+ | |
| | |   [B2B][B2C]                 | |  |                                      | |
| | +------------------------------+ |  | +-- TOOLBAR --+ +-- VIEW TOGGLE ---+ | |
| | | [size] Rozmiar       8  >    | |  | | + Dodaj    | | [List] [Grid]    | | |
| | |   [B2B]                      | |  | | Bulk Act.  | |                  | | |
| | +------------------------------+ |  | +------------+ +------------------+ | |
| | | [material] Material  5  >    | |  |                                      | |
| | |   [B2B][B2C]                 | |  | +-- VALUES GRID/LIST ---------------+ | |
| | +------------------------------+ |  | |                                    | | |
| |                                  |  | | [Czerwony]  [Niebieski]  [Zielony] | | |
| | + Dodaj grupe                    |  | |  #FF0000     #0000FF     #00FF00   | | |
| |                                  |  | |  24 prod.    18 prod.    12 prod.  | | |
| +----------------------------------+  | |                                    | | |
|                                       | | [Czarny]    [Bialy]     [Szary]    | | |
|                                       | |  #000000     #FFFFFF     #888888   | | |
|                                       | |  45 prod.    32 prod.    8 prod.   | | |
|                                       | |                                    | | |
|                                       | +------------------------------------+ | |
|                                       |                                      | |
|                                       | +-- PRODUCTS USING SELECTED --------+ | |
|                                       | | (pokazuje sie po kliknieciu w      | | |
|                                       | |  wartosc - inline panel, nie modal)| | |
|                                       | +------------------------------------+ | |
|                                       +--------------------------------------+ |
+--------------------------------------------------------------------------------+
```

### 3.2 Wariant Alternatywny: Tab-based Navigation

```
+--------------------------------------------------------------------------------+
|                    SYSTEM ATRYBUTOW (WARIANTOW)                                |
+--------------------------------------------------------------------------------+
| +--- TABS -----------------------------------------------------------+        |
| | [Kolor]  [Rozmiar]  [Material]  [+ Dodaj]                          |        |
| +--------------------------------------------------------------------+        |
|                                                                                |
| +--- CONTENT AREA (full width) ------------------------------------------+    |
| |                                                                        |    |
| | +-- HEADER ROW ------------------------------------------------+       |    |
| | | Kolor (color) | Display: Color | 12 wartosci | 156 produktow |       |    |
| | | [Edytuj grupe] [Sync PS] [Usun]                               |       |    |
| | +--------------------------------------------------------------+       |    |
| |                                                                        |    |
| | +-- VALUES SECTION -----------------------------------------------+    |    |
| | |                                                                  |    |    |
| | | +-- COLOR SWATCHES GRID ------------------------------------+   |    |    |
| | | |                                                            |   |    |    |
| | | | +--------+  +--------+  +--------+  +--------+  +--------+ |   |    |    |
| | | | |   []   |  |   []   |  |   []   |  |   []   |  |   []   | |   |    |    |
| | | | |Czerwony|  |Niebieski| |Zielony |  |Czarny  |  |Bialy   | |   |    |    |
| | | | | 24 pr. |  | 18 pr. |  | 12 pr. |  | 45 pr. |  | 32 pr. | |   |    |    |
| | | | | [Edit] |  | [Edit] |  | [Edit] |  | [Edit] |  | [Edit] | |   |    |    |
| | | | +--------+  +--------+  +--------+  +--------+  +--------+ |   |    |    |
| | | |                                                            |   |    |    |
| | | +------------------------------------------------------------+   |    |    |
| | |                                                                  |    |    |
| | | +-- PRODUCTS PANEL (collapsible) ---------------------------+   |    |    |
| | | | > Produkty uzywajace "Czerwony" (24)                       |   |    |    |
| | | | +--------------------------------------------------------+ |   |    |    |
| | | | | SKU-001 | Rower Mountain Bike | 3 warianty            | |   |    |    |
| | | | | SKU-002 | Rower City Cruiser  | 2 warianty            | |   |    |    |
| | | | | ... (lazy load)                                        | |   |    |    |
| | | | +--------------------------------------------------------+ |   |    |    |
| | | +------------------------------------------------------------+   |    |    |
| | +------------------------------------------------------------------+    |    |
| +------------------------------------------------------------------------+    |
+--------------------------------------------------------------------------------+
```

### 3.3 REKOMENDACJA: Master-Detail Split Panel

**Uzasadnienie:**
1. **Kontekst zachowany** - lista grup zawsze widoczna po lewej
2. **Wiecej przestrzeni** - detail panel wykorzystuje pelna szerokosc
3. **Szybka nawigacja** - 1 klik zamiast otwierania/zamykania modali
4. **Skalowalne** - dziala dobrze przy 5 i przy 50 grupach
5. **Responsive** - na mobile master panel staje sie drawer'em

---

## 4. PROJEKT KOMPONENTOW

### 4.1 Master Panel - Lista Grup (AttributeType)

```blade
{{-- attribute-type-list-item.blade.php --}}
<div class="attribute-type-item {{ $isSelected ? 'attribute-type-item--selected' : '' }}"
     wire:click="selectType({{ $type->id }})">

    {{-- Left: Display type indicator --}}
    <div class="attribute-type-item__indicator">
        @if($type->display_type === 'color')
            <div class="indicator-color-grid">
                @foreach($type->values->take(4) as $val)
                    <span class="color-dot" style="--color: {{ $val->color_hex }}"></span>
                @endforeach
            </div>
        @else
            <span class="indicator-icon">
                @switch($type->display_type)
                    @case('dropdown') <span>▼</span> @break
                    @case('radio') <span>◉</span> @break
                    @case('button') <span>▢</span> @break
                @endswitch
            </span>
        @endif
    </div>

    {{-- Center: Name and code --}}
    <div class="attribute-type-item__content">
        <span class="attribute-type-item__name">{{ $type->name }}</span>
        <span class="attribute-type-item__code">{{ $type->code }}</span>
    </div>

    {{-- Right: Count and arrow --}}
    <div class="attribute-type-item__meta">
        <span class="attribute-type-item__count">{{ $type->values_count }}</span>
        <span class="attribute-type-item__arrow">›</span>
    </div>

    {{-- Bottom: Sync badges --}}
    <div class="attribute-type-item__sync">
        @foreach($syncStatuses as $shopId => $status)
            <span class="sync-dot sync-dot--{{ $status['status'] }}"
                  title="{{ $status['shop_name'] }}: {{ $status['status'] }}"></span>
        @endforeach
    </div>
</div>
```

**CSS dla Master Panel Item:**
```css
/* resources/css/admin/components.css - dodaj do istniejacego pliku */

/* ========================================
   ATTRIBUTE TYPE LIST ITEM (Master Panel)
   ======================================== */

.attribute-type-item {
    display: grid;
    grid-template-columns: 48px 1fr auto;
    grid-template-rows: auto auto;
    gap: 4px 12px;
    padding: 12px 16px;
    background: var(--bg-card);
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.attribute-type-item:hover {
    background: var(--bg-card-hover);
    border-color: rgba(224, 172, 126, 0.2);
}

.attribute-type-item--selected {
    background: rgba(37, 99, 235, 0.1);
    border-color: var(--ppm-primary);
    box-shadow: 0 0 0 1px var(--ppm-primary);
}

.attribute-type-item__indicator {
    grid-row: 1 / 3;
    display: flex;
    align-items: center;
    justify-content: center;
}

.indicator-color-grid {
    display: grid;
    grid-template-columns: repeat(2, 16px);
    gap: 2px;
}

.color-dot {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    background: var(--color, #888);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.indicator-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    font-size: 14px;
    color: var(--text-secondary);
}

.attribute-type-item__content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.attribute-type-item__name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
}

.attribute-type-item__code {
    font-family: monospace;
    font-size: 11px;
    color: var(--text-muted);
}

.attribute-type-item__meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.attribute-type-item__count {
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
}

.attribute-type-item__arrow {
    color: var(--text-muted);
    font-size: 18px;
    transition: transform 0.2s ease;
}

.attribute-type-item--selected .attribute-type-item__arrow {
    transform: translateX(2px);
}

.attribute-type-item__sync {
    grid-column: 2 / 4;
    display: flex;
    gap: 4px;
    margin-top: 4px;
}

.sync-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.sync-dot--synced { background: #34d399; }
.sync-dot--pending { background: #fbbf24; }
.sync-dot--missing { background: #9ca3af; }
.sync-dot--error { background: #f87171; }
```

### 4.2 Detail Panel - Value Cards (dla typu Color)

```blade
{{-- attribute-value-color-card.blade.php --}}
<div class="value-color-card {{ $isSelected ? 'value-color-card--selected' : '' }}"
     wire:click="selectValue({{ $value->id }})"
     wire:key="value-card-{{ $value->id }}">

    {{-- Color Swatch --}}
    <div class="value-color-card__swatch">
        <div class="color-swatch" style="--bg-color: {{ $value->color_hex ?? '#888' }}">
            @if(!$value->color_hex)
                <span class="no-color">?</span>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="value-color-card__info">
        <span class="value-color-card__label">{{ $value->label }}</span>
        <span class="value-color-card__hex">{{ $value->color_hex ?? 'brak koloru' }}</span>
    </div>

    {{-- Stats --}}
    <div class="value-color-card__stats">
        <span class="stat-badge stat-badge--products">
            <span class="stat-badge__icon">📦</span>
            <span class="stat-badge__value">{{ $stats['products_count'] }}</span>
        </span>
        @if($stats['products_count'] === 0)
            <span class="stat-badge stat-badge--warning">nieuzywana</span>
        @endif
    </div>

    {{-- Actions (on hover/focus) --}}
    <div class="value-color-card__actions">
        <button wire:click.stop="editValue({{ $value->id }})"
                class="action-btn action-btn--edit" title="Edytuj">
            ✏️
        </button>
        <button wire:click.stop="openSyncModal({{ $value->id }})"
                class="action-btn action-btn--sync" title="Sync">
            🔄
        </button>
        @if($stats['products_count'] === 0)
            <button wire:click.stop="deleteValue({{ $value->id }})"
                    wire:confirm="Usunac {{ $value->label }}?"
                    class="action-btn action-btn--delete" title="Usun">
                🗑️
            </button>
        @endif
    </div>

    {{-- Sync Status Indicators --}}
    <div class="value-color-card__sync-status">
        @foreach($value->prestashopMappings as $mapping)
            <span class="sync-indicator sync-indicator--{{ $mapping->status }}"
                  title="{{ $mapping->shop->name }}: {{ $mapping->status }}">
            </span>
        @endforeach
    </div>
</div>
```

**CSS dla Value Color Card:**
```css
/* ========================================
   VALUE COLOR CARD (Detail Panel - Color Type)
   ======================================== */

.value-color-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--bg-card);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 140px;
}

.value-color-card:hover {
    border-color: rgba(224, 172, 126, 0.3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.value-color-card--selected {
    border-color: var(--ppm-primary);
    background: rgba(37, 99, 235, 0.1);
}

.value-color-card__swatch {
    margin-bottom: 12px;
}

.color-swatch {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 8px;
    background: var(--bg-color, #888);
    border: 2px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.color-swatch .no-color {
    font-size: 24px;
    color: rgba(255, 255, 255, 0.3);
}

.value-color-card__info {
    text-align: center;
    margin-bottom: 8px;
}

.value-color-card__label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
    margin-bottom: 2px;
}

.value-color-card__hex {
    font-family: monospace;
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
}

.value-color-card__stats {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-bottom: 8px;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 500;
}

.stat-badge--products {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.stat-badge--warning {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    font-size: 10px;
}

.value-color-card__actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.value-color-card:hover .value-color-card__actions,
.value-color-card:focus-within .value-color-card__actions {
    opacity: 1;
}

.action-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: rgba(0, 0, 0, 0.5);
    color: var(--text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: rgba(0, 0, 0, 0.7);
}

.action-btn--edit:hover { color: #60a5fa; }
.action-btn--sync:hover { color: #34d399; }
.action-btn--delete:hover { color: #f87171; }

.value-color-card__sync-status {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin-top: auto;
}

.sync-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.sync-indicator--synced { background: #34d399; }
.sync-indicator--pending { background: #fbbf24; }
.sync-indicator--error { background: #f87171; }
```

### 4.3 Detail Panel - Value List Row (dla typu non-Color)

```blade
{{-- attribute-value-list-row.blade.php --}}
<tr class="value-list-row {{ $isSelected ? 'value-list-row--selected' : '' }}"
    wire:click="selectValue({{ $value->id }})"
    wire:key="value-row-{{ $value->id }}">

    {{-- Checkbox --}}
    <td class="value-list-row__checkbox">
        <input type="checkbox"
               wire:model="selectedValues"
               value="{{ $value->id }}"
               class="checkbox-enterprise"
               @click.stop>
    </td>

    {{-- Label + Code --}}
    <td class="value-list-row__label">
        <div class="label-group">
            <span class="label-primary">{{ $value->label }}</span>
            <span class="label-code">{{ $value->code }}</span>
        </div>
        @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
            <div class="sku-preview">
                @if($value->auto_prefix_enabled)
                    <span class="sku-prefix">{{ $value->auto_prefix }}-</span>
                @endif
                <span class="sku-base">SKU</span>
                @if($value->auto_suffix_enabled)
                    <span class="sku-suffix">-{{ $value->auto_suffix }}</span>
                @endif
            </div>
        @endif
    </td>

    {{-- Products Count --}}
    <td class="value-list-row__products">
        @if($stats['products_count'] > 0)
            <button wire:click.stop="showProducts({{ $value->id }})"
                    class="products-badge">
                {{ $stats['products_count'] }} produktow
            </button>
        @else
            <span class="products-none">brak</span>
        @endif
    </td>

    {{-- Sync Status --}}
    <td class="value-list-row__sync">
        <div class="sync-badges">
            @foreach($value->prestashopMappings as $mapping)
                <span class="sync-badge-mini sync-badge-mini--{{ $mapping->status }}">
                    {{ $mapping->shop->name[0] }}
                </span>
            @endforeach
            @if($value->prestashopMappings->isEmpty())
                <span class="sync-badge-mini sync-badge-mini--none">-</span>
            @endif
        </div>
    </td>

    {{-- Status --}}
    <td class="value-list-row__status">
        @if($value->is_active)
            <span class="status-dot status-dot--active"></span>
        @else
            <span class="status-dot status-dot--inactive"></span>
        @endif
    </td>

    {{-- Actions --}}
    <td class="value-list-row__actions">
        <div class="row-actions">
            <button wire:click.stop="editValue({{ $value->id }})"
                    class="btn-enterprise-ghost" title="Edytuj">✏️</button>
            <button wire:click.stop="openSyncModal({{ $value->id }})"
                    class="btn-enterprise-ghost" title="Sync">🔄</button>
            @if($stats['products_count'] === 0)
                <button wire:click.stop="deleteValue({{ $value->id }})"
                        wire:confirm="Usunac {{ $value->label }}?"
                        class="btn-enterprise-ghost text-red-400" title="Usun">🗑️</button>
            @endif
        </div>
    </td>
</tr>
```

### 4.4 Products Preview Panel (Inline, nie modal)

```blade
{{-- products-preview-panel.blade.php --}}
<div class="products-preview-panel"
     x-show="selectedValueId"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0">

    <div class="products-preview-panel__header">
        <h4 class="products-preview-panel__title">
            Produkty uzywajace:
            <span class="highlight">{{ $selectedValue?->label }}</span>
        </h4>
        <button @click="selectedValueId = null" class="btn-enterprise-ghost">
            Zamknij
        </button>
    </div>

    <div class="products-preview-panel__content">
        @if($productsUsingValue->count() > 0)
            <div class="products-mini-list">
                @foreach($productsUsingValue->take(5) as $product)
                    <a href="{{ route('products.edit', $product['id']) }}"
                       class="product-mini-card" target="_blank">
                        <span class="product-mini-card__sku">{{ $product['sku'] }}</span>
                        <span class="product-mini-card__name">{{ Str::limit($product['name'], 30) }}</span>
                        <span class="product-mini-card__variants">{{ $product['variant_count'] }} war.</span>
                    </a>
                @endforeach
            </div>
            @if($productsUsingValue->count() > 5)
                <button wire:click="openFullProductsModal" class="btn-enterprise-secondary btn-enterprise-sm mt-3">
                    Zobacz wszystkie ({{ $productsUsingValue->count() }})
                </button>
            @endif
        @else
            <p class="text-gray-400 text-center py-4">Brak produktow</p>
        @endif
    </div>
</div>
```

---

## 5. WIREFRAME ASCII - PELNY LAYOUT

```
+====================================================================================+
|  PPM TRADE                           [Dashboard] [Produkty] [Admin] [@user]        |
+====================================================================================+
|                                                                                    |
|  SIDEBAR |  SYSTEM ATRYBUTOW (WARIANTOW)                                          |
|  --------|                                                                         |
|  > Sklepy|  +----------------------------------------------------------------+    |
|  > Prod. |  | MASTER PANEL           |  DETAIL PANEL                         |    |
|  > Attr. |  | (240px)                |  (flex-1)                              |    |
|  v Param |  |------------------------|----------------------------------------|    |
|    > Atr.|  | [____Search____]       |  +-- HEADER -------------------------+ |    |
|    > Marki |                         |  | KOLOR (color)                      | |    |
|    > Magaz|  | GRUPY ATRYBUTOW       |  | Display: Color | 12 wartosci       | |    |
|  > Import|  |                        |  | Produktow: 156 | PS: [B2B] [B2C]   | |    |
|          |  | +--------------------+ |  | [Edytuj grupe] [Sync All] [Usun]  | |    |
|          |  | | [==] Kolor     12 >| |  +------------------------------------+ |    |
|          |  | |     [B2B][B2C]     | |                                        |    |
|          |  | +--------------------+ |  +-- TOOLBAR -------------------------+ |    |
|          |  | | [VV] Rozmiar    8 >| |  | [+ Dodaj wartosc]  [Bulk: 0]       | |    |
|          |  | |     [B2B]         | |  | Filter: [All v]  View: [Grid][List]| |    |
|          |  | +--------------------+ |  +------------------------------------+ |    |
|          |  | | [>>] Material   5 >| |                                        |    |
|          |  | |     [B2B][B2C]     | |  +-- VALUES GRID ---------------------+ |    |
|          |  | +--------------------+ |  |                                      | |    |
|          |  |                        |  |  +--------+  +--------+  +--------+  | |    |
|          |  | + Dodaj grupe          |  |  |  [==]  |  |  [==]  |  |  [==]  |  | |    |
|          |  |                        |  |  |Czerwony|  |Niebieski| | Zielony|  | |    |
|          |  |                        |  |  |#FF0000 |  | #0000FF|  | #00FF00|  | |    |
|          |  |                        |  |  | 24 pr. |  | 18 pr. |  | 12 pr. |  | |    |
|          |  |                        |  |  +--------+  +--------+  +--------+  | |    |
|          |  |                        |  |                                      | |    |
|          |  |                        |  |  +--------+  +--------+  +--------+  | |    |
|          |  |                        |  |  |  [==]  |  |  [==]  |  |  [==]  |  | |    |
|          |  |                        |  |  | Czarny |  |  Bialy |  |  Szary |  | |    |
|          |  |                        |  |  |#000000 |  | #FFFFFF|  | #888888|  | |    |
|          |  |                        |  |  | 45 pr. |  | 32 pr. |  | 8 pr.  |  | |    |
|          |  |                        |  |  +--------+  +--------+  +--------+  | |    |
|          |  |                        |  |                                      | |    |
|          |  |                        |  +--------------------------------------+ |    |
|          |  |                        |                                        |    |
|          |  |                        |  +-- PRODUCTS PREVIEW (inline) -------+ |    |
|          |  |                        |  | Produkty uzywajace: Czerwony (24)   | |    |
|          |  |                        |  | +--------------------------------+  | |    |
|          |  |                        |  | | SKU-001 | Rower Mountain | 3w   | | |    |
|          |  |                        |  | | SKU-002 | Rower City     | 2w   | | |    |
|          |  |                        |  | | [Zobacz wszystkie (24)]         | | |    |
|          |  |                        |  | +--------------------------------+  | |    |
|          |  |                        |  +--------------------------------------+ |    |
|          |  +----------------------------------------------------------------+    |
+====================================================================================+
```

---

## 6. MAPOWANIE KLAS CSS (PPM Styling Playbook)

### 6.1 Istniejace klasy do wykorzystania

| Klasa | Uzycie w projekcie |
|-------|-------------------|
| `.enterprise-card` | Kontener glowny (master + detail panele) |
| `.btn-enterprise-primary` | Dodaj grupe, Dodaj wartosc |
| `.btn-enterprise-secondary` | Anuluj, Zamknij |
| `.btn-enterprise-danger` | Usun grupe/wartosc |
| `.btn-enterprise-ghost` | Akcje w wierszu tabeli |
| `.btn-enterprise-sm` | Male przyciski w toolbarach |
| `.form-input` | Pola search i edycji |
| `.sync-status-*` | Badge'e statusu synchronizacji |
| `.checkbox-enterprise` | Checkboxy zaznaczania |

### 6.2 Nowe klasy do dodania

| Nowa klasa | Opis |
|------------|------|
| `.attribute-type-item` | Element listy grup (master panel) |
| `.attribute-type-item--selected` | Zaznaczony element |
| `.value-color-card` | Karta wartosci typu color |
| `.value-list-row` | Wiersz tabeli wartosci (non-color) |
| `.products-preview-panel` | Inline panel produktow |
| `.products-mini-card` | Mini karta produktu w preview |
| `.split-panel` | Kontener master-detail |
| `.split-panel__master` | Lewy panel (lista grup) |
| `.split-panel__detail` | Prawy panel (wartosci) |

### 6.3 Tokeny kolorow do uzycia

```css
/* Z istniejacych tokenow */
--mpp-primary: #e0ac7e;          /* CTA, akcenty */
--ppm-primary: #2563eb;          /* Zaznaczenie, aktywne */
--ppm-secondary: #059669;        /* Sukces, synced */
--ppm-accent: #dc2626;           /* Bledy, usuwanie */
--bg-card: rgba(31, 41, 55, 0.95);
--bg-card-hover: rgba(55, 65, 81, 0.95);
--text-primary: #f8fafc;
--text-secondary: #94a3b8;
--text-muted: #64748b;
```

---

## 7. PLAN IMPLEMENTACJI

### Faza 1: Struktura Layout (4h)
1. Utworzenie nowego pliku `attribute-system-split.blade.php`
2. Implementacja split-panel layout (master + detail)
3. Przeniesienie logiki z `AttributeSystemManager.php`

### Faza 2: Master Panel (2h)
1. Komponent `attribute-type-list-item`
2. Search i filtrowanie w master panel
3. Animacje zaznaczenia

### Faza 3: Detail Panel - Values (4h)
1. Widok grid dla kolorow (`value-color-card`)
2. Widok list dla innych typow (`value-list-row`)
3. Toggle miedzy widokami
4. Bulk actions toolbar

### Faza 4: Products Preview (2h)
1. Inline panel zamiast modalu
2. Mini karty produktow
3. Link do pelnej listy (modal jako fallback)

### Faza 5: CSS i Polish (2h)
1. Dodanie nowych klas do `components.css`
2. Responsive breakpoints
3. Animacje i transitions
4. Testowanie na roznych rozdzielczosciach

### Faza 6: Migracja i Cleanup (2h)
1. Redirect ze starej strony
2. Usuniecie nieuzywanych plikow
3. Dokumentacja zmian

**Szacowany czas: 16h**

---

## 8. PODSUMOWANIE REKOMENDACJI

### 8.1 Glowne zmiany

| Obecny stan | Proponowany stan |
|-------------|------------------|
| Modal jako glowny UI | Split-panel na dedykowanej stronie |
| 3-kolumnowy grid kart | Lista z wybranym elementem |
| Zagniezdzone modale | Inline preview panels |
| Brak wizualizacji kolorow w liscie | Color swatches w master panel |
| Statystyki jako liczby | Visual badges z ikonami |

### 8.2 Korzysci

1. **UX**: Mniej klikniec, szybsza nawigacja
2. **Przestrzen**: Wiecej miejsca na wartosci
3. **Kontekst**: Zawsze widoczna lista grup
4. **Czytelnosc**: Jasna hierarchia wizualna
5. **Responsywnosc**: Latwa adaptacja na mobile

### 8.3 Zgodnosc z PPM Standards

- Wszystkie nowe klasy CSS w `components.css`
- Uzycie istniejacych tokenow kolorow
- Zero inline styles
- Uzycie `.btn-enterprise-*`, `.enterprise-card`
- Zachowanie layer system dla z-index

---

## 9. PLIKI DO UTWORZENIA/MODYFIKACJI

### Nowe pliki
```
resources/views/livewire/admin/variants/
    attribute-system-split.blade.php          <- Nowy layout split-panel
    partials/attribute-type-list-item.blade.php
    partials/value-color-card.blade.php
    partials/value-list-row.blade.php
    partials/products-preview-panel.blade.php
```

### Modyfikowane pliki
```
resources/css/admin/components.css            <- +~200 linii nowych klas
app/Http/Livewire/Admin/Variants/
    AttributeSystemManager.php                <- Modyfikacja logiki
```

---

**Agent**: frontend-specialist
**Status**: PROJEKT UKONCZONY - gotowy do implementacji
**Nastepny krok**: Konsultacja z uzytkownikiem, wybor wariantu (split-panel vs tabs)
