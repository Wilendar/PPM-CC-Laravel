# ❌ ETAP_09: System IMPORT DO PPM - Masowy Import Produktów

## 📋 INFORMACJE O ETAPIE

**Status ETAPU:** ❌ **NIEROZPOCZĘTY**
**Szacowany czas:** 80 godzin (10 dni roboczych)
**Priorytet:** 🔴 WYSOKI
**Zależności:** ETAP_05_Produkty.md (✅ COMPLETED), ETAP_07_Prestashop_API.md (🛠️ IN PROGRESS)
**Następny etap:** ETAP_10_Dashboard_Analytics.md

**📊 POSTĘP IMPLEMENTACJI:** 0%

**Raport Architektoniczny:** `_AGENT_REPORTS/architect_PPM_IMPORT_SYSTEM_ARCHITECTURE.md`
**Diagramy:** `_AGENT_REPORTS/architect_PPM_IMPORT_DIAGRAMS.md`

---

## 🎯 OPIS ETAPU

System **IMPORT DO PPM** to dedykowany moduł służący do masowego wprowadzania produktów przez różne działy organizacji MPP TRADE. Kluczowa różnica: produkty "niekompletne" przechowywane są w dedykowanym panelu i **NIE pojawiają się** w głównej liście `/admin/products` dopóki nie zostaną uzupełnione wszystkie wymagane dane.

### 🏗️ GŁÓWNE KOMPONENTY:
- **📋 Panel Produktów Oczekujących** - lista DRAFT products z wizualizacją statusu
- **🔍 Walidacja przed publikacją** - wymuszenie kompletu danych
- **⚡ Akcje masowe** - przypisywanie kategorii, prefixów, typów
- **📤 3 sposoby importu** - wklejanie SKU, CSV/Excel, ERP (przyszłość)
- **🔄 Workflow publikacji** - DRAFT → ProductList → PrestaShop sync
- **📜 Historia importu** - audit trail wszystkich operacji

### Kluczowe osiągnięcia etapu:
- ✅ System Draft produktów niezależny od ProductList
- ✅ 3 tryby importu (paste SKU, paste SKU+Name, CSV/Excel)
- ✅ Panel edycji z inline editing i modal
- ✅ Akcje masowe (kategorie, prefix/suffix, typ, sklepy)
- ✅ Workflow publikacji z queue jobs
- ✅ Historia publikacji z sync tracking
- ✅ Integracja z ProductForm, CategoryTree, CompatibilityManagement

---

## 📋 SZCZEGÓŁOWY PLAN ZADAŃ

### ❌ **FAZA 1: FUNDAMENT DATABASE SCHEMA (8h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Utworzenie tabel i podstawowych modeli

- ❌ **1.1 Database Migrations**
  - ❌ **1.1.1 Migration: pending_products table**
    - ❌ 1.1.1.1 Schema definition z wszystkimi polami
    - ❌ 1.1.1.2 Foreign keys (import_session_id, imported_by, published_as_product_id)
    - ❌ 1.1.1.3 Indexes (sku, completion_percentage, is_ready_for_publish)
    - ❌ 1.1.1.4 JSON fields (category_ids, shop_ids, temp_media_paths, completion_status)
  - ❌ **1.1.2 Migration: import_sessions table**
    - ❌ 1.1.2.1 Schema definition z tracking fields
    - ❌ 1.1.2.2 UUID field dla public tracking
    - ❌ 1.1.2.3 ENUM dla import_method i status
    - ❌ 1.1.2.4 JSON fields (parsed_data, error_log)
  - ❌ **1.1.3 Migration: publish_history table**
    - ❌ 1.1.3.1 Schema definition z audit trail
    - ❌ 1.1.3.2 Foreign keys (pending_product_id, product_id, published_by)
    - ❌ 1.1.3.3 JSON fields (published_shops, published_categories, sync_jobs_dispatched)

- ❌ **1.2 Eloquent Models**
  - ❌ **1.2.1 PendingProduct Model**
    - ❌ 1.2.1.1 Fillable, casts, dates definition
    - ❌ 1.2.1.2 Relations: belongsTo(ImportSession), belongsTo(User), belongsTo(Product)
    - ❌ 1.2.1.3 Scopes: scopeIncomplete, scopeReadyForPublish, scopeBySession
    - ❌ 1.2.1.4 Methods: calculateCompletion(), canPublish(), publishToProductList()
    - ❌ 1.2.1.5 Accessors: getCompletionPercentageAttribute(), getMissingFieldsAttribute()
  - ❌ **1.2.2 ImportSession Model**
    - ❌ 1.2.2.1 Fillable, casts, relations
    - ❌ 1.2.2.2 hasMany(PendingProduct), belongsTo(User)
    - ❌ 1.2.2.3 Methods: markAsParsing(), markAsReady(), markAsCompleted(), addError()
    - ❌ 1.2.2.4 getStats() method - zwraca statystyki sesji
  - ❌ **1.2.3 PublishHistory Model**
    - ❌ 1.2.3.1 Fillable, casts, relations
    - ❌ 1.2.3.2 belongsTo(PendingProduct), belongsTo(Product), belongsTo(User)
    - ❌ 1.2.3.3 Scopes: scopeByUser, scopeByDateRange, scopeBySyncStatus

