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

## ZADANIE 3: Pending Sync UI dla ERP TAB

### Problem
Na screenshocie widać że PrestaShop TAB ma:
- Żółte badge "OCZEKUJE NA SYNCHRONIZACJĘ" przy polach
- Progressbar "Trwa aktualizacja" w sidebarze
- Zamrożone pola podczas sync

ERP TAB **NIE MA** tej funkcjonalności mimo że kod częściowo istnieje!

### Analiza Obecnego Stanu
**Co już istnieje:**
- `ProductFormERPTabs.php` ma properties: `$activeErpJobStatus`, `$activeErpJobType`
- `ProductErpData` ma kolumny: `sync_status`, `pending_fields`
- `erp-sync-status-panel.blade.php` istnieje

**Co NIE DZIAŁA:**
- Pola NIE pokazują badge "OCZEKUJE NA SYNCHRONIZACJĘ"
- `trackErpFieldChange()` NIE jest wywoływany przy edycji
- Overlay blokujący NIE pojawia się
- `getErpFieldStatusIndicator()` NIE jest używany w Blade

### Plan Implementacji

#### 3.1 Podłączyć trackErpFieldChange() do input fields
**Plik:** `resources/views/livewire/products/management/partials/erp-data-form.blade.php`

```blade
{{-- Każdy input musi mieć wire:change --}}
<input type="text"
       wire:model.live="sku"
       wire:change="trackErpFieldChange('sku')"
       class="form-input-enterprise {{ $this->getErpFieldClasses('sku') }}"
/>

{{-- Badge przy labelu --}}
<label>
    SKU produktu
    @php $indicator = $this->getErpFieldStatusIndicator('sku'); @endphp
    @if($indicator['show'])
        <span class="{{ $indicator['class'] }}">{{ $indicator['text'] }}</span>
    @endif
</label>
```

#### 3.2 Dodać blocking overlay w ERP section
**Plik:** `resources/views/livewire/products/management/partials/erp-management.blade.php`

```blade
{{-- Blocking overlay when ERP sync active --}}
@if($this->hasActiveErpSyncJob())
<div class="erp-sync-overlay">
    <div class="erp-sync-overlay-content">
        <svg class="animate-spin h-8 w-8 text-blue-400">...</svg>
        <span class="text-blue-300 font-medium">OCZEKUJE NA SYNCHRONIZACJĘ ERP</span>
        <span class="text-xs text-blue-400">Pola są zablokowane do zakończenia synchronizacji</span>
    </div>
</div>
@endif
```

#### 3.3 Rozszerzyć sidebar "Szybkie akcje"
**Plik:** `resources/views/livewire/products/management/partials/quick-actions-sidebar.blade.php`

```blade
{{-- ERP Sync Status (analogia do PrestaShop) --}}
@if($this->hasActiveErpSyncJob())
<div class="quick-action-card bg-blue-900/30 border-blue-700">
    <div class="flex items-center gap-2">
        <svg class="animate-spin h-5 w-5 text-blue-400">...</svg>
        <span class="text-blue-400 font-medium">Trwa synchronizacja ERP</span>
    </div>
    <div class="mt-2">
        <span x-text="'Synchronizowanie... (' + remainingSeconds + 's)'"></span>
    </div>
    {{-- Progress bar --}}
    <div class="h-1 bg-blue-900 rounded-full mt-2">
        <div class="h-full bg-blue-500 rounded-full transition-all"
             :style="'width: ' + progress + '%'"></div>
    </div>
</div>
@endif
```

#### 3.4 CSS dla ERP pending states
**Plik:** `resources/css/admin/components.css`

```css
/* ERP Sync Overlay */
.erp-sync-overlay {
    position: absolute;
    inset: 0;
    background: rgba(30, 58, 138, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    border-radius: inherit;
}

/* Pending field indicator */
.erp-field-pending {
    border-color: #fbbf24 !important;
    box-shadow: inset 0 0 0 2px rgba(251, 191, 36, 0.2);
}

/* Pending badge */
.erp-pending-badge {
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
    margin-left: 0.5rem;
}
```

#### 3.5 Upewnić się że wire:poll działa
**Plik:** `resources/views/livewire/products/management/partials/erp-sync-status-panel.blade.php`

```blade
{{-- wire:poll ONLY when job is active --}}
@if($this->hasActiveErpSyncJob())
<div wire:poll.2s="checkErpJobStatus" class="hidden"></div>
@endif
```

### Pliki do Modyfikacji
| Plik | Akcja |
|------|-------|
| `resources/views/livewire/products/management/partials/erp-data-form.blade.php` | Dodać wire:change + badges |
| `resources/views/livewire/products/management/partials/erp-management.blade.php` | Dodać overlay |
| `resources/views/livewire/products/management/partials/quick-actions-sidebar.blade.php` | Dodać ERP progress |
| `resources/css/admin/components.css` | CSS dla pending states |
| `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php` | Verify methods work |

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
