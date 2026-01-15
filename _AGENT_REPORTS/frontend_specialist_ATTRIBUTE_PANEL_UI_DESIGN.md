# RAPORT PRACY AGENTA: frontend-specialist
**Data**: 2025-12-11 (sesja projektowania UI/UX)
**Agent**: frontend-specialist
**Zadanie**: Zaprojektowanie UI/UX dla przebudowy panelu Atrybutow Wariantow

## 1. ANALIZA OBECNEGO STANU

### 1.1 Obecne Pliki
- **Blade**: `resources/views/livewire/admin/variants/attribute-value-manager.blade.php`
- **PHP**: `app/Http/Livewire/Admin/Variants/AttributeValueManager.php`
- **Model**: `app/Models/AttributeValue.php`

### 1.2 Obecne Funkcjonalnosci
- Lista atrybutow wariantow z podziałem na typy (grupy)
- Dodawanie/edycja/usuwanie wartosci atrybutow
- Kolor picker dla atrybutow typu "kolor"
- Obrazek dla atrybutow typu "texture"

### 1.3 Braki do naprawienia
- Brak liczby produktow wariantowych uzywajacych danego atrybutu
- Brak wyszukiwania/filtrowania
- Brak sortowania
- Brak bulk selection
- Brak podgladu produktow uzywajacych atrybutu
- Brak wykrywania duplikatow
- Brak merge duplikatow
- Brak informacji o nieuzywanych atrybutach

---

## 2. WIREFRAME ASCII - NOWY DESIGN

