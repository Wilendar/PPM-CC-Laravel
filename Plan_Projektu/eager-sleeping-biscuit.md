# PLAN: Warehouse Location Labels System (ETAP_08 FAZA 8)

> **Status:** ✅ UKOŃCZONE
> **Data:** 2026-01-27
> **Zakres:** UI lokalizacji magazynowych + integracja ERP
> **Ukończono:** 2026-01-27

---

## 1. Cel

Rozbudowa systemu lokalizacji magazynowych:
- Parsowanie wielu lokalizacji (separator: przecinek)
- UI z klikalnymi labelami (kopiowanie, edycja, usuwanie)
- Ustawienia ERP: domyślna lokalizacja + kopiowanie na wszystkie magazyny
- Bidirectional sync tw_Pole2 (Subiekt GT) / lokalizacje (Baselinker)

---

## 2. Pliki do modyfikacji

### 2.1 Backend (Livewire + Services)

| Plik | Zmiany |
|------|--------|
| `app/Http/Livewire/Admin/ERP/ERPManager.php` | Dodanie `default_location`, `copy_location_to_all` do config |
| `app/Http/Livewire/Products/Management/ProductForm.php` | Load/save location, dirty tracking, lock/unlock |
| `app/Services/ERP/SubiektGTService.php` | `parseStockLocationsFromErp()` - READ sync |

### 2.2 Frontend (Blade + CSS)

| Plik | Zmiany |
|------|--------|
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | UI: default_location input, copy switch |
| `resources/views/livewire/products/management/tabs/stock-tab.blade.php` | Location labels component |
| `resources/css/products/product-form.css` | `.location-label`, `.location-label__edit`, `.location-label__remove` |

---

## 3. Implementacja krok po kroku

### FAZA 8.1: ERP Settings - Default Location

**Pliki:** ERPManager.php, erp-manager.blade.php

1. **Dodaj pola do `$subiektConfig`:**
   ```php
   'default_location' => '',           // Domyślna lokalizacja
   'copy_location_to_all' => false,    // Kopiuj na wszystkie magazyny
   ```

2. **Dodaj pola do `$baselinkerConfig`:**
   ```php
   'default_location' => '',
   'copy_location_to_all' => false,
   ```

3. **UI w modal (pod "Default Warehouse"):**
   - Input text "Domyślna lokalizacja" (placeholder: "np. A-12-3")
   - Switch "Kopiuj lokalizację na wszystkie magazyny"
   - Helper text: "Lokalizacja będzie ustawiona dla nowych produktów"

4. **Aktualizuj `buildConnectionConfig()` i `loadConnectionForEdit()`**

---

### FAZA 8.2: Stock Tab - Location Labels UI

**Plik:** stock-tab.blade.php (linie 289-298)

1. **Zamień input na Alpine component:**
   ```html
   <td class="px-4 py-3" x-data="locationLabels(@entangle('stock.'.$warehouseId.'.location'))">
       <!-- Labels container -->
       <div class="flex flex-wrap gap-1">
           <template x-for="(loc, index) in locations" :key="index">
               <span class="location-label">
                   <span x-text="loc" @click="copyToClipboard(loc)" class="cursor-pointer"></span>
                   <button @click="editLocation(index)" class="location-label__edit">
                       <svg><!-- pencil --></svg>
                   </button>
                   <button @click="removeLocation(index)" class="location-label__remove">
                       <svg><!-- x --></svg>
                   </button>
               </span>
           </template>
       </div>
       <!-- Input for new location -->
       <input type="text"
              @keydown.enter="addLocation($event.target.value); $event.target.value=''"
              @blur="addLocation($event.target.value); $event.target.value=''"
              placeholder="Dodaj lokalizację..."
              class="form-input-enterprise text-sm mt-1">
   </td>
   ```

2. **Nagłówek kolumny LOKALIZACJA:**
   - Dodaj ikonę kłódki (jak przy Stan/Rez./Min.)
   - Dodaj przycisk "Powiel na wszystkie" (kopiuje z default warehouse)

3. **Alpine component `locationLabels`:**
   ```javascript
   Alpine.data('locationLabels', (initialValue) => ({
       rawValue: initialValue || '',
       get locations() {
           return this.rawValue ? this.rawValue.split(',').map(l => l.trim()).filter(Boolean) : [];
       },
       set locations(arr) {
           this.rawValue = arr.join(', ');
       },
       addLocation(loc) {
           if (loc.trim()) {
               this.locations = [...this.locations, loc.trim()];
           }
       },
       removeLocation(index) {
           const arr = [...this.locations];
           arr.splice(index, 1);
           this.locations = arr;
       },
       editLocation(index) {
           const newVal = prompt('Edytuj lokalizację:', this.locations[index]);
           if (newVal !== null) {
               const arr = [...this.locations];
               arr[index] = newVal.trim();
               this.locations = arr;
           }
       },
       copyToClipboard(text) {
           navigator.clipboard.writeText(text);
           // Toast notification
       }
   }));
   ```

---

### FAZA 8.3: CSS dla Location Labels

**Plik:** resources/css/products/product-form.css

