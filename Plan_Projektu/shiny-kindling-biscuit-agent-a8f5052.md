# PLAN IMPLEMENTACJI: BaseLinker ERP Integration

**Data utworzenia:** 2026-01-16
**Autor:** Claude Code AI (architect agent)
**Status:** PLAN_READY

---

## EXECUTIVE SUMMARY

Plan kompleksowej implementacji integracji BaseLinker ERP w projekcie PPM-CC-Laravel. Na podstawie audytu istniejacego kodu stwierdzono, ze wiele elementow infrastruktury juz istnieje, ale wymaga uzupelnienia i modernizacji.

---

## 1. AUDYT INFRASTRUKTURY (COMPLETED)

### 1.1 ISTNIEJACE ELEMENTY

#### 1.1.1 Modele (GOTOWE)
| Plik | Status | Uwagi |
|------|--------|-------|
| `app/Models/ERPConnection.php` | ISTNIEJE (579 linii) | Pelna implementacja z constants, relationships, methods |
| `app/Models/SyncJob.php` | ISTNIEJE (700 linii) | Kompletna implementacja job tracking |
| `app/Models/IntegrationMapping.php` | ISTNIEJE (663 linii) | Polymorphic mapping dla wszystkich systemow |
| `app/Models/IntegrationLog.php` | ISTNIEJE (593 linii) | Comprehensive logging z PSR-3 levels |
| `app/Models/SyncLog.php` | ISTNIEJE | Dodatkowy model sync logging |

#### 1.1.2 Migracje (GOTOWE)
| Plik | Kolumny | Status |
|------|---------|--------|
| `2024_01_01_000027_create_erp_connections_table.php` | ~50 kolumn | ISTNIEJE - enterprise-grade |
| `2024_01_01_000028_create_sync_jobs_table.php` | ~40 kolumn | ISTNIEJE - kompletna |
| `2024_01_01_000014_create_integration_mappings_table.php` | ~15 kolumn | ISTNIEJE - polymorphic |
| `2024_01_01_000029_create_integration_logs_table.php` | ~50 kolumn | ISTNIEJE - enterprise |

#### 1.1.3 Services (CZESCIOWO GOTOWE)
| Plik | Status | Kompletnosc |
|------|--------|-------------|
| `app/Services/ERP/BaselinkerService.php` | ISTNIEJE (669 linii) | ~60% gotowe |

**BaselinkerService - implementacja:**
- testAuthentication() - GOTOWE
- testConnection() - GOTOWE
- syncProducts() - GOTOWE (basic)
- syncSingleProduct() - GOTOWE
- createBaselinkerProduct() - GOTOWE
- updateBaselinkerProduct() - GOTOWE
- syncProductStock() - GOTOWE (basic)
- syncProductPrices() - GOTOWE (basic)
- getOrders() - GOTOWE
- makeRequest() - GOTOWE z logging

**BRAKUJACE:**
- syncProductFromBaseLinker() - NIE ZAIMPLEMENTOWANE
- syncAllFromERP() - NIE ZAIMPLEMENTOWANE
- getInventoryProductsData() - NIE ZAIMPLEMENTOWANE
- updateInventoryProductsPrices() - bulk method
- updateInventoryProductsStock() - bulk method
- getInventoryCategories() - NIE ZAIMPLEMENTOWANE
- addInventoryCategory() - NIE ZAIMPLEMENTOWANE

#### 1.1.4 Livewire Components (NIE ISTNIEJA)
- `app/Http/Livewire/Admin/ERP/*` - DO UTWORZENIA
- ERPConnectionManager.php - DO UTWORZENIA
- ERPDashboard.php - DO UTWORZENIA
- ERPSyncMonitor.php - DO UTWORZENIA

#### 1.1.5 Jobs (NIE ISTNIEJA)
- `app/Jobs/ERP/*` - DO UTWORZENIA
- SyncProductToERP.php - DO UTWORZENIA
- SyncStockToBaseLinker.php - DO UTWORZENIA
- SyncPricesToBaseLinker.php - DO UTWORZENIA
- PullProductsFromBaseLinker.php - DO UTWORZENIA

---

### 1.2 ROZNICE VS ETAP_08 SPEC

#### 1.2.1 Tabele - porownanie

