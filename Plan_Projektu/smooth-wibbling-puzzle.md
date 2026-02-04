# PLAN: System Skanowania i Powiązań Produktów

**Data:** 2026-02-03
**Status:** ✅ ZAIMPLEMENTOWANO
**Etap:** ETAP_10 - Product Scan System
**Data ukończenia:** 2026-02-03

---

## 1. CEL SYSTEMU

Automatyczne wyszukiwanie i tworzenie powiązań produktów między PPM a systemami zewnętrznymi (ERP + PrestaShop) z możliwością dwukierunkowej synchronizacji.

### Typy skanów:
| # | Typ | Opis | Akcja |
|---|-----|------|-------|
| 1 | **Skan powiązań** | Produkty PPM bez linkow do źródła | Linkuj po SKU |
| 2 | **Brakujące w PPM** | Produkty w źródle, brak w PPM | Import do PendingProduct |
| 3 | **Brakujące w źródle** | Produkty PPM, brak w źródle | Eksport lub dezaktywacja |

### Integracje:
- ✅ Subiekt GT (REST API: sapi.mpptrade.pl)
- ✅ Baselinker (API)
- ✅ Microsoft Dynamics
- ✅ PrestaShop (wszystkie sklepy)

---

## 2. ARCHITEKTURA

```
┌─────────────────────────────────────────────────────────────────┐
│                    /admin/scan-products                          │
│                  ScanProductsPanel (Livewire)                    │
├─────────────────────────────────────────────────────────────────┤
│  [Skan Powiązań] [Brak w PPM] [Brak w Źródle] [Historia]        │
└─────────────────────────────────────────────────────────────────┘
                              │
                   ┌──────────┼──────────┐
                   ▼          ▼          ▼
          ┌────────────┐ ┌────────────┐ ┌────────────┐
          │ScanLinksJob│ │ScanMissing │ │ScanOrphans │
          │(Background)│ │InPpmJob    │ │Job         │
          └────────────┘ └────────────┘ └────────────┘
                   │          │          │
                   └──────────┼──────────┘
                              ▼
                   ┌──────────────────────┐
                   │  ProductScanService  │
                   │  - SKU matching      │
                   │  - Diff generation   │
                   │  - Bulk resolution   │
                   └──────────────────────┘
                              │
            ┌─────────────────┼─────────────────┐
            ▼                 ▼                 ▼
   ┌────────────────┐ ┌────────────────┐ ┌────────────────┐
   │SubiektGTSource │ │PrestaShopSource│ │BaselinkerSource│
   │(ScanSource)    │ │(ScanSource)    │ │(ScanSource)    │
   └────────────────┘ └────────────────┘ └────────────────┘
            │                 │                 │
            ▼                 ▼                 ▼
   ┌────────────────┐ ┌────────────────┐ ┌────────────────┐
   │SubiektRestApi  │ │PrestaShop8     │ │Baselinker      │
   │Client (REUSE)  │ │Client (REUSE)  │ │Service (REUSE) │
   └────────────────┘ └────────────────┘ └────────────────┘
```

---

## 3. NOWE TABELE BAZY DANYCH

### 3.1 `product_scan_sessions`
```sql
CREATE TABLE product_scan_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    scan_type ENUM('links', 'missing_in_ppm', 'missing_in_source') NOT NULL,
    source_type VARCHAR(50) NOT NULL,  -- 'subiekt_gt', 'baselinker', 'prestashop'
    source_id INT NULL,                -- erp_connection_id / shop_id

    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,

    total_scanned INT DEFAULT 0,
    matched_count INT DEFAULT 0,
    unmatched_count INT DEFAULT 0,
    errors_count INT DEFAULT 0,

    result_summary JSON NULL,
    error_message TEXT NULL,
    sync_job_id INT NULL,
    user_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_scan_type (scan_type),
    INDEX idx_source (source_type, source_id),
    INDEX idx_status (status)
);
```

