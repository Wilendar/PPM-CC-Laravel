# ❌ ETAP 08: INTEGRACJE Z SYSTEMAMI ERP

## PLAN RAMOWY ETAPU

- ❌ 8.1 Analiza i dokumentacja systemów ERP
- ❌ 8.2 Modele i migracje ERP
- ❌ 8.3 BaseLinker Integration Service
- 🛠️ 8.4 Subiekt GT Integration Service (FAZY 1-5 ✅, FAZA 6 TESTY ⏳)
- ❌ 8.5 Microsoft Dynamics Integration
- ❌ 8.6 Unified ERP Service Layer
- ❌ 8.7 Jobs i queue system
- ❌ 8.8 Panel administracyjny ERP
- ❌ 8.9 Monitoring i raporty
- ❌ 8.10 Testy i dokumentacja
- ❌ 8.11 Deployment i finalizacja

---

## 🔗 POWIĄZANE FEATURE BRANCHES

| Feature | Status | Plik planu | Opis |
|---------|--------|------------|------|
| **STOCK_TAB_GRANULAR_LOCKS** | ❌ Do implementacji | [STOCK_TAB_GRANULAR_LOCKS.md](STOCK_TAB_GRANULAR_LOCKS.md) | System granularnych blokad kolumn w zakładce "Stany magazynowe" - zapobiega przypadkowej synchronizacji do ERP |

**Zależności:**
- STOCK_TAB_GRANULAR_LOCKS wymaga działających integracji ERP (8.3, 8.4, 8.5)
- Implementuje dirty tracking dla selektywnej synchronizacji stock data do ERP

---


## 🔍 INSTRUKCJE PRZED ROZPOCZĘCIEM ETAPU

**OBOWIĄZKOWE CZYNNOŚCI:**

1. **ANALIZA ZADAŃ ETAPU**
   - Przeanalizuj wszystkie zadania i podzadania w tym ETAP-ie
   - Zidentyfikuj wymagane pliki, klasy, migracje i komponenty
   - Określ zależności z innymi ETAPami (szczególnie ETAP_04, ETAP_06)

2. **AKTUALIZACJA DOKUMENTACJI STRUKTURY**
   - Otwórz `_DOCS/Struktura_Plikow_Projektu.md`
   - Dodaj wszystkie nowe pliki i foldery zaplanowane w tym ETAP-ie:
     - `app/Services/ERP/` - BaseLinker, SubiektGT, Dynamics services
     - `app/Models/ErpConnection.php`, `ErpFieldMapping.php`, `ErpSyncJob.php`
     - `app/Jobs/ERP/` - SyncProductToERP jobs
     - Livewire components dla panelu ERP
     - Migracje dla tabel ERP (`database/migrations/`)
   - Otwórz `_DOCS/Struktura_Bazy_Danych.md`
   - Dodaj nowe tabele z tego ETAP-u:
     - `erp_connections` - konfiguracje połączeń ERP
     - `erp_field_mappings` - mapowania pól między systemami
     - `erp_sync_jobs` - zadania synchronizacji
     - `erp_entity_sync_status` - statusy synchronizacji encji
     - `erp_sync_logs` - logi operacji ERP

3. **PRZYGOTOWANIE ŚRODOWISKA**
   - Upewnij się, że BaseLinker API key jest dostępny
   - Sprawdź dostępność Subiekt GT (DLL/COM)
   - Przygotuj dane testowe Microsoft Dynamics 365

**UWAGA** WYŁĄCZ autoryzację AdminMiddleware na czas developmentu!

**Szacowany czas realizacji:** 45 godzin  
**Priorytet:** 🟡 WYSOKI  
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński  
**Wymagane zasoby:** BaseLinker API, Subiekt GT DLL, Microsoft Dynamics OData, MySQL  

---

## 🎯 CEL ETAPU

Implementacja kompletnej dwukierunkowej integracji z trzema kluczowymi systemami ERP używanymi w organizacji MPP Trade. System musi umożliwiać synchronizację produktów, stanów magazynowych, cenników oraz zamówień między PPM a systemami BaseLinker, Subiekt GT i Microsoft Dynamics.

### Kluczowe rezultaty:
- ✅ Integracja z BaseLinker API (produkty, zamówienia, stany)
- ✅ Integracja z Subiekt GT via DLL/.NET Bridge
- ✅ Integracja z Microsoft Dynamics via OData API
- ✅ Unified ERP Service Layer dla jednolitego interfejsu
- ✅ System mapowań i transformacji między formatami ERP
- ✅ Automatyczne synchronizacje według harmonogramów
- ✅ Monitoring i raportowanie integracji ERP
- ✅ Panel konfiguracji i zarządzania integracjami

---

## ❌ 8.1 ANALIZA I DOKUMENTACJA ERP SYSTEMS

### ❌ 8.1.1 Analiza BaseLinker API
#### ❌ 8.1.1.1 Dokumentacja i endpointy BaseLinker
- ❌ 8.1.1.1.1 Przegląd BaseLinker API v2 Documentation
- ❌ 8.1.1.1.2 Analiza limitów API (requests per minute/hour)
- ❌ 8.1.1.1.3 Struktura odpowiedzi i kody błędów
- ❌ 8.1.1.1.4 Webhook system BaseLinker
- ❌ 8.1.1.1.5 Rate limiting i retry strategies

#### ❌ 8.1.1.2 BaseLinker Products API
- ❌ 8.1.1.2.1 getInventoryProductsList - lista produktów
- ❌ 8.1.1.2.2 addInventoryProduct - dodawanie produktu
- ❌ 8.1.1.2.3 updateInventoryProduct - aktualizacja produktu
- ❌ 8.1.1.2.4 deleteInventoryProduct - usuwanie produktu
- ❌ 8.1.1.2.5 getInventoryProductsStock - stany magazynowe

#### ❌ 8.1.1.3 BaseLinker Orders & Warehouses
- ❌ 8.1.1.3.1 getOrders - pobieranie zamówień
- ❌ 8.1.1.3.2 getInventories - magazyny BaseLinker
- ❌ 8.1.1.3.3 updateInventoryProductsStock - aktualizacja stanów
- ❌ 8.1.1.3.4 getInventoryCategories - kategorie BaseLinker
- ❌ 8.1.1.3.5 addInventoryCategory - zarządzanie kategoriami

### ❌ 8.1.2 Analiza Subiekt GT Integration
#### ❌ 8.1.2.1 Subiekt GT DLL Analysis
- ❌ 8.1.2.1.1 Analiza SubiektGT.dll capabilities
- ❌ 8.1.2.1.2 COM/OLE automation interfaces
- ❌ 8.1.2.1.3 Database access patterns (Firebird/MSSQL)
- ❌ 8.1.2.1.4 Transaction handling i session management
- ❌ 8.1.2.1.5 Error handling i exception management

#### ❌ 8.1.2.2 Subiekt GT Data Structures
- ❌ 8.1.2.2.1 Products table structure (tw_towary)
- ❌ 8.1.2.2.2 Stock levels table (tw_stany) 
- ❌ 8.1.2.2.3 Price lists structure (tw_cenniki)
- ❌ 8.1.2.2.4 Categories structure (tw_kategorie)
- ❌ 8.1.2.2.5 Orders structure (dk_dokumenty)

#### ❌ 8.1.2.3 .NET Bridge Architecture
- ❌ 8.1.2.3.1 C# wrapper service design
- ❌ 8.1.2.3.2 JSON API interface for PHP
- ❌ 8.1.2.3.3 Windows Service deployment
- ❌ 8.1.2.3.4 Authentication i security model
- ❌ 8.1.2.3.5 Process monitoring i auto-restart

