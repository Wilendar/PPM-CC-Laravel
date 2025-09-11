<?php

namespace App\Http\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Admin User Management List Component
 * 
 * FAZA C: Enterprise user management interface
 * 
 * Features:
 * - Advanced search, sorting, filtering z pagination
 * - Bulk operations: activate/deactivate, role assignment, export
 * - Real-time search z debounced input
 * - Column visibility customization
 * - Performance optimized dla large datasets
 */
class UserList extends Component
{
    use WithPagination;

    // ==========================================
    // SEARCH & FILTERING PROPERTIES
    // ==========================================

    public $search = '';
    public $roleFilter = 'all';
    public $companyFilter = 'all';
    public $statusFilter = 'all';
    public $lastLoginFilter = 'all';
    
    // ==========================================
    // SORTING PROPERTIES  
    // ==========================================
    
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;
    
    // ==========================================
    // DISPLAY PROPERTIES
    // ==========================================
    
    public $showFilters = false;
    public $showColumnSettings = false;
    public $visibleColumns = [
        'avatar' => true,
        'name' => true,
        'email' => true,
        'company' => true,
        'roles' => true,
        'status' => true,
        'last_login' => true,
        'created_at' => true,
        'actions' => true
    ];
    
    // ==========================================
    // BULK OPERATIONS
    // ==========================================
    
    public $selectedUsers = [];
    public $selectAll = false;
    public $bulkAction = '';
    public $showBulkModal = false;
    public $bulkRoleId = '';
    
    // ==========================================
    // EXPORT PROPERTIES
    // ==========================================
    
    public $showExportModal = false;
    public $exportFormat = 'excel';
    public $exportFields = [
        'name' => true,
        'email' => true,
        'company' => true,
        'roles' => true,
        'status' => true,
        'last_login' => true,
        'created_at' => true
    ];

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('viewAny', User::class);
        