### 3.2 `product_scan_results`
```sql
CREATE TABLE product_scan_results (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    scan_session_id BIGINT NOT NULL,

    sku VARCHAR(100) NULL,
    external_id VARCHAR(100) NULL,
    name VARCHAR(255) NULL,

    match_status ENUM('matched', 'unmatched', 'conflict', 'multiple') NOT NULL,
    ppm_product_id INT NULL,
    external_source_type VARCHAR(50) NULL,
    external_source_id INT NULL,

    source_data JSON NULL,
    ppm_data JSON NULL,
    diff_data JSON NULL,

    resolution_status ENUM('pending', 'linked', 'created', 'ignored', 'error') DEFAULT 'pending',
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (scan_session_id) REFERENCES product_scan_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (scan_session_id),
    INDEX idx_sku (sku),
    INDEX idx_match_status (match_status)
);
```

---

## 4. NOWE PLIKI

### 4.1 Modele
| Plik | Opis |
|------|------|
| `app/Models/ProductScanSession.php` | Sesja skanowania |
| `app/Models/ProductScanResult.php` | Wynik pojedynczego produktu |

### 4.2 Serwisy
| Plik | Opis |
|------|------|
| `app/Services/Scan/ProductScanService.php` | Główna logika skanowania |
| `app/Services/Scan/Contracts/ScanSourceInterface.php` | Interfejs źródeł |
| `app/Services/Scan/Sources/SubiektGTScanSource.php` | Adapter Subiekt GT |
| `app/Services/Scan/Sources/PrestaShopScanSource.php` | Adapter PrestaShop |
| `app/Services/Scan/Sources/BaselinkerScanSource.php` | Adapter Baselinker |
| `app/Services/Scan/Sources/DynamicsScanSource.php` | Adapter Dynamics |

### 4.3 Jobs (Background)
| Plik | Opis |
|------|------|
| `app/Jobs/Scan/ScanProductLinksJob.php` | Skan powiązań |
| `app/Jobs/Scan/ScanMissingInPpmJob.php` | Brakujące w PPM |
| `app/Jobs/Scan/ScanMissingInSourceJob.php` | Brakujące w źródle |

### 4.4 Livewire
| Plik | Opis |
|------|------|
| `app/Http/Livewire/Admin/Scan/ScanProductsPanel.php` | Główny komponent |

### 4.5 Widoki Blade
| Plik | Opis |
|------|------|
| `resources/views/livewire/admin/scan/scan-products-panel.blade.php` | Layout |
| `resources/views/livewire/admin/scan/partials/tabs-navigation.blade.php` | Nawigacja |
| `resources/views/livewire/admin/scan/partials/source-selector.blade.php` | Wybór źródła |
| `resources/views/livewire/admin/scan/partials/scan-progress.blade.php` | Progress bar |
| `resources/views/livewire/admin/scan/partials/results-table.blade.php` | Tabela wyników |
| `resources/views/livewire/admin/scan/partials/bulk-actions-toolbar.blade.php` | Akcje masowe |
| `resources/views/livewire/admin/scan/partials/history-list.blade.php` | Historia |

### 4.6 Migracje
| Plik | Opis |
|------|------|
| `database/migrations/2026_02_03_XXXXXX_create_product_scan_sessions_table.php` | Sesje |
| `database/migrations/2026_02_03_XXXXXX_create_product_scan_results_table.php` | Wyniki |

### 4.7 Konfiguracja
| Plik | Opis |
|------|------|
| `config/permissions/scan.php` | Uprawnienia |
| `resources/css/admin/scan-products.css` | Style CSS |

---

## 5. ISTNIEJĄCE PLIKI DO WYKORZYSTANIA (NIE MODYFIKOWAĆ!)

| Plik | Użycie |
|------|--------|
| `app/Services/ERP/SubiektGT/SubiektRestApiClient.php` | `getProducts()`, `getAllSkus()` |
| `app/Services/PrestaShop/PrestaShop8Client.php` | `getProducts()`, `getProductsDateUpd()` |
| `app/Services/ERP/BaselinkerService.php` | `pullAllProducts()` |
| `app/Jobs/ERP/PullProductsFromSubiektGT.php` | Wzorzec joba |
| `app/Jobs/PrestaShop/PullProductsFromPrestaShop.php` | Wzorzec joba |
| `app/Models/ProductErpData.php` | Model powiązań ERP |
| `app/Models/ProductShopData.php` | Model powiązań PrestaShop |
| `app/Models/PendingProduct.php` | Drafty importu |