### ❌ 8.1.3 Analiza Microsoft Dynamics Integration
#### ❌ 8.1.3.1 Dynamics 365 Business Central OData
- ❌ 8.1.3.1.1 OData v4 endpoint analysis
- ❌ 8.1.3.1.2 Authentication via Azure AD/OAuth2
- ❌ 8.1.3.1.3 Entity sets i navigation properties
- ❌ 8.1.3.1.4 Query options ($filter, $expand, $select)
- ❌ 8.1.3.1.5 Batch operations i change sets

#### ❌ 8.1.3.2 Dynamics Data Entities
- ❌ 8.1.3.2.1 Items entity (products) structure
- ❌ 8.1.3.2.2 ItemCategories entity mapping
- ❌ 8.1.3.2.3 ItemVariants i UoM handling
- ❌ 8.1.3.2.4 PricesAndDiscounts entity
- ❌ 8.1.3.2.5 InventoryLevels per location

#### ❌ 8.1.3.3 Dynamics API Limitations
- ❌ 8.1.3.3.1 Rate limits i throttling policies
- ❌ 8.1.3.3.2 Data consistency i transaction scope
- ❌ 8.1.3.3.3 Field mapping i custom fields support
- ❌ 8.1.3.3.4 Delta queries for change tracking
- ❌ 8.1.3.3.5 Error handling i retry patterns

---

## ❌ 8.2 MODELE I MIGRACJE ERP

### ❌ 8.2.1 Tabele konfiguracji ERP
#### ❌ 8.2.1.1 Tabela erp_connections
```sql
CREATE TABLE erp_connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('baselinker', 'subiekt_gt', 'dynamics365') NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sync_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- Connection settings (JSON based on type)
    connection_config JSON NOT NULL,
    
    -- Sync configuration
    sync_frequency ENUM('realtime', '5min', '15min', '30min', '1hour', '4hour', '12hour', '24hour') DEFAULT '30min',
    sync_direction ENUM('pull_only', 'push_only', 'bidirectional') DEFAULT 'bidirectional',
    
    -- Status tracking
    last_sync_at TIMESTAMP NULL,
    last_success_sync_at TIMESTAMP NULL,
    sync_status ENUM('idle', 'syncing', 'error', 'disabled') DEFAULT 'idle',
    error_message TEXT NULL,
    
    -- Performance metrics
    total_syncs INT UNSIGNED DEFAULT 0,
    successful_syncs INT UNSIGNED DEFAULT 0,
    failed_syncs INT UNSIGNED DEFAULT 0,
    avg_sync_duration_ms INT UNSIGNED DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_active (type, is_active),
    INDEX idx_sync_frequency (sync_frequency, sync_enabled),
    INDEX idx_sync_status (sync_status)
);
```

#### ❌ 8.2.1.2 Tabela erp_field_mappings
```sql
CREATE TABLE erp_field_mappings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    connection_id BIGINT UNSIGNED NOT NULL,
    entity_type ENUM('product', 'category', 'warehouse', 'price_group', 'order', 'customer') NOT NULL,
    ppm_field VARCHAR(255) NOT NULL,
    erp_field VARCHAR(255) NOT NULL,
    mapping_direction ENUM('pull_only', 'push_only', 'bidirectional') DEFAULT 'bidirectional',
    transform_rule JSON NULL, -- Rules for data transformation
    is_required BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (connection_id) REFERENCES erp_connections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (connection_id, entity_type, pmp_field),
    INDEX idx_connection_entity (connection_id, entity_type),
    INDEX idx_active_mappings (is_active, mapping_direction)
);
```

### ❌ 8.2.2 Tabele synchronizacji ERP
#### ❌ 8.2.2.1 Tabela erp_sync_jobs
```sql
CREATE TABLE erp_sync_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    connection_id BIGINT UNSIGNED NOT NULL,
    job_type ENUM('full_sync', 'incremental_sync', 'single_entity', 'batch_update') NOT NULL,
    entity_type ENUM('product', 'category', 'stock', 'price', 'order') NOT NULL,
    entity_ids JSON NULL, -- Array of IDs to sync (for single/batch operations)
    
    -- Job configuration
    sync_direction ENUM('pull', 'push') NOT NULL,
    priority TINYINT UNSIGNED DEFAULT 5, -- 1=highest, 10=lowest
    scheduled_at TIMESTAMP NULL,
    
    -- Status tracking
    status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Results
    processed_count INT UNSIGNED DEFAULT 0,
    success_count INT UNSIGNED DEFAULT 0,
    error_count INT UNSIGNED DEFAULT 0,
    result_data JSON NULL, -- Detailed results per entity
    error_details JSON NULL, -- Error details per failed entity
    
    -- Performance
    execution_time_ms INT UNSIGNED NULL,
    memory_usage_mb DECIMAL(8,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (connection_id) REFERENCES erp_connections(id) ON DELETE CASCADE,
    INDEX idx_connection_status (connection_id, status),
    INDEX idx_job_type_priority (job_type, priority, status),
    INDEX idx_scheduled (scheduled_at, status),
    INDEX idx_entity_type (entity_type, sync_direction)
);
```

#### ❌ 8.2.2.2 Tabela erp_entity_sync_status
```sql
CREATE TABLE erp_entity_sync_status (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    connection_id BIGINT UNSIGNED NOT NULL,
    entity_type ENUM('product', 'category', 'warehouse', 'order') NOT NULL,
    ppm_entity_id BIGINT UNSIGNED NOT NULL, -- ID in PPM system
    erp_entity_id VARCHAR(255) NOT NULL, -- ID in ERP system (can be non-numeric)
    
    -- Sync tracking
    sync_status ENUM('synced', 'pending', 'error', 'conflict', 'disabled') DEFAULT 'pending',
    last_sync_at TIMESTAMP NULL,
    last_success_sync_at TIMESTAMP NULL,
    sync_direction ENUM('pull', 'push', 'bidirectional') DEFAULT 'bidirectional',
    
    -- Change detection
    ppm_checksum VARCHAR(64) NULL, -- MD5 of PPM data
    erp_checksum VARCHAR(64) NULL, -- MD5 of ERP data
    
    -- Conflict resolution
    conflict_data JSON NULL,
    conflict_resolution ENUM('use_ppm', 'use_erp', 'manual', 'skip') NULL,
    
    -- Error handling
    error_message TEXT NULL,
    retry_count TINYINT UNSIGNED DEFAULT 0,
    max_retries TINYINT UNSIGNED DEFAULT 3,
    next_retry_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (connection_id) REFERENCES erp_connections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_entity_sync (connection_id, entity_type, ppm_entity_id),
    INDEX idx_connection_status (connection_id, sync_status),
    INDEX idx_entity_status (entity_type, sync_status),
    INDEX idx_retry_queue (next_retry_at, retry_count, max_retries),
    INDEX idx_conflict_resolution (conflict_resolution)
);
```

### ❌ 8.2.3 Tabele logowania ERP
#### ❌ 8.2.3.1 Tabela erp_sync_logs
```sql
CREATE TABLE erp_sync_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    connection_id BIGINT UNSIGNED NOT NULL,
    sync_job_id BIGINT UNSIGNED NULL,
    operation ENUM('create', 'update', 'delete', 'read', 'batch_operation') NOT NULL,
    entity_type ENUM('product', 'category', 'stock', 'price', 'order') NOT NULL,
    entity_id VARCHAR(255) NULL, -- Can be PPM or ERP ID
    direction ENUM('pull', 'push') NOT NULL,
    
    -- Operation details
    operation_data JSON NULL, -- Data sent/received
    response_data JSON NULL, -- Response from ERP
    
    -- Status and timing
    status ENUM('success', 'warning', 'error') NOT NULL,
    message TEXT NULL,
    execution_time_ms INT UNSIGNED NULL,
    
    -- Error details
    error_code VARCHAR(50) NULL,
    error_details JSON NULL,
    
    -- Context
    user_id BIGINT UNSIGNED NULL, -- Who triggered the operation
    ip_address INET6 NULL,
    user_agent TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (connection_id) REFERENCES erp_connections(id) ON DELETE CASCADE,
    FOREIGN KEY (sync_job_id) REFERENCES erp_sync_jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_connection_operation (connection_id, operation, created_at),
    INDEX idx_job_logs (sync_job_id, status),
    INDEX idx_status_created (status, created_at),
    INDEX idx_entity_logs (entity_type, entity_id, created_at)
);
```

