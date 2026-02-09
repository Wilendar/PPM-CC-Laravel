# PPM - ProductForm Component Documentation

> **Wersja:** 1.2
> **Data:** 2026-02-09
> **Status:** Production Ready
> **Changelog:** Bidirectional UVE Sync, BUG#1-3 fixes (defaultData sync, cross-context save, character counts)

---

## Spis tresci

1. [Overview](#1-overview)
2. [Architektura plikow](#2-architektura-plikow)
3. [Taby (Zakladki)](#3-taby-zakladki)
4. [Properties](#4-properties)
5. [Metody](#5-metody)
6. [Shop Tabs System](#6-shop-tabs-system)
7. [ERP Tabs System](#7-erp-tabs-system)
8. [Joby i Synchronizacja](#8-joby-i-synchronizacja)
9. [Eventy i Listeners](#9-eventy-i-listeners)
10. [Walidacja](#10-walidacja)
11. [Variant System](#11-variant-system)
12. [Category Management](#12-category-management)
13. [Visual Description (UVE)](#13-visual-description-uve)
14. [CSS i Styling](#14-css-i-styling)
15. [Diagramy](#15-diagramy)

---

## 1. Overview

### 1.1 Opis komponentu

ProductForm to **mega-komponent Livewire** odpowiedzialny za tworzenie i edycje produktow w systemie PPM. Jest to centralny punkt zarzadzania danymi produktu z obsluga multi-store.

### 1.2 Statystyki

| Metryka | Wartosc |
|---------|---------|
| Linie kodu (glowny komponent) | ~10,651 |
| Traits | 9 |
| Services | 3 |
| Child Components | 3 |
| Blade Partials | 50+ |
| Public Properties | 100+ |
| Public Methods | 150+ |
| Taby | 10 |

### 1.3 Glowne funkcjonalnosci

- **Tworzenie/Edycja produktow** - pelny CRUD
- **Multi-store management** - dane per sklep PrestaShop
- **ERP integration** - synchronizacja z BaseLinker, Subiekt GT, Dynamics
- **Variant management** - warianty produktu z cenami/stanami
- **Media management** - galeria zdjec
- **Category management** - kategorie hierarchiczne
- **Visual Editor** - blokowy edytor opisow (UVE)
- **Job tracking** - monitorowanie synchronizacji w real-time

---

## 2. Architektura plikow

### 2.1 Glowny komponent

```
app/Http/Livewire/Products/Management/ProductForm.php
```

### 2.2 Traits (modulacja logiki)

| Trait | Plik | Linie | Przeznaczenie |
|-------|------|-------|---------------|
| ProductFormValidation | `Traits/ProductFormValidation.php` | ~500 | Reguly walidacji Livewire |
| ProductFormUpdates | `Traits/ProductFormUpdates.php` | ~500 | Hooki `updated()` |
| ProductFormComputed | `Traits/ProductFormComputed.php` | ~200 | Computed properties |
| ProductFormShopTabs | `Traits/ProductFormShopTabs.php` | ~800 | Shop tab management |
| ProductFormERPTabs | `Traits/ProductFormERPTabs.php` | ~1000 | ERP tab management |
| ProductFormFeatures | `Traits/ProductFormFeatures.php` | ~400 | Atrybuty produktu |
| ProductFormVariants | `Traits/ProductFormVariants.php` | ~100 | Variant orchestrator |
| ProductFormCompatibility | `Traits/ProductFormCompatibility.php` | ~600 | Dopasowania czesci |
| ProductFormVisualDescription | `Traits/ProductFormVisualDescription.php` | ~300 | Visual Editor |

### 2.3 Variant Traits (wyodrebnione)

| Trait | Plik | Przeznaczenie |
|-------|------|---------------|
| VariantShopContextTrait | `Traits/VariantShopContextTrait.php` | Kontekst sklepu dla wariantow |
| VariantModalsTrait | `Traits/VariantModalsTrait.php` | Modalne okna wariantow |
| VariantCrudTrait | `Traits/VariantCrudTrait.php` | CRUD wariantow |
| VariantPriceTrait | `Traits/VariantPriceTrait.php` | Ceny wariantow |
| VariantStockTrait | `Traits/VariantStockTrait.php` | Stany wariantow |
| VariantImageTrait | `Traits/VariantImageTrait.php` | Obrazy wariantow |
| VariantAttributeTrait | `Traits/VariantAttributeTrait.php` | Atrybuty wariantow |
| VariantValidation | `Traits/VariantValidation.php` | Walidacja wariantow |

### 2.4 Services

| Service | Plik | Przeznaczenie |
|---------|------|---------------|
| ProductFormSaver | `Services/ProductFormSaver.php` | Logika zapisu produktu |
| ProductCategoryManager | `Services/ProductCategoryManager.php` | Operacje na kategoriach |
| ProductMultiStoreManager | `Services/ProductMultiStoreManager.php` | Multi-store sync |

### 2.5 Child Components

| Component | Plik | Przeznaczenie |
|-----------|------|---------------|
| GalleryTab | `Tabs/GalleryTab.php` | Zarzadzanie mediami |
| CategoryPicker | `CategoryPicker.php` | Wybor kategorii (drzewo) |
| CategoryConflictModal | `CategoryConflictModal.php` | Rozwiazywanie konfliktow |

### 2.6 Blade Templates

```
resources/views/livewire/products/management/
├── product-form.blade.php           # Glowny template (~1000 linii)
├── partials/
│   ├── form-header.blade.php        # Header z SKU, nazwa
│   ├── form-messages.blade.php      # Notyfikacje success/error
│   ├── tab-navigation.blade.php     # Nawigacja tabów (10 tabs)
│   ├── shop-management.blade.php    # Panel sklepow
│   ├── erp-management.blade.php     # Panel ERP
│   ├── quick-actions.blade.php      # Przyciski Save/Cancel
│   ├── product-info.blade.php       # Info panel (prawy sidebar)
│   ├── category-browser.blade.php   # Przegladarka kategorii
│   ├── category-controls.blade.php  # Przyciski kategorii
│   ├── create-category-modal.blade.php  # Modal tworzenia kategorii
│   ├── primary-image-preview.blade.php  # Podglad glownego zdjecia
│   ├── version-history-modal.blade.php  # Historia zmian
│   ├── variant-prices-modal.blade.php  # Modal edycji cen wariantu (ETAP_14)
│   ├── variant-stock-modal.blade.php   # Modal edycji stanow wariantu (ETAP_14)
│   └── variant-*.blade.php          # 10+ partiali wariantow
├── tabs/
│   ├── basic-tab.blade.php          # Informacje podstawowe
│   ├── description-tab.blade.php    # Opisy i SEO
│   ├── physical-tab.blade.php       # Wlasciwosci fizyczne
│   ├── attributes-tab.blade.php     # Atrybuty/cechy
│   ├── compatibility-tab.blade.php  # Dopasowania
│   ├── prices-tab.blade.php         # Ceny grupowe
│   ├── stock-tab.blade.php          # Stany magazynowe
│   ├── variants-tab.blade.php       # Warianty CRUD
│   ├── gallery-tab.blade.php        # Galeria (GalleryTab component)
│   └── visual-description-tab.blade.php  # Visual Editor
```

---

## 3. Taby (Zakladki)

### 3.1 Lista tabow

ProductForm zawiera **10 glownych tabow**:

| # | Tab ID | Label | Plik Blade | Warunek |
|---|--------|-------|------------|---------|
| 1 | `basic` | Informacje podstawowe | `tabs/basic-tab.blade.php` | Zawsze |
| 2 | `description` | Opisy i SEO | `tabs/description-tab.blade.php` | Zawsze |
| 3 | `physical` | Wlasciwosci fizyczne | `tabs/physical-tab.blade.php` | Zawsze |
| 4 | `attributes` | Atrybuty | `tabs/attributes-tab.blade.php` | Zawsze |
| 5 | `compatibility` | Dopasowania | `tabs/compatibility-tab.blade.php` | Tylko type=czesc-zamienna |
| 6 | `prices` | Ceny | `tabs/prices-tab.blade.php` | Zawsze |
| 7 | `stock` | Stany magazynowe | `tabs/stock-tab.blade.php` | Zawsze |
| 8 | `variants` | Warianty | `tabs/variants-tab.blade.php` | Tylko is_variant_master=true |
| 9 | `gallery` | Galeria | GalleryTab component | Zawsze |
| 10 | `visual-description` | Visual Description | `tabs/visual-description-tab.blade.php` | Zawsze |

### 3.2 Szczegoly tabow

#### Basic Tab
```
Dane:
- SKU (wymagane, unikalne)
- Nazwa produktu
- Slug (URL-friendly)
- Typ produktu (simple, composite, czesc-zamienna)
- Producent
- Kod dostawcy
- EAN (8-13 cyfr)
- Status (aktywny/nieaktywny)
- Czy master wariantow
- Kolejnosc sortowania

Akcje:
- Pobierz dane z PrestaShop (per shop)
- Auto-generuj slug
```

#### Description Tab
```
Dane:
- Krotki opis (max 800 znakow)
- Dlugi opis (max 21844 znakow)
- Meta title (SEO)
- Meta description (SEO)

Features:
- Liczniki znakow (real-time, inicjalizowane przy mount/loadDefaultDataToForm)
- Ostrzezenia przy przekroczeniu limitow
- Bidirectional sync z UVE (Visual Editor) via ProductShopData
```

#### Physical Tab
```
Dane:
- Waga (kg)
- Wysokosc (cm)
- Szerokosc (cm)
- Dlugosc (cm)
- Stawka VAT (domyslna + per-shop overrides)

Features:
- Auto-kalkulacja objetosci
- VAT rule groups z PrestaShop
```

#### Attributes Tab
```
Dane:
- Cechy produktu (key-value)
- Per-shop overrides atrybutow

Akcje:
- Dodaj/Edytuj/Usun atrybut
- Sync do PrestaShop
```

#### Compatibility Tab (warunkowy)
```
Warunek: product_type.slug === 'czesc-zamienna'

Dane:
- Matryca dopasowania (marka, model, rocznik)
- Produkty kompatybilne

Akcje:
- Dodaj/Usun dopasowanie
- Import z pliku
```

#### Prices Tab
```
Dane:
- Ceny per grupa cenowa (6+ grup)
- Cena netto / brutto / marza
- Status aktywnosci ceny

Features:
- Lock/Unlock pricing (ochrona przed zmianami)
- Auto-kalkulacja brutto z netto
- Sync cen do ERP/PrestaShop
```

#### Stock Tab
```
Dane:
- Stany per magazyn (6+ magazynow)
- Ilosc / Zarezerwowane / Minimum

Features:
- Lock/Unlock per kolumna
- Kolumny blokowane przez ERP source
```

#### Variants Tab (warunkowy)
```
Warunek: is_variant_master === true

Dane:
- Lista wariantow
- Per-variant: atrybuty, ceny, stany, obrazy

Akcje:
- Create variant
- Edit variant
- Delete variant
- Duplicate variant
- Sync variant to shop
```

#### Gallery Tab
```
Component: GalleryTab (child Livewire)

Dane:
- Media produktu (images)
- Primary image flag
- Sort order

Akcje:
- Upload images
- Set primary
- Reorder (drag & drop)
- Delete
- Push to PrestaShop
```

#### Visual Description Tab
```
Integracja: UVE (Visual Editor)

Dane:
- Bloki opisowe (HTML blocks)
- CSS per block

Akcje:
- Edycja blokowa
- Preview
- Sync CSS do PrestaShop
```

---

## 4. Properties

### 4.1 Product & Mode

```php
public ?Product $product = null;       // Model produktu (null = tryb tworzenia)
public bool $isEditMode = false;       // true = edycja, false = tworzenie
```

### 4.2 Tab Navigation

```php
public string $activeTab = 'basic';              // Aktywny tab glowny
public string $activeShopTab = 'all';            // Aktywny shop tab
public string $activeErpTab = 'all';             // Aktywny ERP tab
public ?int $activeShopId = null;                // ID aktywnego sklepu (null = dane domyslne)
public ?int $activeErpConnectionId = null;       // ID aktywnego ERP connection
```

### 4.3 Basic Information

```php
public string $sku = '';
public string $name = '';
public string $slug = '';
public ?int $product_type_id = 1;
public string $manufacturer = '';
public string $supplier_code = '';
public string $ean = '';
public bool $is_active = true;
public bool $is_variant_master = false;
public int $sort_order = 0;
public bool $is_featured = false;
```

### 4.4 Description & SEO

```php
public string $short_description = '';
public string $long_description = '';
public string $meta_title = '';
public string $meta_description = '';
public int $shortDescriptionCount = 0;    // Licznik znakow (inicjalizowany w mount via updateCharacterCounts)
public int $longDescriptionCount = 0;     // Licznik znakow (inicjalizowany w mount via updateCharacterCounts)
```

### 4.5 Physical Properties

```php
public ?float $weight = null;
public ?float $height = null;
public ?float $width = null;
public ?float $length = null;
public float $tax_rate = 23.00;
```

### 4.6 Tax Management (ETAP_14)

```php
public array $shopTaxRateOverrides = [];       // [shopId => tax_rate]
public string $selectedTaxRateOption = 'use_default';
public ?float $customTaxRate = null;
public array $availableTaxRuleGroups = [];     // PS tax rule groups
public array $taxRuleGroupsCacheTimestamp = [];
```

### 4.7 Category Management

```php
public array $defaultCategories = ['selected' => [], 'primary' => null];
public array $shopCategories = [];              // [shopId => ['selected' => [], 'primary' => null]]
public array $expandedCategoryIds = [];
public array $pendingNewCategories = [];        // Do utworzenia
public array $pendingDeleteCategories = [];     // Do usuniecia
public bool $showCreateCategoryModal = false;
public string $newCategoryName = '';
public ?int $newCategoryParentId = null;
```

### 4.8 Shop Management

```php
public array $shopAttributes = [];              // [shopId => [attr => value]]
public array $exportedShops = [];               // IDs eksportowanych sklepow
public bool $shopInternet = false;
public array $shopData = [];                    // Cache danych per shop
public array $defaultData = [];                 // Oryginalne dane PPM
public array $availableShops = [];
public bool $showShopSelector = false;
public array $selectedShopsToAdd = [];
```

### 4.9 ERP Management (ETAP_08)

```php
public array $erpExternalData = [];             // Dane z API ERP
public array $erpDefaultData = [];              // PPM defaults do porownania
public bool $syncingToErp = false;
public bool $loadingErpData = false;
```

### 4.10 Prices & Stock

```php
public array $prices = [];                      // [price_group_id => ['net' => x, 'gross' => y, 'margin' => z]]
public array $priceGroups = [];
public bool $pricesUnlocked = false;
public array $pricesOriginalValues = [];

public array $stock = [];                       // [warehouse_id => ['quantity' => x, 'reserved' => y, 'minimum' => z]]
public array $warehouses = [];
public array $stockColumnLocks = [];            // Blokady kolumn
public array $stockOriginalValues = [];
```

### 4.11 Job Tracking (PrestaShop)

```php
public ?int $activeJobId = null;
public ?string $activeJobStatus = null;         // pending|processing|completed|failed
public ?string $activeJobType = null;           // sync|pull
public ?string $jobCreatedAt = null;
public ?int $syncJobShopId = null;
public ?string $jobResult = null;
```

### 4.12 Job Tracking (ERP - ETAP_08.5)

```php
public ?string $activeErpJobStatus = null;      // pending|running|completed|failed
public ?string $activeErpJobType = null;        // sync|pull
public ?int $activeErpJobId = null;
public ?string $activeErpJobCreatedAt = null;
```

### 4.13 Form State

```php
public bool $isSaving = false;
public bool $categoryEditingDisabled = false;
public array $validationErrors = [];
public string $successMessage = '';
public bool $showSlugField = false;
public array $pendingChanges = [];              // [shopId => [field => value]]
public bool $hasUnsavedChanges = false;
public bool $isLoadingData = false;
public array $originalFormData = [];
```

---

## 5. Metody

### 5.1 Initialization & Lifecycle

| Metoda | Opis |
|--------|------|
| `mount(?Product $product)` | Inicjalizacja komponentu |
| `hydrate()` | Rehydratacja po request |
| `render()` | Renderowanie widoku |

### 5.2 Tab Navigation

| Metoda | Opis |
|--------|------|
| `switchTab(string $tab)` | Przelacz aktywny tab |

### 5.3 Category Management (~25 metod)

| Metoda | Opis |
|--------|------|
| `toggleCategory(int $categoryId)` | Zaznacz/odznacz kategorie |
| `setPrimaryCategory(int $categoryId)` | Ustaw kategorie glowna |
| `clearCategorySelection(string $context)` | Wyczysc zaznaczenie |
| `markCategoryForDeletion(int $categoryId)` | Oznacz do usuniecia |
| `processPendingCategories()` | Przetworz batch create/delete |
| `openCreateCategoryModal()` | Otworz modal tworzenia |
| `createNewCategory()` | Utworz nowa kategorie |
| `loadPrestaShopCategories(int $shopId)` | Zaladuj kategorie z PS |

### 5.4 Shop Management (~20 metod)

| Metoda | Opis |
|--------|------|
| `selectShopTab(int $shopId)` | Przelacz na shop tab |
| `selectDefaultTab()` | Wróc do danych domyslnych |
| `syncShop(int $shopId)` | Synchronizuj do sklepu |
| `pullShopData(int $shopId)` | Pobierz dane z PS |
| `addToShops()` | Dodaj produkt do sklepow |
| `removeFromShop(int $shopId)` | Usun z sklepu |
| `toggleShopVisibility(int $shopId)` | Zmien widocznosc |
| `loadProductDataFromPrestaShop(int $shopId)` | Zaladuj dane API |

### 5.5 ERP Management (~15 metod)

| Metoda | Opis |
|--------|------|
| `selectErpTab(int $connectionId)` | Przelacz na ERP tab |
| `selectDefaultErpTab()` | Wróc do danych PPM |
| `syncToErp(int $connectionId)` | Synchronizuj do ERP |
| `pullErpData(int $connectionId)` | Pobierz dane z ERP |
| `loadErpDataToForm(int $connectionId)` | Zaladuj ERP do formularza |
| `checkErpJobStatus()` | Sprawdz status joba ERP |

### 5.6 Prices & Stock Lock (~15 metod)

| Metoda | Opis |
|--------|------|
| `togglePricesLock()` | Zablokuj/odblokuj ceny |
| `toggleStockLock()` | Zablokuj/odblokuj stany |
| `requestStockColumnUnlock(string $column)` | Zadaj odblokowania kolumny |
| `confirmStockColumnUnlock()` | Potwierdz odblokowanie |
| `canUnlockPrices()` | Czy mozna odblokowac ceny |
| `isStockColumnUnlocked(string $column)` | Czy kolumna odblokowana |

### 5.7 Sync & Job Tracking (~20 metod)

| Metoda | Opis |
|--------|------|
| `syncToShops()` | Dispatch jobow sync PS |
| `checkJobStatus()` | Poll statusu joba PS |
| `checkErpJobStatus()` | Poll statusu joba ERP |
| `retrySync(int $shopId)` | Ponow synchronizacje |
| `detectActiveJobOnMount()` | Wykryj aktywny job przy ladowaniu |

### 5.8 Save Operations (~10 metod)

| Metoda | Opis |
|--------|------|
| `save()` | Glowna metoda zapisu |
| `updateOnly()` | Zapisz bez przekierowania |
| `saveAndClose()` | Zapisz i wróc do listy |
| `saveCurrentContextOnly()` | Zapisz aktywny kontekst + pending default changes |
| `saveAllChanges()` | Zapisz wszystkie pending changes |
| `savePendingChangesToProduct()` | Persist pending changes do tabeli products |
| `cancel()` | Anuluj edycje |

### 5.9 Validation (~15 metod)

| Metoda | Opis |
|--------|------|
| `rules()` | Reguly walidacji Livewire |
| `messages()` | Komunikaty bledow |
| `validateBusinessRules()` | Walidacja biznesowa |
| `getFieldStatus(string $field)` | Status pola (sync) |
| `getFieldStatusIndicator(string $field)` | Badge dla pola |
| `getFieldClasses(string $field)` | CSS classes dla inputa |
| `updateCharacterCounts()` | Aktualizuj liczniki znakow (short/long description) |

### 5.10 Variant Management (~20 metod)

| Metoda | Opis |
|--------|------|
| `createVariant()` | Utworz wariant |
| `updateVariant(int $variantId)` | Aktualizuj wariant |
| `deleteVariant(int $variantId)` | Usun wariant |
| `duplicateVariant(int $variantId)` | Sklonuj wariant |
| `updateVariantAttribute()` | Aktualizuj atrybut wariantu |
| `updateVariantPrice()` | Aktualizuj cene wariantu |
| `updateVariantStock()` | Aktualizuj stan wariantu |

---

## 6. Shop Tabs System

### 6.1 Architektura Multi-Store

ProductForm implementuje **3-warstwowy model danych**:

```
┌─────────────────────────────────┐
│  Warstwa 1: PPM Default Data    │  (tabela: products)
│  SKU, name, description, etc.   │  Dane wspolne dla wszystkich sklepow
└─────────────────────────────────┘
              ↓
        [Context Switch]
        - activeShopId = null  → pokaz PPM data
        - activeShopId = int   → pokaz shop override
              ↓
┌─────────────────────────────────┐
│  Warstwa 2: Shop-Specific Data  │  (tabela: product_shop_data)
│  name, description, etc.        │  Nadpisania per sklep PrestaShop
│  sync_status: pending/synced    │
└─────────────────────────────────┘
              ↓
        [Sync Pipeline]
        - dispatch SyncProductToPrestaShop
        - polling statusu joba
        - update UI przy ukonczeniu
              ↓
┌─────────────────────────────────┐
│  Warstwa 3: PrestaShop External │  (API PrestaShop)
│  Faktyczne dane w sklepie       │
└─────────────────────────────────┘
```

### 6.2 Metody Shop Tab (ProductFormShopTabs trait)

```php
// Przelaczanie tabow
selectShopTab(int $shopId)     // Wejdz w kontekst sklepu
selectDefaultTab()              // Wróc do PPM defaults

// Synchronizacja
syncShop(int $shopId)          // Dispatch job sync
pullShopData(int $shopId)      // Pobierz z PrestaShop API

// Warianty
pullVariantsFromPrestaShop(int $shopId)
```

### 6.3 Flow synchronizacji

```
1. User klika "Synchronizuj do sklepu"
   ↓
2. syncShop($shopId) dispatches SyncProductToPrestaShop job
   ↓
3. Job status = 'pending', activeJobId set
   ↓
4. Alpine.js startuje polling (co 5s)
   ↓
5. checkJobStatus() sprawdza queue
   ↓
6. Job completes → status = 'completed'
   ↓
7. UI refresh, success message
```

### 6.4 Field Status Indicators

Kazde pole formularza moze miec status:

| Status | CSS Class | Badge | Opis |
|--------|-----------|-------|------|
| `inherited` | - | - | Pole uzywa wartosci domyslnej PPM |
| `same` | - | - | Wartosc shop === PPM |
| `different` | `field-different` | "Nadpisane" | Wartosc shop != PPM |
| `pending_sync` | `field-pending-sync` | "Oczekuje na sync" | Zmiana niezapisana |
| `own` | `field-different` | "Wlasne" | Shop ma wlasna wartosc (rozna od default) |

---

## 7. ERP Tabs System

### 7.1 Architektura (ETAP_08)

ERP Tabs uzywa **identycznego patternu** jak Shop Tabs:

```
┌─────────────────────────────────┐
│  PPM Default Data               │  (tabela: products)
└─────────────────────────────────┘
              ↓
        [ERP Context Switch]
        - activeErpConnectionId = null → PPM data
        - activeErpConnectionId = int  → ERP data
              ↓
┌─────────────────────────────────┐
│  ERP-Specific Data              │  (tabela: product_erp_data)
│  external_id, sync_status       │
│  pending_fields, last_sync_at   │
└─────────────────────────────────┘
              ↓
        [Sync Pipeline]
        - dispatch SyncProductToERP
        - polling statusu (2s interval)
              ↓
┌─────────────────────────────────┐
│  ERP External System            │  (BaseLinker/Subiekt/Dynamics API)
└─────────────────────────────────┘
```

### 7.2 Supportowane systemy ERP

| ERP | Service | Job |
|-----|---------|-----|
| BaseLinker | `BaselinkerService` | `BaselinkerSyncJob` |
| Subiekt GT | `SubiektGTService` | `SubiektGTSyncJob` |
| Microsoft Dynamics | `DynamicsService` | `DynamicsSyncJob` |

### 7.3 Metody ERP Tab (ProductFormERPTabs trait)

```php
// Przelaczanie tabow
selectErpTab(int $connectionId)    // Wejdz w kontekst ERP
selectDefaultErpTab()               // Wróc do PPM defaults

// Synchronizacja
syncToErp(int $connectionId)       // Dispatch job sync
pullErpData(int $connectionId)     // Pobierz z ERP API

// Ladowanie danych
loadErpDataToForm(int $connectionId)
storeDefaultData()
loadDefaultDataToForm()
```

### 7.4 Job Tracking (ETAP_08.5)

```php
// Properties do trackingu
$activeErpJobStatus    // pending|running|completed|failed
$activeErpJobType      // sync|pull
$activeErpJobId
$erpJobCreatedAt
$erpJobResult
$erpJobMessage

// Metoda pollingu
checkErpJobStatus()    // Wywolywana co 2s przez Alpine
```

---

## 8. Joby i Synchronizacja

### 8.1 PrestaShop Jobs

| Job | Wywolanie z | Payload | Tracking |
|-----|-------------|---------|----------|
| `SyncProductToPrestaShop` | `syncShop()` | Product, Shop, userId | `$activeJobId`, polling 5s |
| `PullSingleProductFromPrestaShop` | `pullShopData()` | Product, Shop, userId | Instant |
| `BulkSyncProducts` | `bulkUpdateShops()` | productIds[], shopId | Per-product |
| `DeleteProductFromPrestaShop` | `deleteFromPrestaShop()` | Product, Shop, userId | No tracking |
| `SyncShopVariantsToPrestaShopJob` | Variant context | Variant data | Variant modal |

### 8.2 ERP Jobs

| Job | Wywolanie z | Payload | Tracking |
|-----|-------------|---------|----------|
| `SyncProductToERP` | `syncToErp()` | Product, ERPConnection | `$activeErpJobStatus`, polling 2s |
| `PullProductFromERP` | `pullErpData()` | Product, ERPConnection | Instant |
| `BaselinkerSyncJob` | Background | Connection-specific | Per-connection |
| `SubiektGTSyncJob` | Background | Connection-specific | Per-connection |

### 8.3 Polling Mechanism

**PrestaShop (5s interval):**
```javascript
// Alpine.js
pollingInterval: null,
pollCount: 0,
maxPolls: 120,

startPolling() {
    this.pollCount = 0;
    this.pollingInterval = setInterval(async () => {
        await $wire.checkJobStatus();
        this.pollCount++;
        if (this.pollCount >= this.maxPolls) {
            this.stopPolling();
        }
    }, 5000);
}
```

**ERP (2s interval):**
```javascript
erpPollingInterval: null,
erpPollCount: 0,
erpMaxPolls: 120,

startErpPolling() {
    this.erpPollCount = 0;
    this.erpPollingInterval = setInterval(async () => {
        await $wire.checkErpJobStatus();
        this.erpPollCount++;
        if (this.erpPollCount >= this.erpMaxPolls) {
            this.stopErpPolling();
        }
    }, 2000);
}
```

---

## 9. Eventy i Listeners

### 9.1 Livewire Listeners

```php
protected $listeners = [
    'shop-categories-reloaded' => 'handleCategoriesReloaded',
    'delayed-reset-unsaved-changes' => 'forceResetUnsavedChanges',
];
```

### 9.2 Alpine Window Events

| Event | Emitowany z | Obsluga |
|-------|-------------|---------|
| `job-started` | `syncToShops()` | `@job-started.window="startPolling()"` |
| `job-completed` | `checkJobStatus()` | `@job-completed.window="stopPolling()"` |
| `job-failed` | `checkJobStatus()` | `@job-failed.window="stopPolling()"` |
| `erp-job-started` | `syncToErp()` | `@erp-job-started.window="startErpPolling()"` |
| `prestashop-loading-start` | `pullShopData()` | Loading overlay |
| `prestashop-loading-end` | `pullShopData()` | Hide overlay |
| `redirect-to-product-list` | Save success | Navigation |

### 9.3 Dispatch Events

```php
// Z Livewire do Alpine
$this->dispatch('job-started');
$this->dispatch('job-completed', ['result' => 'success']);
$this->dispatch('erp-job-started');

// Z serwisu
$this->dispatch('shop-categories-reloaded');
```

---

## 10. Walidacja

### 10.1 Livewire Rules (ProductFormValidation trait)

```php
protected function rules(): array
{
    return [
        // Basic
        'sku' => ['required', 'string', 'max:100', 'unique:products,sku,...', 'regex:/^[A-Z0-9\-_]+$/'],
        'name' => 'required|string|max:500|min:3',
        'slug' => ['nullable', 'string', 'max:500', 'unique:products,slug,...', 'regex:/^[a-z0-9\-]+$/'],
        'product_type_id' => 'required|exists:product_types,id',
        'manufacturer' => 'nullable|string|max:200',
        'ean' => 'nullable|string|max:13|regex:/^[0-9]{8,13}$/',

        // Descriptions
        'short_description' => 'nullable|string|max:1000',
        'long_description' => 'nullable|string|max:10000',
        'meta_title' => 'nullable|string|max:200',
        'meta_description' => 'nullable|string|max:500',

        // Physical
        'weight' => 'nullable|numeric|min:0|max:999999.99',
        'height' => 'nullable|numeric|min:0|max:999999.99',
        'width' => 'nullable|numeric|min:0|max:999999.99',
        'length' => 'nullable|numeric|min:0|max:999999.99',

        // Tax
        'tax_rate' => ['required', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,2}(\.\d{1,2})?$/'],
        'shopTaxRateOverrides.*' => ['nullable', 'numeric', 'min:0', 'max:100'],

        // Categories
        'selectedCategories' => 'array',
        'selectedCategories.*' => 'exists:categories,id',
        'primaryCategoryId' => 'nullable|exists:categories,id',
    ];
}
```

### 10.2 Custom Messages

```php
protected function messages(): array
{
    return [
        'sku.required' => 'SKU jest wymagane.',
        'sku.unique' => 'SKU musi byc unikalne.',
        'sku.regex' => 'SKU moze zawierac tylko wielkie litery, cyfry, myslniki i podkreslenia.',
        'name.required' => 'Nazwa produktu jest wymagana.',
        'name.min' => 'Nazwa musi miec co najmniej 3 znaki.',
        'ean.regex' => 'EAN musi skladac sie z 8-13 cyfr.',
        'tax_rate.required' => 'Stawka VAT jest wymagana.',
    ];
}
```

### 10.3 Business Rules Validation

```php
public function validateBusinessRules(): void
{
    // Primary category required if categories selected
    if (!empty($this->selectedCategories) && !$this->primaryCategoryId) {
        throw ValidationException::withMessages([
            'primaryCategoryId' => 'Nalezy wybrac kategorie glowna.'
        ]);
    }

    // Variant master requires at least one variant
    if ($this->is_variant_master && empty($this->variants)) {
        throw ValidationException::withMessages([
            'variants' => 'Master wariantow wymaga co najmniej jednego wariantu.'
        ]);
    }
}
```

---

## 11. Variant System

### 11.1 Traits wariantow

| Trait | Przeznaczenie |
|-------|---------------|
| `ProductFormVariants` | Orchestrator (deleguje do innych traits) |
| `VariantCrudTrait` | Create, Read, Update, Delete |
| `VariantPriceTrait` | Zarzadzanie cenami wariantow |
| `VariantStockTrait` | Zarzadzanie stanami wariantow |
| `VariantImageTrait` | Obrazy wariantow |
| `VariantAttributeTrait` | Atrybuty wariantow (rozmiar, kolor, etc.) |
| `VariantShopContextTrait` | Kontekst sklepu dla wariantow |
| `VariantModalsTrait` | Modalne okna CRUD + edycja cen/stanow (ETAP_14) |
| `VariantValidation` | Walidacja danych wariantow |

### 11.2 Operacje CRUD

```php
// Create
createVariant()
openCreateVariantModal()

// Read
getAllVariantsForDisplay()
getVariantById(int $variantId)

// Update
updateVariant(int $variantId)
updateVariantAttribute(int $variantId, string $attribute, $value)
updateVariantPrice(int $variantId, int $priceGroupId, $value)
updateVariantStock(int $variantId, int $warehouseId, $value)

// Delete
deleteVariant(int $variantId)
confirmDeleteVariant()

// Duplicate
duplicateVariant(int $variantId)
```

### 11.3 Modalne okna

- **Create Variant Modal** - formularz tworzenia
- **Edit Variant Modal** - formularz edycji
- **Delete Confirm Modal** - potwierdzenie usuniecia
- **Sync Variant Modal** - sync do sklepu
- **Variant Prices Modal** (ETAP_14) - edycja cen wariantu (wszystkie grupy cenowe, kalkulacja VAT netto/brutto)
- **Variant Stock Modal** (ETAP_14) - edycja stanow magazynowych wariantu (per magazyn)

### 11.4 Sync wariantow do PrestaShop (ETAP_14 - 2026-01-29)

#### Architektura sync cen/stanow

```
PPM Variant              SyncShopVariantsToPrestaShopJob           PrestaShop
    |                              |                                    |
    | variant.prices.first()       |                                    |
    |----------------------------->| syncVariantPrice()                 |
    |                              |                                    |
    |                              | 1. Get PPM variant absolute price  |
    |                              | 2. Get PS base product price       |
    |                              | 3. price_impact = variant - base   |
    |                              |                                    |
    |                              | updateCombination({price: delta})  |
    |                              |----------------------------------->|
    |                              |                                    |
    | variant.stock.sum('quantity') |                                   |
    |----------------------------->| syncVariantStock()                 |
    |                              |                                    |
    |                              | 1. Sum stock across warehouses     |
    |                              | 2. getStockForCombination()        |
    |                              |    (filter by id_product_attribute) |
    |                              | 3. GET-MERGE-PUT stock_available   |
    |                              |----------------------------------->|
    |                              |                                    |
```

#### Kluczowe metody (SyncShopVariantsToPrestaShopJob)

| Metoda | Opis |
|--------|------|
| `syncVariantPrice()` | Oblicza `price_impact` (delta) i aktualizuje combination |
| `syncVariantStock()` | Pobiera `stock_available` i aktualizuje ilosc przez GET-MERGE-PUT |
| `handleAddOperation()` | Tworzy combination + wywoluje syncPrice/syncStock |
| `handleOverrideOperation()` | Aktualizuje combination + wywoluje syncPrice/syncStock |

#### Kluczowe metody (PrestaShop8Client)

| Metoda | Opis |
|--------|------|
| `getStockForCombination(productId, combinationId)` | Pobiera stock_available filtrujac po id_product_attribute |
| `updateStock(stockId, quantity, productId, combinationId)` | GET-MERGE-PUT pattern (PS wymaga wszystkich pol w PUT) |
| `updateCombination(combinationId, data)` | Aktualizuje dane combination (price_impact, atrybuty) |

#### Uwagi techniczne

- **price_impact vs absolute price**: PrestaShop przechowuje cene wariantu jako delta od ceny bazowej produktu (`combination.price` = `variant_price - product.price`)
- **stock_availables klucz API**: PS API zwraca `stock_availables` (liczba mnoga z tablica), nawet dla GET pojedynczego rekordu
- **GET-MERGE-PUT**: PS API wymaga WSZYSTKICH pol w PUT body - trzeba najpierw GET z `display=full`, zmienic pole, PUT calosc
- **Fallback**: Bledy sync cen/stanow sa logowane ale NIE przerywaja calego sync wariantow

### 11.5 ShopVariantService - mapowanie danych (FIX 2026-01-29)

`mapCombinationsToVariants()` teraz laduje lokalne warianty PPM z relacjami `prices` i `stock`:

```php
// Ladowanie lokalnych wariantow PPM
$localVariants = $product->variants()
    ->with(['prices', 'stock'])
    ->get()
    ->keyBy('id');

// Matchowanie: po variant_id (bezposredni link) lub po SKU (fallback)
$localVariant = $localVariantId
    ? $localVariants->get($localVariantId)
    : $localVariants->firstWhere('sku', $psSku);

// Dane z PPM (nie z PrestaShop API!)
'price' => $localVariant->prices->first()?->price ?? 0,
'stock' => $localVariant->stock->sum('quantity'),
```

**Efekt:** Zakladka PrestaShop Tab pokazuje identyczne ceny/stany co Default/ERP Tab.

---

## 12. Category Management

### 12.1 ProductCategoryManager Service

```php
class ProductCategoryManager
{
    // CRUD
    public function createCategory(string $name, ?int $parentId, ?int $shopId): Category
    public function updateCategory(int $categoryId, array $data): Category
    public function deleteCategory(int $categoryId): bool

    // Hierarchia
    public function getCategoryTree(?int $shopId = null): array
    public function getCategoryPath(int $categoryId): array
    public function moveCategoryToParent(int $categoryId, ?int $newParentId): void

    // Sync
    public function syncCategoryToPrestaShop(int $categoryId, int $shopId): void
    public function pullCategoriesFromPrestaShop(int $shopId): array
}
```

### 12.2 CategoryPicker Component

Hierarchiczny wybor kategorii z:
- Checkbox selection
- Primary category radio
- Expandable tree
- Search filter
- Inline create new

### 12.3 Batch Operations

```php
// Pending operations (batched)
$pendingNewCategories = [
    ['name' => 'New Category', 'parentId' => 5, 'tempId' => 'temp_123'],
];

$pendingDeleteCategories = [10, 15, 20];

// Process batch
processPendingCategories()
```

---

## 13. Visual Description (UVE) - Bidirectional Sync

### 13.1 Architektura Bidirectional Sync (v1.2)

```
ProductDescription (blocks_v2)     ProductShopData (long_description)
     |                                      |
     | UVE save() -> compile HTML --------> | (auto-write)
     |                                      |
     | UVE load() <- detect diff <--------- | (textarea edits)
     |                                      |
                                            | --------> PrestaShop API
                                            |   (via ProductTransformer)
                                            |   (via getEffectiveValue)
```

**Kluczowa zasada**: ProductShopData.long_description = SINGLE SOURCE OF TRUTH dla PrestaShop sync. ProductDescription jest EDYTOREM (narzedzie do tworzenia HTML), nie zrodlem danych sync.

### 13.2 UVE save() -> auto-write do ProductShopData

Przy kazdym zapisie UVE (`UnifiedVisualEditor::save()`), rendered HTML jest automatycznie zapisywany do `ProductShopData.long_description`:

```php
// Po ProductDescription::updateOrCreate():
$shopData = ProductShopData::firstOrNew([...]);
$shopData->long_description = $renderedHtml;
$shopData->save();
```

**Efekt:** Textarea w "Opisy i SEO" natychmiast odzwierciedla zmiany z UVE.

### 13.3 Wykrywanie zewnetrznych edycji

Przy ladowaniu UVE (`detectExternalEdits()`), system porownuje `ProductShopData.long_description` z `ProductDescription.rendered_html`. Jezeli sie roznia → notyfikacja warning:

> "Opis zostal zmodyfikowany poza edytorem wizualnym. Zapisanie nadpisze te zmiany wersja z edytora."

### 13.4 syncVisualToStandard() - zapis do DB

Metoda `syncVisualToStandard()` w traicie `ProductFormVisualDescription` nie tylko aktualizuje in-memory `$this->shopData`, ale rowniez persystuje do bazy:

```php
$psd = ProductShopData::firstOrNew([...]);
$psd->long_description = $html;
$psd->save();
```

### 13.5 ProductTransformer - uproszczenie

ProductTransformer nie korzysta juz z bezposredniego Visual Description bypass. Zamiast tego uzywa `getEffectiveValue()` ktore czyta z ProductShopData (single source of truth):

```php
$effectiveShortDesc = $this->getEffectiveValue($shopData, $product, 'short_description');
$effectiveLongDesc = $this->getEffectiveValue($shopData, $product, 'long_description');
```

### 13.6 ProductFormVisualDescription trait

```php
trait ProductFormVisualDescription
{
    public array $visualBlocks = [];
    public ?string $activeBlockId = null;

    public function loadVisualDescription(): void
    public function saveVisualDescription(): void
    public function syncVisualToStandard(): void    // Sync do DB (nie tylko in-memory)
    public function addBlock(string $type): void
    public function updateBlock(string $blockId, array $data): void
    public function deleteBlock(string $blockId): void
    public function reorderBlocks(array $order): void
}
```

---

## 14. CSS i Styling

### 14.1 Enterprise Component Classes

| Klasa | Przeznaczenie |
|-------|---------------|
| `.tabs-enterprise` | Kontener nawigacji tabow |
| `.tab-enterprise` | Pojedynczy tab |
| `.enterprise-card` | Karta formularza |
| `.form-input-enterprise` | Input formularza |
| `.btn-enterprise-primary` | Przycisk glowny (Save) |
| `.btn-enterprise-secondary` | Przycisk wtorny (Cancel) |
| `.badge-enterprise` | Badge statusu |

### 14.2 Field Status Classes

| Klasa | Przeznaczenie |
|-------|---------------|
| `.field-pending-sync` | Zolte obramowanie (oczekuje sync) |
| `.field-inherited` | Odziedziczone z PPM |
| `.field-different` | Nadpisane (shop-specific) |
| `.field-synced` | Zsynchronizowane |

### 14.3 Frozen States

| Klasa | Przeznaczenie |
|-------|---------------|
| `.category-tree-frozen` | Blokada interakcji podczas sync |
| `.shop-data-loading-overlay` | Overlay ladowania |

---

## 15. Diagramy

### 15.1 Multi-Store Data Flow

```
┌──────────────────────────────────────────────────────────────┐
│                      PRODUCT FORM                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │  PPM Data   │  │  Shop Data  │  │  ERP Data   │           │
│  │  (Default)  │  │  (Override) │  │  (External) │           │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘           │
│         │                │                │                   │
│         ▼                ▼                ▼                   │
│  ┌─────────────────────────────────────────────────────┐     │
│  │              FORM FIELDS (shared)                    │     │
│  │  [SKU] [Name] [Description] [Prices] [Stock] [...]   │     │
│  └─────────────────────────────────────────────────────┘     │
│                          │                                    │
│         ┌────────────────┼────────────────┐                  │
│         ▼                ▼                ▼                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │  products   │  │  product_   │  │  product_   │          │
│  │  (table)    │  │  shop_data  │  │  erp_data   │          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
└──────────────────────────────────────────────────────────────┘
                          │
         ┌────────────────┼────────────────┐
         ▼                ▼                ▼
  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
  │ PrestaShop  │  │  BaseLinker │  │  Subiekt GT │
  │    API      │  │    API      │  │    API      │
  └─────────────┘  └─────────────┘  └─────────────┘
```

### 15.2 Job Polling Sequence

```
USER                    LIVEWIRE                 QUEUE                    EXTERNAL
 │                         │                       │                         │
 │ Click "Sync"            │                       │                         │
 ├────────────────────────►│                       │                         │
 │                         │ dispatch Job          │                         │
 │                         ├──────────────────────►│                         │
 │                         │                       │ Job pending             │
 │                         │◄──────────────────────┤                         │
 │ dispatch('job-started') │                       │                         │
 │◄────────────────────────┤                       │                         │
 │                         │                       │                         │
 │ startPolling() (Alpine) │                       │                         │
 │ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─│                       │                         │
 │                         │                       │ Job processing          │
 │                         │                       ├────────────────────────►│
 │                         │                       │                         │
 │ poll (every 5s)         │                       │                         │
 ├────────────────────────►│ checkJobStatus()     │                         │
 │                         ├──────────────────────►│                         │
 │                         │ status: processing    │                         │
 │                         │◄──────────────────────┤                         │
 │ status: processing      │                       │                         │
 │◄────────────────────────┤                       │                         │
 │                         │                       │◄────────────────────────┤
 │                         │                       │ Job completed           │
 │ poll (every 5s)         │                       │                         │
 ├────────────────────────►│ checkJobStatus()     │                         │
 │                         ├──────────────────────►│                         │
 │                         │ status: completed     │                         │
 │                         │◄──────────────────────┤                         │
 │ dispatch('job-completed')                       │                         │
 │◄────────────────────────┤                       │                         │
 │                         │                       │                         │
 │ stopPolling() + refresh │                       │                         │
 └─────────────────────────┴───────────────────────┴─────────────────────────┘
```

### 15.3 Tab Navigation State

```
                    ┌─────────────────────────┐
                    │      PRODUCT FORM       │
                    └───────────┬─────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        ▼                       ▼                       ▼
┌───────────────┐       ┌───────────────┐       ┌───────────────┐
│  Main Tabs    │       │  Shop Tabs    │       │   ERP Tabs    │
│  (10 tabs)    │       │  (per shop)   │       │  (per conn)   │
└───────┬───────┘       └───────┬───────┘       └───────┬───────┘
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────┐       ┌───────────────┐       ┌───────────────┐
│ activeTab:    │       │activeShopId:  │       │activeErpConn: │
│ - basic       │       │ - null (PPM)  │       │ - null (PPM)  │
│ - description │       │ - 1 (Shop A)  │       │ - 1 (BaseLnk) │
│ - physical    │       │ - 2 (Shop B)  │       │ - 2 (Subiekt) │
│ - attributes  │       │ - 3 (Shop C)  │       │ - 3 (Dynamics)│
│ - prices      │       └───────────────┘       └───────────────┘
│ - stock       │
│ - variants    │
│ - gallery     │
│ - visual-desc │
└───────────────┘
```

---

## 16. Bugfixes (v1.2)

### BUG#1: defaultData nie aktualizowane po edycji default tab

**Symptom:** Po wklejeniu identycznego opisu z shop tab do default tab, walidator caly czas pokazuje "Wlasne" mimo identycznych wartosci.

**Przyczyna:** `defaultData['long_description']` bylo ladowane z DB przy mount() i NIGDY nie aktualizowane gdy user edytowal pola na default tab. `getShopFieldStatusInternal()` porownywalo shop value z STALE defaultData.

**Fix:** W `switchToShop()`, po `savePendingChanges()`, gdy opuszczamy default tab (`$previousShopId === null`), synchronizujemy pending default values do `defaultData[]`:

```php
if ($previousShopId === null && isset($this->pendingChanges['default'])) {
    foreach ($fieldsToSync as $field) {
        if (array_key_exists($field, $pendingDefault)) {
            $this->defaultData[$field] = $pendingDefault[$field];
        }
    }
}
```

### BUG#2: Pending default changes tracone przy zapisie z shop context

**Symptom:** Po edycji opisu w default tab, przejsciu do shop tab i kliknieciu "Zapisz zmiany", opis w default tab nie zostal zapisany.

**Przyczyna:** `saveCurrentContextOnly()` zapisywalo TYLKO aktywny kontekst (shop). Pending changes z default tab byly w pamieci ale nigdy nie persistowane do DB. Redirect do listy produktow traciwal caly stan.

**Fix:** W `saveCurrentContextOnly()`, przy zapisie z non-default context, rowniez persist pending default changes:

```php
if (isset($this->pendingChanges['default']) && $this->isEditMode && $this->product) {
    $this->savePendingChangesToProduct($this->pendingChanges['default'], markShopsAsPending: false);
    unset($this->pendingChanges['default']);
}
```

### BUG#3: Liczniki znakow pokazuja 0 przy ladowaniu

**Symptom:** Po otwarciu karty produktu i przejsciu na "Opisy i SEO", liczniki pokazuja "0/800" i "0/21844" do momentu edycji textarea.

**Przyczyna:** `updateCharacterCounts()` bylo wywolywane tylko w `switchToShop()` i `resetToDefaults()`. NIE bylo wywolywane po `mount()` → `loadProductData()` ani `loadDefaultDataToForm()`. Properties inicjalizowane jako `0`.

**Fix:** Dodano `$this->updateCharacterCounts()` w dwoch miejscach:
1. Po `storeDefaultData()` w `loadProductData()`
2. Na koncu `loadDefaultDataToForm()`

---

## Appendix A: Kluczowe pliki

| Typ | Sciezka |
|-----|---------|
| Main Component | `app/Http/Livewire/Products/Management/ProductForm.php` |
| ShopTabs Trait | `app/Http/Livewire/Products/Management/Traits/ProductFormShopTabs.php` |
| ERPTabs Trait | `app/Http/Livewire/Products/Management/Traits/ProductFormERPTabs.php` |
| Validation Trait | `app/Http/Livewire/Products/Management/Traits/ProductFormValidation.php` |
| Saver Service | `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php` |
| Category Manager | `app/Http/Livewire/Products/Management/Services/ProductCategoryManager.php` |
| Main Blade | `resources/views/livewire/products/management/product-form.blade.php` |
| ShopVariantService | `app/Services/PrestaShop/ShopVariantService.php` |
| PrestaShop8Client | `app/Services/PrestaShop/PrestaShop8Client.php` |
| VariantModalsTrait | `app/Http/Livewire/Products/Management/Traits/VariantModalsTrait.php` |
| VariantCrudTrait | `app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php` |
| ProductTransformer | `app/Services/PrestaShop/ProductTransformer.php` |
| UnifiedVisualEditor | `app/Http/Livewire/Products/VisualDescription/UnifiedVisualEditor.php` |
| VisualDescription Trait | `app/Http/Livewire/Products/Management/Traits/ProductFormVisualDescription.php` |

---

## Appendix B: Related Jobs

| Job | Sciezka | Przeznaczenie |
|-----|---------|---------------|
| SyncProductToPrestaShop | `app/Jobs/PrestaShop/SyncProductToPrestaShop.php` | Sync do PS |
| PullSingleProductFromPrestaShop | `app/Jobs/PrestaShop/PullSingleProductFromPrestaShop.php` | Pull z PS |
| SyncProductToERP | `app/Jobs/ERP/SyncProductToERP.php` | Sync do ERP |
| SyncShopVariantsToPrestaShopJob | `app/Jobs/PrestaShop/SyncShopVariantsToPrestaShopJob.php` | Sync wariantow + ceny/stany (ETAP_14) |

---

## Appendix C: Related Models

| Model | Tabela | Przeznaczenie |
|-------|--------|---------------|
| Product | `products` | Glowne dane produktu |
| ProductShopData | `product_shop_data` | Dane per sklep PS |
| ProductErpData | `product_erp_data` | Dane per ERP connection |
| ProductPrice | `product_prices` | Ceny grupowe |
| ProductStock | `product_stock` | Stany magazynowe |
| ProductVariant | `product_variants` | Warianty produktu |
| ProductAttribute | `product_attributes` | Atrybuty/cechy |
| PriceGroup | `price_groups` | Grupy cenowe |
| Warehouse | `warehouses` | Magazyny |
| PrestaShopShop | `prestashop_shops` | Sklepy PrestaShop |
| ERPConnection | `erp_connections` | Polaczenia ERP |

---

**Koniec dokumentacji ProductForm**
