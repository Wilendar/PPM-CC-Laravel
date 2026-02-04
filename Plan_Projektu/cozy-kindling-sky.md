# PLAN: Panel Zarządzania Dostawcami (BusinessPartner System)

**Data:** 2026-01-30
**Branch:** `feature/supplier-management`
**Etap:** ETAP_15 - Supplier/Manufacturer/Importer Management

---

## 1. PODSUMOWANIE

Stworzenie panelu zarządzania dostawcami, producentami i importerami z:
- Unified `BusinessPartner` model z type field (supplier/manufacturer/importer)
- Panel admin `/admin/suppliers` z 2-kolumnowym layoutem (25/75%)
- 4 zakładki: DOSTAWCA | PRODUCENT | IMPORTER | BRAK
- Integracja z ProductForm (3 dropdown fields)
- Integracja z Import Panel
- Sync: PrestaShop (manufacturer + supplier) + Subiekt GT (tw_IdPodstDostawca, tw_IdProducenta)

---

## 2. ARCHITEKTURA

### 2.1 Model danych

**Tabela `business_partners`** (nowa, unified):
```
id, type (enum: supplier/manufacturer/importer), name, code,
company_name, address, postal_code, city, country, email, phone,
ps_link_rewrite, description, short_description,
meta_title, meta_description, meta_keywords,
logo_path, website, is_active, sort_order,
subiekt_contractor_id,
timestamps, soft_deletes
UNIQUE(type, code)
```

**Tabela `business_partner_shop`** (pivot, nowa):
```
id, business_partner_id (FK), prestashop_shop_id (FK),
ps_manufacturer_id (dla type=manufacturer),
ps_supplier_id (dla type=importer),
sync_status, last_synced_at, logo_synced, logo_synced_at, sync_error,
timestamps
UNIQUE(business_partner_id, prestashop_shop_id)
```

**Products - nowe FK kolumny:**
```
supplier_id → business_partners(id) [NOWA]
manufacturer_id → business_partners(id) [ISTNIEJĄCA, remap FK]
importer_id → business_partners(id) [NOWA]
```

### 2.2 Mapowanie sync

| PPM (BusinessPartner type) | PrestaShop | Subiekt GT |
|---------------------------|-----------|-----------|
| supplier (Dostawca) | BRAK | tw_IdPodstDostawca |
| manufacturer (Producent) | id_manufacturer | tw_IdProducenta |
| importer (Importer) | id_supplier | BRAK |

### 2.3 Backward Compatibility

`Manufacturer extends BusinessPartner` z global scope `type='manufacturer'` - cały istniejący kod (ManufacturerSyncService, ManufacturerTransformer, ManufacturerManager, ProductTransformer) działa bez zmian.

---

## 3. PLAN IMPLEMENTACJI (SUBTASKS)

### SUBTASK 1: Migracje bazy danych
**Pliki:**
- `database/migrations/YYYY_MM_DD_000001_create_business_partners_table.php`
- `database/migrations/YYYY_MM_DD_000002_create_business_partner_shop_table.php`
- `database/migrations/YYYY_MM_DD_000003_migrate_manufacturers_to_business_partners.php`
- `database/migrations/YYYY_MM_DD_000004_migrate_manufacturer_shop_to_business_partner_shop.php`
- `database/migrations/YYYY_MM_DD_000005_add_supplier_importer_fk_to_products.php`
- `database/migrations/YYYY_MM_DD_000006_remap_manufacturer_id_fk_to_business_partners.php`

**Krytyczne:**
- Migracja #3 MUSI zachować oryginalne ID z manufacturers (dla FK spójności)
- Migracja #6 drop FK z manufacturers → add FK do business_partners
- pending_products też dostaje supplier_id + importer_id
- NIE usuwamy manufacturers table na razie (dopiero po weryfikacji)

### SUBTASK 2: Modele (BusinessPartner + proxy models)
**Pliki:**
- `app/Models/BusinessPartner.php` (~200 linii)
  - type constants + labels (PL)
  - fillable + casts
  - relationships: shops (pivot), productsAsSupplier/Manufacturer/Importer
  - scopes: byType(), suppliers(), manufacturers(), importers(), active(), ordered(), search()
  - helpers: isSupplier(), isManufacturer(), isImporter(), hasPsSync(), getPsEntityField()
  - contact helpers: hasContactInfo(), getFullAddress()
  - static: getForDropdown(type)

- `app/Models/Manufacturer.php` (REFAKTOR ~30 linii)
  - `extends BusinessPartner`
  - `$table = 'business_partners'`
  - Global scope: `type = 'manufacturer'`
  - Auto-set type on creating
  - Override products() → hasMany via manufacturer_id