---

## ❌ 8.3 BASELINKER INTEGRATION SERVICE
**🔗 🔗 POWIAZANIE Z ETAP_04 (sekcja 3.1.2) oraz ETAP_06 (punkt 5.2.2.2.1):** Serwis BaseLinker jest wykorzystywany przez panel ERP i eksporty CSV, wymagajac spójnych pol i konfiguracji.

### ❌ 8.3.1 BaseLinkerClient
**🔗 🔗 POWIAZANIE Z ETAP_04 (punkt 3.1.1.1.2):** Statusy polaczen w panelu admin opieraja sie na tej klasie klienta.
#### ❌ 8.3.1.1 Klasa BaseLinkerApiClient
```php
<?php
namespace App\Services\ERP\BaseLinker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ErpConnection;
use App\Exceptions\BaseLinkerException;

class BaseLinkerApiClient
{
    protected ErpConnection $connection;
    protected string $apiUrl = 'https://api.baselinker.com/connector.php';
    protected int $timeout = 30;
    protected int $rateLimitPerMinute = 60;
    
    public function __construct(ErpConnection $connection)
    {
        $this->connection = $connection;
    }
    
    protected function makeRequest(string $method, array $parameters = []): array
    {
        $this->checkRateLimit();
        
        $postData = [
            'token' => $this->connection->connection_config['api_key'],
            'method' => $method,
            'parameters' => json_encode($parameters)
        ];
        
        $response = Http::timeout($this->timeout)
            ->asForm()
            ->post($this->apiUrl, $postData);
            
        $this->logRequest($method, $parameters, $response);
        
        if (!$response->successful()) {
            throw new BaseLinkerException("BaseLinker API error: " . $response->body());
        }
        
        $data = $response->json();
        
        if ($data['status'] !== 'SUCCESS') {
            throw new BaseLinkerException("BaseLinker API error: " . ($data['error_message'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    protected function checkRateLimit(): void
    {
        $cacheKey = "baselinker_rate_limit_{$this->connection->id}";
        $requests = Cache::get($cacheKey, 0);
        
        if ($requests >= $this->rateLimitPerMinute) {
            throw new BaseLinkerException('Rate limit exceeded. Try again later.');
        }
        
        Cache::put($cacheKey, $requests + 1, now()->addMinute());
    }
    
    // Product operations
    public function getInventoryProductsList(int $inventoryId, array $filters = []): array
    {
        $parameters = array_merge(['inventory_id' => $inventoryId], $filters);
        return $this->makeRequest('getInventoryProductsList', $parameters);
    }
    
    public function addInventoryProduct(int $inventoryId, array $productData): array
    {
        $parameters = [
            'inventory_id' => $inventoryId,
            'product_id' => $productData['product_id'] ?? '',
            'parent_id' => $productData['parent_id'] ?? 0,
            'is_bundle' => $productData['is_bundle'] ?? false,
            'sku' => $productData['sku'],
            'name' => $productData['name'],
            'quantity' => $productData['quantity'] ?? 0,
            'price_brutto' => $productData['price_brutto'] ?? 0,
            'tax_rate' => $productData['tax_rate'] ?? 23,
            'weight' => $productData['weight'] ?? 0,
            'description_short' => $productData['description_short'] ?? '',
            'description_long' => $productData['description_long'] ?? '',
            'images' => $productData['images'] ?? [],
            'category_id' => $productData['category_id'] ?? 0
        ];
        
        return $this->makeRequest('addInventoryProduct', $parameters);
    }
    
    public function updateInventoryProduct(int $inventoryId, string $productId, array $productData): array
    {
        $parameters = [
            'inventory_id' => $inventoryId,
            'product_id' => $productId,
            'products' => [$productId => $productData]
        ];
        
        return $this->makeRequest('updateInventoryProducts', $parameters);
    }
    
    public function deleteInventoryProduct(int $inventoryId, string $productId): array
    {
        return $this->makeRequest('deleteInventoryProduct', [
            'inventory_id' => $inventoryId,
            'product_id' => $productId
        ]);
    }
    
    // Stock operations
    public function getInventoryProductsStock(int $inventoryId, array $products = []): array
    {
        $parameters = [
            'inventory_id' => $inventoryId
        ];
        
        if (!empty($products)) {
            $parameters['products'] = $products;
        }
        
        return $this->makeRequest('getInventoryProductsStock', $parameters);
    }
    
    public function updateInventoryProductsStock(int $inventoryId, array $stockUpdates): array
    {
        return $this->makeRequest('updateInventoryProductsStock', [
            'inventory_id' => $inventoryId,
            'products' => $stockUpdates
        ]);
    }
    
    // Category operations
    public function getInventoryCategories(int $inventoryId): array
    {
        return $this->makeRequest('getInventoryCategories', [
            'inventory_id' => $inventoryId
        ]);
    }
    
    public function addInventoryCategory(int $inventoryId, string $name, int $parentId = 0): array
    {
        return $this->makeRequest('addInventoryCategory', [
            'inventory_id' => $inventoryId,
            'category_id' => 0, // 0 for new category
            'name' => $name,
            'parent_id' => $parentId
        ]);
    }
    
    // Order operations
    public function getOrders(array $filters = []): array
    {
        return $this->makeRequest('getOrders', $filters);
    }
    
    public function getInventories(): array
    {
        return $this->makeRequest('getInventories');
    }
    
    protected function logRequest(string $method, array $parameters, $response): void
    {
        Log::channel('erp')->info('BaseLinker API Request', [
            'connection_id' => $this->connection->id,
            'method' => $method,
            'parameters_count' => count($parameters),
            'status_code' => $response->status(),
            'execution_time' => $response->transferStats?->getTransferTime(),
            'success' => $response->successful()
        ]);
    }
}
```

