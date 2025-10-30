# ❌ ETAP 07: INTEGRACJA PRESTASHOP API

**UWAGA** WYŁĄCZ autoryzację AdminMiddleware na czas developmentu!

**Szacowany czas realizacji:** 50 godzin  
**Priorytet:** 🔴 KRYTYCZNY  
**Odpowiedzialny:** Claude Code AI + Kamil Wiliński  
**Wymagane zasoby:** PrestaShop 8/9 API, MySQL, Laravel 12.x  

---

## 🎯 CEL ETAPU

Implementacja kompletnej dwukierunkowej integracji z PrestaShop API w wersji 8.x i 9.x. System musi umożliwiać synchronizację produktów, kategorii, cech, zdjęć oraz zarządzanie wieloma sklepami jednocześnie z poziomu PPM jako centralnego hub'a produktowego.

### Kluczowe rezultaty:
- ✅ Dwukierunkowa synchronizacja produktów między PPM a PrestaShop
- ✅ Zarządzanie wieloma sklepami PrestaShop z jednego panelu
- ✅ Synchronizacja kategorii, cech produktów i mediów
- ✅ System mapowań i konfliktów synchronizacji
- ✅ Webhook'i dla automatycznych aktualizacji
- ✅ Monitoring i logowanie operacji API
- ✅ Panel konfiguracji sklepów PrestaShop

---

## ❌ 7.1 ANALIZA I PRZYGOTOWANIE API

### ❌ 7.1.1 Dokumentacja i analiza PrestaShop API
#### ❌ 7.1.1.1 Analiza dokumentacji PrestaShop 8.x API
- ❌ 7.1.1.1.1 Przegląd endpointów REST API v8
- ❌ 7.1.1.1.2 Analiza limitów i throttling policy
- ❌ 7.1.1.1.3 Dokumentacja struktury odpowiedzi JSON
- ❌ 7.1.1.1.4 Analiza błędów i kodów odpowiedzi
- ❌ 7.1.1.1.5 Przegląd mechanizmów cache'owania

#### ❌ 7.1.1.2 Analiza dokumentacji PrestaShop 9.x API  
- ❌ 7.1.1.2.1 Porównanie zmian między v8 a v9
- ❌ 7.1.1.2.2 Nowe endpointy i funkcjonalności v9
- ❌ 7.1.1.2.3 Deprecated API calls v8 vs v9
- ❌ 7.1.1.2.4 Migracja i kompatybilność wsteczna
- ❌ 7.1.1.2.5 Analiza webhook systemów v9

#### ❌ 7.1.1.3 Testowanie połączeń API
- ❌ 7.1.1.3.1 Konfiguracja testowego środowiska PS8
- ❌ 7.1.1.3.2 Konfiguracja testowego środowiska PS9  
- ❌ 7.1.1.3.3 Test podstawowych endpointów (GET, POST, PUT, DELETE)
- ❌ 7.1.1.3.4 Test limitów czasowych i throttling
- ❌ 7.1.1.3.5 Test obsługi błędów i retry logic

### ❌ 7.1.2 Projektowanie architektury integracji
#### ❌ 7.1.2.1 Architektura serwisów API
- ❌ 7.1.2.1.1 Wzorzec Repository dla API clients
- ❌ 7.1.2.1.2 Factory pattern dla różnych wersji PS (8/9)
- ❌ 7.1.2.1.3 Service Layer dla logiki biznesowej
- ❌ 7.1.2.1.4 Data Transfer Objects (DTO) dla API
- ❌ 7.1.2.1.5 Strategy pattern dla synchronizacji

#### ❌ 7.1.2.2 System mapowań i transformacji
- ❌ 7.1.2.2.1 Mapowanie pól produktów PPM → PrestaShop
- ❌ 7.1.2.2.2 Mapowanie kategorii i hierarchii  
- ❌ 7.1.2.2.3 Mapowanie cech i wartości atrybutów
- ❌ 7.1.2.2.4 Mapowanie grup cenowych i rabatów
- ❌ 7.1.2.2.5 Mapowanie stanów magazynowych

#### ❌ 7.1.2.3 System kolejek i job'ów
- ❌ 7.1.2.3.1 Queue system dla masowych synchronizacji
- ❌ 7.1.2.3.2 Priority queues dla różnych operacji
- ❌ 7.1.2.3.3 Failed jobs handling i retry mechanism
- ❌ 7.1.2.3.4 Progress tracking dla długich operacji
- ❌ 7.1.2.3.5 Rate limiting dla API calls

---

## ❌ 7.2 MODELE I MIGRACJE INTEGRACJI