```
+-----------------------------------------------------------------------------------+
|  HEADER (sticky top)                                                              |
+-----------------------------------------------------------------------------------+
| [icon] Atrybuty Wariantow                      [+ Dodaj atrybut] [Bulk Actions v] |
|        248 atrybutow | 12 typow | 23 nieuzywane                                   |
+-----------------------------------------------------------------------------------+
|                                                                                   |
| +-- FILTERS BAR ----------------------------------------------------------+       |
| | [Search: Szukaj atrybutu...]  [Typ: Wszystkie v]  [Status: Wszystkie v] |       |
| | [Sortuj: Nazwa A-Z v]         [ ] Tylko nieuzywane  [ ] Pokaz duplikaty |       |
| +------------------------------------------------------------------------+       |
|                                                                                   |
| +-- BULK ACTIONS BAR (gdy zaznaczono) ------------------------------------+       |
| | Zaznaczono: 5 atrybutow  [Merge] [Usun nieuzywane] [Zmien typ] [Anuluj] |       |
| +------------------------------------------------------------------------+       |
|                                                                                   |
| +-- ATTRIBUTE CARDS GRID (2-3 kolumny na desktop) ------------------------+       |
| |                                                                          |       |
| |  +-- ATTRIBUTE TYPE SECTION: KOLOR -------------------------+            |       |
| |  | [v] Kolor (24 wartosci)                    [+ Dodaj]     |            |       |
| |  +----------------------------------------------------------+            |       |
| |  |                                                          |            |       |
| |  |  +-- ATTRIBUTE VALUE CARD ---------------------------+   |            |       |
| |  |  | [ ] [##] Czerwony                                 |   |            |       |
| |  |  |     Uzywany w: 156 wariantow                      |   |            |       |
| |  |  |     [Aktywny]                     [Edit] [Delete] |   |            |       |
| |  |  +---------------------------------------------------+   |            |       |
| |  |                                                          |            |       |
| |  |  +-- ATTRIBUTE VALUE CARD (NIEUZYWANY) --------------+   |            |       |
| |  |  | [ ] [##] Burgundowy                               |   |            |       |
| |  |  |     Uzywany w: 0 wariantow        [!NIEUZYWANY]   |   |            |       |
| |  |  |     [Nieaktywny]                  [Edit] [Delete] |   |            |       |
| |  |  +---------------------------------------------------+   |            |       |
| |  |                                                          |            |       |
| |  |  +-- ATTRIBUTE VALUE CARD (DUPLIKAT) ----------------+   |            |       |
| |  |  | [ ] [##] Czerwony PL               [!DUPLIKAT]    |   |            |       |
| |  |  |     Uzywany w: 3 wariantow                        |   |            |       |
| |  |  |     Podobne: "Czerwony" (156)     [Merge] [Edit]  |   |            |       |
| |  |  +---------------------------------------------------+   |            |       |
| |  |                                                          |            |       |
| |  +----------------------------------------------------------+            |       |
| |                                                                          |       |
| |  +-- ATTRIBUTE TYPE SECTION: ROZMIAR -----------------------+            |       |
| |  | [v] Rozmiar (18 wartosci)                  [+ Dodaj]     |            |       |
| |  +----------------------------------------------------------+            |       |
| |  |                                                          |            |       |
| |  |  +-- ATTRIBUTE VALUE CARD ---------------------------+   |            |       |
| |  |  | [ ] [S] S (Small)                                 |   |            |       |
| |  |  |     Uzywany w: 89 wariantow                       |   |            |       |
| |  |  |     [Aktywny]                     [Edit] [Delete] |   |            |       |
| |  |  +---------------------------------------------------+   |            |       |
| |  |                                                          |            |       |
| |  +----------------------------------------------------------+            |       |
| |                                                                          |       |
| +--------------------------------------------------------------------------+       |
|                                                                                   |
+-----------------------------------------------------------------------------------+

+-- PRODUCTS MODAL (slide-over z prawej) ------------------------------------+
|                                                               [X Close]    |
|  Produkty uzywajace atrybutu: "Czerwony"                                   |
|  156 wariantow produktowych                                                |
+---------------------------------------------------------------------------+
|  [Search: Szukaj produktu...]                                              |
+---------------------------------------------------------------------------+
|                                                                            |
|  +-- PRODUCT CARD ------------------------------------------------+        |
|  | [IMG] SKU-12345-RED                                            |        |
|  |       Koszulka Nike Air Max - Czerwona                         |        |
|  |       Wariant: XL/Czerwony     [Zobacz produkt ->]             |        |
|  +----------------------------------------------------------------+        |
|                                                                            |
|  +-- PRODUCT CARD ------------------------------------------------+        |
|  | [IMG] SKU-67890-RED                                            |        |
|  |       Spodnie Adidas Training - Czerwone                       |        |
|  |       Wariant: M/Czerwony      [Zobacz produkt ->]             |        |
|  +----------------------------------------------------------------+        |
|                                                                            |
|  [Load more...]                                                            |
+---------------------------------------------------------------------------+

+-- MERGE MODAL ------------------------------------------------------------+
|                                                               [X Close]    |
|  Polacz duplikaty                                                          |
+---------------------------------------------------------------------------+
|                                                                            |
|  Zrodlowy atrybut (zostanie usuniety):                                     |
|  +----------------------------------------------------------------+        |
|  | [##] Czerwony PL (3 warianty)                                  |        |
|  +----------------------------------------------------------------+        |
|                                                                            |
|  Docelowy atrybut (otrzyma produkty):                                      |
|  +----------------------------------------------------------------+        |
|  | [Select: Wybierz atrybut docelowy v]                           |        |
|  | > Czerwony (156 wariantow) - RECOMMENDED                       |        |
|  | > Red (12 wariantow)                                           |        |
|  +----------------------------------------------------------------+        |
|                                                                            |
|  [!] Ostrzezenie: 3 warianty zostana przepiete na nowy atrybut            |
|                                                                            |
|  [Anuluj]                                             [Polacz atrybuty]    |
+---------------------------------------------------------------------------+
```

---

## 3. KOMPONENTY DO UTWORZENIA

### 3.1 Nowe Komponenty Livewire

| Komponent | Plik | Opis |
|-----------|------|------|
| `AttributeValueManager` | Refaktor istniejacego | Glowny komponent z nowymi funkcjami |
| `AttributeValueCard` | Nowy sub-component | Pojedyncza karta atrybutu |
| `AttributeProductsModal` | Nowy modal | Lista produktow uzywajacych atrybutu |
| `AttributeMergeModal` | Nowy modal | Merge duplikatow |
| `AttributeBulkActions` | Nowy partial | Pasek akcji masowych |

### 3.2 Nowe Partial Views

