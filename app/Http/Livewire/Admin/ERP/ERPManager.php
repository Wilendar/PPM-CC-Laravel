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

    // Filters and Search
    public $search = '';
    public $erpTypeFilter = 'all';
    public $statusFilter = 'all';
    public $sortBy = 'priority';
    public $sortDirection = 'asc';

    // Add/Edit ERP Form
    public $connectionForm = [
        'erp_type' => 'baselinker',
        'instance_name' => '',
        'description' => '',
        'priority' => 1,
        'connection_config' => [],
        'sync_mode' => 'bidirectional',
        'auto_sync_products' => true,
        'auto_sync_stock' => true,
        'auto_sync_prices' => true,
        'auto_sync_orders' => false,
        'max_retry_attempts' => 3,
        'retry_delay_seconds' => 60,
        'webhook_enabled' => false,
        'notify_on_errors' => true,
        'notify_on_auth_expire' => true,
    ];

    // ERP-specific configuration forms
    public $baselinkerConfig = [
        'api_token' => '',
        'inventory_id' => '',
        'warehouse_mappings' => [],
    ];

    public $subiektConfig = [
        'dll_path' => '',
        'database_name' => '',
        'server' => 'localhost',
        'username' => '',
        'password' => '',
        'data_mappings' => [],
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
                $rules['subiektConfig.dll_path'] = 'required';
                $rules['subiektConfig.database_name'] = 'required';
                $rules['subiektConfig.server'] = 'required';
                $rules['subiektConfig.username'] = 'required';
                $rules['subiektConfig.password'] = 'required';
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
        $this->authorize('admin.erp.view');
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
        $this->authorize('admin.erp.create');
        
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
            'description' => '',
            'priority' => $this->getNextPriority(),
            'connection_config' => [],
            'sync_mode' => 'bidirectional',
            'auto_sync_products' => true,
            'auto_sync_stock' => true,
            'auto_sync_prices' => true,
            'auto_sync_orders' => false,
            'max_retry_attempts' => 3,
            'retry_delay_seconds' => 60,
            'webhook_enabled' => false,
            'notify_on_errors' => true,
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
        ];

        $this->subiektConfig = [
            'dll_path' => '',
            'database_name' => '',
            'server' => 'localhost',
            'username' => '',
            'password' => '',
            'data_mappings' => [],
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
                    'subiektConfig.dll_path' => 'required',
                    'subiektConfig.database_name' => 'required',
                    'subiektConfig.server' => 'required',
                    'subiektConfig.username' => 'required',
                    'subiektConfig.password' => 'required',
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
     * Complete wizard and save connection.
     */
    public function completeWizard()
    {
        $this->validate();
        
        try {
            $connectionData = $this->connectionForm;
            $connectionData['connection_config'] = $this->buildConnectionConfig();
            
            // Add authentication results if available
            if ($this->authTestResult && $this->authTestResult['success']) {
                $connectionData['auth_status'] = ERPConnection::AUTH_AUTHENTICATED;
                $connectionData['connection_status'] = ERPConnection::CONNECTION_CONNECTED;
                $connectionData['last_auth_at'] = now();
                $connectionData['last_health_check'] = now();
                
                if (isset($this->authTestResult['auth_expires_at'])) {
                    $connectionData['auth_expires_at'] = $this->authTestResult['auth_expires_at'];
                }
            } else {
                $connectionData['auth_status'] = ERPConnection::AUTH_PENDING;
                $connectionData['connection_status'] = ERPConnection::CONNECTION_DISCONNECTED;
            }
            
            $connection = ERPConnection::create($connectionData);

            session()->flash('success', 'Połączenie ERP zostało dodane pomyślnie!');
            
            $this->closeWizard();
            $this->dispatch('connectionCreated', $connection->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas dodawania połączenia ERP: ' . $e->getMessage());
        }
    }

    /**
     * Build connection configuration array.
     */
    protected function buildConnectionConfig(): array
    {
        switch ($this->connectionForm['erp_type']) {
            case 'baselinker':
                return $this->baselinkerConfig;
            case 'subiekt_gt':
                return $this->subiektConfig;
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
        $this->resetWizard();
    }

    /**
     * Test connection for existing ERP.
     */
    public function testConnection($connectionId)
    {
        $this->authorize('admin.erp.test');
        
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
        $this->authorize('admin.erp.sync');
        
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
        $this->authorize('admin.erp.edit');
        
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
        $this->authorize('admin.erp.delete');
        
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
}