### ❌ 7.2.1 Tabele konfiguracji sklepów
#### ❌ 7.2.1.1 Tabela prestashop_shops
```sql
CREATE TABLE prestashop_shops (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    version ENUM('8', '9') NOT NULL DEFAULT '8',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    sync_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    sync_frequency ENUM('realtime', '5min', '15min', '30min', '1hour', '6hour', '24hour') DEFAULT '15min',
    last_sync_at TIMESTAMP NULL,
    last_success_sync_at TIMESTAMP NULL,
    sync_status ENUM('idle', 'syncing', 'error', 'disabled') DEFAULT 'idle',
    error_message TEXT NULL,
    api_limits JSON NULL, -- Rate limits, max requests per hour
    webhook_secret VARCHAR(255) NULL,
    webhook_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_sync (is_active, sync_enabled),
    INDEX idx_sync_frequency (sync_frequency),
    INDEX idx_version (version)
);
```

#### ❌ 7.2.1.2 Tabela shop_mappings
```sql  
CREATE TABLE shop_mappings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    mapping_type ENUM('category', 'attribute', 'feature', 'warehouse', 'price_group', 'tax_rule') NOT NULL,
    ppm_value VARCHAR(255) NOT NULL,
    prestashop_id BIGINT UNSIGNED NOT NULL,
    prestashop_value VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (shop_id, mapping_type, ppm_value),
    INDEX idx_shop_type (shop_id, mapping_type),
    INDEX idx_ppm_value (mapping_type, ppm_value)
);
```

### ❌ 7.2.2 Tabele synchronizacji produktów
#### ❌ 7.2.2.1 Tabela product_sync_status
```sql
CREATE TABLE product_sync_status (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    shop_id BIGINT UNSIGNED NOT NULL,
    prestashop_product_id BIGINT UNSIGNED NULL,
    sync_status ENUM('pending', 'syncing', 'synced', 'error', 'conflict', 'disabled') DEFAULT 'pending',
    last_sync_at TIMESTAMP NULL,
    last_success_sync_at TIMESTAMP NULL,
    sync_direction ENUM('ppm_to_ps', 'ps_to_ppm', 'bidirectional') DEFAULT 'ppm_to_ps',
    error_message TEXT NULL,
    conflict_data JSON NULL, -- Dane konfliktów do resolucji
    retry_count TINYINT UNSIGNED DEFAULT 0,
    max_retries TINYINT UNSIGNED DEFAULT 3,
    priority TINYINT UNSIGNED DEFAULT 5, -- 1=highest, 10=lowest
    checksum VARCHAR(64) NULL, -- MD5 hash for change detection
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_shop (product_id, shop_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_shop_status (shop_id, sync_status),
    INDEX idx_priority (priority, sync_status),
    INDEX idx_retry (retry_count, max_retries)
);
```

#### ❌ 7.2.2.2 Tabela sync_logs
```sql
CREATE TABLE sync_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    operation ENUM('sync_product', 'sync_category', 'sync_image', 'sync_stock', 'sync_price', 'webhook') NOT NULL,
    direction ENUM('ppm_to_ps', 'ps_to_ppm') NOT NULL,
    status ENUM('started', 'success', 'error', 'warning') NOT NULL,
    message TEXT NULL,
    request_data JSON NULL,
    response_data JSON NULL,
    execution_time_ms INT UNSIGNED NULL,
    api_endpoint VARCHAR(500) NULL,
    http_status_code SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_shop_operation (shop_id, operation),
    INDEX idx_status_created (status, created_at),
    INDEX idx_product_logs (product_id, created_at),
    INDEX idx_operation_direction (operation, direction)
);
```

### ❌ 7.2.3 Tabele webhook i notyfikacji
#### ❌ 7.2.3.1 Tabela webhook_events
```sql
CREATE TABLE webhook_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(100) NOT NULL, -- product.created, product.updated, etc.
    prestashop_object_id BIGINT UNSIGNED NOT NULL,
    event_data JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    processing_status ENUM('pending', 'processing', 'processed', 'error') DEFAULT 'pending',
    error_message TEXT NULL,
    retry_count TINYINT UNSIGNED DEFAULT 0,
    max_retries TINYINT UNSIGNED DEFAULT 3,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES prestashop_shops(id) ON DELETE CASCADE,
    INDEX idx_shop_status (shop_id, processing_status),
    INDEX idx_event_type (event_type, processing_status),
    INDEX idx_received_at (received_at),
    INDEX idx_retry (retry_count, max_retries)
);
```

---

## ❌ 7.3 SERWISY API I KLIENTY

