<?php

namespace App\Http\Livewire\Admin\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin Role Management List Component
 * 
 * FAZA C: 7-level role hierarchy management
 * 
 * Features:
 * - Role hierarchy visualization (tree structure)
 * - Permission matrix per role (grid layout)
 * - Role usage statistics (how many users per role)
 * - Role templates dla quick setup
 * - Drag & drop role hierarchy reordering
 * - Permission inheritance visualization
 * - Bulk permission assignment
 * - Role comparison tool
 */
class RoleList extends Component
{
    use WithPagination;

    // ==========================================
    // SEARCH & FILTERING PROPERTIES
    // ==========================================

    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'asc';
    
    // ==========================================
    // DISPLAY PROPERTIES
    // ==========================================
    
    public $showUsageStats = true;
    public $showPermissionMatrix = false;
    public $viewMode = 'list'; // list, matrix, hierarchy
    
    // ==========================================
    // ROLE MANAGEMENT
    // ==========================================
    
    public $selectedRoles = [];
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    
    // Create/Edit role data
    public $editingRole = null;
    public string $roleName = '';
    public string $roleGuardName = 'web';
    public string $roleDescription = '';
    public int $roleLevel = 7;  // Type hint for auto-casting from select (string -> int)
    public string $roleColor = 'gray';
    public bool $isSystemRole = false;
    public array $selectedPermissions = [];
    
    // Role hierarchy
    public $roleHierarchy = [
        1 => ['name' => 'Admin', 'color' => 'red'],
        2 => ['name' => 'Manager', 'color' => 'orange'], 
        3 => ['name' => 'Editor', 'color' => 'green'],
        4 => ['name' => 'Warehouseman', 'color' => 'blue'],
        5 => ['name' => 'Salesperson', 'color' => 'purple'],
        6 => ['name' => 'Claims', 'color' => 'teal'],
        7 => ['name' => 'User', 'color' => 'gray']
    ];
    