| ETAP_08 Spec | Istniejacy odpowiednik | Status |
|--------------|------------------------|--------|
| `erp_connections` | `erp_connections` | ZGODNE - istniejaca tabela ma WIECEJ kolumn |
| `erp_field_mappings` | `integration_mappings` | CZESCIOWO - inna struktura ale pokrywa |
| `erp_sync_jobs` | `sync_jobs` | ZGODNE - istniejaca tabela jest kompletna |
| `erp_entity_sync_status` | `integration_mappings.sync_status` | ZINTEGROWANE - w ramach integration_mappings |
| `erp_sync_logs` | `integration_logs` | ZGODNE - istniejaca tabela jest lepsza |

**DECYZJA:** NIE TWORZYMY NOWYCH TABEL - uzywamy istniejacych, ktore sa bardziej kompletne niz spec ETAP_08.

#### 1.2.2 Modele - porownanie

| ETAP_08 Spec | Istniejacy Model | Status |
|--------------|------------------|--------|
| `ErpConnection` | `ERPConnection` | ZGODNE |
| `ErpFieldMapping` | Nie istnieje osobno | Uzywamy `integration_mappings` |
| `ErpSyncJob` | `SyncJob` | ZGODNE |
| `ErpEntitySyncStatus` | `IntegrationMapping` | ZINTEGROWANE |
| `ErpSyncLog` | `IntegrationLog` | ZGODNE |

---

### 1.3 KRYTYCZNE DECYZJE ARCHITEKTONICZNE

#### 1.3.1 Rate Limit Discrepancy

**PROBLEM:**
- ETAP_08 spec mowi 60 req/min
- BaseLinker official API: 100 req/min
- Istniejacy kod: `protected $rateLimit = 60;`

**DECYZJA:** Zachowac 60 req/min jako bezpieczny margines (60% official limit)

**UZASADNIENIE:**
- Bezpiecznosc - burst traffic nie spowoduje blokady
- Stabilnosc - mniej ryzykowne dla produkcji
- Mozna zwiekszyc pozniej po stabilizacji

#### 1.3.2 Field Mappings Strategy

**OPCJA A:** Tworzyc nowa tabele `erp_field_mappings` per ETAP_08 spec
**OPCJA B:** Uzywac istniejacych `integration_mappings` + JSON `field_mappings` w `erp_connections`

**DECYZJA:** OPCJA B - Hybrid approach

**UZASADNIENIE:**
- `erp_connections.field_mappings` (JSON) - konfiguracja static mappings
- `integration_mappings` - runtime entity mappings (product_id -> baselinker_id)
- Mniej tabel, mniej complexity, istniejaca infrastruktura

#### 1.3.3 Sync Architecture

**SKU-First Strategy:**
- SKU jako primary identifier dla product sync
- BaseLinker product_id jako secondary (external_id w integration_mappings)
- Fallback: EAN jeśli SKU brak

---

## 2. PLAN IMPLEMENTACJI

### FAZA 1: BaseLinker Service Completion (8h)

#### 2.1.1 Uzupelnienie BaselinkerService

**Plik:** `app/Services/ERP/BaselinkerService.php`

**Brakujace metody do implementacji:**

```
1. syncProductFromBaseLinker(ERPConnection, string $baselinkerProductId)
   - Pobiera produkt z BaseLinker
   - Transformuje do formatu PPM
   - Aktualizuje/tworzy w PPM

2. syncAllFromERP(ERPConnection)
   - Pobiera liste produktow z BaseLinker
   - Iteruje przez produkty
   - Sync do PPM

3. getInventoryProductsData(array $config, array $productIds)
   - Pobiera szczegolowe dane produktow
   - Bulk operation

4. syncCategoriesToBaseLinker(ERPConnection)
   - Sync kategorii PPM -> BaseLinker

5. syncCategoriesFromBaseLinker(ERPConnection)
   - Sync kategorii BaseLinker -> PPM
```

#### 2.1.2 Rate Limiting Improvements

**Obecny kod:**
```php
// usleep(1000000); // 1 second - ZA WOLNO!
```

**Nowy kod:**
```php
// Smart rate limiting - 60 req/min = 1 req/s
// Ale mozemy grupowac w batch
protected function checkRateLimit(): void
{
    $cacheKey = "bl_rate_{$this->connection->id}";
    $requests = Cache::get($cacheKey, ['count' => 0, 'reset_at' => now()]);

    if (now()->gt($requests['reset_at'])) {
        $requests = ['count' => 0, 'reset_at' => now()->addMinute()];
    }

    if ($requests['count'] >= $this->rateLimit) {
        $sleepSeconds = $requests['reset_at']->diffInSeconds(now());
        sleep($sleepSeconds + 1);
        $requests = ['count' => 0, 'reset_at' => now()->addMinute()];
    }

    $requests['count']++;
    Cache::put($cacheKey, $requests, 120);
}
```

