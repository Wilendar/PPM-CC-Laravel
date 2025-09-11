<div class="admin-theme-customization">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Customizacja Panelu Admin
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Dostosuj wygląd i zachowanie panelu administracyjnego
            </p>
        </div>
        
        <div class="flex space-x-2">
            <button wire:click="togglePreview" 
                    class="btn {{ $previewMode ? 'btn-secondary' : 'btn-outline' }}">
                @if($previewMode)
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Podgląd ON
                @else
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.72 6.72m3.158 3.158l4.242 4.242M12 5c4.478 0 8.268 2.943 9.542 7a10.085 10.085 0 01-1.563 3.029"></path>
                    </svg>
                    Podgląd OFF
                @endif
            </button>
            
            <button wire:click="resetToDefault" 
                    class="btn btn-secondary"
                    onclick="return confirm('Czy na pewno zresetować do domyślnego motywu?')">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reset
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('message') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Kategorie</h3>
                
                <nav class="space-y-2">
                    <button wire:click="switchTab('colors')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'colors' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                        </svg>
                        Kolory
                    </button>
                    
                    <button wire:click="switchTab('layout')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'layout' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        Layout
                    </button>
                    
                    <button wire:click="switchTab('branding')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'branding' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Branding
                    </button>
                    
                    <button wire:click="switchTab('widgets')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'widgets' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                        </svg>
                        Widgety
                    </button>
                    
                    <button wire:click="switchTab('css')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'css' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                        Custom CSS
                    </button>
                    
                    <button wire:click="switchTab('themes')" 
                            class="w-full text-left px-3 py-2 rounded-lg transition-colors
                                   {{ $activeTab === 'themes' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Motywy
                    </button>
                </nav>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                
                <!-- Colors Tab -->
                @if($activeTab === 'colors')
                    @include('livewire.admin.customization.partials.colors-tab')
                @endif

                <!-- Layout Tab -->
                @if($activeTab === 'layout')
                    @include('livewire.admin.customization.partials.layout-tab')
                @endif

                <!-- Branding Tab -->
                @if($activeTab === 'branding')
                    @include('livewire.admin.customization.partials.branding-tab')
                @endif

                <!-- Widgets Tab -->
                @if($activeTab === 'widgets')
                    @include('livewire.admin.customization.partials.widgets-tab')
                @endif

                <!-- Custom CSS Tab -->
                @if($activeTab === 'css')
                    @include('livewire.admin.customization.partials.css-tab')
                @endif

                <!-- Themes Management Tab -->
                @if($activeTab === 'themes')
                    @include('livewire.admin.customization.partials.themes-tab')
                @endif
            </div>
        </div>
    </div>

    <!-- Preview Overlay -->
    @if($previewMode)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
             x-data="{ open: true }"
             x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full h-5/6 m-4 overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-medium">Podgląd Motywu</h3>
                    <button wire:click="togglePreview" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="h-full overflow-auto p-4">
                    <div class="theme-preview-container">
                        <style>
                            {!! $this->getCssPreview() !!}
                        </style>
                        
                        <!-- Mock Admin Interface -->
                        @include('livewire.admin.customization.partials.theme-preview')
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span>Ładowanie...</span>
        </div>
    </div>
</div>

@push('styles')
<style>
    .admin-theme-customization {
        min-height: calc(100vh - 200px);
    }
    
    .color-picker {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 50px;
        height: 50px;
        border: none;
        cursor: pointer;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .color-picker::-webkit-color-swatch-wrapper {
        padding: 0;
    }
    
    .color-picker::-webkit-color-swatch {
        border: none;
        border-radius: 50%;
    }
    
    .theme-preview-container {
        transform: scale(0.8);
        transform-origin: top left;
        width: 125%;
        height: 125%;
        overflow: hidden;
    }
    
    @media (max-width: 1024px) {
        .theme-preview-container {
            transform: scale(0.6);
            width: 166%;
            height: 166%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Real-time theme preview updates
    window.addEventListener('themeColorsUpdated', function() {
        // Apply colors immediately dla preview
        document.documentElement.style.setProperty('--primary-color', @this.primaryColor);
        document.documentElement.style.setProperty('--secondary-color', @this.secondaryColor);
        document.documentElement.style.setProperty('--accent-color', @this.accentColor);
    });
    
    window.addEventListener('themeLayoutUpdated', function() {
        // Apply layout changes dla preview
        const sidebar = document.querySelector('.admin-sidebar');
        const content = document.querySelector('.admin-content');
        
        if (sidebar) {
            sidebar.className = sidebar.className.replace(/layout-\w+/g, '') + ' layout-' + @this.layoutDensity;
        }
    });
    
    // Handle color picker changes
    document.addEventListener('DOMContentLoaded', function() {
        const colorPickers = document.querySelectorAll('.color-picker');
        colorPickers.forEach(picker => {
            picker.addEventListener('change', function() {
                @this.set(this.getAttribute('wire:model'), this.value);
            });
        });
    });
</script>
@endpush