# ETAP_07 FAZA 1 - Implementation Plan

**Data utworzenia:** 2025-10-01
**Status:** 🛠️ IN PROGRESS
**Cel:** Panel konfiguracyjny PrestaShop + synchronizacja produktów/kategorii (BEZ ZDJĘĆ)

**📌 Ten dokument jest częścią:** [Plan_Projektu/ETAP_07_Prestashop_API.md](../Plan_Projektu/ETAP_07_Prestashop_API.md)

**📚 Powiązane dokumenty:**
- **[ETAP_07_Prestashop_API.md](../Plan_Projektu/ETAP_07_Prestashop_API.md)** - High-level plan całego ETAP_07 (wszystkie fazy)
- **[ETAP_07_Synchronization_Workflow.md](ETAP_07_Synchronization_Workflow.md)** - Szczegółowe workflow synchronizacji
- **[Struktura_Bazy_Danych.md](Struktura_Bazy_Danych.md)** - Sekcja ETAP_07 (3 nowe tabele)
- **[Struktura_Plikow_Projektu.md](Struktura_Plikow_Projektu.md)** - Sekcja ETAP_07 (struktura folderów)

---

## 📋 SPIS TREŚCI

- [Zakres FAZA 1](#zakres-faza-1)
- [Prerequisites](#prerequisites)
- [Workflow Implementacji](#workflow-implementacji)
- [Szczegółowa Kolejność Zadań](#szczegółowa-kolejność-zadań)
- [Testing Strategy](#testing-strategy)
- [Deployment Strategy](#deployment-strategy)

---

## 🎯 ZAKRES FAZA 1

### ✅ W ZAKRESIE:
- Panel konfiguracji połączenia PrestaShop (URL, API key, wersja)
- Test połączenia z PrestaShop API
- Synchronizacja produktów: PPM → PrestaShop (bez zdjęć)
- Synchronizacja kategorii: PPM → PrestaShop (hierarchia 5 poziomów)
- Mapowanie: kategorie, grupy cenowe, magazyny
- Status synchronizacji produktów (pending/syncing/synced/error)
- Queue jobs dla operacji sync (background processing)
- Podstawowy logging operacji sync

### ❌ POZA ZAKRESEM FAZA 1:
- ❌ Synchronizacja zdjęć produktów (FAZA 2)
- ❌ Webhook system (FAZA 3)
- ❌ Synchronizacja PrestaShop → PPM (FAZA 2)
- ❌ Bulk operations UI (FAZA 2)
- ❌ Advanced conflict resolution (FAZA 2)
- ❌ Real-time monitoring dashboard (FAZA 3)

---

## ✔️ PREREQUISITES

### Database:
- ✅ Tabela `prestashop_shops` - EXISTS (z ETAP_04)
- ✅ Tabela `shop_mappings` - DOCUMENTED (needs migration)
- ✅ Tabela `product_sync_status` - DOCUMENTED (needs migration)
- ✅ Tabela `sync_logs` - DOCUMENTED (needs migration)

### Models:
- ✅ `PrestaShopShop.php` - EXISTS (z ETAP_04)
- ⏳ `ShopMapping.php` - TO CREATE
- ⏳ `ProductSyncStatus.php` - TO CREATE
- ⏳ `SyncLog.php` - TO CREATE

### UI Components:
- ✅ `ShopManager.php` (Livewire) - EXISTS, needs extension
- ✅ `AddShop.php` (Livewire) - EXISTS, needs extension
- ✅ `SyncController.php` (Livewire) - EXISTS, needs extension

---

## 🔄 WORKFLOW IMPLEMENTACJI

### FAZA 1A - Foundation (Database + Models)
**Czas: ~4h | Priorytet: KRYTYCZNY**

```
1. Migracje bazy danych
   ├── shop_mappings (kategorie, ceny, magazyny)
   ├── product_sync_status (tracking sync)
   └── sync_logs (operacje sync)

2. Modele Eloquent
   ├── ShopMapping (relationships + scopes)
   ├── ProductSyncStatus (relationships + statuses)
   └── SyncLog (relationships + filtering)

3. Rozszerzenie PrestaShopShop
   ├── Relacje z mappings/sync_status
   └── Pomocnicze metody (isConnected, canSync)
```

### FAZA 1B - API Layer (PrestaShop Clients)
**Czas: ~6h | Priorytet: KRYTYCZNY**

```
1. Base API Client
   ├── BasePrestaShopClient.php
   │   ├── Constructor (shop config)
   │   ├── makeRequest() - HTTP wrapper z retry
   │   ├── handleResponse() - Error handling
   │   └── logRequest() - API call logging
   │
2. Version-Specific Clients
   ├── PrestaShop8Client.php
   │   ├── getProducts($filters)
   │   ├── getProduct($id)
   │   ├── createProduct($data)
   │   ├── updateProduct($id, $data)
   │   ├── getCategories()
   │   └── createCategory($data)
   │
   └── PrestaShop9Client.php
       ├── Extends PrestaShop8Client
       ├── Enhanced API endpoints (v9 features)
       └── Bulk operations support

3. Factory Pattern
   └── PrestaShopClientFactory.php
       └── create(PrestaShopShop $shop): BasePrestaShopClient
```

### FAZA 1C - Sync Layer (Strategies)
**Czas: ~8h | Priorytet: HIGH**

```
1. Interface & Base
   ├── ISyncStrategy.php (interface)
   │   ├── syncToPrestaShop(Model $model, BasePrestaShopClient $client)
   │   ├── calculateChecksum(Model $model)
   │   └── handleSyncError(Exception $e)
   │
2. Product Sync Strategy
   ├── ProductSyncStrategy.php
   │   ├── syncToPrestaShop(Product $product)
   │   ├── prepareProductData(Product $product)
   │   ├── mapCategories(Product $product)
   │   ├── mapPrices(Product $product, PrestaShopShop $shop)
   │   ├── mapStock(Product $product, PrestaShopShop $shop)
   │   └── updateSyncStatus(Product $product, $response)
   │
3. Category Sync Strategy
   └── CategorySyncStrategy.php
       ├── syncToPrestaShop(Category $category)
       ├── syncCategoryHierarchy(PrestaShopShop $shop)
       ├── ensureParentExists(Category $category)
       └── createMapping(Category $category, $ps_id)
```

### FAZA 1D - Data Layer (Mappers & Transformers)
**Czas: ~6h | Priorytet: HIGH**

```
1. Mappers
   ├── CategoryMapper.php
   │   ├── mapToPrestaShop($category_id, $shop)
   │   ├── mapFromPrestaShop($ps_category_id, $shop)
   │   └── syncCategoryHierarchy(PrestaShopShop $shop)
   │
   ├── PriceGroupMapper.php
   │   ├── mapToPrestaShop($price_group, $shop)
   │   └── getDefaultPriceGroup($shop)
   │
   └── WarehouseMapper.php
       ├── mapToPrestaShop($warehouse_code, $shop)
       └── calculateStockForShop(Product $product, $shop)

2. Transformers
   ├── ProductTransformer.php
   │   ├── transformForPrestaShop(Product $product, $client)
   │   ├── transformName($product)
   │   ├── transformDescription($product)
   │   ├── transformPrice($product, $shop)
   │   ├── transformStock($product, $shop)
   │   └── transformCategories($product, $shop)
   │
   └── CategoryTransformer.php
       ├── transformForPrestaShop(Category $category, $client)
       ├── transformName($category)
       └── transformParent($category, $shop)
```

### FAZA 1E - Queue System (Background Jobs)
**Czas: ~4h | Priorytet: MEDIUM**

```
1. Job Classes
   ├── SyncProductToPrestaShop.php
   │   ├── __construct(Product $product, PrestaShopShop $shop)
   │   ├── handle(ProductSyncStrategy $strategy)
   │   ├── failed(Exception $exception)
   │   └── retryUntil()
   │
   ├── BulkSyncProducts.php
   │   ├── __construct(Collection $products, PrestaShopShop $shop)
   │   ├── handle()
   │   └── Dispatch individual SyncProductToPrestaShop jobs
   │
   └── SyncCategoryToPrestaShop.php
       ├── __construct(Category $category, PrestaShopShop $shop)
       └── handle(CategorySyncStrategy $strategy)

2. Queue Configuration
   └── config/queue.php
       └── Add 'prestashop_sync' connection
```

### FAZA 1F - Service Orchestration
**Czas: ~4h | Priorytet: MEDIUM**

```
1. Main Sync Service
   └── PrestaShopSyncService.php
       ├── __construct(ClientFactory, Strategies...)
       ├── testConnection(PrestaShopShop $shop)
       ├── syncProduct(Product $product, PrestaShopShop $shop)
       ├── syncProductToAllShops(Product $product)
       ├── syncCategory(Category $category, PrestaShopShop $shop)
       ├── syncCategoryHierarchy(PrestaShopShop $shop)
       └── getSyncStatus(Product $product, PrestaShopShop $shop)
```

### FAZA 1G - UI Extension (Livewire Components)
**Czas: ~6h | Priorytet: HIGH**

```
1. ShopManager Extension
   └── app/Http/Livewire/Admin/Shops/ShopManager.php
       ├── Add: testPrestaShopConnection($shopId)
       ├── Add: syncProductsToShop($shopId)
       ├── Add: syncCategoriesToShop($shopId)
       ├── Add: viewSyncStatus($shopId)
       └── Add: syncStatusBadges w widoku

2. AddShop Wizard Extension
   └── app/Http/Livewire/Admin/Shops/AddShop.php
       ├── Add: Step 4 - "Test Połączenia PrestaShop"
       ├── Add: validatePrestaShopCredentials()
       ├── Add: testPrestaShopAPI()
       └── Add: createInitialMappings()

3. SyncController Extension
   └── app/Http/Livewire/Admin/Shops/SyncController.php
       ├── Add: syncSelectedProducts()
       ├── Add: syncAllProducts()
       ├── Add: viewSyncLogs($productId)
       └── Add: retrySyncErrors()

4. Product Form Integration
   └── app/Http/Livewire/Products/Management/ProductForm.php
       ├── Add: "Synchronizuj z PrestaShop" button section
       ├── Add: displaySyncStatus() - badges per shop
       └── Add: quickSync($shopId) - sync to specific shop
```

### FAZA 1H - Views (Blade Templates)
**Czas: ~4h | Priorytet: MEDIUM**

```
1. ShopManager View Extension
   └── resources/views/livewire/admin/shops/shop-manager.blade.php
       ├── Add: "Test Połączenia" button
       ├── Add: "Synchronizuj Produkty" button
       ├── Add: "Synchronizuj Kategorie" button
       ├── Add: Sync status badges
       └── Add: Modal z logami sync

2. AddShop Wizard View
   └── resources/views/livewire/admin/shops/add-shop.blade.php
       ├── Add: Step 4 - PrestaShop Connection Test
       ├── Add: API credentials form
       └── Add: Test result display

3. SyncController View
   └── resources/views/livewire/admin/shops/sync-controller.blade.php
       ├── Add: Bulk sync controls
       ├── Add: Product selection interface
       ├── Add: Sync progress indicators
       └── Add: Error logs table

4. ProductForm Sync Section
   └── resources/views/livewire/products/management/product-form.blade.php
       ├── Add: "Synchronizacja PrestaShop" tab
       ├── Add: Shop sync status cards
       └── Add: Quick sync buttons per shop
```

---

## 📝 SZCZEGÓŁOWA KOLEJNOŚĆ ZADAŃ

### KROK 1: Database Foundation (DZIEŃ 1 - 2h)
```
1.1 Stwórz migrację shop_mappings
    └── php artisan make:migration create_shop_mappings_table

1.2 Stwórz migrację product_sync_status
    └── php artisan make:migration create_product_sync_status_table

1.3 Stwórz migrację sync_logs
    └── php artisan make:migration create_sync_logs_table

1.4 Uruchom migracje
    └── php artisan migrate

1.5 Deploy migracji na serwer
    └── pscp + plink migrate --force
```

### KROK 2: Models (DZIEŃ 1 - 2h)
```
2.1 Stwórz model ShopMapping
    ├── php artisan make:model ShopMapping
    ├── Relationships: belongsTo(PrestaShopShop)
    └── Scopes: byType(), byShop(), active()

2.2 Stwórz model ProductSyncStatus
    ├── php artisan make:model ProductSyncStatus
    ├── Relationships: belongsTo(Product, PrestaShopShop)
    ├── Mutators: status enum handling
    └── Methods: markAsSyncing(), markAsSynced(), markAsError()

2.3 Stwórz model SyncLog
    ├── php artisan make:model SyncLog
    ├── Relationships: belongsTo(PrestaShopShop, Product)
    └── Scopes: byOperation(), byStatus(), recent()

2.4 Rozszerz PrestaShopShop model
    ├── Relationships: hasMany(ShopMapping, ProductSyncStatus, SyncLog)
    └── Methods: canSync(), isConnected(), getApiVersion()
```

### KROK 3: API Clients (DZIEŃ 2 - 6h)
```
3.1 BasePrestaShopClient (abstract)
    ├── Create: app/Services/PrestaShop/BasePrestaShopClient.php
    ├── Properties: $shop, $timeout, $retryAttempts
    ├── Abstract methods: getVersion(), getApiBasePath()
    ├── Method: makeRequest($method, $endpoint, $data)
    ├── Method: handleResponse($response)
    └── Method: logRequest($method, $url, $response)

3.2 PrestaShop8Client
    ├── Create: app/Services/PrestaShop/PrestaShop8Client.php
    ├── Extends: BasePrestaShopClient
    ├── Method: getProducts($filters = [])
    ├── Method: getProduct($id)
    ├── Method: createProduct($data)
    ├── Method: updateProduct($id, $data)
    ├── Method: getCategories($filters = [])
    └── Method: createCategory($data)

3.3 PrestaShop9Client
    ├── Create: app/Services/PrestaShop/PrestaShop9Client.php
    ├── Extends: PrestaShop8Client
    ├── Override: getApiBasePath() → '/api/v1'
    └── Add: bulkUpdateProducts($products) - v9 feature

3.4 PrestaShopClientFactory
    ├── Create: app/Services/PrestaShop/PrestaShopClientFactory.php
    ├── Static method: create(PrestaShopShop $shop)
    └── Returns: PrestaShop8Client | PrestaShop9Client based on version

3.5 Test API Clients
    └── Manual test with real PrestaShop credentials
```

### KROK 4: Sync Strategies (DZIEŃ 3-4 - 8h)
```
4.1 ISyncStrategy Interface
    ├── Create: app/Services/PrestaShop/Sync/ISyncStrategy.php
    ├── Method: syncToPrestaShop(Model $model, BasePrestaShopClient $client)
    ├── Method: calculateChecksum(Model $model)
    └── Method: handleSyncError(Exception $e, Model $model)

4.2 ProductSyncStrategy
    ├── Create: app/Services/PrestaShop/Sync/ProductSyncStrategy.php
    ├── Implements: ISyncStrategy
    ├── Method: syncToPrestaShop(Product $product, $client)
    ├── Method: prepareProductData(Product $product, $shop)
    ├── Method: updateSyncStatus($product, $shop, $response)
    └── Method: calculateChecksum(Product $product)

4.3 CategorySyncStrategy
    ├── Create: app/Services/PrestaShop/Sync/CategorySyncStrategy.php
    ├── Implements: ISyncStrategy
    ├── Method: syncToPrestaShop(Category $category, $client)
    ├── Method: syncCategoryHierarchy(PrestaShopShop $shop)
    ├── Method: ensureParentExists(Category $category, $shop)
    └── Method: createMapping(Category $category, $ps_id, $shop)
```

### KROK 5: Mappers & Transformers (DZIEŃ 5 - 6h)
```
5.1 CategoryMapper
    ├── Create: app/Services/PrestaShop/Mappers/CategoryMapper.php
    ├── Method: mapToPrestaShop($category_id, $shop)
    ├── Method: createMapping($category, $ps_id, $shop)
    └── Method: syncHierarchy(PrestaShopShop $shop)

5.2 PriceGroupMapper
    ├── Create: app/Services/PrestaShop/Mappers/PriceGroupMapper.php
    ├── Method: mapToPrestaShop($price_group, $shop)
    └── Method: getDefaultPriceGroup($shop)

5.3 WarehouseMapper
    ├── Create: app/Services/PrestaShop/Mappers/WarehouseMapper.php
    ├── Method: mapToPrestaShop($warehouse_code, $shop)
    └── Method: calculateStockForShop(Product $product, $shop)

5.4 ProductTransformer
    ├── Create: app/Services/PrestaShop/Transformers/ProductTransformer.php
    ├── Method: transformForPrestaShop(Product $product, $client, $shop)
    ├── Method: transformPrice(Product $product, $shop)
    ├── Method: transformStock(Product $product, $shop)
    └── Method: transformCategories(Product $product, $shop)

5.5 CategoryTransformer
    ├── Create: app/Services/PrestaShop/Transformers/CategoryTransformer.php
    └── Method: transformForPrestaShop(Category $category, $client, $shop)
```

### KROK 6: Queue Jobs (DZIEŃ 6 - 4h)
```
6.1 SyncProductToPrestaShop Job
    ├── Create: app/Jobs/PrestaShop/SyncProductToPrestaShop.php
    ├── Constructor: Product $product, PrestaShopShop $shop
    ├── Method: handle(ProductSyncStrategy $strategy)
    ├── Method: failed(Exception $exception)
    └── Properties: $tries = 3, $timeout = 120

6.2 BulkSyncProducts Job
    ├── Create: app/Jobs/PrestaShop/BulkSyncProducts.php
    ├── Constructor: Collection $products, PrestaShopShop $shop
    └── Method: handle() - dispatch individual jobs

6.3 SyncCategoryToPrestaShop Job
    ├── Create: app/Jobs/PrestaShop/SyncCategoryToPrestaShop.php
    └── Similar structure to SyncProductToPrestaShop

6.4 Queue Configuration
    └── Update: config/queue.php
        └── Add 'prestashop_sync' connection
```

### KROK 7: Sync Service Orchestrator (DZIEŃ 6 - 4h)
```
7.1 PrestaShopSyncService
    ├── Create: app/Services/PrestaShop/PrestaShopSyncService.php
    ├── Constructor: Inject Factory, Strategies, Mappers
    ├── Method: testConnection(PrestaShopShop $shop)
    ├── Method: syncProduct(Product $product, PrestaShopShop $shop)
    ├── Method: syncProductToAllShops(Product $product)
    ├── Method: syncCategory(Category $category, PrestaShopShop $shop)
    ├── Method: syncCategoryHierarchy(PrestaShopShop $shop)
    └── Method: getSyncStatus(Product $product, PrestaShopShop $shop)
```

### KROK 8: Livewire UI Extensions (DZIEŃ 7-8 - 6h)
```
8.1 ShopManager Extension
    ├── Modify: app/Http/Livewire/Admin/Shops/ShopManager.php
    ├── Add property: $syncService (injected)
    ├── Add method: testPrestaShopConnection($shopId)
    ├── Add method: syncProductsToShop($shopId)
    ├── Add method: syncCategoriesToShop($shopId)
    └── Add method: viewSyncStatus($shopId)

8.2 AddShop Wizard Extension
    ├── Modify: app/Http/Livewire/Admin/Shops/AddShop.php
    ├── Add: Step 4 logic - "Test Połączenia"
    ├── Add method: validatePrestaShopCredentials()
    ├── Add method: testPrestaShopAPI()
    └── Add method: createInitialMappings()

8.3 ProductForm Integration
    ├── Modify: app/Http/Livewire/Products/Management/ProductForm.php
    ├── Add method: displaySyncStatus()
    ├── Add method: syncToShop($shopId)
    └── Add method: syncToAllShops()
```

### KROK 9: Blade Views (DZIEŃ 8-9 - 4h)
```
9.1 ShopManager View Extension
    ├── Modify: resources/views/livewire/admin/shops/shop-manager.blade.php
    ├── Add: "Test Połączenia" button (wire:click)
    ├── Add: "Synchronizuj Produkty" button
    ├── Add: "Synchronizuj Kategorie" button
    ├── Add: Sync status badges per shop
    └── Add: Modal z sync logs

9.2 AddShop View Extension
    ├── Modify: resources/views/livewire/admin/shops/add-shop.blade.php
    ├── Add: Step 4 - Connection Test UI
    └── Add: API test result display

9.3 ProductForm Sync Tab
    ├── Modify: resources/views/livewire/products/management/product-form.blade.php
    ├── Add: "Synchronizacja PrestaShop" tab
    ├── Add: Shop sync status cards
    └── Add: Quick sync buttons per shop
```

### KROK 10: Testing (DZIEŃ 9-10 - 4h)
```
10.1 Unit Tests
     ├── Test: PrestaShopClientFactory
     ├── Test: ProductTransformer
     ├── Test: CategoryMapper
     └── Test: Sync Strategies

10.2 Feature Tests
     ├── Test: Create shop with PS connection
     ├── Test: Sync single product
     ├── Test: Sync category hierarchy
     └── Test: Error handling

10.3 Manual Integration Tests
     ├── Test: Real PrestaShop 8.x connection
     ├── Test: Real PrestaShop 9.x connection
     ├── Test: Product sync with real data
     └── Test: Category sync with real data
```

---

## 🧪 TESTING STRATEGY

### Unit Tests:
```php
// tests/Unit/Services/PrestaShop/
├── ClientFactoryTest.php
├── ProductTransformerTest.php
├── CategoryMapperTest.php
└── ProductSyncStrategyTest.php
```

### Feature Tests:
```php
// tests/Feature/PrestaShop/
├── ConnectionTest.php
├── ProductSyncTest.php
├── CategorySyncTest.php
└── MappingTest.php
```

### Manual Integration Tests:
1. Stwórz testowy sklep PrestaShop 8.x
2. Stwórz testowy sklep PrestaShop 9.x
3. Test połączenia API
4. Test synchronizacji 1 produktu
5. Test synchronizacji 1 kategorii
6. Test mapowania kategorii
7. Test błędnej synchronizacji
8. Test retry mechanism

---

## 🚀 DEPLOYMENT STRATEGY

### Deployment Process:
```powershell
# 1. Deploy migracji
pscp -i $HostidoKey migrations/* host379076@...
plink ... "php artisan migrate --force"

# 2. Deploy modeli
pscp -i $HostidoKey app/Models/* host379076@...

# 3. Deploy services
pscp -i $HostidoKey app/Services/PrestaShop/* host379076@...

# 4. Deploy jobs
pscp -i $HostidoKey app/Jobs/PrestaShop/* host379076@...

# 5. Deploy Livewire components
pscp -i $HostidoKey app/Http/Livewire/Admin/Shops/* host379076@...

# 6. Deploy views
pscp -i $HostidoKey resources/views/livewire/admin/shops/* host379076@...

# 7. Clear cache
plink ... "php artisan view:clear && php artisan cache:clear"

# 8. Restart queue workers (jeśli używane)
plink ... "php artisan queue:restart"
```

### Rollback Strategy:
- Git commit przed każdym krokiem
- Backup bazy danych przed migracjami
- Możliwość rollback migracji (`php artisan migrate:rollback`)
- Backup plików przed deployment

---

## 📊 SUCCESS CRITERIA

### FAZA 1 jest ukończona gdy:
- ✅ Panel dodawania sklepu PrestaShop działa
- ✅ Test połączenia z PrestaShop API działa (8.x i 9.x)
- ✅ Możliwe jest zsynchronizowanie 1 produktu do PrestaShop
- ✅ Możliwa jest synchronizacja hierarchii kategorii
- ✅ Mapowania kategorii/cen/magazynów działają
- ✅ Status synchronizacji jest widoczny w ProductForm
- ✅ Queue jobs działają w tle
- ✅ Logging operacji sync działa
- ✅ Unit tests przechodzą (>80% coverage)
- ✅ Manual integration tests przechodzą

---

## 🔄 NASTĘPNE KROKI (Po FAZA 1)

### FAZA 2: Zaawansowana synchronizacja
- Synchronizacja zdjęć produktów
- Synchronizacja PrestaShop → PPM (dwukierunkowa)
- Bulk operations UI
- Advanced conflict resolution
- Automatic sync scheduling

### FAZA 3: Monitoring & Webhooks
- Webhook system dla real-time updates
- Real-time monitoring dashboard
- Performance metrics i analytics
- Alerting system
- Retry queue management UI

---

**NOTATKI IMPLEMENTACYJNE:**

1. **Context7 MCP:** Wszystkie agenty MUSZĄ używać Context7 dla aktualnej dokumentacji PrestaShop API
2. **Debug Logging:** Podczas development używać extensive Log::debug(), po user confirmation wyczyścić
3. **Enterprise Quality:** Bez skrótów, proper error handling, comprehensive logging
4. **Testing First:** Unit tests przed feature tests przed integration tests
5. **Incremental Deployment:** Deploy po każdym zakończonym module, nie wszystko naraz

**CZAS REALIZACJI:** ~10 dni roboczych (80h developer time)
**COMPLEXITY:** HIGH (integracja z external API, multi-version support)
**RISK LEVEL:** MEDIUM (zależność od PrestaShop API availability)

---

**Autor:** Claude Code - architect agent
**Data:** 2025-10-01
**Wersja:** 1.0