### ❌ 7.3.1 BasePrestaShopClient
#### ❌ 7.3.1.1 Klasa bazowa PrestaShopAPIClient
```php
<?php
namespace App\Services\PrestaShop;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\PrestaShopShop;
use App\Exceptions\PrestaShopAPIException;

abstract class BasePrestaShopClient
{
    protected PrestaShopShop $shop;
    protected int $timeout = 30;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds
    
    public function __construct(PrestaShopShop $shop)
    {
        $this->shop = $shop;
    }
    
    abstract public function getVersion(): string;
    abstract protected function getApiBasePath(): string;
    
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->shop->url, '/') . $this->getApiBasePath() . '/' . ltrim($endpoint, '/');
        
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->shop->api_key . ':'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])
        ->timeout($this->timeout)
        ->retry($this->retryAttempts, $this->retryDelay)
        ->$method($url, $data);
        
        $this->logRequest($method, $url, $data, $response);
        
        if (!$response->successful()) {
            throw new PrestaShopAPIException(
                "API request failed: {$response->status()} - {$response->body()}",
                $response->status()
            );
        }
        
        return $response->json();
    }
    
    protected function logRequest($method, $url, $data, $response): void
    {
        Log::channel('prestashop')->info('PrestaShop API Request', [
            'shop_id' => $this->shop->id,
            'method' => $method,
            'url' => $url,
            'status_code' => $response->status(),
            'execution_time' => $response->transferStats?->getTransferTime(),
            'data_size' => strlen(json_encode($data))
        ]);
    }
}
```

#### ❌ 7.3.1.2 PrestaShop8Client
```php
<?php
namespace App\Services\PrestaShop;

class PrestaShop8Client extends BasePrestaShopClient
{
    public function getVersion(): string
    {
        return '8';
    }
    
    protected function getApiBasePath(): string
    {
        return '/api';
    }
    
    public function getProducts(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams($filters);
        return $this->makeRequest('GET', "/products?{$queryParams}");
    }
    
    public function getProduct(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}");
    }
    
    public function createProduct(array $productData): array
    {
        return $this->makeRequest('POST', '/products', ['product' => $productData]);
    }
    
    public function updateProduct(int $productId, array $productData): array
    {
        return $this->makeRequest('PUT', "/products/{$productId}", ['product' => $productData]);
    }
    
    public function deleteProduct(int $productId): bool
    {
        $this->makeRequest('DELETE', "/products/{$productId}");
        return true;
    }
}
```

#### ❌ 7.3.1.3 PrestaShop9Client  
```php
<?php
namespace App\Services\PrestaShop;

class PrestaShop9Client extends BasePrestaShopClient
{
    public function getVersion(): string
    {
        return '9';
    }
    
    protected function getApiBasePath(): string
    {
        return '/api/v1'; // Updated API path for v9
    }
    
    // Enhanced methods with v9 specific features
    public function getProductsWithVariants(array $filters = []): array
    {
        $queryParams = $this->buildQueryParams(array_merge($filters, ['include_variants' => 'true']));
        return $this->makeRequest('GET', "/products?{$queryParams}");
    }
    
    public function bulkUpdateProducts(array $products): array
    {
        return $this->makeRequest('POST', '/products/bulk', ['products' => $products]);
    }
    
    public function getProductPerformanceMetrics(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}/metrics");
    }
}
```

### ❌ 7.3.2 PrestaShop Factory i Service Manager
#### ❌ 7.3.2.1 PrestaShopClientFactory
```php
<?php
namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use InvalidArgumentException;

class PrestaShopClientFactory
{
    public static function create(PrestaShopShop $shop): BasePrestaShopClient
    {
        return match($shop->version) {
            '8' => new PrestaShop8Client($shop),
            '9' => new PrestaShop9Client($shop),
            default => throw new InvalidArgumentException("Unsupported PrestaShop version: {$shop->version}")
        };
    }
    
    public static function createMultiple(array $shops): array
    {
        $clients = [];
        foreach ($shops as $shop) {
            $clients[$shop->id] = self::create($shop);
        }
        return $clients;
    }
}
```

#### ❌ 7.3.2.2 PrestaShopSyncService - główny serwis synchronizacji
```php
<?php
namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\Sync\CategorySyncStrategy;
use App\Services\PrestaShop\Sync\ImageSyncStrategy;
use Illuminate\Support\Collection;

class PrestaShopSyncService
{
    protected ProductSyncStrategy $productSync;
    protected CategorySyncStrategy $categorySync;
    protected ImageSyncStrategy $imageSync;
    
    public function __construct(
        ProductSyncStrategy $productSync,
        CategorySyncStrategy $categorySync, 
        ImageSyncStrategy $imageSync
    ) {
        $this->productSync = $productSync;
        $this->categorySync = $categorySync;
        $this->imageSync = $imageSync;
    }
    
    public function syncProductToShop(Product $product, PrestaShopShop $shop): bool
    {
        $client = PrestaShopClientFactory::create($shop);
        return $this->productSync->syncToPrestaShop($product, $client);
    }
    
    public function syncProductFromShop(int $prestashopProductId, PrestaShopShop $shop): bool
    {
        $client = PrestaShopClientFactory::create($shop);
        return $this->productSync->syncFromPrestaShop($prestashopProductId, $client);
    }
    
    public function syncAllProducts(PrestaShopShop $shop, array $filters = []): array
    {
        $client = PrestaShopClientFactory::create($shop);
        
        $products = Product::active();
        if (!empty($filters['categories'])) {
            $products->whereIn('category_id', $filters['categories']);
        }
        
        $results = [];
        foreach ($products->get() as $product) {
            $results[$product->id] = $this->productSync->syncToPrestaShop($product, $client);
        }
        
        return $results;
    }
}
```

