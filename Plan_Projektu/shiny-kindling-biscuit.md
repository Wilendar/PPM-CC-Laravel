# Plan: ETAP_08.6 - ERP Integration Enhancement

**Data:** 2026-01-19
**Etap:** ETAP_08.6 - ERP Full Feature Parity
**Priorytet:** WYSOKI
**Status:** ✅ UKOŃCZONY (2026-01-19)

---

## ZAKRES PRAC

Trzy główne zadania:
1. ✅ **Galeria Media + ERP** - integracja Baselinker w GalleryTab jak PrestaShop
2. ✅ **Warianty w Baselinker** - PUSH wariantów (obecnie tylko main product)
3. ✅ **Pending Sync UI dla ERP** - zamrażanie pól, badge "OCZEKUJE NA SYNCHRONIZACJĘ"

---

## ZADANIE 1: Galeria Media + ERP Integration

### Problem
GalleryTab ma pełną integrację z PrestaShop (sync checkboxy, import modal), ale **BRAK** integracji z ERP (Baselinker).

### Analiza Obecnego Stanu
- `GalleryTab.php` - osobny komponent Livewire
- PrestaShop: checkboxy per shop, pending changes, import modal
- ERP: **BRAK** - zdjęcia NIE są wysyłane do Baselinker

### Plan Implementacji

#### 1.1 Rozszerzyć GalleryTab o ERP connections
**Plik:** `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php`

```php
// Nowe properties
public array $erpConnections = [];           // Aktywne ERP connections
public array $pendingErpChanges = [];        // ['mediaId:erpId' => 'sync'|'unsync']
public array $erpSyncStatus = [];            // Status sync per media per ERP

// Nowe metody
public function loadErpConnections(): void
public function toggleErpAssignment(int $mediaId, int $erpConnectionId): void
public function applyPendingErpChanges(): void
public function syncMediaToErp(Media $media, ERPConnection $connection): bool
```

#### 1.2 Rozszerzyć Media model o ERP mapping
**Plik:** `app/Models/Media.php`

```php
// Nowa kolumna JSON (analogia do prestashop_mapping)
public array $erp_mapping;  // JSON:
// {
//   "baselinker_1": {
//     "product_id": 358946840,
//     "image_position": 0,
//     "synced_at": "2026-01-19T..."
//   }
// }

public function setErpMapping(int $connectionId, array $data): void
public function getErpMapping(int $connectionId): ?array
public function clearErpMapping(int $connectionId): void
```

#### 1.3 Migracja dla erp_mapping
**Plik:** `database/migrations/2026_01_19_XXXXXX_add_erp_mapping_to_media.php`

```php
Schema::table('media', function (Blueprint $table) {
    $table->json('erp_mapping')->nullable()->after('prestashop_mapping');
});
```

#### 1.4 Rozszerzyć BaselinkerService o images sync
**Plik:** `app/Services/ERP/BaselinkerService.php`

```php
// Nowe metody
public function syncProductImages(Product $product, ERPConnection $connection): array
public function updateProductImages(string $baselinkerProductId, array $imageUrls): array
```

#### 1.5 UI w gallery-tab.blade.php
- Dodać sekcję "Integracje ERP" obok "Zarządzanie sklepami"
- Checkboxy per ERP connection (analogia do PrestaShop)
- Pending changes buttons

### Pliki do Modyfikacji
| Plik | Akcja |
|------|-------|
| `app/Http/Livewire/Products/Management/Tabs/GalleryTab.php` | Rozszerzyć o ERP |
| `app/Models/Media.php` | Dodać erp_mapping |
| `app/Services/ERP/BaselinkerService.php` | Dodać syncProductImages() |
| `resources/views/livewire/products/management/tabs/gallery-tab.blade.php` | UI dla ERP |
| `database/migrations/` | Nowa migracja |

---

## ZADANIE 2: Warianty w Baselinker (PUSH)

### Problem
Sync produktu "MRF20-111-025" eksportuje tylko produkt główny, **BEZ wariantów**.

### Analiza Obecnego Stanu
```php
// BaselinkerService.php - OBECNE:
'parent_id' => 0,  // ❌ ZAWSZE 0 - warianty nie są linkowane!

// syncProductStock() - OBECNE:
'variant_id' => 0,  // ❌ Stock idzie na main product, nie warianty!
```

