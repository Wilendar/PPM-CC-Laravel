<?php

namespace App\Http\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

/**
 * Admin User Form Component
 * 
 * FAZA C: Comprehensive user creation/edit interface
 * 
 * Features:
 * - Multi-step form wizard dla complex user creation
 * - Real-time validation z server-side checks
 * - Role assignment z permission preview
 * - Avatar upload z drag & drop support
 * - Auto-save drafts functionality
 * - Email notifications dla new users
 */
class UserForm extends Component
{
    use WithFileUploads, AuthorizesRequests;

    // ==========================================
    // FORM PROPERTIES
    // ==========================================

    public ?User $user = null;
    public $isEditing = false;
    
    // Basic user information
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $company = '';
    public $position = '';
    public $is_active = true;
    
    // Password fields
    public $password = '';
    public $password_confirmation = '';
    public $generate_password = true;
    public $send_credentials = true;
    
    // Avatar upload
    public $avatar;
    public $existing_avatar = '';
    
    // UI Preferences
    public $preferred_language = 'pl';
    public $timezone = 'Europe/Warsaw';
    public $date_format = 'Y-m-d';
    public $ui_preferences = [];
    public $notification_settings = [];
    
    // Role and permissions
    public $selected_roles = [];
    public $custom_permissions = [];
    public $permission_overrides = [];
    
    // Multi-step form
    public $currentStep = 1;
    public $maxSteps = 4;
    public $stepTitles = [
        1 => 'Informacje podstawowe',
        2 => 'Dostęp i bezpieczeństwo', 
        3 => 'Role i uprawnienia',
        4 => 'Preferencje i podsumowanie'
    ];
    
    // Validation state
    public $realTimeValidation = true;
    
    // Auto-save
    public $draftSaved = false;
    public $lastSaveTime = null;

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount(User $user = null)
    {
        if ($user && $user->exists) {
            $this->isEditing = true;
            $this->user = $user;
            $this->loadUserData();
            
            $this->authorize('update', $user);
        } else {
            $this->authorize('create', User::class);
            
            $this->user = new User();
            $this->loadDefaults();
        }
    }

    protected function loadUserData()
    {
        $this->first_name = $this->user->first_name;
        $this->last_name = $this->user->last_name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->company = $this->user->company;
        $this->position = $this->user->position;
        $this->is_active = $this->user->is_active;
        $this->existing_avatar = $this->user->avatar;
        
        $this->preferred_language = $this->user->preferred_language ?? 'pl';
        $this->timezone = $this->user->timezone ?? 'Europe/Warsaw';
        $this->date_format = $this->user->date_format ?? 'Y-m-d';
        $this->ui_preferences = $this->user->ui_preferences ?? User::getDefaultUIPreferences();
        $this->notification_settings = $this->user->notification_settings ?? User::getDefaultNotificationSettings();
        
        $this->selected_roles = $this->user->roles->pluck('name')->toArray();
        $this->custom_permissions = $this->user->getDirectPermissions()->pluck('name')->toArray();
        
        // Don't require password for editing
        $this->generate_password = false;
        $this->send_credentials = false;
    }

    protected function loadDefaults()
    {
        $this->ui_preferences = User::getDefaultUIPreferences();
        $this->notification_settings = User::getDefaultNotificationSettings();
    }

    // ==========================================
    // VALIDATION RULES
    // ==========================================

