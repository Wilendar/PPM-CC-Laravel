@extends('layouts.auth')

@section('title', 'Logowanie do PPM')

@section('content')
{{-- Error Messages --}}
@if($errors->any())
    <div class="mb-4">
        @foreach($errors->all() as $error)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-2">
                {{ $error }}
            </div>
        @endforeach
    </div>
@endif

@if(session('error'))
    <div class="mb-4">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    </div>
@endif

{{-- Login Form --}}
<form method="POST" action="{{ route('login.store') }}" class="space-y-6" id="loginForm">
    @csrf
    
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Adres email
        </label>
        <div class="relative">
            <input 
                id="email"
                name="email" 
                type="email" 
                autocomplete="email" 
                required 
                value="{{ old('email') }}"
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-ppm-primary focus:border-ppm-primary sm:text-sm @error('email') border-red-300 @enderror" 
                placeholder="wprowadź adres email"
            >
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
            </div>
        </div>
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Hasło
        </label>
        <div class="relative">
            <input 
                id="password"
                name="password" 
                type="password" 
                autocomplete="current-password" 
                required 
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-ppm-primary focus:border-ppm-primary sm:text-sm @error('password') border-red-300 @enderror" 
                placeholder="wprowadź hasło"
            >
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input 
                id="remember" 
                name="remember" 
                type="checkbox" 
                class="h-4 w-4 text-ppm-primary focus:ring-ppm-primary border-gray-300 rounded"
                value="1"
            >
            <label for="remember" class="ml-2 block text-sm text-gray-700">
                Zapamiętaj mnie
            </label>
        </div>
    </div>

    <div>
        <button 
            type="submit" 
            id="loginButton"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-ppm-primary hover:bg-ppm-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ppm-primary transition duration-150 ease-in-out"
        >
            <span id="buttonText">Zaloguj się</span>
            <svg id="loadingSpinner" class="hidden ml-2 -mr-1 w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>
    
    {{-- Debug Info --}}
    <div class="mt-4 text-xs text-gray-500 text-center">
        <p>Demo: admin@mpptrade.pl / Admin123!MPP</p>
    </div>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const button = document.getElementById('loginButton');
    const buttonText = document.getElementById('buttonText');
    const spinner = document.getElementById('loadingSpinner');
    
    // Show loading state
    buttonText.textContent = 'Logowanie...';
    spinner.classList.remove('hidden');
    button.disabled = true;
    
    // Debug info
    console.log('Form submitted');
    console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    console.log('Form action:', this.action);
    
    // Allow form to submit naturally
    return true;
});
</script>
@endsection