```
resources/views/livewire/admin/variants/
├── attribute-value-manager.blade.php (REFAKTOR)
├── partials/
│   ├── attribute-type-section.blade.php
│   ├── attribute-value-card.blade.php
│   ├── attribute-filters.blade.php
│   ├── attribute-bulk-bar.blade.php
│   └── modals/
│       ├── attribute-products-modal.blade.php
│       ├── attribute-merge-modal.blade.php
│       └── attribute-edit-modal.blade.php
```

---

## 4. CSS CLASSES DO DODANIA

### 4.1 Nowe klasy w `resources/css/admin/components.css`

```css
/* ========================================
   ATTRIBUTE VALUE MANAGER (ETAP Variants UI)
   ======================================== */

/* Type Section Container */
.attribute-type-section {
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95));
    border: 1px solid rgba(224, 172, 126, 0.15);
    border-radius: 1rem;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.attribute-type-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(224, 172, 126, 0.1);
    cursor: pointer;
    transition: background 0.2s ease;
}

.attribute-type-header:hover {
    background: rgba(0, 0, 0, 0.3);
}

.attribute-type-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: #f8fafc;
}

.attribute-type-count {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 400;
}

.attribute-type-content {
    padding: 1rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

/* Attribute Value Card */
.attribute-value-card {
    background: rgba(31, 41, 55, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.75rem;
    padding: 1rem;
    transition: all 0.2s ease;
    position: relative;
}

.attribute-value-card:hover {
    background: rgba(31, 41, 55, 0.8);
    border-color: rgba(224, 172, 126, 0.2);
}

.attribute-value-card--selected {
    border-color: var(--mpp-primary);
    background: rgba(224, 172, 126, 0.1);
}

.attribute-value-card--unused {
    opacity: 0.7;
    border-style: dashed;
}

.attribute-value-card--duplicate {
    border-color: rgba(251, 191, 36, 0.3);
    background: rgba(251, 191, 36, 0.05);
}

.attribute-value-card__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.attribute-value-card__checkbox {
    flex-shrink: 0;
}

.attribute-value-card__preview {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    color: #f8fafc;
}

.attribute-value-card__preview--color {
    border: 2px solid rgba(0, 0, 0, 0.3);
}

.attribute-value-card__preview--texture {
    background-size: cover;
    background-position: center;
}

.attribute-value-card__name {
    font-weight: 600;
    color: #f8fafc;
    font-size: 0.9375rem;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attribute-value-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.attribute-value-card__usage {
    font-size: 0.8125rem;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.2s ease;
}

.attribute-value-card__usage:hover {
    color: var(--mpp-primary);
    text-decoration: underline;
}

.attribute-value-card__usage--zero {
    color: #f87171;
}

.attribute-value-card__badges {
    display: flex;
    gap: 0.5rem;
}

.attribute-value-card__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

/* Badge styles */
.attribute-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.attribute-badge--active {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.attribute-badge--inactive {
    background: rgba(107, 114, 128, 0.2);
    color: #9ca3af;
}

.attribute-badge--unused {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.attribute-badge--duplicate {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
}

/* Filters Bar */
.attribute-filters {
    background: rgba(17, 24, 39, 0.5);
    border: 1px solid rgba(224, 172, 126, 0.1);
    border-radius: 0.75rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.attribute-filters__row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
}

.attribute-filters__search {
    flex: 1;
    min-width: 200px;
    max-width: 320px;
}

.attribute-filters__select {
    min-width: 160px;
}

.attribute-filters__checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #cbd5e1;
    cursor: pointer;
}

/* Bulk Actions Bar */
.attribute-bulk-bar {
    background: linear-gradient(90deg, rgba(224, 172, 126, 0.15), rgba(224, 172, 126, 0.05));
    border: 1px solid rgba(224, 172, 126, 0.3);
    border-radius: 0.75rem;
    padding: 0.75rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.attribute-bulk-bar__info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--mpp-primary);
    font-weight: 500;
}

.attribute-bulk-bar__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Products Modal (Slide-over) */
.attribute-products-modal {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 480px;
    background: linear-gradient(145deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.98));
    backdrop-filter: blur(16px);
    border-left: 1px solid rgba(224, 172, 126, 0.15);
    z-index: 100;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.attribute-products-modal--open {
    transform: translateX(0);
}

.attribute-products-modal__header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(224, 172, 126, 0.1);
}

.attribute-products-modal__content {
    padding: 1rem 1.5rem;
    overflow-y: auto;
    max-height: calc(100vh - 140px);
}

.attribute-products-modal__item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(31, 41, 55, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.75rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.attribute-products-modal__item:hover {
    background: rgba(31, 41, 55, 0.7);
    border-color: rgba(224, 172, 126, 0.2);
}

.attribute-products-modal__thumbnail {
    width: 3rem;
    height: 3rem;
    border-radius: 0.5rem;
    object-fit: cover;
    background: rgba(0, 0, 0, 0.2);
}

.attribute-products-modal__info {
    flex: 1;
    min-width: 0;
}

.attribute-products-modal__sku {
    font-size: 0.75rem;
    color: var(--mpp-primary);
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.attribute-products-modal__name {
    font-size: 0.875rem;
    color: #f8fafc;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attribute-products-modal__variant {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.25rem;
}
```