- `app/Models/Supplier.php` (NOWY ~25 linii)
  - `extends BusinessPartner`
  - Global scope: `type = 'supplier'`
  - products() → hasMany via supplier_id

- `app/Models/Importer.php` (NOWY ~25 linii)
  - `extends BusinessPartner`
  - Global scope: `type = 'importer'`
  - products() → hasMany via importer_id

- `app/Models/Product.php` (MODYFIKACJA)
  - Dodać do fillable: `supplier_id`, `importer_id`
  - Dodać relationships: `supplierRelation()`, `importerRelation()`
  - Update `manufacturerRelation()` → wskazuje na BusinessPartner

### SUBTASK 3: Panel Admin - Livewire Component
**Pliki:**
- `app/Http/Livewire/Admin/Suppliers/BusinessPartnerPanel.php` (~120 linii)
  - Main component z 4 tabs + selectedEntityId
  - uses 3 traits + WithPagination + WithFileUploads
  - #[Layout('layouts.admin')]
  - #[Url] activeTab

- `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerCrudTrait.php` (~200 linii)
  - CRUD: openCreateModal, save, saveEntityDetails, confirmDelete, delete
  - Computed: entities(), selectedEntity()
  - Form data management, validation
  - Logo upload

- `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerProductsTrait.php` (~250 linii)
  - Computed: entityProducts() (paginated)
  - Inline editing: updateProductAssignment(), updateProductSupplierCode()
  - Computed dropdowns: allSuppliers(), allManufacturers(), allImporters()
  - BRAK tab: brakFilter, produkty bez przypisań

- `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerFiltersTrait.php` (~100 linii)
  - entitySearch, productSearch, statusFilter
  - Reset/updated hooks

### SUBTASK 4: Blade Templates (Panel Admin)
**Pliki:**
- `resources/views/livewire/admin/suppliers/business-partner-panel.blade.php` (main layout)
  - Header + 4 tabs (DOSTAWCA/PRODUCENT/IMPORTER/BRAK)
  - 2-column grid: sidebar 25% | main 75%
  - Include partials + create modal

- `resources/views/livewire/admin/suppliers/partials/entity-list.blade.php`
  - Add button + search + scrollable lista podmiotów
  - Klik → selectEntity() → ładuje prawą kolumnę

- `resources/views/livewire/admin/suppliers/partials/entity-form.blade.php`
  - Edytowalne dane: name, company_name, address, postal_code, city, country, email, phone, logo
  - Save/Cancel buttons

- `resources/views/livewire/admin/suppliers/partials/product-table.blade.php`
  - Search + tabela produktów z inline editing
  - Kolumny: Nazwa, SKU, Kod dostawcy (input), Dostawca (dropdown), Producent (dropdown), Importer (dropdown), Sklepy (badges), ERP (badges), Akcje
  - Paginacja

- `resources/views/livewire/admin/suppliers/partials/brak-tab.blade.php`
  - Filtr: brak dowolnego / brak dostawcy / brak producenta / brak importera
  - Identyczna tabela produktów z inline editing

- `resources/views/livewire/admin/suppliers/partials/create-modal.blade.php`
  - Modal tworzenia nowego BusinessPartner
  - Pola: logo upload, nazwa, firma, adres, kod pocztowy, miasto, kraj, email, telefon

### SUBTASK 5: CSS + Route + Navigation
**Pliki:**
- `resources/css/admin/supplier-panel.css` (NOWY ~200 linii)
  - BEM: `.supplier-panel__*`
  - Layout grid 1:3, tabs, entity-list (scrollable), entity-item, inline-input/select
  - Responsive (<1024px → single column)
  - Dark theme tokens: bg-gray-800/50, text-gray-300, brand color var(--mpp-primary)

- `resources/css/app.css` - dodać `@import './admin/supplier-panel.css'`

- `routes/web.php` - dodać route:
  ```php
  Route::get('/suppliers', BusinessPartnerPanel::class)->name('suppliers.index');
  ```

- `resources/views/layouts/admin.blade.php` - dodać sidebar link "Zarządzanie dostawcami"
- `resources/views/layouts/navigation.blade.php` - dodać mobile link

### SUBTASK 6: Integracja ProductForm
**Pliki:**
- `app/Http/Livewire/Products/Management/ProductForm.php`
  - Dodać properties: `public ?int $supplier_id = null`, `public ?int $importer_id = null`
  - manufacturer_id już istnieje (remap do BusinessPartner)

- `resources/views/livewire/products/management/tabs/basic-tab.blade.php`
  - ZMIENIĆ pole "Producent" (text input, linie 447-468):
    - "Producent" label → "Dostawca" label
    - text input → dropdown select z BusinessPartner::getForDropdown('supplier')
    - wire:model → supplier_id
  - DODAĆ pole "Producent" (dropdown):
    - BusinessPartner::getForDropdown('manufacturer')
    - wire:model → manufacturer_id
  - DODAĆ pole "Importer" (dropdown):
    - BusinessPartner::getForDropdown('importer')
    - wire:model → importer_id
  - Zachować "Kod dostawcy" (supplier_code) jako text input