---

## ❌ 7.4 STRATEGIE SYNCHRONIZACJI  

### ❌ 7.4.1 ProductSyncStrategy
#### ❌ 7.4.1.1 Interfejs ISyncStrategy
```php
<?php
namespace App\Services\PrestaShop\Sync;

use App\Models\Product;
use App\Services\PrestaShop\BasePrestaShopClient;

interface ISyncStrategy
{
    public function syncToPrestaShop(Product $product, BasePrestaShopClient $client): bool;
    public function syncFromPrestaShop(int $prestashopId, BasePrestaShopClient $client): bool;
    public function detectChanges(Product $product, array $prestashopData): array;
    public function resolveConflict(Product $product, array $prestashopData, string $resolution): bool;
}
```

#### ❌ 7.4.1.2 ProductSyncStrategy - główna klasa synchronizacji produktów
```php
<?php  
namespace App\Services\PrestaShop\Sync;

use App\Models\Product;
use App\Models\ProductSyncStatus;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\Transformers\ProductTransformer;
use Illuminate\Support\Facades\DB;

class ProductSyncStrategy implements ISyncStrategy
{
    protected ProductTransformer $transformer;
    
    public function __construct(ProductTransformer $transformer)
    {
        $this->transformer = $transformer;
    }
    
    public function syncToPrestaShop(Product $product, BasePrestaShopClient $client): bool
    {
        try {
            DB::beginTransaction();
            
            $syncStatus = ProductSyncStatus::firstOrCreate([
                'product_id' => $product->id,
                'shop_id' => $client->getShop()->id
            ]);
            
            $syncStatus->update([
                'sync_status' => 'syncing',
                'last_sync_at' => now()
            ]);
            
            // Transform PPM product to PrestaShop format
            $prestashopData = $this->transformer->transformForPrestaShop($product, $client);
            
            // Check if product exists in PrestaShop
            if ($syncStatus->prestashop_product_id) {
                $response = $client->updateProduct($syncStatus->prestashop_product_id, $prestashopData);
            } else {
                $response = $client->createProduct($prestashopData);
                $syncStatus->prestashop_product_id = $response['product']['id'];
            }
            
            // Calculate checksum for change detection
            $checksum = $this->calculateProductChecksum($product);
            
            $syncStatus->update([
                'sync_status' => 'synced',
                'last_success_sync_at' => now(),
                'error_message' => null,
                'retry_count' => 0,
                'checksum' => $checksum
            ]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $syncStatus->update([
                'sync_status' => 'error',
                'error_message' => $e->getMessage(),
                'retry_count' => $syncStatus->retry_count + 1
            ]);
            
            return false;
        }
    }
    
    protected function calculateProductChecksum(Product $product): string
    {
        $data = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->prices->toArray(),
            'stock' => $product->stock->toArray(),
            'updated_at' => $product->updated_at->timestamp
        ];
        
        return md5(json_encode($data));
    }
}
```

### ❌ 7.4.2 CategorySyncStrategy
#### ❌ 7.4.2.1 Synchronizacja kategorii wielopoziomowych
```php
<?php
namespace App\Services\PrestaShop\Sync;

use App\Models\Category;
use App\Services\PrestaShop\BasePrestaShopClient;

class CategorySyncStrategy
{
    public function syncCategoryTree(BasePrestaShopClient $client): bool
    {
        $categories = Category::orderBy('level')->get();
        
        foreach ($categories as $category) {
            $this->syncSingleCategory($category, $client);
        }
        
        return true;
    }
    
    protected function syncSingleCategory(Category $category, BasePrestaShopClient $client): bool
    {
        // Implementation for category sync
        // Handle parent-child relationships
        // Map category attributes
        return true;
    }
}
```