### ❌ 8.3.2 BaseLinker Sync Service
#### ❌ 8.3.2.1 BaseLinkerSyncService
```php
<?php
namespace App\Services\ERP\BaseLinker;

use App\Models\Product;
use App\Models\ErpConnection;
use App\Models\ErpEntitySyncStatus;
use App\Services\ERP\BaseLinker\Transformers\BaseLinkerProductTransformer;
use Illuminate\Support\Collection;

class BaseLinkerSyncService
{
    protected BaseLinkerApiClient $client;
    protected BaseLinkerProductTransformer $transformer;
    protected ErpConnection $connection;
    
    public function __construct(ErpConnection $connection)
    {
        $this->connection = $connection;
        $this->client = new BaseLinkerApiClient($connection);
        $this->transformer = new BaseLinkerProductTransformer($connection);
    }
    
    public function syncProductToBaseLinker(Product $product): bool
    {
        try {
            $syncStatus = ErpEntitySyncStatus::firstOrCreate([
                'connection_id' => $this->connection->id,
                'entity_type' => 'product',
                'ppm_entity_id' => $product->id
            ]);
            
            $syncStatus->update(['sync_status' => 'pending']);
            
            // Get inventory ID from connection config
            $inventoryId = $this->connection->connection_config['inventory_id'];
            
            // Transform product data
            $baselinkerData = $this->transformer->transformForBaseLinker($product);
            
            // Create or update in BaseLinker
            if ($syncStatus->erp_entity_id) {
                $response = $this->client->updateInventoryProduct(
                    $inventoryId, 
                    $syncStatus->erp_entity_id, 
                    $baselinkerData
                );
            } else {
                $response = $this->client->addInventoryProduct($inventoryId, $baselinkerData);
                $syncStatus->erp_entity_id = $response['product_id'] ?? $product->sku;
            }
            
            // Update sync status
            $syncStatus->update([
                'sync_status' => 'synced',
                'last_sync_at' => now(),
                'last_success_sync_at' => now(),
                'ppm_checksum' => $this->transformer->calculateProductChecksum($product),
                'error_message' => null,
                'retry_count' => 0
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $syncStatus->update([
                'sync_status' => 'error',
                'error_message' => $e->getMessage(),
                'retry_count' => $syncStatus->retry_count + 1,
                'next_retry_at' => now()->addMinutes(pow(2, $syncStatus->retry_count)) // Exponential backoff
            ]);
            
            return false;
        }
    }
    
    public function syncProductFromBaseLinker(string $baselinkerProductId): bool
    {
        try {
            $inventoryId = $this->connection->connection_config['inventory_id'];
            
            // Get product data from BaseLinker
            $response = $this->client->getInventoryProductsList($inventoryId, [
                'products' => [$baselinkerProductId]
            ]);
            
            if (empty($response['products'])) {
                throw new \Exception("Product not found in BaseLinker: {$baselinkerProductId}");
            }
            
            $baselinkerProduct = $response['products'][$baselinkerProductId];
            
            // Transform and create/update PPM product
            $product = $this->transformer->transformFromBaseLinker($baselinkerProduct);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('BaseLinker sync error', [
                'connection_id' => $this->connection->id,
                'product_id' => $baselinkerProductId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function syncAllProducts(array $filters = []): array
    {
        $results = ['success' => 0, 'errors' => 0, 'details' => []];
        
        $products = Product::active();
        
        if (!empty($filters['categories'])) {
            $products->whereIn('category_id', $filters['categories']);
        }
        
        foreach ($products->get() as $product) {
            $success = $this->syncProductToBaseLinker($product);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
            
            $results['details'][$product->id] = $success;
        }
        
        return $results;
    }
    
    public function syncStock(Product $product): bool
    {
        try {
            $inventoryId = $this->connection->connection_config['inventory_id'];
            
            $syncStatus = ErpEntitySyncStatus::where('connection_id', $this->connection->id)
                ->where('entity_type', 'product')
                ->where('ppm_entity_id', $product->id)
                ->first();
                
            if (!$syncStatus || !$syncStatus->erp_entity_id) {
                throw new \Exception('Product not synced to BaseLinker yet');
            }
            
            // Calculate total stock for BaseLinker
            $totalStock = $product->stock->sum('quantity');
            
            $response = $this->client->updateInventoryProductsStock($inventoryId, [
                $syncStatus->erp_entity_id => [
                    'quantity' => $totalStock
                ]
            ]);
            
            return $response['status'] === 'SUCCESS';
            
        } catch (\Exception $e) {
            Log::error('BaseLinker stock sync error', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
```

---

## ❌ 8.4 SUBIEKT GT INTEGRATION SERVICE
**🔗 🔗 POWIAZANIE Z ETAP_04 (sekcja 3.1.3) oraz ETAP_06 (punkt 5.2.2.2.2):** Moduly panelu i eksporty do Subiekt korzystaja z tego samego bridge i mapowan.

### ❌ 8.4.1 .NET Bridge Service
#### ❌ 8.4.1.1 SubiektGTBridge.cs - Windows Service
**🔗 🔗 POWIAZANIE Z ETAP_04 (punkt 3.1.3.1):** Konfiguracja bridge w panelu admin musi uzywac tych samych ustawien polaczenia.
```csharp
using System;
using System.Collections.Generic;
using System.ServiceProcess;
using System.Threading;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using Newtonsoft.Json;
using SubiektGT;

namespace PPM.SubiektGTBridge
{
    public partial class SubiektGTBridgeService : ServiceBase
    {
        private IHost _host;
        private CancellationTokenSource _cancellationTokenSource;
        private ILogger<SubiektGTBridgeService> _logger;
        
        public SubiektGTBridgeService()
        {
            InitializeComponent();
        }
        
        protected override void OnStart(string[] args)
        {
            _cancellationTokenSource = new CancellationTokenSource();
            
            _host = Host.CreateDefaultBuilder()
                .ConfigureWebHostDefaults(webBuilder =>
                {
                    webBuilder.UseUrls("http://localhost:8080");
                    webBuilder.UseStartup<Startup>();
                })
                .ConfigureServices(services =>
                {
                    services.AddSingleton<ISubiektGTService, SubiektGTService>();
                    services.AddLogging();
                })
                .Build();
                
            Task.Run(() => _host.RunAsync(_cancellationTokenSource.Token));
        }
        
        protected override void OnStop()
        {
            _cancellationTokenSource?.Cancel();
            _host?.StopAsync().Wait(TimeSpan.FromSeconds(30));
            _host?.Dispose();
        }
    }
    
    public interface ISubiektGTService
    {
        Task<string> GetProducts(string filters = "");
        Task<string> GetProduct(string productId);
        Task<string> CreateProduct(string productData);
        Task<string> UpdateProduct(string productId, string productData);
        Task<string> DeleteProduct(string productId);
        Task<string> GetStock(string productId = "");
        Task<string> UpdateStock(string stockData);
    }
    
    public class SubiektGTService : ISubiektGTService
    {
        private readonly ILogger<SubiektGTService> _logger;
        private readonly object _lock = new object();
        
        public SubiektGTService(ILogger<SubiektGTService> logger)
        {
            _logger = logger;
        }
        
        public async Task<string> GetProducts(string filters = "")
        {
            return await ExecuteWithSubiektGT(async (gt) =>
            {
                var products = new List<object>();
                
                // Access Subiekt GT COM objects
                var tovary = gt.Tovary;
                tovary.Filtr = filters;
                
                while (!tovary.EOF)
                {
                    var product = new
                    {
                        Id = tovary.Pola["tw_id"].Wartosc,
                        Name = tovary.Pola["tw_nazwa"].Wartosc,
                        SKU = tovary.Pola["tw_symbol"].Wartosc,
                        Price = tovary.Pola["tw_cena_sprz"].Wartosc,
                        Stock = tovary.Pola["tw_stan"].Wartosc,
                        Category = tovary.Pola["tw_kategoria"].Wartosc,
                        Description = tovary.Pola["tw_opis"].Wartosc,
                        IsActive = tovary.Pola["tw_aktywny"].Wartosc,
                        VAT = tovary.Pola["tw_stawka_vat"].Wartosc
                    };
                    
                    products.Add(product);
                    tovary.Nastepny();
                }
                
                return JsonConvert.SerializeObject(new { status = "success", data = products });
            });
        }
        
        public async Task<string> CreateProduct(string productData)
        {
            return await ExecuteWithSubiektGT(async (gt) =>
            {
                var productInfo = JsonConvert.DeserializeObject<dynamic>(productData);
                
                var tovary = gt.Tovary;
                tovary.Nowy();
                
                // Set product fields
                tovary.Pola["tw_nazwa"].Wartosc = productInfo.Name;
                tovary.Pola["tw_symbol"].Wartosc = productInfo.SKU;
                tovary.Pola["tw_cena_sprz"].Wartosc = productInfo.Price;
                tovary.Pola["tw_opis"].Wartosc = productInfo.Description ?? "";
                tovary.Pola["tw_aktywny"].Wartosc = productInfo.IsActive ?? true;
                tovary.Pola["tw_stawka_vat"].Wartosc = productInfo.VAT ?? 23;
                
                // Save product
                tovary.Zapisz();
                var newId = tovary.Pola["tw_id"].Wartosc;
                
                return JsonConvert.SerializeObject(new { status = "success", id = newId });
            });
        }
        
        private async Task<string> ExecuteWithSubiektGT<T>(Func<dynamic, Task<T>> action)
        {
            return await Task.Run(() =>
            {
                lock (_lock)
                {
                    try
                    {
                        // Initialize Subiekt GT COM object
                        var gt = Activator.CreateInstance(Type.GetTypeFromProgID("Subiekt.Application"));
                        
                        // Connect to database
                        var connectionResult = gt.Polacz("server", "database", "username", "password");
                        
                        if (!connectionResult)
                        {
                            throw new Exception("Failed to connect to Subiekt GT database");
                        }
                        
                        var result = action(gt).Result;
                        
                        // Disconnect
                        gt.Rozlacz();
                        
                        return result.ToString();
                    }
                    catch (Exception ex)
                    {
                        _logger.LogError(ex, "Subiekt GT operation failed");
                        return JsonConvert.SerializeObject(new { status = "error", message = ex.Message });
                    }
                }
            });
        }
    }
}
```

