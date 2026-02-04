# Plan: Dokumentacja ProductForm dla .Release_docs/

## Cel
Utworzenie kompletnej dokumentacji komponentu ProductForm w `.Release_docs/02_PRODUCT_FORM.md`

---

## Struktura dokumentacji (sekcje)

### 1. OVERVIEW
- Opis komponentu (mega-component: 10,651 linii)
- Przeznaczenie (edycja/tworzenie produktow multi-store)
- Architektura (main component + 9 traits + 3 services)

### 2. ARCHITEKTURA PLIKOW
- Glowny komponent: `app/Http/Livewire/Products/Management/ProductForm.php`
- Traits (9 plikow w `Traits/`)
- Services (3 pliki w `Services/`)
- Blade templates (50+ partials w `resources/views/`)
- Child components (GalleryTab, CategoryPicker, CategoryConflictModal)

### 3. TABY (ZAKLADKI) - 10 tabow
| Tab | Plik | Dane | Akcje |
|-----|------|------|-------|
| basic | tabs/basic-tab.blade.php | SKU, name, manufacturer | Pull from PS |
| description | tabs/description-tab.blade.php | Opisy, SEO | Character counters |
| physical | tabs/physical-tab.blade.php | Waga, wymiary | Volume calc |
| attributes | tabs/attributes-tab.blade.php | Cechy produktu | CRUD features |
| compatibility | tabs/compatibility-tab.blade.php | Dopasowania | Matrix edit |
| prices | tabs/prices-tab.blade.php | Ceny grupowe | Lock/unlock |
| stock | tabs/stock-tab.blade.php | Stany magazynowe | Lock/unlock |
| variants | tabs/variants-tab.blade.php | Warianty | Full CRUD |
| gallery | GalleryTab component | Media | Upload/manage |
| visual-description | tabs/visual-description-tab.blade.php | Visual Editor | Block builder |

### 4. PROPERTIES (100+ public properties)
- Product & Mode
- Tab Navigation
- Basic Information
- Description
- Physical Properties
- Tax Management (ETAP_14)
- Category Management
- Shop Management
- ERP Management (ETAP_08)
- Prices & Stock
- Job Tracking
- Form State

### 5. METODY (150+ public methods)
- Initialization (mount, hydrate, render)
- Tab Navigation
- Category Management (~25 metod)
- Shop Management (~20 metod)
- ERP Management (~15 metod)
- Prices/Stock Lock (~15 metod)
- Sync & Job Tracking (~20 metod)
- Save Operations (~10 metod)
- Validation (~15 metod)
- Variant Management (~20 metod)

### 6. SHOP TABS SYSTEM
- Multi-store pattern (3-tier data model)
- PPM Default Data -> Shop-Specific Data -> PrestaShop External
- Sync pipeline (dispatch job -> poll status -> update UI)
- Conflict resolution

### 7. ERP TABS SYSTEM (ETAP_08)
- Analogiczny pattern do Shop Tabs
- Supportowane ERP: BaseLinker, Subiekt GT, Dynamics
- Job tracking (2s polling)
- Pending changes indicators

### 8. JOBY I SYNCHRONIZACJA
- PrestaShop Jobs: SyncProductToPrestaShop, PullSingle, BulkSync, Delete
- ERP Jobs: SyncProductToERP, PullFromERP, BaselinkerSyncJob, etc.
- Job polling mechanism (Alpine.js + Livewire)
- Status tracking (pending, processing, completed, failed)

### 9. EVENTY I LISTENERS
- Livewire listeners (shop-categories-reloaded, delayed-reset)
- Alpine window events (job-started, job-completed, job-failed)
- Dispatchers (syncToShops, syncToErp)

### 10. WALIDACJA
- Livewire validation rules (ProductFormValidation trait)
- Business rules validation
- Category validation per shop

### 11. VARIANT SYSTEM
- Traits: VariantCrudTrait, VariantPriceTrait, VariantStockTrait, etc.
- Modals: Create, Edit, Delete, Duplicate
- Per-variant: prices, stock, images, attributes

### 12. CATEGORY MANAGEMENT
- ProductCategoryManager service
- Hierarchical tree (CategoryPicker component)
- Pending create/delete batching
- Sync to PrestaShop

### 13. VISUAL DESCRIPTION (UVE)
- ProductFormVisualDescription trait
- Integration with Visual Editor
- Block-based content

### 14. CSS & STYLING
- Enterprise component classes
- Field status indicators
- Frozen states during sync

### 15. DIAGRAMY
- Multi-Store Data Flow
- Job Polling Sequence
- Tab Navigation State Machine

---

## Krytyczne pliki do przeczytania

### Main Component
- `app/Http/Livewire/Products/Management/ProductForm.php`

### Traits (do zrozumienia modulow)
- `Traits/ProductFormShopTabs.php` - Shop management
- `Traits/ProductFormERPTabs.php` - ERP management
- `Traits/ProductFormVariants.php` - Variant orchestrator
- `Traits/ProductFormValidation.php` - Validation rules

### Services
- `Services/ProductFormSaver.php` - Save logic
- `Services/ProductCategoryManager.php` - Category operations

### Blade Templates
- `product-form.blade.php` - Main template
- `tabs/*.blade.php` - Tab templates
- `partials/shop-management.blade.php` - Shop UI
- `partials/erp-management.blade.php` - ERP UI

---

## Plan wykonania

1. Przeczytac kluczowe traits dla szczegulow
2. Przeczytac glowne blade templates
3. Napisac dokumentacje sekcja po sekcji
4. Dodac diagramy ASCII dla data flow

---

## Weryfikacja

- [ ] Wszystkie 10 tabow udokumentowane
- [ ] Wszystkie traits wymienione z opisem
- [ ] Job tracking mechanism opisany
- [ ] Multi-store pattern wyjasiony
- [ ] ERP integration pattern wyjasiony
- [ ] Diagramy data flow dodane