- ❌ **1.3 Seeders & Testing Data**
  - ❌ 1.3.1 PendingProductSeeder z przykładowymi danymi (różne completion_percentage)
  - ❌ 1.3.2 ImportSessionSeeder z przykładowymi sesjami importu
  - ❌ 1.3.3 Factory definitions dla testów

**🎯 Kryteria akceptacji FAZY 1:**
- ✅ Wszystkie migrations wykonane poprawnie
- ✅ Modele z pełnymi relations i methods
- ✅ Seeders działają bez błędów
- ✅ php artisan migrate:fresh --seed działa
- ✅ Podstawowe testy unit dla models (calculateCompletion, canPublish)

---

### ❌ **FAZA 2: IMPORT ENGINE (12h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Parsing różnych formatów importu + walidacja

- ❌ **2.1 Services Layer**
  - ❌ **2.1.1 ImportProcessor Service**
    - ❌ 2.1.1.1 parseSingleColumn(string $text, string $separator) - paste SKU
    - ❌ 2.1.1.2 parseTwoColumns(string $text, string $separator, string $columnDelimiter) - SKU+Name
    - ❌ 2.1.1.3 parseCsvFile(string $filePath, array $columnMapping) - CSV import
    - ❌ 2.1.1.4 parseExcelFile(string $filePath, array $columnMapping) - Excel import
    - ❌ 2.1.1.5 Auto-detect separator (semicolon, comma, tab, newline)
    - ❌ 2.1.1.6 validateImportData(array $rows) - format validation
    - ❌ 2.1.1.7 detectDuplicates(array $skus) - conflict detection

  - ❌ **2.1.2 ValidationService**
    - ❌ 2.1.2.1 validateForPublish(PendingProduct $product) - business rules
    - ❌ 2.1.2.2 checkSkuConflict(string $sku) - vs products + pending_products
    - ❌ 2.1.2.3 validateRequiredFields() - SKU, Name, Category, Type, Shops
    - ❌ 2.1.2.4 validateCategoryDepth() - L3-L7 check
    - ❌ 2.1.2.5 validateMediaCount() - min 1 image rule

  - ❌ **2.1.3 PendingProductService**
    - ❌ 2.1.3.1 create(array $data) - CRUD create
    - ❌ 2.1.3.2 update(PendingProduct $product, array $data) - CRUD update
    - ❌ 2.1.3.3 delete(PendingProduct $product) - soft delete
    - ❌ 2.1.3.4 bulkUpdate(array $productIds, array $data) - bulk operations
    - ❌ 2.1.3.5 calculateCompletion(PendingProduct $product) - dynamic calculation

- ❌ **2.2 Controllers**
  - ❌ **2.2.1 ImportController**
    - ❌ 2.2.1.1 parse(Request $request) - handle import submission
    - ❌ 2.2.1.2 confirm(Request $request) - confirm import after preview
    - ❌ 2.2.1.3 Validation rules dla różnych import methods

- ❌ **2.3 Unit Tests**
  - ❌ **2.3.1 ImportProcessorTest**
    - ❌ 2.3.1.1 test_parse_single_column_with_newline
    - ❌ 2.3.1.2 test_parse_two_columns_semicolon
    - ❌ 2.3.1.3 test_parse_two_columns_comma
    - ❌ 2.3.1.4 test_parse_two_columns_tab
    - ❌ 2.3.1.5 test_auto_detect_separator
    - ❌ 2.3.1.6 test_parse_csv_with_mapping
    - ❌ 2.3.1.7 test_detect_duplicates_in_products
    - ❌ 2.3.1.8 test_detect_duplicates_in_pending
  - ❌ **2.3.2 ValidationServiceTest**
    - ❌ 2.3.2.1 test_validate_complete_product
    - ❌ 2.3.2.2 test_validate_missing_required_fields
    - ❌ 2.3.2.3 test_check_sku_conflict
    - ❌ 2.3.2.4 test_validate_category_depth

