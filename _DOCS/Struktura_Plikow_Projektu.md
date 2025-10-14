# STRUKTURA PLIKÓW PROJEKTU PPM-CC-Laravel

**Data utworzenia:** 2025-09-29
**Wersja:** 1.0
**Autor:** Claude Code - Dokumentacja systemowa

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
| **app/Http/Livewire/Dashboard/** | Dashboard główny | ✅ COMPLETED | Widgets, Analytics |
| **resources/views/livewire/** | Templates Blade | ✅ COMPLETED | Odpowiadające pliki .blade.php |

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

### ETAP_06 - Import/Export ⏳
- 🛠️ `app/Services/` (Import services)
- ❌ Import wizards, mapowanie XLSX

### ETAP_07 - PrestaShop API 🛠️ IN PROGRESS - FAZA 1
**Status:** Panel konfiguracyjny + synchronizacja produktów/kategorii (bez zdjęć)

#### 📁 Services Layer:
```
app/Services/PrestaShop/
├── BasePrestaShopClient.php         # Abstract base dla API client
├── PrestaShop8Client.php            # Implementacja API PrestaShop 8.x
├── PrestaShop9Client.php            # Implementacja API PrestaShop 9.x
├── PrestaShopClientFactory.php      # Factory pattern dla versioning
├── PrestaShopSyncService.php        # Orchestrator synchronizacji
├── Sync/                            # Strategy pattern dla sync operations
│   ├── ISyncStrategy.php            # Interface strategii sync
│   ├── ProductSyncStrategy.php      # Strategia sync produktów
│   ├── CategorySyncStrategy.php     # Strategia sync kategorii
│   └── ConflictResolver.php         # Rozwiązywanie konfliktów sync
├── Mappers/                         # Mapowanie danych PPM ↔ PrestaShop
│   ├── CategoryMapper.php           # Mapowanie kategorii
│   ├── PriceGroupMapper.php         # Mapowanie grup cenowych
│   ├── WarehouseMapper.php          # Mapowanie magazynów
│   └── AttributeMapper.php          # Mapowanie atrybutów
└── Transformers/                    # Transformacja danych
    ├── ProductTransformer.php       # Transform Product → PrestaShop format
    └── CategoryTransformer.php      # Transform Category → PrestaShop format
```

#### 📁 Queue Jobs:
```
app/Jobs/PrestaShop/
├── SyncProductToPrestaShop.php      # Job sync pojedynczego produktu
├── BulkSyncProducts.php             # Job sync masowego produktów
├── SyncCategoryToPrestaShop.php     # Job sync kategorii
└── ProcessWebhookEvent.php          # Job przetwarzania webhooków
```

#### 📁 Controllers/Routes:
```
app/Http/Controllers/
└── WebhookController.php            # Webhook endpoint dla PrestaShop

routes/
└── api.php                          # Route: POST /api/webhooks/prestashop/{shop_id}
```

#### 📁 Livewire Components (Extensions):
```
app/Http/Livewire/Admin/Shops/
├── ShopManager.php                  # ✅ EXISTS - rozszerzenie o sync controls
├── AddShop.php                      # ✅ EXISTS - rozszerzenie o PS connection wizard
└── SyncController.php               # ✅ EXISTS - rozszerzenie o PS sync operations
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

**FAZA 1 SCOPE:**
- ✅ Database structure (4 tables)
- ⏳ Base API clients (PS8/PS9)
- ⏳ Product/Category sync strategies
- ⏳ ShopManager UI extension
- ❌ Images sync (FAZA 2)
- ❌ Webhooks (FAZA 3)
- ❌ Advanced monitoring (FAZA 3)

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

**OSTATNIA AKTUALIZACJA:** 2025-10-01 (ETAP_04 completed, ETAP_07 FAZA 1 in progress, ETAP_08 in progress)

---

**AUTOR:** Claude Code System
**PROJEKT:** PPM-CC-Laravel
**WERSJA:** Enterprise 1.0