### ❌ 8.4.2 PHP Subiekt GT Client
#### ❌ 8.4.2.1 SubiektGTClient.php
```php
<?php
namespace App\Services\ERP\SubiektGT;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ErpConnection;
use App\Exceptions\SubiektGTException;

class SubiektGTClient
{
    protected ErpConnection $connection;
    protected string $bridgeUrl;
    protected int $timeout = 60; // Longer timeout for database operations
    
    public function __construct(ErpConnection $connection)
    {
        $this->connection = $connection;
        $this->bridgeUrl = $connection->connection_config['bridge_url'] ?? 'http://localhost:8080';
    }
    
    protected function makeRequest(string $endpoint, array $data = [], string $method = 'GET'): array
    {
        $url = rtrim($this->bridgeUrl, '/') . '/' . ltrim($endpoint, '/');
        
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->connection->connection_config['api_key'] ?? ''
                ]);
            
            if ($method === 'GET') {
                $response = $response->get($url, $data);
            } else {
                $response = $response->$method($url, $data);
            }
            
            $this->logRequest($endpoint, $data, $response, $method);
            
            if (!$response->successful()) {
                throw new SubiektGTException("Bridge API error: " . $response->body());
            }
            
            $result = $response->json();
            
            if ($result['status'] !== 'success') {
                throw new SubiektGTException("Subiekt GT error: " . ($result['message'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Subiekt GT API Error', [
                'connection_id' => $this->connection->id,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            
            throw new SubiektGTException("Subiekt GT connection failed: " . $e->getMessage());
        }
    }
    
    public function getProducts(string $filters = ''): array
    {
        return $this->makeRequest('api/products', ['filters' => $filters]);
    }
    
    public function getProduct(string $productId): array
    {
        return $this->makeRequest("api/products/{$productId}");
    }
    
    public function createProduct(array $productData): array
    {
        return $this->makeRequest('api/products', $productData, 'POST');
    }
    
    public function updateProduct(string $productId, array $productData): array
    {
        return $this->makeRequest("api/products/{$productId}", $productData, 'PUT');
    }
    
    public function deleteProduct(string $productId): array
    {
        return $this->makeRequest("api/products/{$productId}", [], 'DELETE');
    }
    
    public function getStock(string $productId = ''): array
    {
        $endpoint = $productId ? "api/stock/{$productId}" : 'api/stock';
        return $this->makeRequest($endpoint);
    }
    
    public function updateStock(array $stockData): array
    {
        return $this->makeRequest('api/stock', $stockData, 'PUT');
    }
    
    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('api/health');
            return $response['status'] === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function logRequest(string $endpoint, array $data, $response, string $method): void
    {
        Log::channel('erp')->info('Subiekt GT Bridge Request', [
            'connection_id' => $this->connection->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'data_size' => strlen(json_encode($data)),
            'status_code' => $response->status(),
            'execution_time' => $response->transferStats?->getTransferTime(),
            'success' => $response->successful()
        ]);
    }
}
```

---

## ❌ 8.5 MICROSOFT DYNAMICS INTEGRATION
**🔗 🔗 POWIAZANIE Z ETAP_04 (sekcja 3.1.4) oraz ETAP_06 (punkt 5.2.2.2.3):** Integracja Dynamics jest kontrolowana z panelu ERP i wymaga zgodnych formatow eksportu.

### ❌ 8.5.1 Dynamics OData Client
**🔗 🔗 POWIAZANIE Z ETAP_04 (punkt 3.1.4.1):** Testy polaczen w panelu admin bazuja na metodach tego klienta.
#### ❌ 8.5.1.1 DynamicsODataClient.php
```php
<?php
namespace App\Services\ERP\Dynamics;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ErpConnection;
use App\Exceptions\DynamicsException;
use Microsoft\Graph\Auth\ClientCredentialAuth;

class DynamicsODataClient
{
    protected ErpConnection $connection;
    protected string $baseUrl;
    protected string $accessToken;
    protected int $timeout = 45;
    
    public function __construct(ErpConnection $connection)
    {
        $this->connection = $connection;
        $this->baseUrl = $connection->connection_config['odata_url'];
    }
    
    protected function getAccessToken(): string
    {
        $cacheKey = "dynamics_token_{$this->connection->id}";
        
        return Cache::remember($cacheKey, 3500, function () { // 58 minutes (token expires in 1h)
            $config = $this->connection->connection_config;
            
            $response = Http::asForm()->post('https://login.microsoftonline.com/' . $config['tenant_id'] . '/oauth2/v2.0/token', [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'scope' => 'https://api.businesscentral.dynamics.com/.default',
                'grant_type' => 'client_credentials'
            ]);
            
            if (!$response->successful()) {
                throw new DynamicsException('Failed to get access token: ' . $response->body());
            }
            
            return $response->json()['access_token'];
        });
    }
    
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $response = Http::timeout($this->timeout)
            ->withToken($this->getAccessToken())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'OData-MaxVersion' => '4.0',
                'OData-Version' => '4.0'
            ]);
            
        switch (strtoupper($method)) {
            case 'GET':
                $response = $response->get($url, $data);
                break;
            case 'POST':
                $response = $response->post($url, $data);
                break;
            case 'PATCH':
                $response = $response->patch($url, $data);
                break;
            case 'DELETE':
                $response = $response->delete($url);
                break;
        }
        
        $this->logRequest($method, $endpoint, $data, $response);
        
        if (!$response->successful()) {
            $error = $response->json();
            throw new DynamicsException(
                "Dynamics API error: " . ($error['error']['message'] ?? $response->body())
            );
        }
        
        return $response->json();
    }
    
    // Items (Products) operations
    public function getItems(array $filters = []): array
    {
        $query = '';
        
        if (!empty($filters['select'])) {
            $query .= '$select=' . implode(',', $filters['select']);
        }
        
        if (!empty($filters['filter'])) {
            $query .= ($query ? '&' : '') . '$filter=' . $filters['filter'];
        }
        
        if (!empty($filters['expand'])) {
            $query .= ($query ? '&' : '') . '$expand=' . implode(',', $filters['expand']);
        }
        
        $endpoint = 'items' . ($query ? '?' . $query : '');
        return $this->makeRequest('GET', $endpoint);
    }
    
    public function getItem(string $itemId, array $expand = []): array
    {
        $endpoint = "items('{$itemId}')";
        
        if (!empty($expand)) {
            $endpoint .= '?$expand=' . implode(',', $expand);
        }
        
        return $this->makeRequest('GET', $endpoint);
    }
    
    public function createItem(array $itemData): array
    {
        return $this->makeRequest('POST', 'items', $itemData);
    }
    
    public function updateItem(string $itemId, array $itemData, string $etag = ''): array
    {
        $headers = [];
        if ($etag) {
            $headers['If-Match'] = $etag;
        }
        
        return $this->makeRequest('PATCH', "items('{$itemId}')", $itemData);
    }
    
    public function deleteItem(string $itemId, string $etag = ''): bool
    {
        $this->makeRequest('DELETE', "items('{$itemId}')");
        return true;
    }
    
    // Item Categories operations
    public function getItemCategories(): array
    {
        return $this->makeRequest('GET', 'itemCategories');
    }
    
    public function createItemCategory(array $categoryData): array
    {
        return $this->makeRequest('POST', 'itemCategories', $categoryData);
    }
    
    // Inventory operations
    public function getItemLedgerEntries(array $filters = []): array
    {
        $query = '';
        
        if (!empty($filters['item_no'])) {
            $query = '$filter=itemNumber eq \'' . $filters['item_no'] . '\'';
        }
        
        if (!empty($filters['location_code'])) {
            $filterPart = 'locationCode eq \'' . $filters['location_code'] . '\'';
            $query = $query ? $query . ' and ' . $filterPart : '$filter=' . $filterPart;
        }
        
        $endpoint = 'itemLedgerEntries' . ($query ? '?' . $query : '');
        return $this->makeRequest('GET', $endpoint);
    }
    
    // Batch operations
    public function executeBatch(array $requests): array
    {
        $batchData = [
            'requests' => $requests
        ];
        
        return $this->makeRequest('POST', '$batch', $batchData);
    }
    
    protected function logRequest(string $method, string $endpoint, array $data, $response): void
    {
        Log::channel('erp')->info('Dynamics API Request', [
            'connection_id' => $this->connection->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'data_size' => strlen(json_encode($data)),
            'status_code' => $response->status(),
            'execution_time' => $response->transferStats?->getTransferTime(),
            'success' => $response->successful()
        ]);
    }
}
```

