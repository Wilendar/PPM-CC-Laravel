# ETAP_06: System Importu Produktow do PPM

## INSTRUKCJE PRZED ROZPOCZECIEM ETAPU

**OBOWIAZKOWE KROKI:**
1. **Przeanalizuj dokumentacje struktury:** Przeczytaj `_DOCS/Struktura_Plikow_Projektu.md` i `_DOCS/Struktura_Bazy_Danych.md`
2. **Sprawdz aktualny stan:** Porownaj obecna strukture plikow z planem w tym ETAP
3. **Zidentyfikuj nowe komponenty:** Lista plikow/tabel/modeli do utworzenia w tym ETAP
4. **Zaktualizuj dokumentacje:** Dodaj planowane komponenty z statusem do dokumentacji struktury

---

## PODSUMOWANIE ETAPU

**Cel:** Zbudowac dedykowany panel do masowego importu produktow DO aplikacji PPM z workflow "pending → complete → publish → PrestaShop".

**Kluczowa roznica vs istniejacy ImportManager:**
- ImportManager (`/admin/shops/import`) = import Z PrestaShop DO PPM
- **NOWY SYSTEM** (`/admin/products/import`) = tworzenie produktow W PPM → eksport NA PrestaShop

**Status ETAPU:** 🛠️ W TRAKCIE - FAZA 1-6.5, 9 ukonczone (95%)
**Szacowany czas:** 50 godzin (35h zuzyte)
**Priorytet:** WYSOKI
**Zaleznosci:** ETAP_05_Produkty.md, ETAP_07_Prestashop_API.md (sync jobs)

---

## ARCHITEKTURA SYSTEMU

### Koncepcja "Pending Products"

```
[USER INPUT]                    [PENDING PRODUCTS PANEL]              [PRODUCTION]

SKU Paste ──────┐
                ├─► PendingProduct ─► Uzupelnianie ─► Publikacja ──► Product
CSV/Excel ──────┤       (DB)          danych w         (batch)        (ProductList)
                │                     panelu UI                          │
ERP (future) ───┘                                                        │
                                                                         ▼
                                                              SyncProductToPrestaShop
                                                              (per sklep, kolejka)
```

### Tabele Bazy Danych (nowe)

```
pending_products              import_sessions               pending_product_images
─────────────────            ──────────────────            ────────────────────────
id                           id                            id
import_session_id (FK)       user_id (FK)                  pending_product_id (FK)
sku (unique per session)     source_type                   media_id (FK nullable)
name                         status (active/completed)     temp_path (for uploads)
product_type_id (FK)         total_items                   is_primary
category_l3_id               processed_items               sort_order
category_l4_id               created_at                    created_at
category_l5_id               completed_at
category_l6_id
category_l7_id
status (incomplete/complete/published)
validation_errors (JSON)
shop_ids (JSON array)
compatibility_ids (JSON array)
feature_template_id (nullable)
custom_features (JSON)
variant_data (JSON)
created_at
updated_at
```

### Komponenty Livewire (nowe)

```
app/Http/Livewire/Admin/Import/
├── ProductImportPanel.php       # Glowny panel z lista pending products
├── Modals/
│   ├── SKUPasteModal.php        # Modal wklejania SKU
│   ├── CSVImportModal.php       # Modal importu CSV/Excel
│   ├── VariantModal.php         # Modal tworzenia wariantow
│   ├── FeatureTemplateModal.php # Modal wyboru szablonu cech
│   └── CompatibilityModal.php   # Modal przypisywania dopasowań
├── Components/
│   ├── PendingProductRow.php    # Wiersz produktu do edycji inline
│   ├── CategoryPickerL3L7.php   # Hierarchiczny picker kategorii
│   ├── ShopTiles.php            # Kafelki wyboru sklepow
│   └── ImportProgressBar.php    # Pasek postepu publikacji
└── ImportHistory.php            # Historia zakonczonych importow
```

### Serwisy (nowe)

```
app/Services/Import/
├── SKUParserService.php         # Parsowanie wklejonej listy SKU
├── CSVParserService.php         # Parsowanie plikow CSV/Excel
├── PendingProductService.php    # CRUD dla pending products
├── ProductPublicationService.php # Publikacja: Pending → Product → Sync
└── ValidationService.php        # Walidacja kompletnosci produktu
```

---

## PLAN RAMOWY FAZ

- ✅ FAZA 1. INFRASTRUKTURA BAZY DANYCH I MODELE
- ✅ FAZA 2. PANEL IMPORTU - CORE UI
- ✅ FAZA 3. IMPORT SKU (WKLEJANIE LISTY)
- ✅ FAZA 3.5. ROZBUDOWA MODALU SKU (DWUKOLUMNOWY TRYB + INTELIGENTNE SEPARATORY)
- ✅ FAZA 4. IMPORT CSV/EXCEL
- ✅ FAZA 5. EDYCJA INLINE W PANELU (podstawowy zakres ukończony)
- ✅ FAZA 6. SYSTEM PUBLIKACJI (6.1, 6.3, 6.4 ukończone)
- ✅ FAZA 6.5. ROZBUDOWA PANELU - NOWE KOLUMNY I FUNKCJE (resizable columns, marka, cena, opisy)
- FAZA 7. HISTORIA I AUDYT
- FAZA 8. TESTOWANIE I OPTYMALIZACJA
- ✅ FAZA 9. REDESIGN PANELU IMPORTU (zunifikowany modal, modal cen, publikacja, scheduler, uprawnienia)

---

## SZCZEGOLOWY PLAN ZADAN

### ✅ FAZA 1: INFRASTRUKTURA BAZY DANYCH I MODELE (UKONCZONA 2025-12-08)

- **✅ 1.1 Migracje Bazy Danych**
  - ✅ 1.1.1 Migracja create_import_sessions_table
    └── 📁 PLIK: database/migrations/2025_12_08_102551_create_import_sessions_table.php
    - ✅ 1.1.1.1 Kolumny: uuid, session_name, import_method (enum), status (enum), statistics counters
    - ✅ 1.1.1.2 Indeksy: status, import_method, imported_by, created_at
    - ✅ 1.1.1.3 Foreign key do users table (imported_by)
  - ✅ 1.1.2 Migracja create_pending_products_table
    └── 📁 PLIK: database/migrations/2025_12_08_102552_create_pending_products_table.php
    - ✅ 1.1.2.1 Kolumny podstawowe: id, sku, name, slug, product_type_id, manufacturer, supplier_code, ean
    - ✅ 1.1.2.2 Kolumny kategorii: category_ids (JSON) - hierarchiczna struktura
    - ✅ 1.1.2.3 Kolumny statusu: completion_status (JSON), completion_percentage, is_ready_for_publish
    - ✅ 1.1.2.4 Kolumny relacji: shop_ids (JSON), shop_categories (JSON per shop override)
    - ✅ 1.1.2.5 Kolumny danych: variant_data (JSON), compatibility_data (JSON), feature_data (JSON)
    - ✅ 1.1.2.6 Indeksy: unique_sku_per_session (sku + import_session_id), status indexes
    - ✅ 1.1.2.7 Soft deletes + publication tracking (published_at, published_as_product_id)
  - ✅ 1.1.3 Migracja create_publish_history_table (NOWA - z architect)
    └── 📁 PLIK: database/migrations/2025_12_08_102553_create_publish_history_table.php
    - ✅ 1.1.3.1 Audit trail: pending_product_id, product_id, published_by, published_at
    - ✅ 1.1.3.2 Snapshots: sku_snapshot, name_snapshot, published_shops, published_categories
    - ✅ 1.1.3.3 Sync tracking: sync_jobs_dispatched (JSON), sync_status (enum), sync_completed_at
    - ✅ 1.1.3.4 Batch support: batch_id (UUID), publish_mode (single/bulk)