#### 2.1.3 Bulk Operations (BaseLinker API Best Practice)

**BaseLinker API limits:**
- `updateInventoryProductsPrices` - max 1000 products/request
- `updateInventoryProductsStock` - max 1000 products/request

**Implementacja:**
```php
public function bulkUpdatePrices(ERPConnection $connection, array $priceUpdates): array
{
    $results = ['success' => 0, 'failed' => 0, 'errors' => []];

    // Chunk by 1000
    foreach (array_chunk($priceUpdates, 1000) as $batch) {
        $response = $this->makeRequest(
            $connection->connection_config,
            'updateInventoryProductsPrices',
            [
                'inventory_id' => $connection->connection_config['inventory_id'],
                'products' => $batch
            ]
        );

        if ($response['status'] === 'SUCCESS') {
            $results['success'] += count($batch);
        } else {
            $results['failed'] += count($batch);
            $results['errors'][] = $response['error_message'] ?? 'Unknown error';
        }
    }

    return $results;
}
```

---

### FAZA 2: ERPServiceManager & Interface (4h)

#### 2.2.1 ERPSyncServiceInterface

**Plik:** `app/Services/ERP/Contracts/ERPSyncServiceInterface.php`

```php
interface ERPSyncServiceInterface
{
    public function testConnection(): array;
    public function syncProductToERP(Product $product): array;
    public function syncProductFromERP(string $erpProductId): array;
    public function syncAllProductsToERP(array $filters = []): array;
    public function syncAllProductsFromERP(): array;
    public function syncStock(Product $product): array;
    public function syncPrices(Product $product): array;
    public function syncCategories(): array;
}
```

#### 2.2.2 ERPServiceManager (Factory Pattern)

**Plik:** `app/Services/ERP/ERPServiceManager.php`

```php
class ERPServiceManager
{
    protected array $resolvedServices = [];

    public function getService(ERPConnection $connection): ERPSyncServiceInterface
    {
        $key = $connection->erp_type . '_' . $connection->id;

        if (!isset($this->resolvedServices[$key])) {
            $this->resolvedServices[$key] = $this->resolveService($connection);
        }

        return $this->resolvedServices[$key];
    }

    protected function resolveService(ERPConnection $connection): ERPSyncServiceInterface
    {
        return match($connection->erp_type) {
            ERPConnection::ERP_BASELINKER => new BaseLinkerSyncService($connection),
            ERPConnection::ERP_SUBIEKT_GT => new SubiektGTSyncService($connection), // placeholder
            ERPConnection::ERP_DYNAMICS => new DynamicsSyncService($connection), // placeholder
            default => throw new InvalidArgumentException("Unsupported ERP type: {$connection->erp_type}")
        };
    }

    public function syncProductToAllERP(Product $product): array {...}
}
```

#### 2.2.3 BaseLinkerSyncService Adapter

**Plik:** `app/Services/ERP/BaseLinker/BaseLinkerSyncService.php`

Wrapper ktory implementuje `ERPSyncServiceInterface` i deleguje do `BaselinkerService`.

---

### FAZA 3: Queue Jobs (6h)

#### 2.3.1 SyncProductToERP Job

**Plik:** `app/Jobs/ERP/SyncProductToERP.php`

