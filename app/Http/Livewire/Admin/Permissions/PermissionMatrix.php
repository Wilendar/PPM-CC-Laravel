<?php

namespace App\Http\Livewire\Admin\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Permission Matrix Management Component
 * 
 * FAZA C: Advanced permission management interface
 * 
 * Features:
 * - Interactive permission grid (roles vs permissions)
 * - Module-based grouping for 49 permissions
 * - Visual permission inheritance display
 * - Quick templates: "Read Only", "Full Access", "Manager Level"
 * - Bulk select/deselect operations
 * - Changes preview before saving
 * - Drag & drop for bulk operations
 */
class PermissionMatrix extends Component
{
    // ==========================================
    // CORE PROPERTIES
    // ==========================================

    public $selectedRole = null;
    public $permissionMatrix = [];
    public $originalPermissions = [];
    public $hasChanges = false;
    public $previewMode = false;
    
    // ==========================================
    // MODULE FILTERING
    // ==========================================
    
    public $selectedModule = 'all';
    public $expandedModules = [];
    public $moduleStats = [];
    
    // ==========================================
    // BULK OPERATIONS
    // ==========================================
    
    public $bulkSelectMode = false;
    public $selectedPermissions = [];
    public $bulkAction = '';
    public $showBulkModal = false;
    
    // ==========================================
    // TEMPLATES & PRESETS
    // ==========================================
    
    public $showTemplateModal = false;
    public $selectedTemplate = '';
    public $templateName = '';
    public $customTemplates = [];
    
    // ==========================================
    // UI STATE
    // ==========================================
    
    public $showChangesModal = false;
    public $showConflictsModal = false;
    public $conflictingUsers = [];
    public $loading = false;

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('manage', Permission::class);
        
        $this->initializeMatrix();
        $this->loadCustomTemplates();
        $this->calculateModuleStats();
        