### ❌ 8.5.2 Dynamics Sync Service
#### ❌ 8.5.2.1 DynamicsSyncService.php
```php
<?php
namespace App\Services\ERP\Dynamics;

use App\Models\Product;
use App\Models\ErpConnection;
use App\Models\ErpEntitySyncStatus;
use App\Services\ERP\Dynamics\Transformers\DynamicsProductTransformer;

class DynamicsSyncService
{
    protected DynamicsODataClient $client;
    protected DynamicsProductTransformer $transformer;
    protected ErpConnection $connection;
    
    public function __construct(ErpConnection $connection)
    {
        $this->connection = $connection;
        $this->client = new DynamicsODataClient($connection);
        $this->transformer = new DynamicsProductTransformer($connection);
    }
    
    public function syncProductToDynamics(Product $product): bool
    {
        try {
            $syncStatus = ErpEntitySyncStatus::firstOrCreate([
                'connection_id' => $this->connection->id,
                'entity_type' => 'product',
                'pmp_entity_id' => $product->id
            ]);
            
            $syncStatus->update(['sync_status' => 'pending']);
            
            // Transform product data for Dynamics
            $dynamicsData = $this->transformer->transformForDynamics($product);
            
            // Create or update in Dynamics
            if ($syncStatus->erp_entity_id) {
                // Get current ETag for optimistic concurrency
                $currentItem = $this->client->getItem($syncStatus->erp_entity_id);
                $etag = $currentItem['@odata.etag'] ?? '';
                
                $response = $this->client->updateItem(
                    $syncStatus->erp_entity_id,
                    $dynamicsData,
                    $etag
                );
            } else {
                $response = $this->client->createItem($dynamicsData);
                $syncStatus->erp_entity_id = $response['number'];
            }
            
            // Update sync status
            $syncStatus->update([
                'sync_status' => 'synced',
                'last_sync_at' => now(),
                'last_success_sync_at' => now(),
                'ppm_checksum' => $this->transformer->calculateProductChecksum($product),
                'error_message' => null,
                'retry_count' => 0
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $syncStatus->update([
                'sync_status' => 'error',
                'error_message' => $e->getMessage(),
                'retry_count' => $syncStatus->retry_count + 1,
                'next_retry_at' => now()->addMinutes(pow(2, $syncStatus->retry_count))
            ]);
            
            return false;
        }
    }
    
    public function syncProductFromDynamics(string $itemNumber): bool
    {
        try {
            // Get item from Dynamics with inventory details
            $response = $this->client->getItem($itemNumber, ['itemLedgerEntries']);
            
            if (empty($response)) {
                throw new \Exception("Item not found in Dynamics: {$itemNumber}");
            }
            
            // Transform and create/update PPM product
            $product = $this->transformer->transformFromDynamics($response);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Dynamics sync error', [
                'connection_id' => $this->connection->id,
                'item_number' => $itemNumber,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function syncInventory(Product $product): bool
    {
        try {
            $syncStatus = ErpEntitySyncStatus::where('connection_id', $this->connection->id)
                ->where('entity_type', 'product')
                ->where('ppm_entity_id', $product->id)
                ->first();
                
            if (!$syncStatus || !$syncStatus->erp_entity_id) {
                throw new \Exception('Product not synced to Dynamics yet');
            }
            
            // Get current inventory from Dynamics
            $ledgerEntries = $this->client->getItemLedgerEntries([
                'item_no' => $syncStatus->erp_entity_id
            ]);
            
            // Calculate total quantity by location
            $dynamicsStock = [];
            foreach ($ledgerEntries['value'] as $entry) {
                $location = $entry['locationCode'];
                $quantity = $entry['quantity'];
                
                if (!isset($dynamicsStock[$location])) {
                    $dynamicsStock[$location] = 0;
                }
                $dynamicsStock[$location] += $quantity;
            }
            
            // Update PPM stock levels to match Dynamics
            foreach ($dynamicsStock as $location => $quantity) {
                $warehouse = $this->mapDynamicsLocationToWarehouse($location);
                if ($warehouse) {
                    $product->stock()->updateOrCreate(
                        ['warehouse_code' => $warehouse],
                        ['quantity' => max(0, $quantity)]
                    );
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Dynamics inventory sync error', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    protected function mapDynamicsLocationToWarehouse(string $locationCode): ?string
    {
        $mapping = $this->connection->fieldMappings()
            ->where('entity_type', 'warehouse')
            ->where('erp_field', $locationCode)
            ->first();
            
        return $mapping?->pmp_field;
    }
}
```

---

## ❌ 8.6 UNIFIED ERP SERVICE LAYER

### ❌ 8.6.1 ERPServiceManager
#### ❌ 8.6.1.1 Unified interface dla wszystkich ERP
```php
<?php
namespace App\Services\ERP;

use App\Models\ErpConnection;
use App\Models\Product;
use App\Services\ERP\BaseLinker\BaseLinkerSyncService;
use App\Services\ERP\SubiektGT\SubiektGTSyncService;
use App\Services\ERP\Dynamics\DynamicsSyncService;

class ERPServiceManager
{
    protected array $services = [];
    
    public function getService(ErpConnection $connection): ERPSyncServiceInterface
    {
        $key = $connection->type . '_' . $connection->id;
        
        if (!isset($this->services[$key])) {
            $this->services[$key] = $this->createService($connection);
        }
        
        return $this->services[$key];
    }
    
    protected function createService(ErpConnection $connection): ERPSyncServiceInterface
    {
        return match($connection->type) {
            'baselinker' => new BaseLinkerSyncService($connection),
            'subiekt_gt' => new SubiektGTSyncService($connection),
            'dynamics365' => new DynamicsSyncService($connection),
            default => throw new \InvalidArgumentException("Unsupported ERP type: {$connection->type}")
        };
    }
    
    public function syncProductToAllERP(Product $product): array
    {
        $results = [];
        
        $connections = ErpConnection::where('is_active', true)
            ->where('sync_enabled', true)
            ->where('sync_direction', 'LIKE', '%push%')
            ->orWhere('sync_direction', 'bidirectional')
            ->get();
            
        foreach ($connections as $connection) {
            $service = $this->getService($connection);
            $results[$connection->id] = $service->syncProductToERP($product);
        }
        
        return $results;
    }
    
    public function syncAllFromERP(): array
    {
        $results = [];
        
        $connections = ErpConnection::where('is_active', true)
            ->where('sync_enabled', true)
            ->where('sync_direction', 'LIKE', '%pull%')
            ->orWhere('sync_direction', 'bidirectional')
            ->get();
            
        foreach ($connections as $connection) {
            $service = $this->getService($connection);
            $results[$connection->id] = $service->syncAllFromERP();
        }
        
        return $results;
    }
}
```