**Co działa:**
- ✅ PULL wariantów z Baselinker (`importVariantsFromBaselinker()`)
- ✅ Model ProductVariant z relacjami

**Co NIE działa:**
- ❌ PUSH wariantów DO Baselinker
- ❌ IntegrationMapping dla ProductVariant
- ❌ parent_id w API call
- ❌ Stock/Prices dla wariantów

### Plan Implementacji

#### 2.1 Rozszerzyć IntegrationMapping o warianty
**Plik:** `app/Models/ProductVariant.php`

```php
// Dodać trait HasIntegrationMappings (już polymorphic!)
use HasIntegrationMappings;

// IntegrationMapping::mappable_type = 'App\Models\ProductVariant'
```

#### 2.2 Nowe metody w BaselinkerService
**Plik:** `app/Services/ERP/BaselinkerService.php`

```php
// PUSH wariantu jako child produktu
protected function createVariantInBaselinker(
    ERPConnection $connection,
    Product $mainProduct,
    ProductVariant $variant,
    string $inventoryId,
    string $parentBaselinkerProductId
): array {
    $variantData = $this->buildVariantProductData($variant, $mainProduct);

    return $this->makeRequest($config, 'addInventoryProduct', [
        'inventory_id' => $inventoryId,
        'product_id' => '',  // CREATE
        'parent_id' => $parentBaselinkerProductId,  // ← KEY: Link to main!
        'sku' => $variant->sku,
        'text_fields' => $variantData['text_fields'],
        // ... inne pola
    ]);
}

protected function updateVariantInBaselinker(...): array
protected function buildVariantProductData(ProductVariant $variant, Product $main): array
protected function syncVariantStock(ProductVariant $variant, string $blVariantId): array
protected function syncVariantPrices(ProductVariant $variant, string $blVariantId): array
```

#### 2.3 Rozszerzyć syncSingleProduct() o warianty
```php
protected function syncSingleProduct(...): array
{
    // 1. Sync main product (existing)
    $mainResult = $this->createOrUpdateBaselinkerProduct(...);
    $mainBaselinkerProductId = $mainResult['product_id'];

    // 2. NEW: Sync variants if is_variant_master
    if ($product->is_variant_master && $product->variants->count() > 0) {
        foreach ($product->variants as $variant) {
            $variantMapping = $variant->integrationMappings()
                ->where('integration_type', 'baselinker')
                ->first();

            if ($variantMapping) {
                $this->updateVariantInBaselinker(..., $variantMapping->external_id);
            } else {
                $result = $this->createVariantInBaselinker(..., $mainBaselinkerProductId);
                // Save mapping!
                $variant->integrationMappings()->create([
                    'integration_type' => 'baselinker',
                    'integration_identifier' => $connection->instance_name,
                    'external_id' => $result['product_id'],
                ]);
            }
        }
    }
}
```

#### 2.4 Rozszerzyć syncProductStock() o warianty
```php
protected function syncProductStock(...): array
{
    $stockUpdates = [];

    // Main product stock (if not variant master)
    if (!$product->is_variant_master) {
        $stockUpdates[] = [...];  // existing logic
    }

    // Variant stocks
    foreach ($product->variants as $variant) {
        $variantMapping = $variant->integrationMappings()...;
        if ($variantMapping) {
            foreach ($variant->stock as $stock) {
                $stockUpdates[] = [
                    'product_id' => $variantMapping->external_id,  // Variant BL ID!
                    'variant_id' => 0,
                    'warehouse_id' => $this->mapWarehouse($stock->warehouse_id),
                    'stock' => $stock->quantity,
                ];
            }
        }
    }
}
```

### Pliki do Modyfikacji
| Plik | Akcja |
|------|-------|
| `app/Models/ProductVariant.php` | Dodać HasIntegrationMappings trait |
| `app/Services/ERP/BaselinkerService.php` | Nowe metody wariantów |
| `.claude/skills/baselinker-api-integration/SKILL.md` | Dodać sekcję wariantów |

---

