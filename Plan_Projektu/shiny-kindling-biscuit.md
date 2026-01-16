# Plan: ERP TAB = Shop TAB (Pełna Przebudowa)

**Data:** 2026-01-16
**Etap:** ETAP_08.4 - ERP Tab Full Shop-Tab Pattern
**Priorytet:** KRYTYCZNY
**Status:** 🛠️ DO IMPLEMENTACJI

---

## PROBLEM

ERP TAB **NIE NADPISUJE** pól formularza danymi z ERP. Komentarz w kodzie:
```php
// NOTE: We do NOT override form fields here!
// The form shows PPM data, and badges show comparison with ERP data.
// This is the KEY difference from Shop-Tab which DOES override fields.
```

**WYMAGANIE:** ERP TAB ma działać **IDENTYCZNIE** jak Shop TAB:
1. Dane pobierane z ERP przy wejściu na tab
2. Dane NADPISUJĄ pola formularza
3. Zmiany trackowane jako pending changes
4. Przy "Zapisz zmiany" → JOB do synchronizacji
5. Per-ERP overrides zapisywane do `product_erp_data`

---

## ARCHITEKTURA DOCELOWA

```
[WEJŚCIE NA ERP TAB]
    ↓
selectErpTab(connectionId)
    ↓
shouldPullFromErp() → tryPullFromErp() → Baselinker API
    ↓
loadErpDataToForm(connectionId) → NADPISANIE PÓL FORMULARZA
    ↓
[USER EDITS FIELDS]
    ↓
updated() hook → trackErpFieldChange() → pending_fields[]
    ↓
[ZAPISZ ZMIANY]
    ↓
saveCurrentErpData() → product_erp_data columns
    ↓
SyncProductToERP::dispatch() → JOB
    ↓
[JOB COMPLETES] → sync_status='synced', last_push_at=now()
```

---

## FAZA 1: ProductFormERPTabs.php - Refaktoryzacja

### 1.1 Metoda `loadErpDataToForm()` - NADPISYWANIE PÓL

**Lokalizacja:** `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php`

**Zmiana:** Zamiast "NOTE: We do NOT override" → NADPISUJ pola jak Shop TAB

```php
protected function loadErpDataToForm(int $connectionId): void
{
    $connection = ERPConnection::find($connectionId);
    $erpData = $this->product->getOrCreateErpData($connectionId);

    // 1. Pull fresh data if needed
    if ($this->shouldPullFromErp($erpData)) {
        $this->tryPullFromErp($connectionId);
        $erpData->refresh();
    }

    // 2. Store external data for UI
    $this->erpExternalData = [
        'connection' => $connection,
        'external_id' => $erpData->external_id,
        'sync_status' => $erpData->sync_status,
        'pending_fields' => $erpData->pending_fields ?? [],
        'external_data' => $erpData->external_data ?? [],
        'last_pull_at' => $erpData->last_pull_at,
        'last_push_at' => $erpData->last_push_at,
    ];

    // 3. CRITICAL: OVERRIDE form fields with ERP data
    //    Priority: product_erp_data columns > external_data > PPM defaults
    $this->sku = $erpData->sku ?? $this->getExternalDataValue('sku') ?? $this->erpDefaultData['sku'];
    $this->name = $erpData->name ?? $this->getExternalDataValue('name') ?? $this->erpDefaultData['name'];
    $this->ean = $erpData->ean ?? $this->getExternalDataValue('ean') ?? $this->erpDefaultData['ean'];
    // ... all fields
}
```

### 1.2 Nowa metoda `saveCurrentErpData()`

```php
protected function saveCurrentErpData(): void
{
    if ($this->activeErpConnectionId === null) return;

    $erpData = $this->product->getOrCreateErpData($this->activeErpConnectionId);

    // Save to product_erp_data columns
    $erpData->update([
        'sku' => $this->sku,
        'name' => $this->name,
        'ean' => $this->ean,
        'manufacturer' => $this->manufacturer,
        'short_description' => $this->short_description,
        'long_description' => $this->long_description,
        'weight' => $this->weight,
        'height' => $this->height,
        'width' => $this->width,
        'length' => $this->length,
        'tax_rate' => $this->tax_rate,
    ]);

    // Mark changed fields as pending
    $changedFields = $this->detectChangedErpFields();
    if (!empty($changedFields)) {
        $erpData->markAsPending($changedFields);
    }
}
```

### 1.3 Zmiana `syncToErp()` - Dispatch JOB

```php
public function syncToErp(int $connectionId): void
{
    $connection = ERPConnection::find($connectionId);

    // Save current data
    $this->saveCurrentErpData();

    // Mark as syncing
    $erpData = $this->product->getOrCreateErpData($connectionId);
    $erpData->markSyncing();

    // Dispatch Job (ASYNC)
    SyncProductToERP::dispatch($this->product, $connection);

    session()->flash('message', 'Synchronizacja uruchomiona: ' . $connection->instance_name);
}
```

### 1.4 Helper `shouldPullFromErp()`