---

## 6. WORKFLOW SKANÓW

### 6.1 Skan Powiązań (Typ 1)
```
1. Użytkownik wybiera źródło (np. Subiekt GT)
2. Klik "Rozpocznij Skan"
3. Background Job:
   a) Pobiera SKU produktów PPM BEZ powiązania do wybranego źródła
   b) Pobiera wszystkie SKU ze źródła (API batch)
   c) Dla każdego SKU z PPM szuka dopasowania w źródle
   d) Zapisuje wyniki: matched/unmatched + diff_data jeśli różnice
4. Użytkownik przegląda wyniki
5. Bulk actions:
   - "Połącz wybrane" → tworzy ProductErpData/ProductShopData
   - "Połącz wszystkie" → batch linkowanie
```

### 6.2 Brakujące w PPM (Typ 2)
```
1. Użytkownik wybiera źródło
2. Background Job:
   a) Pobiera wszystkie SKU ze źródła
   b) Pobiera wszystkie SKU z PPM
   c) SKU_źródło - SKU_ppm = brakujące
   d) Dla każdego brakującego pobiera pełne dane
3. Bulk actions:
   - "Importuj jako Draft" → tworzy PendingProduct
   - "Importuj wszystkie" → batch PendingProduct
```

### 6.3 Brakujące w Źródle (Typ 3)
```
1. Użytkownik wybiera źródło
2. Background Job:
   a) Pobiera produkty PPM z powiązaniem (ProductErpData)
   b) Sprawdza czy każdy istnieje w źródle
   c) Jeśli nie → orphan
3. Bulk actions:
   - "Usuń powiązanie" → usuwa ProductErpData
   - "Eksportuj do źródła" → wykorzystuje istniejące joby sync
```

---

## 7. UI/UX (Dark Theme Admin)

### 7.1 Zakładki
| Tab | Zawartość |
|-----|-----------|
| Skan Powiązań | Selector źródła + przycisk + wyniki |
| Brak w PPM | Selector źródła + przycisk + wyniki |
| Brak w Źródle | Selector źródła + przycisk + wyniki |
| Historia | Lista poprzednich skanów |

### 7.2 Komponenty
- **Source Selector**: Dropdown z ERP connections + PrestaShop shops
- **Progress Bar**: Real-time progress z JobProgress
- **Results Table**: Paginowana tabela z checkboxami
- **Diff Viewer**: Modal pokazujący różnice PPM vs Źródło
- **Bulk Actions**: Toolbar z przyciskami akcji masowych

---

## 8. ROUTING I UPRAWNIENIA

### 8.1 Routing
```php
// routes/web.php - w admin group
Route::get('/scan-products', \App\Http\Livewire\Admin\Scan\ScanProductsPanel::class)
    ->name('scan-products');
```

### 8.2 Uprawnienia
```php
// config/permissions/scan.php
return [
    'admin.scan.view' => 'Przeglądanie skanów',
    'admin.scan.start' => 'Uruchamianie skanów',
    'admin.scan.link' => 'Łączenie produktów',
    'admin.scan.create' => 'Import produktów',
    'admin.scan.bulk' => 'Akcje masowe',
];
```

---

## 9. FAZY IMPLEMENTACJI

### FAZA 1: Podstawy (3-4 dni) ✅ COMPLETED
- [x] 1.1 Migracje bazy danych
- [x] 1.2 Model `ProductScanSession`
- [x] 1.3 Model `ProductScanResult`
- [x] 1.4 Interface `ScanSourceInterface`
- [x] 1.5 Adapter `SubiektGTScanSource`
- [x] 1.6 Podstawowy `ProductScanService`

### FAZA 2: Jobs (2-3 dni) ✅ COMPLETED 2026-02-03
- [x] 2.1 `ScanProductLinksJob`
- [x] 2.2 `ScanMissingInPpmJob`
- [x] 2.3 `ScanMissingInSourceJob`
- [x] 2.4 Kolejka `scan` w config/queue.php

