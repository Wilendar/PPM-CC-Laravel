# PLAN: Modale Cen i Stanów dla Wariantów Produktu

**Data**: 2026-01-27
**Etap**: ETAP_14 - Variant Modals
**Status**: Do implementacji

---

## CEL

Dodanie interaktywnych modali dla edycji cen i stanów magazynowych wariantów produktu. Kliknięcie na cenę/stan w liście wariantów otwiera modal z pełnym interfejsem edycji (identyczny jak zakładki "Ceny" i "Stany magazynowe" dla produktu głównego).

---

## ZAKRES ZMIAN

### 1. Nowe pliki do utworzenia

| Plik | Opis | Linie |
|------|------|-------|
| `app/Http/Livewire/Products/Management/Traits/VariantModalsTrait.php` | Trait z logiką modali | ~200 |
| `resources/views/livewire/products/management/partials/variant-prices-modal.blade.php` | Modal cen wariantu | ~120 |
| `resources/views/livewire/products/management/partials/variant-stock-modal.blade.php` | Modal stanów wariantu | ~150 |

### 2. Pliki do modyfikacji

| Plik | Zakres zmian |
|------|--------------|
| `resources/views/livewire/products/management/tabs/variants-tab.blade.php` | Linie 565-632: zmiana plain text na klikalne buttony + include modali |
| `resources/css/products/product-form.css` | Dodanie ~50 linii CSS dla modali |
| `resources/js/app.js` | Dodanie Alpine component `variantPricesCalculator` |
| `app/Http/Livewire/Products/Management/ProductForm.php` | Dodanie `use VariantModalsTrait` |

---

## SZCZEGÓŁY IMPLEMENTACJI

### KROK 1: VariantModalsTrait.php

**Właściwości:**
```php
public bool $showVariantPricesModal = false;
public bool $showVariantStockModal = false;
public ?ProductVariant $selectedVariantForPrices = null;
public ?ProductVariant $selectedVariantForStock = null;
public array $variantModalPrices = [];   // [groupId => ['net', 'gross']]
public array $variantModalStock = [];    // [warehouseId => ['quantity', 'reserved', 'minimum', 'location']]
```

**Metody:**
- `openVariantPricesModal(int $variantId)` - ładuje ceny wariantu i otwiera modal
- `closeVariantPricesModal()` - zamyka modal cen
- `saveVariantPrices()` - zapisuje ceny (używa istniejącej `updateVariantPrice()`)
- `openVariantStockModal(int $variantId)` - ładuje stany wariantu i otwiera modal
- `closeVariantStockModal()` - zamyka modal stanów
- `saveVariantStock()` - zapisuje stany (używa istniejącej `updateVariantStock()`)
- `loadVariantPricesForModal(ProductVariant $variant)` - helper do ładowania cen
- `loadVariantStockForModal(ProductVariant $variant)` - helper do ładowania stanów

### KROK 2: variant-prices-modal.blade.php

**Struktura:**
- Overlay + panel (x-teleport to body, z-index: 9999)
- Header: "Ceny wariantu: {SKU}"
- Tabela 8 grup cenowych z inputami netto/brutto
- Alpine `variantPricesCalculator` do kalkulacji VAT
- Przyciski: Anuluj / Zapisz zmiany

**Wzór z prices-tab.blade.php** - reużycie Alpine kalkulacji VAT

### KROK 3: variant-stock-modal.blade.php

**Struktura:**
- Overlay + panel (x-teleport to body, z-index: 9999)
- Header: "Stan magazynowy: {SKU}"
- Tabela magazynów: Magazyn, Stan, Zarezerwowane, Minimum, Lokalizacja
- Reużycie Alpine `locationLabels` z app.js
- Przyciski: Anuluj / Zapisz zmiany

**Wzór z stock-tab.blade.php** - reużycie locationLabels

### KROK 4: Modyfikacja variants-tab.blade.php