**🎯 Kryteria akceptacji FAZY 2:**
- ✅ Import 100 SKU: parsing <3s
- ✅ Auto-detect separator działa dla 95% przypadków
- ✅ CSV/Excel import z custom mapping
- ✅ Conflict detection wykrywa duplikaty vs products + pending_products
- ✅ 100% code coverage dla ImportProcessor
- ✅ Wszystkie unit tests przechodzą

---

### ❌ **FAZA 3: UI PANEL IMPORTU (16h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Panel importu + lista produktów oczekujących

- ❌ **3.1 Livewire Components**
  - ❌ **3.1.1 PendingProductsList Component**
    - ❌ 3.1.1.1 Główna lista z tabelą (SKU, Nazwa, Typ, Kategorie, % Gotowe)
    - ❌ 3.1.1.2 Inline editing (SKU, Nazwa, Typ produktu)
    - ❌ 3.1.1.3 Checkboxes dla bulk actions
    - ❌ 3.1.1.4 Row actions: Podgląd, Edytuj, Usuń, Publikuj
    - ❌ 3.1.1.5 Filtry: By completion %, by session, by user
    - ❌ 3.1.1.6 Sortowanie po wszystkich kolumnach
    - ❌ 3.1.1.7 Pagination (25, 50, 100 per page)
    - ❌ 3.1.1.8 Real-time progress bars (0-100%)

  - ❌ **3.1.2 ImportWizard Component**
    - ❌ 3.1.2.1 Modal z 3 trybami importu
    - ❌ 3.1.2.2 Tryb A: Paste SKU (textarea)
    - ❌ 3.1.2.3 Tryb B: Paste SKU+Name (textarea)
    - ❌ 3.1.2.4 Tryb C: Upload CSV/Excel (file input)
    - ❌ 3.1.2.5 Column mapping interface (dropdown dla każdej kolumny)
    - ❌ 3.1.2.6 Preview 10 rows przed confirm
    - ❌ 3.1.2.7 Error display (duplicates, invalid format)
    - ❌ 3.1.2.8 Import progress bar

- ❌ **3.2 Blade Views**
  - ❌ **3.2.1 pending-products-list.blade.php**
    - ❌ 3.2.1.1 Header z buttonem "Nowy Import"
    - ❌ 3.2.1.2 Bulk actions dropdown
    - ❌ 3.2.1.3 Filters sidebar (collapsed by default)
    - ❌ 3.2.1.4 Table layout z wszystkimi kolumnami
    - ❌ 3.2.1.5 Empty state (gdy brak produktów)
    - ❌ 3.2.1.6 Footer z pagination i stats

  - ❌ **3.2.2 import-wizard.blade.php**
    - ❌ 3.2.2.1 Modal dialog z steps
    - ❌ 3.2.2.2 Step 1: Wybór trybu importu (3 kafelki)
    - ❌ 3.2.2.3 Step 2: Input (textarea lub file upload)
    - ❌ 3.2.2.4 Step 3: Mapping (jeśli CSV/Excel)
    - ❌ 3.2.2.5 Step 4: Preview (tabela)
    - ❌ 3.2.2.6 Step 5: Confirmation (stats + errors)

- ❌ **3.3 Controllers**
  - ❌ **3.3.1 PendingProductsController**
    - ❌ 3.3.1.1 index() - główna strona panelu importu
    - ❌ 3.3.1.2 Authorization: role:admin|manager|editor

- ❌ **3.4 CSS Styling**
  - ❌ **3.4.1 import-panel.css**
    - ❌ 3.4.1.1 Table styling zgodny z PPM Playbook
    - ❌ 3.4.1.2 Progress bar animations
    - ❌ 3.4.1.3 Status badges (✅, ❌, 🛠️)
    - ❌ 3.4.1.4 Modal styling
    - ❌ 3.4.1.5 Inline editing states (focus, blur)
    - ❌ 3.4.1.6 Responsive dla tablet/mobile

- ❌ **3.5 Routing**
  - ❌ 3.5.1 Route: /admin/import/products (GET) → PendingProductsController@index
  - ❌ 3.5.2 Navigation menu: dodanie "Import Produktów" w Admin section

**🎯 Kryteria akceptacji FAZY 3:**
- ✅ Panel importu dostępny pod /admin/import/products
- ✅ Lista wyświetla pending products z completion %
- ✅ Inline editing działa (SKU, Nazwa, Typ)
- ✅ Modal importu otwiera się bez błędów
- ✅ 3 tryby importu zaimplementowane
- ✅ Preview wyświetla pierwsze 10 wierszy
- ✅ Styling zgodny z PPM Playbook
- ✅ Responsive na różnych rozdzielczościach