- `app/Http/Livewire/Products/Management/Traits/ProductFormComputed.php`
  - Dodać computed: suppliersForDropdown(), manufacturersForDropdown(), importersForDropdown()

- `app/Http/Livewire/Products/Management/Services/ProductFormSaver.php`
  - Dodać do save data: supplier_id, importer_id

### SUBTASK 7: Integracja Import Panel
**Pliki:**
- `app/Services/Import/ColumnMappingService.php`
  - Dodać mappings: supplier_id (aliases: dostawca, supplier), importer_id (aliases: importer)
  - Rename: manufacturer → manufacturer_id (producent, manufacturer, marka)

- `app/Services/Import/BatchImportProcessor.php`
  - Dodać resolveBusinessPartnerId(name, type) → auto-create
  - W processRow: resolve supplier_id, manufacturer_id, importer_id z nazw

### SUBTASK 8: Sync - PrestaShop (Importer → id_supplier)
**Pliki:**
- `app/Services/PrestaShop/PrestaShop8Client.php`
  - Dodać methods: getSuppliers(), getSupplier(), createSupplier(), updateSupplier()
  - Analogicznie do manufacturer endpoints ale dla /suppliers

- `app/Services/PrestaShop/ImporterSyncService.php` (NOWY)
  - Analogiczny do ManufacturerSyncService
  - Import/export importerów jako PS suppliers
  - Logo sync

- `app/Services/PrestaShop/ProductTransformer.php`
  - Dodać: getImporterPsId() → zwraca ps_supplier_id z business_partner_shop
  - W transformForPrestaShop(): dodać 'id_supplier' => getImporterPsId()
  - W transformToPPM(): dodać importImporter() analogiczny do importManufacturer()

### SUBTASK 9: Sync - Subiekt GT (Dostawca + Producent)
**Pliki:**
- `app/Services/ERP/SubiektGTService.php`
  - Dodać mapowanie: supplier.subiekt_contractor_id → tw_IdPodstDostawca
  - Dodać mapowanie: manufacturer.subiekt_contractor_id → tw_IdProducenta
  - W pullProducts(): importować dostawcę/producenta z Subiekt GT
  - W pushProducts(): wysyłać dostawcę/producenta do Subiekt GT

- `_TOOLS/SubiektGT_REST_API_DotNet/SubiektRepository.cs`
  - Dodać do product query: tw_IdPodstDostawca, tw_IdProducenta
  - Dodać endpoint: GET /api/contractors (kh__Kontrahent) do lookupów

### SUBTASK 10: Build, Deploy, Verify
- `npm run build`
- Upload do produkcji (pscp)
- `php artisan migrate` na produkcji
- Chrome verification: panel, ProductForm, import
- Test sync: PrestaShop + Subiekt GT

---

## 4. ZALEŻNOŚCI MIĘDZY SUBTASKS

```
SUBTASK 1 (Migracje) ──→ SUBTASK 2 (Modele) ──→ SUBTASK 3 (Livewire)
                                    │                      │
                                    ↓                      ↓
                           SUBTASK 6 (ProductForm)  SUBTASK 4 (Blade)
                           SUBTASK 7 (Import)       SUBTASK 5 (CSS/Route)
                                    │
                                    ↓
                           SUBTASK 8 (PS Sync) + SUBTASK 9 (Subiekt Sync)
                                    │
                                    ↓
                           SUBTASK 10 (Deploy/Verify)
```

**Parallel groups:**
- Group A (core): ST1 → ST2 → ST3 + ST4 + ST5 (sequential)
- Group B (integrations): ST6 + ST7 + ST8 + ST9 (po ST2, mogą iść równolegle)
- Group C (final): ST10 (po wszystkich)

---

## 5. KLUCZOWE PLIKI DO MODYFIKACJI

| Plik | Zmiana |
|------|--------|
| `app/Models/Product.php` | +supplier_id, +importer_id w fillable, +2 relacje |
| `app/Models/Manufacturer.php` | REFAKTOR: extends BusinessPartner, global scope |
| `app/Services/PrestaShop/ProductTransformer.php` | +getImporterPsId(), +id_supplier mapping |
| `app/Services/PrestaShop/PrestaShop8Client.php` | +supplier CRUD endpoints |
| `app/Services/ERP/SubiektGTService.php` | +tw_IdPodstDostawca, +tw_IdProducenta mapping |
| `app/Services/Import/ColumnMappingService.php` | +supplier_id, +importer_id mappings |
| `app/Services/Import/BatchImportProcessor.php` | +resolveBusinessPartnerId() |
| `app/Http/Livewire/Products/Management/ProductForm.php` | +supplier_id, +importer_id properties |
| `resources/views/.../tabs/basic-tab.blade.php` | Producent→Dostawca dropdown, +Producent, +Importer |
| `routes/web.php` | +/admin/suppliers route |
| `resources/views/layouts/admin.blade.php` | +sidebar link |
| `resources/css/app.css` | +import supplier-panel.css |