---

## 5. ALPINE.JS INTERACTIONS

### 5.1 Glowny komponent x-data

```javascript
// Main Alpine data for AttributeValueManager
function attributeValueManager() {
    return {
        // State
        search: '',
        typeFilter: '',
        statusFilter: 'all', // all, active, inactive
        sortBy: 'name', // name, usage, date
        sortDirection: 'asc',
        showUnusedOnly: false,
        showDuplicatesOnly: false,
        selectedValues: [],
        expandedTypes: [],

        // Modals
        productsModalOpen: false,
        productsModalAttributeId: null,
        mergeModalOpen: false,
        mergeSourceId: null,

        // Methods
        toggleType(typeId) {
            const index = this.expandedTypes.indexOf(typeId);
            if (index === -1) {
                this.expandedTypes.push(typeId);
            } else {
                this.expandedTypes.splice(index, 1);
            }
        },

        isTypeExpanded(typeId) {
            return this.expandedTypes.includes(typeId);
        },

        toggleSelection(valueId) {
            const index = this.selectedValues.indexOf(valueId);
            if (index === -1) {
                this.selectedValues.push(valueId);
            } else {
                this.selectedValues.splice(index, 1);
            }
        },

        isSelected(valueId) {
            return this.selectedValues.includes(valueId);
        },

        selectAll() {
            // Zaznacz wszystkie widoczne
            this.selectedValues = [...document.querySelectorAll('[data-value-id]')]
                .map(el => parseInt(el.dataset.valueId));
        },

        deselectAll() {
            this.selectedValues = [];
        },

        openProductsModal(attributeId) {
            this.productsModalAttributeId = attributeId;
            this.productsModalOpen = true;
            this.$wire.loadProductsForAttribute(attributeId);
        },

        closeProductsModal() {
            this.productsModalOpen = false;
            this.productsModalAttributeId = null;
        },

        openMergeModal(sourceId) {
            this.mergeSourceId = sourceId;
            this.mergeModalOpen = true;
        },

        closeMergeModal() {
            this.mergeModalOpen = false;
            this.mergeSourceId = null;
        },

        // Bulk actions
        bulkDelete() {
            if (this.selectedValues.length === 0) return;
            if (!confirm(`Czy na pewno chcesz usunac ${this.selectedValues.length} atrybutow?`)) return;
            this.$wire.bulkDeleteAttributes(this.selectedValues);
            this.selectedValues = [];
        },

        bulkChangeType(newTypeId) {
            if (this.selectedValues.length === 0) return;
            this.$wire.bulkChangeAttributeType(this.selectedValues, newTypeId);
            this.selectedValues = [];
        }
    }
}

// Type section collapse/expand
function attributeTypeSection(typeId) {
    return {
        expanded: true,

        toggle() {
            this.expanded = !this.expanded;
        }
    }
}
```

### 5.2 Animacje i przejscia

```javascript
// Smooth scroll do sekcji typu
function scrollToType(typeId) {
    const element = document.getElementById(`type-section-${typeId}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Highlight nowo dodanego atrybutu
