<?php

namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PrestaShopShop;
use App\Models\SyncJob;
use App\Services\PrestaShop\PrestaShopService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * ShopManager Livewire Component
 * 
 * FAZA B: Shop & ERP Management - PrestaShop Connections Dashboard
 * 
 * Kompleksowy komponent zarządzania sklepami PrestaShop z features:
 * - Real-time connection health monitoring
 * - Shop configuration wizard z multi-step setup
 * - Manual i bulk sync operations
 * - Performance metrics i connection testing
 * - Advanced filtering i search capabilities
 * 
 * Enterprise Features:
 * - Automatic health checks z retry logic
 * - Connection wizard z validation steps
 * - Real-time sync progress tracking
 * - Error handling z detailed diagnostics
 */
class ShopManager extends Component
{
    use WithPagination, AuthorizesRequests;

    // Component State
    public $showAddShop = false;
    public $showShopDetails = false;
    public $selectedShop = null;
    public $testingConnection = false;
    public $syncingShop = false;

    // Filters and Search
    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    // Add/Edit Shop Form
    public $shopForm = [
        'name' => '',
        'url' => '',
        'description' => '',
        'api_key' => '',
        'api_version' => '1.7',
        'ssl_verify' => true,
        'timeout_seconds' => 30,
        'rate_limit_per_minute' => 60,
        'sync_frequency' => 'hourly',
        'auto_sync_products' => true,
        'auto_sync_categories' => true,
        'auto_sync_prices' => true,
        'auto_sync_stock' => true,
        'conflict_resolution' => 'ppm_wins',
        'notify_on_errors' => true,
        'notify_on_sync_complete' => false,
    ];

    // Shop Configuration Wizard State
    public $wizardStep = 1;
    public $wizardData = [];
    public $connectionTestResult = null;

    // Listeners
    protected $listeners = [
        'shopUpdated' => '$refresh',
        'syncCompleted' => 'handleSyncCompleted',
        'refreshShops' => '$refresh',
    ];

    /**
     * Component validation rules.
     */
    protected function rules()
    {
        return [
            'shopForm.name' => 'required|min:3|max:200',
            'shopForm.url' => 'required|url|max:500',
            'shopForm.description' => 'nullable|max:1000',
            'shopForm.api_key' => 'required|min:32|max:200',
            'shopForm.api_version' => 'required|in:1.6,1.7,8.0,9.0',
            'shopForm.timeout_seconds' => 'required|integer|min:5|max:300',
            'shopForm.rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'shopForm.sync_frequency' => 'required|in:realtime,hourly,daily,manual',
            'shopForm.conflict_resolution' => 'required|in:ppm_wins,prestashop_wins,manual,newest_wins',
        ];
    }

    /**
     * Mount component.
     */
    public function mount()
    {
        $this->authorize('admin.shops.view');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $shops = $this->getShops();
        $stats = $this->getShopStats();

        return view('livewire.admin.shops.shop-manager', [
            'shops' => $shops,
            'stats' => $stats,
        ])->layout('layouts.admin');
    }

    /**
     * Get shops with filtering and pagination.
     */
    protected function getShops()
    {
        $query = PrestaShopShop::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('url', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            switch ($this->statusFilter) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'connected':
                    $query->healthy();
                    break;
                case 'issues':
                    $query->withConnectionIssues();
                    break;
                case 'sync_due':
                    $query->dueForSync();
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    /**
     * Get shop statistics.
     */
    protected function getShopStats()
    {
        return [
            'total' => PrestaShopShop::count(),
            'active' => PrestaShopShop::active()->count(),
            'connected' => PrestaShopShop::healthy()->count(),
            'issues' => PrestaShopShop::withConnectionIssues()->count(),
            'sync_due' => PrestaShopShop::dueForSync()->count(),
        ];
    }

    /**
     * Start shop configuration wizard.
     */
    public function startWizard()
    {
        $this->authorize('admin.shops.create');
        
        $this->resetWizard();
        $this->showAddShop = true;
    }

    /**
     * Reset wizard state.
     */
    protected function resetWizard()
    {
        $this->wizardStep = 1;
        $this->wizardData = [];
        $this->connectionTestResult = null;
        $this->shopForm = [
            'name' => '',
            'url' => '',
            'description' => '',
            'api_key' => '',
            'api_version' => '1.7',
            'ssl_verify' => true,
            'timeout_seconds' => 30,
            'rate_limit_per_minute' => 60,
            'sync_frequency' => 'hourly',
            'auto_sync_products' => true,
            'auto_sync_categories' => true,
            'auto_sync_prices' => true,
            'auto_sync_stock' => true,
            'conflict_resolution' => 'ppm_wins',
            'notify_on_errors' => true,
            'notify_on_sync_complete' => false,
        ];
    }

    /**
     * Go to next wizard step.
     */
    public function nextWizardStep()
    {
        $this->validateCurrentWizardStep();
        
        $this->wizardStep++;
        
        if ($this->wizardStep === 3) {
            // Step 3: Connection Test
            $this->testShopConnection();
        }
    }

    /**
     * Go to previous wizard step.
     */
    public function previousWizardStep()
    {
        if ($this->wizardStep > 1) {
            $this->wizardStep--;
        }
    }

    /**
     * Validate current wizard step.
     */
    protected function validateCurrentWizardStep()
    {
        switch ($this->wizardStep) {
            case 1:
                $this->validate([
                    'shopForm.name' => 'required|min:3|max:200',
                    'shopForm.url' => 'required|url|max:500',
                    'shopForm.description' => 'nullable|max:1000',
                ]);
                break;
            case 2:
                $this->validate([
                    'shopForm.api_key' => 'required|min:32|max:200',
                    'shopForm.api_version' => 'required|in:1.6,1.7,8.0,9.0',
                    'shopForm.timeout_seconds' => 'required|integer|min:5|max:300',
                    'shopForm.rate_limit_per_minute' => 'required|integer|min:1|max:1000',
                ]);
                break;
        }
    }

    /**
     * Test shop connection during wizard.
     */
    public function testShopConnection()
    {
        $this->testingConnection = true;
        $this->connectionTestResult = null;

        try {
            $prestaShopService = new PrestaShopService();
            
            $result = $prestaShopService->testConnection([
                'url' => $this->shopForm['url'],
                'api_key' => $this->shopForm['api_key'],
                'ssl_verify' => $this->shopForm['ssl_verify'],
                'timeout' => $this->shopForm['timeout_seconds'],
            ]);

            $this->connectionTestResult = [
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'] ?? [],
                'prestashop_version' => $result['prestashop_version'] ?? null,
                'response_time' => $result['response_time'] ?? null,
                'supported_features' => $result['supported_features'] ?? [],
            ];

            if ($result['success']) {
                session()->flash('success', 'Połączenie z sklepem PrestaShop zostało pomyślnie przetestowane!');
            } else {
                session()->flash('error', 'Test połączenia nieudany: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->connectionTestResult = [
                'success' => false,
                'message' => 'Błąd testowania połączenia: ' . $e->getMessage(),
                'details' => [],
            ];
            
            session()->flash('error', 'Błąd podczas testowania połączenia');
        }

        $this->testingConnection = false;
    }

    /**
     * Complete wizard and save shop.
     */
    public function completeWizard()
    {
        $this->validate();
        
        try {
            $shopData = $this->shopForm;
            
            // Add connection test results if available
            if ($this->connectionTestResult && $this->connectionTestResult['success']) {
                $shopData['connection_status'] = PrestaShopShop::CONNECTION_CONNECTED;
                $shopData['prestashop_version'] = $this->connectionTestResult['prestashop_version'];
                $shopData['last_response_time'] = $this->connectionTestResult['response_time'];
                $shopData['supported_features'] = $this->connectionTestResult['supported_features'];
                $shopData['last_connection_test'] = now();
                $shopData['version_compatible'] = true;
            } else {
                $shopData['connection_status'] = PrestaShopShop::CONNECTION_DISCONNECTED;
            }
            
            // Calculate next sync time
            $shop = PrestaShopShop::create($shopData);
            $shop->next_scheduled_sync = $shop->calculateNextSyncTime();
            $shop->save();

            session()->flash('success', 'Sklep PrestaShop został dodany pomyślnie!');
            
            $this->closeWizard();
            $this->emit('shopCreated', $shop->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas dodawania sklepu: ' . $e->getMessage());
        }
    }

    /**
     * Close wizard.
     */
    public function closeWizard()
    {
        $this->showAddShop = false;
        $this->resetWizard();
    }

    /**
     * Test connection for existing shop.
     */
    public function testConnection($shopId)
    {
        $this->authorize('admin.shops.test');
        
        $shop = PrestaShopShop::findOrFail($shopId);
        $this->testingConnection = true;

        try {
            $prestaShopService = new PrestaShopService();
            
            $result = $prestaShopService->testConnection([
                'url' => $shop->url,
                'api_key' => $shop->api_key,
                'ssl_verify' => $shop->ssl_verify,
                'timeout' => $shop->timeout_seconds,
            ]);

            $shop->updateConnectionHealth(
                $result['success'] ? PrestaShopShop::CONNECTION_CONNECTED : PrestaShopShop::CONNECTION_ERROR,
                $result['response_time'] ?? null,
                $result['success'] ? null : $result['message']
            );

            if ($result['success']) {
                session()->flash('success', "Połączenie ze sklepem '{$shop->name}' działa prawidłowo!");
            } else {
                session()->flash('error', "Test połączenia ze sklepem '{$shop->name}' nieudany: " . $result['message']);
            }

        } catch (\Exception $e) {
            $shop->updateConnectionHealth(
                PrestaShopShop::CONNECTION_ERROR,
                null,
                'Błąd testowania połączenia: ' . $e->getMessage()
            );
            
            session()->flash('error', 'Błąd podczas testowania połączenia');
        }

        $this->testingConnection = false;
    }

    /**
     * Trigger manual sync for shop.
     */
    public function syncShop($shopId)
    {
        $this->authorize('admin.shops.sync');
        
        $shop = PrestaShopShop::findOrFail($shopId);
        $this->syncingShop = true;

        try {
            $syncJob = SyncJob::create([
                'job_id' => \Str::uuid(),
                'job_type' => SyncJob::JOB_PRODUCT_SYNC,
                'job_name' => "Synchronizacja produktów: {$shop->name}",
                'source_type' => SyncJob::TYPE_PPM,
                'target_type' => SyncJob::TYPE_PRESTASHOP,
                'target_id' => $shop->id,
                'trigger_type' => SyncJob::TRIGGER_MANUAL,
                'user_id' => auth()->id(),
                'scheduled_at' => now(),
                'job_config' => [
                    'shop_id' => $shop->id,
                    'sync_type' => 'full',
                ],
            ]);

            // Dispatch job to queue
            \App\Jobs\PrestaShop\SyncProductsJob::dispatch($syncJob);

            session()->flash('success', "Synchronizacja sklepu '{$shop->name}' została uruchomiona!");
            
            $this->emit('syncStarted', $syncJob->job_id);

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas uruchamiania synchronizacji: ' . $e->getMessage());
        }

        $this->syncingShop = false;
    }

    /**
     * Toggle shop active status.
     */
    public function toggleShopStatus($shopId)
    {
        $this->authorize('admin.shops.edit');
        
        $shop = PrestaShopShop::findOrFail($shopId);
        $shop->is_active = !$shop->is_active;
        $shop->save();

        $status = $shop->is_active ? 'aktywny' : 'nieaktywny';
        session()->flash('success', "Sklep '{$shop->name}' jest teraz {$status}.");
    }

    /**
     * Delete shop.
     */
    public function deleteShop($shopId)
    {
        $this->authorize('admin.shops.delete');
        
        $shop = PrestaShopShop::findOrFail($shopId);
        $shopName = $shop->name;
        
        // Check for active sync jobs
        $activeSyncJobs = SyncJob::where('target_type', SyncJob::TYPE_PRESTASHOP)
            ->where('target_id', $shopId)
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->count();

        if ($activeSyncJobs > 0) {
            session()->flash('error', "Nie można usunąć sklepu '{$shopName}' - trwają aktywne synchronizacje.");
            return;
        }

        $shop->delete();
        
        session()->flash('success', "Sklep '{$shopName}' został usunięty.");
    }

    /**
     * Show shop details modal.
     */
    public function showDetails($shopId)
    {
        $this->selectedShop = PrestaShopShop::with(['syncJobs' => function($q) {
            $q->latest()->take(5);
        }])->findOrFail($shopId);
        
        $this->showShopDetails = true;
    }

    /**
     * Close shop details modal.
     */
    public function closeDetails()
    {
        $this->showShopDetails = false;
        $this->selectedShop = null;
    }

    /**
     * Handle sync completed event.
     */
    public function handleSyncCompleted($jobId)
    {
        session()->flash('success', 'Synchronizacja została ukończona!');
        $this->emit('refreshShops');
    }

    /**
     * Update search query and reset pagination.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination.
     */
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Sort shops by column.
     */
    public function sortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
}