```php
protected function shouldPullFromErp(ProductErpData $erpData): bool
{
    // Pull if: never pulled OR > 5 min ago OR has external_id but no data
    if (!$erpData->last_pull_at) return true;
    if ($erpData->last_pull_at->lt(now()->subMinutes(5))) return true;
    if ($erpData->external_id && empty($erpData->external_data)) return true;
    return false;
}
```

---

## FAZA 2: ProductForm.php - Updated Hook

### 2.1 Dodać `updated()` hook dla ERP context

**Lokalizacja:** `app/Http/Livewire/Products/Management/ProductForm.php`

```php
public function updated($propertyName): void
{
    if (!$this->isEditMode || !$this->product) return;

    // Track ERP changes
    if ($this->activeErpConnectionId !== null) {
        $this->trackErpFieldChange($propertyName);
    }
}

protected function trackErpFieldChange(string $propertyName): void
{
    $trackable = ['sku','name','ean','manufacturer','short_description',
                  'long_description','weight','height','width','length','tax_rate'];

    if (!in_array($propertyName, $trackable)) return;

    $erpData = $this->product->getOrCreateErpData($this->activeErpConnectionId);
    $defaultValue = $this->erpDefaultData[$propertyName] ?? null;

    if ($this->$propertyName !== $defaultValue) {
        $pending = $erpData->pending_fields ?? [];
        if (!in_array($propertyName, $pending)) {
            $pending[] = $propertyName;
            $erpData->update(['pending_fields' => $pending]);
        }
    }
}
```

### 2.2 Modyfikacja `saveAndClose()` - ERP Job dispatch

W sekcji save, dodać:
```php
// If in ERP context, dispatch sync job
if ($this->activeErpConnectionId !== null) {
    $this->saveCurrentErpData();

    $connection = ERPConnection::find($this->activeErpConnectionId);
    if ($connection) {
        SyncProductToERP::dispatch($this->product, $connection);
    }
}
```

---

## FAZA 3: UI Updates

### 3.1 erp-sync-status-panel.blade.php

**Lokalizacja:** `resources/views/livewire/products/management/partials/erp-sync-status-panel.blade.php`

Dodać:
- Pending changes counter i lista
- Loading states dla przycisków
- Disable "Wyślij" gdy brak pending changes

---

## PLIKI DO MODYFIKACJI

| Plik | Akcja | Opis |
|------|-------|------|
| `ProductFormERPTabs.php` | MAJOR REFACTOR | loadErpDataToForm, saveCurrentErpData, syncToErp |
| `ProductForm.php` | MODIFY | updated() hook, saveAndClose() |
| `erp-sync-status-panel.blade.php` | ENHANCE | Pending changes UI |

---

## WERYFIKACJA

### Funkcjonalność
- [ ] Wejście na ERP TAB pobiera dane z Baselinker API
- [ ] Dane z ERP nadpisują pola formularza (sku, name, etc.)
- [ ] Zmiana pola → dodanie do pending_fields
- [ ] Badge "Własny"/"Zgodny" przy polach
- [ ] Zapisz → dispatch SyncProductToERP Job
- [ ] Job aktualizuje sync_status i last_push_at

### UI
- [ ] Pending changes counter widoczny
- [ ] Lista zmienionych pól
- [ ] Loading states na przyciskach
- [ ] Timestamps (last_pull_at, last_push_at)

### Chrome DevTools Test
```javascript
// Navigate to product edit
mcp__claude-in-chrome__navigate({ url: "https://ppm.mpptrade.pl/admin/products/11217/edit" })

// Click ERP tab
mcp__claude-in-chrome__javascript_tool({ text: "document.querySelector('[wire:click*=\"selectErpTab\"]').click()" })

// Verify form fields have ERP data
mcp__claude-in-chrome__javascript_tool({ text: "document.querySelector('[wire:model*=\"name\"]').value" })

// Check pending_fields in UI
mcp__claude-in-chrome__read_page({ depth: 5, filter: "interactive" })
```

---

## CZAS ESTIMAT

| Faza | Czas |
|------|------|
| FAZA 1: ProductFormERPTabs.php | 2-3h |
| FAZA 2: ProductForm.php | 1-2h |
| FAZA 3: UI | 1h |
| Testowanie | 1-2h |
| **TOTAL** | **5-8h** |

---

## KRYTYCZNA ZASADA

**Z CLAUDE.md:**
> TAB ERP ma pokazywać wyłącznie dane pobrane z ERP! Jeżeli zostaną dodane/usunięte/edytowane jakieś dane w TAB ERP TO SĄ ONE OZNACZONE JAKO PENDING CHANGES DO CZASU ZAPISANIA ZMIAN, UTWORZENIA JOBA I ZAKOŃCZENIA JOBA SYNC TO ERP.

Ta zasada jest teraz **analogiczna do Shop TAB** - dane z zewnętrznego źródła (ERP/PrestaShop) są pokazywane w formularzu, a zmiany są trackowane jako pending do momentu sync.
