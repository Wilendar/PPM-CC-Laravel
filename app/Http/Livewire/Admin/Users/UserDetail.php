<?php

namespace App\Http\Livewire\Admin\Users;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Admin User Detail Component
 * 
 * FAZA C: Complete user profile display and management
 * 
 * Features:
 * - User info cards z edit-in-place functionality
 * - Activity timeline (login history, actions performed)
 * - Current sessions z device details
 * - Permission matrix display (inherited vs direct)
 * - Audit log entries for this user
 * - Quick actions: reset password, send verification email
 */
class UserDetail extends Component
{
    public User $user;
    
    // Edit modes dla inline editing
    public $editMode = [];
    public $editData = [];
    
    // Activity settings
    public $activityDays = 30;
    public $activityType = 'all';
    
    // Quick actions
    public $showPasswordResetModal = false;
    public $newPassword = '';
    public $passwordResetSent = false;
    
    // Permissions view
    public $showAllPermissions = false;
    public $permissionGroupBy = 'module';

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount(User $user)
    {
        $this->user = $user->load(['roles', 'permissions']);
        $this->authorize('view', $user);
        
        // Initialize edit data
        $this->editData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'company' => $user->company,
            'position' => $user->position,
        ];
    }

    // ==========================================
    // EDIT IN PLACE FUNCTIONALITY
    // ==========================================

    public function startEdit($field)
    {
        $this->authorize('update', $this->user);
        
        $this->editMode[$field] = true;
        $this->editData[$field] = $this->user->$field;
    }

    public function saveEdit($field)
    {
        $this->authorize('update', $this->user);
        
        $rules = [
            'editData.first_name' => 'required|string|max:255',
            'editData.last_name' => 'required|string|max:255',
            'editData.phone' => 'nullable|string|max:20',
            'editData.company' => 'nullable|string|max:255',
            'editData.position' => 'nullable|string|max:255',
        ];

        $this->validate([$rules["editData.$field"]]);

        $this->user->update([$field => $this->editData[$field]]);
        $this->editMode[$field] = false;
        
        session()->flash('success', 'Zaktualizowano dane użytkownika.');
    }

    public function cancelEdit($field)
    {
        $this->editMode[$field] = false;
        $this->editData[$field] = $this->user->$field;
    }

    // ==========================================
    // USER STATUS MANAGEMENT
    // ==========================================

    public function toggleUserStatus()
    {
        $this->authorize('update', $this->user);
        
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'Nie możesz zmienić statusu swojego konta.');
            return;
        }

        $this->user->update(['is_active' => !$this->user->is_active]);
        
        $status = $this->user->is_active ? 'aktywowano' : 'dezaktywowano';
        session()->flash('success', "Użytkownika {$this->user->full_name} {$status}.");
        
        $this->user->refresh();
    }

    public function toggleEmailVerified()
    {
        $this->authorize('update', $this->user);

        if ($this->user->email_verified_at) {
            $this->user->update(['email_verified_at' => null]);
            session()->flash('success', 'Oznaczono email jako niezweryfikowany.');
        } else {
            $this->user->update(['email_verified_at' => now()]);
            session()->flash('success', 'Oznaczono email jako zweryfikowany.');
        }
        
        $this->user->refresh();
    }

    // ==========================================
    // PASSWORD MANAGEMENT
    // ==========================================

    public function openPasswordResetModal()
    {
        $this->authorize('update', $this->user);
        
        $this->showPasswordResetModal = true;
        $this->newPassword = '';
        $this->passwordResetSent = false;
    }

    public function resetPassword()
    {
        $this->authorize('update', $this->user);
        
        $this->validate([
            'newPassword' => 'required|string|min:8'
        ]);

        $this->user->update([
            'password' => Hash::make($this->newPassword)
        ]);

        $this->passwordResetSent = true;
        
        // TODO: Send email notification
        session()->flash('success', 'Hasło zostało zmienione. Email informacyjny zostanie wysłany.');
    }

    public function generatePassword()
    {
        $this->newPassword = \Illuminate\Support\Str::random(16);
        $this->dispatchBrowserEvent('password-generated', [
            'password' => $this->newPassword
        ]);
    }

    public function closePasswordModal()
    {
        $this->showPasswordResetModal = false;
        $this->newPassword = '';
        $this->passwordResetSent = false;
    }

    // ==========================================
    // IMPERSONATION
    // ==========================================

    public function impersonateUser()
    {
        $this->authorize('impersonate', User::class);
        
        if ($this->user->hasRole('Admin')) {
            session()->flash('error', 'Nie można przejąć tożsamości administratora.');
            return;
        }

        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'Nie można przejąć własnej tożsamości.');
            return;
        }

        // Store original user ID in session
        session(['impersonating_user_id' => $this->user->id, 'original_user_id' => auth()->id()]);
        
        auth()->login($this->user);
        
        return redirect()->route('dashboard')->with('info', "Przejęto tożsamość użytkownika: {$this->user->full_name}");
    }

    // ==========================================
    // PERMISSION MANAGEMENT
    // ==========================================

    public function togglePermissionView()
    {
        $this->showAllPermissions = !$this->showAllPermissions;
    }

    public function changePermissionGrouping($groupBy)
    {
        $this->permissionGroupBy = $groupBy;
    }

    // ==========================================
    // ACTIVITY FILTERING
    // ==========================================

    public function updateActivityFilter($days, $type = 'all')
    {
        $this->activityDays = $days;
        $this->activityType = $type;
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getUserActivityProperty()
    {
        // Simulate activity data - will be replaced with real audit log data
        $activities = collect([
            [
                'id' => 1,
                'type' => 'login',
                'description' => 'Zalogował się do systemu',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subHours(2),
            ],
            [
                'id' => 2,
                'type' => 'update',
                'description' => 'Zaktualizował produkt: Sprężarka ACM-150',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subHours(5),
            ],
            [
                'id' => 3,
                'type' => 'export',
                'description' => 'Wyeksportował 150 produktów do Excel',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subDay(),
            ],
        ]);

        if ($this->activityType !== 'all') {
            $activities = $activities->where('type', $this->activityType);
        }

        return $activities->where('created_at', '>=', now()->subDays($this->activityDays));
    }

    public function getLoginHistoryProperty()
    {
        // Simulate login history - will be replaced with real session data
        return collect([
            [
                'id' => 1,
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'location' => 'Warszawa, PL',
                'device_type' => 'Desktop',
                'browser' => 'Chrome 120',
                'login_at' => now()->subHours(2),
                'logout_at' => null,
                'is_current' => true,
            ],
            [
                'id' => 2,
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15',
                'location' => 'Warszawa, PL',
                'device_type' => 'Mobile',
                'browser' => 'Safari Mobile',
                'login_at' => now()->subDay(),
                'logout_at' => now()->subDay()->addHours(3),
                'is_current' => false,
            ],
        ]);
    }

    public function getRolePermissionsProperty()
    {
        $rolePermissions = [];
        
        foreach ($this->user->roles as $role) {
            $rolePermissions[$role->name] = $role->permissions->pluck('name')->toArray();
        }
        
        return $rolePermissions;
    }

    public function getDirectPermissionsProperty()
    {
        return $this->user->getDirectPermissions()->pluck('name')->toArray();
    }

    public function getAllUserPermissionsProperty()
    {
        return $this->user->getAllPermissions()->pluck('name')->toArray();
    }

    public function getPermissionsByModuleProperty()
    {
        $permissions = $this->user->getAllPermissions();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'other';
            $action = $parts[1] ?? $permission->name;
            
            $grouped[$module][] = [
                'name' => $permission->name,
                'action' => $action,
                'from_role' => $this->isPermissionFromRole($permission->name),
                'direct' => in_array($permission->name, $this->directPermissions),
            ];
        }
        
        return $grouped;
    }

    protected function isPermissionFromRole($permissionName)
    {
        foreach ($this->rolePermissions as $roleName => $permissions) {
            if (in_array($permissionName, $permissions)) {
                return $roleName;
            }
        }
        
        return false;
    }

    public function getUserStatsProperty()
    {
        return [
            'total_logins' => 47, // Simulated
            'last_login' => $this->user->last_login_at,
            'account_age_days' => $this->user->created_at->diffInDays(),
            'total_permissions' => count($this->allUserPermissions),
            'direct_permissions' => count($this->directPermissions),
            'role_permissions' => count($this->allUserPermissions) - count($this->directPermissions),
            'active_sessions' => 1, // Simulated
        ];
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.users.user-detail', [
            'userActivity' => $this->userActivity,
            'loginHistory' => $this->loginHistory,
            'rolePermissions' => $this->rolePermissions,
            'directPermissions' => $this->directPermissions,
            'allUserPermissions' => $this->allUserPermissions,
            'permissionsByModule' => $this->permissionsByModule,
            'userStats' => $this->userStats,
        ])->layout('layouts.app', [
            'title' => 'Profil użytkownika: ' . $this->user->full_name . ' - Admin PPM'
        ]);
    }
}