    protected function rules()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->isEditing ? ',' . $this->user->id : ''),
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'preferred_language' => 'required|in:pl,en',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'selected_roles' => 'required|array|min:1',
            'selected_roles.*' => 'exists:roles,name',
            'custom_permissions' => 'array',
            'custom_permissions.*' => 'exists:permissions,name',
            'avatar' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,gif,webp',
        ];

        if (!$this->isEditing || $this->password) {
            $passwordRule = $this->generate_password ? 'nullable' : 'required';
            $rules['password'] = [$passwordRule, 'string', Password::min(8)->mixedCase()->numbers()->symbols()];
            $rules['password_confirmation'] = 'required_with:password|same:password';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'first_name.required' => 'Imię jest wymagane.',
            'last_name.required' => 'Nazwisko jest wymagane.',
            'email.required' => 'Email jest wymagany.',
            'email.email' => 'Podaj prawidłowy adres email.',
            'email.unique' => 'Ten adres email jest już zajęty.',
            'selected_roles.required' => 'Użytkownik musi mieć przypisaną przynajmniej jedną rolę.',
            'selected_roles.min' => 'Wybierz przynajmniej jedną rolę.',
            'password.required' => 'Hasło jest wymagane.',
            'password_confirmation.same' => 'Potwierdzenie hasła nie pasuje.',
            'avatar.image' => 'Avatar musi być plikiem graficznym.',
            'avatar.max' => 'Avatar nie może być większy niż 2MB.',
            'avatar.mimes' => 'Avatar musi być w formacie JPG, JPEG, PNG, GIF lub WEBP.',
        ];
    }

    // ==========================================
    // REAL-TIME VALIDATION
    // ==========================================

    public function updatedEmail()
    {
        if ($this->realTimeValidation) {
            $this->validateOnly('email');
        }
    }

    public function updatedFirstName()
    {
        if ($this->realTimeValidation) {
            $this->validateOnly('first_name');
        }
        $this->autoSaveDraft();
    }

    public function updatedLastName()
    {
        if ($this->realTimeValidation) {
            $this->validateOnly('last_name');
        }
        $this->autoSaveDraft();
    }

    public function updatedPassword()
    {
        if ($this->realTimeValidation && $this->password) {
            $this->validateOnly('password');
        }
    }

    public function updatedPasswordConfirmation()
    {
        if ($this->realTimeValidation && $this->password_confirmation) {
            $this->validateOnly('password_confirmation');
        }
    }

    public function updatedGeneratePassword($value)
    {
        if ($value) {
            $this->password = '';
            $this->password_confirmation = '';
        }
    }

    public function updatedSelectedRoles()
    {
        if ($this->realTimeValidation) {
            $this->validateOnly('selected_roles');
        }
        $this->autoSaveDraft();
    }

    public function updatedAvatar()
    {
        if ($this->realTimeValidation && $this->avatar) {
            $this->validateOnly('avatar');
        }
    }

    // ==========================================
    // MULTI-STEP FORM METHODS
    // ==========================================

    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->maxSteps) {
            $this->currentStep++;
        }
        
        $this->autoSaveDraft();
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->maxSteps && $step <= $this->currentStep + 1) {
            // Validate all previous steps
            for ($i = 1; $i < $step; $i++) {
                $this->validateStep($i);
            }
            
            $this->currentStep = $step;
        }
    }

    protected function validateCurrentStep()
    {
        $this->validateStep($this->currentStep);
    }

    protected function validateStep($step)
    {
        switch ($step) {
            case 1:
                $this->validate([
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email' . ($this->isEditing ? ',' . $this->user->id : ''),
                    'phone' => 'nullable|string|max:20',
                    'company' => 'nullable|string|max:255',
                    'position' => 'nullable|string|max:255',
                ]);
                break;
                
            case 2:
                $rules = ['is_active' => 'boolean'];
                if (!$this->isEditing || $this->password) {
                    $passwordRule = $this->generate_password ? 'nullable' : 'required';
                    $rules['password'] = [$passwordRule, 'string', Password::min(8)->mixedCase()->numbers()->symbols()];
                    $rules['password_confirmation'] = 'required_with:password|same:password';
                }
                $this->validate($rules);
                break;
                
            case 3:
                $this->validate([
                    'selected_roles' => 'required|array|min:1',
                    'selected_roles.*' => 'exists:roles,name',
                    'custom_permissions' => 'array',
                    'custom_permissions.*' => 'exists:permissions,name',
                ]);
                break;
                
            case 4:
                $this->validate([
                    'preferred_language' => 'required|in:pl,en',
                    'timezone' => 'required|string',
                    'date_format' => 'required|string',
                    'avatar' => 'nullable|image|max:2048|mimes:jpg,jpeg,png,gif,webp',
                ]);
                break;
        }
    }

    // ==========================================
    // AUTO-SAVE FUNCTIONALITY
    // ==========================================

    public function autoSaveDraft()
    {
        if (!$this->isEditing) {
            $draftData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
                'position' => $this->position,
                'selected_roles' => $this->selected_roles,
                'custom_permissions' => $this->custom_permissions,
                'ui_preferences' => $this->ui_preferences,
                'notification_settings' => $this->notification_settings,
            ];
            
            session(['user_form_draft' => $draftData]);
            $this->draftSaved = true;
            $this->lastSaveTime = now()->format('H:i:s');
            
            // Clear draft saved indicator after 2 seconds
            $this->dispatchBrowserEvent('draft-saved');
        }
    }

    public function loadDraft()
    {
        $draft = session('user_form_draft', []);
        
        if (!empty($draft)) {
            foreach ($draft as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            
            session()->flash('info', 'Załadowano zapisaną wersję roboczą formularza.');
        }
    }

    public function clearDraft()
    {
        session()->forget('user_form_draft');
        $this->draftSaved = false;
        $this->lastSaveTime = null;
    }

    // ==========================================
    // ROLE AND PERMISSION METHODS
    // ==========================================

    public function toggleRole($roleName)
    {
        if (in_array($roleName, $this->selected_roles)) {
            $this->selected_roles = array_diff($this->selected_roles, [$roleName]);
        } else {
            $this->selected_roles[] = $roleName;
        }
        
        $this->autoSaveDraft();
    }

    public function togglePermission($permissionName)
    {
        if (in_array($permissionName, $this->custom_permissions)) {
            $this->custom_permissions = array_diff($this->custom_permissions, [$permissionName]);
        } else {
            $this->custom_permissions[] = $permissionName;
        }
        
        $this->autoSaveDraft();
    }

    public function getInheritedPermissions()
    {
        $inheritedPermissions = [];
        
        foreach ($this->selected_roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($role->permissions as $permission) {
                    $inheritedPermissions[] = $permission->name;
                }
            }
        }
        
        return array_unique($inheritedPermissions);
    }

    public function getAllUserPermissions()
    {
        return array_unique(array_merge(
            $this->getInheritedPermissions(),
            $this->custom_permissions
        ));
    }

    // ==========================================
    // AVATAR METHODS
    // ==========================================

    public function removeAvatar()
    {
        $this->avatar = null;
        
        if ($this->isEditing && $this->existing_avatar) {
            $this->existing_avatar = '';
            // Mark for deletion on save
            $this->deleteExistingAvatar = true;
        }
    }

    protected function saveAvatar()
    {
        if ($this->avatar) {
            // Delete old avatar if editing
            if ($this->isEditing && $this->user->avatar) {
                Storage::delete('public/' . $this->user->avatar);
            }
            
            $path = $this->avatar->store('avatars', 'public');
            return $path;
        }
        
        return $this->isEditing ? $this->user->avatar : null;
    }

    // ==========================================
    // PASSWORD METHODS
    // ==========================================

    public function generateSecurePassword()
    {
        $this->password = Str::random(16);
        $this->password_confirmation = $this->password;
        $this->generate_password = true;
        
        $this->dispatchBrowserEvent('password-generated', [
            'password' => $this->password
        ]);
    }

    protected function getPassword()
    {
        if ($this->generate_password && !$this->isEditing) {
            return Str::random(16);
        }
        
        return $this->password ? Hash::make($this->password) : null;
    }

    // ==========================================
    // FORM SUBMISSION
    // ==========================================

    public function save()
    {
        $this->validate();
        
        try {
            $userData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
                'position' => $this->position,
                'is_active' => $this->is_active,
                'preferred_language' => $this->preferred_language,
                'timezone' => $this->timezone,
                'date_format' => $this->date_format,
                'ui_preferences' => $this->ui_preferences,
                'notification_settings' => $this->notification_settings,
            ];

            // Handle avatar
            $avatarPath = $this->saveAvatar();
            if ($avatarPath) {
                $userData['avatar'] = $avatarPath;
            } elseif (!$this->isEditing || $this->existing_avatar === '') {
                $userData['avatar'] = null;
            }

            // Handle password
            $password = $this->getPassword();
            if ($password) {
                $userData['password'] = $password;
            }

            if ($this->isEditing) {
                $this->user->update($userData);
                $action = 'zaktualizowany';
            } else {
                $this->user = User::create($userData);
                $action = 'utworzony';
                
                // Clear draft after successful creation
                $this->clearDraft();
            }

            // Sync roles
            $this->user->syncRoles($this->selected_roles);

            // Sync direct permissions (beyond roles)
            $this->user->syncPermissions($this->custom_permissions);

            // Send credentials email if requested
            if ($this->send_credentials && !$this->isEditing) {
                $this->sendCredentialsEmail();
            }

            session()->flash('success', "Użytkownik {$this->user->full_name} został pomyślnie {$action}.");
            
            return redirect()->route('admin.users.index');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Wystąpił błąd podczas zapisywania użytkownika: ' . $e->getMessage());
        }
    }

    protected function sendCredentialsEmail()
    {
        // TODO: Implement email sending
        // This will be implemented in later phase
        session()->flash('info', 'Email z danymi dostępu zostanie wysłany po implementacji systemu mailingowego.');
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getRolesProperty()
    {
        return Role::orderBy('name')->get();
    }

    public function getPermissionsProperty()
    {
        return Permission::orderBy('name')->get();
    }

    public function getPermissionsByModuleProperty()
    {
        $permissions = Permission::orderBy('name')->get();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $module = explode('.', $permission->name)[0];
            $grouped[$module][] = $permission;
        }
        
        return $grouped;
    }

    public function getCompanySuggestionsProperty()
    {
        return User::select('company')
            ->whereNotNull('company')
            ->distinct()
            ->orderBy('company')
            ->limit(10)
            ->pluck('company');
    }

    public function getTimezoneOptionsProperty()
    {
        return [
            'Europe/Warsaw' => 'Europa/Warszawa (UTC+1)',
            'Europe/London' => 'Europa/Londyn (UTC+0)', 
            'Europe/Berlin' => 'Europa/Berlin (UTC+1)',
            'America/New_York' => 'Ameryka/Nowy Jork (UTC-5)',
            'Asia/Tokyo' => 'Azja/Tokio (UTC+9)',
        ];
    }

    public function getLanguageOptionsProperty()
    {
        return [
            'pl' => 'Polski',
            'en' => 'English',
        ];
    }

    public function getDateFormatOptionsProperty()
    {
        return [
            'Y-m-d' => '2024-01-15',
            'd.m.Y' => '15.01.2024',
            'd/m/Y' => '15/01/2024',
            'm/d/Y' => '01/15/2024',
        ];
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        // Note: Layout is handled by wrapper views (admin/users/create.blade.php, edit.blade.php)
        // to work around Livewire 3 full-page component routing issues
        return view('livewire.admin.users.user-form', [
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'permissionsByModule' => $this->permissionsByModule,
            'companySuggestions' => $this->companySuggestions,
            'timezoneOptions' => $this->timezoneOptions,
            'languageOptions' => $this->languageOptions,
            'dateFormatOptions' => $this->dateFormatOptions,
            'inheritedPermissions' => $this->getInheritedPermissions(),
            'allUserPermissions' => $this->getAllUserPermissions(),
        ]);
    }
}