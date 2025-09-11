<?php

namespace App\Http\Livewire\Profile;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class EditProfile extends Component
{
    use WithFileUploads;

    // Personal Information
    public $first_name;
    public $last_name; 
    public $email;
    public $company;
    public $position;
    public $phone;

    // Avatar
    public $avatar;
    public $current_avatar;

    // Password Change
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    public $showPasswordSection = false;

    // UI Preferences
    public $theme = 'light';
    public $language = 'pl';
    public $date_format = 'd.m.Y';
    public $timezone = 'Europe/Warsaw';

    // Notification Settings
    public $email_notifications = true;
    public $browser_notifications = true;
    public $mobile_notifications = false;
    public $marketing_emails = false;

    // State
    public $loading = false;
    public $activeTab = 'personal';

    protected function rules()
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048', // Max 2MB
            'theme' => 'in:light,dark,auto',
            'language' => 'in:pl,en',
            'date_format' => 'in:d.m.Y,Y-m-d,m/d/Y',
            'timezone' => 'string',
        ];

        // Add password rules if changing password
        if ($this->showPasswordSection && !empty($this->current_password)) {
            $rules['current_password'] = 'required';
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $rules;
    }

    protected $messages = [
        'first_name.required' => 'Imię jest wymagane.',
        'last_name.required' => 'Nazwisko jest wymagane.',
        'email.required' => 'Email jest wymagany.',
        'email.unique' => 'Ten adres email jest już zajęty.',
        'avatar.image' => 'Plik musi być obrazem.',
        'avatar.max' => 'Maksymalny rozmiar zdjęcia to 2MB.',
        'current_password.required' => 'Wprowadź obecne hasło.',
        'password.confirmed' => 'Hasła muszą być identyczne.'
    ];

    public function mount()
    {
        $user = Auth::user();
        
        // Load user data
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->company = $user->company;
        $this->position = $user->position;
        $this->phone = $user->phone;
        $this->current_avatar = $user->avatar;

        // Load preferences (with defaults if not set)
        $preferences = $user->preferences ?? [];
        $this->theme = $preferences['theme'] ?? 'light';
        $this->language = $preferences['language'] ?? 'pl';
        $this->date_format = $preferences['date_format'] ?? 'd.m.Y';
        $this->timezone = $preferences['timezone'] ?? 'Europe/Warsaw';

        // Load notification settings
        $notifications = $user->notification_settings ?? [];
        $this->email_notifications = $notifications['email'] ?? true;
        $this->browser_notifications = $notifications['browser'] ?? true;
        $this->mobile_notifications = $notifications['mobile'] ?? false;
        $this->marketing_emails = $user->marketing_accepted ?? false;
    }

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function updatedAvatar()
    {
        $this->validateOnly('avatar');
    }

    public function updatedCurrentPassword()
    {
        if (!empty($this->current_password)) {
            if (!Hash::check($this->current_password, Auth::user()->password)) {
                $this->addError('current_password', 'Obecne hasło jest nieprawidłowe.');
            } else {
                $this->resetErrorBag('current_password');
            }
        }
    }

    public function updatedPassword()
    {
        if (!empty($this->password)) {
            $this->validateOnly('password');
        }
    }

    public function togglePasswordSection()
    {
        $this->showPasswordSection = !$this->showPasswordSection;
        
        // Reset password fields when hiding section
        if (!$this->showPasswordSection) {
            $this->current_password = '';
            $this->password = '';
            $this->password_confirmation = '';
            $this->resetErrorBag(['current_password', 'password']);
        }
    }

    public function removeAvatar()
    {
        $user = Auth::user();
        
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
            $this->current_avatar = null;
            
            session()->flash('success', 'Zdjęcie profilowe zostało usunięte.');
        }
    }

    public function save()
    {
        $this->loading = true;

        $this->validate();

        try {
            $user = Auth::user();
            
            // Handle avatar upload
            $avatarPath = $this->current_avatar;
            if ($this->avatar) {
                // Delete old avatar
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                // Store new avatar
                $avatarPath = $this->avatar->store('avatars', 'public');
            }

            // Update user data
            $userData = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'company' => $this->company,
                'position' => $this->position,
                'phone' => $this->phone,
                'avatar' => $avatarPath,
                'marketing_accepted' => $this->marketing_emails
            ];

            // Handle password change
            if ($this->showPasswordSection && !empty($this->current_password) && !empty($this->password)) {
                if (!Hash::check($this->current_password, $user->password)) {
                    $this->addError('current_password', 'Obecne hasło jest nieprawidłowe.');
                    $this->loading = false;
                    return;
                }
                
                $userData['password'] = Hash::make($this->password);
                
                // Clear password fields after successful change
                $this->current_password = '';
                $this->password = '';
                $this->password_confirmation = '';
                $this->showPasswordSection = false;
            }

            // Update preferences
            $preferences = [
                'theme' => $this->theme,
                'language' => $this->language,
                'date_format' => $this->date_format,
                'timezone' => $this->timezone
            ];

            // Update notification settings
            $notificationSettings = [
                'email' => $this->email_notifications,
                'browser' => $this->browser_notifications,
                'mobile' => $this->mobile_notifications
            ];

            $userData['preferences'] = $preferences;
            $userData['notification_settings'] = $notificationSettings;

            // Update user
            $user->update($userData);

            // Update current avatar property
            $this->current_avatar = $avatarPath;

            // Clear uploaded avatar from component
            $this->avatar = null;

            // Log profile update
            \Log::info('User profile updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'changes' => array_keys($userData),
                'ip' => request()->ip(),
                'timestamp' => now()
            ]);

            session()->flash('success', 'Profil został zaktualizowany pomyślnie.');

        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            session()->flash('error', 'Wystąpił błąd podczas aktualizacji profilu.');
        }

        $this->loading = false;
    }

    public function getPasswordStrengthProperty()
    {
        if (empty($this->password)) {
            return 0;
        }

        $score = 0;
        
        // Length
        if (strlen($this->password) >= 8) $score++;
        if (strlen($this->password) >= 12) $score++;
        
        // Character types
        if (preg_match('/[a-z]/', $this->password)) $score++;
        if (preg_match('/[A-Z]/', $this->password)) $score++;
        if (preg_match('/[0-9]/', $this->password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $this->password)) $score++;

        return min($score, 5);
    }

    public function getPasswordStrengthTextProperty()
    {
        $strength = $this->passwordStrength;
        
        switch ($strength) {
            case 0:
            case 1:
                return 'Bardzo słabe';
            case 2:
                return 'Słabe';
            case 3:
                return 'Średnie';
            case 4:
                return 'Silne';
            case 5:
                return 'Bardzo silne';
            default:
                return 'Nieznane';
        }
    }

    public function getPasswordStrengthColorProperty()
    {
        $strength = $this->passwordStrength;
        
        switch ($strength) {
            case 0:
            case 1:
                return 'text-red-600 bg-red-100';
            case 2:
                return 'text-orange-600 bg-orange-100';
            case 3:
                return 'text-yellow-600 bg-yellow-100';
            case 4:
                return 'text-blue-600 bg-blue-100';
            case 5:
                return 'text-green-600 bg-green-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    public function render()
    {
        return view('livewire.profile.edit-profile')
            ->layout('layouts.app', [
                'title' => 'Profil użytkownika - PPM'
            ]);
    }
}