        // Load user preferences dla visible columns
        $this->loadUserPreferences();
    }

    protected function loadUserPreferences()
    {
        $user = auth()->user();
        $preferences = $user->getUIPreference('admin_user_list', []);
        
        if (!empty($preferences['visible_columns'])) {
            $this->visibleColumns = array_merge($this->visibleColumns, $preferences['visible_columns']);
        }
        
        if (!empty($preferences['per_page'])) {
            $this->perPage = $preferences['per_page'];
        }
        
        if (!empty($preferences['sort_field'])) {
            $this->sortField = $preferences['sort_field'];
            $this->sortDirection = $preferences['sort_direction'] ?? 'desc';
        }
    }

    protected function saveUserPreferences()
    {
        $user = auth()->user();
        
        $preferences = [
            'visible_columns' => $this->visibleColumns,
            'per_page' => $this->perPage,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection
        ];
        
        $user->updateUIPreference('admin_user_list', $preferences);
    }

    // ==========================================
    // SEARCH & FILTERING METHODS
    // ==========================================

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function updatedCompanyFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedLastLoginFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
        $this->saveUserPreferences();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = 'all';
        $this->companyFilter = 'all';
        $this->statusFilter = 'all';
        $this->lastLoginFilter = 'all';
        $this->resetPage();
    }

    // ==========================================
    // SORTING METHODS
    // ==========================================

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->saveUserPreferences();
        $this->resetPage();
    }

    // ==========================================
    // COLUMN VISIBILITY METHODS
    // ==========================================

    public function toggleColumn($column)
    {
        $this->visibleColumns[$column] = !$this->visibleColumns[$column];
        $this->saveUserPreferences();
    }

    public function showAllColumns()
    {
        foreach ($this->visibleColumns as $key => $value) {
            $this->visibleColumns[$key] = true;
        }
        $this->saveUserPreferences();
    }

    public function hideAllColumns()
    {
        foreach ($this->visibleColumns as $key => $value) {
            if ($key !== 'name' && $key !== 'actions') { // Always keep name and actions
                $this->visibleColumns[$key] = false;
            }
        }
        $this->saveUserPreferences();
    }

    // ==========================================
    // BULK OPERATIONS METHODS
    // ==========================================

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = $this->users->pluck('id')->toArray();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function updatedSelectedUsers()
    {
        $this->selectAll = count($this->selectedUsers) === $this->users->count();
    }

    public function openBulkModal()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Wybierz przynajmniej jednego użytkownika.');
            return;
        }

        $this->showBulkModal = true;
    }

    public function closeBulkModal()
    {
        $this->showBulkModal = false;
        $this->bulkAction = '';
        $this->bulkRoleId = '';
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Brak wybranych użytkowników.');
            return;
        }

        switch ($this->bulkAction) {
            case 'activate':
                $this->bulkActivateUsers();
                break;
            case 'deactivate':
                $this->bulkDeactivateUsers();
                break;
            case 'assign_role':
                $this->bulkAssignRole();
                break;
            case 'export':
                $this->bulkExportUsers();
                break;
            case 'delete':
                $this->bulkDeleteUsers();
                break;
        }

        $this->closeBulkModal();
        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    protected function bulkActivateUsers()
    {
        $this->authorize('update', User::class);

        User::whereIn('id', $this->selectedUsers)->update(['is_active' => true]);
        
        session()->flash('success', 'Aktywowano ' . count($this->selectedUsers) . ' użytkowników.');
    }

    protected function bulkDeactivateUsers()
    {
        $this->authorize('update', User::class);

        // Don't deactivate current user
        $usersToDeactivate = array_diff($this->selectedUsers, [auth()->id()]);
        
        if (empty($usersToDeactivate)) {
            session()->flash('error', 'Nie możesz dezaktywować swojego własnego konta.');
            return;
        }

        User::whereIn('id', $usersToDeactivate)->update(['is_active' => false]);
        
        session()->flash('success', 'Dezaktywowano ' . count($usersToDeactivate) . ' użytkowników.');
    }

    protected function bulkAssignRole()
    {
        $this->authorize('assignRole', User::class);

        if (empty($this->bulkRoleId)) {
            session()->flash('error', 'Wybierz rolę do przypisania.');
            return;
        }

        $role = Role::findById($this->bulkRoleId);
        $users = User::whereIn('id', $this->selectedUsers)->get();

        foreach ($users as $user) {
            $user->syncRoles([$role->name]);
        }

        session()->flash('success', 'Przypisano rolę "' . $role->name . '" dla ' . count($this->selectedUsers) . ' użytkowników.');
    }

    protected function bulkExportUsers()
    {
        $this->authorize('export', User::class);
        
        // TODO: Implement export functionality
        session()->flash('info', 'Funkcja eksportu zostanie wkrótce zaimplementowana.');
    }

    protected function bulkDeleteUsers()
    {
        $this->authorize('delete', User::class);
        
        // Don't delete current user or admins
        $usersToDelete = User::whereIn('id', $this->selectedUsers)
            ->where('id', '!=', auth()->id())
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Admin');
            })
            ->get();

        if ($usersToDelete->isEmpty()) {
            session()->flash('error', 'Nie można usunąć wybranych użytkowników (chronione konta).');
            return;
        }

        foreach ($usersToDelete as $user) {
            $user->delete();
        }

        session()->flash('success', 'Usunięto ' . $usersToDelete->count() . ' użytkowników.');
    }

    // ==========================================
    // INDIVIDUAL USER ACTIONS
    // ==========================================

    public function toggleUserStatus($userId)
    {
        $this->authorize('update', User::class);

        $user = User::findOrFail($userId);
        
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Nie możesz zmienić statusu swojego konta.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'aktywowano' : 'dezaktywowano';
        session()->flash('success', "Użytkownika {$user->full_name} {$status}.");
    }

    public function impersonateUser($userId)
    {
        $this->authorize('impersonate', User::class);
        
        $user = User::findOrFail($userId);
        
        if ($user->hasRole('Admin')) {
            session()->flash('error', 'Nie można przejąć tożsamości administratora.');
            return;
        }

        // Store original user ID in session
        session(['impersonating_user_id' => $user->id, 'original_user_id' => auth()->id()]);
        
        auth()->login($user);
        
        return redirect()->route('dashboard')->with('info', "Przejęto tożsamość użytkownika: {$user->full_name}");
    }

    public function deleteUser($userId)
    {
        $this->authorize('delete', User::class);
        
        $user = User::findOrFail($userId);
        
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Nie możesz usunąć swojego konta.');
            return;
        }
        
        if ($user->hasRole('Admin')) {
            session()->flash('error', 'Nie można usunąć konta administratora.');
            return;
        }
        
        $userName = $user->full_name;
        $user->delete();
        
        session()->flash('success', "Użytkownik {$userName} został usunięty.");
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getUsersProperty()
    {
        return $this->getUsersQuery()->paginate($this->perPage);
    }

    protected function getUsersQuery(): Builder
    {
        $query = User::query()
            ->with(['roles', 'permissions'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('company', 'like', '%' . $this->search . '%')
                      ->orWhere('position', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter !== 'all', function ($query) {
                $query->role($this->roleFilter);
            })
            ->when($this->companyFilter !== 'all', function ($query) {
                $query->where('company', $this->companyFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->when($this->lastLoginFilter !== 'all', function ($query) {
                switch ($this->lastLoginFilter) {
                    case 'today':
                        $query->whereDate('last_login_at', today());
                        break;
                    case 'week':
                        $query->where('last_login_at', '>=', now()->subWeek());
                        break;
                    case 'month':
                        $query->where('last_login_at', '>=', now()->subMonth());
                        break;
                    case 'never':
                        $query->whereNull('last_login_at');
                        break;
                }
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function getRolesProperty()
    {
        return Role::orderBy('name')->get();
    }

    public function getCompaniesProperty()
    {
        return User::select('company')
            ->whereNotNull('company')
            ->distinct()
            ->orderBy('company')
            ->pluck('company');
    }

    public function getStatsProperty()
    {
        $allUsers = User::query();
        
        return [
            'total' => $allUsers->count(),
            'active' => $allUsers->where('is_active', true)->count(),
            'inactive' => $allUsers->where('is_active', false)->count(),
            'online_today' => $allUsers->whereDate('last_login_at', today())->count(),
            'never_logged' => $allUsers->whereNull('last_login_at')->count(),
        ];
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.users.user-list', [
            'users' => $this->users,
            'roles' => $this->roles,
            'companies' => $this->companies,
            'stats' => $this->stats,
        ])->layout('layouts.app', [
            'title' => 'Zarządzanie Użytkownikami - Admin PPM'
        ]);
    }
}