```php
class SyncProductToERP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Batchable;

    public int $tries = 3;
    public int $timeout = 300;
    public array $backoff = [30, 60, 120]; // Exponential backoff

    public function __construct(
        public Product $product,
        public ERPConnection $connection
    ) {
        $this->onQueue($this->determineQueue());
    }

    protected function determineQueue(): string
    {
        return $this->product->is_featured ? 'erp_high' : 'erp_default';
    }

    public function handle(ERPServiceManager $erpManager): void
    {
        $service = $erpManager->getService($this->connection);

        // Create/update SyncJob record
        $syncJob = SyncJob::create([
            'job_id' => (string) Str::uuid(),
            'job_type' => SyncJob::JOB_PRODUCT_SYNC,
            'job_name' => "Sync product {$this->product->sku} to {$this->connection->instance_name}",
            'source_type' => SyncJob::TYPE_PPM,
            'source_id' => (string) $this->product->id,
            'target_type' => $this->connection->erp_type,
            'target_id' => (string) $this->connection->id,
            'status' => SyncJob::STATUS_RUNNING,
            'total_items' => 1,
            'started_at' => now(),
            'trigger_type' => SyncJob::TRIGGER_EVENT,
        ]);

        try {
            $result = $service->syncProductToERP($this->product);

            if ($result['success']) {
                $syncJob->complete(['result' => $result]);
                $this->connection->updateSyncStats(true, 1);
            } else {
                $syncJob->fail($result['message'] ?? 'Unknown error');
                $this->connection->updateSyncStats(false);
            }
        } catch (Exception $e) {
            $syncJob->fail($e->getMessage(), null, $e->getTraceAsString());
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SyncProductToERP failed', [
            'product_id' => $this->product->id,
            'connection_id' => $this->connection->id,
            'error' => $exception->getMessage()
        ]);

        // Update connection health
        $this->connection->updateConnectionHealth(
            ERPConnection::CONNECTION_ERROR,
            null,
            $exception->getMessage()
        );
    }
}
```

#### 2.3.2 BulkSyncProductsToERP Job

**Plik:** `app/Jobs/ERP/BulkSyncProductsToERP.php`

Job dla batch synchronizacji wielu produktow z progress tracking.

#### 2.3.3 PullProductsFromBaseLinker Job

**Plik:** `app/Jobs/ERP/PullProductsFromBaseLinker.php`

Job dla pobierania produktow z BaseLinker do PPM.

---

### FAZA 4: Livewire Admin Panel (12h)

#### 2.4.1 ERPConnectionManager Component

**Plik:** `app/Http/Livewire/Admin/ERP/ERPConnectionManager.php`
**View:** `resources/views/livewire/admin/erp/connection-manager.blade.php`

**Funkcjonalnosci:**
- Lista wszystkich ERP connections z statusami
- Add/Edit connection form
- Test Connection button
- Enable/Disable connection
- Delete connection (soft delete)
- View connection logs

#### 2.4.2 ERPDashboard Component

**Plik:** `app/Http/Livewire/Admin/ERP/ERPDashboard.php`
**View:** `resources/views/livewire/admin/erp/dashboard.blade.php`

**Funkcjonalnosci:**
- Overview all ERP connections health
- Sync statistics (today, week, month)
- Recent sync jobs list
- Error rate monitoring
- Quick actions (trigger sync, retry failed)

#### 2.4.3 ERPSyncMonitor Component

**Plik:** `app/Http/Livewire/Admin/ERP/ERPSyncMonitor.php`
**View:** `resources/views/livewire/admin/erp/sync-monitor.blade.php`

**Funkcjonalnosci:**
- Real-time sync progress (wire:poll)
- Job details view
- Cancel/Pause/Resume jobs
- View job logs
- Retry failed items

#### 2.4.4 Routes

```php
// routes/web.php
Route::prefix('admin/erp')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', ERPDashboard::class)->name('admin.erp.dashboard');
    Route::get('/connections', ERPConnectionManager::class)->name('admin.erp.connections');
    Route::get('/connections/{connection}/sync', ERPSyncMonitor::class)->name('admin.erp.sync-monitor');
    Route::get('/connections/{connection}/logs', ERPConnectionLogs::class)->name('admin.erp.logs');
});
```

---

### FAZA 5: Testing & Verification (4h)

#### 2.5.1 Unit Tests

**Plik:** `tests/Unit/Services/ERP/BaseLinkerServiceTest.php`

```php
class BaseLinkerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'api.baselinker.com/*' => Http::response(['status' => 'SUCCESS', 'inventories' => []])
        ]);
    }

    public function test_can_test_authentication(): void {...}
    public function test_can_sync_product_to_baselinker(): void {...}
    public function test_handles_rate_limit(): void {...}
    public function test_handles_api_errors(): void {...}
}
```

#### 2.5.2 Feature Tests

**Plik:** `tests/Feature/Admin/ERPIntegrationTest.php`

- Test panel admin dziala
- Test crud connections
- Test sync operations

#### 2.5.3 Chrome DevTools Verification

**MANDATORY przed zamknieciem FAZY:**
1. Navigate to https://ppm.mpptrade.pl/admin/erp
2. Test connection form submission
3. Verify sync monitoring works
4. Check console for errors

---

## 3. HARMONOGRAM

| Faza | Nazwa | Czas | Zalezy od |
|------|-------|------|-----------|
| 1 | BaseLinker Service Completion | 8h | - |
| 2 | ERPServiceManager & Interface | 4h | Faza 1 |
| 3 | Queue Jobs | 6h | Faza 1, 2 |
| 4 | Livewire Admin Panel | 12h | Faza 1, 2, 3 |
| 5 | Testing & Verification | 4h | Faza 1-4 |

**TOTAL:** 34h

---

## 4. KRYTERIA AKCEPTACJI

### 4.1 Faza 1 Complete When:
- [ ] BaselinkerService ma wszystkie wymagane metody
- [ ] Rate limiting dziala poprawnie
- [ ] Bulk operations zaimplementowane
- [ ] Unit tests GREEN

### 4.2 Faza 2 Complete When:
- [ ] ERPSyncServiceInterface zdefiniowany
- [ ] ERPServiceManager Factory dziala
- [ ] BaseLinkerSyncService implementuje interface

### 4.3 Faza 3 Complete When:
- [ ] SyncProductToERP job dziala
- [ ] BulkSyncProductsToERP job dziala
- [ ] Queue processing testowane
- [ ] Failed jobs sa retry'owane

### 4.4 Faza 4 Complete When:
- [ ] ERPConnectionManager - CRUD dziala
- [ ] ERPDashboard - statystyki wyswietlane
- [ ] ERPSyncMonitor - real-time progress dziala
- [ ] Chrome DevTools verification OK

### 4.5 Faza 5 Complete When:
- [ ] Wszystkie unit tests GREEN
- [ ] Wszystkie feature tests GREEN
- [ ] Production deployment OK
- [ ] Manual testing OK

---

## 5. RYZYKA I MITYGACJE

| Ryzyko | Prawdopodobienstwo | Impact | Mitygacja |
|--------|-------------------|--------|-----------|
| BaseLinker API rate limit | Medium | High | Conservative limit (60/min vs 100/min official) |
| Data inconsistency | Medium | Medium | SKU-First strategy, checksums |
| Long sync times | Medium | Low | Batch operations, queue priority |
| Production downtime | Low | High | Feature flag, gradual rollout |

---

## 6. PLIKI DO UTWORZENIA

### Nowe pliki:
1. `app/Services/ERP/Contracts/ERPSyncServiceInterface.php`
2. `app/Services/ERP/ERPServiceManager.php`
3. `app/Services/ERP/BaseLinker/BaseLinkerSyncService.php`
4. `app/Jobs/ERP/SyncProductToERP.php`
5. `app/Jobs/ERP/BulkSyncProductsToERP.php`
6. `app/Jobs/ERP/PullProductsFromBaseLinker.php`
7. `app/Http/Livewire/Admin/ERP/ERPConnectionManager.php`
8. `app/Http/Livewire/Admin/ERP/ERPDashboard.php`
9. `app/Http/Livewire/Admin/ERP/ERPSyncMonitor.php`
10. `resources/views/livewire/admin/erp/connection-manager.blade.php`
11. `resources/views/livewire/admin/erp/dashboard.blade.php`
12. `resources/views/livewire/admin/erp/sync-monitor.blade.php`
13. `tests/Unit/Services/ERP/BaseLinkerServiceTest.php`
14. `tests/Feature/Admin/ERPIntegrationTest.php`

### Pliki do modyfikacji:
1. `app/Services/ERP/BaselinkerService.php` - dodanie brakujacych metod
2. `routes/web.php` - dodanie routes ERP admin panel
3. `config/queue.php` - konfiguracja ERP queues

---

## 7. NEXT STEPS

Po zatwierdzeniu planu przez uzytkownika:

1. **Utworzenie szczegolowego pliku** `Plan_Projektu/ETAP_08a_BaseLinker_Integration.md` z podziałem na podzadania zgodnie z formatem projektowym
2. **Aktualizacja** `Plan_Projektu/ETAP_08_ERP_Integracje.md` ze statusami
3. **Rozpoczecie implementacji** od Fazy 1

---

**PYTANIA DO UZYTKOWNIKA:**

1. Czy priorytet to pełna dwukierunkowa synchronizacja (PPM <-> BaseLinker) czy na początek tylko PPM -> BaseLinker (push)?

2. Czy mamy dostep do konta testowego BaseLinker z API key do testow?

3. Czy panel admin ERP powinien byc dostepny dla wszystkich administratorow czy tylko dla super admin?

4. Czy chcemy implementowac Subiekt GT i Dynamics w tym samym etapie czy jako oddzielne fazy?
