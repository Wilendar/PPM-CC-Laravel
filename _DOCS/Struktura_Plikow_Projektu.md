# STRUKTURA PLIKÓW PROJEKTU PPM-CC-Laravel

**Data utworzenia:** 2025-09-29
**Wersja:** 1.1
**Autor:** Claude Code - Dokumentacja systemowa
**Ostatnia aktualizacja:** 2025-10-22

## 📚 POWIĄZANA DOKUMENTACJA

**⚠️ ARCHITEKTURA STRON I MENU:** Zobacz [`PPM_ARCHITEKTURA_STRON_MENU.md`](PPM_ARCHITEKTURA_STRON_MENU.md) dla:
- **21 modułów tematycznych** w `_DOCS/ARCHITEKTURA_PPM/`
- **49 route'ów aplikacji** (kompletna tabela routingu)
- **7-poziomowy system uprawnień** (macierz dostępów)
- **Role-Based Dashboards** (7 wersji per rola użytkownika)
- **UI/UX Guidelines** + **Design System** + **Implementation Checklist**

Ten dokument skupia się na **strukturze plików fizycznych**, podczas gdy PPM_ARCHITEKTURA_STRON_MENU.md opisuje **architekturę funkcjonalną i routing**.

## 📋 SPIS TREŚCI

