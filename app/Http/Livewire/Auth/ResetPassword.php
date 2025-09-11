<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ResetPassword extends Component
{
    public $token;
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $loading = false;

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    protected $messages = [
        'email.required' => 'Email jest wymagany.',
        'email.email' => 'Wprowadź poprawny adres email.',
        'password.required' => 'Hasło jest wymagane.',
        'password.confirmed' => 'Hasła muszą być identyczne.',
    ];

    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');

        // Redirect if already logged in
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
    }

    public function updatedPassword()
    {
        $this->validateOnly('password');
    }

    public function updatedPasswordConfirmation()
    {
        $this->validateOnly('password');
    }

    public function resetPassword()
    {
        $this->loading = true;

        $this->validate();

        try {
            // Attempt to reset the user's password
            $status = Password::reset(
                [
                    'email' => $this->email,
                    'password' => $this->password,
                    'password_confirmation' => $this->password_confirmation,
                    'token' => $this->token
                ],
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));

                    // Log password reset success
                    \Log::info('Password reset successful', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => request()->ip(),
                        'timestamp' => now()
                    ]);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                session()->flash('success', 
                    'Hasło zostało zmienione pomyślnie. Zostałeś automatycznie zalogowany.'
                );

                // Auto-login user after successful password reset
                $user = \App\Models\User::where('email', $this->email)->first();
                Auth::login($user);

                $this->loading = false;
                
                return redirect()->route('dashboard');
            } else {
                $this->handleResetError($status);
            }

        } catch (\Exception $e) {
            \Log::error('Password reset failed', [
                'email' => $this->email,
                'token' => $this->token,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            $this->addError('password', 'Wystąpił błąd podczas resetowania hasła. Spróbuj ponownie.');
        }

        $this->loading = false;
    }

    private function handleResetError($status)
    {
        switch ($status) {
            case Password::INVALID_USER:
                $this->addError('email', 'Nie znaleziono użytkownika z tym adresem email.');
                break;
            case Password::INVALID_TOKEN:
                $this->addError('token', 'Link resetujący hasło jest nieprawidłowy lub wygasł. Poproś o nowy link.');
                break;
            default:
                $this->addError('password', 'Nie udało się zresetować hasła. Spróbuj ponownie.');
                break;
        }
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
        return view('livewire.auth.reset-password')
            ->layout('layouts.auth', [
                'title' => 'Nowe hasło - PPM'
            ]);
    }
}