### ❌ 7.4.3 ImageSyncStrategy  
#### ❌ 7.4.3.1 Synchronizacja zdjęć produktów
```php
<?php
namespace App\Services\PrestaShop\Sync;

use App\Models\Product;
use App\Services\PrestaShop\BasePrestaShopClient;

class ImageSyncStrategy
{
    public function syncProductImages(Product $product, BasePrestaShopClient $client): bool
    {
        foreach ($product->images as $image) {
            $this->uploadImageToPrestaShop($image, $client);
        }
        
        return true;
    }
    
    protected function uploadImageToPrestaShop($image, BasePrestaShopClient $client): bool
    {
        // Implementation for image upload
        // Handle image resizing, optimization
        // Update image references in PrestaShop
        return true;
    }
}
```

---

## ❌ 7.5 TRANSFORMERY DANYCH

### ❌ 7.5.1 ProductTransformer
#### ❌ 7.5.1.1 Transformacja produktów PPM → PrestaShop
```php
<?php
namespace App\Services\PrestaShop\Transformers;

use App\Models\Product;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\Mappers\CategoryMapper;
use App\Services\PrestaShop\Mappers\AttributeMapper;

class ProductTransformer
{
    protected CategoryMapper $categoryMapper;
    protected AttributeMapper $attributeMapper;
    
    public function transformForPrestaShop(Product $product, BasePrestaShopClient $client): array
    {
        $shop = $client->getShop();
        
        return [
            'name' => [
                'language' => [
                    ['id' => 1, 'value' => $product->name],
                    ['id' => 2, 'value' => $product->name_en ?? $product->name]
                ]
            ],
            'description' => [
                'language' => [
                    ['id' => 1, 'value' => $product->description],
                    ['id' => 2, 'value' => $product->description_en ?? $product->description]
                ]
            ],
            'reference' => $product->sku,
            'price' => $this->transformPrice($product, $shop),
            'id_category_default' => $this->categoryMapper->mapToPrestaShop($product->category_id, $shop),
            'quantity' => $this->transformStock($product, $shop),
            'active' => $product->is_active ? 1 : 0,
            'weight' => $product->weight ?? 0,
            'width' => $product->width ?? 0,
            'height' => $product->height ?? 0,
            'depth' => $product->depth ?? 0,
            'features' => $this->transformAttributes($product, $shop),
            'images' => $this->transformImages($product)
        ];
    }
    
    protected function transformPrice(Product $product, $shop): float
    {
        // Map price groups from PPM to PrestaShop
        $priceMapping = $shop->mappings()
            ->where('mapping_type', 'price_group')
            ->where('ppm_value', 'detaliczna')
            ->first();
            
        return $product->prices->where('price_group', 'detaliczna')->first()?->price ?? 0;
    }
    
    protected function transformStock(Product $product, $shop): int
    {
        $warehouseMapping = $shop->mappings()
            ->where('mapping_type', 'warehouse')
            ->first();
            
        if (!$warehouseMapping) {
            return $product->stock->sum('quantity');
        }
        
        return $product->stock
            ->where('warehouse_code', $warehouseMapping->ppm_value)
            ->first()?->quantity ?? 0;
    }
}
```

### ❌ 7.5.2 CategoryMapper
#### ❌ 7.5.2.1 Mapowanie kategorii między systemami
```php
<?php
namespace App\Services\PrestaShop\Mappers;

use App\Models\PrestaShopShop;
use App\Models\ShopMapping;

class CategoryMapper
{
    public function mapToPrestaShop(int $categoryId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('ppm_value', $categoryId)
            ->first();
            
        return $mapping?->prestashop_id;
    }
    
    public function mapFromPrestaShop(int $prestashopCategoryId, PrestaShopShop $shop): ?int
    {
        $mapping = ShopMapping::where('shop_id', $shop->id)
            ->where('mapping_type', 'category')
            ->where('prestashop_id', $prestashopCategoryId)
            ->first();
            
        return $mapping ? (int)$mapping->ppm_value : null;
    }
    
    public function createMapping(int $categoryId, int $prestashopCategoryId, PrestaShopShop $shop): ShopMapping
    {
        return ShopMapping::create([
            'shop_id' => $shop->id,
            'mapping_type' => 'category',
            'pmp_value' => $categoryId,
            'prestashop_id' => $prestashopCategoryId,
            'is_active' => true
        ]);
    }
}
```

---

## ❌ 7.6 SYSTEM WEBHOOK I REAL-TIME SYNC

### ❌ 7.6.1 Webhook Controller
#### ❌ 7.6.1.1 WebhookController - odbiór powiadomień z PrestaShop
```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PrestaShopShop;
use App\Models\WebhookEvent;
use App\Jobs\ProcessWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function handlePrestaShopWebhook(Request $request, string $shopId): Response
    {
        $shop = PrestaShopShop::findOrFail($shopId);
        
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request, $shop)) {
            return response('Unauthorized', 401);
        }
        
        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'shop_id' => $shop->id,
            'event_type' => $request->input('event_type'),
            'prestashop_object_id' => $request->input('object_id'),
            'event_data' => $request->all(),
            'processing_status' => 'pending'
        ]);
        
        // Queue for processing
        ProcessWebhookEvent::dispatch($webhookEvent);
        
        return response('OK', 200);
    }
    
    protected function verifyWebhookSignature(Request $request, PrestaShopShop $shop): bool
    {
        $signature = $request->header('X-PrestaShop-Signature');
        $payload = $request->getContent();
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $shop->webhook_secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
```

