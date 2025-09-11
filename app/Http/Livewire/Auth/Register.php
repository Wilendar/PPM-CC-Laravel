<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules;

class Register extends Component
{
    // Personal Information
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    
    // Company Information
    public $company = '';
    public $position = '';
    public $phone = '';
    
    // Terms and Privacy
    public $terms_accepted = false;
    public $privacy_accepted = false;
    public $marketing_accepted = false;
    
    // State management
    public $loading = false;
    public $step = 1; // Multi-step registration
    public $maxSteps = 3;
    
    protected function rules()
    {
        return [
            // Step 1: Personal Information
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            
            // Step 2: Company Information (optional)
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            
            // Step 3: Legal agreements
            'terms_accepted' => 'accepted',
            'privacy_accepted' => 'accepted',
            'marketing_accepted' => 'boolean'
        ];
    }

    protected $messages = [
        'first_name.required' => 'Imię jest wymagane.',
        'last_name.required' => 'Nazwisko jest wymagane.',
        'email.required' => 'Email jest wymagany.',
        'email.email' => 'Wprowadź poprawny adres email.',
        'email.unique' => 'Ten adres email jest już zarejestrowany.',
        'password.required' => 'Hasło jest wymagane.',
        'password.confirmed' => 'Hasła muszą być identyczne.',
        'terms_accepted.accepted' => 'Musisz zaakceptować regulamin.',
        'privacy_accepted.accepted' => 'Musisz zaakceptować politykę prywatności.'
    ];

    public function mount()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
    }

    // Real-time validation
    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function updatedPassword()
    {
        $this->validateOnly('password');
    }

    public function updatedPasswordConfirmation()
    {
        $this->validateOnly('password');
    }

    // Multi-step navigation
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->step < $this->maxSteps) {
            $this->step++;
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    private function validateCurrentStep()
    {
        switch ($this->step) {
            case 1:
                $this->validate([
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]);
                break;
            case 2:
                $this->validate([
                    'company' => 'nullable|string|max:255',
                    'position' => 'nullable|string|max:255',
                    'phone' => 'nullable|string|max:20',
                ]);
                break;
            case 3:
                $this->validate([
                    'terms_accepted' => 'accepted',
                    'privacy_accepted' => 'accepted',
                ]);
                break;
        }
    }

    public function register()
    {
        $this->loading = true;
        
        // Validate all fields
        $this->validate();

        try {
            // Determine default role based on company domain
            $defaultRole = $this->determineDefaultRole();
            
            // Create user
            $user = User::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'company' => $this->company,
                'position' => $this->position,
                'phone' => $this->phone,
                'email_verified_at' => null, // Will be verified via email
                'marketing_accepted' => $this->marketing_accepted,
            ]);

            // Assign default role
            $user->assignRole($defaultRole);

            // Fire registered event (for email verification)
            event(new Registered($user));

            // Log registration
            \Log::info('User registered', [
                'email' => $this->email,
                'company' => $this->company,
                'role' => $defaultRole,
                'ip' => request()->ip(),
                'timestamp' => now()
            ]);

            // Auto-login user
            Auth::login($user, true);

            session()->flash('success', 
                'Konto zostało utworzone pomyślnie! Sprawdź swoją skrzynkę email w celu weryfikacji.'
            );

            $this->loading = false;

            // Redirect to dashboard
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            $this->loading = false;
            
            \Log::error('User registration failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            session()->flash('error', 
                'Wystąpił błąd podczas rejestracji. Spróbuj ponownie.'
            );
        }
    }

    private function determineDefaultRole()
    {
        $emailDomain = strtolower(substr(strrchr($this->email, "@"), 1));
        
        // Company domain role mapping
        $domainRoleMap = [
            'mpptrade.eu' => 'Manager',
            'mpptrade.pl' => 'Manager',
            'mpptrade.com' => 'Editor'
        ];

        // Check if email domain matches company domains
        if (isset($domainRoleMap[$emailDomain])) {
            return $domainRoleMap[$emailDomain];
        }

        // Default role for external users
        return 'User';
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
        return view('livewire.auth.register')
            ->layout('layouts.auth', [
                'title' => 'Rejestracja - PPM'
            ]);
    }
}