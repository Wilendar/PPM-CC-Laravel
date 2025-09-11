<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPassword extends Component
{
    public $email = '';
    public $loading = false;
    public $emailSent = false;

    protected $rules = [
        'email' => 'required|email|exists:users,email'
    ];

    protected $messages = [
        'email.required' => 'Email jest wymagany.',
        'email.email' => 'Wprowadź poprawny adres email.',
        'email.exists' => 'Nie znaleziono konta z tym adresem email.'
    ];

    public function updatedEmail()
    {
        $this->validateOnly('email');
        $this->emailSent = false;
    }

    public function sendResetLink()
    {
        $this->loading = true;
        
        $this->validate();

        try {
            // Send password reset email
            $status = Password::sendResetLink(['email' => $this->email]);

            if ($status === Password::RESET_LINK_SENT) {
                $this->emailSent = true;
                
                // Log password reset request
                \Log::info('Password reset requested', [
                    'email' => $this->email,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()
                ]);

                session()->flash('success', 
                    'Link do resetowania hasła został wysłany na Twój adres email.'
                );
            } else {
                throw ValidationException::withMessages([
                    'email' => ['Nie udało się wysłać linku resetującego hasło. Spróbuj ponownie.']
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Password reset failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            $this->addError('email', 'Wystąpił błąd. Spróbuj ponownie później.');
        }

        $this->loading = false;
    }

    public function resendEmail()
    {
        $this->emailSent = false;
        $this->sendResetLink();
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.auth', [
                'title' => 'Resetowanie hasła - PPM'
            ]);
    }
}