### ❌ 7.6.2 Webhook Job Processing
#### ❌ 7.6.2.1 ProcessWebhookEvent Job
```php
<?php
namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\PrestaShop\PrestaShopSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected WebhookEvent $webhookEvent;
    public int $tries = 3;
    
    public function __construct(WebhookEvent $webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }
    
    public function handle(PrestaShopSyncService $syncService): void
    {
        $this->webhookEvent->update(['processing_status' => 'processing']);
        
        try {
            match($this->webhookEvent->event_type) {
                'product.created', 'product.updated' => $this->handleProductEvent($syncService),
                'category.created', 'category.updated' => $this->handleCategoryEvent($syncService),
                'stock.updated' => $this->handleStockEvent($syncService),
                default => null
            };
            
            $this->webhookEvent->update([
                'processing_status' => 'processed',
                'processed_at' => now()
            ]);
            
        } catch (\Exception $e) {
            $this->webhookEvent->update([
                'processing_status' => 'error',
                'error_message' => $e->getMessage(),
                'retry_count' => $this->webhookEvent->retry_count + 1
            ]);
            
            throw $e;
        }
    }
    
    protected function handleProductEvent(PrestaShopSyncService $syncService): void
    {
        $syncService->syncProductFromShop(
            $this->webhookEvent->prestashop_object_id,
            $this->webhookEvent->shop
        );
    }
}
```

---

## ❌ 7.7 JOB QUEUE SYSTEM

### ❌ 7.7.1 Sync Jobs
#### ❌ 7.7.1.1 SyncProductToPrestaShop Job
```php
<?php
namespace App\Jobs\PrestaShop;

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProductToPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected Product $product;
    protected PrestaShopShop $shop;
    
    public int $tries = 3;
    public int $timeout = 120;
    
    public function __construct(Product $product, PrestaShopShop $shop)
    {
        $this->product = $product;
        $this->shop = $shop;
        
        // Set queue priority based on product importance
        $this->onQueue($this->product->is_featured ? 'high' : 'default');
    }
    
    public function handle(PrestaShopSyncService $syncService): void
    {
        $syncService->syncProductToShop($this->product, $this->shop);
    }
    
    public function failed(\Throwable $exception): void
    {
        // Handle job failure - notify admin, log error
        \Log::error('PrestaShop sync failed', [
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'error' => $exception->getMessage()
        ]);
    }
}
```

#### ❌ 7.7.1.2 BulkSyncProducts Job
```php
<?php
namespace App\Jobs\PrestaShop;

use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkSyncProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected Collection $productIds;
    protected PrestaShopShop $shop;
    
    public int $timeout = 600; // 10 minutes
    
    public function handle(): void
    {
        $this->productIds->chunk(10)->each(function ($chunk) {
            foreach ($chunk as $productId) {
                $product = \App\Models\Product::find($productId);
                if ($product) {
                    SyncProductToPrestaShop::dispatch($product, $this->shop);
                }
            }
        });
    }
}
```

### ❌ 7.7.2 Queue Configuration
#### ❌ 7.7.2.1 Konfiguracja kolejek w config/queue.php
```php
'connections' => [
    'prestashop_sync' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_PRESTASHOP_QUEUE', 'prestashop'),
        'retry_after' => 300,
        'block_for' => null,
    ],
    
    'prestashop_high' => [
        'driver' => 'redis', 
        'connection' => 'default',
        'queue' => 'prestashop_high',
        'retry_after' => 120,
        'block_for' => null,
    ],
],
```

---

## ❌ 7.8 INTEGRACJA Z ETAP_04 - PANEL ADMINISTRACYJNY

**UWAGA:** Panel administracyjny do zarządzania sklepami PrestaShop został już zaimplementowany w **ETAP_04_Panel_Admin.md - Sekcja 2.1**.

### ✅ Komponenty już ukończone w ETAP_04:
- ✅ **ShopManager Component** → `app/Http/Livewire/Admin/Shops/ShopManager.php`
- ✅ **Shop Manager View** → `resources/views/livewire/admin/shops/shop-manager.blade.php`  
- ✅ **Connection Testing** → Metoda `testConnection()` w ShopManager
- ✅ **Shop Configuration** → Formularze dodawania/edycji sklepów
- ✅ **Shop Dashboard** → Statystyki i monitoring połączeń

