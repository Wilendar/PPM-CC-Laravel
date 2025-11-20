# PPM - PROJECT KNOWLEDGE & ARCHITECTURE

**Project:** PrestaShop Product Manager (PPM)
**Version:** 1.0 (Development)
**Last Updated:** 2025-11-04
**Status:** ~35% Complete (ETAP 01-04 ✅, ETAP 05 85%)

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Core Features](#core-features)
5. [Data Model](#data-model)
6. [Integration Architecture](#integration-architecture)
7. [Frontend Architecture](#frontend-architecture)
8. [Security & Authorization](#security--authorization)
9. [Deployment Architecture](#deployment-architecture)
10. [Development Workflow](#development-workflow)

---

## 1. Executive Summary

### What is PPM?

PPM (PrestaShop Product Manager) is an enterprise-class centralized product management system for MPP TRADE organization. It serves as the single source of truth for managing products across multiple PrestaShop shops and ERP systems simultaneously.

### Problem Solved

**BEFORE PPM:**
- Manual product entry in each PrestaShop shop (5+ shops)
- Inconsistent product data across shops
- Time-consuming updates (change one product = edit in 5 places)
- No central inventory management
- Difficult ERP integration (Baselinker, Subiekt GT)

**AFTER PPM:**
- Single product entry, multi-shop sync
- Consistent product data (single source of truth)
- One-click updates across all shops
- Central inventory with multi-warehouse support
- Automated ERP integration

### Key Statistics

- **Codebase:** ~300-400k LOC
- **Livewire Components:** 60+
- **Database Tables:** 88 (via migrations)
- **Seeders:** 18 (demo data ready)
- **Planned Capacity:** 100,000+ products
- **PrestaShop Shops:** Multi-shop support (unlimited)
- **Price Groups:** 8 (Detaliczna, Dealer Standard/Premium, etc.)
- **Warehouses:** 6 default + custom
- **Roles:** 7 (Admin → User)
- **Permissions:** 47 granular permissions

---

## 2. System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        PPM Web Application                       │
│                    (Laravel 12 + Livewire 3)                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │   Product    │  │  Category    │  │   Variant    │         │
│  │  Management  │  │     Tree     │  │    System    │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │ Multi-Store  │  │ Price Groups │  │  Warehouse   │         │
│  │     Data     │  │  Management  │  │    Stock     │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
│                                                                  │
└────────────┬────────────────────────────────────────────────────┘
             │
             │ API Integration Layer
             │
    ┌────────┴──────────────────────────────────┐
    │                                            │
    v                                            v
┌────────────────┐                    ┌────────────────────┐
│  PrestaShop    │                    │   ERP Systems      │
│  Multi-Shop    │                    │  (Baselinker, GT)  │
│                │                    │                    │
│  • Shop 1      │                    │  • Baselinker      │
│  • Shop 2      │                    │  • Subiekt GT      │
│  • Shop 3...   │                    │  • MS Dynamics     │
└────────────────┘                    └────────────────────┘
```

### Layer Architecture (MVC + Service Layer)

```
┌─────────────────────────────────────────┐
│         Presentation Layer              │
│  (Livewire Components + Blade Views)    │
│                                         │
│  • 60+ Livewire Components             │
│  • Alpine.js for UI state              │
│  • Tailwind CSS + Custom CSS           │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────v───────────────────────┐
│          Application Layer              │
│      (Controllers + Services)           │
│                                         │
│  • Minimal Controllers (slim)          │
│  • Fat Services (business logic)       │
│  • Transformers (data mapping)         │
│  • Mappers (system integration)        │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────v───────────────────────┐
│           Domain Layer                  │
│     (Models + Business Rules)           │
│                                         │
│  • 52 Eloquent Models                  │
│  • SKU-first architecture              │
│  • Trait Composition (concerns)        │
│  • Policies (authorization)            │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────v───────────────────────┐
│       Infrastructure Layer              │
│   (Database + External Systems)         │
│                                         │
│  • MySQL/MariaDB (88 tables)           │
│  • Redis (cache/queues)                │
│  • PrestaShop API (8.x, 9.x)           │
│  • ERP API (Baselinker, Subiekt)       │
└─────────────────────────────────────────┘
```

### Module Architecture

PPM consists of 21 functional modules:

**Core Modules (ETAP 01-04 - Completed):**
1. **Authentication & Authorization** - Spatie Permissions (7 roles, 47 permissions)
2. **Dashboard** - Admin dashboard with widgets
3. **Product Management** - CRUD products (SKU-first)
4. **Category Management** - 5-level tree hierarchy
5. **Price Group Management** - 8 price groups
6. **Warehouse Management** - Multi-warehouse stock
7. **User Management** - User CRUD + permissions

**Integration Modules (ETAP 07-08 - In Progress):**
8. **PrestaShop Shops** - Multi-shop configuration
9. **PrestaShop Sync** - Product/category synchronization
10. **Baselinker Integration** - ERP integration (priority #1)
11. **Subiekt GT Integration** - Polish ERP
12. **MS Dynamics Integration** - Enterprise ERP

**Advanced Modules (ETAP 05-06 - 85%):**
13. **Variant System** - Product variants (attributes, combinations)
14. **Feature System** - Product features (vehicle compatibility)
15. **Compatibility System** - Vehicle model matching
16. **CSV Import/Export** - Bulk operations

**Future Modules (ETAP 09-12 - Planned):**
17. **Search Engine** - Intelligent search with fuzzy matching
18. **Delivery System** - Container management + customs
19. **Order System** - Order + reservations
20. **Complaint System** - RMA management
21. **Reporting & Analytics** - Business intelligence

---

## 3. Technology Stack

### Backend

**Framework:**
- Laravel 12.x (latest stable)
- PHP 8.3.23

**Database:**
- Production: MariaDB 10.11.13
- Development: MySQL 8.x

**Authentication:**
- Laravel Sanctum 4.0 (API tokens)
- Laravel Socialite 5.15 (OAuth - future)
- Spatie Laravel Permission 6.0 (roles/permissions)

**Queue & Cache:**
- Redis (primary)
- Database driver (fallback)

**Key Packages:**
- **maatwebsite/excel 3.1** - XLSX import/export (PhpSpreadsheet)
- **intervention/image 3.0** - Image processing
- **laravel/telescope 5.0** - Debug dashboard
- **phpstan/phpstan 1.10** - Static analysis
- **friendsofphp/php-cs-fixer 3.48** - Code style

### Frontend

**UI Framework:**
- **Livewire 3.x** (PRIMARY - 60+ components)
- Blade Templates

**JavaScript:**
- Alpine.js 3.13 (client-side UI state)
- @alpinejs/persist (state persistence)
- Axios 1.6.4 (HTTP client)

**CSS:**
- Tailwind CSS 3.4.17
- Custom CSS (design system)
- PostCSS

**Build Tool:**
- Vite 5.4.20 (LOCAL ONLY!)

**Component Library:**
- vanilla-colorful 0.7.2 (color picker)

### Development Tools

**Testing:**
- PHPUnit (unit + feature tests)
- Playwright 1.55.1 (E2E testing)

**Code Quality:**
- PHPStan (level 8 static analysis)
- PHP-CS-Fixer (PSR-12 compliance)

**Debugging:**
- Laravel Telescope (requests, queries, exceptions)
- Custom logging system

---

## 4. Core Features

### 4.1 Product Management (ETAP 05)

**ProductForm Component** (Refactored: 2182 → 250 lines!)
- 12-tab interface
- Trait composition pattern
- Service injection
- Real-time validation

**Tabs:**
1. Basic Info (SKU, nazwa, typ produktu)
2. Descriptions (HTML WYSIWYG)
3. Categories (5-level tree picker)
4. Prices (8 price groups)
5. Stock (multi-warehouse)
6. Images (gallery manager)
7. Variants (attribute combinations)
8. Features (vehicle compatibility)
9. Multi-Store (per-shop overrides)
10. SEO (meta tags, URLs)
11. Related Products (cross-sell, upsell)
12. History (audit log)

**SKU-First Architecture:**
- SKU = primary identifier (not database ID!)
- Conflict detection by SKU
- Import/Export uses SKU
- Multi-system sync via SKU

### 4.2 Category System

**CategoryTree Component:**
- 5-level hierarchy (Kategoria → Kategoria4)
- Drag & drop reordering
- Per-shop category assignments
- Parent-child validation

**Category Picker:**
- Reusable component
- Multi-select support
- Shop-specific filtering
- Conflict detection

### 4.3 Multi-Store System (FAZA 1.5)

**ProductShopData Model:**
- Per-shop product data
- Override nazwa/opis/cena
- Shop-specific categories
- Sync status tracking

**Sync Status:**
- `synced` - Up to date
- `pending` - Changes awaiting sync
- `failed` - Sync error
- `not_synced` - Never synced

**Conflict Resolution:**
- Detect local vs remote changes
- Manual conflict resolution UI
- Pending changes system

### 4.4 Variant System (ETAP 05b)

**AttributeType & AttributeValue:**
- Kolor (Czerwony, Niebieski, Zielony...)
- Rozmiar (S, M, L, XL...)
- Materiał (Bawełna, Poliester...)

**ProductVariant:**
- Combination matrix (Kolor × Rozmiar)
- SKU variantu (auto-generated)
- Variant prices (per price group)
- Variant stock (per warehouse)
- Variant images

**PrestaShop Mapping:**
- Map AttributeType → PS attribute_group
- Map AttributeValue → PS attribute
- Sync combinations to PS product_attribute

### 4.5 Price Group System

**8 Price Groups:**
1. Detaliczna (retail)
2. Dealer Standard
3. Dealer Premium
4. Warsztat Standard
5. Warsztat Premium
6. Szkółka-Komis-Drop
7. Pracownik (employee discount)
8. Custom (configurable)

**Price Management:**
- Base price (cena)
- Promotional price (cena_promocyjna)
- Group-specific prices
- Price history tracking
- Bulk price updates

### 4.6 Warehouse & Stock System

**Default Warehouses:**
1. MPPTRADE (main warehouse)
2. Pitbike.pl
3. Cameraman
4. Otopit
5. INFMS
6. Reklamacje (RMA)
+ Custom warehouses

**Stock Operations:**
- Stock levels per warehouse
- Stock movements (in/out/transfer)
- Stock reservations (orders, containers)
- Stock history tracking

---

## 5. Data Model

### Core Entities (ERD Simplified)

```
┌──────────────┐
│   Product    │ ─── SKU (unique, business key)
└──────┬───────┘
       │
       ├─── ProductShopData (1:N) ─── PrestaShopShop
       ├─── ProductPrice (1:N) ─── PriceGroup
       ├─── ProductStock (1:N) ─── Warehouse
       ├─── ProductVariant (1:N) ─── VariantAttribute
       ├─── ProductFeature (1:N) ─── FeatureType + FeatureValue
       ├─── VehicleCompatibility (1:N) ─── VehicleModel
       └─── Category (N:M) via product_categories
```

### Product Model (Refactored with Traits)

```php
// app/Models/Product.php (250 lines - was 2182!)
class Product extends Model
{
    use HasPricing;           // 8 price groups
    use HasStock;             // Multi-warehouse
    use HasCategories;        // 5-level hierarchy
    use HasVariants;          // Master-Variant pattern
    use HasFeatures;          // EAV attributes
    use HasCompatibility;     // Vehicle compatibility
    use HasMultiStore;        // Per-shop data
    use HasSyncStatus;        // PrestaShop sync tracking

    // Only coordination logic here!
}
```

**Traits (Concerns):**
- `HasPricing.php` - Relationships to ProductPrice, PriceGroup
- `HasStock.php` - Relationships to ProductStock, Warehouse
- `HasCategories.php` - Category relationships + validation
- `HasVariants.php` - Variant logic + combinations
- `HasFeatures.php` - Feature relationships (EAV)
- `HasCompatibility.php` - Vehicle compatibility
- `HasMultiStore.php` - Per-shop data + sync status
- `HasSyncStatus.php` - Sync status tracking

### Key Database Tables

**Products & Variants:**
- `products` (main table - SKU as business key)
- `product_variants` (color × size combinations)
- `product_types` (dynamic types, not ENUM!)
- `product_shop_data` (per-shop overrides)

**Categories:**
- `categories` (5-level hierarchy via parent_id)
- `product_categories` (pivot + shop_id support)
- `product_shop_categories` (per-shop assignments)

**Pricing:**
- `price_groups` (8 groups)
- `product_prices` (product × price_group)
- `price_history` (audit trail)

**Stock:**
- `warehouses` (6 default + custom)
- `product_stock` (product × warehouse)
- `stock_movements` (in/out/transfer history)
- `stock_reservations` (orders, containers)

**Variants & Features:**
- `attribute_types` (Kolor, Rozmiar, Materiał)
- `attribute_values` (Czerwony, S, Bawełna)
- `variant_attributes` (variant × attribute_value)
- `feature_types` (Model, Oryginał, Zamiennik)
- `feature_values` (BMW E46, 51717140470)
- `product_features` (product × feature)

**Vehicle Compatibility:**
- `vehicle_models` (BMW E46, Audi A4, etc.)
- `compatibility_attributes` (Engine, Year, Body)
- `vehicle_compatibility` (product × vehicle)
- `vehicle_compatibility_cache` (performance)

**PrestaShop Integration:**
- `prestashop_shops` (shop definitions)
- `shop_mappings` (category, price, warehouse mappings)
- `sync_jobs` (async sync tracking)
- `sync_logs` (detailed sync logs)
- `prestashop_attribute_group_mapping` (AttributeType → PS)
- `prestashop_attribute_value_mapping` (AttributeValue → PS)

**System:**
- `users` (Spatie permissions)
- `roles` + `permissions` + `role_has_permissions`
- `system_settings` (centralized config)
- `backup_jobs` (automated backups)
- `maintenance_tasks` (DB health)
- `admin_notifications` (alerts)

---

## 6. Integration Architecture

### 6.1 PrestaShop Integration

**API Clients:**
```php
BasePrestaShopClient (abstract)
├── PrestaShop8Client (PS 8.x specific)
└── PrestaShop9Client (PS 9.x specific)

PrestaShopClientFactory → creates appropriate client
```

**Transformers:**
- `ProductTransformer` - PPM Product → PrestaShop product
- `CategoryTransformer` - PPM Category → PrestaShop category

**Mappers:**
- `CategoryMapper` - Map PPM category_id ↔ PS id_category
- `PriceGroupMapper` - Map PPM price_group ↔ PS id_group
- `WarehouseMapper` - Map PPM warehouse ↔ PS id_warehouse

**Sync Services:**
- `PrestaShopSyncService` - Main sync orchestrator
- `PrestaShopImportService` - Import from PS → PPM
- `PrestaShopAttributeSyncService` - Sync attributes/combinations

**Sync Jobs (Async):**
```php
SyncProductToPrestaShop (queued job)
├── Load product
├── Transform to PS format
├── Send via API
├── Update sync status
└── Log result
```

### 6.2 ERP Integration

**Planned Integrations:**
1. **Baselinker** (Priority #1)
   - Product sync (PPM → Baselinker)
   - Order import (Baselinker → PPM)
   - Stock sync (bidirectional)

2. **Subiekt GT** (Polish market)
   - Product export (PPM → Subiekt)
   - Invoice import (Subiekt → PPM)

3. **Microsoft Dynamics** (Enterprise)
   - Full ERP integration
   - Advanced business logic

**BaselinkerService (Stub):**
```php
// app/Services/ERP/BaselinkerService.php
class BaselinkerService
{
    public function syncProduct(Product $product): bool
    {
        // TODO: Implement Baselinker API integration
    }

    public function importOrders(): Collection
    {
        // TODO: Implement order import
    }
}
```

---

## 7. Frontend Architecture

### 7.1 Livewire 3.x Architecture

**Component Organization:**
```
app/Livewire/
├── Dashboard/
│   └── AdminDashboard.php
├── Products/
│   ├── Listing/ProductList.php
│   ├── Management/ProductForm.php (REFACTORED!)
│   └── Categories/CategoryTree.php
├── Admin/
│   ├── Shops/ShopManager.php
│   ├── Variants/AttributeSystemManager.php
│   └── Users/UserList.php
└── Components/
    ├── CategoryPicker.php (reusable)
    └── JobProgressBar.php (reusable)
```

**Patterns Used:**
- **Single Responsibility** - Max 300 lines per component
- **Trait Composition** - ProductForm pattern (4 traits)
- **Service Injection** - Business logic in services
- **Computed Properties** - `#[Computed]` attribute (Livewire 3)
- **Event Coordination** - `$this->dispatch()` syntax

### 7.2 Blade Templates

**Layout Structure:**
```blade
layouts/
├── app.blade.php (main layout)
├── admin.blade.php (admin panel)
└── guest.blade.php (authentication)

livewire/
├── products/
│   ├── management/
│   │   ├── product-form.blade.php
│   │   └── tabs/ (12 tab partials)
│   └── categories/
│       └── category-tree.blade.php
└── admin/
    └── ... (admin panel views)
```

### 7.3 CSS Architecture

**Design System:**
```css
/* CSS Variables (Design Tokens) */
:root {
    /* Z-index Scale */
    --z-dropdown: 1000;
    --z-modal-overlay: 1050;
    --z-tooltip: 1070;

    /* Brand Colors */
    --color-brand-500: #e0ac7e; /* MPP Orange */

    /* Semantic Colors */
    --color-success: #10b981;
    --color-error: #ef4444;
}
```

**Component Library:**
- `.enterprise-card` - Card system
- `.tabs-enterprise` - Tab navigation
- `.btn-enterprise-primary/secondary/danger` - Buttons
- `.modal-overlay` + `.modal-content` - Modals

**File Organization:**
```
resources/css/
├── app.css (Tailwind directives + imports)
├── admin/
│   ├── layout.css
│   └── components.css
├── products/
│   └── category-form.css
└── components/
    ├── modal.css
    ├── buttons.css
    └── tabs.css
```

### 7.4 Alpine.js Integration

**Usage Pattern:**
- **Client-side UI state** (modals, dropdowns, tabs)
- **Livewire coordination** (Alpine triggers Livewire methods)
- **Animations** (transitions, reveals)

**Example:**
```blade
<div x-data="{ open: false }">
    <button @click="open = true">Open Modal</button>

    <div x-show="open" class="modal-overlay">
        <div class="modal-content">
            <livewire:product.form />
            <button @click="open = false">Close</button>
        </div>
    </div>
</div>
```

---

## 8. Security & Authorization

### 8.1 Spatie Permissions

**7 Roles (Hierarchical):**
1. **Admin** - Full access
2. **Menadżer** - Product management + import/export
3. **Redaktor** - Edit descriptions/images
4. **Magazynier** - Delivery panel
5. **Handlowiec** - Container reservations
6. **Reklamacje** - RMA panel
7. **User** - Read-only + search

**47 Permissions (Granular):**
- `products.view/create/update/delete`
- `categories.view/create/update/delete`
- `shops.manage`
- `prices.manage`
- `users.manage`
- `system.settings`
- ... (47 total)

### 8.2 Policies

**Authorization Patterns:**
```php
// BasePolicy.php - Shared logic
class BasePolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->hasRole('Admin');
    }
}

// ProductPolicy.php
class ProductPolicy extends BasePolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete') || $this->isAdmin($user);
    }
}
```

### 8.3 OAuth (Future - ETAP 12)

**Planned Providers:**
- Google Workspace (MPP TRADE organization)
- Microsoft Entra ID (Azure AD)

**Implementation:** Last step (after core features complete)

---

## 9. Deployment Architecture

### 9.1 Production Environment

**Hosting:** Hostido.net.pl (shared hosting)
- **Domain:** ppm.mpptrade.pl
- **SSH:** host379076@host379076.hostido.net.pl:64321
- **PHP:** 8.3.23 (native)
- **Composer:** 2.8.5 (preinstalled)
- **Database:** MariaDB 10.11.13
- **Node.js/npm:** ❌ NOT AVAILABLE

**Laravel Root Path:**
```
domains/ppm.mpptrade.pl/public_html/
├── app/
├── public/
│   └── build/ (uploaded from local)
├── vendor/
└── ... (standard Laravel)
```

### 9.2 Vite Build Process

**CRITICAL:** Vite ONLY runs locally!

**Workflow:**
```
[Local Windows]                [Production Hostido]
npm run build                  No Node.js/Vite!
  ↓
public/build/
├── assets/
│   ├── app-[hash].css
│   ├── app-[hash].js
│   └── ...
└── manifest.json
  ↓
pscp upload →                  Laravel @vite() helper
                                ↓
                               Reads manifest.json
                                ↓
                               Serves static files
```

**Build Command:**
```bash
# Local
npm run build

# Verify output
ls public/build/manifest.json  # Must exist!
ls public/build/assets/        # Hashed files
```

**Deployment Commands:**
```powershell
# Upload assets (ALL files!)
pscp -r -i $HostidoKey -P 64321 "public/build/assets/*" host379076@...:public/build/assets/

# Upload manifest to ROOT
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" host379076@...:public/build/manifest.json

# Clear cache
plink ... -batch "cd domains/.../public_html && php artisan view:clear && php artisan cache:clear"
```

### 9.3 Deployment Checklist

1. ✅ Local build: `npm run build`
2. ✅ Upload ALL assets: `pscp -r public/build/assets/*`
3. ✅ Upload manifest to ROOT: `pscp .vite/manifest.json → build/manifest.json`
4. ✅ Clear caches: `php artisan view:clear && cache:clear && config:clear`
5. ✅ Verify HTTP 200: `curl -I https://ppm.mpptrade.pl/build/assets/app-X.css`
6. ✅ Screenshot test: `node _TOOLS/screenshot_page.cjs`

**Reference:** `_DOCS/DEPLOYMENT_GUIDE.md`

---

## 10. Development Workflow

### 10.1 Planning System

**Plan Location:** `Plan_Projektu/`
- Each ETAP = separate file
- Hierarchical tasks with emoji statuses

**Statuses:**
- ❌ Not started
- 🛠️ In progress
- ✅ Completed
- ⚠️ Blocked

**Example:**
```markdown
# ❌ ETAP_09: Wyszukiwarka
## 🛠️ 9.1 Inteligentne Autosugesti
### ✅ 9.1.1 Fuzzy Search Algorithm
    └──📁 PLIK: app/Services/SearchService.php
### ❌ 9.1.2 Tolerancja Błędów
```

### 10.2 Agent System

**Agents:** `.claude/agents/` (13 active)

**Core Agents:**
- architect - Planning & design
- debugger - Bug diagnosis
- coding-style-agent - Code review
- documentation-reader - Doc verification

**Domain Agents:**
- laravel-expert - Laravel patterns
- livewire-specialist - Livewire debugging
- prestashop-api-expert - PrestaShop integration
- deployment-specialist - Production deployment

**Workflow:**
```
New Feature:
architect → docs → specialist → coding-style → deploy

Bug Fix:
debugger → specialist → coding-style
```

### 10.3 Skills System

**Skills:** `C:\Users\kamil\.claude\skills\` (9 active + 3 new)

**Workflow Skills:**
- hostido-deployment - Auto deploy
- livewire-troubleshooting - Known issues (9 patterns)
- frontend-verification - MANDATORY UI screenshots
- agent-report-writer - MANDATORY reports

**Domain Skills (NEW):**
- livewire-dev-guidelines - Livewire patterns (CRITICAL)
- frontend-dev-guidelines - Frontend rules (ZAKAZ inline styles)
- laravel-dev-guidelines - Laravel Service Layer

**Enforcement:**
- MANDATORY: ppm-architecture-compliance, frontend-verification, agent-report-writer
- REQUIRED: context7-docs-lookup (before new patterns)

### 10.4 Testing Strategy

**Unit Tests:** `tests/Unit/`
- Models (ProductTest, CategoryTest)
- Services (PrestaShopSyncTest)
- Rules (UniqueSKUTest)

**Feature Tests:** `tests/Feature/`
- OAuth (Google, Microsoft)
- Security (session, CSRF)

**E2E Tests:**
- Playwright (`playwright.config.js`)
- Custom tool: `_TOOLS/full_console_test.cjs`

**Coverage:** ~10 tests currently, expansion planned (ETAP_12)

---

## Related Documentation

- **CLAUDE.md** - Main instructions for Claude Code
- **DEPLOYMENT_GUIDE.md** - Complete deployment reference
- **SKU_ARCHITECTURE_GUIDE.md** - SKU-first patterns
- **FRONTEND_VERIFICATION_GUIDE.md** - UI verification workflow
- **CSS_STYLING_GUIDE.md** - CSS rules & design system
- **AGENT_USAGE_GUIDE.md** - Agent patterns
- **SKILLS_USAGE_GUIDE.md** - Skills reference
- **CONTEXT7_INTEGRATION_GUIDE.md** - Context7 MCP
- **TROUBLESHOOTING.md** - Common issues (NEW!)

---

**Document Version:** 1.0
**Last Updated:** 2025-11-04
**Maintainer:** PPM Development Team
**Contributing:** Keep this document updated as architecture evolves
