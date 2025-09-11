<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div>
            <div class="mx-auto h-12 w-auto flex justify-center">
                <h1 class="text-3xl font-bold text-gray-900">PPM</h1>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Zaloguj się do swojego konta
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Prestashop Product Manager - System zarządzania produktami
            </p>
        </div>

        {{-- Login Form --}}
        <form wire:submit.prevent="login" class="mt-8 space-y-6">
            <div class="rounded-md shadow-sm -space-y-px">
                {{-- Email Field --}}
                <div>
                    <label for="email" class="sr-only">Adres email</label>
                    <input 
                        wire:model.lazy="email"
                        id="email"
                        name="email" 
                        type="email" 
                        autocomplete="email" 
                        required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border 
                               @error('email') border-red-300 @else border-gray-300 @enderror
                               placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none 
                               focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Adres email"
                        @error('email') aria-invalid="true" @enderror
                    >
                    @error('email')
                        <div class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</div>
                    @enderror
                </div>
                
                {{-- Password Field --}}
                <div>
                    <label for="password" class="sr-only">Hasło</label>
                    <input 
                        wire:model.lazy="password"
                        id="password"
                        name="password" 
                        type="password" 
                        autocomplete="current-password" 
                        required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border 
                               @error('password') border-red-300 @else border-gray-300 @enderror
                               placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none 
                               focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Hasło"
                        @error('password') aria-invalid="true" @enderror
                    >
                    @error('password')
                        <div class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Remember Me & Forgot Password --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input 
                        wire:model="remember"
                        id="remember-me" 
                        name="remember-me" 
                        type="checkbox" 
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Zapamiętaj mnie
                    </label>
                </div>

                <div class="text-sm">
                    <a href="{{ route('password.request') }}" 
                       class="font-medium text-blue-600 hover:text-blue-500">
                        Zapomniałeś hasła?
                    </a>
                </div>
            </div>

            {{-- Submit Button --}}
            <div>
                <button 
                    type="submit" 
                    @if($loading || $rateLimited) disabled @endif
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                           text-sm font-medium rounded-md text-white 
                           @if($loading || $rateLimited) 
                               bg-gray-400 cursor-not-allowed 
                           @else 
                               bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                           @endif 
                           focus:outline-none transition duration-150 ease-in-out"
                >
                    {{-- Loading Spinner --}}
                    @if($loading)
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Logowanie...
                    @elseif($rateLimited)
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Zablokowany ({{ gmdate('i:s', $remainingTime) }})
                    @else
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Zaloguj się
                    @endif
                </button>
            </div>

            {{-- Register Link --}}
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Nie masz konta? 
                    <a href="{{ route('register') }}" 
                       class="font-medium text-blue-600 hover:text-blue-500">
                        Zarejestruj się
                    </a>
                </p>
            </div>
        </form>

        {{-- Rate Limit Warning --}}
        @if($rateLimited)
            <div class="rounded-md bg-yellow-50 p-4 mt-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Konto tymczasowo zablokowane
                        </h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            Ze względów bezpieczeństwa, konto zostało tymczasowo zablokowane po zbyt wielu nieudanych próbach logowania. 
                            Spróbuj ponownie za <strong>{{ gmdate('i:s', $remainingTime) }}</strong>.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Loading Overlay --}}
        <div wire:loading.flex wire:target="login" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-3 text-gray-900">Sprawdzanie danych logowania...</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-refresh rate limit timer --}}
    @if($rateLimited && $remainingTime > 0)
        <script>
            setTimeout(() => {
                @this.checkRateLimit();
            }, 1000);
        </script>
    @endif
</div>