## ZADANIE 3: Pending Sync UI dla ERP TAB (SHOP-TAB PATTERN)

### Problem - AKTUALIZACJA 2026-01-20

**WYMAGANIE:** Po uruchomieniu JOB synchronizacji ERP musi się dziać TO SAMO co w PrestaShop TAB:

1. **Panel "Szybkie akcje"** (sidebar):
   - Zmiana na "Trwa aktualizacja" (niebieski header z ikoną spinnera)
   - Progress bar z gradientem i odliczaniem czasu "(Xs)"
   - Przycisk "Wróć do Listy Produktów"

2. **Connection badge** (sekcja "Integracje ERP"):
   - Status zmienia się z "Zsynchronizowany" na "Oczekuje: [lista pól]"
   - Pokazuje DOKŁADNĄ LISTĘ oczekujących pól (jak PrestaShop: "waga, stawka VAT, główny wariant...")

3. **Pola formularza**:
   - Żółty badge "OCZEKUJE NA SYNCHRONIZACJĘ" przy każdym polu pending
   - Pola NIE są zablokowane, ale są oznaczone wizualnie

4. **Po zakończeniu job**:
   - Panel wraca do normalnego stanu
   - Badge zmienia się na "Zsynchronizowany"
   - Badge przy polach znikają

### Analiza Obecnego Stanu - SZCZEGÓŁOWA

**✅ Co JUŻ DZIAŁA (KOD ISTNIEJE):**

1. `quick-actions.blade.php`:
   - ✅ Alpine `quickActionsTracker` z pełną obsługą ERP
   - ✅ `@entangle('activeErpJobStatus')`, `@entangle('erpJobCreatedAt')` itd.
   - ✅ Progress bar z countdown dla ERP
   - ✅ Success/Error states

2. `erp-management.blade.php`:
   - ✅ Blocking overlay: `.erp-sync-overlay` (linie 134-146)
   - ✅ Connection badges z status display

3. `erp-connection-data.blade.php`:
   - ✅ `wire:change="trackErpFieldChange('sku')"` na polach
   - ✅ `getErpFieldStatusIndicator()` wywoływane dla badge
   - ✅ `getErpFieldClasses()` dla stylowania inputów

4. `ProductFormERPTabs.php`:
   - ✅ Pełna logika job tracking (linie 72-106)
   - ✅ `checkErpJobStatus()` dla wire:poll
   - ✅ `hasActiveErpSyncJob()` dla UI
   - ✅ `getErpFieldStatusIndicator()` z tekstem "Oczekuje synchronizacji"

5. `erp-sync-status-panel.blade.php`:
   - ✅ Alpine `erpSyncStatusTracker` z progress bar
   - ✅ Success/Error states

**❌ BŁĘDY DO NAPRAWIENIA:**

### BUG 1: wire:poll NIE inicjalizuje się (KRYTYCZNY!)
**Plik:** `erp-sync-status-panel.blade.php` (linie 5-7)

**OBECNY KOD (BŁĘDNY):**
```blade
@if($this->hasActiveErpSyncJob())
<div wire:poll.2s="checkErpJobStatus" class="hidden"></div>
@endif
```

**PROBLEM:** `@if` jest NA ZEWNĄTRZ div z wire:poll!
- Gdy strona ładuje się BEZ aktywnego joba → wire:poll element NIE istnieje w DOM
- Gdy użytkownik kliknie "Sync do ERP" → job się uruchamia
- ALE wire:poll nadal NIE istnieje → Livewire NIGDY nie wywoła `checkErpJobStatus()`!

**ZASADA Z HKS:** `wire:poll element MUSI istniec w DOM ZAWSZE - @if WEWNATRZ wrappera`

**FIX:**
```blade
{{-- wire:poll ZAWSZE w DOM, @if WEWNATRZ --}}
<div wire:poll.2s="checkErpJobStatus">
    @if($this->hasActiveErpSyncJob())
        {{-- polling active --}}
    @endif
</div>
```

### BUG 2: Connection badge NIE pokazuje listy pól
**Plik:** `erp-management.blade.php` (linie 89-92)