    // Role comparison
    public $compareRoles = [];
    public $showComparisonModal = false;

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('viewAny', Role::class);
    }

    // ==========================================
    // SEARCH & FILTERING METHODS
    // ==========================================

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // ==========================================
    // VIEW MODE METHODS
    // ==========================================

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function togglePermissionMatrix()
    {
        $this->showPermissionMatrix = !$this->showPermissionMatrix;
    }

    public function toggleUsageStats()
    {
        $this->showUsageStats = !$this->showUsageStats;
    }

    // ==========================================
    // ROLE CRUD METHODS
    // ==========================================

    public function openCreateModal()
    {
        $this->authorize('create', Role::class);
        
        $this->resetRoleForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('update', $role);
        
        $this->editingRole = $role;
        $this->roleName = $role->name;
        $this->roleGuardName = $role->guard_name;
        $this->roleDescription = $role->description ?? '';
        $this->roleLevel = $role->level ?? 7;
        $this->roleColor = $role->color ?? 'gray';
        $this->isSystemRole = $role->is_system ?? false;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        
        $this->showEditModal = true;
    }

    public function openDeleteModal($roleId)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('delete', $role);
        
        $this->editingRole = $role;
        $this->showDeleteModal = true;
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showComparisonModal = false;
        $this->resetRoleForm();
    }

    protected function resetRoleForm()
    {
        $this->editingRole = null;
        $this->roleName = '';
        $this->roleGuardName = 'web';
        $this->roleDescription = '';
        $this->roleLevel = 7;
        $this->roleColor = 'gray';
        $this->isSystemRole = false;
        $this->selectedPermissions = [];
    }

    public function saveRole()
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name' . ($this->editingRole ? ',' . $this->editingRole->id : ''),
            'roleGuardName' => 'required|string|max:255',
            'roleDescription' => 'nullable|string|max:1000',
            'roleLevel' => 'required|integer|between:1,7',
            'roleColor' => 'required|string|max:50',
            'selectedPermissions' => 'array'
        ]);

        $roleData = [
            'name' => $this->roleName,
            'guard_name' => $this->roleGuardName,
            'description' => $this->roleDescription,
            'level' => $this->roleLevel,
            'color' => $this->roleColor,
            'is_system' => $this->isSystemRole,
        ];

        if ($this->editingRole) {
            $this->editingRole->update($roleData);
            $role = $this->editingRole;
            $action = 'zaktualizowana';
        } else {
            $role = Role::create($roleData);
            $action = 'utworzona';
        }

        // Sync permissions - for system roles, ensure locked permissions are always included
        $permissionsToSync = $this->selectedPermissions;

        if ($role->is_system) {
            // Get all locked module permissions and ensure they're included
            $lockedModules = ['system', 'users', 'audit'];

            foreach ($this->permissionsByModule as $moduleName => $permissions) {
                $moduleKey = strtolower($moduleName);
                $isLocked = false;

                foreach ($lockedModules as $locked) {
                    if (stripos($moduleKey, $locked) !== false || $moduleKey === 'uzytkownicy') {
                        $isLocked = true;
                        break;
                    }
                }

                if ($isLocked) {
                    foreach ($permissions as $permission) {
                        if (!in_array($permission->name, $permissionsToSync)) {
                            $permissionsToSync[] = $permission->name;
                        }
                    }
                }
            }
        }

        $role->syncPermissions($permissionsToSync);

        session()->flash('success', "Rola '{$role->name}' została {$action}.");
        $this->closeModals();
    }

    public function deleteRole()
    {
        if (!$this->editingRole) return;
        
        $this->authorize('delete', $this->editingRole);
        
        // Check if role is in use
        if ($this->editingRole->users()->count() > 0) {
            session()->flash('error', 'Nie można usunąć roli przypisanej do użytkowników.');
            return;
        }

        // Don't allow deleting system roles
        if ($this->editingRole->is_system) {
            session()->flash('error', 'Nie można usunąć roli systemowej.');
            return;
        }

        $roleName = $this->editingRole->name;
        $this->editingRole->delete();
        
        session()->flash('success', "Rola '{$roleName}' została usunięta.");
        $this->closeModals();
    }

    // ==========================================
    // PERMISSION MANAGEMENT
    // ==========================================

    /**
     * Select all permissions for a given module (used in create/edit modal)
     */
    public function selectAllPermissionsForModule(string $moduleName): void
    {
        $modulePermissions = $this->permissionsByModule[$moduleName] ?? collect();
        $permissionNames = $modulePermissions->pluck('name')->toArray();

        // Merge with existing selections (avoid duplicates)
        $this->selectedPermissions = array_values(
            array_unique(array_merge($this->selectedPermissions, $permissionNames))
        );
    }

    public function togglePermissionForRole($roleId, $permissionName)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('update', $role);
        
        if ($role->hasPermissionTo($permissionName)) {
            $role->revokePermissionTo($permissionName);
            $action = 'odebrano';
        } else {
            $role->givePermissionTo($permissionName);
            $action = 'przyznano';
        }
        
        session()->flash('success', "Uprawnienie '{$permissionName}' {$action} roli '{$role->name}'.");
    }

    public function bulkAssignPermissions($roleId, $permissions)
    {
        $role = Role::findOrFail($roleId);
        $this->authorize('update', $role);
        
        $role->syncPermissions($permissions);
        
        session()->flash('success', "Zaktualizowano uprawnienia dla roli '{$role->name}'.");
    }

    // ==========================================
    // ROLE COMPARISON
    // ==========================================

    public function addToComparison($roleId)
    {
        if (!in_array($roleId, $this->compareRoles) && count($this->compareRoles) < 4) {
            $this->compareRoles[] = $roleId;
        }
    }

    public function removeFromComparison($roleId)
    {
        $this->compareRoles = array_diff($this->compareRoles, [$roleId]);
    }

    public function openComparisonModal()
    {
        if (count($this->compareRoles) < 2) {
            session()->flash('error', 'Wybierz przynajmniej 2 role do porównania.');
            return;
        }
        
        $this->showComparisonModal = true;
    }

    // ==========================================
    // ROLE TEMPLATES
    // ==========================================

    public function createFromTemplate($templateName)
    {
        $this->authorize('create', Role::class);
        
        $templates = $this->getRoleTemplates();
        
        if (!isset($templates[$templateName])) {
            session()->flash('error', 'Nieprawidłowy szablon roli.');
            return;
        }
        
        $template = $templates[$templateName];
        
        $this->roleName = $template['name'] . ' - Kopia';
        $this->roleDescription = $template['description'];
        $this->roleLevel = $template['level'];
        $this->roleColor = $template['color'];
        $this->selectedPermissions = $template['permissions'];
        
        $this->showCreateModal = true;
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getRolesProperty()
    {
        return $this->getRolesQuery()->get();
    }

    protected function getRolesQuery(): Builder
    {
        return Role::query()
            // Use subquery for users_count to avoid Spatie guard_name issue with withCount('users')
            ->selectRaw('roles.*, (SELECT COUNT(*) FROM model_has_roles WHERE model_has_roles.role_id = roles.id) as users_count')
            ->with('permissions')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function getPermissionsProperty()
    {
        return Permission::orderBy('name')->get();
    }

    public function getPermissionsByModuleProperty()
    {
        $permissions = Permission::orderBy('name')->get();

        // Group by module name and return as Collection (NOT array!)
        // This is required because Blade template uses ->pluck() on modulePermissions
        return $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });
    }

    public function getRoleUsageStatsProperty()
    {
        // Use subquery for users_count to avoid Spatie guard_name issue with withCount('users')
        $roles = Role::selectRaw('roles.*, (SELECT COUNT(*) FROM model_has_roles WHERE model_has_roles.role_id = roles.id) as users_count')->get();
        
        return $roles->mapWithKeys(function ($role) {
            return [$role->id => [
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions()->count(),
                'color' => $role->color ?? 'gray',
                'level' => $role->level ?? 7,
            ]];
        });
    }

    public function getPermissionMatrixProperty()
    {
        $roles = $this->roles;
        $permissions = $this->permissionsByModule;
        $matrix = [];
        
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            foreach ($permissions as $module => $modulePermissions) {
                foreach ($modulePermissions as $permission) {
                    $matrix[$role->id][$permission->name] = in_array($permission->name, $rolePermissions);
                }
            }
        }
        
        return $matrix;
    }

    public function getRoleTemplates()
    {
        return [
            'content_manager' => [
                'name' => 'Content Manager',
                'description' => 'Zarządzanie treścią i produktami bez uprawnień administracyjnych',
                'level' => 5,
                'color' => 'blue',
                'permissions' => [
                    'products.create', 'products.read', 'products.update', 
                    'categories.read', 'categories.update',
                    'media.create', 'media.read', 'media.update'
                ]
            ],
            'sales_rep' => [
                'name' => 'Sales Representative', 
                'description' => 'Przedstawiciel handlowy z dostępem do produktów i zamówień',
                'level' => 6,
                'color' => 'green',
                'permissions' => [
                    'products.read', 'prices.read', 'stock.read', 'orders.create', 'orders.read'
                ]
            ],
            'read_only' => [
                'name' => 'Read Only',
                'description' => 'Dostęp tylko do odczytu wszystkich danych',
                'level' => 7,
                'color' => 'gray',
                'permissions' => [
                    'products.read', 'categories.read', 'users.read', 'reports.read'
                ]
            ]
        ];
    }

    /**
     * Check if a permission is locked for the currently editing role (system role protection)
     * System roles (Admin) must always have system.*, users.* and audit.* permissions
     */
    public function isPermissionLockedForEdit(string $permissionName): bool
    {
        // Only apply lock when editing an existing system role
        if (!$this->editingRole || !($this->editingRole->is_system ?? false)) {
            return false;
        }

        // Locked modules for system roles
        $lockedModules = ['system', 'users', 'audit'];
        $module = explode('.', $permissionName)[0];

        return in_array($module, $lockedModules);
    }

    /**
     * Get all locked permission names for current editing role
     */
    public function getLockedPermissionNamesProperty(): array
    {
        if (!$this->editingRole || !($this->editingRole->is_system ?? false)) {
            return [];
        }

        $lockedModules = ['system', 'users', 'audit'];
        $lockedNames = [];

        foreach ($this->permissionsByModule as $moduleName => $permissions) {
            $moduleKey = strtolower($moduleName);
            $isLocked = false;

            foreach ($lockedModules as $locked) {
                if (stripos($moduleKey, $locked) !== false || $moduleKey === 'uzytkownicy') {
                    $isLocked = true;
                    break;
                }
            }

            if ($isLocked) {
                foreach ($permissions as $permission) {
                    $lockedNames[] = $permission->name;
                }
            }
        }

        return $lockedNames;
    }

    public function getCompareRolesDataProperty()
    {
        if (empty($this->compareRoles)) {
            return [];
        }
        
        $roles = Role::whereIn('id', $this->compareRoles)->with('permissions')->get();
        $permissions = Permission::orderBy('name')->get();
        
        $comparison = [];
        
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            $comparison[] = [
                'role' => $role,
                'permissions' => $permissions->mapWithKeys(function ($permission) use ($rolePermissions) {
                    return [$permission->name => in_array($permission->name, $rolePermissions)];
                })
            ];
        }
        
        return $comparison;
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.roles.role-list', [
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'permissionsByModule' => $this->permissionsByModule,
            'roleUsageStats' => $this->roleUsageStats,
            'permissionMatrix' => $this->permissionMatrix,
            'roleTemplates' => $this->getRoleTemplates(),
            'compareRolesData' => $this->compareRolesData,
        ])->layout('layouts.admin', [
            'title' => 'Zarzadzanie Rolami - Admin PPM',
            'breadcrumb' => 'Role'
        ]);
    }
}