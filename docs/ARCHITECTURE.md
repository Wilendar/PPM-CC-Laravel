# Architektura PPM-CC-Laravel - System PIM Klasy Enterprise

## 📋 Spis Treści

1. [Przegląd Architektury](#przegląd-architektury)
2. [Stack Technologiczny](#stack-technologiczny)
3. [System Użytkowników i Uprawnień](#system-użytkowników-i-uprawnień)
4. [Struktura Bazy Danych](#struktura-bazy-danych)
5. [Moduły Biznesowe](#moduły-biznesowe)
6. [Integracje Zewnętrzne](#integracje-zewnętrzne)
7. [System Import/Export](#system-importexport)
8. [Frontend Architecture](#frontend-architecture)
9. [Bezpieczeństwo](#bezpieczeństwo)
10. [Wydajność i Skalowanie](#wydajność-i-skalowanie)
11. [Monitoring i Logging](#monitoring-i-logging)

## 🏗️ Przegląd Architektury

### Architektura Wysokiego Poziomu

```
┌─────────────────────────────────────────────────────────────┐
│                    PPM-CC-Laravel                           │
│                Product Information Management                │
└─────────────────────────────────────────────────────────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
         ┌──────▼──────┐ ┌──────▼──────┐ ┌──────▼──────┐
         │  Frontend   │ │   Backend   │ │  Database   │
         │             │ │             │ │             │
         │ Livewire 3  │ │ Laravel 12  │ │  MariaDB    │
         │ Alpine.js   │ │ PHP 8.3     │ │  10.11.13   │
         │ TailwindCSS │ │ Spatie      │ │             │
         └─────────────┘ └─────────────┘ └─────────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
         ┌──────▼──────┐ ┌──────▼──────┐ ┌──────▼──────┐
         │ Prestashop  │ │     ERP     │ │   Excel     │
         │   8.x/9.x   │ │ Integration │ │   Import    │
         │ Multi-Store │ │ Baselinker  │ │   Export    │
         └─────────────┘ └─────────────┘ └─────────────┘
```

### Założenia Projektowe

**Klasa Enterprise:**
- ✅ **Skalowalna architektura** - modułowy design
- ✅ **Bezpieczeństwo** - wielopoziomowe uprawnienia
- ✅ **Integracje** - API-first approach
- ✅ **Niezawodność** - error handling, backup, monitoring
- ✅ **Wydajność** - cache strategies, optimization

**Multi-Store Management:**
- ✅ **Centralized Product Management** - jeden produkt → wiele sklepów
- ✅ **Store-specific Configuration** - różne opisy/kategorie per sklep
- ✅ **Synchronization Control** - selective sync, conflict resolution

**Business Logic:**
- ✅ **Automotive Focus** - Model/Oryginał/Zamiennik matching
- ✅ **Complex Pricing** - 8 grup cenowych + marże + promocje
- ✅ **Container Management** - import z kontenerów + dokumenty odprawy
- ✅ **ERP Integration** - bidirectional sync Baselinker/Subiekt/Dynamics

## ⚡ Stack Technologiczny

### Backend Stack

```php
// composer.json - Pakiety kluczowe
{
    "require": {
        "laravel/framework": "^12.0",        // 🔥 Laravel 12.x LTS
        "livewire/livewire": "^3.0",         // 🎯 Full-stack components
        "maatwebsite/excel": "^3.1",         // 📊 Excel import/export
        "spatie/laravel-permission": "^6.0",  // 🔐 RBAC system
        "laravel/socialite": "^5.0",         // 🌐 OAuth (future)
        "spatie/laravel-backup": "^8.0"      // 💾 Backup system
    }
}
```

**PHP Configuration:**
- **Version**: PHP 8.3.23 (Hostido.net.pl)
- **Extensions**: mb_string, openssl, pdo_mysql, zip, xml, gd
- **Memory Limit**: 512MB (hosting współdzielony)
- **Execution Time**: 300s (dla import/export)

### Frontend Stack

```json
{
  "devDependencies": {
    "vite": "^5.0",                    // 🔥 Modern bundler
    "tailwindcss": "^3.2.1",         // 🎨 Utility-first CSS
    "alpinejs": "^3.4.2",            // ⚡ Minimal JS framework
    "laravel-vite-plugin": "^1.0"     // 🔗 Laravel integration
  }
}
```

**Architecture Pattern**: **Hybrid SPA**
- **Livewire Components** - Server-side rendering + reaktywność
- **Alpine.js** - Client-side interactions (modal, dropdown, form validation)
- **TailwindCSS** - Konsystentny design system
- **Vite HMR** - Hot module replacement podczas development

### Database Schema

**MariaDB 10.11.13:**
- **Encoding**: utf8mb4_unicode_ci (full Polish characters support)
- **Engine**: InnoDB (transakcje, foreign keys)
- **Backup Strategy**: Daily automated + pre-deployment backup
- **Indexing Strategy**: Optimized dla PIM queries (SKU, kategorie, pełnotekstowe)

## 👥 System Użytkowników i Uprawnień

### 7 Poziomów Użytkowników

```php
// Hierarchia uprawnień (Spatie Laravel Permission)
enum UserRole: string {
    case ADMIN = 'admin';                    // Level 1 - Full access
    case MANAGER = 'manager';                // Level 2 - Product management
    case EDITOR = 'editor';                  // Level 3 - Content editing
    case WAREHOUSE = 'warehouse';            // Level 4 - Inventory management
    case SALES = 'sales';                   // Level 5 - Sales operations
    case CLAIMS = 'claims';                 // Level 6 - Claims handling
    case USER = 'user';                     // Level 7 - Read only
}
```

### Matrix Uprawnień

| Funkcjonalność | Admin | Manager | Editor | Warehouse | Sales | Claims | User |
|----------------|-------|---------|--------|-----------|--------|--------|------|
| **Zarządzanie użytkownikami** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Konfiguracja sklepów** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Integracje ERP** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Dodawanie produktów** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Usuwanie produktów** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Edycja opisów/zdjęć** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Import CSV/XLSX** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Eksport do Prestashop** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Panel dostaw** | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| **Rezerwacje z kontenera** | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Widoczność cen zakupu** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Panel reklamacji** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Wyszukiwarka** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Middleware Stack

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
    'pim.access' => \App\Http\Middleware\PimAccessControl::class,  // Custom PIM middleware
];
```

## 🗄️ Struktura Bazy Danych

### Główne Encje

```sql
-- Core Product Management Tables
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(100) UNIQUE NOT NULL,           -- Główny klucz biznesowy
    symbol_dostawcy VARCHAR(100),               -- Osobne pole (nie w SKU)
    name VARCHAR(500) NOT NULL,
    description_short TEXT,
    description_long LONGTEXT,
    manufacturer_id BIGINT,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_sku (sku),
    INDEX idx_manufacturer (manufacturer_id),
    FULLTEXT idx_search (name, description_short)
);

-- Category System (5 poziomów zagnieżdżenia)
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    parent_id BIGINT NULL,                      -- Self-referencing
    level TINYINT DEFAULT 1,                    -- 1-5 levels
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT 1,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Product-Category Associations (Many-to-Many)
CREATE TABLE product_categories (
    product_id BIGINT,
    category_id BIGINT,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Pricing Groups (8 grup + HuHa)
CREATE TABLE price_groups (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,                 -- 'Detaliczna', 'Dealer Standard', etc.
    code VARCHAR(50) UNIQUE NOT NULL,           -- 'RETAIL', 'DEALER_STD', etc.
    margin_default DECIMAL(5,2) DEFAULT 0,     -- Domyślna marża %
    active BOOLEAN DEFAULT 1
);

-- Product Prices per Group
CREATE TABLE product_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    price_group_id BIGINT NOT NULL,
    purchase_price DECIMAL(10,4),              -- Cena zakupu
    selling_price DECIMAL(10,4),               -- Cena sprzedaży
    margin_percent DECIMAL(5,2),               -- Marża %
    active BOOLEAN DEFAULT 1,
    UNIQUE KEY unique_product_price (product_id, price_group_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (price_group_id) REFERENCES price_groups(id)
);
```

### Multi-Store Architecture

```sql
-- Prestashop Stores Configuration  
CREATE TABLE prestashop_stores (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    api_key VARCHAR(500) NOT NULL,
    version ENUM('8.x', '9.x') NOT NULL,
    active BOOLEAN DEFAULT 1,
    sync_enabled BOOLEAN DEFAULT 1,
    last_sync_at TIMESTAMP NULL
);

-- Store-specific Product Data
CREATE TABLE product_store_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    store_id BIGINT NOT NULL,
    name_override VARCHAR(500) NULL,            -- Różna nazwa per sklep
    description_override LONGTEXT NULL,         -- Różny opis per sklep  
    category_mapping JSON,                      -- Mapowanie kategorii
    active_in_store BOOLEAN DEFAULT 1,
    sync_status ENUM('pending', 'synced', 'error', 'disabled') DEFAULT 'pending',
    last_sync_at TIMESTAMP NULL,
    UNIQUE KEY unique_product_store (product_id, store_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES prestashop_stores(id) ON DELETE CASCADE
);
```

### Vehicle Matching System

```sql
-- System dopasowań pojazdów (Model/Oryginał/Zamiennik)
CREATE TABLE vehicle_attributes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT NOT NULL,
    attribute_type ENUM('model', 'original', 'replacement') NOT NULL,
    value VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_type (product_id, attribute_type),
    INDEX idx_search_value (value)
);

-- Store-specific Vehicle Filtering (banowanie modeli per sklep)
CREATE TABLE store_vehicle_filters (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    store_id BIGINT NOT NULL,
    attribute_value VARCHAR(255) NOT NULL,      -- Nazwa modelu do zbanowania
    action ENUM('allow', 'deny') DEFAULT 'allow',
    FOREIGN KEY (store_id) REFERENCES prestashop_stores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_store_filter (store_id, attribute_value)
);
```

### Container Import System

```sql
-- System kontenerów i importu
CREATE TABLE containers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    container_number VARCHAR(100) UNIQUE NOT NULL,
    arrival_date DATE,
    status ENUM('expected', 'arrived', 'cleared', 'distributed') DEFAULT 'expected',
    supplier VARCHAR(255),
    total_items INT DEFAULT 0,
    documents_path VARCHAR(500),                -- Ścieżka do dokumentów (.zip)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Import Jobs (batch processing)
CREATE TABLE import_jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    container_id BIGINT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_size BIGINT,
    column_mapping JSON,                        -- Mapowanie kolumn XLSX
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_rows INT DEFAULT 0,
    processed_rows INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    errors JSON NULL,                           -- Szczegóły błędów
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (container_id) REFERENCES containers(id) ON DELETE SET NULL
);
```

## 🔧 Moduły Biznesowe

### App Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                   # Panel administratora
│   │   ├── Product/                 # Zarządzanie produktami
│   │   ├── Import/                  # System importu
│   │   └── Api/                     # API endpoints
│   ├── Livewire/
│   │   ├── Product/
│   │   │   ├── ProductList.php      # Lista produktów
│   │   │   ├── ProductEdit.php      # Edycja produktu
│   │   │   └── ProductImport.php    # Import wizard
│   │   ├── Admin/
│   │   │   ├── Dashboard.php        # Dashboard administratora
│   │   │   └── UserManagement.php   # Zarządzanie użytkownikami
│   │   └── Search/
│   │       └── ProductSearch.php    # Inteligentna wyszukiwarka
│   └── Middleware/
│       ├── PimAccessControl.php     # Custom access control
│       └── StoreContext.php         # Multi-store context
├── Models/
│   ├── Product.php                  # Główny model produktu
│   ├── Category.php                 # System kategorii
│   ├── PriceGroup.php              # Grupy cenowe
│   ├── PrestashopStore.php         # Konfiguracja sklepów
│   └── Container.php               # System kontenerów
├── Services/
│   ├── PrestaShop/
│   │   ├── ApiService.php          # Prestashop API client
│   │   ├── ProductSync.php         # Synchronizacja produktów
│   │   └── CategorySync.php        # Synchronizacja kategorii
│   ├── ERP/
│   │   ├── BaselinkerService.php   # Integracja Baselinker
│   │   ├── SubiektService.php      # Integracja Subiekt GT
│   │   └── DynamicsService.php     # Microsoft Dynamics
│   ├── Import/
│   │   ├── ExcelImporter.php       # Import XLSX
│   │   ├── ColumnMapper.php        # Mapowanie kolumn
│   │   └── ValidationService.php   # Walidacja danych
│   └── Search/
│       ├── ProductSearchService.php # Wyszukiwarka produktów
│       └── AutocompleteService.php  # Podpowiedzi wyszukiwania
└── Jobs/
    ├── ProcessProductImport.php     # Async import processing
    ├── SyncToPrestaShop.php        # Sync produktów do sklepów
    └── GenerateReports.php         # Generowanie raportów
```

### Key Business Services

**ProductService:**
```php
<?php

namespace App\Services\Product;

class ProductService 
{
    public function createProduct(array $data): Product
    {
        // Walidacja SKU uniqueness
        // Tworzenie produktu z relacjami (kategorie, ceny)
        // Trigger sync events
    }
    
    public function bulkUpdatePrices(array $products, PriceGroup $group): void
    {
        // Batch update cen z optimistic locking
        // Audit log changes
        // Trigger Prestashop sync
    }
    
    public function searchProducts(string $query, array $filters = []): Collection
    {
        // Intelligent search z fuzzy matching
        // Support dla literówek i skrótów
        // Category filtering, availability filtering
    }
}
```

**PrestaShopSyncService:**
```php
<?php

namespace App\Services\PrestaShop;

class ProductSyncService 
{
    public function syncProduct(Product $product, PrestashopStore $store): SyncResult
    {
        // Check store-specific configuration
        // Apply store-specific overrides (name, description, categories)
        // Handle vehicle filtering (ban specific models per store)
        // API call z error handling i retry logic
        // Update sync status
    }
    
    public function batchSync(Collection $products, PrestashopStore $store): BatchSyncResult
    {
        // Chunked processing dla large datasets
        // Progress tracking
        // Error collection i reporting
    }
}
```

## 🔗 Integracje Zewnętrzne

### Prestashop API Integration

```php
// config/prestashop.php
<?php

return [
    'api' => [
        'timeout' => 30,
        'retry_attempts' => 3,
        'rate_limit' => [
            'requests_per_minute' => 100,   // API rate limiting
            'burst_limit' => 10,
        ],
        'batch_size' => 50,                 // Bulk operations
    ],
    'sync' => [
        'auto_sync' => env('PRESTASHOP_AUTO_SYNC', false),
        'sync_images' => env('PRESTASHOP_SYNC_IMAGES', true),
        'preserve_prestashop_data' => true,  // Nie nadpisuj PS-specific data
    ],
];
```

**API Client Architecture:**
```php
<?php

namespace App\Services\PrestaShop;

class ApiClient
{
    private HttpClient $client;
    private RateLimiter $rateLimiter;
    private Logger $logger;
    
    public function createProduct(array $productData, PrestashopStore $store): ApiResponse
    {
        $this->rateLimiter->throttle($store->id);
        
        try {
            $response = $this->client->post("/products", [
                'headers' => [
                    'Authorization' => "Basic " . base64_encode($store->api_key . ':'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->transformProductData($productData, $store),
            ]);
            
            return new ApiResponse($response);
        } catch (RequestException $e) {
            $this->logger->error('Prestashop API error', [
                'store' => $store->name,
                'error' => $e->getMessage(),
                'product_sku' => $productData['sku'] ?? 'unknown',
            ]);
            
            throw new PrestaShopApiException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
```

### ERP Integrations

**Baselinker Integration (Priority #1):**
```php
<?php

namespace App\Services\ERP;

class BaselinkerService
{
    private string $apiUrl = 'https://api.baselinker.com/connector.php';
    private string $token;
    
    public function getInventoryProducts(int $inventoryId): Collection
    {
        $response = $this->apiCall('getInventoryProductsData', [
            'inventory_id' => $inventoryId,
            'products' => [], // Empty = all products
        ]);
        
        return collect($response['products'])->map(function ($product) {
            return new BaselinkerProduct($product);
        });
    }
    
    public function updateProductStock(string $sku, int $quantity, int $warehouseId): void
    {
        $this->apiCall('updateInventoryProductsStock', [
            'inventory_id' => $this->getInventoryId(),
            'products' => [
                $sku => [
                    $warehouseId => $quantity,
                ],
            ],
        ]);
    }
    
    private function apiCall(string $method, array $parameters = []): array
    {
        // HTTP client z retry logic
        // Error handling i logging
        // Rate limiting compliance
    }
}
```

**Subiekt GT Integration:**
```php
<?php

namespace App\Services\ERP;

class SubiektGTService
{
    // Subiekt GT używa SOAP lub REST API w zależności od wersji
    public function importProducts(): Collection
    {
        // Connect to Subiekt GT database or API
        // Map Subiekt fields to PPM structure
        // Handle encoding issues (Windows-1250 → UTF-8)
    }
    
    public function exportPriceList(PriceGroup $priceGroup): string
    {
        // Generate CSV/XML dla Subiekt GT import
        // Apply price group margins
        // Include warehouse quantities
    }
}
```

## 📊 System Import/Export

### Excel Import Architecture

```php
<?php

namespace App\Services\Import;

use Maatwebsite\Excel\Concerns\{
    FromArray, WithHeadingRow, WithBatchInserts, 
    WithChunkReading, ShouldQueue
};

class ProductImport implements FromArray, WithHeadingRow, WithBatchInserts, WithChunkReading, ShouldQueue
{
    private ImportJob $importJob;
    private array $columnMapping;
    
    public function __construct(ImportJob $importJob, array $columnMapping)
    {
        $this->importJob = $importJob;
        $this->columnMapping = $columnMapping;
    }
    
    public function array(array $row): ?array
    {
        try {
            // Map columns according to user configuration
            $productData = $this->mapColumns($row);
            
            // Validate data
            $validator = Validator::make($productData, [
                'sku' => 'required|string|max:100',
                'name' => 'required|string|max:500',
                // ... validation rules
            ]);
            
            if ($validator->fails()) {
                $this->logError($row, $validator->errors());
                return null;
            }
            
            // Create or update product
            return $this->processProduct($productData);
            
        } catch (\Exception $e) {
            $this->logError($row, $e->getMessage());
            return null;
        }
    }
    
    public function batchSize(): int
    {
        return 500; // Process w batch dla memory efficiency
    }
    
    public function chunkSize(): int
    {
        return 1000; // Read w chunks dla large files
    }
}
```

### Column Mapping System

**Predefiniowane szablony:**
```php
<?php

// config/import_templates.php
return [
    'POJAZDY' => [
        'required_columns' => ['ORDER', 'Parts Name', 'Model'],
        'optional_columns' => ['U8 Code', 'MRF CODE', 'Qty', 'Ctn no.'],
        'mapping' => [
            'ORDER' => 'sort_order',
            'Parts Name' => 'name',
            'U8 Code' => 'sku',
            'MRF CODE' => 'symbol_dostawcy',
            'Model' => 'vehicle_models',
            'Qty' => 'quantity',
            'Ctn no.' => 'container_number',
        ],
    ],
    'CZĘŚCI' => [
        'required_columns' => ['SKU', 'Nazwa', 'Cena'],
        'mapping' => [
            'SKU' => 'sku',
            'Nazwa' => 'name', 
            'Cena' => 'price',
            'Opis' => 'description_short',
            'Kategoria' => 'categories',
        ],
    ],
];
```

### Export System

**Multi-format Export:**
```php
<?php

namespace App\Services\Export;

class ProductExporter
{
    public function exportToPrestaShop(Collection $products, PrestashopStore $store): string
    {
        // Generate XML format compatible z Prestashop import
        // Apply store-specific transformations
        // Include category mappings
        // Handle image URLs
    }
    
    public function exportToCsv(Collection $products, array $columns): string
    {
        // Generate CSV z selected columns
        // Apply encoding (UTF-8 with BOM for Excel)
        // Include headers
    }
    
    public function exportPriceList(PriceGroup $priceGroup): string
    {
        // Generate price list dla specific group
        // Include margins and final prices
        // Group by categories
    }
}
```

## 🎨 Frontend Architecture

### Livewire Component Strategy

**Component Hierarchy:**
```
resources/views/
├── layouts/
│   ├── app.blade.php               # Main layout
│   ├── navigation.blade.php        # Navigation with role-based menu
│   └── sidebar.blade.php          # Sidebar z quick actions
├── livewire/
│   ├── product/
│   │   ├── product-list.blade.php  # Table z filtering i pagination
│   │   ├── product-edit.blade.php  # Form z validation
│   │   └── product-search.blade.php # Autocomplete search
│   ├── admin/
│   │   ├── dashboard.blade.php     # Stats i charts
│   │   └── user-management.blade.php
│   └── import/
│       └── import-wizard.blade.php # Multi-step import process
└── components/
    ├── forms/                      # Reusable form components
    ├── tables/                     # Data table components  
    └── modals/                     # Modal dialogs
```

**Key Livewire Components:**
```php
<?php

namespace App\Livewire\Product;

use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;
    
    public string $search = '';
    public array $selectedCategories = [];
    public array $selectedStores = [];
    public string $sortField = 'updated_at';
    public string $sortDirection = 'desc';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategories' => ['except' => []],
    ];
    
    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination po search
    }
    
    public function render()
    {
        $products = Product::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->when($this->selectedCategories, function ($query) {
                $query->whereHas('categories', function ($q) {
                    $q->whereIn('id', $this->selectedCategories);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(25);
            
        return view('livewire.product.product-list', [
            'products' => $products,
            'categories' => Category::all(),
            'stores' => PrestashopStore::active()->get(),
        ]);
    }
}
```

### TailwindCSS Design System

**Custom Configuration:**
```js
// tailwind.config.js
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Livewire/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        'pim-primary': {
          50: '#eff6ff',
          500: '#3b82f6',
          900: '#1e3a8a',
        },
        'pim-success': '#10b981',
        'pim-warning': '#f59e0b', 
        'pim-danger': '#ef4444',
      },
      fontFamily: {
        'sans': ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### Alpine.js Integration

**Interactive Components:**
```html
<!-- resources/views/components/product-search.blade.php -->
<div x-data="productSearch()" x-init="init()">
    <div class="relative">
        <input 
            type="text"
            x-model="query"
            @input.debounce.300ms="search()"
            @keydown.arrow-down.prevent="highlightNext()"
            @keydown.arrow-up.prevent="highlightPrevious()"
            @keydown.enter.prevent="selectHighlighted()"
            placeholder="Szukaj produktów..."
            class="w-full px-4 py-2 border rounded-lg"
        >
        
        <!-- Dropdown z sugestiami -->
        <div x-show="showSuggestions" x-transition class="absolute w-full bg-white border rounded-lg shadow-lg z-10">
            <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
                <div 
                    @click="selectProduct(suggestion)"
                    :class="{'bg-blue-50': index === highlightedIndex}"
                    class="px-4 py-2 hover:bg-gray-50 cursor-pointer"
                >
                    <div class="font-medium" x-text="suggestion.name"></div>
                    <div class="text-sm text-gray-500" x-text="suggestion.sku"></div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function productSearch() {
    return {
        query: '',
        suggestions: [],
        showSuggestions: false,
        highlightedIndex: -1,
        
        async search() {
            if (this.query.length < 2) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }
            
            try {
                const response = await fetch(`/api/products/search?q=${encodeURIComponent(this.query)}`);
                this.suggestions = await response.json();
                this.showSuggestions = true;
                this.highlightedIndex = -1;
            } catch (error) {
                console.error('Search error:', error);
            }
        },
        
        selectProduct(product) {
            // Redirect lub emit event do Livewire component
            window.location.href = `/products/${product.id}`;
        }
    }
}
</script>
```

## 🔒 Bezpieczeństwo

### Authentication & Authorization

```php
// config/auth.php - Multi-guard authentication
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',  // API tokens dla integration
        'provider' => 'users',
    ],
],

// Future: OAuth2 providers
'socialite' => [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/google/callback',
    ],
    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/microsoft/callback',
    ],
],
```

### Security Middleware Stack

```php
<?php

namespace App\Http\Middleware;

class PimAccessControl
{
    public function handle(Request $request, Closure $next, string $requiredRole)
    {
        // Sprawdź czy user ma wymagane uprawnienia
        if (!$request->user()?->hasRole($requiredRole)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'user_id' => $request->user()?->id,
                'required_role' => $requiredRole,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            
            abort(403, 'Brak uprawnień do tej sekcji.');
        }
        
        return $next($request);
    }
}
```

### Data Validation & Sanitization

```php
<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9\-_]+$/i',  // Tylko alphanumeric + - _
                'unique:products,sku',
            ],
            'name' => [
                'required',
                'string',
                'max:500',
                'regex:/^[\p{L}\p{N}\s\-_().,]+$/u',  // Unicode letters, numbers, podstawowe znaki
            ],
            'description_short' => 'nullable|string|max:1000',
            'description_long' => 'nullable|string',
            'categories' => 'array|max:10',
            'categories.*' => 'exists:categories,id',
            'prices' => 'array',
            'prices.*.price_group_id' => 'exists:price_groups,id',
            'prices.*.purchase_price' => 'numeric|min:0|max:999999.99',
            'prices.*.selling_price' => 'numeric|min:0|max:999999.99',
        ];
    }
    
    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU może zawierać tylko litery, cyfry, myślniki i podkreślenia.',
            'name.regex' => 'Nazwa produktu zawiera niedozwolone znaki.',
        ];
    }
}
```

### File Upload Security

```php
<?php

