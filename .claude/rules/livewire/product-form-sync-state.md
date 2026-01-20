# Livewire: ProductForm Synchronization State

## Overview
ProductForm obsługuje dwa typy synchronizacji:
1. **PrestaShop Sync** - synchronizacja do sklepów PrestaShop
2. **ERP Sync** - synchronizacja do systemów ERP (BaseLinker, Subiekt GT, Dynamics)

Oba typy MUSZĄ działać identycznie pod względem UI/UX.

## UI State During Active Sync Job

### WYMAGANE elementy podczas aktywnego joba:

| Element | Opis | Implementacja |
|---------|------|---------------|
| **Górny banner** | Lekki informacyjny banner (NIE blocking overlay!) | `x-show="erpIsJobRunning"` |
| **Badge przy źródle** | "Synchronizacja..." żółty badge | `getErpSyncStatusDisplay()` |
| **Badges na polach** | "OCZEKUJE NA SYNCHRONIZACJĘ" na WSZYSTKICH polach | `getFieldStatusIndicator()` |
| **Żółta obramówka inputów** | `.field-pending-sync` class na wszystkich inputach | `getFieldClasses()` |
| **Zamrożone inputy** | `.category-tree-frozen` na kontenerze | `:class` binding w blade |
| **Progress bar** | Zielony pasek z czasem pozostałym | `quickActionsTracker` Alpine component |
| **Zmienione przyciski** | "Wróć do Listy" zamiast "Zapisz zmiany" | Quick Actions panel |

### ZAKAZANE podczas aktywnego joba:
- Heavy blocking overlay (zasłaniający cały formularz)
- Ukrywanie inputów
- Wyłączanie przycisków nawigacji

## Key Methods

### getFieldClasses(string $field): string
Zwraca CSS classes dla inputa. **PRIORYTET:**
```php
// PRIORITY -1: ERP SYNC JOB RUNNING
if ($this->hasActiveErpSyncJob()) {
    return $baseClasses . ' field-pending-sync';
}

// PRIORITY 0: PrestaShop SYNC JOB RUNNING
if ($this->activeShopId !== null && $this->hasActiveSyncJob()) {
    return $baseClasses . ' field-pending-sync';
}

// PRIORITY 1: Pending ERP field changes
// PRIORITY 2: Pending PrestaShop sync
// PRIORITY 3: Field status (inherited/same/different)
```

### getFieldStatusIndicator(string $field): array
Zwraca badge config dla pola. **PRIORYTET:**
```php
// PRIORITY -1: ERP SYNC JOB RUNNING - ALL fields show badge
if ($this->hasActiveErpSyncJob()) {
    return ['show' => true, 'text' => 'Oczekuje na synchronizację ERP', 'class' => 'pending-sync-badge'];
}

// PRIORITY 0: PrestaShop SYNC JOB RUNNING - ALL fields show badge
if ($this->activeShopId !== null && $this->hasActiveSyncJob()) {
    return ['show' => true, 'text' => 'Oczekuje na synchronizację', 'class' => 'pending-sync-badge'];
}
```

### detectActiveErpJobOnMount(): void
Wykrywa aktywny ERP job przy ładowaniu strony (persistence po F5).
```php
$syncingErpData = $this->product->erpData()
    ->where('sync_status', ProductErpData::STATUS_SYNCING)
    ->orderBy('updated_at', 'desc')
    ->first();

if ($syncingErpData) {
    $this->activeErpJobStatus = 'running';
    // ... restore tracking state
}
```

## CSS Classes

### .field-pending-sync
```css
.field-pending-sync {
    border-color: #f59e0b !important;
    border-width: 2px !important;
    background-color: rgba(251, 191, 36, 0.15) !important;
    box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.25),
                inset 0 0 0 1px rgba(251, 191, 36, 0.1) !important;
}
```
**Lokalizacja:** `resources/css/products/product-form.css`

### .category-tree-frozen
Zamraża inputy (pointer-events: none, opacity: 0.7).
**Lokalizacja:** `resources/css/products/category-form.css`

### .pending-sync-badge
Żółty badge z tekstem "OCZEKUJE NA SYNCHRONIZACJĘ".
**Lokalizacja:** `resources/css/products/product-form.css`

## Alpine.js Integration

### Reactive Getters (product-form.blade.php)
```javascript
x-data="{
    get erpIsJobRunning() {
        const status = $wire.activeErpJobStatus;
        return status === 'pending' || status === 'running';
    },
    get isJobRunning() {
        const status = $wire.activeJobStatus;
        return status === 'pending' || status === 'processing';
    },
    // ... polling logic
}"
```

### Frozen Container
```html
<div :class="{
    'category-tree-frozen': erpIsJobRunning ||
        ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing')
}">
```

## Persistence After Page Refresh

**KRYTYCZNE:** Stan synchronizacji MUSI się utrzymać po F5!

1. `mount()` wywołuje `detectActiveJobOnMount()` (PrestaShop)
2. `mount()` wywołuje `detectActiveErpJobOnMount()` (ERP)
3. Te metody sprawdzają DB czy jest aktywny job
4. Jeśli tak - przywracają stan `activeJobStatus`/`activeErpJobStatus`
5. Alpine polling automatycznie się uruchamia

## Polling Mechanism

### ERP Polling (Alpine)
```javascript
async startErpPolling() {
    while (this.erpPollingActive) {
        await new Promise(r => setTimeout(r, 2000));
        const status = $wire.activeErpJobStatus;
        if (status !== 'pending' && status !== 'running') {
            this.stopErpPolling();
            break;
        }
        await $wire.checkErpJobStatus();
    }
}
```

### Livewire Event Trigger
```php
// W syncToErp() po utworzeniu joba:
$this->dispatch('erp-job-started');
```

## Checklist dla nowych typów synchronizacji

- [ ] Dodaj `hasActiveXxxSyncJob()` method
- [ ] Dodaj `detectActiveXxxJobOnMount()` method
- [ ] Zaktualizuj `getFieldClasses()` - dodaj priority check
- [ ] Zaktualizuj `getFieldStatusIndicator()` - dodaj priority check
- [ ] Dodaj Alpine getter `xxxIsJobRunning`
- [ ] Dodaj polling logic w Alpine
- [ ] Dodaj `:class` binding dla frozen state
- [ ] Dodaj banner z `x-show`
- [ ] Test persistence po F5