        // Default to first role if exists
        $firstRole = Role::orderBy('id')->first();
        if ($firstRole) {
            $this->selectedRole = $firstRole->id;
            $this->loadRolePermissions();
        }
    }

    protected function initializeMatrix()
    {
        $modules = $this->getPermissionModules();
        
        foreach ($modules as $module => $permissions) {
            $this->expandedModules[$module] = true;
            foreach ($permissions as $permission) {
                $this->permissionMatrix[$permission->id] = false;
            }
        }
    }

    // ==========================================
    // ROLE SELECTION & LOADING
    // ==========================================

    public function selectRole($roleId)
    {
        if ($this->hasChanges) {
            $this->showChangesModal = true;
            return;
        }
        
        $this->selectedRole = $roleId;
        $this->loadRolePermissions();
        $this->calculateModuleStats();
    }

    public function confirmRoleChange()
    {
        $this->saveChanges();
        $this->showChangesModal = false;
    }

    public function discardChanges()
    {
        $this->loadRolePermissions();
        $this->hasChanges = false;
        $this->showChangesModal = false;
    }

    protected function loadRolePermissions()
    {
        if (!$this->selectedRole) {
            return;
        }

        $role = Role::with('permissions')->findOrFail($this->selectedRole);
        
        // Reset matrix
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Set role permissions
        foreach ($role->permissions as $permission) {
            $this->permissionMatrix[$permission->id] = true;
        }
        
        $this->originalPermissions = $this->permissionMatrix;
        $this->hasChanges = false;
        $this->calculateModuleStats();
    }

    // ==========================================
    // PERMISSION TOGGLE & MANAGEMENT
    // ==========================================

    public function togglePermission($permissionId)
    {
        $this->permissionMatrix[$permissionId] = !$this->permissionMatrix[$permissionId];
        $this->checkForChanges();
        $this->calculateModuleStats();
    }

    public function toggleModule($module)
    {
        $permissions = $this->getPermissionModules()[$module];
        $allEnabled = true;
        
        foreach ($permissions as $permission) {
            if (!$this->permissionMatrix[$permission->id]) {
                $allEnabled = false;
                break;
            }
        }
        
        // If all enabled, disable all. Otherwise enable all.
        foreach ($permissions as $permission) {
            $this->permissionMatrix[$permission->id] = !$allEnabled;
        }
        
        $this->checkForChanges();
        $this->calculateModuleStats();
    }

    protected function checkForChanges()
    {
        $this->hasChanges = $this->permissionMatrix !== $this->originalPermissions;
    }

    // ==========================================
    // BULK OPERATIONS
    // ==========================================

    public function enableBulkSelect()
    {
        $this->bulkSelectMode = true;
        $this->selectedPermissions = [];
    }

    public function disableBulkSelect()
    {
        $this->bulkSelectMode = false;
        $this->selectedPermissions = [];
    }

    public function togglePermissionSelection($permissionId)
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    public function selectAllPermissions()
    {
        if ($this->selectedModule === 'all') {
            $this->selectedPermissions = array_keys($this->permissionMatrix);
        } else {
            $permissions = $this->getPermissionModules()[$this->selectedModule];
            $this->selectedPermissions = $permissions->pluck('id')->toArray();
        }
    }

    public function deselectAllPermissions()
    {
        $this->selectedPermissions = [];
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedPermissions)) {
            session()->flash('error', 'Nie wybrano żadnych uprawnień.');
            return;
        }

        switch ($this->bulkAction) {
            case 'enable':
                $this->bulkEnablePermissions();
                break;
            case 'disable':
                $this->bulkDisablePermissions();
                break;
            case 'copy_to_role':
                $this->showBulkModal = true;
                return;
        }

        $this->bulkAction = '';
        $this->selectedPermissions = [];
    }

    protected function bulkEnablePermissions()
    {
        foreach ($this->selectedPermissions as $permissionId) {
            $this->permissionMatrix[$permissionId] = true;
        }
        
        $this->checkForChanges();
        $this->calculateModuleStats();
        
        session()->flash('success', 'Włączono ' . count($this->selectedPermissions) . ' uprawnień.');
    }

    protected function bulkDisablePermissions()
    {
        foreach ($this->selectedPermissions as $permissionId) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        $this->checkForChanges();
        $this->calculateModuleStats();
        
        session()->flash('success', 'Wyłączono ' . count($this->selectedPermissions) . ' uprawnień.');
    }

    // ==========================================
    // TEMPLATES & PRESETS
    // ==========================================

    public function applyTemplate($template)
    {
        switch ($template) {
            case 'read_only':
                $this->applyReadOnlyTemplate();
                break;
            case 'full_access':
                $this->applyFullAccessTemplate();
                break;
            case 'manager_level':
                $this->applyManagerLevelTemplate();
                break;
            case 'editor_level':
                $this->applyEditorLevelTemplate();
                break;
            case 'user_level':
                $this->applyUserLevelTemplate();
                break;
            default:
                $this->applyCustomTemplate($template);
        }
        
        $this->checkForChanges();
        $this->calculateModuleStats();
    }

    protected function applyReadOnlyTemplate()
    {
        // Reset all permissions
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Enable only read permissions
        $readPermissions = Permission::where('name', 'like', '%.read')
            ->orWhere('name', 'like', '%.view%')
            ->get();
            
        foreach ($readPermissions as $permission) {
            $this->permissionMatrix[$permission->id] = true;
        }
    }

    protected function applyFullAccessTemplate()
    {
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = true;
        }
    }

    protected function applyManagerLevelTemplate()
    {
        // Reset all permissions
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Enable manager-level permissions (all except admin and dangerous operations)
        $managerPermissions = Permission::where('name', 'not like', 'admin.%')
            ->where('name', 'not like', '%.delete')
            ->where('name', 'not like', 'user.impersonate')
            ->where('name', 'not like', 'system.%')
            ->get();
            
        foreach ($managerPermissions as $permission) {
            $this->permissionMatrix[$permission->id] = true;
        }
    }

    protected function applyEditorLevelTemplate()
    {
        // Reset all permissions
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Enable editor-level permissions
        $editorPermissions = Permission::whereIn('name', [
            'product.read', 'product.update', 'product.export',
            'category.read', 'category.update',
            'media.read', 'media.create', 'media.update', 'media.upload',
            'price.read',
            'stock.read',
            'integration.read'
        ])->get();
            
        foreach ($editorPermissions as $permission) {
            $this->permissionMatrix[$permission->id] = true;
        }
    }

    protected function applyUserLevelTemplate()
    {
        // Reset all permissions
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Enable basic user permissions
        $userPermissions = Permission::whereIn('name', [
            'product.read',
            'category.read', 
            'media.read',
            'price.read',
            'stock.read'
        ])->get();
            
        foreach ($userPermissions as $permission) {
            $this->permissionMatrix[$permission->id] = true;
        }
    }

    // ==========================================
    // CUSTOM TEMPLATES
    // ==========================================

    public function saveAsTemplate()
    {
        if (empty($this->templateName)) {
            session()->flash('error', 'Wprowadź nazwę szablonu.');
            return;
        }

        $enabledPermissions = array_keys(array_filter($this->permissionMatrix));
        
        $this->customTemplates[$this->templateName] = [
            'name' => $this->templateName,
            'permissions' => $enabledPermissions,
            'created_at' => now(),
            'created_by' => auth()->user()->full_name
        ];
        
        $this->saveCustomTemplates();
        
        session()->flash('success', 'Szablon "' . $this->templateName . '" został zapisany.');
        
        $this->templateName = '';
        $this->showTemplateModal = false;
    }

    public function deleteCustomTemplate($templateName)
    {
        unset($this->customTemplates[$templateName]);
        $this->saveCustomTemplates();
        
        session()->flash('success', 'Szablon został usunięty.');
    }

    protected function applyCustomTemplate($templateName)
    {
        if (!isset($this->customTemplates[$templateName])) {
            return;
        }

        $template = $this->customTemplates[$templateName];
        
        // Reset all permissions
        foreach ($this->permissionMatrix as $permissionId => $value) {
            $this->permissionMatrix[$permissionId] = false;
        }
        
        // Enable template permissions
        foreach ($template['permissions'] as $permissionId) {
            if (isset($this->permissionMatrix[$permissionId])) {
                $this->permissionMatrix[$permissionId] = true;
            }
        }
    }

    protected function loadCustomTemplates()
    {
        $user = auth()->user();
        $this->customTemplates = $user->getUIPreference('permission_templates', []);
    }

    protected function saveCustomTemplates()
    {
        $user = auth()->user();
        $user->updateUIPreference('permission_templates', $this->customTemplates);
    }

    // ==========================================
    // MODULE MANAGEMENT
    // ==========================================

    public function toggleModuleExpansion($module)
    {
        $this->expandedModules[$module] = !$this->expandedModules[$module];
    }

    protected function calculateModuleStats()
    {
        $modules = $this->getPermissionModules();
        
        foreach ($modules as $module => $permissions) {
            $total = $permissions->count();
            $enabled = 0;
            
            foreach ($permissions as $permission) {
                if ($this->permissionMatrix[$permission->id] ?? false) {
                    $enabled++;
                }
            }
            
            $this->moduleStats[$module] = [
                'total' => $total,
                'enabled' => $enabled,
                'percentage' => $total > 0 ? round(($enabled / $total) * 100) : 0
            ];
        }
    }

    // ==========================================
    // SAVE OPERATIONS
    // ==========================================

    public function saveChanges()
    {
        if (!$this->selectedRole || !$this->hasChanges) {
            return;
        }

        $this->loading = true;

        try {
            DB::beginTransaction();
            
            $role = Role::findOrFail($this->selectedRole);
            
            // Check for conflicting users
            $this->checkForConflictingUsers($role);
            
            if (!empty($this->conflictingUsers)) {
                $this->showConflictsModal = true;
                $this->loading = false;
                DB::rollBack();
                return;
            }
            
            // Get enabled permissions
            $enabledPermissionIds = array_keys(array_filter($this->permissionMatrix));
            $permissions = Permission::whereIn('id', $enabledPermissionIds)->get();
            
            // Sync permissions
            $role->syncPermissions($permissions);
            
            // Update original permissions
            $this->originalPermissions = $this->permissionMatrix;
            $this->hasChanges = false;
            
            DB::commit();
            
            session()->flash('success', 'Uprawnienia dla roli "' . $role->name . '" zostały zaktualizowane.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            session()->flash('error', 'Błąd podczas zapisywania uprawnień: ' . $e->getMessage());
        }
        
        $this->loading = false;
    }

    protected function checkForConflictingUsers($role)
    {
        // Find users with this role who might be affected by permission changes
        $users = User::role($role->name)->get();
        $this->conflictingUsers = [];
        
        foreach ($users as $user) {
            if ($user->hasRole('Admin')) {
                continue; // Admins are not affected
            }
            
            // Check if user has additional permissions that might conflict
            $userPermissions = $user->getDirectPermissions();
            if ($userPermissions->isNotEmpty()) {
                $this->conflictingUsers[] = [
                    'user' => $user,
                    'conflicting_permissions' => $userPermissions
                ];
            }
        }
    }

    public function resolveConflicts($action)
    {
        switch ($action) {
            case 'keep_user_permissions':
                // Keep user-specific permissions, just update role
                $this->saveChanges();
                break;
            case 'remove_user_permissions':
                // Remove conflicting user permissions
                foreach ($this->conflictingUsers as $conflictData) {
                    $conflictData['user']->revokePermissionTo($conflictData['conflicting_permissions']);
                }
                $this->saveChanges();
                break;
            case 'cancel':
                // Do nothing
                break;
        }
        
        $this->conflictingUsers = [];
        $this->showConflictsModal = false;
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getRolesProperty()
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    public function getSelectedRoleDataProperty()
    {
        if (!$this->selectedRole) {
            return null;
        }
        
        return Role::with('permissions', 'users')->findOrFail($this->selectedRole);
    }

    public function getPermissionModules()
    {
        $permissions = Permission::orderBy('name')->get();
        $modules = [];
        
        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'other';
            
            if (!isset($modules[$module])) {
                $modules[$module] = collect();
            }
            
            $modules[$module]->push($permission);
        }
        
        // Define module order and display names
        $moduleOrder = [
            'product' => 'Produkty',
            'category' => 'Kategorie',
            'media' => 'Media',
            'price' => 'Ceny',
            'stock' => 'Magazyn',
            'warehouse' => 'Magazyny',
            'user' => 'Użytkownicy',
            'role' => 'Role',
            'integration' => 'Integracje',
            'order' => 'Zamówienia',
            'claim' => 'Reklamacje',
            'admin' => 'Administracja',
            'system' => 'System'
        ];
        
        $orderedModules = [];
        foreach ($moduleOrder as $key => $name) {
            if (isset($modules[$key])) {
                $orderedModules[$name] = $modules[$key];
            }
        }
        
        // Add any remaining modules
        foreach ($modules as $key => $permissions) {
            if (!in_array($key, array_keys($moduleOrder))) {
                $orderedModules[ucfirst($key)] = $permissions;
            }
        }
        
        return $orderedModules;
    }

    public function getPermissionsByModuleProperty()
    {
        $modules = $this->getPermissionModules();
        
        if ($this->selectedModule === 'all') {
            return $modules;
        }
        
        return array_filter($modules, function ($key) {
            return $key === $this->selectedModule;
        }, ARRAY_FILTER_USE_KEY);
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.permissions.permission-matrix', [
            'roles' => $this->roles,
            'selectedRoleData' => $this->selectedRoleData,
            'permissionsByModule' => $this->permissionsByModule,
        ])->layout('layouts.app', [
            'title' => 'Macierz Uprawnień - Admin PPM'
        ]);
    }
}