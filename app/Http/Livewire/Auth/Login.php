<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Login extends Component
{
    // Form properties
    public $email = '';
    public $password = '';
    public $remember = false;
    
    // State management
    public $loading = false;
    public $rateLimited = false;
    public $remainingTime = 0;
    
    protected $rules = [
        'email' => 'required|email|max:255',
        'password' => 'required|min:6|max:255',
        'remember' => 'boolean'
    ];

    protected $messages = [
        'email.required' => 'Email jest wymagany.',
        'email.email' => 'Wprowadź poprawny adres email.',
        'password.required' => 'Hasło jest wymagane.',
        'password.min' => 'Hasło musi mieć co najmniej 6 znaków.'
    ];

    public function mount()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            return $this->redirectBasedOnRole();
        }
        
        // Check if rate limited
        $this->checkRateLimit();
    }

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function updatedPassword()
    {
        $this->validateOnly('password');
    }

    public function login()
    {
        $this->loading = true;
        
        // Check rate limit
        if ($this->checkRateLimit()) {
            $this->loading = false;
            return;
        }

        $this->validate();

        $throttleKey = Str::lower($this->email).'|'.request()->ip();
        
        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->handleRateLimit($throttleKey);
            $this->loading = false;
            return;
        }

        $credentials = [
            'email' => $this->email,
            'password' => $this->password
        ];

        if (Auth::attempt($credentials, $this->remember)) {
            RateLimiter::clear($throttleKey);
            
            // Log successful login
            $this->logLoginAttempt(true);
            
            // Regenerate session
            request()->session()->regenerate();
            
            $this->loading = false;
            
            // Role-based redirect
            return $this->redirectBasedOnRole();
        }

        // Failed login - increment attempts
        RateLimiter::hit($throttleKey);
        $this->logLoginAttempt(false);
        
        $this->loading = false;
        
        throw ValidationException::withMessages([
            'email' => ['Nieprawidłowe dane logowania.'],
        ]);
    }

    private function checkRateLimit()
    {
        $throttleKey = Str::lower($this->email).'|'.request()->ip();
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->handleRateLimit($throttleKey);
            return true;
        }
        
        $this->rateLimited = false;
        return false;
    }

    private function handleRateLimit($throttleKey)
    {
        $this->rateLimited = true;
        $this->remainingTime = RateLimiter::availableIn($throttleKey);
        
        $this->addError('email', 
            'Zbyt wiele prób logowania. Spróbuj ponownie za ' . 
            gmdate('i:s', $this->remainingTime) . '.'
        );
    }

    private function redirectBasedOnRole()
    {
        $user = Auth::user();
        
        // Admin redirect
        if ($user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        // Manager redirect  
        if ($user->hasRole('Manager')) {
            return redirect()->route('manager.dashboard');
        }
        
        // Editor redirect
        if ($user->hasRole('Editor')) {
            return redirect()->route('products.index');
        }
        
        // Warehouseman redirect
        if ($user->hasRole('Warehouseman')) {
            return redirect()->route('warehouse.dashboard');
        }
        
        // Salesperson redirect
        if ($user->hasRole('Salesperson')) {
            return redirect()->route('sales.dashboard');
        }
        
        // Claims redirect
        if ($user->hasRole('Claims')) {
            return redirect()->route('claims.dashboard');
        }
        
        // Default User redirect
        return redirect()->route('dashboard');
    }

    private function logLoginAttempt($success)
    {
        // Log activity - will be enhanced with audit system
        \Log::info('Login attempt', [
            'email' => $this->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'success' => $success,
            'timestamp' => now()
        ]);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.auth', [
                'title' => 'Logowanie - PPM'
            ]);
    }
}