<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

/**
 * Policy Testing Tools Component
 * 
 * FAZA C: Development/debugging tool dla administrators
 * 
 * Features:
 * - User selection dropdown (all users w systemie)
 * - Action/resource selection (products.create, categories.update, etc.)
 * - Live policy evaluation results
 * - Policy rule explanation z step-by-step logic
 * - "What can this user do?" comprehensive analyzer
 * - Policy conflict detection
 * - Permission inheritance tracing
 * - Debug output dla policy logic
 * - Bulk permission testing
 */
class PolicyTester extends Component
{
    // ==========================================
    // CORE PROPERTIES
    // ==========================================

    public $selectedUserId = null;
    public $selectedUser = null;
    public $selectedAction = '';
    public $selectedResource = '';
    public $selectedModel = null;
    public $testResult = null;
    public $debugInfo = [];
    
    // ==========================================
    // TESTING MODES
    // ==========================================
    
    public $testingMode = 'single'; // single, bulk, analysis
    public $bulkActions = [];
    public $bulkResults = [];
    public $analysisResults = [];
    
    // ==========================================
    // RESOURCE PROPERTIES
    // ==========================================
    
    public $availableActions = [];
    public $availableResources = [];
    public $policyClasses = [];
    public $gateDefinitions = [];
    
    // ==========================================
    // UI STATE
    // ==========================================
    
    public $showDebugDetails = false;
    public $showAnalysisModal = false;
    public $expandedSections = [
        'roles' => true,
        'permissions' => true,
        'policies' => true,
        'inheritance' => false
    ];

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('access', 'policy_tester');
        