---

### ❌ **FAZA 4: EDYCJA & AKCJE MASOWE (14h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Modal edycji produktu + bulk actions

- ❌ **4.1 Livewire Components**
  - ❌ **4.1.1 PendingProductForm Component**
    - ❌ 4.1.1.1 Modal z tab navigation (6 tabs)
    - ❌ 4.1.1.2 Tab 1: Podstawowe (SKU, Nazwa, Typ, Producent, EAN)
    - ❌ 4.1.1.3 Tab 2: Kategorie (CategoryTree picker)
    - ❌ 4.1.1.4 Tab 3: Warianty (button → ProductForm integration)
    - ❌ 4.1.1.5 Tab 4: Cechy/Dopasowania (button → CompatibilityManagement)
    - ❌ 4.1.1.6 Tab 5: Zdjęcia (drag&drop upload, Livewire File Upload)
    - ❌ 4.1.1.7 Tab 6: Sklepy (tile selector z checkboxes)
    - ❌ 4.1.1.8 Footer: Zapisz, Zapisz i Publikuj buttons
    - ❌ 4.1.1.9 Real-time completion % display

  - ❌ **4.1.2 BulkActions Trait/Component**
    - ❌ 4.1.2.1 Zaznacz wszystkie / Odznacz wszystkie
    - ❌ 4.1.2.2 Bulk action: Przypisz kategorie (modal CategoryTree)
    - ❌ 4.1.2.3 Bulk action: Dodaj prefix do SKU (modal input)
    - ❌ 4.1.2.4 Bulk action: Dodaj suffix do SKU (modal input)
    - ❌ 4.1.2.5 Bulk action: Dodaj prefix do Nazwy (modal input)
    - ❌ 4.1.2.6 Bulk action: Ustaw typ produktu (dropdown)
    - ❌ 4.1.2.7 Bulk action: Wybierz sklepy (tile selector)
    - ❌ 4.1.2.8 Bulk action: Publikuj zaznaczone (confirmation)
    - ❌ 4.1.2.9 Bulk action: Usuń zaznaczone (confirmation)

- ❌ **4.2 Integracje**
  - ❌ **4.2.1 CategoryTree Integration**
    - ❌ 4.2.1.1 Embed CategoryTree jako picker w modal (Tab 2)
    - ❌ 4.2.1.2 Livewire event: categories-selected
    - ❌ 4.2.1.3 Max 10 kategorii per produkt
    - ❌ 4.2.1.4 Validation: tylko L3-L7 kategorie
    - ❌ 4.2.1.5 Display: breadcrumbs dla wybranych kategorii

  - ❌ **4.2.2 Temporary Media Upload**
    - ❌ 4.2.2.1 Livewire File Upload (wire:model="tempMedia")
    - ❌ 4.2.2.2 Store uploads w storage/app/tmp/{uuid}/
    - ❌ 4.2.2.3 Image preview thumbnails
    - ❌ 4.2.2.4 Drag to reorder (primary image selection)
    - ❌ 4.2.2.5 Delete individual images
    - ❌ 4.2.2.6 Max 20 images per product

  - ❌ **4.2.3 ProductForm Integration (Warianty)**
    - ❌ 4.2.3.1 Button "Edytuj warianty" dispatches event
    - ❌ 4.2.3.2 ProductForm opens w modal mode
    - ❌ 4.2.3.3 Load pending product data → ProductForm state
    - ❌ 4.2.3.4 Save warianty → PendingProduct.variant_data (JSON)
    - ❌ 4.2.3.5 Close ProductForm → return to PendingProductForm

  - ❌ **4.2.4 CompatibilityManagement Integration**
    - ❌ 4.2.4.1 Button "Zarządzaj dopasowaniami" redirects
    - ❌ 4.2.4.2 CompatibilityManagement?context=pending_product&id=X
    - ❌ 4.2.4.3 Load compatibilities from PendingProduct
    - ❌ 4.2.4.4 Save compatibilities → PendingProduct.compatibility_data (JSON)
    - ❌ 4.2.4.5 Redirect back → Import Panel