### ❌ 8.6.2 ERP Interface
#### ❌ 8.6.2.1 ERPSyncServiceInterface
```php
<?php
namespace App\Services\ERP;

use App\Models\Product;

interface ERPSyncServiceInterface
{
    public function syncProductToERP(Product $product): bool;
    public function syncProductFromERP(string $erpProductId): bool;
    public function syncAllProducts(): array;
    public function syncStock(Product $product): bool;
    public function testConnection(): bool;
}
```

---

## ❌ 8.7 JOBS I QUEUE SYSTEM

### ❌ 8.7.1 ERP Sync Jobs
#### ❌ 8.7.1.1 SyncProductToERP Job
```php
<?php
namespace App\Jobs\ERP;

use App\Models\Product;
use App\Models\ErpConnection;
use App\Services\ERP\ERPServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductToERP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected Product $product;
    protected ErpConnection $connection;
    
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    
    public function __construct(Product $product, ErpConnection $connection)
    {
        $this->product = $product;
        $this->connection = $connection;
        
        // Set queue priority
        $this->onQueue($this->product->is_featured ? 'erp_high' : 'erp_default');
    }
    
    public function handle(ERPServiceManager $erpManager): void
    {
        $service = $erpManager->getService($this->connection);
        $service->syncProductToERP($this->product);
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('ERP sync job failed', [
            'product_id' => $this->product->id,
            'connection_id' => $this->connection->id,
            'connection_type' => $this->connection->type,
            'error' => $exception->getMessage()
        ]);
        
        // Optionally send notification to admin
    }
}
```

---

## ❌ 8.8 PANEL ADMINISTRACYJNY ERP

### ❌ 8.8.1 ERP Connections Manager
#### ❌ 8.8.1.1 ERPConnectionManager Livewire Component
```php
<?php
namespace App\Livewire\Admin;

use App\Models\ErpConnection;
use App\Services\ERP\ERPServiceManager;
use Livewire\Component;
use Livewire\WithPagination;

class ERPConnectionManager extends Component
{
    use WithPagination;
    
    public $name = '';
    public $type = 'baselinker';
    public $connectionConfig = [];
    public $syncDirection = 'bidirectional';
    public $syncFrequency = '30min';
    public $editingConnectionId = null;
    public $showModal = false;
    public $testingConnection = false;
    
    protected $rules = [
        'name' => 'required|min:3|max:255',
        'type' => 'required|in:baselinker,subiekt_gt,dynamics365',
        'syncDirection' => 'required|in:pull_only,push_only,bidirectional',
        'syncFrequency' => 'required|in:realtime,5min,15min,30min,1hour,4hour,12hour,24hour'
    ];
    
    public function render()
    {
        return view('livewire.admin.erp-connection-manager', [
            'connections' => ErpConnection::with('syncStatus')
                ->orderBy('created_at', 'desc')
                ->paginate(15)
        ]);
    }
    
    public function testConnection(ERPServiceManager $erpManager)
    {
        $this->testingConnection = true;
        
        try {
            $tempConnection = new ErpConnection([
                'name' => $this->name,
                'type' => $this->type,
                'connection_config' => $this->connectionConfig
            ]);
            
            $service = $erpManager->getService($tempConnection);
            $result = $service->testConnection();
            
            if ($result) {
                session()->flash('message', 'Połączenie z ERP udane!');
            } else {
                session()->flash('error', 'Błąd połączenia z ERP');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd połączenia: ' . $e->getMessage());
        } finally {
            $this->testingConnection = false;
        }
    }
    
    public function saveConnection()
    {
        $this->validate();
        
        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'connection_config' => $this->connectionConfig,
            'sync_direction' => $this->syncDirection,
            'sync_frequency' => $this->syncFrequency,
            'is_active' => true,
            'sync_enabled' => true
        ];
        
        if ($this->editingConnectionId) {
            $connection = ErpConnection::find($this->editingConnectionId);
            $connection->update($data);
        } else {
            ErpConnection::create($data);
        }
        
        $this->resetModal();
        session()->flash('message', 'Połączenie ERP zostało zapisane.');
    }
    
    public function syncConnection($connectionId, ERPServiceManager $erpManager)
    {
        $connection = ErpConnection::findOrFail($connectionId);
        $service = $erpManager->getService($connection);
        
        try {
            $result = $service->syncAllProducts();
            session()->flash('message', "Synchronizacja ukończona. Sukces: {$result['success']}, Błędy: {$result['errors']}");
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd synchronizacji: ' . $e->getMessage());
        }
    }
}
```

---

## ❌ 8.9 MONITORING I RAPORTY

### ❌ 8.9.1 ERP Dashboard
#### ❌ 8.9.1.1 ERPDashboard Component
```php
<?php
namespace App\Livewire\Admin;

use App\Models\ErpConnection;
use App\Models\ErpSyncJob;
use App\Models\ErpSyncLog;
use App\Models\ErpEntitySyncStatus;
use Livewire\Component;
use Carbon\Carbon;

class ERPDashboard extends Component
{
    public $selectedConnection = null;
    public $dateFrom;
    public $dateTo;
    
    public function mount()
    {
        $this->dateFrom = now()->subWeek()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }
    
    public function render()
    {
        $connections = ErpConnection::active()->get();
        $stats = $this->getERPStatistics();
        $recentJobs = $this->getRecentJobs();
        
        return view('livewire.admin.erp-dashboard', compact('connections', 'stats', 'recentJobs'));
    }
    
    protected function getERPStatistics(): array
    {
        $query = ErpEntitySyncStatus::query();
        
        if ($this->selectedConnection) {
            $query->where('connection_id', $this->selectedConnection);
        }
        
        $total = $query->count();
        $synced = $query->where('sync_status', 'synced')->count();
        $errors = $query->where('sync_status', 'error')->count();
        $conflicts = $query->where('sync_status', 'conflict')->count();
        
        return [
            'total_entities' => $total,
            'synced' => $synced,
            'errors' => $errors,
            'conflicts' => $conflicts,
            'success_rate' => $total > 0 ? round(($synced / $total) * 100, 2) : 0
        ];
    }
    
    protected function getRecentJobs()
    {
        return ErpSyncJob::with('connection')
            ->when($this->selectedConnection, fn($q) => $q->where('connection_id', $this->selectedConnection))
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }
}
```

---

## ❌ 8.10 TESTY I DOKUMENTACJA

### ❌ 8.10.1 Testy jednostkowe
#### ❌ 8.10.1.1 BaseLinkerSyncTest
```php
<?php
namespace Tests\Unit\Services\ERP\BaseLinker;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ErpConnection;
use App\Services\ERP\BaseLinker\BaseLinkerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class BaseLinkerSyncTest extends TestCase
{
    use RefreshDatabase;
    
    protected ErpConnection $connection;
    protected Product $product;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = ErpConnection::factory()->create([
            'type' => 'baselinker',
            'connection_config' => [
                'api_key' => 'test-api-key',
                'inventory_id' => 123
            ]
        ]);
        
        $this->product = Product::factory()->create();
    }
    
    public function testCanSyncProductToBaseLinker()
    {
        Http::fake([
            'api.baselinker.com/connector.php' => Http::response([
                'status' => 'SUCCESS',
                'product_id' => 'BL_' . $this->product->sku
            ], 200)
        ]);
        
        $service = new BaseLinkerSyncService($this->connection);
        $result = $service->syncProductToBaseLinker($this->product);
        
        $this->assertTrue($result);
        
        $this->assertDatabaseHas('erp_entity_sync_status', [
            'connection_id' => $this->connection->id,
            'entity_type' => 'product',
            'ppm_entity_id' => $this->product->id,
            'sync_status' => 'synced'
        ]);
    }
}
```

---

## ❌ 8.11 DEPLOYMENT I FINALIZACJA