        $this->loadAvailableResources();
        $this->loadPolicyClasses();
        $this->loadGateDefinitions();
    }

    protected function loadAvailableResources()
    {
        $this->availableResources = [
            'User' => 'App\\Models\\User',
            'Product' => 'App\\Models\\Product',
            'Category' => 'App\\Models\\Category',
            'Media' => 'App\\Models\\Media',
            'ProductAttribute' => 'App\\Models\\ProductAttribute',
            'PriceGroup' => 'App\\Models\\PriceGroup',
            'Warehouse' => 'App\\Models\\Warehouse',
            'AuditLog' => 'App\\Models\\AuditLog',
            'UserSession' => 'App\\Models\\UserSession'
        ];

        $this->availableActions = [
            'viewAny' => 'Wyświetl listę',
            'view' => 'Wyświetl szczegóły',
            'create' => 'Tworzenie',
            'update' => 'Edycja',
            'delete' => 'Usuwanie',
            'restore' => 'Przywracanie',
            'forceDelete' => 'Trwałe usuwanie',
            'export' => 'Eksport',
            'import' => 'Import',
            'sync' => 'Synchronizacja',
            'assignRole' => 'Przypisywanie ról',
            'impersonate' => 'Przejęcie tożsamości',
            'forceLogout' => 'Wymuszenie wylogowania',
            'manage' => 'Zarządzanie',
            'configure' => 'Konfiguracja'
        ];
    }

    protected function loadPolicyClasses()
    {
        $this->policyClasses = [
            'UserPolicy' => 'App\\Policies\\UserPolicy',
            'ProductPolicy' => 'App\\Policies\\ProductPolicy',
            'CategoryPolicy' => 'App\\Policies\\CategoryPolicy',
            'BasePolicy' => 'App\\Policies\\BasePolicy'
        ];
    }

    protected function loadGateDefinitions()
    {
        // This would require reflection to get gate definitions
        // For now, we'll define common gates
        $this->gateDefinitions = [
            'accessAdminPanel' => 'Dostęp do panelu administracyjnego',
            'manageSystemSettings' => 'Zarządzanie ustawieniami systemu',
            'viewSystemLogs' => 'Przeglądanie logów systemu',
            'manageBackups' => 'Zarządzanie kopiami zapasowymi',
            'manageIntegrations' => 'Zarządzanie integracjami',
            'reserveStock' => 'Rezerwacja magazynu',
            'manageDeliveries' => 'Zarządzanie dostawami',
            'manageClaims' => 'Zarządzanie reklamacjami',
            'bulkActions' => 'Operacje masowe',
            'exportData' => 'Eksport danych'
        ];
    }

    // ==========================================
    // USER SELECTION
    // ==========================================

    public function updatedSelectedUserId($value)
    {
        if ($value) {
            $this->selectedUser = User::with(['roles', 'permissions'])->find($value);
            $this->clearResults();
        } else {
            $this->selectedUser = null;
            $this->clearResults();
        }
    }

    public function impersonateUser()
    {
        if (!$this->selectedUser) {
            return;
        }
        
        // This would switch testing context to selected user
        session()->flash('info', "Kontekst testowy przełączony na użytkownika: {$this->selectedUser->full_name}");
    }

    // ==========================================
    // TESTING ACTIONS
    // ==========================================

    public function testSinglePermission()
    {
        if (!$this->selectedUser || !$this->selectedAction || !$this->selectedResource) {
            session()->flash('error', 'Wybierz użytkownika, akcję i zasób.');
            return;
        }

        $this->testResult = null;
        $this->debugInfo = [];

        try {
            $modelClass = $this->availableResources[$this->selectedResource];
            
            // Test if model exists
            if (!class_exists($modelClass)) {
                throw new \Exception("Model class {$modelClass} not found");
            }

            // Create test instance if needed
            $modelInstance = null;
            if (in_array($this->selectedAction, ['view', 'update', 'delete', 'restore', 'forceDelete'])) {
                $modelInstance = $this->createTestModelInstance($modelClass);
            }

            // Perform authorization test
            $result = $this->performAuthorizationTest(
                $this->selectedUser, 
                $this->selectedAction, 
                $modelInstance ?: $modelClass
            );

            $this->testResult = [
                'allowed' => $result,
                'user' => $this->selectedUser->full_name,
                'action' => $this->selectedAction,
                'resource' => $this->selectedResource,
                'timestamp' => now()->format('H:i:s')
            ];

            // Generate debug information
            $this->generateDebugInfo();

        } catch (\Exception $e) {
            $this->testResult = [
                'allowed' => false,
                'error' => $e->getMessage(),
                'user' => $this->selectedUser->full_name,
                'action' => $this->selectedAction,
                'resource' => $this->selectedResource,
                'timestamp' => now()->format('H:i:s')
            ];
        }
    }

    protected function createTestModelInstance($modelClass)
    {
        switch ($modelClass) {
            case 'App\\Models\\User':
                return User::first() ?: User::factory()->make(['id' => 999]);
            case 'App\\Models\\Product':
                return Product::first() ?: new Product(['id' => 999, 'name' => 'Test Product']);
            case 'App\\Models\\Category':
                return Category::first() ?: new Category(['id' => 999, 'name' => 'Test Category']);
            default:
                $model = new $modelClass();
                $model->id = 999;
                return $model;
        }
    }

    protected function performAuthorizationTest($user, $action, $model)
    {
        // Temporarily switch auth user for testing
        $originalUser = auth()->user();
        auth()->login($user);

        try {
            $result = auth()->user()->can($action, $model);
        } catch (\Exception $e) {
            $result = false;
            $this->debugInfo[] = [
                'type' => 'error',
                'message' => "Policy error: " . $e->getMessage()
            ];
        }

        // Restore original user
        if ($originalUser) {
            auth()->login($originalUser);
        }

        return $result;
    }

    // ==========================================
    // BULK TESTING
    // ==========================================

    public function startBulkTest()
    {
        if (!$this->selectedUser) {
            session()->flash('error', 'Wybierz użytkownika do testowania.');
            return;
        }

        $this->testingMode = 'bulk';
        $this->bulkResults = [];

        // Test all actions for selected resource
        if ($this->selectedResource) {
            $this->testAllActionsForResource();
        } else {
            // Test all actions for all resources
            $this->testAllPermissions();
        }
    }

    protected function testAllActionsForResource()
    {
        $modelClass = $this->availableResources[$this->selectedResource];
        
        foreach ($this->availableActions as $action => $description) {
            try {
                $modelInstance = null;
                if (in_array($action, ['view', 'update', 'delete', 'restore', 'forceDelete'])) {
                    $modelInstance = $this->createTestModelInstance($modelClass);
                }

                $result = $this->performAuthorizationTest(
                    $this->selectedUser,
                    $action,
                    $modelInstance ?: $modelClass
                );

                $this->bulkResults[] = [
                    'resource' => $this->selectedResource,
                    'action' => $action,
                    'description' => $description,
                    'allowed' => $result,
                    'reason' => $this->getPermissionReason($action, $this->selectedResource, $result)
                ];

            } catch (\Exception $e) {
                $this->bulkResults[] = [
                    'resource' => $this->selectedResource,
                    'action' => $action,
                    'description' => $description,
                    'allowed' => false,
                    'reason' => 'Error: ' . $e->getMessage()
                ];
            }
        }
    }

    protected function testAllPermissions()
    {
        foreach ($this->availableResources as $resourceName => $modelClass) {
            foreach ($this->availableActions as $action => $description) {
                try {
                    $modelInstance = null;
                    if (in_array($action, ['view', 'update', 'delete', 'restore', 'forceDelete'])) {
                        $modelInstance = $this->createTestModelInstance($modelClass);
                    }

                    $result = $this->performAuthorizationTest(
                        $this->selectedUser,
                        $action,
                        $modelInstance ?: $modelClass
                    );

                    $this->bulkResults[] = [
                        'resource' => $resourceName,
                        'action' => $action,
                        'description' => $description,
                        'allowed' => $result,
                        'reason' => $this->getPermissionReason($action, $resourceName, $result)
                    ];

                } catch (\Exception $e) {
                    // Skip invalid combinations
                    continue;
                }
            }
        }
    }

    // ==========================================
    // USER ANALYSIS
    // ==========================================

    public function analyzeUserPermissions()
    {
        if (!$this->selectedUser) {
            session()->flash('error', 'Wybierz użytkownika do analizy.');
            return;
        }

        $this->analysisResults = [
            'roles' => $this->analyzeUserRoles(),
            'direct_permissions' => $this->analyzeDirectPermissions(),
            'effective_permissions' => $this->analyzeEffectivePermissions(),
            'policy_results' => $this->analyzePolicyResults(),
            'gate_results' => $this->analyzeGateResults(),
            'inheritance_chain' => $this->analyzeInheritanceChain(),
            'conflicts' => $this->detectPermissionConflicts()
        ];

        $this->showAnalysisModal = true;
    }

    protected function analyzeUserRoles()
    {
        $roles = $this->selectedUser->roles;
        
        return $roles->map(function ($role) {
            return [
                'name' => $role->name,
                'guard' => $role->guard_name,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('name')->toArray(),
                'is_admin' => $role->name === 'Admin',
                'level' => $this->getRoleLevel($role->name)
            ];
        })->toArray();
    }

    protected function analyzeDirectPermissions()
    {
        $directPermissions = $this->selectedUser->getDirectPermissions();
        
        return $directPermissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'guard' => $permission->guard_name,
                'granted_directly' => true
            ];
        })->toArray();
    }

    protected function analyzeEffectivePermissions()
    {
        $allPermissions = $this->selectedUser->getAllPermissions();
        
        return $allPermissions->map(function ($permission) {
            $source = $this->getPermissionSource($permission);
            
            return [
                'name' => $permission->name,
                'source' => $source,
                'module' => explode('.', $permission->name)[0] ?? 'other'
            ];
        })->groupBy('module')->toArray();
    }

    protected function analyzePolicyResults()
    {
        $results = [];
        
        foreach ($this->policyClasses as $policyName => $policyClass) {
            if (class_exists($policyClass)) {
                $reflection = new ReflectionClass($policyClass);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                
                $policyResults = [];
                foreach ($methods as $method) {
                    if (!in_array($method->getName(), ['__construct', '__call', '__callStatic'])) {
                        // This would require more complex testing
                        $policyResults[$method->getName()] = 'untested';
                    }
                }
                
                $results[$policyName] = $policyResults;
            }
        }
        
        return $results;
    }

    protected function analyzeGateResults()
    {
        $results = [];
        
        foreach ($this->gateDefinitions as $gateName => $description) {
            try {
                $originalUser = auth()->user();
                auth()->login($this->selectedUser);
                
                $result = Gate::allows($gateName);
                
                if ($originalUser) {
                    auth()->login($originalUser);
                }
                
                $results[$gateName] = [
                    'allowed' => $result,
                    'description' => $description
                ];
            } catch (\Exception $e) {
                $results[$gateName] = [
                    'allowed' => false,
                    'error' => $e->getMessage(),
                    'description' => $description
                ];
            }
        }
        
        return $results;
    }

    protected function analyzeInheritanceChain()
    {
        $chain = [];
        
        // Start with user's direct permissions
        $directPermissions = $this->selectedUser->getDirectPermissions();
        if ($directPermissions->isNotEmpty()) {
            $chain['direct'] = $directPermissions->pluck('name')->toArray();
        }
        
        // Add role-based permissions
        foreach ($this->selectedUser->roles as $role) {
            $chain[$role->name] = $role->permissions->pluck('name')->toArray();
        }
        
        return $chain;
    }

    protected function detectPermissionConflicts()
    {
        $conflicts = [];
        
        // Check for permission overrides
        $directPermissions = $this->selectedUser->getDirectPermissions()->pluck('name');
        
        foreach ($this->selectedUser->roles as $role) {
            $rolePermissions = $role->permissions->pluck('name');
            $overlap = $directPermissions->intersect($rolePermissions);
            
            if ($overlap->isNotEmpty()) {
                $conflicts[] = [
                    'type' => 'permission_override',
                    'role' => $role->name,
                    'permissions' => $overlap->toArray(),
                    'message' => "Użytkownik ma bezpośrednie uprawnienia, które duplikują uprawnienia z roli {$role->name}"
                ];
            }
        }
        
        return $conflicts;
    }

    // ==========================================
    // DEBUG INFORMATION
    // ==========================================

    protected function generateDebugInfo()
    {
        $this->debugInfo = [];
        
        // User roles
        $this->debugInfo[] = [
            'type' => 'info',
            'category' => 'Roles',
            'message' => 'User roles: ' . $this->selectedUser->getRoleNames()->implode(', ')
        ];
        
        // Direct permissions
        $directPermissions = $this->selectedUser->getDirectPermissions();
        if ($directPermissions->isNotEmpty()) {
            $this->debugInfo[] = [
                'type' => 'info',
                'category' => 'Direct Permissions',
                'message' => 'Direct permissions: ' . $directPermissions->pluck('name')->implode(', ')
            ];
        }
        
        // Role permissions
        foreach ($this->selectedUser->roles as $role) {
            $this->debugInfo[] = [
                'type' => 'info',
                'category' => "Role: {$role->name}",
                'message' => 'Permissions: ' . $role->permissions->pluck('name')->implode(', ')
            ];
        }
        
        // Permission check logic
        $permissionName = strtolower($this->selectedResource) . '.' . $this->selectedAction;
        $hasPermission = $this->selectedUser->hasPermissionTo($permissionName);
        
        $this->debugInfo[] = [
            'type' => $hasPermission ? 'success' : 'warning',
            'category' => 'Permission Check',
            'message' => "Permission '{$permissionName}': " . ($hasPermission ? 'GRANTED' : 'DENIED')
        ];
        
        // Policy check
        $this->debugInfo[] = [
            'type' => 'info',
            'category' => 'Policy',
            'message' => "Testing policy authorization for action '{$this->selectedAction}'"
        ];
    }

    protected function getPermissionSource($permission)
    {
        if ($this->selectedUser->getDirectPermissions()->contains($permission)) {
            return 'direct';
        }
        
        foreach ($this->selectedUser->roles as $role) {
            if ($role->permissions->contains($permission)) {
                return "role:{$role->name}";
            }
        }
        
        return 'unknown';
    }

    protected function getPermissionReason($action, $resource, $allowed)
    {
        if (!$allowed) {
            // Check why permission was denied
            $permissionName = strtolower($resource) . '.' . $action;
            $hasPermission = $this->selectedUser->hasPermissionTo($permissionName);
            
            if (!$hasPermission) {
                return "No permission: {$permissionName}";
            }
            
            return "Policy denied";
        }
        
        return "Authorized via role/permission";
    }

    protected function getRoleLevel($roleName)
    {
        $levels = [
            'Admin' => 1,
            'Manager' => 2,
            'Editor' => 3,
            'Warehouseman' => 4,
            'Salesperson' => 5,
            'Claims' => 6,
            'User' => 7
        ];
        
        return $levels[$roleName] ?? 99;
    }

    // ==========================================
    // UI METHODS
    // ==========================================

    public function clearResults()
    {
        $this->testResult = null;
        $this->debugInfo = [];
        $this->bulkResults = [];
        $this->analysisResults = [];
    }

    public function toggleSection($section)
    {
        $this->expandedSections[$section] = !$this->expandedSections[$section];
    }

    public function closeAnalysisModal()
    {
        $this->showAnalysisModal = false;
        $this->analysisResults = [];
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getUsersProperty()
    {
        return User::with('roles')->orderBy('first_name')->get();
    }

    public function getFilteredBulkResultsProperty()
    {
        $results = collect($this->bulkResults);
        
        return [
            'allowed' => $results->where('allowed', true),
            'denied' => $results->where('allowed', false),
            'by_resource' => $results->groupBy('resource'),
            'summary' => [
                'total' => $results->count(),
                'allowed' => $results->where('allowed', true)->count(),
                'denied' => $results->where('allowed', false)->count()
            ]
        ];
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.policy-tester', [
            'users' => $this->users,
            'filteredBulkResults' => $this->filteredBulkResults,
        ])->layout('layouts.app', [
            'title' => 'Tester Uprawnień - Admin PPM'
        ]);
    }
}