```css
/* Location Labels */
.location-label {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: rgba(224, 172, 126, 0.15);
    border: 1px solid rgba(224, 172, 126, 0.3);
    border-radius: 0.375rem;
    font-size: 0.75rem;
    color: var(--mpp-primary);
}

.location-label:hover {
    background: rgba(224, 172, 126, 0.25);
}

.location-label__edit,
.location-label__remove {
    padding: 0.125rem;
    border-radius: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.location-label__edit:hover,
.location-label__remove:hover {
    opacity: 1;
}

.location-label__remove:hover {
    color: var(--ppm-accent);
}
```

---

### FAZA 8.4: ProductForm - Load/Save Location

**Plik:** ProductForm.php

1. **`loadProductStock()` - dodaj location:**
   ```php
   $this->stock[$warehouseId] = [
       'quantity' => $stockData->quantity ?? 0,
       'reserved' => $stockData->reserved ?? 0,
       'minimum' => $stockData->minimum_stock ?? 0,
       'location' => $stockData->location ?? '',  // DODAJ
   ];
   ```

2. **Dirty tracking dla location:**
   ```php
   $this->stockOriginalValues[$warehouseId] = [
       'quantity' => ...,
       'reserved' => ...,
       'minimum' => ...,
       'location' => $stockData->location ?? '',  // DODAJ
   ];
   ```

3. **Lock/unlock dla location column:**
   - Dodaj `$locationUnlocked` property
   - Metoda `toggleLocationLock()`
   - W blade: readonly gdy locked

4. **"Powiel na wszystkie" metoda:**
   ```php
   public function copyLocationToAllWarehouses()
   {
       $defaultWarehouseId = $this->getDefaultWarehouseId();
       $defaultLocation = $this->stock[$defaultWarehouseId]['location'] ?? '';

       foreach ($this->stock as $warehouseId => $data) {
           if ($warehouseId !== $defaultWarehouseId) {
               $this->stock[$warehouseId]['location'] = $defaultLocation;
           }
       }
   }
   ```

---

### FAZA 8.5: Subiekt GT - READ Sync (tw_Pole2 -> product_stock.location)

**Plik:** SubiektGTService.php

1. **Nowa metoda `parseStockLocationsFromErp()`:**
   ```php
   protected function parseStockLocationsFromErp(string $pole2Csv, array $warehouseMappings): array
   {
       // Input: "MPPTRADE:A-12,PITBIKE:B-05"
       // Output: [ppm_warehouse_id => 'A-12', ...]

       $locations = [];
       $pairs = explode(',', $pole2Csv);

       foreach ($pairs as $pair) {
           $parts = explode(':', trim($pair), 2);
           if (count($parts) === 2) {
               $warehouseName = trim($parts[0]);
               $location = trim($parts[1]);

               // Find PPM warehouse ID by name or ERP mapping
               $ppmWarehouseId = $this->findPpmWarehouseIdByName($warehouseName, $warehouseMappings);
               if ($ppmWarehouseId) {
                   $locations[$ppmWarehouseId] = $location;
               }
           }
       }

       return $locations;
   }
   ```

2. **Aktualizuj `updateProductBasicDataFromErp()`:**
   ```php
   // Po pobraniu Pole2
   $pole2 = $subiektProduct->Pole2 ?? $subiektProduct->pole2 ?? null;
   if ($pole2) {
       $warehouseMappings = $config['warehouse_mappings'] ?? [];
       $locations = $this->parseStockLocationsFromErp($pole2, $warehouseMappings);

       foreach ($locations as $ppmWarehouseId => $location) {
           ProductStock::updateOrCreate(
               ['product_id' => $product->id, 'warehouse_id' => $ppmWarehouseId],
               ['location' => $location]
           );
       }
   }
   ```

---

## 4. Kolejność implementacji

1. **Commit aktualnego kodu** (przed rozpoczęciem)
2. FAZA 8.1: ERP Settings (ERPManager)
3. FAZA 8.3: CSS dla labels
4. FAZA 8.2: Stock Tab UI (Alpine component)
5. FAZA 8.4: ProductForm load/save
6. FAZA 8.5: Subiekt GT READ sync
7. **Test E2E**
8. **Deploy + weryfikacja Chrome**

---

## 5. Weryfikacja

### Test manualne:
1. **ERP Settings:** Otwórz modal, ustaw default_location, włącz switch, zapisz, sprawdź persistence
2. **Stock Tab:** Dodaj lokalizację przez input, sprawdź label, kliknij (kopiowanie), edytuj, usuń
3. **Powiel:** Ustaw lokalizację na default warehouse, kliknij "Powiel na wszystkie", sprawdź inne magazyny
4. **Sync READ:** Ustaw tw_Pole2 w Subiekt GT, kliknij "Pobierz dane", sprawdź czy lokalizacje się rozparsowały

### Chrome DevTools:
```javascript
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/products/XXX/edit" })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

---

## 6. Pliki krytyczne

| Plik | Linie | Co zmienić |
|------|-------|------------|
| `ERPManager.php` | 86-106 | `$baselinkerConfig`, `$subiektConfig` + nowe pola |
| `erp-manager.blade.php` | 686-720 | UI pod "Default Warehouse" |
| `stock-tab.blade.php` | 289-298 | Alpine component zamiast input |
| `ProductForm.php` | 1155-1166 | Load location do stock array |
| `SubiektGTService.php` | 2040-2092 | `parseStockLocationsFromErp()` |
| `product-form.css` | EOF | `.location-label` classes |