### FAZA 3: UI (3-4 dni) ✅ COMPLETED 2026-02-03
- [x] 3.1 `ScanProductsPanel` Livewire
- [x] 3.2 Widoki Blade (dark theme)
- [x] 3.3 Progress tracking (wire:poll)
- [x] 3.4 Bulk actions
- [x] 3.5 CSS styling

### FAZA 4: Pozostałe źródła (2-3 dni) ✅ COMPLETED 2026-02-03
- [x] 4.1 Adapter `PrestaShopScanSource`
- [x] 4.2 Adapter `BaselinkerScanSource`
- [x] 4.3 Adapter `DynamicsScanSource`
- [x] 4.4 Test wszystkich źródeł

### FAZA 5: Polish (1-2 dni) ✅ COMPLETED 2026-02-03
- [x] 5.1 Permissions i sidebar
- [x] 5.2 Historia skanów
- [x] 5.3 Export wyników CSV
- [x] 5.4 Chrome DevTools verification
- [x] 5.5 Deployment

**Szacowany czas:** 11-16 dni roboczych

---

## 10. KLUCZOWE DECYZJE

| Decyzja | Wartość | Uzasadnienie |
|---------|---------|--------------|
| Klucz matchowania | **SKU** | Architektura SKU-FIRST |
| Skala | **20 000+** | Background jobs + chunked |
| Import workflow | **PendingProduct** | Zgodne z istniejącym systemem |
| Warianty | **Tak** | Pełne wsparcie ProductVariant |
| Historia | **Tak** | Zapisywanie wyników |
| Konflikty | **Diff view** | Pokazuj różnice, nie blokuj |

---

## 11. WERYFIKACJA (po implementacji)

- [ ] Skan powiązań dla Subiekt GT działa
- [ ] Skan powiązań dla PrestaShop działa
- [ ] Skan brakujących w PPM importuje do PendingProduct
- [ ] Skan brakujących w źródle wykrywa orphany
- [ ] Bulk link działa poprawnie
- [ ] Progress bar aktualizuje się w czasie rzeczywistym
- [ ] Historia skanów zapisuje się poprawnie
- [ ] Dark theme admin consistent
- [ ] Chrome DevTools - brak błędów konsoli

---

## 12. POWIĄZANE DOKUMENTY

- [JOBS_AND_WORKERS.md](.Release_docs/JOBS_AND_WORKERS.md)
- [SKU_ARCHITECTURE_GUIDE.md](_DOCS/SKU_ARCHITECTURE_GUIDE.md)
- [SUBIEKT_GT_DATABASE_SCHEMA.md](_DOCS/SUBIEKT_GT_DATABASE_SCHEMA.md)
- [INTEGRATION_LABELS.md](.Release_docs/INTEGRATION_LABELS.md) - **NOWE** (2026-02-03)

---

## 13. PLAN: Zastąpienie Hardcoded Labels Integracji

**Data:** 2026-02-03
**Status:** ⏳ DO WYKONANIA
**Skill:** `integration-labels` (utworzony)
**Dokumentacja:** `.Release_docs/INTEGRATION_LABELS.md` (utworzona)

### 13.1 Analiza Codebase - Wyniki

#### A. Hardcoded nazwy ERP (HIGH/MEDIUM risk)

| Plik | Linia | Problem | Ryzyko |
|------|-------|---------|--------|
| `resources/views/livewire/admin/erp/erp-manager.blade.php` | 180-184 | $typeLabels z hardcoded nazwami | HIGH |
| `app/Services/ERP/ERPServiceManager.php` | 221-228 | getSupportedERPTypes() | MEDIUM |
| `app/Models/ProductScanSession.php` | 156-164 | getAvailableSourceTypes() | MEDIUM |

#### B. Brakujące label_color/label_icon w eager loading (HIGH risk)

