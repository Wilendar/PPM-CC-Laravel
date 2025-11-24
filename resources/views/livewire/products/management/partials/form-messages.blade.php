{{-- Messages --}}
@if (session()->has('message'))
    <div class="alert-dark-success flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        {{ session('message') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="alert-dark-error flex items-center">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ session('error') }}
    </div>
@endif

@if($successMessage)
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="alert-dark-success flex items-center justify-between">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ $successMessage }}
        </div>
        <button @click="show = false" class="ml-4">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif
