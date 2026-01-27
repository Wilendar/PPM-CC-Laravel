<?php

namespace App\Http\Livewire\Admin\ERP;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ERPConnection;
use App\Models\SyncJob;
use App\Services\ERP\BaselinkerService;
use App\Services\ERP\SubiektGTService;
use App\Services\ERP\DynamicsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * ERPManager Livewire Component
 * 
 * FAZA B: Shop & ERP Management - ERP Systems Integration Dashboard
 * 
 * Kompleksowy komponent zarządzania systemami ERP z features:
 * - Multi-ERP support (Baselinker, Subiekt GT, Microsoft Dynamics)
 * - Real-time connection health monitoring
 * - Advanced authentication management (OAuth2, API Keys, DLL bridges)
 * - Sync configuration i monitoring
 * - Performance metrics i error tracking
 * 
 * Enterprise Features:
 * - Priority-based ERP processing
 * - Automatic authentication renewal
 * - Comprehensive error recovery
 * - Multi-instance support per ERP type
 */
class ERPManager extends Component
{
    use WithPagination, AuthorizesRequests;

    // Component State
    public $showAddConnection = false;
    public $showConnectionDetails = false;
    public $selectedConnection = null;
    public $testingConnection = false;
    public $syncingERP = false;
    public bool $showReplaceAllConfirmModal = false;

    // Filters and Search
    public $search = '';
    public $erpTypeFilter = 'all';
    public $statusFilter = 'all';
    public $sortBy = 'priority';
    public $sortDirection = 'asc';

    // Mapping Management
    public bool $clearExistingMappingsOnSave = false;
    public ?int $editingConnectionId = null;
    public array $availableErpWarehouses = [];
    public array $availableErpPriceLevels = [];
    public array $warehouseMappings = [];
    public array $priceGroupMappings = [];
    public array $ppmWarehouses = [];
    public array $ppmPriceGroups = [];
    public array $mappingSummary = [];

    // Add/Edit ERP Form
    public $connectionForm = [
        'erp_type' => 'baselinker',
        'instance_name' => '',
        'description' => null,  // nullable in DB
        'is_active' => true,    // REQUIRED - was missing!
        'priority' => 1,
        'connection_config' => [],
        'sync_mode' => 'bidirectional',
        'auto_sync_products' => true,
        'auto_sync_stock' => true,
        'auto_sync_prices' => true,
        'auto_sync_orders' => false,
        'max_retry_attempts' => 3,
        'retry_delay_seconds' => 60,
        'auto_disable_on_errors' => false,  // was missing
        'error_threshold' => 10,            // was missing
        'webhook_enabled' => false,
        'notify_on_errors' => true,
        'notify_on_sync_complete' => false, // was missing
        'notify_on_auth_expire' => true,
    ];

    // ERP-specific configuration forms
    public $baselinkerConfig = [
        'api_token' => '',
        'inventory_id' => '',
        'warehouse_mappings' => [],
        // ETAP_08 FAZA 8: Warehouse Location Settings
        'default_location' => '',             // Default location for new products
        'copy_location_to_all' => false,      // Copy location to all warehouses on sync
    ];

    public $subiektConfig = [
        // REST API Configuration (ONLY mode supported)
        'connection_mode' => 'rest_api',
        'rest_api_url' => 'https://sapi.mpptrade.pl',
        'rest_api_key' => 'YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb',
        'rest_api_timeout' => 30,
        'rest_api_verify_ssl' => false, // Temporarily disabled - sapi.mpptrade.pl certificate issue
        // Mappings (populated after test)
        'default_warehouse_id' => 1,
        'default_price_type_id' => 1,
        'warehouse_mappings' => [],
        'price_group_mappings' => [],
        // Options
        'create_missing_products' => false,
        // ETAP_08 FAZA 8: Warehouse Location Settings
        'default_location' => '',             // Default location for new products (e.g., "A-12-3")
        'copy_location_to_all' => false,      // Copy location to all warehouses on sync
    ];

    public $dynamicsConfig = [
        'tenant_id' => '',
        'client_id' => '',
        'client_secret' => '',
        'odata_url' => '',
        'company_id' => '',
        'entity_mappings' => [],
    ];

    // Configuration Wizard State
    public $wizardStep = 1;
    public $authTestResult = null;

    // Listeners
    protected $listeners = [
        'connectionUpdated' => '$refresh',
        'syncCompleted' => 'handleSyncCompleted',
        'refreshConnections' => '$refresh',
    ];

    /**
     * Component validation rules.
     */
    protected function rules()
    {
        $rules = [
            'connectionForm.erp_type' => 'required|in:baselinker,subiekt_gt,dynamics,insert,custom',
            'connectionForm.instance_name' => 'required|min:3|max:200',
            'connectionForm.description' => 'nullable|max:1000',
            'connectionForm.priority' => 'required|integer|min:1|max:100',
            'connectionForm.sync_mode' => 'required|in:bidirectional,push_only,pull_only,disabled',
            'connectionForm.max_retry_attempts' => 'required|integer|min:1|max:10',
            'connectionForm.retry_delay_seconds' => 'required|integer|min:1|max:3600',
        ];

        // Add ERP-specific validation rules
        switch ($this->connectionForm['erp_type']) {
            case 'baselinker':
                $rules['baselinkerConfig.api_token'] = 'required|min:32';
                $rules['baselinkerConfig.inventory_id'] = 'required|integer';
                break;
            case 'subiekt_gt':
                $rules['subiektConfig.rest_api_url'] = 'required|url';
                $rules['subiektConfig.rest_api_key'] = 'required|min:32';
                $rules['subiektConfig.rest_api_timeout'] = 'required|integer|min:5|max:120';
                break;
            case 'dynamics':
                $rules['dynamicsConfig.tenant_id'] = 'required|uuid';
                $rules['dynamicsConfig.client_id'] = 'required|uuid';
                $rules['dynamicsConfig.client_secret'] = 'required|min:10';
                $rules['dynamicsConfig.odata_url'] = 'required|url';
                break;
        }

        return $rules;
    }

    /**
     * Mount component.
     */
    public function mount()
    {
        // DEVELOPMENT: Autoryzacja tymczasowo wyłączona
        // $this->authorize('admin.erp.view');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $connections = $this->getERPConnections();
        $stats = $this->getERPStats();
        $priorityConflicts = $this->checkPriorityConflicts();

        return view('livewire.admin.erp.erp-manager', [
            'connections' => $connections,
            'stats' => $stats,
            'priorityConflicts' => $priorityConflicts,
        ])->layout('layouts.admin');
    }

    /**
     * Get ERP connections with filtering and pagination.
     */
    protected function getERPConnections()
    {
        $query = ERPConnection::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('instance_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('erp_type', 'like', '%' . $this->search . '%');
            });
        }

        // Apply ERP type filter
        if ($this->erpTypeFilter !== 'all') {
            $query->where('erp_type', $this->erpTypeFilter);
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
                case 'auth_issues':
                    $query->withAuthIssues();
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    /**
     * Get ERP statistics.
     */
    protected function getERPStats()
    {
        return [
            'total' => ERPConnection::count(),
            'active' => ERPConnection::active()->count(),
            'connected' => ERPConnection::healthy()->count(),
            'issues' => ERPConnection::withConnectionIssues()->count(),
            'auth_issues' => ERPConnection::withAuthIssues()->count(),
            'baselinker' => ERPConnection::baselinker()->count(),
            'subiekt_gt' => ERPConnection::subiektGT()->count(),
            'dynamics' => ERPConnection::dynamics()->count(),
        ];
    }

    /**
     * Check for priority conflicts.
     */
    protected function checkPriorityConflicts()
    {
        return ERPConnection::active()
            ->select('priority')
            ->groupBy('priority')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('priority')
            ->toArray();
    }

    /**
     * Start connection wizard.
     */
    public function startWizard()
    {
        // DEVELOPMENT: $this->authorize('admin.erp.create');

        $this->resetWizard();
        $this->showAddConnection = true;
    }

    /**
     * Reset wizard state.
     */
    protected function resetWizard()
    {
        $this->wizardStep = 1;
        $this->authTestResult = null;
        $this->connectionForm = [
            'erp_type' => 'baselinker',
            'instance_name' => '',
            'description' => null,              // nullable in DB
            'is_active' => true,                // REQUIRED
            'priority' => $this->getNextPriority(),
            'connection_config' => [],
            'sync_mode' => 'bidirectional',
            'auto_sync_products' => true,
            'auto_sync_stock' => true,
            'auto_sync_prices' => true,
            'auto_sync_orders' => false,
            'max_retry_attempts' => 3,
            'retry_delay_seconds' => 60,
            'auto_disable_on_errors' => false,  // was missing
            'error_threshold' => 10,            // was missing
            'webhook_enabled' => false,
            'notify_on_errors' => true,
            'notify_on_sync_complete' => false, // was missing
            'notify_on_auth_expire' => true,
        ];
        $this->resetERPSpecificConfig();
    }

    /**
     * Reset ERP-specific configuration.
     */
    protected function resetERPSpecificConfig()
    {
        $this->baselinkerConfig = [
            'api_token' => '',
            'inventory_id' => '',
            'warehouse_mappings' => [],
            // ETAP_08 FAZA 8: Warehouse Location Settings
            'default_location' => '',
            'copy_location_to_all' => false,
        ];

        $this->subiektConfig = [
            'connection_mode' => 'rest_api',
            'rest_api_url' => 'https://sapi.mpptrade.pl',
            'rest_api_key' => '',
            'rest_api_timeout' => 30,
            'default_warehouse_id' => 1,
            'default_price_type_id' => 1,
            'warehouse_mappings' => [],
            'price_group_mappings' => [],
            'create_missing_products' => false,
            // ETAP_08 FAZA 8: Warehouse Location Settings
            'default_location' => '',
            'copy_location_to_all' => false,
        ];

        $this->dynamicsConfig = [
            'tenant_id' => '',
            'client_id' => '',
            'client_secret' => '',
            'odata_url' => '',
            'company_id' => '',
            'entity_mappings' => [],
        ];
    }

    /**
     * Get next available priority.
     */
    protected function getNextPriority(): int
    {
        return ERPConnection::max('priority') + 1 ?: 1;
    }

    /**
     * Handle ERP type change in wizard.
     */
    public function updatedConnectionFormErpType($value)
    {
        $this->resetERPSpecificConfig();
        
        // Set default instance name based on ERP type
        switch ($value) {
            case 'baselinker':
                $this->connectionForm['instance_name'] = 'Baselinker-' . time();
                break;
            case 'subiekt_gt':
                $this->connectionForm['instance_name'] = 'SubiektGT-' . time();
                break;
            case 'dynamics':
                $this->connectionForm['instance_name'] = 'Dynamics365-' . time();
                break;
        }
    }

    /**
     * Go to next wizard step.
     */
    public function nextWizardStep()
    {
        $this->validateCurrentWizardStep();
        
        $this->wizardStep++;
        
        if ($this->wizardStep === 3) {
            // Step 3: Authentication Test
            $this->testAuthentication();
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
                    'connectionForm.erp_type' => 'required|in:baselinker,subiekt_gt,dynamics,insert,custom',
                    'connectionForm.instance_name' => 'required|min:3|max:200',
                    'connectionForm.description' => 'nullable|max:1000',
                    'connectionForm.priority' => 'required|integer|min:1|max:100',
                ]);
                break;
            case 2:
                $this->validateERPSpecificConfig();
                break;
        }
    }

    /**
     * Validate ERP-specific configuration.
     */
    protected function validateERPSpecificConfig()
    {
        switch ($this->connectionForm['erp_type']) {
            case 'baselinker':
                $this->validate([
                    'baselinkerConfig.api_token' => 'required|min:32',
                    'baselinkerConfig.inventory_id' => 'required|integer',
                ]);
                break;
            case 'subiekt_gt':
                $this->validate([
                    'subiektConfig.rest_api_url' => 'required|url',
                    'subiektConfig.rest_api_key' => 'required|min:32',
                    'subiektConfig.rest_api_timeout' => 'required|integer|min:5|max:120',
                ]);
                break;
            case 'dynamics':
                $this->validate([
                    'dynamicsConfig.tenant_id' => 'required',
                    'dynamicsConfig.client_id' => 'required',
                    'dynamicsConfig.client_secret' => 'required|min:10',
                    'dynamicsConfig.odata_url' => 'required|url',
                ]);
                break;
        }
    }

    /**
     * Test authentication for ERP system.
     */
    public function testAuthentication()
    {
        $this->testingConnection = true;
        $this->authTestResult = null;

        try {
            $service = $this->getERPService($this->connectionForm['erp_type']);
            $config = $this->buildConnectionConfig();
            
            $result = $service->testAuthentication($config);

            $this->authTestResult = [
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'] ?? [],
                'auth_expires_at' => $result['auth_expires_at'] ?? null,
                'supported_features' => $result['supported_features'] ?? [],
            ];

            if ($result['success']) {
                session()->flash('success', 'Uwierzytelnienie z systemem ERP zostało pomyślnie przetestowane!');

                // Extract and store ERP warehouses for mapping
                if (!empty($result['details']['warehouses'])) {
                    $this->availableErpWarehouses = collect($result['details']['warehouses'])
                        ->map(fn($w) => [
                            'id' => $w['mag_Id'] ?? $w['id'] ?? 0,
                            'name' => $w['mag_Nazwa'] ?? $w['name'] ?? 'Unknown',
                            'symbol' => $w['mag_Symbol'] ?? $w['symbol'] ?? '',
                        ])
                        ->toArray();
                }

                // Extract and store ERP price levels for mapping
                if (!empty($result['details']['price_types'])) {
                    $this->availableErpPriceLevels = collect($result['details']['price_types'])
                        ->map(fn($p) => [
                            'id' => $p['rc_Id'] ?? $p['id'] ?? 0,
                            'name' => $p['rc_Nazwa'] ?? $p['name'] ?? 'Unknown',
                            'description' => $p['rc_Opis'] ?? $p['description'] ?? '',
                        ])
                        ->toArray();
                }

                // Load PPM data for mapping dropdowns
                $this->loadPpmMappingData();
                $this->loadMappingSummary();
            } else {
                session()->flash('error', 'Test uwierzytelnienia nieudany: ' . $result['message']);
            }

        } catch (\Exception $e) {
            $this->authTestResult = [
                'success' => false,
                'message' => 'Błąd testowania uwierzytelnienia: ' . $e->getMessage(),
                'details' => [],
            ];
            
            session()->flash('error', 'Błąd podczas testowania uwierzytelnienia');
        }

        $this->testingConnection = false;
    }

    /**
     * Complete wizard and save connection (create or update).
     */
    public function completeWizard()
    {
        $this->validate();

        try {
            $connectionData = $this->connectionForm;
            $connectionData['connection_config'] = $this->buildConnectionConfig();

            // Ensure description is null if empty (not empty string)
            if (empty($connectionData['description'])) {
                $connectionData['description'] = null;
            }

            // Add authentication results if available
            if ($this->authTestResult && $this->authTestResult['success']) {
                $connectionData['auth_status'] = ERPConnection::AUTH_AUTHENTICATED;
                $connectionData['connection_status'] = ERPConnection::CONNECTION_CONNECTED;
                $connectionData['last_auth_at'] = now();
                $connectionData['last_health_check'] = now();

                if (isset($this->authTestResult['auth_expires_at'])) {
                    $connectionData['auth_expires_at'] = $this->authTestResult['auth_expires_at'];
                }
            } else if (!$this->editingConnectionId) {
                // Only set pending status for new connections
                $connectionData['auth_status'] = ERPConnection::AUTH_PENDING;
                $connectionData['connection_status'] = ERPConnection::CONNECTION_DISCONNECTED;
            }

            // UPDATE existing connection
            if ($this->editingConnectionId) {
                $connection = ERPConnection::findOrFail($this->editingConnectionId);

                \Log::info('ERPManager::completeWizard updating connection', [
                    'connection_id' => $this->editingConnectionId,
                    'erp_type' => $connectionData['erp_type'] ?? 'missing',
                    'instance_name' => $connectionData['instance_name'] ?? 'missing',
                    'warehouse_mappings_count' => count($this->warehouseMappings),
                    'price_group_mappings_count' => count($this->priceGroupMappings),
                ]);

                $connection->update($connectionData);

                // AUTO-SAVE mappings to PPM models (Warehouse, PriceGroup)
                // This replaces the separate "Zapisz mapowania" button
                $this->saveMappingsToModelsInternal();

                session()->flash('success', "Połączenie '{$connection->instance_name}' zostało zaktualizowane wraz z mapowaniami!");

                $this->closeWizard();
                $this->dispatch('connectionUpdated', $connection->id);
            }
            // CREATE new connection
            else {
                \Log::info('ERPManager::completeWizard creating connection', [
                    'data_keys' => array_keys($connectionData),
                    'erp_type' => $connectionData['erp_type'] ?? 'missing',
                    'instance_name' => $connectionData['instance_name'] ?? 'missing',
                ]);

                $connection = ERPConnection::create($connectionData);

                session()->flash('success', 'Połączenie ERP zostało dodane pomyślnie!');

                $this->closeWizard();
                $this->dispatch('connectionCreated', $connection->id);
            }

        } catch (\Exception $e) {
            \Log::error('ERPManager::completeWizard ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $action = $this->editingConnectionId ? 'aktualizacji' : 'dodawania';
            session()->flash('error', "Błąd podczas {$action} połączenia ERP: " . $e->getMessage());
        }
    }

    /**
     * Build connection configuration array.
     * INCLUDES warehouse and price group mappings for persistence!
     */
    protected function buildConnectionConfig(): array
    {
        switch ($this->connectionForm['erp_type']) {
            case 'baselinker':
                return $this->baselinkerConfig;
            case 'subiekt_gt':
                // Force SSL verification off (sapi.mpptrade.pl certificate issue)
                // CRITICAL: Include mappings for persistence!
                return array_merge($this->subiektConfig, [
                    'rest_api_verify_ssl' => false,
                    'warehouse_mappings' => $this->warehouseMappings,
                    'price_group_mappings' => $this->priceGroupMappings,
                ]);
            case 'dynamics':
                return $this->dynamicsConfig;
            default:
                return [];
        }
    }

    /**
     * Get ERP service instance.
     */
    protected function getERPService(string $erpType)
    {
        switch ($erpType) {
            case 'baselinker':
                return app(BaselinkerService::class);
            case 'subiekt_gt':
                return app(SubiektGTService::class);
            case 'dynamics':
                return app(DynamicsService::class);
            default:
                throw new \Exception('Unsupported ERP type: ' . $erpType);
        }
    }

    /**
     * Close wizard.
     */
    public function closeWizard()
    {
        $this->showAddConnection = false;
        $this->editingConnectionId = null;
        $this->resetWizard();
    }

    /**
     * Test connection for existing ERP.
     */
    public function testConnection($connectionId)
    {
        // DEVELOPMENT: $this->authorize('admin.erp.test');

        $connection = ERPConnection::findOrFail($connectionId);
        $this->testingConnection = true;

        try {
            $service = $this->getERPService($connection->erp_type);
            $result = $service->testConnection($connection->connection_config);

            $connection->updateConnectionHealth(
                $result['success'] ? ERPConnection::CONNECTION_CONNECTED : ERPConnection::CONNECTION_ERROR,
                $result['response_time'] ?? null,
                $result['success'] ? null : $result['message']
            );

            if ($result['success']) {
                session()->flash('success', "Test połączenia z '{$connection->instance_name}' pomyślny!");
            } else {
                session()->flash('error', "Test połączenia z '{$connection->instance_name}' nieudany: " . $result['message']);
            }

        } catch (\Exception $e) {
            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                'Błąd testowania połączenia: ' . $e->getMessage()
            );
            
            session()->flash('error', 'Błąd podczas testowania połączenia');
        }

        $this->testingConnection = false;
    }

    /**
     * Trigger manual sync for ERP connection.
     */
    public function syncERP($connectionId)
    {
        // DEVELOPMENT: $this->authorize('admin.erp.sync');

        $connection = ERPConnection::findOrFail($connectionId);
        $this->syncingERP = true;

        try {
            $syncJob = SyncJob::create([
                'job_id' => \Str::uuid(),
                'job_type' => SyncJob::JOB_PRODUCT_SYNC,
                'job_name' => "Synchronizacja ERP: {$connection->instance_name}",
                'source_type' => SyncJob::TYPE_PPM,
                'target_type' => $connection->erp_type,
                'target_id' => $connection->id,
                'trigger_type' => SyncJob::TRIGGER_MANUAL,
                'user_id' => auth()->id(),
                'scheduled_at' => now(),
                'job_config' => [
                    'connection_id' => $connection->id,
                    'sync_type' => 'full',
                ],
            ]);

            // Dispatch appropriate job based on ERP type
            $jobClass = $this->getERPJobClass($connection->erp_type);
            $jobClass::dispatch($syncJob);

            session()->flash('success', "Synchronizacja ERP '{$connection->instance_name}' została uruchomiona!");

            $this->dispatch('syncStarted', $syncJob->job_id);

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas uruchamiania synchronizacji: ' . $e->getMessage());
        }

        $this->syncingERP = false;
    }

    /**
     * Get ERP job class.
     */
    protected function getERPJobClass(string $erpType): string
    {
        switch ($erpType) {
            case 'baselinker':
                return \App\Jobs\ERP\BaselinkerSyncJob::class;
            case 'subiekt_gt':
                return \App\Jobs\ERP\SubiektGTSyncJob::class;
            case 'dynamics':
                return \App\Jobs\ERP\DynamicsSyncJob::class;
            default:
                throw new \Exception('No job class defined for ERP type: ' . $erpType);
        }
    }

    /**
     * Toggle ERP connection active status.
     */
    public function toggleConnectionStatus($connectionId)
    {
        // DEVELOPMENT: $this->authorize('admin.erp.edit');

        $connection = ERPConnection::findOrFail($connectionId);
        $connection->is_active = !$connection->is_active;
        $connection->save();

        $status = $connection->is_active ? 'aktywne' : 'nieaktywne';
        session()->flash('success', "Połączenie '{$connection->instance_name}' jest teraz {$status}.");
    }

    /**
     * Delete ERP connection.
     */
    public function deleteConnection($connectionId)
    {
        // DEVELOPMENT: $this->authorize('admin.erp.delete');

        $connection = ERPConnection::findOrFail($connectionId);
        $connectionName = $connection->instance_name;
        
        // Check for active sync jobs
        $activeSyncJobs = SyncJob::where('target_type', $connection->erp_type)
            ->where('target_id', $connectionId)
            ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
            ->count();

        if ($activeSyncJobs > 0) {
            session()->flash('error', "Nie można usunąć połączenia '{$connectionName}' - trwają aktywne synchronizacje.");
            return;
        }

        $connection->delete();
        
        session()->flash('success', "Połączenie ERP '{$connectionName}' zostało usunięte.");
    }

    /**
     * Show connection details modal.
     */
    public function showDetails($connectionId)
    {
        $this->selectedConnection = ERPConnection::with(['syncJobs' => function($q) {
            $q->latest()->take(5);
        }])->findOrFail($connectionId);
        
        $this->showConnectionDetails = true;
    }

    /**
     * Close connection details modal.
     */
    public function closeDetails()
    {
        $this->showConnectionDetails = false;
        $this->selectedConnection = null;
    }

    /**
     * Edit existing connection - load data and open wizard.
     *
     * @param int $connectionId
     */
    public function editConnection(int $connectionId): void
    {
        $connection = ERPConnection::findOrFail($connectionId);

        // Store editing connection ID
        $this->editingConnectionId = $connectionId;

        // Load connection data into form
        $this->connectionForm = [
            'erp_type' => $connection->erp_type,
            'instance_name' => $connection->instance_name,
            'description' => $connection->description,
            'is_active' => $connection->is_active,
            'priority' => $connection->priority,
            'connection_config' => $connection->connection_config ?? [],
            'sync_mode' => $connection->sync_mode,
            'auto_sync_products' => $connection->auto_sync_products,
            'auto_sync_stock' => $connection->auto_sync_stock,
            'auto_sync_prices' => $connection->auto_sync_prices,
            'auto_sync_orders' => $connection->auto_sync_orders,
            'max_retry_attempts' => $connection->max_retry_attempts,
            'retry_delay_seconds' => $connection->retry_delay_seconds,
            'auto_disable_on_errors' => $connection->auto_disable_on_errors ?? false,
            'error_threshold' => $connection->error_threshold ?? 10,
            'webhook_enabled' => $connection->webhook_enabled ?? false,
            'notify_on_errors' => $connection->notify_on_errors,
            'notify_on_sync_complete' => $connection->notify_on_sync_complete ?? false,
            'notify_on_auth_expire' => $connection->notify_on_auth_expire,
        ];

        // Load ERP-specific config
        $config = $connection->connection_config ?? [];

        switch ($connection->erp_type) {
            case 'baselinker':
                $this->baselinkerConfig = array_merge($this->baselinkerConfig, [
                    'api_token' => $config['api_token'] ?? '',
                    'inventory_id' => $config['inventory_id'] ?? '',
                    'warehouse_mappings' => $config['warehouse_mappings'] ?? [],
                    // ETAP_08 FAZA 8: Warehouse Location Settings
                    'default_location' => $config['default_location'] ?? '',
                    'copy_location_to_all' => $config['copy_location_to_all'] ?? false,
                ]);
                break;

            case 'subiekt_gt':
                $this->subiektConfig = array_merge($this->subiektConfig, [
                    'connection_mode' => $config['connection_mode'] ?? 'rest_api',
                    'rest_api_url' => $config['rest_api_url'] ?? 'https://sapi.mpptrade.pl',
                    'rest_api_key' => $config['rest_api_key'] ?? '',
                    'rest_api_timeout' => $config['rest_api_timeout'] ?? 30,
                    'rest_api_verify_ssl' => $config['rest_api_verify_ssl'] ?? false,
                    'default_warehouse_id' => $config['default_warehouse_id'] ?? 1,
                    'default_price_type_id' => $config['default_price_type_id'] ?? 1,
                    'warehouse_mappings' => $config['warehouse_mappings'] ?? [],
                    'price_group_mappings' => $config['price_group_mappings'] ?? [],
                    'create_missing_products' => $config['create_missing_products'] ?? false,
                    // ETAP_08 FAZA 8: Warehouse Location Settings
                    'default_location' => $config['default_location'] ?? '',
                    'copy_location_to_all' => $config['copy_location_to_all'] ?? false,
                ]);
                break;

            case 'dynamics':
                $this->dynamicsConfig = array_merge($this->dynamicsConfig, [
                    'tenant_id' => $config['tenant_id'] ?? '',
                    'client_id' => $config['client_id'] ?? '',
                    'client_secret' => $config['client_secret'] ?? '',
                    'odata_url' => $config['odata_url'] ?? '',
                    'company_id' => $config['company_id'] ?? '',
                    'entity_mappings' => $config['entity_mappings'] ?? [],
                ]);
                break;
        }

        // Set wizard to Step 4 (Mappings) for quick access to settings
        $this->wizardStep = 4;
        $this->authTestResult = [
            'success' => $connection->connection_status === ERPConnection::CONNECTION_CONNECTED,
            'message' => 'Using existing connection data',
            'details' => [],
        ];

        // Load PPM warehouses and price groups for mapping dropdowns
        $this->loadPpmMappingData();

        // Load ERP warehouses and price levels from saved config or fetch fresh
        $this->loadErpMappingDataForEdit($connection);

        // Load existing mappings from connection_config
        // Format: ERP_ID => PPM_ID
        $this->warehouseMappings = $config['warehouse_mappings'] ?? [];
        $this->priceGroupMappings = $config['price_group_mappings'] ?? [];

        // If no mappings in config, try to reconstruct from PPM models (erp_mapping field)
        if (empty($this->warehouseMappings)) {
            $this->reconstructMappingsFromModels($connection->erp_type);
        }

        // Calculate mapping summary
        $this->loadMappingSummary();

        // Open wizard
        $this->showAddConnection = true;
    }

    /**
     * Load ERP warehouse and price level data for edit mode.
     *
     * @param ERPConnection $connection
     */
    protected function loadErpMappingDataForEdit(ERPConnection $connection): void
    {
        try {
            $service = $this->getERPService($connection->erp_type);
            $config = $connection->connection_config ?? [];

            // Test connection and get fresh data
            $result = $service->testAuthentication($config);

            if ($result['success']) {
                // Store in authTestResult for UI
                $this->authTestResult['details'] = $result['details'] ?? [];

                // Extract warehouses
                if (!empty($result['details']['warehouses'])) {
                    $this->availableErpWarehouses = collect($result['details']['warehouses'])
                        ->map(fn($w) => [
                            'id' => $w['mag_Id'] ?? $w['id'] ?? 0,
                            'name' => $w['mag_Nazwa'] ?? $w['name'] ?? 'Unknown',
                            'symbol' => $w['mag_Symbol'] ?? $w['symbol'] ?? '',
                        ])
                        ->toArray();
                }

                // Extract price levels
                if (!empty($result['details']['price_types'])) {
                    $this->availableErpPriceLevels = collect($result['details']['price_types'])
                        ->map(fn($p) => [
                            'id' => $p['rc_Id'] ?? $p['id'] ?? 0,
                            'name' => $p['rc_Nazwa'] ?? $p['name'] ?? 'Unknown',
                            'description' => $p['rc_Opis'] ?? $p['description'] ?? '',
                        ])
                        ->toArray();
                }
            }
        } catch (\Exception $e) {
            \Log::warning('ERPManager::loadErpMappingDataForEdit failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            // Use empty arrays - user can still edit other settings
            $this->availableErpWarehouses = [];
            $this->availableErpPriceLevels = [];
        }
    }

    /**
     * Update search query and reset pagination.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Update ERP type filter and reset pagination.
     */
    public function updatedErpTypeFilter()
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
     * Sort connections by column.
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
        $this->erpTypeFilter = 'all';
        $this->statusFilter = 'all';
        $this->sortBy = 'priority';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Handle sync completed event.
     */
    public function handleSyncCompleted($jobId)
    {
        session()->flash('success', 'Synchronizacja ERP została ukończona!');
        $this->dispatch('refreshConnections');
    }

    /*
    |--------------------------------------------------------------------------
    | MAPPING MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Load PPM warehouses and price groups for mapping selection.
     */
    public function loadPpmMappingData(): void
    {
        $this->ppmWarehouses = \App\Models\Warehouse::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->toArray();

        $this->ppmPriceGroups = \App\Models\PriceGroup::active()
            ->ordered()
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    /**
     * Reconstruct mappings from PPM models' erp_mapping field.
     * Used when connection_config doesn't have mappings (backward compatibility).
     *
     * @param string $erpType
     */
    protected function reconstructMappingsFromModels(string $erpType): void
    {
        // Reconstruct warehouse mappings from Warehouse.erp_mapping
        $warehouses = \App\Models\Warehouse::whereNotNull("erp_mapping->{$erpType}")->get();
        foreach ($warehouses as $warehouse) {
            $mapping = $warehouse->erp_mapping[$erpType] ?? null;
            if ($mapping && isset($mapping['id'])) {
                $this->warehouseMappings[$mapping['id']] = $warehouse->id;
            }
        }

        // Reconstruct price group mappings from PriceGroup.erp_mapping
        $priceGroups = \App\Models\PriceGroup::whereNotNull("erp_mapping->{$erpType}")->get();
        foreach ($priceGroups as $priceGroup) {
            $mapping = $priceGroup->erp_mapping[$erpType] ?? null;
            if ($mapping && isset($mapping['id'])) {
                $this->priceGroupMappings[$mapping['id']] = $priceGroup->id;
            }
        }

        \Log::debug('Reconstructed mappings from models', [
            'erp_type' => $erpType,
            'warehouses' => count($this->warehouseMappings),
            'price_groups' => count($this->priceGroupMappings),
        ]);
    }

    /**
     * Load and calculate mapping summary statistics.
     */
    public function loadMappingSummary(): void
    {
        $mappedWarehouses = count(array_filter($this->warehouseMappings));
        $totalErpWarehouses = count($this->availableErpWarehouses);

        $mappedPriceGroups = count(array_filter($this->priceGroupMappings));
        $totalErpPriceGroups = count($this->availableErpPriceLevels);

        $this->mappingSummary = [
            'warehouses' => [
                'mapped' => $mappedWarehouses,
                'total' => $totalErpWarehouses,
                'percentage' => $totalErpWarehouses > 0
                    ? round(($mappedWarehouses / $totalErpWarehouses) * 100)
                    : 0,
            ],
            'price_groups' => [
                'mapped' => $mappedPriceGroups,
                'total' => $totalErpPriceGroups,
                'percentage' => $totalErpPriceGroups > 0
                    ? round(($mappedPriceGroups / $totalErpPriceGroups) * 100)
                    : 0,
            ],
        ];
    }

    /**
     * Create warehouse in PPM from ERP data and auto-assign mapping.
     *
     * @param int $erpWarehouseId ERP warehouse ID
     */
    public function createWarehouseFromErp(int $erpWarehouseId): void
    {
        $erpWarehouse = collect($this->availableErpWarehouses)
            ->firstWhere('id', $erpWarehouseId);

        if (!$erpWarehouse) {
            $this->addError('warehouse', 'Nie znaleziono magazynu ERP');
            return;
        }

        $erpType = $this->connectionForm['erp_type'];
        $connectionId = $this->editingConnectionId ?? 0;

        $warehouse = \App\Models\Warehouse::createFromErpData(
            $erpType,
            $erpWarehouse,
            $connectionId
        );

        // Refresh PPM warehouses list
        $this->loadPpmMappingData();

        // Auto-assign mapping
        $this->warehouseMappings[$erpWarehouseId] = $warehouse->id;

        $this->loadMappingSummary();

        $this->dispatch('notify', type: 'success', message: "Utworzono magazyn: {$warehouse->name}");
    }

    /**
     * Create price group in PPM from ERP data and auto-assign mapping.
     *
     * @param int $erpPriceLevelId ERP price level ID
     */
    public function createPriceGroupFromErp(int $erpPriceLevelId): void
    {
        $erpPriceLevel = collect($this->availableErpPriceLevels)
            ->firstWhere('id', $erpPriceLevelId);

        if (!$erpPriceLevel) {
            $this->addError('priceGroup', 'Nie znaleziono poziomu cenowego ERP');
            return;
        }

        $erpType = $this->connectionForm['erp_type'];
        $connectionId = $this->editingConnectionId ?? 0;

        $priceGroup = \App\Models\PriceGroup::createFromErpData(
            $erpType,
            $erpPriceLevel,
            $connectionId
        );

        // Refresh PPM price groups list
        $this->loadPpmMappingData();

        // Auto-assign mapping
        $this->priceGroupMappings[$erpPriceLevelId] = $priceGroup->id;

        $this->loadMappingSummary();

        $this->dispatch('notify', type: 'success', message: "Utworzono grupe cenowa: {$priceGroup->name}");
    }

    /**
     * Save mappings to PPM models (Warehouse and PriceGroup).
     * Called by user button click - shows notification.
     */
    public function saveMappingsToModels(): void
    {
        $this->saveMappingsToModelsInternal();
        $this->dispatch('notify', type: 'success', message: 'Mapowania zostaly zapisane');
    }

    /**
     * Internal method to save mappings without notification.
     * Called automatically by completeWizard().
     */
    protected function saveMappingsToModelsInternal(): void
    {
        $erpType = $this->connectionForm['erp_type'];

        // Clear existing mappings if requested
        if ($this->clearExistingMappingsOnSave) {
            \App\Models\Warehouse::whereNotNull('erp_mapping->' . $erpType)
                ->each(fn($w) => $w->clearErpMapping($erpType));

            \App\Models\PriceGroup::whereNotNull('erp_mapping->' . $erpType)
                ->each(fn($pg) => $pg->clearErpMapping($erpType));

            $this->clearExistingMappingsOnSave = false;
        }

        // Save warehouse mappings
        foreach ($this->warehouseMappings as $erpWarehouseId => $ppmWarehouseId) {
            if (!$ppmWarehouseId) {
                continue;
            }

            $warehouse = \App\Models\Warehouse::find($ppmWarehouseId);
            if (!$warehouse) {
                continue;
            }

            $erpWarehouse = collect($this->availableErpWarehouses)
                ->firstWhere('id', $erpWarehouseId);

            if ($erpWarehouse) {
                $warehouse->setErpMapping($erpType, [
                    'id' => $erpWarehouseId,
                    'name' => $erpWarehouse['name'] ?? null,
                    'symbol' => $erpWarehouse['symbol'] ?? null,
                    'connection_id' => $this->editingConnectionId ?? 0,
                    'synced_at' => now()->toIso8601String(),
                ]);
            }
        }

        // Save price group mappings
        foreach ($this->priceGroupMappings as $erpPriceLevelId => $ppmPriceGroupId) {
            if (!$ppmPriceGroupId) {
                continue;
            }

            $priceGroup = \App\Models\PriceGroup::find($ppmPriceGroupId);
            if (!$priceGroup) {
                continue;
            }

            $erpPriceLevel = collect($this->availableErpPriceLevels)
                ->firstWhere('id', $erpPriceLevelId);

            if ($erpPriceLevel) {
                $priceGroup->setErpMapping($erpType, [
                    'id' => $erpPriceLevelId,
                    'name' => $erpPriceLevel['name'] ?? null,
                    'description' => $erpPriceLevel['description'] ?? null,
                    'connection_id' => $this->editingConnectionId ?? 0,
                    'synced_at' => now()->toIso8601String(),
                ]);
            }
        }

        \Log::info('Mappings saved to models', [
            'erp_type' => $erpType,
            'warehouses_mapped' => count(array_filter($this->warehouseMappings)),
            'price_groups_mapped' => count(array_filter($this->priceGroupMappings)),
        ]);
    }

    // =========================================================================
    // FAZA B: REPLACE EXISTING FEATURE
    // =========================================================================

    /**
     * Replace existing PPM warehouse data with ERP data.
     * Updates name and symbol from ERP, maintaining PPM ID.
     *
     * @param int $ppmWarehouseId PPM warehouse to replace
     * @param int $erpWarehouseId Source ERP warehouse
     */
    public function replaceWarehouseWithErp(int $ppmWarehouseId, int $erpWarehouseId): void
    {
        $warehouse = \App\Models\Warehouse::find($ppmWarehouseId);
        if (!$warehouse) {
            $this->addError('replace', 'Magazyn PPM nie zostal znaleziony');
            return;
        }

        $erpWarehouse = collect($this->availableErpWarehouses)
            ->firstWhere('id', $erpWarehouseId);

        if (!$erpWarehouse) {
            $this->addError('replace', 'Magazyn ERP nie zostal znaleziony');
            return;
        }

        // Check for related products
        $relatedProducts = $warehouse->stock()->count();
        if ($relatedProducts > 0) {
            \Log::info('Replacing warehouse with products', [
                'warehouse_id' => $ppmWarehouseId,
                'related_products' => $relatedProducts,
            ]);
        }

        $erpType = $this->connectionForm['erp_type'];

        // Update warehouse with ERP data
        $warehouse->update([
            'name' => $erpWarehouse['name'] ?? $warehouse->name,
            'code' => $erpWarehouse['symbol'] ?? $warehouse->code,
        ]);

        // Set ERP mapping
        $warehouse->setErpMapping($erpType, [
            'id' => $erpWarehouseId,
            'name' => $erpWarehouse['name'] ?? null,
            'symbol' => $erpWarehouse['symbol'] ?? null,
            'connection_id' => $this->editingConnectionId ?? 0,
            'replaced_at' => now()->toIso8601String(),
            'synced_at' => now()->toIso8601String(),
        ]);

        // Update mapping
        $this->warehouseMappings[$erpWarehouseId] = $ppmWarehouseId;

        $this->loadPpmMappingData();
        $this->loadMappingSummary();

        $this->dispatch('notify', type: 'success', message: "Magazyn '{$warehouse->name}' zostal zaktualizowany danymi z ERP");
    }

    /**
     * Replace existing PPM price group data with ERP data.
     * Updates name from ERP, maintaining PPM ID.
     *
     * @param int $ppmPriceGroupId PPM price group to replace
     * @param int $erpPriceLevelId Source ERP price level
     */
    public function replacePriceGroupWithErp(int $ppmPriceGroupId, int $erpPriceLevelId): void
    {
        $priceGroup = \App\Models\PriceGroup::find($ppmPriceGroupId);
        if (!$priceGroup) {
            $this->addError('replace', 'Grupa cenowa PPM nie zostala znaleziona');
            return;
        }

        $erpPriceLevel = collect($this->availableErpPriceLevels)
            ->firstWhere('id', $erpPriceLevelId);

        if (!$erpPriceLevel) {
            $this->addError('replace', 'Poziom cenowy ERP nie zostal znaleziony');
            return;
        }

        // Check for related prices
        $relatedPrices = $priceGroup->prices()->count();
        if ($relatedPrices > 0) {
            \Log::info('Replacing price group with prices', [
                'price_group_id' => $ppmPriceGroupId,
                'related_prices' => $relatedPrices,
            ]);
        }

        $erpType = $this->connectionForm['erp_type'];

        // Update price group with ERP data
        $priceGroup->update([
            'name' => $erpPriceLevel['name'] ?? $priceGroup->name,
            'description' => $erpPriceLevel['description'] ?? "Zaktualizowano z ERP: {$erpType}",
        ]);

        // Set ERP mapping
        $priceGroup->setErpMapping($erpType, [
            'id' => $erpPriceLevelId,
            'name' => $erpPriceLevel['name'] ?? null,
            'description' => $erpPriceLevel['description'] ?? null,
            'connection_id' => $this->editingConnectionId ?? 0,
            'replaced_at' => now()->toIso8601String(),
            'synced_at' => now()->toIso8601String(),
        ]);

        // Update mapping
        $this->priceGroupMappings[$erpPriceLevelId] = $ppmPriceGroupId;

        $this->loadPpmMappingData();
        $this->loadMappingSummary();

        $this->dispatch('notify', type: 'success', message: "Grupa cenowa '{$priceGroup->name}' zostala zaktualizowana danymi z ERP");
    }

    // =========================================================================
    // FAZA C: BULK OPERATIONS
    // =========================================================================

    /**
     * Replace ALL existing PPM warehouses and price groups with ERP data.
     * This destructive operation:
     * 1. Clears all existing ERP mappings
     * 2. Deletes all PPM warehouses/price groups that came from this ERP
     * 3. Creates new ones from current ERP data
     *
     * REQUIRES: User confirmation via wire:confirm
     */
    /**
     * Open confirmation modal for Replace All operation.
     */
    public function openReplaceAllConfirmModal(): void
    {
        $this->showReplaceAllConfirmModal = true;
    }

    /**
     * Close confirmation modal for Replace All operation.
     */
    public function closeReplaceAllConfirmModal(): void
    {
        $this->showReplaceAllConfirmModal = false;
    }

    /**
     * Execute Replace All operation after user confirmation via modal.
     */
    public function replaceAllWithErp(): void
    {
        $this->showReplaceAllConfirmModal = false; // Close modal first
        $erpType = $this->connectionForm['erp_type'];
        $connectionId = $this->editingConnectionId ?? 0;

        \Log::info('replaceAllWithErp STARTED', [
            'erp_type' => $erpType,
            'connection_id' => $connectionId,
            'available_erp_warehouses' => count($this->availableErpWarehouses),
            'available_erp_price_levels' => count($this->availableErpPriceLevels),
        ]);

        $deletedWarehouses = 0;
        $deletedPriceGroups = 0;
        $createdWarehouses = 0;
        $createdPriceGroups = 0;

        \DB::beginTransaction();
        try {
            // 1. Delete ALL warehouses (not just those with ERP mapping!)
            $warehousesToDelete = \App\Models\Warehouse::all();

            \Log::info('Warehouses to delete (ALL)', [
                'count' => $warehousesToDelete->count(),
            ]);

            foreach ($warehousesToDelete as $warehouse) {
                // Force delete all related stock records first
                $deletedStocks = $warehouse->stock()->delete();
                if ($deletedStocks > 0) {
                    \Log::info('Deleted stock records for warehouse replacement', [
                        'warehouse_id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'deleted_stocks' => $deletedStocks,
                    ]);
                }

                // Now delete the warehouse
                $warehouse->forceDelete(); // Use forceDelete to bypass SoftDeletes
                $deletedWarehouses++;
            }

            // 2. Delete ALL price groups (not just those with ERP mapping!)
            $priceGroupsToDelete = \App\Models\PriceGroup::all();

            \Log::info('Price groups to delete (ALL)', [
                'count' => $priceGroupsToDelete->count(),
            ]);

            foreach ($priceGroupsToDelete as $priceGroup) {
                // Force delete all related price records first
                $deletedPrices = $priceGroup->prices()->delete();
                if ($deletedPrices > 0) {
                    \Log::info('Deleted price records for price group replacement', [
                        'price_group_id' => $priceGroup->id,
                        'name' => $priceGroup->name,
                        'deleted_prices' => $deletedPrices,
                    ]);
                }

                // Now delete the price group
                $priceGroup->forceDelete(); // Use forceDelete to bypass SoftDeletes
                $deletedPriceGroups++;
            }

            // 3. Clear component mapping state
            $this->warehouseMappings = [];
            $this->priceGroupMappings = [];

            // 4. Create all warehouses from ERP
            foreach ($this->availableErpWarehouses as $erpWarehouse) {
                $warehouse = \App\Models\Warehouse::createFromErpData(
                    $erpType,
                    $erpWarehouse,
                    $connectionId
                );
                $this->warehouseMappings[$erpWarehouse['id']] = $warehouse->id;
                $createdWarehouses++;
            }

            // 5. Create all price groups from ERP
            foreach ($this->availableErpPriceLevels as $erpPriceLevel) {
                $priceGroup = \App\Models\PriceGroup::createFromErpData(
                    $erpType,
                    $erpPriceLevel,
                    $connectionId
                );
                $this->priceGroupMappings[$erpPriceLevel['id']] = $priceGroup->id;
                $createdPriceGroups++;
            }

            \DB::commit();

            \Log::info('replaceAllWithErp COMPLETED', [
                'deleted_warehouses' => $deletedWarehouses,
                'deleted_price_groups' => $deletedPriceGroups,
                'created_warehouses' => $createdWarehouses,
                'created_price_groups' => $createdPriceGroups,
            ]);

            // Refresh data
            $this->loadPpmMappingData();
            $this->loadMappingSummary();

            $this->dispatch('notify', type: 'success', message: "Zastąpiono dane ERP: usunięto {$deletedWarehouses} magazynów i {$deletedPriceGroups} grup cenowych, utworzono {$createdWarehouses} magazynów i {$createdPriceGroups} grup cenowych");

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('replaceAllWithErp failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Błąd podczas zastępowania danych: ' . $e->getMessage());
        }
    }

    /**
     * Create all missing warehouses from ERP in one operation.
     * Only creates warehouses that don't have a PPM mapping yet.
     */
    public function createAllMissingWarehousesFromErp(): void
    {
        $created = 0;
        $erpType = $this->connectionForm['erp_type'];
        $connectionId = $this->editingConnectionId ?? 0;

        foreach ($this->availableErpWarehouses as $erpWarehouse) {
            // Skip if already mapped
            if (!empty($this->warehouseMappings[$erpWarehouse['id']] ?? null)) {
                continue;
            }

            // Create new warehouse
            $warehouse = \App\Models\Warehouse::createFromErpData(
                $erpType,
                $erpWarehouse,
                $connectionId
            );

            // Auto-assign mapping
            $this->warehouseMappings[$erpWarehouse['id']] = $warehouse->id;
            $created++;
        }

        if ($created > 0) {
            $this->loadPpmMappingData();
            $this->loadMappingSummary();
            $this->dispatch('notify', type: 'success', message: "Utworzono {$created} nowych magazynow z ERP");
        } else {
            $this->dispatch('notify', type: 'info', message: 'Wszystkie magazyny ERP sa juz zmapowane');
        }
    }

    /**
     * Create all missing price groups from ERP in one operation.
     * Only creates price groups that don't have a PPM mapping yet.
     */
    public function createAllMissingPriceGroupsFromErp(): void
    {
        $created = 0;
        $erpType = $this->connectionForm['erp_type'];
        $connectionId = $this->editingConnectionId ?? 0;

        foreach ($this->availableErpPriceLevels as $erpPriceLevel) {
            // Skip if already mapped
            if (!empty($this->priceGroupMappings[$erpPriceLevel['id']] ?? null)) {
                continue;
            }

            // Create new price group
            $priceGroup = \App\Models\PriceGroup::createFromErpData(
                $erpType,
                $erpPriceLevel,
                $connectionId
            );

            // Auto-assign mapping
            $this->priceGroupMappings[$erpPriceLevel['id']] = $priceGroup->id;
            $created++;
        }

        if ($created > 0) {
            $this->loadPpmMappingData();
            $this->loadMappingSummary();
            $this->dispatch('notify', type: 'success', message: "Utworzono {$created} nowych grup cenowych z ERP");
        } else {
            $this->dispatch('notify', type: 'info', message: 'Wszystkie poziomy cenowe ERP sa juz zmapowane');
        }
    }

    /**
     * Auto-map ERP entities to PPM by name similarity.
     * Uses Levenshtein distance to find best matches.
     */
    public function autoMapByName(): void
    {
        $warehousesMapped = 0;
        $priceGroupsMapped = 0;

        // Auto-map warehouses
        foreach ($this->availableErpWarehouses as $erpWarehouse) {
            // Skip if already mapped
            if (!empty($this->warehouseMappings[$erpWarehouse['id']] ?? null)) {
                continue;
            }

            $erpName = strtolower($erpWarehouse['name'] ?? '');
            $erpSymbol = strtolower($erpWarehouse['symbol'] ?? '');

            $bestMatch = null;
            $bestScore = PHP_INT_MAX;

            foreach ($this->ppmWarehouses as $ppmWarehouse) {
                $ppmName = strtolower($ppmWarehouse['name'] ?? '');
                $ppmCode = strtolower($ppmWarehouse['code'] ?? '');

                // Calculate similarity scores
                $nameScore = levenshtein($erpName, $ppmName);
                $symbolScore = $erpSymbol && $ppmCode ? levenshtein($erpSymbol, $ppmCode) : PHP_INT_MAX;
                $score = min($nameScore, $symbolScore);

                // Consider exact matches or very close matches (score <= 3)
                if ($score < $bestScore && $score <= 5) {
                    $bestScore = $score;
                    $bestMatch = $ppmWarehouse;
                }
            }

            if ($bestMatch) {
                $this->warehouseMappings[$erpWarehouse['id']] = $bestMatch['id'];
                $warehousesMapped++;
            }
        }

        // Auto-map price groups
        foreach ($this->availableErpPriceLevels as $erpPriceLevel) {
            // Skip if already mapped
            if (!empty($this->priceGroupMappings[$erpPriceLevel['id']] ?? null)) {
                continue;
            }

            $erpName = strtolower($erpPriceLevel['name'] ?? '');

            $bestMatch = null;
            $bestScore = PHP_INT_MAX;

            foreach ($this->ppmPriceGroups as $ppmPriceGroup) {
                $ppmName = strtolower($ppmPriceGroup['name'] ?? '');

                $score = levenshtein($erpName, $ppmName);

                // Consider exact matches or very close matches (score <= 5)
                if ($score < $bestScore && $score <= 5) {
                    $bestScore = $score;
                    $bestMatch = $ppmPriceGroup;
                }
            }

            if ($bestMatch) {
                $this->priceGroupMappings[$erpPriceLevel['id']] = $bestMatch['id'];
                $priceGroupsMapped++;
            }
        }

        $this->loadMappingSummary();

        if ($warehousesMapped > 0 || $priceGroupsMapped > 0) {
            $this->dispatch('notify', type: 'success', message: "Auto-mapowanie: {$warehousesMapped} magazynow, {$priceGroupsMapped} grup cenowych");
        } else {
            $this->dispatch('notify', type: 'info', message: 'Nie znaleziono pasujacych nazw do auto-mapowania');
        }
    }
}