namespace App\Services\Import;

class SecureFileUploader
{
    private array $allowedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
    ];
    
    private array $allowedExtensions = ['xlsx', 'xls', 'csv'];
    private int $maxFileSize = 50 * 1024 * 1024; // 50MB
    
    public function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new ValidationException('Plik jest za duży. Maksymalny rozmiar: 50MB.');
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new ValidationException('Niedozwolony typ pliku.');
        }
        
        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new ValidationException('Niedozwolone rozszerzenie pliku.');
        }
        
        // Scan for malware (jeśli dostępne)
        $this->scanForMalware($file);
    }
    
    private function scanForMalware(UploadedFile $file): void
    {
        // Integration z antivirus scanning jeśli dostępne na hostingu
        // Lub basic file content validation
    }
}
```

## ⚡ Wydajność i Skalowanie

### Database Optimization

```php
<?php

// config/database.php - MySQL optimization dla PIM
'mysql' => [
    'driver' => 'mysql',
    'options' => [
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Dla large datasets
    ],
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    ]) : [],
    
    // Connection pooling settings
    'pool_size' => 10,
    'idle_timeout' => 600,
],

// Indexes dla PIM queries
/*
CREATE INDEX idx_products_search ON products (name(100), sku);
CREATE INDEX idx_products_category ON product_categories (category_id, product_id);
CREATE INDEX idx_products_prices ON product_prices (product_id, price_group_id, active);
CREATE INDEX idx_sync_status ON product_store_data (sync_status, last_sync_at);
CREATE FULLTEXT INDEX ft_products_search ON products (name, description_short);
*/
```

### Cache Strategy

```php
<?php

// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
    
    // Fallback na database jeśli Redis niedostępny
    'database' => [
        'driver' => 'database',
        'table' => 'cache',
        'connection' => null,
        'lock_connection' => null,
    ],
],

// Custom cache tags dla PIM
'tags' => [
    'products' => 3600,        // 1 hour
    'categories' => 86400,     // 24 hours  
    'price_groups' => 86400,   // 24 hours
    'search_results' => 1800,  // 30 minutes
],
```

**Cache Usage w Services:**
```php
<?php

namespace App\Services\Product;

class ProductCacheService
{
    public function getCachedProduct(string $sku): ?Product
    {
        return Cache::tags(['products'])
            ->remember("product:{$sku}", 3600, function () use ($sku) {
                return Product::with(['categories', 'prices', 'vehicleAttributes'])
                    ->where('sku', $sku)
                    ->first();
            });
    }
    
    public function invalidateProductCache(Product $product): void
    {
        Cache::tags(['products'])->forget("product:{$product->sku}");
        Cache::tags(['search_results'])->flush(); // Invalidate search results
        
        // Invalidate related caches
        foreach ($product->categories as $category) {
            Cache::forget("category_products:{$category->id}");
        }
    }
}
```

### Queue System

```php
<?php

// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
    ],
    
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],

// Job queues dla różnych operacji
'queues' => [
    'imports' => ['timeout' => 3600],        // 1 hour dla import jobs
    'sync' => ['timeout' => 1800],           // 30 min dla Prestashop sync
    'reports' => ['timeout' => 600],         // 10 min dla reports
    'default' => ['timeout' => 300],         // 5 min dla standard jobs
],
```

## 📊 Monitoring i Logging

### Logging Strategy

```php
<?php

// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'error'),
        'days' => 14,
        'replace_placeholders' => true,
    ],
    
    // Dedicated channels dla PIM modules
    'prestashop_sync' => [
        'driver' => 'daily',
        'path' => storage_path('logs/prestashop_sync.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'import_jobs' => [
        'driver' => 'daily', 
        'path' => storage_path('logs/import_jobs.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 90, // Longer retention dla security logs
    ],
],
```

### Performance Monitoring

```php
<?php

namespace App\Services\Monitoring;

class PerformanceMonitor
{
    public function trackOperation(string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $this->logPerformance($operation, [
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'memory_mb' => round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'status' => 'success',
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logPerformance($operation, [
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    private function logPerformance(string $operation, array $metrics): void
    {
        Log::info("Performance: {$operation}", $metrics);
        
        // Alert jeśli operation jest za wolna
        if ($metrics['duration_ms'] > 5000) { // 5 seconds
            Log::warning("Slow operation detected: {$operation}", $metrics);
        }
    }
}
```

### Health Check System

```php
<?php

namespace App\Http\Controllers\Api;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),  
            'prestashop_api' => $this->checkPrestashopConnections(),
            'file_storage' => $this->checkFileStorage(),
            'queue_workers' => $this->checkQueueWorkers(),
        ];
        
        $overall = collect($checks)->every(fn($status) => $status['healthy']);
        
        return response()->json([
            'status' => $overall ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version'),
            'environment' => config('app.env'),
        ], $overall ? 200 : 503);
    }
    
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['healthy' => true, 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function checkPrestashopConnections(): array
    {
        $stores = PrestashopStore::active()->get();
        $results = [];
        
        foreach ($stores as $store) {
            try {
                // Quick API test
                $response = Http::timeout(5)
                    ->withBasicAuth($store->api_key, '')
                    ->get($store->url . '/api');
                    
                $results[$store->name] = $response->successful();
            } catch (\Exception $e) {
                $results[$store->name] = false;
            }
        }
        
        $healthy = collect($results)->every(fn($status) => $status === true);
        
        return [
            'healthy' => $healthy,
            'stores' => $results,
        ];
    }
}
```

---

**Podsumowanie:** Architektura PPM-CC-Laravel została zaprojektowana jako skalowalna, bezpieczna i wydajna platforma PIM klasy enterprise, gotowa do obsługi złożonych operacji multi-store oraz integracji z wieloma systemami ERP jednocześnie.

**Next Steps:** Implementacja ETAP_02 - Modele bazy danych i migracje na podstawie tej architektury.