- [Wizualna Mapa Struktury](#wizualna-mapa-struktury)
- [Szczegółowy Opis Folderów](#szczegółowy-opis-folderów)
- [Pliki Konfiguracyjne](#pliki-konfiguracyjne)
- [Mapowanie do ETAP-ów](#mapowanie-do-etap-ów)
- [Konwencje Nazewnictwa](#konwencje-nazewnictwa)

---

## 🗂️ WIZUALNA MAPA STRUKTURY

```
PPM-CC-Laravel/
├── 📁 _AGENT_REPORTS/              # Raporty prac agentów
├── 📁 _DOCS/                       # Dokumentacja projektu
│   ├── 📄 AGENT_USAGE_GUIDE.md
│   ├── 📄 CODE_ORGANIZATION_RULES.md
│   ├── 📄 PPM_Color_Style_Guide.md
│   ├── 📄 SERVER_MANAGEMENT_COMMANDS.md
│   ├── 📄 Struktura_Bazy_Danych.md
│   └── 📄 _init.md
├── 📁 _ISSUES_FIXES/               # Rozwiązania problemów
├── 📁 _OTHER/                      # Pliki tymczasowe/pomocnicze
├── 📁 _REPORTS/                    # Raporty stanu prac
├── 📁 _TEST/                       # Pliki testowe
├── 📁 _TOOLS/                      # Narzędzia deployment/pomocnicze
│   ├── 📄 hostido_deploy.ps1
│   └── 📄 hostido_quick_push.ps1
├── 📁 .claude/                     # Konfiguracja Claude Code
│   ├── 📁 agents/                  # Definicje specjalistycznych agentów
│   └── 📄 settings.local.json
├── 📁 .git/                        # Git repository data
├── 📁 app/                         # Aplikacja Laravel
│   ├── 📁 Events/                  # Event classes
│   ├── 📁 Http/                    # HTTP layer
│   │   ├── 📁 Controllers/         # Standard controllers
│   │   │   └── 📁 Auth/            # Authentication controllers
│   │   ├── 📁 Livewire/            # Livewire components [GŁÓWNY UI LAYER]
│   │   │   ├── 📁 Admin/           # Panel administracyjny
│   │   │   │   ├── 📁 Api/         # API Management
│   │   │   │   ├── 📁 Backup/      # Backup Manager
│   │   │   │   ├── 📁 Customization/ # Admin Theme
│   │   │   │   ├── 📁 Dashboard/   # Admin Dashboard
│   │   │   │   ├── 📁 ERP/         # ERP Manager
│   │   │   │   ├── 📁 Maintenance/ # Database Maintenance
│   │   │   │   ├── 📁 Notifications/ # Notification Center
│   │   │   │   ├── 📁 Permissions/ # Permission Management
│   │   │   │   ├── 📁 PriceManagement/ # Price Management System
│   │   │   │   ├── 📁 Products/    # Product Management
│   │   │   │   ├── 📁 Reports/     # Reports Dashboard
│   │   │   │   ├── 📁 Roles/       # Role Management
│   │   │   │   ├── 📁 Settings/    # System Settings
│   │   │   │   ├── 📁 Shops/       # Shop Manager
│   │   │   │   └── 📁 Users/       # User Management
│   │   │   ├── 📁 Auth/            # Authentication UI
│   │   │   ├── 📁 Dashboard/       # Main Dashboard + Widgets
│   │   │   ├── 📁 Products/        # Product Management UI
│   │   │   │   ├── 📁 Categories/  # Category Management
│   │   │   │   ├── 📁 Listing/     # Product Lists
│   │   │   │   └── 📁 Management/  # Product Forms
│   │   │   └── 📁 Profile/         # User Profile
│   │   ├── 📁 Middleware/          # HTTP Middleware
│   │   └── 📁 Requests/            # Form Request Validation
│   ├── 📁 Jobs/                    # Queue Jobs
│   │   └── 📁 PrestaShop/          # PrestaShop sync jobs
│   ├── 📁 Mail/                    # Mail classes
│   ├── 📁 Models/                  # Eloquent Models [DANE]
│   ├── 📁 Policies/                # Authorization policies
│   ├── 📁 Providers/               # Service Providers
│   └── 📁 Services/                # Business Logic Services
│       ├── 📁 ERP/                 # ERP Integration Services
│       └── 📁 PrestaShop/          # PrestaShop Integration Services [ETAP_07]
│           ├── 📁 Sync/            # Sync strategies
│           ├── 📁 Mappers/         # Data mappers
│           └── 📁 Transformers/    # Data transformers
├── 📁 artisan_commands/            # Custom Artisan Commands
├── 📁 bootstrap/                   # Laravel bootstrap
├── 📁 config/                      # Configuration files
├── 📁 database/                    # Database layer
│   ├── 📁 factories/               # Model factories
│   ├── 📁 migrations/              # Database migrations [STRUKTURA DB]
│   └── 📁 seeders/                 # Database seeders
├── 📁 docs/                        # Dokumentacja techniczna
│   ├── 📁 adr/                     # Architecture Decision Records
│   ├── 📁 import/                  # Import documentation
│   ├── 📁 schema/                  # Schema documentation
│   └── 📁 security/                # Security documentation
├── 📁 Plan_Projektu/               # Plan projektu w ETAP-ach
│   ├── 📄 ETAP_01_Fundament.md
│   ├── 📄 ETAP_02_Modele_Bazy.md
│   ├── 📄 ETAP_03_Autoryzacja.md
│   ├── 📄 ETAP_04_Panel_Admin.md
│   ├── 📄 ETAP_04a_Panel_Admin_CC.md
│   ├── 📄 ETAP_05_Produkty.md
│   ├── 📄 ETAP_06_Import_Export.md
│   ├── 📄 ETAP_07_Prestashop_API.md
│   ├── 📄 ETAP_08_ERP_Integracje.md
│   ├── 📄 ETAP_09_Wyszukiwanie.md
│   ├── 📄 ETAP_10_Dostawy.md
│   ├── 📄 ETAP_11_Dopasowania.md
│   ├── 📄 ETAP_12_UI_Deploy.md
│   └── 📄 README.md
├── 📁 public/                      # Web root
│   └── 📁 build/                   # Compiled assets (Vite)
├── 📁 References/                  # Mockupy i pliki źródłowe
├── 📁 resources/                   # Frontend resources
│   ├── 📁 css/                     # Stylesheets
│   ├── 📁 js/                      # JavaScript
│   └── 📁 views/                   # Blade templates
│       ├── 📁 auth/                # Authentication views
│       ├── 📁 components/          # Blade components
│       ├── 📁 dashboard/           # Dashboard views
│       ├── 📁 debug/               # Debug views
│       ├── 📁 emails/              # Email templates
│       ├── 📁 errors/              # Error pages
│       ├── 📁 layouts/             # Layout templates
│       ├── 📁 livewire/            # Livewire views [GŁÓWNY UI]
│       │   ├── 📁 admin/           # Admin panel views
│       │   ├── 📁 auth/            # Auth views
│       │   ├── 📁 dashboard/       # Dashboard views
│       │   ├── 📁 products/        # Product views
│       │   └── 📁 profile/         # Profile views
│       ├── 📁 pages/               # Static pages
│       └── 📁 profile/             # User profile views
├── 📁 routes/                      # Route definitions
├── 📁 storage/                     # Storage files
├── 📁 tests/                       # PHPUnit tests
│   ├── 📁 Feature/                 # Feature tests
│   └── 📁 Unit/                    # Unit tests
├── 📄 .htaccess                    # Apache configuration
├── 📄 AGENTS.md                    # Instrukcje dla agentów
├── 📄 CLAUDE.md                    # Konfiguracja Claude Code
├── 📄 artisan                      # Laravel CLI
├── 📄 composer.json                # PHP dependencies
├── 📄 package.json                 # Node.js dependencies
├── 📄 vite.config.js               # Vite configuration
└── 📄 dane_hostingu.md             # Dane hostingu Hostido
```

---

## 📂 SZCZEGÓŁOWY OPIS FOLDERÓW

### 🏗️ APLIKACJA CORE (app/)

| Folder | Przeznaczenie | ETAP | Opis |
|---------|---------------|------|------|
| **Models/** | Modele danych | ETAP_02 | Eloquent models - struktura danych aplikacji |
| **Http/Livewire/** | UI Components | ETAP_04, 05 | Główny interfejs użytkownika - komponenty Livewire |
| **Services/** | Logika biznesowa | ETAP_06, 07, 08 | Serwisy integracji i logika biznesowa |
| **Jobs/** | Zadania asynchroniczne | ETAP_07, 08 | Queue jobs dla synchronizacji |

### 🎨 INTERFEJS UŻYTKOWNIKA

| Folder | Przeznaczenie | Status | Komponenty |
|---------|---------------|---------|-------------|
| **app/Http/Livewire/Admin/** | Panel administratora | ✅ COMPLETED | 12 głównych modułów |
| **app/Http/Livewire/Products/** | Zarządzanie produktami | ✅ COMPLETED | Categories, Listing, Management |
| **app/Http/Livewire/Dashboard/** | Dashboard główny | ✅ COMPLETED | Widgets, Analytics + **7 wersji role-based** (Admin, Menadżer, Redaktor, Magazynier, Handlowiec, Reklamacje, Użytkownik) |
| **resources/views/livewire/** | Templates Blade | ✅ COMPLETED | Odpowiadające pliki .blade.php |

#### 📋 ProductForm - Refactoring Modular (2025-11-21)

**STATUS:** ✅ COMPLETED - Refactoring z monolitycznego pliku (2200 linii) → Modularny system **TABS + PARTIALS**

**ARCHITEKTURA:** Conditional tab rendering (performance) + Reusable partials (DRY principle)

**📖 DOKUMENTACJA:** [`_DOCS/Site_Rules/ProductForm_REFACTORING_2025-11-22.md`](Site_Rules/ProductForm_REFACTORING_2025-11-22.md) - Pełna dokumentacja refactoringu + Critical bug case study

**STRUKTURA KATALOGÓW:**

```
resources/views/livewire/products/management/
├── product-form.blade.php              # ✅ Main orchestrator (~100 linii)
├── tabs/                               # ✅ CONDITIONAL RENDERING (tylko 1 tab w DOM)
│   ├── basic-tab.blade.php             # ✅ 53KB - SKU, Name, Slug, Manufacturer, Categories
│   ├── description-tab.blade.php       # ✅ 8KB  - Short/Full/Meta descriptions
│   ├── physical-tab.blade.php          # ✅ 8KB  - Weight, Width, Height, Depth
│   ├── attributes-tab.blade.php        # ✅ 4KB  - Product attributes system
│   ├── prices-tab.blade.php            # ✅ 8KB  - Price groups (7 grup cenowych)
│   └── stock-tab.blade.php             # ✅ 8KB  - Warehouse stock levels
└── partials/                           # ✅ ALWAYS INCLUDED (reusable components)
    ├── form-header.blade.php           # ✅ 2KB  - Breadcrumbs + Status badge + Unsaved badge
    ├── form-messages.blade.php         # ✅ 1KB  - Success/Error messages
    ├── tab-navigation.blade.php        # ✅ 2KB  - 6 tab buttons
    ├── shop-management.blade.php       # ✅ 10KB - Shop dropdown + Sync status badge
    ├── quick-actions.blade.php         # ✅ 6KB  - Sidebar: Zapisz/Aktualizuj/Wczytaj/Anuluj
    ├── product-info.blade.php          # ✅ 2KB  - Sidebar: SKU/Status/Shops info
    ├── category-tree-item.blade.php    # ✅ 5KB  - Recursive category tree node
    ├── category-browser.blade.php      # ✅ 1KB  - Category browser wrapper
    └── product-shop-tab.blade.php      # ✅ 19KB - Shop-specific data panel (legacy)
```

**ARCHITEKTURA TABS (Conditional Rendering):**

| Tab | File | Size | Odpowiedzialność |
|-----|------|------|------------------|
| **Basic** | `basic-tab.blade.php` | 53KB | SKU, Name, Slug, Manufacturer, Supplier, EAN, Tax Rate, Active/Featured checkboxes, **CATEGORIES SECTION** |
| **Description** | `description-tab.blade.php` | 8KB | Short description, Full description, Meta description |
| **Physical** | `physical-tab.blade.php` | 8KB | Weight, Width, Height, Depth (dimensions) |
| **Attributes** | `attributes-tab.blade.php` | 4KB | Product attributes (attribute system) |
| **Prices** | `prices-tab.blade.php` | 8KB | Price groups (Detaliczna, Dealer, Warsztat, etc.) |
| **Stock** | `stock-tab.blade.php` | 8KB | Warehouse stock levels (MPPTRADE, Pitbike, etc.) |

**ARCHITEKTURA PARTIALS (Always Included - Reusable):**

| Partial | File | Size | Odpowiedzialność |
|---------|------|------|------------------|
| **Form Header** | `form-header.blade.php` | 2KB | Breadcrumbs, Page title, Status badge (Aktywny/Nieaktywny), "Niezapisane zmiany" badge |
| **Form Messages** | `form-messages.blade.php` | 1KB | Success messages, Error messages, Validation errors |
| **Tab Navigation** | `tab-navigation.blade.php` | 2KB | 6 tab buttons (Basic, Description, Physical, Attributes, Prices, Stock) |
| **Shop Management** | `shop-management.blade.php` | 10KB | Dropdown wyboru sklepu (Default / B2B Test DEV / etc.), Badge sync status |
| **Quick Actions** | `quick-actions.blade.php` | 6KB | Sidebar buttons: "Zapisz zmiany", "Aktualizuj sklepy", "Wczytaj ze sklepów", "Anuluj i wróć" |
| **Product Info** | `product-info.blade.php` | 2KB | Sidebar info box: SKU, Status, Liczba sklepów |
| **Category Tree Item** | `category-tree-item.blade.php` | 5KB | Recursive category tree node (checkbox + "Ustaw główną" button + children) |
| **Category Browser** | `category-browser.blade.php` | 1KB | Category browser wrapper (if needed) |
| **Shop Tab** | `product-shop-tab.blade.php` | 19KB | Shop-specific data panel (legacy - may be deprecated) |

**KORZYŚCI REFACTORINGU:**
- ✅ **Performance:** Tylko 1 tab w DOM równocześnie (conditional rendering)
- ✅ **Maintainability:** Separation of concerns - każdy tab = 1 odpowiedzialność
- ✅ **Reusability:** Partials używane across all tabs (header, messages, actions)
- ✅ **Testability:** Łatwiejsze testowanie individual tabs
- ✅ **Code organization:** 15 plików zamiast 1 monolitycznego (2200 linii)

**LAYOUT STRUCTURE:**
```blade
<div class="category-form-main-container">  <!-- Flexbox container -->
  <div class="category-form-left-column">   <!-- flex: 1 -->
    <div class="enterprise-card p-8">
      @include('partials.tab-navigation')
      @include('partials.shop-management')

      {{-- CONDITIONAL TAB CONTENT (only 1 in DOM) --}}
      @if($activeTab === 'basic') @include('tabs.basic-tab') @endif
      @elseif($activeTab === 'description') @include('tabs.description-tab') @endif
      ...
    </div>
  </div>

  <div class="category-form-right-column">  <!-- width: 350px, sticky -->
    @include('partials.quick-actions')
    @include('partials.product-info')
  </div>
</div>
```

**⚠️ CRITICAL BUG CASE STUDY (2025-11-22):**
Refactoring wprowadził dodatkowe linie kodu w Categories Section (`basic-tab.blade.php`):
- Dodano: `$expandedCategoryIds = $this->calculateExpandedCategoryIds();`
- Dodano: Parameter `'expandedCategoryIds' => $expandedCategoryIds` w @include

**EFEKT:** 0 category checkboxes, sidepanel na dole zamiast po prawej

**ROZWIĄZANIE:** Przywrócenie DOKŁADNIE działającej wersji z commit `bdfcd42` (bez extra parametrów)

**LEKCJE:**
1. Git history is gold - ZAWSZE sprawdzaj last working commit
2. Refactoring = TYLKO structural changes (NO "improvements"!)
3. Test IMMEDIATELY po refactoringu
4. Compare parameters EXACTLY (working vs refactored)
5. Chrome DevTools MCP verification MANDATORY po deployment

**📖 SZCZEGÓŁY:** [`_DOCS/Site_Rules/ProductForm_REFACTORING_2025-11-22.md`](Site_Rules/ProductForm_REFACTORING_2025-11-22.md)

### 🗄️ BAZA DANYCH

| Folder | Przeznaczenie | Status | Liczba plików |
|---------|---------------|---------|---------------|
| **database/migrations/** | Struktura tabel | ✅ COMPLETED | 42 migracje |
| **database/seeders/** | Dane testowe | ✅ PARTIAL | 2 seeders |
| **database/factories/** | Factory patterns | ❌ TODO | - |

### 📋 PLANOWANIE I DOKUMENTACJA

| Folder | Przeznaczenie | Status | ETAP |
|---------|---------------|---------|------|
| **Plan_Projektu/** | Plan ETAP-ów | ✅ ACTIVE | 12 ETAP-ów |
| **_DOCS/** | Dokumentacja | ✅ ACTIVE | Guides, structures |
| **_AGENT_REPORTS/** | Raporty agentów | ✅ ACTIVE | 25+ raportów |
| **_ISSUES_FIXES/** | Issue tracking | ✅ ACTIVE | Known issues |

### 🔧 DEPLOYMENT I NARZĘDZIA

| Folder | Przeznaczenie | Status | Narzędzia |
|---------|---------------|---------|-----------|
| **_TOOLS/** | Scripts deployment | ✅ ACTIVE | PowerShell scripts |
| **.claude/** | Claude Code config | ✅ ACTIVE | 13 agentów |
| **config/** | Laravel config | ✅ COMPLETED | Standard + custom |

---

## 🔧 PLIKI KONFIGURACYJNE

### Kluczowe pliki konfiguracji:

| Plik | Przeznaczenie | ETAP | Status |
|------|---------------|------|--------|
| **CLAUDE.md** | Instrukcje dla Claude Code | ALL | ✅ ACTIVE |
| **composer.json** | PHP dependencies | ETAP_01 | ✅ COMPLETED |
| **vite.config.js** | Frontend build | ETAP_12 | ✅ COMPLETED |
| **.htaccess** | Apache config | ETAP_01 | ✅ COMPLETED |
| **dane_hostingu.md** | Deployment data | DEPLOYMENT | ✅ COMPLETED |

---

## 🎯 MAPOWANIE DO ETAP-ÓW

### ETAP_01 - Fundament ✅
- ✅ `composer.json`, `.htaccess`, `config/`
- ✅ `routes/`, `app/Providers/`

### ETAP_02 - Modele Bazy ✅
- ✅ `app/Models/` (30 modeli)
- ✅ `database/migrations/` (42 migracje)

### ETAP_03 - Autoryzacja ✅
- ✅ `app/Http/Livewire/Auth/`
- ✅ `resources/views/livewire/auth/`

### ETAP_04 - Panel Admin ✅
- ✅ `app/Http/Livewire/Admin/` (12 modułów)
- ✅ `resources/views/livewire/admin/`

### ETAP_05 - Produkty ✅
- ✅ `app/Http/Livewire/Products/`
- ✅ `resources/views/livewire/products/`

### ETAP_06 - Import/Export ✅ COMPLETED (jako Unified Import System w PRODUKTY)
**⚠️ ARCHITEKTURA v2.0:** Import/Export przeniesiony do sekcji PRODUKTY (nie osobny moduł)

- ✅ `app/Services/CSV/` (6 serwisów)
  - `TemplateGenerator.php` - Generowanie szablonów XLSX
  - `ImportMapper.php` - Mapowanie kolumn importu
  - `ImportValidator.php` - Walidacja danych
  - `ExportFormatter.php` - Formatowanie eksportu
  - `BulkOperationService.php` - Operacje masowe
  - `ErrorReporter.php` - Raportowanie błędów
- ✅ `app/Http/Livewire/Admin/CSV/ImportPreview.php` - Podgląd importu
- ✅ `app/Http/Controllers/Admin/CSVExportController.php` - Eksport kontroler
- ✅ `resources/views/livewire/admin/csv/` - Widoki importu
- ✅ **9 route'ów CSV** (templates, export, import)
- ✅ **Unified Import System** - CSV + XLSX w jednym interfejsie
- ✅ **Template system** - Predefiniowane szablony (POJAZDY/CZĘŚCI)

### ETAP_07 - PrestaShop API 🛠️ IN PROGRESS - FAZA 3 (75%)
**Status:** ✅ FAZA 1+2 COMPLETED | 🔄 FAZA 3 IN PROGRESS (75%)
- **FAZA 1**: ✅ Panel konfiguracyjny + Sync PPM → PrestaShop (bez zdjęć) - **COMPLETED**
- **FAZA 2**: ✅ Dynamic category picker + Reverse transformers - **COMPLETED**
- **FAZA 3**: 🔄 Import PrestaShop → PPM + Real-Time Progress - **75% (3A ✅, 3B 75%, 3C ❌)**

#### 📁 Services Layer (15 plików):
```
app/Services/PrestaShop/
├── BasePrestaShopClient.php         # ✅ Abstract base dla API client
├── PrestaShop8Client.php            # ✅ Implementacja API PrestaShop 8.x
├── PrestaShop9Client.php            # ✅ Implementacja API PrestaShop 9.x
├── PrestaShopClientFactory.php      # ✅ Factory pattern dla versioning
├── PrestaShopService.php            # ✅ Main service facade
├── PrestaShopSyncService.php        # ✅ Orchestrator synchronizacji PPM → PS
├── PrestaShopImportService.php      # ✅ Import service PS → PPM (FAZA 2)
├── Sync/                            # Strategy pattern dla sync operations
│   ├── ISyncStrategy.php            # ✅ Interface strategii sync
│   ├── ProductSyncStrategy.php      # ✅ Strategia sync produktów
│   └── CategorySyncStrategy.php     # ✅ Strategia sync kategorii
├── Mappers (3 pliki):               # Mapowanie danych PPM ↔ PrestaShop
│   ├── CategoryMapper.php           # ✅ Mapowanie kategorii
│   ├── PriceGroupMapper.php         # ✅ Mapowanie grup cenowych
│   └── WarehouseMapper.php          # ✅ Mapowanie magazynów
└── Transformers (2 pliki):          # Transformacja danych bidirectional
    ├── ProductTransformer.php       # ✅ Bidirectional Product ↔ PrestaShop
    └── CategoryTransformer.php      # ✅ Bidirectional Category ↔ PrestaShop
```

#### 📁 Queue Jobs (9 plików):
```
app/Jobs/PrestaShop/
├── SyncProductToPrestaShop.php      # ✅ Job sync pojedynczego produktu PPM → PS
├── BulkSyncProducts.php             # ✅ Job sync masowego produktów PPM → PS
├── SyncProductsJob.php              # ✅ Alternative bulk sync wrapper
├── SyncCategoryToPrestaShop.php     # ✅ Job sync kategorii PPM → PS
├── DeleteProductFromPrestaShop.php  # ✅ Job usuwania produktu z PS
├── BulkImportProducts.php           # ✅ Job import masowego PS → PPM (FAZA 3A)
├── BulkCreateCategories.php         # ✅ Job tworzenia kategorii masowo
├── AnalyzeMissingCategories.php     # ✅ Job analizy brakujących kategorii
└── ExpirePendingCategoryPreview.php # ✅ Job czyszczenia preview cache
```

#### 📁 Controllers/Routes:
```
app/Http/Controllers/
└── WebhookController.php            # Webhook endpoint dla PrestaShop

routes/
└── api.php                          # Route: POST /api/webhooks/prestashop/{shop_id}
```

#### 📁 Livewire Components (5 plików):
```
app/Http/Livewire/Admin/Shops/
├── ShopManager.php                  # ✅ Shop management + connection health
├── AddShop.php                      # ✅ Add/Edit shop wizard + PS connection test
├── SyncController.php               # ✅ Sync operations dashboard + manual triggers
├── BulkExport.php                   # ✅ Bulk export produktów PPM → PS (FAZA 1)
└── ImportManager.php                # ✅ Import manager PS → PPM (FAZA 3)
```

#### 📁 Views (Extensions):
```
resources/views/livewire/admin/shops/
├── shop-manager.blade.php           # ✅ EXISTS - dodanie PS sync UI
├── add-shop.blade.php               # ✅ EXISTS - dodanie PS wizard steps
└── sync-controller.blade.php        # ✅ EXISTS - dodanie PS sync controls
```

#### 📁 Database (Migrations):
```
database/migrations/
├── 2025_XX_XX_extend_prestashop_shops_for_sync.php
├── 2025_XX_XX_create_shop_mappings_table.php
├── 2025_XX_XX_create_product_sync_status_table.php
└── 2025_XX_XX_create_sync_logs_table.php
```

**IMPLEMENTACJA PER FAZA:**
- ✅ **FAZA 1 COMPLETED** (2025-10-08):
  - ✅ Database structure (4 tables: shop_mappings, product_sync_status, sync_logs, product_shop_data extensions)
  - ✅ Base API clients (BasePrestaShopClient, PrestaShop8Client, PrestaShop9Client)
  - ✅ Product/Category sync strategies (ProductSyncStrategy, CategorySyncStrategy)
  - ✅ ShopManager UI extension + BulkExport component
  - ✅ Sync PPM → PrestaShop (products, categories, bez zdjęć)
  - ✅ Queue jobs infrastructure (9 jobs)
  - ✅ Logging system (sync_logs table)

- ✅ **FAZA 2 COMPLETED** (2025-10-03):
  - ✅ Dynamic category picker w ProductForm
  - ✅ Reverse transformers (PrestaShop → PPM data)
  - ✅ PrestaShopImportService implementation
  - ✅ Category API endpoints

- 🔄 **FAZA 3 IN PROGRESS** (75% - 2025-10-08):
  - ✅ 3A: Import PrestaShop → PPM (BulkImportProducts job)
  - 🔄 3B: Real-Time Progress tracking (75% - deployed, pending user test)
  - ❌ 3C: Queue monitoring & optimization (not started)

- ❌ **FAZA 4+ PLANNED** (future):
  - ❌ Images sync
  - ❌ Webhooks
  - ❌ Advanced monitoring dashboard

### ETAP_08 - ERP Integracje ⏳ IN PROGRESS
- 🛠️ `app/Services/ERP/`
- 🛠️ `app/Models/ERPConnection.php`

### ETAP_09 - Wyszukiwanie ❌
- ❌ Search components
- ❌ Advanced filters

### ETAP_10 - Dostawy ❌
- ❌ Delivery management
- ❌ Container tracking

### ETAP_11 - Dopasowania ❌
- ❌ Vehicle matching system
- ❌ Compatibility database

### ETAP_12 - UI/Deploy ⏳
- ✅ `public/build/`
- 🛠️ Production optimization

---

## 📏 KONWENCJE NAZEWNICTWA

### Foldery:
- **PascalCase** dla modułów: `Admin/`, `Products/`
- **kebab-case** dla widoków: `price-management/`
- **snake_case** dla bazy: `product_shop_data`

### Pliki:
- **PascalCase** dla klas: `ProductManager.php`
- **kebab-case** dla widoków: `product-form.blade.php`
- **snake_case** dla migracji: `create_products_table.php`

### Namespace Convention:
```
App\Http\Livewire\Admin\Products\ProductManager
App\Models\Product
App\Services\PrestaShop\ProductSync
```

---

## 🔄 AKTUALIZACJA DOKUMENTACJI

**ZASADA:** Ta dokumentacja MUSI być aktualizowana przy każdej zmianie struktury plików w ramach ETAP-ów.

**PROCES:**
1. Przed rozpoczęciem ETAP → sprawdź obecną strukturę
2. Zaplanuj nowe pliki/foldery w kontekście ETAP
3. Po ukończeniu ETAP → zaktualizuj tę dokumentację
4. Potwierdź zgodność z planem ETAP

**OSTATNIA AKTUALIZACJA:** 2025-11-22
- ✅ Dodano sekcję ProductForm - Refactoring Modular (2025-11-21) - TABS + PARTIALS architecture
- ✅ Udokumentowano critical bug case study (categories not rendering) + 5 lessons learned
- ✅ Dodano strukturę katalogów tabs/ (6 plików) + partials/ (9 plików)
- ✅ Dodano tabele odpowiedzialności TABS vs PARTIALS
- ✅ Dodano layout structure pattern + korzyści refactoringu
- 📖 Cross-reference do [`ProductForm_REFACTORING_2025-11-22.md`](Site_Rules/ProductForm_REFACTORING_2025-11-22.md)

**POPRZEDNIE AKTUALIZACJE:**
- 2025-10-22: Referencja do ARCHITEKTURA_PPM/ (21 modułów)
- 2025-10-22: ETAP_06 → COMPLETED (Unified Import System)
- 2025-10-22: ETAP_07 → FAZA 1+2 COMPLETED, FAZA 3 @ 75%
- 2025-10-22: Role-Based Dashboards (7 wersji)
- 2025-10-22: System CSV (6 serwisów + 9 route'ów)
- ⚠️ **AKTUALNY STATUS:** ETAP_04 ✅, ETAP_05 ✅, ETAP_06 ✅, ETAP_07 @ 75% 🔄, ETAP_08 ⏳

---

**AUTOR:** Claude Code System
**PROJEKT:** PPM-CC-Laravel
**WERSJA:** Enterprise 1.1