- ❌ **4.3 Blade Views**
  - ❌ **4.3.1 pending-product-form.blade.php**
    - ❌ 4.3.1.1 Modal dialog (max-width: 4xl)
    - ❌ 4.3.1.2 Tab navigation (Alpine.js x-data)
    - ❌ 4.3.1.3 Tab panels z dynamic content
    - ❌ 4.3.1.4 Footer buttons z validation states

  - ❌ **4.3.2 bulk-actions-dropdown.blade.php**
    - ❌ 4.3.2.1 Dropdown menu z wszystkimi akcjami
    - ❌ 4.3.2.2 Disabled state (gdy brak zaznaczonych)
    - ❌ 4.3.2.3 Icons dla każdej akcji

  - ❌ **4.3.3 bulk-action-modals.blade.php**
    - ❌ 4.3.3.1 Modal: Przypisz kategorie
    - ❌ 4.3.3.2 Modal: Dodaj prefix/suffix
    - ❌ 4.3.3.3 Modal: Confirmation dla Publikuj
    - ❌ 4.3.3.4 Modal: Confirmation dla Usuń

**🎯 Kryteria akceptacji FAZY 4:**
- ✅ Modal edycji otwiera się poprawnie
- ✅ Wszystkie 6 tabs działają
- ✅ CategoryTree picker pozwala wybrać kategorie
- ✅ Temporary media upload działa (drag&drop)
- ✅ Integration z ProductForm dla wariantów
- ✅ Integration z CompatibilityManagement dla dopasowań
- ✅ Bulk actions działają dla zaznaczonych produktów
- ✅ Validation przed akcjami masowymi

---

### ❌ **FAZA 5: PUBLIKACJA (12h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** PublishService + workflow DRAFT → PUBLISHED

- ❌ **5.1 Services Layer**
  - ❌ **5.1.1 PublishService**
    - ❌ 5.1.1.1 publish(PendingProduct $product, array $options) - single publish
    - ❌ 5.1.1.2 bulkPublish(array $productIds, array $options) - bulk publish
    - ❌ 5.1.1.3 validateBeforePublish() - pre-publish validation
    - ❌ 5.1.1.4 createProduct() - convert PendingProduct → Product
    - ❌ 5.1.1.5 syncCategories() - product_categories pivot
    - ❌ 5.1.1.6 moveMedia() - tmp/ → storage/app/public/products/{sku}/
    - ❌ 5.1.1.7 createProductShopData() - per shop tracking
    - ❌ 5.1.1.8 markAsPublished() - update PendingProduct
    - ❌ 5.1.1.9 createPublishHistory() - audit trail
    - ❌ 5.1.1.10 dispatchSyncJobs() - BulkSyncProducts dla każdego shop

- ❌ **5.2 Controllers**
  - ❌ **5.2.1 PublishController**
    - ❌ 5.2.1.1 single(PendingProduct $product) - publish one
    - ❌ 5.2.1.2 bulk(Request $request) - publish selected
    - ❌ 5.2.1.3 Validation rules (is_ready_for_publish)
    - ❌ 5.2.1.4 Authorization checks
    - ❌ 5.2.1.5 Flash messages z stats

- ❌ **5.3 Queue Jobs**
  - ❌ **5.3.1 PublishProductJob**
    - ❌ 5.3.1.1 Async publish dla bulk operations (>10 products)
    - ❌ 5.3.1.2 DB::transaction dla data integrity
    - ❌ 5.3.1.3 Progress tracking (JobProgressService)
    - ❌ 5.3.1.4 Error handling z retry logic
    - ❌ 5.3.1.5 Email notification po completion

- ❌ **5.4 Integration z BulkSyncProducts**
  - ❌ 5.4.1 Dispatch BulkSyncProducts per shop_id
  - ❌ 5.4.2 Store job UUIDs w PublishHistory.sync_jobs_dispatched
  - ❌ 5.4.3 Callback: update PublishHistory.sync_status
  - ❌ 5.4.4 Tracking: ProductShopData.sync_status per shop

- ❌ **5.5 Routes**
  - ❌ 5.5.1 POST /admin/import/products/{pendingProduct}/publish → PublishController@single
  - ❌ 5.5.2 POST /admin/import/products/publish-bulk → PublishController@bulk

- ❌ **5.6 Integration Tests**
  - ❌ **5.6.1 PublishServiceTest**
    - ❌ 5.6.1.1 test_publish_complete_product
    - ❌ 5.6.1.2 test_publish_creates_product_record
    - ❌ 5.6.1.3 test_publish_syncs_categories
    - ❌ 5.6.1.4 test_publish_moves_media_files
    - ❌ 5.6.1.5 test_publish_creates_product_shop_data
    - ❌ 5.6.1.6 test_publish_marks_pending_as_published
    - ❌ 5.6.1.7 test_publish_creates_history_record
    - ❌ 5.6.1.8 test_publish_dispatches_sync_jobs
    - ❌ 5.6.1.9 test_bulk_publish_multiple_products
    - ❌ 5.6.1.10 test_publish_fails_if_incomplete