## 6. NOWE PLIKI

| Plik | Opis |
|------|------|
| `app/Models/BusinessPartner.php` | Unified model z type field |
| `app/Models/Supplier.php` | Proxy model (extends BusinessPartner) |
| `app/Models/Importer.php` | Proxy model (extends BusinessPartner) |
| `app/Http/Livewire/Admin/Suppliers/BusinessPartnerPanel.php` | Main Livewire component |
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerCrudTrait.php` | CRUD trait |
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerProductsTrait.php` | Products trait |
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerFiltersTrait.php` | Filters trait |
| `resources/views/livewire/admin/suppliers/business-partner-panel.blade.php` | Main template |
| `resources/views/livewire/admin/suppliers/partials/entity-list.blade.php` | Left panel |
| `resources/views/livewire/admin/suppliers/partials/entity-form.blade.php` | Right panel top |
| `resources/views/livewire/admin/suppliers/partials/product-table.blade.php` | Right panel bottom |
| `resources/views/livewire/admin/suppliers/partials/brak-tab.blade.php` | BRAK tab |
| `resources/views/livewire/admin/suppliers/partials/create-modal.blade.php` | Create modal |
| `resources/css/admin/supplier-panel.css` | Panel CSS (BEM) |
| `app/Services/PrestaShop/ImporterSyncService.php` | PS supplier sync |
| 6x migracje | Schema + data migrations |

---

## 7. WERYFIKACJA (end-to-end)

1. **Panel admin** (`/admin/suppliers`):
   - [x] 4 zakładki działają (DOSTAWCA/PRODUCENT/IMPORTER/BRAK) ✅ 2026-02-02
   - [ ] Dodawanie nowego podmiotu via modal (nie testowane - wymaga ręcznego testu)
   - [x] Edycja szczegółów w prawej kolumnie ✅ 2026-02-02
   - [x] Lista produktów z inline editing ✅ 2026-02-02
   - [x] BRAK tab filtruje produkty bez przypisań ✅ 2026-02-02 (38 produktów)

2. **ProductForm** (`/admin/products/{id}/edit`):
   - [x] "Dostawca" dropdown ✅ 2026-02-02 (puste - brak suppliers w DB)
   - [x] "Producent" dropdown ✅ 2026-02-02 (5 opcji: KAYO, MRF, Test Brand, WM, YCF)
   - [x] "Importer" dropdown ✅ 2026-02-02 (puste - brak importerów w DB)
   - [ ] Zapis wszystkich 3 FK (nie testowany - wymaga ręcznego testu)

3. **Import** (`/admin/products/import`):
   - [ ] Kolumny dostawca/producent/importer w mapowaniu (wymaga ręcznego testu z plikiem XLSX)
   - [ ] Auto-create BusinessPartner z importu (wymaga ręcznego testu)

4. **Sync PrestaShop**:
   - [ ] Producent → id_manufacturer (istniejący sync działa - wymaga testu sync)
   - [ ] Importer → id_supplier (nowy sync - wymaga testu sync)

5. **Sync Subiekt GT**:
   - [ ] Dostawca → tw_IdPodstDostawca (wymaga testu sync)
   - [ ] Producent → tw_IdProducenta (wymaga testu sync)

6. **Chrome DevTools verification** (MANDATORY):
   - [x] Screenshot panelu ✅ 2026-02-02
   - [x] Console errors = 0 ✅ 2026-02-02
   - [x] Dark theme poprawny ✅ 2026-02-02
   - [ ] Responsive layout OK (nie testowany)

---

## 8. RYZYKO I MITIGACJA

| Ryzyko | Impact | Mitigacja |
|--------|--------|-----------|
| Migracja manufacturers → business_partners złamie FK | HIGH | Preserve original IDs, test na staging |
| ManufacturerSyncService przestanie działać | HIGH | Proxy model pattern (Manufacturer extends BusinessPartner) |
| Inline editing w tabeli produktów = dużo requestów | MEDIUM | wire:change (nie wire:model.live), debounce |
| Duża ilość produktów w BRAK tab | MEDIUM | Paginacja + indexed NULL queries |
| Subiekt GT REST API nie ma contractor lookups | MEDIUM | Dodać endpoint GET /api/contractors |