**OBECNY KOD:**
```blade
<span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium {{ $syncDisplay['class'] }}">
    {{ $syncDisplay['icon'] }} {{ $syncDisplay['text'] }}
</span>
```

**PROBLEM:** `$syncDisplay['text']` to tylko "Oczekuje" - brak listy pól!
**PrestaShop pokazuje:** "Oczekuje: waga, stawka VAT, główny wariant, wyróżniony, kategorie"

**FIX:** Rozszerzyć `getErpSyncStatusDisplay()` w `ProductFormERPTabs.php`:
```php
// Dla STATUS_PENDING dodać:
$pendingText = 'Oczekuje';
if (!empty($erpData->pending_fields)) {
    $fieldLabels = array_map(fn($f) => __("products.fields.{$f}") ?: ucfirst(str_replace('_', ' ', $f)), $erpData->pending_fields);
    $pendingText = 'Oczekuje: ' . implode(', ', $fieldLabels);
}
```

### BUG 3: Klasa CSS `pending-sync-badge` może nie istnieć
**Plik:** `ProductFormERPTabs.php` (linia 1087)

**OBECNY KOD:**
```php
return [
    'show' => true,
    'class' => 'pending-sync-badge',
    'text' => 'Oczekuje synchronizacji',
];
```

**POTRZEBNA WERYFIKACJA:** Czy `.pending-sync-badge` jest zdefiniowana w CSS?
Jeśli nie, pola pending nie będą miały żółtego tła.

### BUG 4: wire:poll tylko w erp-sync-status-panel (warunkowo includowany)
**Plik:** `erp-management.blade.php` (linie 130-132)

```blade
@if($activeErpConnectionId !== null && !empty($erpExternalData))
    @include('livewire.products.management.partials.erp-sync-status-panel')
@endif
```

**PROBLEM:** Gdy użytkownik jest w widoku "Dane PPM" (default) ale uruchomił sync ERP,
`erp-sync-status-panel` NIE jest includowane → wire:poll nie działa!

**FIX:** Przenieść wire:poll POZA warunek, np. do `erp-management.blade.php` bezpośrednio.

### Plan Implementacji

#### 3.1 FIX wire:poll (KRYTYCZNY!)
**Plik:** `resources/views/livewire/products/management/partials/erp-management.blade.php`

Dodać wire:poll BEZPOŚREDNIO w głównym wrapperze (nie w includowanym pliku):
```blade
{{-- ZAWSZE wire:poll gdy są aktywne ERP connections --}}
@if($erpConnections->isNotEmpty())
<div class="mt-3 bg-gray-800 rounded-lg p-3 relative"
     wire:poll.2s="checkErpJobStatus">
    ...
</div>
@endif
```

#### 3.2 Rozszerzyć getErpSyncStatusDisplay() o listę pól
**Plik:** `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php`

Dodać do metody `getErpSyncStatusDisplay()`:
```php
ProductErpData::STATUS_PENDING => [
    'icon' => '⏳',
    'text' => $this->buildPendingFieldsText($erpData),  // Nowa metoda!
    'class' => 'bg-yellow-600 text-white',
],
```

Nowa metoda:
```php
protected function buildPendingFieldsText(ProductErpData $erpData): string
{
    $pendingFields = $erpData->pending_fields ?? [];
    if (empty($pendingFields)) {
        return 'Oczekuje';
    }

    $fieldLabels = array_map(function($field) {
        return __("products.fields.{$field}") ?: ucfirst(str_replace('_', ' ', $field));
    }, $pendingFields);

    return 'Oczekuje: ' . implode(', ', array_slice($fieldLabels, 0, 5)) .
           (count($fieldLabels) > 5 ? '...' : '');
}
```

#### 3.3 Dodać/zweryfikować CSS dla pending badge
**Plik:** `resources/css/admin/components.css`

```css
/* ERP Pending Sync Badge (jak w PrestaShop) */
.pending-sync-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    background: rgba(251, 191, 36, 0.2);
    border: 1px solid rgba(251, 191, 36, 0.3);
    border-radius: 9999px;
    color: #fbbf24;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
```

#### 3.4 Usunąć duplikat wire:poll z erp-sync-status-panel
**Plik:** `resources/views/livewire/products/management/partials/erp-sync-status-panel.blade.php`