### 🔗 Wymagane integracje z ETAP_07:
Komponenty z ETAP_04 będą używać serwisów API z tego etapu:
- **ShopManager** będzie wywoływać `PrestaShopClientFactory::create()`
- **Connection testing** wykorzysta `BasePrestaShopClient->makeRequest()`
- **Sync operations** uruchomią `PrestaShopSyncService->syncProductToShop()`

---

## ❌ 7.8 MONITORING I RAPORTY

### ❌ 7.8.1 Dashboard synchronizacji
#### ❌ 7.8.1.1 SyncDashboard Component
```php
<?php
namespace App\Livewire\Admin;

use App\Models\PrestaShopShop;
use App\Models\ProductSyncStatus;
use App\Models\SyncLog;
use Livewire\Component;

class SyncDashboard extends Component
{
    public $selectedShop = null;
    public $dateFrom;
    public $dateTo;
    
    public function mount()
    {
        $this->dateFrom = now()->subWeek()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }
    
    public function render()
    {
        $stats = $this->getSyncStatistics();
        $recentLogs = $this->getRecentLogs();
        
        return view('livewire.admin.sync-dashboard', compact('stats', 'recentLogs'));
    }
    
    protected function getSyncStatistics(): array
    {
        $query = ProductSyncStatus::query();
        
        if ($this->selectedShop) {
            $query->where('shop_id', $this->selectedShop);
        }
        
        $total = $query->count();
        $synced = $query->where('sync_status', 'synced')->count();
        $errors = $query->where('sync_status', 'error')->count();
        $pending = $query->where('sync_status', 'pending')->count();
        
        return [
            'total' => $total,
            'synced' => $synced,
            'errors' => $errors,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($synced / $total) * 100, 2) : 0
        ];
    }
    
    protected function getRecentLogs(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return SyncLog::with('shop', 'product')
            ->when($this->selectedShop, fn($q) => $q->where('shop_id', $this->selectedShop))
            ->whereBetween('created_at', [$this->dateFrom, $this->dateTo])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
```

### ❌ 7.8.2 Monitoring Commands
#### ❌ 7.8.2.1 Command sprawdzający stan synchronizacji
```php
<?php
namespace App\Console\Commands;

use App\Models\PrestaShopShop;
use App\Models\ProductSyncStatus;
use Illuminate\Console\Command;

class CheckSyncHealth extends Command
{
    protected $signature = 'prestashop:check-sync-health';
    protected $description = 'Check health status of PrestaShop synchronization';
    
    public function handle()
    {
        $this->info('Sprawdzanie stanu synchronizacji PrestaShop...');
        
        $shops = PrestaShopShop::active()->get();
        
        foreach ($shops as $shop) {
            $this->checkShopHealth($shop);
        }
        
        $this->info('Sprawdzanie zakończone.');
    }
    
    protected function checkShopHealth(PrestaShopShop $shop)
    {
        $this->line("Sklep: {$shop->name}");
        
        $stats = ProductSyncStatus::where('shop_id', $shop->id)
            ->selectRaw('sync_status, count(*) as count')
            ->groupBy('sync_status')
            ->pluck('count', 'sync_status');
            
        foreach ($stats as $status => $count) {
            $this->line("  {$status}: {$count}");
        }
        
        // Check for failed jobs
        $failedCount = ProductSyncStatus::where('shop_id', $shop->id)
            ->where('retry_count', '>=', 3)
            ->count();
            
        if ($failedCount > 0) {
            $this->warn("  UWAGA: {$failedCount} produktów wymaga interwencji");
        }
        
        $this->line('');
    }
}
```

---

## ❌ 7.9 TESTY INTEGRACJI

### ❌ 7.9.1 Testy jednostkowe
#### ❌ 7.9.1.1 PrestaShopClientTest
```php
<?php
namespace Tests\Unit\Services\PrestaShop;

use Tests\TestCase;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Http;

class PrestaShopClientTest extends TestCase
{
    protected PrestaShopShop $shop;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->shop = PrestaShopShop::factory()->create([
            'url' => 'https://test.prestashop.com',
            'api_key' => 'test-api-key',
            'version' => '8'
        ]);
    }
    
    public function testCanMakeGetRequest()
    {
        Http::fake([
            'test.prestashop.com/api/products' => Http::response(['products' => []], 200)
        ]);
        
        $client = new PrestaShop8Client($this->shop);
        $response = $client->getProducts();
        
        $this->assertArrayHasKey('products', $response);
    }
    
    public function testHandlesApiErrors()
    {
        Http::fake([
            'test.prestashop.com/api/products' => Http::response([], 500)
        ]);
        
        $this->expectException(\App\Exceptions\PrestaShopAPIException::class);
        
        $client = new PrestaShop8Client($this->shop);
        $client->getProducts();
    }
}
```