- **✅ 1.2 Modele Eloquent**
  - ✅ 1.2.1 Model ImportSession
    └── 📁 PLIK: app/Models/ImportSession.php
    - ✅ 1.2.1.1 Fillable fields i casts (import_method enum, status enum, JSON arrays)
    - ✅ 1.2.1.2 Relacja hasMany do PendingProduct
    - ✅ 1.2.1.3 Relacja belongsTo do User (imported_by)
    - ✅ 1.2.1.4 Scopes: active(), completed(), failed(), byMethod(), byUser(), recent()
    - ✅ 1.2.1.5 Metody: markAsParsing(), markAsReady(), markAsCompleted(), markAsFailed(), addError()
    - ✅ 1.2.1.6 Accessors: method_label, status_label, status_color
    - ✅ 1.2.1.7 Helpers: isInProgress(), isCompleted(), isFailed(), getStats(), getAverageCompletion()
  - ✅ 1.2.2 Model PendingProduct
    └── 📁 PLIK: app/Models/PendingProduct.php
    - ✅ 1.2.2.1 Constants: REQUIRED_FIELDS (sku, name, category_ids, product_type_id, shop_ids), OPTIONAL_FIELDS
    - ✅ 1.2.2.2 Relacja belongsTo do ImportSession, ProductType, User (imported_by), Product (published_as)
    - ✅ 1.2.2.3 JSON accessors: getCategories(), getShops(), getShopCategories(), etc.
    - ✅ 1.2.2.4 Auto-completion calculation on save via boot() hook
    - ✅ 1.2.2.5 Method: recalculateCompletion() - weighted system (80% required, 20% optional)
    - ✅ 1.2.2.6 Method: getPublishValidationErrors() - lista brakujacych pol
    - ✅ 1.2.2.7 Method: markAsPublished($productId) - zmiana statusu
    - ✅ 1.2.2.8 Scopes: readyForPublish(), incomplete(), unpublished(), published(), bySession(), byUser(), hasImages()
  - ✅ 1.2.3 Model PublishHistory (NOWY - z architect)
    └── 📁 PLIK: app/Models/PublishHistory.php
    - ✅ 1.2.3.1 Constants: SYNC_PENDING/IN_PROGRESS/COMPLETED/PARTIAL/FAILED, MODE_SINGLE/BULK
    - ✅ 1.2.3.2 Relacje: belongsTo PendingProduct (withTrashed), Product, User (publisher)
    - ✅ 1.2.3.3 Sync management: markSyncInProgress(), markSyncCompleted(), markSyncFailed(), addSyncJob()
    - ✅ 1.2.3.4 Factory methods: createForSinglePublish(), createForBulkPublish(), generateBatchId()
    - ✅ 1.2.3.5 Statistics: getBatchStats(), getDailyStats()
    - ✅ 1.2.3.6 Accessors: sync_status_label, sync_status_color, publish_mode_label, processing_time_formatted

- **❌ 1.3 Serwisy Podstawowe** (przesunięte do FAZY 3-6)
  - ❌ 1.3.1 PendingProductService → FAZA 3 (import SKU)
  - ❌ 1.3.2 ValidationService → FAZA 3 (import SKU)
  - ❌ 1.3.3 ProductPublicationService → FAZA 6 (publikacja)

**UWAGA:** Zgodnie z architecture report, serwisy są tworzone w kontekście konkretnych funkcjonalności, nie osobno.
Modele zawierają już podstawową logikę walidacji i completion tracking.

---

### ✅ FAZA 2: PANEL IMPORTU - CORE UI (UKONCZONA 2025-12-08)

- **✅ 2.1 Routing i Layout**
  - ✅ 2.1.1 Route /admin/products/import
    └── 📁 PLIK: routes/web.php (linia 425)
    - ✅ 2.1.1.1 Dodanie do routes/web.php z middleware auth
    - ✅ 2.1.1.2 Nazwa route: admin.products.import
  - ✅ 2.1.2 Integracja z menu admina
    └── 📁 PLIK: resources/views/layouts/admin.blade.php (linie 304-313)
    - ✅ 2.1.2.1 Link w sidebarze Produkty → Import
    - ❌ 2.1.2.2 Badge z liczba pending products (przesunięte do FAZY 3)
  - ✅ 2.1.3 Layout strony
    └── 📁 PLIK: resources/views/pages/product-import.blade.php
    - ✅ 2.1.3.1 Header z tytułem "Import Produktów"
    - ✅ 2.1.3.2 Toolbar z przyciskami akcji (Wklej SKU, Import CSV, Publikuj)
    - ✅ 2.1.3.3 Filtry: status, typ produktu, sesja importu
    - ✅ 2.1.3.4 Obszar tabeli pending products