| Plik | Linia | Problem |
|------|-------|---------|
| `app/Http/Livewire/Admin/Suppliers/Traits/BusinessPartnerProductsTrait.php` | 77-78 | erpData + shopData bez labels |
| `app/Http/Livewire/Products/Listing/ProductList.php` | 594 | shopData.shop bez labels |
| `app/Jobs/VisualEditor/BulkSyncDescriptionsJob.php` | 121-123 | shopData bez relacji shop |

#### C. Inline styles (MEDIUM risk)

| Plik | Problem |
|------|---------|
| `resources/views/livewire/admin/shops/add-shop.blade.php` | ~50 linii z hardcoded #e0ac7e |

### 13.2 Plan Naprawczy

#### PRIORYTET 1: Centralizacja nazw ERP (LOW effort, HIGH impact)

**Krok 1.1:** Dodać stałą ERP_TYPE_LABELS do ERPConnection.php
```php
public const ERP_TYPE_LABELS = [
    self::ERP_BASELINKER => 'BaseLinker',
    self::ERP_SUBIEKT_GT => 'Subiekt GT',
    self::ERP_DYNAMICS => 'Microsoft Dynamics',
];

public static function getErpTypeLabels(): array
{
    return self::ERP_TYPE_LABELS;
}
```

**Krok 1.2:** Zaktualizować ERPServiceManager.php
```php
public static function getSupportedERPTypes(): array
{
    return ERPConnection::getErpTypeLabels();
}
```

**Krok 1.3:** Zaktualizować ProductScanSession.php
```php
public static function getAvailableSourceTypes(): array
{
    $erpLabels = ERPConnection::getErpTypeLabels();
    return [
        self::SOURCE_SUBIEKT => $erpLabels[ERPConnection::ERP_SUBIEKT_GT] ?? 'Subiekt GT',
        // ...
    ];
}
```

**Krok 1.4:** Zaktualizować erp-manager.blade.php - użyć $connection->label_color zamiast $typeLabels

#### PRIORYTET 2: Dodanie label fields do eager loading (LOW effort, HIGH impact)

**Pliki do zmiany:**
1. `BusinessPartnerProductsTrait.php:77-78`
2. `ProductList.php:594`
3. `BulkSyncDescriptionsJob.php:121-123`

**Wzorzec:**
```php
// PRZED
'erpData.erpConnection:id,erp_type,instance_name'

// PO
'erpData.erpConnection:id,erp_type,instance_name,label_color,label_icon'
```

#### PRIORYTET 3: Refaktoryzacja add-shop.blade.php (MEDIUM effort)

**Krok 3.1:** Dodać CSS classes do components.css
**Krok 3.2:** Zamienić inline styles na classes

### 13.3 Pliki do Modyfikacji

| Priorytet | Plik | Zmiana |
|-----------|------|--------|
| 1 | `app/Models/ERPConnection.php` | +ERP_TYPE_LABELS, +getErpTypeLabels() |
| 1 | `app/Services/ERP/ERPServiceManager.php` | Delegacja do ERPConnection |
| 1 | `app/Models/ProductScanSession.php` | Delegacja do ERPConnection |
| 1 | `erp-manager.blade.php` | Użycie accessor label_color |
| 2 | `BusinessPartnerProductsTrait.php` | +label_color,label_icon |
| 2 | `ProductList.php` | +label_color,label_icon |
| 2 | `BulkSyncDescriptionsJob.php` | +shop relacja z labels |
| 3 | `resources/css/admin/components.css` | +shop-wizard-* classes |
| 3 | `add-shop.blade.php` | Zamiana inline → classes |

### 13.4 Weryfikacja

- [x] ERP Manager wyświetla dynamiczne kolory z bazy ✅ 2026-02-03
- [x] Product Scan używa nazw z ERPConnection ✅ 2026-02-03
- [x] BusinessPartnerPanel pokazuje kolorowe badges ✅ 2026-02-03
- [x] ProductList preview ma label colors ✅ 2026-02-03
- [x] Add Shop wizard bez inline styles (tylko dynamiczne PHP) ✅ 2026-02-03
- [x] Brak console errors w Chrome DevTools ✅ 2026-02-03 (verified on production)
