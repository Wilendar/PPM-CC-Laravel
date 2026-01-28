<?php

namespace App\Http\Livewire\Admin\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Services\Permissions\PermissionModuleLoader;
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

    /**
     * Livewire hook - called when selectedRole changes via wire:model.live
     */
    public function updatedSelectedRole($value)
    {
        if ($value && !$this->hasChanges) {
            $this->loadRolePermissions();
        } elseif ($value && $this->hasChanges) {
            $this->showChangesModal = true;
        }
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

    /**
     * Check if permission is locked for editing (system roles can't have system/users permissions disabled)
     */
    public function isPermissionLocked(string $permissionName): bool
    {
        if (!$this->selectedRole) {
            return false;
        }

        $role = Role::find($this->selectedRole);
        if (!$role || !$role->is_system) {
            return false;
        }

        // System roles have system.* and users.* permissions locked (always ON)
        $lockedModules = ['system', 'users', 'audit'];
        $module = explode('.', $permissionName)[0];

        return in_array($module, $lockedModules);
    }

    /**
     * Get list of locked permission IDs for the current role
     */
    public function getLockedPermissionIds(): array
    {
        if (!$this->selectedRole) {
            return [];
        }

        $role = Role::find($this->selectedRole);
        if (!$role || !$role->is_system) {
            return [];
        }

        $lockedModules = ['system', 'users', 'audit'];
        $lockedIds = [];

        foreach ($this->getPermissionModules() as $moduleName => $permissions) {
            $moduleKey = strtolower($moduleName);
            // Check if module name matches locked modules (case-insensitive, handle translations)
            $isLocked = false;
            foreach ($lockedModules as $locked) {
                if (stripos($moduleKey, $locked) !== false || $moduleKey === 'uzytkownicy') {
                    $isLocked = true;
                    break;
                }
            }

            if ($isLocked) {
                foreach ($permissions as $permission) {
                    $lockedIds[] = $permission->id;
                }
            }
        }

        return $lockedIds;
    }

    public function togglePermission($permissionId)
    {
        // Check if permission is locked for system roles
        $permission = Permission::find($permissionId);
        if ($permission && $this->isPermissionLocked($permission->name)) {
            session()->flash('error', 'Nie można zmienić tego uprawnienia dla roli systemowej.');
            return;
        }

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
        // Use subquery for users_count to avoid Spatie guard_name issue with withCount('users')
        return Role::with('permissions')
            ->selectRaw('roles.*, (SELECT COUNT(*) FROM model_has_roles WHERE model_has_roles.role_id = roles.id) as users_count')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedRoleDataProperty()
    {
        if (!$this->selectedRole) {
            return null;
        }
        
        // Load role with permissions, users_count via subquery (avoid Spatie guard_name issue)
        return Role::with('permissions')
            ->selectRaw('roles.*, (SELECT COUNT(*) FROM model_has_roles WHERE model_has_roles.role_id = roles.id) as users_count')
            ->findOrFail($this->selectedRole);
    }

    public function getPermissionModules()
    {
        // Use PermissionModuleLoader for auto-discovery from config/permissions/*.php
        $moduleLoader = app(PermissionModuleLoader::class);
        $configModules = $moduleLoader->getPermissionsByModule();

        // Get actual permissions from database
        $dbPermissions = Permission::orderBy('name')->get()->keyBy('name');

        // Build result with DB permission objects (needed for UI toggling)
        $result = [];

        foreach ($configModules as $moduleName => $moduleData) {
            $modulePermissions = collect();

            foreach ($moduleData['permissions'] as $permConfig) {
                $permName = $permConfig['name'];

                if ($dbPermissions->has($permName)) {
                    // Add extra metadata from config to the permission object
                    $permission = $dbPermissions->get($permName);
                    $permission->label = $permConfig['label'] ?? $permName;
                    $permission->description = $permConfig['description'] ?? '';
                    $permission->dangerous = $permConfig['dangerous'] ?? false;
                    $modulePermissions->push($permission);
                }
            }

            if ($modulePermissions->isNotEmpty()) {
                $result[$moduleName] = $modulePermissions;
            }
        }

        // Add any orphan permissions (in DB but not in config files)
        $configuredPermNames = collect($configModules)
            ->flatMap(fn($m) => collect($m['permissions'])->pluck('name'))
            ->all();

        $orphanPermissions = $dbPermissions->filter(
            fn($p) => !in_array($p->name, $configuredPermNames)
        );

        if ($orphanPermissions->isNotEmpty()) {
            $result['Inne'] = $orphanPermissions->values();
        }

        return $result;
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
        ])->layout('layouts.admin', [
            'title' => 'Macierz Uprawnien - Admin PPM',
            'breadcrumb' => 'Uprawnienia'
        ]);
    }
}