- **✅ 2.2 Komponent ProductImportPanel (z traits)**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/ProductImportPanel.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelFilters.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelActions.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelBulkOperations.php
  - ✅ 2.2.1 Properties Livewire
    - ✅ 2.2.1.1 $pendingProducts - kolekcja z paginacja (computed)
    - ✅ 2.2.1.2 $selectedIds - zaznaczone produkty (array)
    - ✅ 2.2.1.3 $filters - status, product_type, session_id (#[Url])
    - ✅ 2.2.1.4 $sortField, $sortDirection
    - ✅ 2.2.1.5 $activeSession - aktualna sesja importu
  - ✅ 2.2.2 Metody akcji (ImportPanelBulkOperations trait)
    - ✅ 2.2.2.1 openSKUPasteModal() - otwiera modal wklejania
    - ✅ 2.2.2.2 openCSVImportModal() - otwiera modal CSV
    - ✅ 2.2.2.3 selectAll() / deselectAll() - zaznaczanie
    - ✅ 2.2.2.4 bulkDelete() - usuwanie zaznaczonych
    - ✅ 2.2.2.5 bulkSetCategory() - masowa zmiana kategorii
    - ✅ 2.2.2.6 bulkSetType() - masowa zmiana typu
    - ✅ 2.2.2.7 bulkSetShops() - masowy wybor sklepow
    - ✅ 2.2.2.8 publishSelected() - publikacja zaznaczonych
  - ✅ 2.2.3 View blade
    └── 📁 PLIK: resources/views/livewire/products/import/product-import-panel.blade.php
    - ✅ 2.2.3.1 Toolbar z przyciskami akcji masowych
    - ✅ 2.2.3.2 Tabela z kolumnami wg specyfikacji uzytkownika
    - ✅ 2.2.3.3 Paginacja z wyborem ilosci na stronie
    - ✅ 2.2.3.4 Empty state dla braku produktow

- **✅ 2.3 Tabela Pending Products**
  └── 📁 PLIK: resources/views/livewire/products/import/partials/product-row.blade.php
  └── 📁 PLIK: resources/views/livewire/products/import/partials/sort-indicator.blade.php
  - ✅ 2.3.1 Kolumny tabeli (zgodne z wymaganiami)
    - ✅ 2.3.1.1 Checkbox zaznaczenia
    - ✅ 2.3.1.2 Miniaturka zdjecia glownego
    - ✅ 2.3.1.3 SKU (edytowalny inline)
    - ✅ 2.3.1.4 Nazwa (edytowalna inline)
    - ✅ 2.3.1.5 Typ produktu (dropdown)
    - ✅ 2.3.1.6 Kategorie (badge z licznikiem)
    - ❌ 2.3.1.7 Master/Wariant (przesunięte do FAZY 5)
    - ❌ 2.3.1.8 Cechy/Dopasowania (przesunięte do FAZY 5)
    - ✅ 2.3.1.9 Sklepy (badge z licznikiem)
    - ✅ 2.3.1.10 Status gotowosci (ikona check/warning + procent)
    - ✅ 2.3.1.11 Akcje (edytuj, duplikuj, publikuj, usun)
  - ✅ 2.3.2 Inline editing (ImportPanelActions trait)
    - ✅ 2.3.2.1 Klikniecie w pole → edycja z autosave
    - ✅ 2.3.2.2 Walidacja w czasie rzeczywistym
    - ✅ 2.3.2.3 Feedback wizualny (zielony = OK, czerwony = blad)
  - ✅ 2.3.3 Sortowanie i filtrowanie (ImportPanelFilters trait)
    - ✅ 2.3.3.1 Sortowanie po: SKU, nazwa, status, data utworzenia
    - ✅ 2.3.3.2 Filtrowanie po: status, typ produktu, sesja

- **✅ 2.4 Style CSS**
  └── 📁 PLIK: resources/css/admin/components.css
  - ✅ 2.4.1 btn-enterprise-danger, btn-enterprise-success, btn-enterprise-ghost
  - ✅ 2.4.2 form-input-dark-sm, form-select-dark-sm
  - ✅ 2.4.3 form-checkbox-dark

---

### ✅ FAZA 3: IMPORT SKU (WKLEJANIE LISTY) - UKONCZONA 2025-12-08

- **✅ 3.1 Modal SKUPasteModal**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/SKUPasteModal.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/sku-paste-modal.blade.php
  - ✅ 3.1.1 Layout modala
    - ✅ 3.1.1.1 Tytul "Wklej liste SKU"
    - ✅ 3.1.1.2 Textarea na wklejenie danych (wire:model.live z debounce)
    - ✅ 3.1.1.3 Radio buttons: "Tylko SKU" / "SKU + Nazwa"
    - ✅ 3.1.1.4 Dropdown wyboru separatora (auto/srednik/przecinek/tab/pipe)
    - ✅ 3.1.1.5 Preview rozpoznanych danych (tabela z #, SKU, Status)
    - ✅ 3.1.1.6 Przyciski: Anuluj, Importuj X pozycji
    - ✅ 3.1.1.7 Sekcje Bledy (czerwona) i Ostrzezenia (zolta) z detalami
    - ✅ 3.1.1.8 Statystyki: Linii, Poprawnych, Duplikatow (badges)
  - ✅ 3.1.2 Properties
    - ✅ 3.1.2.1 $rawInput - surowe dane z textarea
    - ✅ 3.1.2.2 $importMode - 'sku_only' lub 'sku_name'
    - ✅ 3.1.2.3 $separator - 'auto' lub konkretny separator
    - ✅ 3.1.2.4 $parsedResult - wynik parsowania (items, errors, warnings, stats)
    - ✅ 3.1.2.5 $isProcessing - stan ladowania
  - ✅ 3.1.3 Metody
    - ✅ 3.1.3.1 parseInput() - real-time parsing via wire:model.live
    - ✅ 3.1.3.2 import() - tworzenie PendingProduct rekordow
    - ✅ 3.1.3.3 closeModal() - zamykanie z dispatchem 'modal-closed'

- **✅ 3.2 SkuParserService**
  └── 📁 PLIK: app/Services/Import/SkuParserService.php
  - ✅ 3.2.1 Metody parsowania
    - ✅ 3.2.1.1 parse($input, $mode, $separator) - glowna metoda (zwraca items/errors/warnings/stats)
    - ✅ 3.2.1.2 detectSeparator($input) - heurystyka separatora (tab > ; > , > |)
    - ✅ 3.2.1.3 splitLines($input) - podzial na linie (normalizacja \r\n)
    - ✅ 3.2.1.4 parseSkuOnly($lines) - parsowanie tylko SKU
    - ✅ 3.2.1.5 parseSkuName($lines, $separator) - parsowanie SKU + nazwa
  - ✅ 3.2.2 Walidacja
    - ✅ 3.2.2.1 validateSKUFormat($sku) - format SKU (min 2, max 64, alfanumeryczny + - _ . /)
    - ✅ 3.2.2.2 checkDuplicatesInBatch($skus) - duplikaty w importowanej partii
    - ✅ 3.2.2.3 checkExistingInPPM($skus) - duplikaty w bazie PPM (Product)
    - ✅ 3.2.2.4 checkExistingInPending($skus, $sessionId) - duplikaty w pending
    - ✅ 3.2.2.5 validateAgainstExisting($skus, $sessionId) - pelna walidacja
  - ✅ 3.2.3 Output
    - ✅ 3.2.3.1 Zwracanie struktury: ['items' => [...], 'errors' => [...], 'warnings' => [...], 'stats' => [...]]
    - ✅ 3.2.3.2 Item structure: ['sku' => '...', 'name' => '...', 'line' => X]
    - ✅ 3.2.3.3 Stats: total_lines, valid_items, skipped_empty, duplicates_in_batch
  - ✅ 3.2.4 Helpers
    - ✅ 3.2.4.1 extractSample() - pierwsze 5 SKU dla preview
    - ✅ 3.2.4.2 groupByStatus() - grupowanie po statusie (valid/duplicates/invalid/existing)
    - ✅ 3.2.4.3 convertToPendingProducts() - konwersja do formatu PendingProduct

- **✅ 3.3 Workflow importu SKU**
  - ✅ 3.3.1 User Flow (ZWERYFIKOWANY Chrome DevTools MCP)
    - ✅ 3.3.1.1 Klik "Wklej SKU" → otwiera modal (wire:click="openModal('sku-paste')")
    - ✅ 3.3.1.2 Wklejenie danych → automatyczny parsing (real-time)
    - ✅ 3.3.1.3 Preview pokazuje rozpoznane SKU + bledy + ostrzezenia
    - ✅ 3.3.1.4 Klik "Importuj" → tworzenie rekordow PendingProduct
    - ✅ 3.3.1.5 Modal zamyka sie → dispatch 'products-imported' → refresh tabeli
  - ✅ 3.3.2 Error Handling
    - ✅ 3.3.2.1 Duplikaty w partii - warning zolty z liniami gdzie wystepuja
    - ✅ 3.3.2.2 Nieprawidlowy format SKU - error czerwony, nie importuj linii
    - ✅ 3.3.2.3 Puste linie - ignoruj bez bledu (skipped_empty w stats)
    - ✅ 3.3.2.4 Usuwanie duplikatow z listy (zachowaj pierwsze wystapienie)

- **✅ 3.4 Integracja z ProductImportPanel**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/ProductImportPanel.php (linie 75-85)
  └── 📁 PLIK: resources/views/livewire/products/import/product-import-panel.blade.php (linie 209-211)
  - ✅ 3.4.1 $activeModal property do kontroli widocznosci
  - ✅ 3.4.2 openModal('sku-paste') / closeModal() metody
  - ✅ 3.4.3 Conditional rendering @if($activeModal === 'sku-paste')
  - ✅ 3.4.4 Event listener 'products-imported' dla odswiezenia listy

**NAPRAWIONY BUG:** PSR-4 autoloading issue - plik `SKUParserService.php` przemianowany na `SkuParserService.php` dla zgodnosci z nazwa klasy na Linux (case-sensitive filesystem)

**NAPRAWIONE STYLOWANIE (2025-12-08):** Modal CSS fallbacks - dodano solid background fallback values (`#1f2937`, `#111827`) do CSS variables w `.modal-enterprise` dla opaque tla zgodnego z PPM_Styling_Playbook.md
  └── 📁 PLIK: resources/css/admin/components.css (linie 8383-8526)

---

### ✅ FAZA 3.5: ROZBUDOWA MODALU SKU - DWUKOLUMNOWY TRYB I INTELIGENTNE SEPARATORY (UKONCZONA 2025-12-08)

- **✅ 3.5.1 Rozbudowa SkuParserService - inteligentne separatory**
  └── 📁 PLIK: app/Services/Import/SkuParserService.php (enhanced, 736 lines)
  - ✅ 3.5.1.1 Metoda hasInlineSeparators($input) - wykrywanie separatorow inline (przecinki, sredniki, spacje)
  - ✅ 3.5.1.2 Metoda parseSkuOnlyMultiSeparator($lines) - parsowanie wielu separatorow w jednej linii (regex `/[\s,;]+/`)
  - ✅ 3.5.1.3 Metoda parseSkuOnlyIntelligent($lines, $separator) - router do odpowiedniej metody parsowania ('auto' | 'newline' | 'multi')
  - ✅ 3.5.1.4 Metoda parseTwoColumn($skuInput, $nameInput) - parsowanie dwoch niezaleznych list i parowanie 1-do-1
  - ✅ 3.5.1.5 Automatyczna detekcja formatu z pierwszych 10 linii

- **✅ 3.5.2 SKUPasteModal split na traits + dwukolumnowy widok**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/SKUPasteModal.php (refactored, 318 lines)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/Traits/SkuPasteParsingTrait.php (new, 280 lines)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/Traits/SkuPasteViewModeTrait.php (new, 178 lines)
  - ✅ 3.5.2.1 Nowe properties: $viewMode ('single_column' | 'two_columns'), $rawSkuInput, $rawNameInput, $separatorMode, $viewModeWarnings
  - ✅ 3.5.2.2 SkuPasteParsingTrait - logika parsowania (parseInput, parseInputTwoColumn, applyParseResults, resetParseResults)
  - ✅ 3.5.2.3 SkuPasteViewModeTrait - helpery widoku (getPlaceholderText, getSkuCount, getNameCount, hasCountMismatch, getCountMismatchMessage)
  - ✅ 3.5.2.4 updatedViewMode() - przelaczanie trybu z resetem danych

- **✅ 3.5.3 UI blade - dwukolumnowy layout**
  └── 📁 PLIK: resources/views/livewire/products/import/modals/sku-paste-modal.blade.php (enhanced)
  - ✅ 3.5.3.1 Toggle view mode: "Jedna kolumna" / "Dwie kolumny" (radio buttons, warunkowe dla SKU+Nazwa)
  - ✅ 3.5.3.2 Dwukolumnowy grid layout: lewa kolumna SKU (wymagane), prawa kolumna Nazwy (opcjonalne)
  - ✅ 3.5.3.3 Liczniki pozycji pod kazdym polem textarea (Pozycji: X)
  - ✅ 3.5.3.4 Ostrzezenie count mismatch (zolty alert gdy SKU count != Names count)
  - ✅ 3.5.3.5 Pomocniczy tekst: "Dwie kolumny: oddzielne pola dla SKU i nazw"

- **✅ 3.5.4 Integracja i weryfikacja**
  - ✅ 3.5.4.1 npm run build - kompilacja assets
  - ✅ 3.5.4.2 Deploy skrypt z 8 krokami (SkuParserService, SKUPasteModal, 2 traits, blade, assets, manifest, cache)
  - ✅ 3.5.4.3 Composer dump-autoload dla nowych traits
  - ✅ 3.5.4.4 Chrome DevTools MCP verification na produkcji

**ZWERYFIKOWANE FUNKCJONALNOSCI:**
- ✅ Modal otwiera sie bez bledow 500
- ✅ Tryb "SKU + Nazwa" pokazuje opcje widoku wklejania
- ✅ Toggle "Jedna kolumna" / "Dwie kolumny" dziala
- ✅ Dwukolumnowy layout wyswietla sie poprawnie
- ✅ Pola SKU (wymagane) i Nazwy (opcjonalne) z placeholderami
- ✅ Liczniki pozycji pod polami

---

### ✅ FAZA 4: IMPORT CSV/EXCEL (UKONCZONA 2025-12-08)

- **✅ 4.1 Modal CSVImportModal**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/CSVImportModal.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/Traits/CsvFileUploadTrait.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/Traits/CsvColumnMappingTrait.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/Traits/CsvPreviewTrait.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/csv-import-modal.blade.php
  - ✅ 4.1.1 Layout modala
    - ✅ 4.1.1.1 Tytul "Import z pliku CSV/Excel"
    - ✅ 4.1.1.2 Drag & drop zone na plik
    - ✅ 4.1.1.3 Akceptowane formaty: .csv, .xlsx, .xls
    - ✅ 4.1.1.4 Limit rozmiaru: 50MB
    - ✅ 4.1.1.5 Progress bar uploadingu
  - ✅ 4.1.2 Mapowanie kolumn
    - ✅ 4.1.2.1 Preview pierwszych 5 wierszy pliku
    - ✅ 4.1.2.2 Automatyczne wykrywanie kolumn (SKU, Nazwa, etc.)
    - ✅ 4.1.2.3 Dropdown mapowania: kolumna pliku → pole PPM
    - ✅ 4.1.2.4 Wymagane minimum: SKU
    - ✅ 4.1.2.5 Opcjonalne: Nazwa, Typ produktu, Kategorie, Cena
  - ✅ 4.1.3 Properties (w Traits)
    - ✅ 4.1.3.1 $uploadedFile - Livewire TemporaryUploadedFile (CsvFileUploadTrait)
    - ✅ 4.1.3.2 $previewRows - pierwsze 5 wierszy (CsvPreviewTrait)
    - ✅ 4.1.3.3 $columnMapping - mapowanie kolumn (CsvColumnMappingTrait)
    - ✅ 4.1.3.4 $totalRows - liczba wierszy do importu
    - ✅ 4.1.3.5 $importProgress - postep importu (dla duzych plikow)

- **✅ 4.2 CSVParserService + ExcelParserService**
  └── 📁 PLIK: app/Services/Import/CsvParserService.php
  └── 📁 PLIK: app/Services/Import/ExcelParserService.php
  └── 📁 PLIK: app/Services/Import/ColumnMappingService.php
  - ✅ 4.2.1 Parsowanie plikow
    - ✅ 4.2.1.1 parseCSV($file) - parsowanie CSV z roznych encoding (CsvParserService)
    - ✅ 4.2.1.2 parseExcel($file) - parsowanie XLSX/XLS (ExcelParserService + PhpSpreadsheet)
    - ✅ 4.2.1.3 detectDelimiter($file) - wykrywanie separatora CSV
    - ✅ 4.2.1.4 detectEncoding($file) - wykrywanie kodowania
  - ✅ 4.2.2 Automatyczne mapowanie (ColumnMappingService)
    - ✅ 4.2.2.1 guessColumnMapping($headers) - heurystyka na podstawie nazw kolumn
    - ✅ 4.2.2.2 Slownik synonimow: "SKU", "Kod", "Indeks", "Reference" → sku
    - ✅ 4.2.2.3 Slownik synonimow: "Nazwa", "Name", "Tytul", "Product" → name
    - ✅ 4.2.2.4 Confidence score dla kazdego mapowania
  - ✅ 4.2.3 Przetwarzanie batch
    - ✅ 4.2.3.1 processInBatches($rows, $mapping, $batchSize = 100)
    - ✅ 4.2.3.2 Progress tracking per batch
    - ✅ 4.2.3.3 Error collection per row
    - ✅ 4.2.3.4 Transaction per batch (rollback on error)

- **❌ 4.3 Integracja z istniejacymi szablonami** (opcjonalne rozszerzenie - przesunięte na pozniej)
  - ❌ 4.3.1 Szablony mapowania (opcjonalne rozszerzenie)
    - ❌ 4.3.1.1 Zapisywanie uzywanych mapowań jako szablony
    - ❌ 4.3.1.2 Wybor szablonu przy kolejnym imporcie
    - ❌ 4.3.1.3 Szablony per user lub globalne
  - ❌ 4.3.2 Predefiniowane szablony
    - ❌ 4.3.2.1 "Pojazdy" - VIN, Engine, Model, Year, etc.
    - ❌ 4.3.2.2 "Czesci zamienne" - SKU, Nazwa, Oryginal, Zamiennik
    - ❌ 4.3.2.3 "Basic" - SKU, Nazwa

**NAPRAWIONY BUG:** Modal nie otwierał się po kliknięciu "Import CSV" - przyczyną było conditional rendering (`@if($activeModal === 'csv-import')`) PRZED zamontowaniem komponentu. Event dispatch następował zanim komponent był w DOM.
**ROZWIĄZANIE:** Zmieniono na always-rendered component z wewnętrzną kontrolą widoczności przez `showModal` property.
  └── 📁 PLIK: resources/views/livewire/products/import/product-import-panel.blade.php (linia 214)

---

### ✅ FAZA 5: EDYCJA INLINE W PANELU (UKONCZONA 2025-12-09 - podstawowy zakres)

- **✅ 5.1 Komponent PendingProductRow**
  └── 📁 PLIK: resources/views/livewire/products/import/partials/product-row.blade.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelActions.php
  - ✅ 5.1.1 Properties
    - ✅ 5.1.1.1 $product - PendingProduct model
    - ✅ 5.1.1.2 $isEditing - ktore pole jest edytowane
    - ✅ 5.1.1.3 $editValue - tymczasowa wartosc edycji
  - ✅ 5.1.2 Edycja podstawowych pol
    - ✅ 5.1.2.1 SKU - input text z walidacja real-time
    - ✅ 5.1.2.2 Nazwa - input text
    - ✅ 5.1.2.3 Typ produktu - dropdown z ProductType
    - ✅ 5.1.2.4 Autosave po blur lub Enter
  - ✅ 5.1.3 Feedback wizualny
    - ✅ 5.1.3.1 Flash messages przy zapisie
    - ✅ 5.1.3.2 Hover states na polach

- **✅ 5.2 Komponent CategoryPickerL3L7 (inline-category-select)**
  └── 📁 PLIK: resources/views/livewire/products/import/partials/inline-category-select.blade.php
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelCategoryShopTrait.php
  - ✅ 5.2.1 Hierarchiczny picker
    - ✅ 5.2.1.1 Dropdown L3 (glowna kategoria)
    - ✅ 5.2.1.2 Dropdown L4 (zalezy od L3)
    - ✅ 5.2.1.3 Dropdown L5 (zalezy od L4)
    - ✅ 5.2.1.4 Dropdown L6 (opcjonalny, zalezy od L5)
    - ❌ 5.2.1.5 Dropdown L7 (opcjonalny - przesunięte)
  - ✅ 5.2.2 Zachowanie
    - ✅ 5.2.2.1 Kaskadowe ladowanie kategorii na podstawie parenta
    - ✅ 5.2.2.2 Zmiana wyzszego poziomu resetuje nizsze
    - ✅ 5.2.2.3 Wyszukiwarka w dropdownie (FIX 2025-12-09)
    - ✅ 5.2.2.4 Wyswietlanie nazwy kategorii po wyborze (FIX 2025-12-09)
    - ✅ 5.2.2.5 Opcja "Dodaj nowa kategorie" inline (FIX 2025-12-09)
  - ❌ 5.2.3 Masowe przypisanie (przesunięte do FAZY 6)

- **✅ 5.3 Komponent ShopTiles (inline-shop-select)**
  └── 📁 PLIK: resources/views/livewire/products/import/partials/inline-shop-select.blade.php
  - ✅ 5.3.1 Multi-select dropdown
    - ✅ 5.3.1.1 Wyswietlanie wszystkich aktywnych sklepow PrestaShop
    - ✅ 5.3.1.2 Checkbox multi-select per sklep
    - ✅ 5.3.1.3 Stan: zaznaczony (checkbox checked)
    - ✅ 5.3.1.4 Klik = toggle zaznaczenia
    - ✅ 5.3.1.5 Licznik wybranych sklepow w przycisku
  - ❌ 5.3.2 Masowy wybor (przesunięte do FAZY 6)

- **✅ 5.4 Modal VariantModal** (UKONCZONE 2025-12-09)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/VariantModal.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/variant-modal.blade.php
  - ✅ 5.4.1 Tworzenie wariantow
    - ✅ 5.4.1.1 Lista atrybutow (dropdown z VariantAttribute)
    - ✅ 5.4.1.2 Dodawanie wartosci atrybutow (input text)
    - ✅ 5.4.1.3 Zapisywanie do variant_data JSON w PendingProduct
    - ✅ 5.4.1.4 Wyswietlanie istniejacych wariantow
  - ✅ 5.4.2 Funkcjonalnosci modala
    - ✅ 5.4.2.1 Kopiowanie wariantow z innego produktu (SKU lookup)
    - ✅ 5.4.2.2 Czyszczenie wszystkich wariantow
    - ✅ 5.4.2.3 Usuwanie pojedynczego wariantu
    - ✅ 5.4.2.4 Licznik wariantow

- **✅ 5.5 Modal FeatureTemplateModal** (UKONCZONE 2025-12-09)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/FeatureTemplateModal.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/feature-template-modal.blade.php
  - ✅ 5.5.1 Zarzadzanie cechami produktu
    - ✅ 5.5.1.1 Lista wszystkich FeatureType z bazy (z paginacja limit 50)
    - ✅ 5.5.1.2 Wyszukiwarka cech (name/code)
    - ✅ 5.5.1.3 Input text dla wartosci cechy
    - ✅ 5.5.1.4 Select dropdown dla cech z predefined FeatureValue
  - ✅ 5.5.2 Funkcjonalnosci modala
    - ✅ 5.5.2.1 Kopiowanie cech z innego produktu (SKU lookup - Product lub PendingProduct)
    - ✅ 5.5.2.2 Czyszczenie wszystkich cech
    - ✅ 5.5.2.3 Zapisywanie do feature_data JSON w PendingProduct
    - ✅ 5.5.2.4 Licznik wypelnionych cech
  - **NAPRAWIONY BUG (2025-12-09):** `orderBy('position')` → `orderBy('sort_order')` dla FeatureGroup

- **✅ 5.6 Modal CompatibilityModal** (UKONCZONE 2025-12-09)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/CompatibilityModal.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/compatibility-modal.blade.php
  - ✅ 5.6.1 Zarzadzanie dopasowaniami pojazdow
    - ✅ 5.6.1.1 Wyszukiwarka pojazdow (Product gdzie product_type = 'pojazd')
    - ✅ 5.6.1.2 Dropdown "Typ dopasowania" (CompatibilityAttribute: Original, Replacement, etc.)
    - ✅ 5.6.1.3 Dropdown "Zrodlo" (CompatibilitySource: Manufacturer, TecDoc, Manual)
    - ✅ 5.6.1.4 Lista przypisanych dopasowan z mozliwoscia usuniecia
    - ✅ 5.6.1.5 Pole notatek per dopasowanie
  - ✅ 5.6.2 Funkcjonalnosci modala
    - ✅ 5.6.2.1 Kopiowanie dopasowan z innego produktu (SKU lookup)
    - ✅ 5.6.2.2 Czyszczenie wszystkich dopasowan
    - ✅ 5.6.2.3 Zapisywanie do compatibility_data JSON w PendingProduct
    - ✅ 5.6.2.4 Licznik przypisanych pojazdow
  - **NAPRAWIONY BUG (2025-12-09):** CompatibilitySource model `ordered()` scope - zmiana z `orderBy('position')` na `orderBy('id')` (tabela nie ma kolumny position)

- **✅ 5.7 Modal ImageUploadModal (Zarzadzanie zdjeciami)** (UKONCZONE 2025-12-09)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/ImageUploadModal.php
  └── 📁 PLIK: resources/views/livewire/products/import/modals/image-upload-modal.blade.php
  - ✅ 5.7.1 Upload zdjec
    - ✅ 5.7.1.1 Drag & drop zone z wizualnym feedbackiem
    - ✅ 5.7.1.2 Akceptowane formaty: JPG, PNG, GIF, WebP
    - ✅ 5.7.1.3 Max rozmiar: 10MB per plik
    - ✅ 5.7.1.4 Livewire TemporaryUploadedFile handling
  - ✅ 5.7.2 Pobieranie z URL
    - ✅ 5.7.2.1 Input URL z walidacja
    - ✅ 5.7.2.2 Przycisk "Pobierz" (download i zapis)
  - ✅ 5.7.3 Kopiowanie z produktu
    - ✅ 5.7.3.1 Input SKU lookup
    - ✅ 5.7.3.2 Kopiowanie media_ids z Product lub temp_media_paths z PendingProduct
  - ✅ 5.7.4 Galeria
    - ✅ 5.7.4.1 Wyswietlanie miniaturek
    - ✅ 5.7.4.2 Wybor zdjecia glownego (is_primary)
    - ✅ 5.7.4.3 Usuwanie zdjec
    - ✅ 5.7.4.4 Zapisywanie do temp_media_paths JSON w PendingProduct
    - ✅ 5.7.4.5 Licznik zdjec

---

### ✅ FAZA 6: SYSTEM PUBLIKACJI (UKONCZONA 2025-12-09)

- **✅ 6.1 Serwis ProductPublicationService**
  └── 📁 PLIK: app/Services/Import/ProductPublicationService.php
  - ✅ 6.1.1 Walidacja przed publikacja
    - ✅ 6.1.1.1 validateForPublication($pendingProduct) - sprawdzenie kompletnosci
    - ✅ 6.1.1.2 Wymagane: SKU, nazwa, kategoria, typ, sklep (via PendingProduct::REQUIRED_FIELDS)
    - ✅ 6.1.1.3 Zwracanie listy brakow jesli niekompletny
  - ✅ 6.1.2 Publikacja pojedynczego produktu
    - ✅ 6.1.2.1 publishSingle($pendingProduct) - tworzenie Product z PendingProduct
    - ✅ 6.1.2.2 Transakcja DB: tworzenie Product + relacje
    - ✅ 6.1.2.3 Przypisanie do sklepow (ProductShopData)
    - ✅ 6.1.2.4 Przypisanie kategorii (categories sync)
    - ❌ 6.1.2.5 Tworzenie dopasowan/cech (przesuniete - optional)
  - ✅ 6.1.3 Publikacja batch
    - ✅ 6.1.3.1 publishBatch($pendingProductIds) - masowa publikacja
    - ✅ 6.1.3.2 Progress tracking (results array)
    - ✅ 6.1.3.3 Error handling - kontynuacja mimo bledow pojedynczych
    - ✅ 6.1.3.4 Raport z wynikami: sukces/blad per produkt
  - ✅ 6.1.4 Tworzenie job-ow sync
    - ✅ 6.1.4.1 dispatchSyncJobs($product, $shopIds)
    - ✅ 6.1.4.2 Jeden SyncProductToPrestaShop job per sklep
    - ✅ 6.1.4.3 Kolejkowanie na 'prestashop-sync' queue

- **❌ 6.2 Komponent ImportProgressBar** (OPCJONALNE - przesuniete)
  - 6.2.1 UI postepu publikacji
    - 6.2.1.1 Modal z progress barem
    - 6.2.1.2 Licznik: X z Y produktow opublikowanych
    - 6.2.1.3 Lista bledow real-time
    - 6.2.1.4 Przycisk "Anuluj" (jesli mozliwe)
  - 6.2.2 Wire:poll dla aktualizacji
    - 6.2.2.1 Polling co 2 sekundy podczas publikacji
    - 6.2.2.2 Zatrzymanie po zakonczeniu
    - 6.2.2.3 Wyswietlenie podsumowania

- **✅ 6.3 Workflow publikacji**
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelActions.php (publishSingle)
  └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelBulkOperations.php (bulkPublish)
  - ✅ 6.3.1 Pojedyncza publikacja
    - ✅ 6.3.1.1 Przycisk "Publikuj" w wierszu produktu
    - ✅ 6.3.1.2 Walidacja → jesli OK, publikacja → przekierowanie do ProductForm
    - ✅ 6.3.1.3 Jesli bledy → wyswietlenie flash message z bledami
  - ✅ 6.3.2 Masowa publikacja
    - ✅ 6.3.2.1 Zaznaczenie produktow → przycisk "Publikuj zaznaczone"
    - ✅ 6.3.2.2 Filtrowanie tylko gotowych produktow (readyForPublish scope)
    - ✅ 6.3.2.3 Walidacja batch → raport ile opublikowanych, ile bledow
    - ✅ 6.3.2.4 Flash message z wynikami
    - ❌ 6.3.2.5 Progress bar podczas publikacji (przesuniete do 6.2)
    - ❌ 6.3.2.6 Modal potwierdzenia (opcjonalne rozszerzenie)

- **✅ 6.4 Integracja z PrestaShop Sync**
  - ✅ 6.4.1 Automatyczne tworzenie job-ow
    - ✅ 6.4.1.1 Po publikacji → SyncProductToPrestaShop dla kazdego sklepu
    - ✅ 6.4.1.2 dispatchSyncJobs() w ProductPublicationService
    - ✅ 6.4.1.3 Kolejka 'prestashop-sync'
  - ✅ 6.4.2 Audit trail (PublishHistory)
    - ✅ 6.4.2.1 Zapis sync_jobs_dispatched do PublishHistory
    - ✅ 6.4.2.2 Tracking sync_status (pending → in_progress → completed)
    - ❌ 6.4.2.3 Link do strony sync status (opcjonalne rozszerzenie)

---

### ✅ FAZA 6.5: ROZBUDOWA PANELU IMPORTU - NOWE KOLUMNY I FUNKCJE (2025-12-10)

- **✅ 6.5.1 System Resizable Columns (zmiana szerokosci kolumn)**
  - ✅ 6.5.1.1 Implementacja CSS resize handles na naglowkach kolumn
    - ✅ 6.5.1.1.1 Dodanie cursor: col-resize na borderach kolumn
    - ✅ 6.5.1.1.2 Alpine.js component do drag-and-drop resize
      └──📁 PLIK: resources/js/resizable-columns.js
    - ✅ 6.5.1.1.3 Min/max width constraints per kolumna (50-500px)
  - ✅ 6.5.1.2 Persistencja ustawien uzytkownika
    - ✅ 6.5.1.2.1 Zapis szerokosci do localStorage (JSON: {columnId: width})
    - ✅ 6.5.1.2.2 Odczyt przy ladowaniu strony
    - ✅ 6.5.1.2.3 Reset do domyslnych przycisk
  - ✅ 6.5.1.3 Optymalizacja domyslnych szerokosci
    - ✅ 6.5.1.3.1 Zwezenie kolumny SKU (z ~200px na ~100px)
    - ✅ 6.5.1.3.2 Dostosowanie pozostalych kolumn proporcjonalnie

- **✅ 6.5.2 Kolumna Marka (Manufacturer) - OBOWIAZKOWA**
  - ✅ 6.5.2.1 UI kolumny
    - ✅ 6.5.2.1.1 Inline edit z text input (uproszczone vs dropdown)
    - ✅ 6.5.2.1.2 Placeholder: "brak" (amber color when empty)
  - ✅ 6.5.2.2 Backend
    - ✅ 6.5.2.2.1 Dodanie manufacturer do REQUIRED_FIELDS w PendingProduct
      └──📁 PLIK: app/Models/PendingProduct.php (linia 91-98)
    - ✅ 6.5.2.2.2 Aktualizacja recalculateCompletion() - wliczanie do %

- **✅ 6.5.3 Kolumna Cena Detal (base_price) - OPCJONALNA**
  - ✅ 6.5.3.1 UI kolumny
    - ✅ 6.5.3.1.1 Input number z formatowaniem PLN (0,00 zl)
    - ✅ 6.5.3.1.2 Domyslna wartosc: 0.00
    - ✅ 6.5.3.1.3 Suffix " zl" po prawej stronie inputa
  - ✅ 6.5.3.2 Backend
    - ✅ 6.5.3.2.1 base_price NIE wliczane do completion % (opcjonalne)
    - ✅ 6.5.3.2.2 Zapis do PendingProduct.base_price

- **✅ 6.5.4 Modal Opisy Produktu (DescriptionModal) - OBOWIAZKOWY**
  - ✅ 6.5.4.1 Struktura modala
    - ✅ 6.5.4.1.1 Tytul: "Opisy produktu: {SKU}"
    - ✅ 6.5.4.1.2 Pole "Krotki opis" (textarea, 500 znakow counter)
    - ✅ 6.5.4.1.3 Pole "Pelny opis" (textarea HTML, character count)
    - ✅ 6.5.4.1.4 Checkbox "Publikuj bez opisow" (skip flag)
    - ✅ 6.5.4.1.5 Przyciski: Anuluj, Zapisz (red when skip)
      └──📁 PLIK: app/Http/Livewire/Products/Import/Modals/DescriptionModal.php
      └──📁 PLIK: resources/views/livewire/products/import/modals/description-modal.blade.php
  - ✅ 6.5.4.2 Backend
    - ✅ 6.5.4.2.1 Model property: skip_descriptions (boolean)
    - ✅ 6.5.4.2.2 Migracja: dodanie kolumny skip_descriptions
      └──📁 PLIK: database/migrations/2025_12_10_180000_add_skip_descriptions_to_pending_products.php
    - ✅ 6.5.4.2.3 isFieldCompleteWithSkip() dla short/long_description
  - ✅ 6.5.4.3 Integracja z wiersem produktu
    - ✅ 6.5.4.3.1 Ikona opisow przed kreska oddzielajaca
    - ✅ 6.5.4.3.2 Color coding ikony:
      - ✅ Szary: brak opisow
      - ✅ Fioletowy (indigo): opisy wypelnione
      - ✅ Czerwony: skip_descriptions = true
    - ✅ 6.5.4.3.3 Klikniecie ikony otwiera DescriptionModal
      └──📁 PLIK: resources/views/livewire/products/import/partials/product-row.blade.php

- **✅ 6.5.5 Aktualizacja completion % calculation**
  - ✅ 6.5.5.1 Nowa formula:
    - ✅ REQUIRED (80%): sku, name, category_ids, product_type_id, shop_ids, **manufacturer**
    - ✅ OPTIONAL (20%): temp_media_paths, compatibility_data/feature_data
  - ✅ 6.5.5.2 Skip flags handling
    - ✅ 6.5.5.2.1 skip_descriptions traktowane jak skip_images/skip_features

- **✅ 6.5.6 Deployment i weryfikacja**
  - ✅ 6.5.6.1 Migracja bazy danych (skip_descriptions)
  - ✅ 6.5.6.2 npm run build
  - ✅ 6.5.6.3 Deploy wszystkich plikow
      └──📁 PLIK: _TOOLS/deploy_faza65.ps1
  - ✅ 6.5.6.4 Chrome DevTools MCP verification
      └──📁 PLIK: _TOOLS/screenshots/faza65_import_panel_initial.jpg
      └──📁 PLIK: _TOOLS/screenshots/faza65_description_modal.jpg

---

### ✅ FAZA 9: REDESIGN PANELU IMPORTU (UKONCZONA 2026-02-02)

**Plan:** `Plan_Projektu/misty-dazzling-pie.md`

- **✅ 9.1 Infrastruktura (DB + Model + Config + Permissions)**
  - ✅ 9.1.1 Migracja: add_import_redesign_fields_to_pending_products
    └── 📁 PLIK: database/migrations/2026_02_02_120000_add_import_redesign_fields_to_pending_products.php
    - ✅ Nowe kolumny: cn_code, material, defect_symbol, application, split_payment, shop_internet, is_variant_master
    - ✅ Nowe kolumny JSON: price_data, publication_targets
    - ✅ Nowe kolumny: scheduled_publish_at, publish_status (draft/scheduled/publishing/published/failed)
  - ✅ 9.1.2 Config import.php (ERP primary config)
    └── 📁 PLIK: config/import.php
  - ✅ 9.1.3 Config permissions/import.php (11 permissions P1-P11)
    └── 📁 PLIK: config/permissions/import.php
  - ✅ 9.1.4 Aktualizacja PendingProduct model (+fillable, +casts, publication_targets)
    └── 📁 PLIK: app/Models/PendingProduct.php
  - ✅ 9.1.5 ImportPanelPermissionTrait (11 metod uprawnien per kolumna/akcja)
    └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelPermissionTrait.php

- **✅ 9.2 Zunifikowany Modal Importu (CSV + Column)**
  - ✅ 9.2.1 ProductImportModal.php (glowny komponent z 2 trybami)
    └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/ProductImportModal.php
  - ✅ 9.2.2 Trait'y modalowe (8 plikow)
    └── 📁 PLIK: .../Modals/Traits/ImportModalCsvModeTrait.php
    └── 📁 PLIK: .../Modals/Traits/ImportModalColumnModeTrait.php
    └── 📁 PLIK: .../Modals/Traits/ImportModalSwitchesTrait.php
    └── 📁 PLIK: .../Modals/Traits/CsvFileUploadTrait.php
    └── 📁 PLIK: .../Modals/Traits/CsvColumnMappingTrait.php
    └── 📁 PLIK: .../Modals/Traits/CsvPreviewTrait.php
    └── 📁 PLIK: .../Modals/Traits/SkuPasteParsingTrait.php
    └── 📁 PLIK: .../Modals/Traits/SkuPasteViewModeTrait.php
  - ✅ 9.2.3 Blade views (modal + 6 partials)
    └── 📁 PLIK: resources/views/livewire/products/import/modals/product-import-modal.blade.php
    └── 📁 PLIK: .../modals/partials/csv-mode.blade.php
    └── 📁 PLIK: .../modals/partials/column-mode.blade.php
    └── 📁 PLIK: .../modals/partials/csv-upload-zone.blade.php
    └── 📁 PLIK: .../modals/partials/csv-column-mapping.blade.php
    └── 📁 PLIK: .../modals/partials/csv-preview-table.blade.php
    └── 📁 PLIK: .../modals/partials/csv-import-result.blade.php
  - ✅ 9.2.4 Przelaczniki: Sklep Internetowy, Podzielona platnosc, Produkt Wariantowy
  - ✅ 9.2.5 Przycisk "Importuj produkty" zastepujacy "Wklej SKU" + "Import CSV"

- **✅ 9.3 Redesign Tabeli**
  - ✅ 9.3.1 Usuniecie kolumny "Marka" (dane w modalu importu)
  - ✅ 9.3.2 Zmiana "Sklepy" → "Publikacja" z badge'ami ERP + PrestaShop
  - ✅ 9.3.3 Nowa kolumna "Publikuj" z maszyna stanow (draft/scheduled/publishing/published/failed)
  - ✅ 9.3.4 Klik "Cena" otwiera ImportPricesModal zamiast inline edit
  - ✅ 9.3.5 ImportPanelPublicationTrait
    └── 📁 PLIK: app/Http/Livewire/Products/Import/Traits/ImportPanelPublicationTrait.php
  - ✅ 9.3.6 CSS import-panel.css (badges, countdown, publish states)
    └── 📁 PLIK: resources/css/products/import-panel.css

- **✅ 9.4 Modal Cen (ImportPricesModal)**
  - ✅ 9.4.1 ImportPricesModal.php - tabela grup cenowych z lock/unlock
    └── 📁 PLIK: app/Http/Livewire/Products/Import/Modals/ImportPricesModal.php
    └── 📁 PLIK: resources/views/livewire/products/import/modals/import-prices-modal.blade.php
  - ✅ 9.4.2 10 grup cenowych z bazy (Detaliczna, MRF-MPP, Szkolka-Komis-Drop, itd.)
  - ✅ 9.4.3 Auto-kalkulacja netto/brutto z Alpine.js
  - ✅ 9.4.4 Zapis do price_data JSON, sync base_price z grupy domyslnej
  - ✅ 9.4.5 PublicationTargetService
    └── 📁 PLIK: app/Services/Import/PublicationTargetService.php

- **✅ 9.5 Scheduler Auto-publikacji**
  - ✅ 9.5.1 PublishScheduledProducts command
    └── 📁 PLIK: app/Console/Commands/PublishScheduledProducts.php
  - ✅ 9.5.2 Rejestracja w routes/console.php (everyMinute)

- **✅ 9.6 Deployment + Testy (2026-02-02)**
  - ✅ 9.6.1 npm run build (import-panel-C2mrGhhj.css)
  - ✅ 9.6.2 Deploy 30+ plikow via pscp do Hostido
  - ✅ 9.6.3 Migracja uruchomiona (po fixie avatar migration blocker)
  - ✅ 9.6.4 RolePermissionSeeder: 11 permissions "Panel Importu"
  - ✅ 9.6.5 Cache clear + composer dump-autoload
  - ✅ 9.6.6 Chrome verification: wizualna + funkcjonalna
    - ✅ Przycisk "Importuj produkty" → zunifikowany modal (CSV + Kolumnowy)
    - ✅ Tryb CSV: stepper, textarea, upload, parsuj dane
    - ✅ Tryb Kolumnowy: dynamiczne kolumny, dodawanie wierszy
    - ✅ Klik "Cena" → ImportPricesModal z 10 grupami cenowymi
    - ✅ Lock/unlock mechanizm
    - ✅ Przycisk "Publikuj": ready (100%) = aktywny button, disabled (<100%) = span
    - ✅ Kolumna "Publikacja" z badge PPM
    - ✅ Brak bledow konsoli

**BLEDY NAPRAWIONE:**
- ✅ Avatar migration blocker (manualne dodanie do tabeli migrations)
- ✅ Livewire RootTagMissingFromViewException (brak root HTML tag w import-prices-modal)
- ✅ TypeError: Cannot assign string to float (explicit (float) cast w $taxRate)

---

### FAZA 7: HISTORIA I AUDYT

- **7.1 Komponent ImportHistory**
  - 7.1.1 Lista zakończonych sesji importu
    - 7.1.1.1 Kolumny: data, zrodlo, liczba produktow, status, user
    - 7.1.1.2 Filtrowanie po dacie, zrodle, userze
    - 7.1.1.3 Sortowanie po dacie (domyslnie najnowsze)
  - 7.1.2 Szczegoly sesji
    - 7.1.2.1 Modal ze szczegolami sesji
    - 7.1.2.2 Lista produktow z tej sesji (z linkami do ProductForm)
    - 7.1.2.3 Statystyki: ile opublikowanych, ile bledow
  - 7.1.3 Eksport historii
    - 7.1.3.1 Przycisk "Eksportuj do CSV"
    - 7.1.3.2 Zawartosc: SKU, nazwa, status, data publikacji, sklepy

- **7.2 Audyt i logi**
  - 7.2.1 Logowanie operacji
    - 7.2.1.1 Log tworzenia PendingProduct (user, timestamp, source)
    - 7.2.1.2 Log edycji (user, timestamp, zmienione pola)
    - 7.2.1.3 Log publikacji (user, timestamp, target Product ID)
    - 7.2.1.4 Log bledow (szczegoly bledu, stack trace)
  - 7.2.2 Statystyki
    - 7.2.2.1 Dashboard z metrykami importu
    - 7.2.2.2 Wykres: importy per dzien/tydzien/miesiac
    - 7.2.2.3 Top users importujacy produkty
    - 7.2.2.4 Najczestsze bledy

---

### FAZA 8: TESTOWANIE I OPTYMALIZACJA

- **8.1 Testy jednostkowe**
  - 8.1.1 Testy SKUParserService
    - 8.1.1.1 Test parsowania tylko SKU
    - 8.1.1.2 Test parsowania SKU + nazwa
    - 8.1.1.3 Test wykrywania separatorow
    - 8.1.1.4 Test walidacji duplikatow
  - 8.1.2 Testy CSVParserService
    - 8.1.2.1 Test parsowania CSV
    - 8.1.2.2 Test parsowania Excel
    - 8.1.2.3 Test automatycznego mapowania kolumn
  - 8.1.3 Testy ProductPublicationService
    - 8.1.3.1 Test walidacji kompletnosci
    - 8.1.3.2 Test publikacji pojedynczego produktu
    - 8.1.3.3 Test publikacji batch
    - 8.1.3.4 Test tworzenia sync jobs

- **8.2 Testy integracyjne**
  - 8.2.1 Testy Livewire
    - 8.2.1.1 Test ProductImportPanel - wyswietlanie listy
    - 8.2.1.2 Test SKUPasteModal - import z wklejenia
    - 8.2.1.3 Test CSVImportModal - import z pliku
    - 8.2.1.4 Test CategoryPickerL3L7 - hierarchia kategorii
  - 8.2.2 Testy E2E
    - 8.2.2.1 Pelny workflow: wklej SKU → uzupelnij dane → publikuj
    - 8.2.2.2 Pelny workflow: import CSV → mapowanie → publikuj
    - 8.2.2.3 Test masowych operacji

- **8.3 Optymalizacja wydajnosci**
  - 8.3.1 Baza danych
    - 8.3.1.1 Indeksy dla czestych zapytan
    - 8.3.1.2 Eager loading relacji
    - 8.3.1.3 Chunking dla duzych batch operacji
  - 8.3.2 Frontend
    - 8.3.2.1 Lazy loading komponentow
    - 8.3.2.2 Debouncing dla autosave
    - 8.3.2.3 Virtual scrolling dla duzych list (opcjonalnie)

- **8.4 Deployment i dokumentacja**
  - 8.4.1 Deployment na produkcje
    - 8.4.1.1 Migracje bazy danych
    - 8.4.1.2 Seed danych testowych (opcjonalnie)
    - 8.4.1.3 Weryfikacja Chrome DevTools MCP
  - 8.4.2 Dokumentacja
    - 8.4.2.1 README dla modulu Import
    - 8.4.2.2 Instrukcja uzytkownika
    - 8.4.2.3 Aktualizacja CLAUDE.md

---

## CRITERIA AKCEPTACJI ETAPU

Etap uznajemy za ukonczony gdy:

1. **Import SKU:**
   - Wklejanie listy SKU (jedna lub dwie kolumny) dziala
   - Automatyczne wykrywanie separatorow dziala
   - Walidacja duplikatow dziala

2. **Import CSV/Excel:**
   - Upload plikow CSV/XLSX dziala
   - Automatyczne mapowanie kolumn dziala
   - Preview i batch processing dziala

3. **Panel Pending Products:**
   - Tabela wyswietla produkty z wymaganymi kolumnami
   - Edycja inline dziala (SKU, nazwa, typ, kategorie)
   - Akcje masowe dzialaja (kategorie, typ, sklepy)

4. **Publikacja:**
   - Walidacja kompletnosci dziala
   - Publikacja pojedyncza i batch dziala
   - SyncProductToPrestaShop jobs sa tworzone
   - Produkty pojawiaja sie w ProductList

5. **UX:**
   - Feedback wizualny przy walidacji
   - Progress bar przy dluzszych operacjach
   - Historia importow dostepna

---

## POTENCJALNE PROBLEMY I ROZWIAZANIA

### Problem 1: Wydajnosc przy duzych importach (>1000 SKU)
**Rozwiazanie:** Batch processing, chunking, queue jobs dla publikacji

### Problem 2: Duplikaty SKU w roznych kontekstach
**Rozwiazanie:** Jasne komunikaty - duplikat w PPM vs duplikat w tej samej sesji

### Problem 3: Zlozonosc hierarchii kategorii L3-L7
**Rozwiazanie:** Kaskadowe dropdown z wyszukiwarka, cache dla list kategorii

### Problem 4: Rozne formaty CSV (encoding, separator)
**Rozwiazanie:** Automatyczne wykrywanie + manual override

---

## METRYKI SUKCESU ETAPU

- Czas wykonania: Max 50 godzin
- Wydajnosc: Import 1000 SKU w < 30 sekund
- Wydajnosc: Publikacja 100 produktow w < 60 sekund
- UX: < 3 kliki od wklejenia SKU do publikacji (dla prostych produktow)
- Niezawodnosc: 99%+ sukces publikacji dla kompletnych produktow

---

## PRZYGOTOWANIE DO ETAP_07

Po ukonczeniu ETAP_06 bedziemy mieli:
- **Kompletny pipeline importu** od SKU do Product w bazie PPM
- **Integracje z sync jobs** dla automatycznego eksportu na PrestaShop
- **System pending products** jako bufor przed publikacja
- **Narzedzia masowe** dla efektywnej pracy z duza iloscia produktow

**Zaleznosc od ETAP_07:** Ten etap wykorzystuje istniejace `SyncProductToPrestaShop` z ETAP_07 do eksportu na PrestaShop.

---

## NOTATKI IMPLEMENTACYJNE

### SKU-First Architecture
Zgodnie z `_DOCS/SKU_ARCHITECTURE_GUIDE.md`:
- SKU jest GLOWNYM identyfikatorem produktu
- PendingProduct.sku musi byc unikatowy w ramach sesji
- Przy publikacji sprawdzamy unikatowosc SKU w tabeli products

### Integracja z istniejacymi komponentami
- **ProductForm** - po publikacji mozna przekierowac do edycji produktu
- **CategoryPicker** - wykorzystac istniejacy komponent lub zbudowac dedykowany L3-L7
- **ShopSelector** - istniejace kafelki sklepow z ProductForm mozna uzyc ponownie
- **CompatibilityManagement** - integracja przez ID dopasowan

### Kolejnosc implementacji
1. DB + Modele (fundament)
2. Panel UI (widocznosc)
3. Import SKU (najczestsza operacja)
4. Edycja inline (core UX)
5. Publikacja (cel koncowy)
6. Import CSV (rozszerzenie)
7. Historia (audyt)
8. Testy (jakosc)