**🎯 Kryteria akceptacji FAZY 5:**
- ✅ Single publish działa (<2s)
- ✅ Bulk publish działa (queue dla >10 produktów)
- ✅ Product record utworzony poprawnie
- ✅ Kategorie zsynchronizowane (product_categories)
- ✅ Media przeniesione do storage/products/{sku}/
- ✅ ProductShopData utworzone dla wszystkich wybranych sklepów
- ✅ PendingProduct oznaczony jako published
- ✅ PublishHistory record utworzony
- ✅ BulkSyncProducts jobs dispatched (per shop)
- ✅ DB transaction rollback w przypadku błędu

---

### ❌ **FAZA 6: HISTORIA & MONITORING (8h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Panel historii importów + sync status tracking

- ❌ **6.1 Livewire Components**
  - ❌ **6.1.1 PublishHistoryList Component**
    - ❌ 6.1.1.1 Tabela z opublikowanymi produktami
    - ❌ 6.1.1.2 Kolumny: Data, SKU, Nazwa, Sklepy, Sync Status
    - ❌ 6.1.1.3 Filtry: By date range, by user, by sync_status
    - ❌ 6.1.1.4 Sortowanie po wszystkich kolumnach
    - ❌ 6.1.1.5 Pagination (25, 50, 100)
    - ❌ 6.1.1.6 Row actions: Podgląd produktu, Podgląd JOB-ów sync

  - ❌ **6.1.2 SyncStatusTracker Component**
    - ❌ 6.1.2.1 Real-time status dla sync jobs
    - ❌ 6.1.2.2 Badge colors: pending (yellow), in_progress (blue), completed (green), failed (red)
    - ❌ 6.1.2.3 Tooltip z details (job UUID, error message)
    - ❌ 6.1.2.4 Livewire polling (every 5s dla in_progress)

- ❌ **6.2 Controllers**
  - ❌ **6.2.1 PublishHistoryController**
    - ❌ 6.2.1.1 index() - główna strona historii
    - ❌ 6.2.1.2 show(PublishHistory $history) - szczegóły publikacji
    - ❌ 6.2.1.3 Authorization: role:admin|manager

- ❌ **6.3 Blade Views**
  - ❌ **6.3.1 publish-history-list.blade.php**
    - ❌ 6.3.1.1 Header z filtami (date range picker)
    - ❌ 6.3.1.2 Table layout z sync status badges
    - ❌ 6.3.1.3 Empty state (gdy brak historii)
    - ❌ 6.3.1.4 Footer z pagination

  - ❌ **6.3.2 publish-history-show.blade.php**
    - ❌ 6.3.2.1 Szczegóły publikacji (data, user, stats)
    - ❌ 6.3.2.2 Lista sklepów z sync status per shop
    - ❌ 6.3.2.3 Lista kategorii przypisanych
    - ❌ 6.3.2.4 Link do opublikowanego produktu w ProductList

- ❌ **6.4 Webhook/Callback System**
  - ❌ **6.4.1 BulkSyncProducts Job Callback**
    - ❌ 6.4.1.1 Po zakończeniu sync job → update PublishHistory.sync_status
    - ❌ 6.4.1.2 Store sync errors w PublishHistory (jeśli failed)
    - ❌ 6.4.1.3 Email notification dla user (jeśli all jobs completed)

- ❌ **6.5 Routes**
  - ❌ 6.5.1 GET /admin/import/history → PublishHistoryController@index
  - ❌ 6.5.2 GET /admin/import/history/{history} → PublishHistoryController@show
  - ❌ 6.5.3 Navigation menu: dodanie "Historia Importu" w Import section

**🎯 Kryteria akceptacji FAZY 6:**
- ✅ Panel historii dostępny pod /admin/import/history
- ✅ Lista wyświetla wszystkie publikacje z datami
- ✅ Filtry działają (date range, user, sync_status)
- ✅ Sync status badges z real-time updates (Livewire polling)
- ✅ Szczegóły publikacji wyświetlają pełne info
- ✅ Callback z BulkSyncProducts aktualizuje sync_status
- ✅ Email notification po zakończeniu sync

---

### ❌ **FAZA 7: TESTING & DEPLOYMENT (10h)**

**Status:** ❌ NIEROZPOCZĘTA
**Cel:** Testy E2E + deployment na produkcję