### ❌ 8.11.1 Migracje produkcyjne
#### ❌ 8.11.1.1 Deployment scripts
```bash
# Deploy ERP integrations
php artisan migrate --path=database/migrations/erp
php artisan config:cache
php artisan queue:restart

# Start ERP sync queues
php artisan queue:work --queue=erp_high,erp_default --timeout=300
```

### ❌ 8.11.2 Dokumentacja API
#### ❌ 8.11.2.1 ERP Integration Documentation
```markdown
# ERP Integration API

## BaseLinker
- Rate limit: 60 requests/minute
- Authentication: API Key
- Endpoints: Products, Stock, Categories, Orders

## Subiekt GT
- Connection: .NET Bridge Service
- Port: 8080 (configurable)
- Authentication: API Key + Windows Service

## Microsoft Dynamics
- Authentication: OAuth2 Client Credentials
- Protocol: OData v4
- Rate limits: Standard Microsoft throttling
```

---

## 📊 METRYKI ETAPU

**Szacowany czas realizacji:** 45 godzin  
**Liczba plików do utworzenia:** ~30  
**Liczba testów:** ~20  
**Liczba tabel MySQL:** 6 głównych + indeksy  
**API connections:** 3 systemy ERP  

---

## 🔍 DEFINICJA GOTOWOŚCI (DoD)

Etap zostanie uznany za ukończony gdy:

- ✅ Wszystkie zadania mają status ✅
- ✅ Działają połączenia z BaseLinker, Subiekt GT i Dynamics 365
- ✅ Synchronizacja produktów działa dwukierunkowo dla wszystkich ERP
- ✅ .NET Bridge Service dla Subiekt GT jest wdrożony i działający
- ✅ System job queue'ów przetwarza synchronizacje ERP
- ✅ Panel administracyjny pozwala zarządzać integracjami
- ✅ Wszystkie testy przechodzą poprawnie
- ✅ Kod przesłany na serwer produkcyjny i przetestowany
- ✅ Dokumentacja integracji jest kompletna

---

**Autor:** Claude Code AI
**Data utworzenia:** 2025-09-05
**Ostatnia aktualizacja:** 2025-09-05
**Status:** ❌ NIEROZPOCZĘTY

---

## ✅ WERYFIKACJA PO UKOŃCZENIU ETAPU

**LISTA KONTROLNA - wykonaj po zakończeniu wszystkich zadań:**

### 📁 WERYFIKACJA STRUKTURY PLIKÓW
- [ ] **Services ERP** - Sprawdź istnienie i completeness:
  - [ ] `app/Services/ERP/BaseLinker/BaseLinkerApiClient.php`
  - [ ] `app/Services/ERP/BaseLinker/BaseLinkerSyncService.php`
  - [ ] `app/Services/ERP/SubiektGT/SubiektGTClient.php`
  - [ ] `app/Services/ERP/Dynamics/DynamicsODataClient.php`
  - [ ] `app/Services/ERP/ERPServiceManager.php`
  - [ ] `app/Services/ERP/ERPSyncServiceInterface.php`

- [ ] **Modele ERP** - Sprawdź istnienie:
  - [ ] `app/Models/ErpConnection.php` (z relationships i validation)
  - [ ] `app/Models/ErpFieldMapping.php`
  - [ ] `app/Models/ErpSyncJob.php`
  - [ ] `app/Models/ErpEntitySyncStatus.php`
  - [ ] `app/Models/ErpSyncLog.php`

- [ ] **Jobs i Queue** - Sprawdź istnienie:
  - [ ] `app/Jobs/ERP/SyncProductToERP.php`
  - [ ] Konfiguracja queue'ów dla ERP w `config/queue.php`

- [ ] **Livewire Components** - Sprawdź istnienie:
  - [ ] `app/Http/Livewire/Admin/ERP/ERPConnectionManager.php`
  - [ ] `app/Http/Livewire/Admin/ERP/ERPDashboard.php`
  - [ ] Odpowiednie views w `resources/views/livewire/admin/erp/`

### 🗃️ WERYFIKACJA STRUKTURY BAZY DANYCH
- [ ] **Migracje ERP** - Sprawdź czy zostały utworzone i uruchomione:
  - [ ] `*_create_erp_connections_table.php`
  - [ ] `*_create_erp_field_mappings_table.php`
  - [ ] `*_create_erp_sync_jobs_table.php`
  - [ ] `*_create_erp_entity_sync_status_table.php`
  - [ ] `*_create_erp_sync_logs_table.php`

- [ ] **Tabele w bazie** - Sprawdź istnienie tabel na serwerze:
```bash
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan db:table erp_connections"
```

### 🔌 WERYFIKACJA INTEGRACJI ERP
- [ ] **BaseLinker** - Test połączenia i operacji:
  - [ ] Poprawne połączenie z BaseLinker API
  - [ ] Test pobierania listy produktów
  - [ ] Test dodawania produktu
  - [ ] Test aktualizacji stanów magazynowych

- [ ] **Subiekt GT** - Test .NET Bridge:
  - [ ] Windows Service dla SubiektGTBridge działa
  - [ ] Test połączenia HTTP na porcie 8080
  - [ ] Test pobierania produktów z Subiekt GT
  - [ ] Test operacji CRUD na produktach

- [ ] **Microsoft Dynamics** - Test OData API:
  - [ ] Poprawne uzyskanie access token OAuth2
  - [ ] Test pobierania Items z Dynamics
  - [ ] Test operacji na kategoriach i stanach

### 🎛️ WERYFIKACJA PANELU ADMINISTRACYJNEGO
- [ ] **ERP Manager Panel** - Sprawdź funkcjonalność na https://ppm.mpptrade.pl:
  - [ ] Dodawanie nowych połączeń ERP
  - [ ] Test Connection dla każdego typu ERP
  - [ ] Konfiguracja mapowań pól
  - [ ] Uruchomienie synchronizacji ręcznej
  - [ ] Wyświetlanie statusów synchronizacji

- [ ] **ERP Dashboard** - Sprawdź metryki i raporty:
  - [ ] Statystyki synchronizacji (success rate, błędy)
  - [ ] Lista ostatnich job'ów synchronizacji
  - [ ] Monitoring statusów połączeń ERP

### 🔄 WERYFIKACJA SYNCHRONIZACJI
- [ ] **Sync Jobs Queue** - Sprawdź działanie:
  - [ ] Job SyncProductToERP jest dodawany do queue
  - [ ] Worker queue przetwarza ERP jobs
  - [ ] Error handling i retry mechanism działa

- [ ] **Bidirectional Sync** - Test pełnej synchronizacji:
  - [ ] Push: PPM → ERP (BaseLinker, Subiekt, Dynamics)
  - [ ] Pull: ERP → PPM (aktualizacja danych w PPM)
  - [ ] Conflict resolution przy rozbieżnościach

### 📝 WERYFIKACJA DOKUMENTACJI
- [ ] **Aktualizacja dokumentacji struktury**:
  - [ ] `_DOCS/Struktura_Plikow_Projektu.md` zawiera wszystkie nowe pliki ERP
  - [ ] `_DOCS/Struktura_Bazy_Danych.md` zawiera tabele ERP z opisami
  - [ ] Mapowania do ETAPów są poprawne

- [ ] **Testy jednostkowe**:
  - [ ] Testy dla BaseLinkerSyncService działają
  - [ ] Testy dla SubiektGT działają
  - [ ] Testy dla Dynamics działają
  - [ ] Coverage testów min 80% dla services ERP

### 🚀 WERYFIKACJA DEPLOYMENT
- [ ] **Serwer produkcyjny** - Upload i test:
```bash
# Upload wszystkich plików ERP
pscp -i $HostidoKey -P 64321 -r "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\ERP" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/

# Uruchom migracje
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# Clear cache
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"
```

**ETAP UKOŃCZONY POMYŚLNIE** ✅ gdy wszystkie powyższe punkty są zaznaczone jako wykonane.