**Linie 588-597 (Cena)** - zmiana z `<div>` na klikalny `<button>`:
```blade
<button type="button"
        wire:click="openVariantPricesModal({{ $variant->id }})"
        class="variant-price-btn ..."
        title="Kliknij aby edytować ceny">
    {{ number_format($priceValue, 2, ',', ' ') }} PLN
    <svg class="w-3 h-3 ml-1 inline opacity-50">...</svg>
</button>
```

**Linie 627-630 (Stan)** - zmiana z `<div>` na klikalny `<button>`:
```blade
<button type="button"
        wire:click="openVariantStockModal({{ $variant->id }})"
        class="variant-stock-btn {{ $stockClass }} ..."
        title="Kliknij aby edytować stany">
    {{ $stockValue }} szt.
    <svg class="w-3 h-3 ml-1 inline opacity-50">...</svg>
</button>
```

**Na końcu pliku** - include modali:
```blade
@include('livewire.products.management.partials.variant-prices-modal')
@include('livewire.products.management.partials.variant-stock-modal')
```

### KROK 5: CSS w product-form.css

```css
/* Variant modal buttons */
.variant-price-btn,
.variant-stock-btn {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    border: none;
    background: transparent;
    transition: all 0.15s ease;
}

.variant-price-btn:hover,
.variant-stock-btn:hover {
    background: rgba(55, 65, 81, 0.5);
    border-radius: 0.25rem;
}

/* Modal table styling */
.variant-modal-table th { ... }
.variant-modal-table td { ... }
.variant-modal-price-input { ... }
.variant-modal-stock-input { ... }
```

### KROK 6: Alpine component w app.js

```javascript
Alpine.data('variantPricesCalculator', (taxRate) => ({
    taxRate: taxRate,
    calculateGross(netValue, groupId) {
        const gross = parseFloat(netValue) * (1 + this.taxRate / 100);
        this.$wire.set('variantModalPrices.' + groupId + '.gross', gross.toFixed(2));
    },
    calculateNet(grossValue, groupId) {
        const net = parseFloat(grossValue) / (1 + this.taxRate / 100);
        this.$wire.set('variantModalPrices.' + groupId + '.net', net.toFixed(2));
    }
}));
```

---

## KOLEJNOŚĆ IMPLEMENTACJI

1. [ ] Utworzyć `VariantModalsTrait.php` z properties i metodami
2. [ ] Dodać `use VariantModalsTrait` do ProductForm.php
3. [ ] Utworzyć `variant-prices-modal.blade.php`
4. [ ] Utworzyć `variant-stock-modal.blade.php`
5. [ ] Zmodyfikować `variants-tab.blade.php` - klikalne ceny/stany + include
6. [ ] Dodać CSS do `product-form.css`
7. [ ] Dodać Alpine component do `app.js`
8. [ ] Build: `npm run build`
9. [ ] Deploy do produkcji (pscp + cache clear)
10. [ ] Weryfikacja Chrome DevTools

---

## WERYFIKACJA

### Testy manualne:
1. Otworzyć produkt z wariantami (np. ID 11191)
2. Kliknąć na cenę wariantu → modal cen się otwiera
3. Zmienić cenę netto → brutto kalkuluje się automatycznie
4. Zapisać → modal się zamyka, cena aktualizuje w liście
5. Kliknąć na stan wariantu → modal stanów się otwiera
6. Zmienić stan + lokalizację → zapisać
7. Sprawdzić w bazie czy zmiany zapisane

### Chrome DevTools:
- Brak błędów konsoli
- Modal renderuje się poprawnie (z-index)
- Inputy działają (wire:model binding)

---

## UWAGI

- **PrestaShop warianty (ps_*)**: Modal powinien być read-only lub blokowany dla wariantów synchronizowanych z PrestaShop
- **Dirty tracking**: Opcjonalnie dodać ostrzeżenie przy zamykaniu modala z niezapisanymi zmianami
- **Kolumna Lokalizacja w liście**: Opcjonalne rozszerzenie (agregat ze wszystkich magazynów)