- ❌ **7.1 Feature Tests**
  - ❌ **7.1.1 ImportWorkflowTest**
    - ❌ 7.1.1.1 test_import_sku_list_workflow (paste SKU → list → edit → publish)
    - ❌ 7.1.1.2 test_import_csv_workflow (upload CSV → map → preview → confirm)
    - ❌ 7.1.1.3 test_bulk_actions_workflow (select → bulk assign → publish)
    - ❌ 7.1.1.4 test_duplicate_sku_handling
    - ❌ 7.1.1.5 test_incomplete_product_cannot_publish
    - ❌ 7.1.1.6 test_publish_creates_product
    - ❌ 7.1.1.7 test_publish_dispatches_sync_jobs
    - ❌ 7.1.1.8 test_history_shows_published_products

- ❌ **7.2 Browser Tests (Dusk)**
  - ❌ **7.2.1 ImportPanelTest**
    - ❌ 7.2.1.1 test_open_import_wizard
    - ❌ 7.2.1.2 test_paste_sku_and_import
    - ❌ 7.2.1.3 test_inline_edit_sku_in_list
    - ❌ 7.2.1.4 test_open_edit_modal
    - ❌ 7.2.1.5 test_select_categories_in_modal
    - ❌ 7.2.1.6 test_upload_media_in_modal
    - ❌ 7.2.1.7 test_bulk_select_all
    - ❌ 7.2.1.8 test_bulk_assign_categories
    - ❌ 7.2.1.9 test_bulk_publish_products
    - ❌ 7.2.1.10 test_view_publish_history

- ❌ **7.3 Documentation**
  - ❌ **7.3.1 User Guide**
    - ❌ 7.3.1.1 Instrukcja: Jak zaimportować listę SKU
    - ❌ 7.3.1.2 Instrukcja: Jak zaimportować CSV/Excel
    - ❌ 7.3.1.3 Instrukcja: Jak uzupełnić dane produktu
    - ❌ 7.3.1.4 Instrukcja: Jak używać akcji masowych
    - ❌ 7.3.1.5 Instrukcja: Jak opublikować produkty
    - ❌ 7.3.1.6 Screenshots dla każdego kroku
    - ❌ 7.3.1.7 Video demo (10 min)

  - ❌ **7.3.2 Technical Documentation**
    - ❌ 7.3.2.1 Architecture overview (z diagramami)
    - ❌ 7.3.2.2 Database schema documentation
    - ❌ 7.3.2.3 API endpoints dla integracji
    - ❌ 7.3.2.4 Queue jobs documentation
    - ❌ 7.3.2.5 Troubleshooting guide

- ❌ **7.4 Deployment**
  - ❌ **7.4.1 Pre-Deployment**
    - ❌ 7.4.1.1 Run all tests (unit, feature, browser)
    - ❌ 7.4.1.2 Review migrations (dry-run)
    - ❌ 7.4.1.3 Backup production database
    - ❌ 7.4.1.4 Build assets (npm run build)
    - ❌ 7.4.1.5 Test na staging environment

  - ❌ **7.4.2 Deployment Steps**
    - ❌ 7.4.2.1 Upload migrations via pscp
    - ❌ 7.4.2.2 Run php artisan migrate (production)
    - ❌ 7.4.2.3 Upload services, controllers, models
    - ❌ 7.4.2.4 Upload Livewire components
    - ❌ 7.4.2.5 Upload views
    - ❌ 7.4.2.6 Upload CSS assets (import-panel.css)
    - ❌ 7.4.2.7 Clear cache (view, config, route)
    - ❌ 7.4.2.8 Restart queue workers

  - ❌ **7.4.3 Post-Deployment Verification**
    - ❌ 7.4.3.1 Smoke test: Open /admin/import/products
    - ❌ 7.4.3.2 Import test: Paste 5 SKUs
    - ❌ 7.4.3.3 Edit test: Open modal, edit product
    - ❌ 7.4.3.4 Publish test: Publish 1 product
    - ❌ 7.4.3.5 History test: View publish history
    - ❌ 7.4.3.6 Check logs dla errors
    - ❌ 7.4.3.7 Monitor queue jobs

- ❌ **7.5 User Training**
  - ❌ 7.5.1 Demo video recording (10 min)
  - ❌ 7.5.2 Live demo dla key users (Admin, Managers)
  - ❌ 7.5.3 Q&A session
  - ❌ 7.5.4 Feedback collection

**🎯 Kryteria akceptacji FAZY 7:**
- ✅ All tests passing (unit, feature, browser)
- ✅ Documentation complete (user guide + technical)
- ✅ Deployment na produkcję successful
- ✅ Post-deployment smoke tests passed
- ✅ No critical errors w logs
- ✅ Queue workers działają poprawnie
- ✅ User training completed
- ✅ Video demo ready