function highlightNewAttribute(valueId) {
    const element = document.querySelector(`[data-value-id="${valueId}"]`);
    if (element) {
        element.classList.add('attribute-value-card--highlight');
        setTimeout(() => {
            element.classList.remove('attribute-value-card--highlight');
        }, 2000);
    }
}
```

---

## 6. RESPONSIVE BREAKPOINTS

### 6.1 Mobile (< 640px)
- Karty atrybutow: 1 kolumna
- Filtry: pionowo
- Bulk bar: pionowo
- Products modal: pelna szerokosc

### 6.2 Tablet (640px - 1024px)
- Karty atrybutow: 2 kolumny
- Filtry: 2-3 w rzedzie
- Bulk bar: poziomo
- Products modal: 400px szerokosc

### 6.3 Desktop (> 1024px)
- Karty atrybutow: 3 kolumny
- Filtry: wszystkie w rzedzie
- Bulk bar: poziomo
- Products modal: 480px szerokosc

### 6.4 CSS Media Queries

```css
/* Responsive grid for attribute cards */
.attribute-type-content {
    display: grid;
    gap: 1rem;
}

/* Mobile first - 1 column */
.attribute-type-content {
    grid-template-columns: 1fr;
}

/* Tablet - 2 columns */
@media (min-width: 640px) {
    .attribute-type-content {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop - 3 columns */
@media (min-width: 1024px) {
    .attribute-type-content {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Large desktop - 4 columns */
@media (min-width: 1280px) {
    .attribute-type-content {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

/* Filters responsive */
.attribute-filters__row {
    flex-direction: column;
}

@media (min-width: 640px) {
    .attribute-filters__row {
        flex-direction: row;
        flex-wrap: wrap;
    }
}

/* Products modal responsive */
.attribute-products-modal {
    width: 100%;
    max-width: 100%;
}

@media (min-width: 640px) {
    .attribute-products-modal {
        max-width: 400px;
    }
}

@media (min-width: 1024px) {
    .attribute-products-modal {
        max-width: 480px;
    }
}
```

---

## 7. IKONY I KOLORY PER TYP ATRYBUTU

### 7.1 Mapowanie ikon Font Awesome

| Typ Atrybutu | Ikona | Kolor tla (rgba) |
|--------------|-------|------------------|
| Kolor | `fa-palette` | `rgba(239, 68, 68, 0.2)` (red) |
| Rozmiar | `fa-ruler` | `rgba(59, 130, 246, 0.2)` (blue) |
| Material | `fa-layer-group` | `rgba(139, 92, 246, 0.2)` (purple) |
| Tekstura | `fa-brush` | `rgba(16, 185, 129, 0.2)` (green) |
| Waga | `fa-weight-hanging` | `rgba(251, 191, 36, 0.2)` (yellow) |
| Dlugosc | `fa-ruler-horizontal` | `rgba(236, 72, 153, 0.2)` (pink) |
| Pojemnosc | `fa-flask` | `rgba(20, 184, 166, 0.2)` (teal) |
| Inny | `fa-tag` | `rgba(107, 114, 128, 0.2)` (gray) |

### 7.2 Preview koloru w karcie

```html
<!-- Dla typu KOLOR - pokazuj kolor -->
<div class="attribute-value-card__preview attribute-value-card__preview--color"
     style="background-color: {{ $value->color_hex ?? '#666' }}">
</div>

<!-- Dla typu TEKSTURA - pokazuj obrazek -->
<div class="attribute-value-card__preview attribute-value-card__preview--texture"
     style="background-image: url('{{ $value->image_path }}')">
</div>

<!-- Dla innych typow - pokazuj pierwsza litere -->
<div class="attribute-value-card__preview">
    {{ strtoupper(substr($value->value, 0, 2)) }}
</div>
```

---

## 8. DODATKOWE FUNKCJE PHP (AttributeValueManager.php)

### 8.1 Nowe metody do dodania

```php
// Liczenie uzycia atrybutu w wariantach
public function getAttributeUsageCount(int $attributeValueId): int
{
    return VariantAttribute::where('value_id', $attributeValueId)->count();
}

// Ladowanie produktow dla atrybutu (modal)
public function loadProductsForAttribute(int $attributeValueId): void
{
    $this->productsForAttribute = ProductVariant::whereHas('attributes', function ($q) use ($attributeValueId) {
        $q->where('value_id', $attributeValueId);
    })
    ->with(['product:id,sku,name', 'images'])
    ->take(50)
    ->get();
}

// Wykrywanie duplikatow (podobne nazwy)
public function findPotentialDuplicates(int $attributeValueId): Collection
{
    $value = AttributeValue::find($attributeValueId);
    if (!$value) return collect();

    $normalizedName = Str::lower(Str::ascii($value->value));

    return AttributeValue::where('attribute_type_id', $value->attribute_type_id)
        ->where('id', '!=', $attributeValueId)
        ->get()
        ->filter(function ($other) use ($normalizedName) {
            $otherNormalized = Str::lower(Str::ascii($other->value));
            return similar_text($normalizedName, $otherNormalized) / max(strlen($normalizedName), strlen($otherNormalized)) > 0.8;
        });
}

// Merge duplikatow
public function mergeAttributes(int $sourceId, int $targetId): void
{
    DB::transaction(function () use ($sourceId, $targetId) {
        // Przepisz wszystkie VariantAttribute z source na target
        VariantAttribute::where('value_id', $sourceId)
            ->update(['value_id' => $targetId]);

        // Usun source
        AttributeValue::destroy($sourceId);
    });

    $this->dispatch('notify', type: 'success', message: 'Atrybuty zostaly polaczone.');
}

// Bulk delete nieuzywanych
public function bulkDeleteUnused(): void
{
    $unusedIds = AttributeValue::whereDoesntHave('variantAttributes')->pluck('id');
    AttributeValue::destroy($unusedIds);

    $this->dispatch('notify', type: 'success', message: "Usunieto {$unusedIds->count()} nieuzywanych atrybutow.");
}

// Bulk zmiana typu
public function bulkChangeAttributeType(array $valueIds, int $newTypeId): void
{
    AttributeValue::whereIn('id', $valueIds)->update(['attribute_type_id' => $newTypeId]);

    $this->dispatch('notify', type: 'success', message: 'Typ atrybutow zostal zmieniony.');
}
```

---

## 9. PODSUMOWANIE I NASTEPNE KROKI

### 9.1 Wykonane prace (projektowanie)
- Analiza obecnego stanu panelu
- Projektowanie nowego UI/UX
- Wireframe ASCII
- Specyfikacja komponentow
- CSS classes
- Alpine.js interactions
- Responsive breakpoints
- Ikony i kolory per typ

### 9.2 Nastepne kroki (implementacja)

| Krok | Opis | Priorytet |
|------|------|-----------|
| 1 | Dodanie CSS classes do `components.css` | HIGH |
| 2 | Refaktor `AttributeValueManager.php` - dodanie nowych metod | HIGH |
| 3 | Refaktor `attribute-value-manager.blade.php` - nowy layout | HIGH |
| 4 | Utworzenie partials dla modularnosci | MEDIUM |
| 5 | Dodanie modal podgladu produktow | MEDIUM |
| 6 | Dodanie modal merge duplikatow | MEDIUM |
| 7 | Testowanie responsive | HIGH |
| 8 | Chrome DevTools verification | HIGH |

### 9.3 Szacowany czas implementacji
- CSS: ~2h
- PHP backend: ~3h
- Blade templates: ~4h
- Testowanie: ~2h
- **TOTAL: ~11h**

---

## 10. PLIKI

| Plik | Status | Opis |
|------|--------|------|
| `_AGENT_REPORTS/frontend_specialist_ATTRIBUTE_PANEL_UI_DESIGN.md` | CREATED | Ten raport |
| `resources/css/admin/components.css` | TO MODIFY | Dodac nowe klasy CSS |
| `app/Http/Livewire/Admin/Variants/AttributeValueManager.php` | TO MODIFY | Dodac nowe metody |
| `resources/views/livewire/admin/variants/attribute-value-manager.blade.php` | TO REFACTOR | Nowy layout |

---

**Agent**: frontend-specialist
**Status**: DESIGN COMPLETE - Ready for implementation