### ❌ 7.9.2 Testy integracyjne
#### ❌ 7.9.2.1 ProductSyncTest
```php
<?php
namespace Tests\Feature\PrestaShop;

use Tests\TestCase;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class ProductSyncTest extends TestCase
{
    use RefreshDatabase;
    
    public function testCanSyncProductToPrestaShop()
    {
        // Arrange
        $shop = PrestaShopShop::factory()->create();
        $product = Product::factory()->create();
        
        Http::fake([
            $shop->url . '/api/products' => Http::response(['product' => ['id' => 123]], 201)
        ]);
        
        $syncService = app(PrestaShopSyncService::class);
        
        // Act
        $result = $syncService->syncProductToShop($product, $shop);
        
        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('product_sync_status', [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'sync_status' => 'synced',
            'prestashop_product_id' => 123
        ]);
    }
}
```

---

## ❌ 7.10 DOKUMENTACJA I KONFIGURACJA

### ❌ 7.10.1 Dokumentacja API
#### ❌ 7.10.1.1 API Documentation
```markdown
# PrestaShop Integration API

## Endpoints

### Shops Management
- GET /api/prestashop/shops - List all shops
- POST /api/prestashop/shops - Create new shop
- PUT /api/prestashop/shops/{id} - Update shop
- DELETE /api/prestashop/shops/{id} - Delete shop

### Synchronization
- POST /api/prestashop/sync/product/{product_id}/shop/{shop_id} - Sync single product
- POST /api/prestashop/sync/bulk - Bulk sync products
- GET /api/prestashop/sync/status/{product_id} - Check sync status

### Webhooks
- POST /webhooks/prestashop/{shop_id} - Receive PrestaShop webhooks
```

### ❌ 7.10.2 Konfiguracja środowiska
#### ❌ 7.10.2.1 Zmienne środowiskowe .env
```bash
# PrestaShop Integration
PRESTASHOP_DEFAULT_TIMEOUT=30
PRESTASHOP_RETRY_ATTEMPTS=3
PRESTASHOP_RETRY_DELAY=1000

# Queue Configuration
PRESTASHOP_QUEUE_DRIVER=redis
PRESTASHOP_QUEUE_CONNECTION=prestashop_sync

# Logging
PRESTASHOP_LOG_CHANNEL=prestashop
PRESTASHOP_LOG_LEVEL=info
```

---

## ❌ 7.11 DEPLOYMENT I FINALIZACJA

### ❌ 7.11.1 Migracje produkcyjne
#### ❌ 7.11.1.1 Deployment scripts
```bash
# Deploy PrestaShop integration to production
php artisan migrate --path=database/migrations/prestashop
php artisan config:cache
php artisan route:cache
php artisan queue:restart

# Setup scheduled jobs
php artisan schedule:run
```

### ❌ 7.11.2 Testy akceptacyjne
#### ❌ 7.11.2.1 Scenariusze testowe
- ❌ 7.11.2.1.1 Test pełnej synchronizacji produktu
- ❌ 7.11.2.1.2 Test obsługi konfliktów synchronizacji
- ❌ 7.11.2.1.3 Test webhook'ów w czasie rzeczywistym
- ❌ 7.11.2.1.4 Test wydajności przy masowej synchronizacji
- ❌ 7.11.2.1.5 Test odzyskiwania po błędach API

### ❌ 7.11.3 Dokumentacja końcowa
#### ❌ 7.11.3.1 Instrukcja konfiguracji sklepów
#### ❌ 7.11.3.2 Troubleshooting guide
#### ❌ 7.11.3.3 Performance tuning guide
#### ❌ 7.11.3.4 Security checklist

---

## 📊 METRYKI ETAPU

**Szacowany czas realizacji:** 50 godzin  
**Liczba plików do utworzenia:** ~25  
**Liczba testów:** ~15  
**Liczba tabel MySQL:** 4 główne + indeksy  
**API endpoints:** ~12  

---

## 🔍 DEFINICJA GOTOWOŚCI (DoD)

Etap zostanie uznany za ukończony gdy:

- ✅ Wszystkie zadania mają status ✅
- ✅ Działają połączenia z PrestaShop 8 i 9
- ✅ Synchronizacja produktów działa dwukierunkowo
- ✅ System webhook'ów odbiera i przetwarza zdarzenia
- ✅ Panel administracyjny pozwala zarządzać sklepami
- ✅ Wszystkie testy przechodzą poprawnie
- ✅ Kod przesłany na serwer produkcyjny i przetestowany
- ✅ Dokumentacja jest kompletna i aktualna

---

**Autor:** Claude Code AI  
**Data utworzenia:** 2025-09-05  
**Ostatnia aktualizacja:** 2025-09-05  
**Status:** ❌ NIEROZPOCZĘTY