Usunąć linie 5-7 (wire:poll jest teraz w parent):
```blade
{{-- USUNIĘTE - wire:poll jest teraz w erp-management.blade.php --}}
```

### Pliki do Modyfikacji
| Plik | Akcja |
|------|-------|
| `erp-management.blade.php` | Dodać wire:poll do głównego wrappera |
| `ProductFormERPTabs.php` | Rozszerzyć getErpSyncStatusDisplay() o listę pól |
| `components.css` | Dodać/zweryfikować .pending-sync-badge |
| `erp-sync-status-panel.blade.php` | Usunąć duplikat wire:poll |

### Weryfikacja - MANDATORY Chrome DevTools

Po implementacji KAŻDEGO fixa:
1. Otwórz produkt w edycji
2. Wybierz ERP connection (np. BASE TEST)
3. Kliknij "Synchronizuj do ERP"
4. **SPRAWDŹ:**
   - [ ] Progress bar pojawia się w "Szybkie akcje"
   - [ ] Connection badge zmienia się na "Oczekuje: [lista pól]"
   - [ ] Pola mają żółty badge "OCZEKUJE NA SYNCHRONIZACJĘ"
   - [ ] Po zakończeniu job → wszystko wraca do "Zsynchronizowany"

---

## KOLEJNOŚĆ IMPLEMENTACJI

### Faza 1: Pending Sync UI (ZADANIE 3) - NAJPIERW
**Powód:** Najszybsze do implementacji, daje natychmiastowy feedback wizualny

1. Dodać wire:change do input fields w ERP form
2. Dodać pending badges przy labelach
3. Dodać blocking overlay
4. Dodać ERP progress w sidebar
5. CSS styling
6. **TEST:** Edytuj pole → zobacz badge → kliknij sync → zobacz overlay + progress

### Faza 2: Warianty Baselinker (ZADANIE 2)
**Powód:** Krytyczne dla pełnej funkcjonalności sync

1. Dodać HasIntegrationMappings do ProductVariant
2. Implementować createVariantInBaselinker()
3. Rozszerzyć syncSingleProduct() o pętlę wariantów
4. Rozszerzyć syncProductStock() o warianty
5. **TEST:** Sync produktu MRF20-111-025 → sprawdź warianty w Baselinker

### Faza 3: Galeria + ERP (ZADANIE 1)
**Powód:** Wymaga najwięcej pracy, ale jest enhancement

1. Migracja dla erp_mapping
2. Rozszerzyć Media model
3. Rozszerzyć GalleryTab o ERP
4. UI w blade
5. **TEST:** Upload zdjęcia → sync do Baselinker → sprawdź w BL

---

## WERYFIKACJA

### Po każdej fazie - Chrome DevTools:
```javascript
mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/products/edit/123" })
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })
```

### Faza 1 Checklist:
- [x] Edycja pola w ERP TAB → pojawia się żółty badge "OCZEKUJE NA SYNCHRONIZACJĘ"
- [x] Kliknięcie "Sync do ERP" → pojawia się overlay + progress bar
- [x] Po zakończeniu job → overlay znika, badge znika, status = synced

### Faza 2 Checklist:
- [x] Sync produktu z wariantami → warianty widoczne w Baselinker
- [x] Każdy wariant ma własny product_id w BL
- [x] Stock/Prices wariantów prawidłowe

### Faza 3 Checklist:
- [x] Checkboxy ERP w galerii produktu
- [x] Sync zdjęcia do Baselinker → zdjęcie widoczne w BL
- [x] erp_mapping zapisany w Media record

---

## ESTYMACJA

| Zadanie | Complexity | Pliki |
|---------|------------|-------|
| Pending Sync UI | MEDIUM | 5 plików |
| Warianty Baselinker | HIGH | 3 pliki + skill |
| Galeria + ERP | HIGH | 5 plików + migracja |

---

## UWAGI

1. **Context7 MANDATORY** przed implementacją Livewire patterns
2. **Chrome DevTools** verification po każdej fazie
3. **Skill update** po implementacji wariantów
4. **NO HARDCODING** - wszystko konfigurowane przez ERPConnection