---

## ✅ CRITERIA AKCEPTACJI ETAPU

Etap uznajemy za ukończony gdy:

1. **Import System:**
   - ✅ 3 tryby importu działają (paste SKU, paste SKU+Name, CSV/Excel)
   - ✅ Auto-detect separator działa
   - ✅ Conflict detection wykrywa duplikaty SKU
   - ✅ Import 100 SKU: <3s parsing, <10s create PendingProducts

2. **Panel Importu:**
   - ✅ Lista pending products z completion % (real-time)
   - ✅ Inline editing (SKU, Nazwa, Typ)
   - ✅ Filtry i sortowanie działają
   - ✅ Pagination poprawnie obsługuje duże zbiory danych

3. **Edycja & Akcje Masowe:**
   - ✅ Modal edycji z 6 tabs
   - ✅ CategoryTree picker dla kategorii
   - ✅ Temporary media upload (drag&drop)
   - ✅ Integration z ProductForm (warianty)
   - ✅ Integration z CompatibilityManagement (dopasowania)
   - ✅ 9 bulk actions zaimplementowanych

4. **Publikacja:**
   - ✅ Single publish: <2s
   - ✅ Bulk publish: queue dla >10 produktów
   - ✅ Product record utworzony poprawnie
   - ✅ Kategorie, media, shop data zsynchronizowane
   - ✅ BulkSyncProducts jobs dispatched
   - ✅ DB transaction rollback w przypadku błędu

5. **Historia & Monitoring:**
   - ✅ Panel historii z filtowaniem
   - ✅ Sync status tracking (real-time)
   - ✅ Email notifications po zakończeniu sync
   - ✅ Audit trail dla wszystkich publikacji

6. **Testing & Deployment:**
   - ✅ 100% critical paths covered
   - ✅ Browser tests dla UI interactions
   - ✅ Documentation complete
   - ✅ Deployment successful na produkcję
   - ✅ User training completed

---

## 🚨 POTENCJALNE PROBLEMY I ROZWIĄZANIA

### Problem 1: Konflikt SKU między PendingProduct a Product
**Mitygacja:**
- ValidationService.checkSkuConflict() PRZED utworzeniem PendingProduct
- Flash warning: "SKU-XXX już istnieje w produktach. Pominięto."
- Opcja "Nadpisz istniejący" (Admin only)

### Problem 2: Temporary media cleanup
**Mitygacja:**
- Scheduled job: CleanupTempMediaJob (daily)
- Delete temp files starsze niż 7 dni bez powiązanego PendingProduct
- Soft delete PendingProduct → cascade delete temp media

### Problem 3: Bulk publish timeout
**Mitygacja:**
- Limit: max 100 produktów per bulk action
- Queue job: PublishProductJob dla każdego produktu
- Progress tracking w UI (Livewire polling)
- Email notification po completion

### Problem 4: Kategorie per-shop conflict
**Mitygacja:**
- PendingProduct.shop_categories JSON: `{shop_id: [category_ids]}`
- Default categories stosowane jeśli brak shop-specific
- UI: Per-shop override w modal (optional)

### Problem 5: Warianty w DRAFT mode
**Mitygacja:**
- Store variants jako JSON w PendingProduct.variant_data
- Po publikacji: convert JSON → ProductVariant records
- Format JSON: `{variants: [{sku, name, attributes}, ...]}`

---

## 📊 METRYKI SUKCESU ETAPU

**Performance:**
- Import 100 SKU: <3s parsing
- Create 100 PendingProducts: <10s DB bulk insert
- Publish 1 produkt: <2s transaction
- Bulk publish 50: <30s queue

**UX:**
- Completion % visible: <100ms cached
- Inline editing: <500ms Livewire
- Modal load: <300ms

**Business:**
- 80% produktów publikowanych <5 min od importu
- <5% konfliktów SKU
- 100% audit trail

---

## 🔄 PRZYGOTOWANIE DO NASTĘPNEGO ETAPU

Po ukończeniu ETAP_09 będziemy mieli:
- **Kompletny system importu produktów** do PPM
- **Separację DRAFT vs PUBLISHED** - ProductList pozostaje clean
- **3 sposoby importu** (paste, CSV, Excel)
- **Workflow publikacji** z queue jobs
- **Historia publikacji** z sync tracking
- **Integracje** z ProductForm, CategoryTree, CompatibilityManagement

**Następny etap:** ETAP_10_Dashboard_Analytics.md - dashboard analytics i raporty dla management

---

**KONIEC PLANU ETAP_09**
