<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ustawienia Systemowe</h1>
            <p class="text-gray-600">Konfiguracja głównych parametrów aplikacji</p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    wire:click="resetCategoryToDefaults" 
                    wire:confirm="Czy na pewno chcesz przywrócić wartości domyślne dla tej kategorii?"
                    class="px-4 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-undo mr-2"></i>Resetuj do domyślnych
            </button>
            
            <button type="button" 
                    wire:click="saveSettings" 
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                <i class="fas fa-save mr-2" wire:loading.remove></i>
                <i class="fas fa-spinner fa-spin mr-2" wire:loading></i>
                Zapisz ustawienia
            </button>
        </div>
    </div>

    <!-- Messages -->
    @if($message)
    <div class="mb-6">
        <div class="p-4 rounded-md {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : ($messageType === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-blue-50 text-blue-800 border border-blue-200') }}">
            <div class="flex">
                <div class="flex-shrink-0">
                    @if($messageType === 'success')
                        <i class="fas fa-check-circle"></i>
                    @elseif($messageType === 'error')
                        <i class="fas fa-exclamation-circle"></i>
                    @else
                        <i class="fas fa-info-circle"></i>
                    @endif
                </div>
                <div class="ml-3">
                    {{ $message }}
                </div>
                <div class="ml-auto pl-3">
                    <button wire:click="resetMessages" class="text-sm underline">Zamknij</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Loading overlay -->
    <div wire:loading.flex wire:target="loadSettings" class="fixed inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <i class="fas fa-spinner fa-spin text-2xl text-blue-600 mb-2"></i>
            <p class="text-gray-700">Ładowanie ustawień...</p>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg">
        <div class="flex border-b border-gray-200">
            <!-- Category tabs -->
            <nav class="flex flex-col w-64 space-y-1 p-4 bg-gray-50 rounded-l-lg">
                @foreach($categories as $category => $label)
                <button type="button" 
                        wire:click="switchCategory('{{ $category }}')"
                        class="flex items-center px-3 py-2 text-sm rounded-md transition-colors {{ $activeCategory === $category ? 'bg-blue-100 text-blue-700 border-r-2 border-blue-500' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="mr-3 {{ $this->getCategoryIcon($category) }}"></i>
                    {{ $label }}
                </button>
                @endforeach
                
                @if($activeCategory === 'email')
                <button type="button" 
                        wire:click="testEmailConnection"
                        class="flex items-center px-3 py-2 mt-4 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors">
                    <i class="fas fa-envelope-open mr-2"></i>
                    Test połączenia
                </button>
                @endif
            </nav>

            <!-- Settings content -->
            <div class="flex-1 p-6">
                <div class="space-y-6">
                    @forelse($categorySettings as $key => $setting)
                    <div class="border-b border-gray-100 pb-4 last:border-b-0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $setting['label'] }}
                            @if($setting['required'] ?? false)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        
                        @if($setting['description'] ?? false)
                        <p class="text-xs text-gray-500 mb-3">{{ $setting['description'] }}</p>
                        @endif
                        
                        <!-- Input based on type -->
                        @switch($setting['type'])
                            @case('string')
                            @case('email')
                                <input type="{{ $setting['type'] === 'email' ? 'email' : 'text' }}" 
                                       wire:model.lazy="tempValues.{{ $key }}"
                                       placeholder="{{ $setting['placeholder'] ?? $setting['value'] }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       @if(isset($setting['max'])) maxlength="{{ $setting['max'] }}" @endif>
                                @break
                            
                            @case('password')
                                <input type="password" 
                                       wire:model.lazy="tempValues.{{ $key }}"
                                       placeholder="{{ $setting['placeholder'] ?? '********' }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @break
                            
                            @case('integer')
                                <input type="number" 
                                       wire:model.lazy="tempValues.{{ $key }}"
                                       value="{{ $setting['value'] }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       @if(isset($setting['min'])) min="{{ $setting['min'] }}" @endif
                                       @if(isset($setting['max'])) max="{{ $setting['max'] }}" @endif>
                                @break
                            
                            @case('boolean')
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           wire:model.lazy="tempValues.{{ $key }}"
                                           {{ $setting['value'] ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <span class="ml-2 text-sm text-gray-700">Włączone</span>
                                </label>
                                @break
                            
                            @case('select')
                                <select wire:model.lazy="tempValues.{{ $key }}" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    @foreach($setting['options'] as $value => $label)
                                    <option value="{{ $value }}" {{ $setting['value'] == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @break
                            
                            @case('file')
                                <div class="space-y-3">
                                    @if($setting['value'])
                                    <div class="flex items-center space-x-3">
                                        <img src="{{ $setting['value'] }}" alt="Current file" class="w-20 h-20 object-cover rounded-lg border">
                                        <div class="text-sm text-gray-600">
                                            <p>Aktualny plik</p>
                                            <a href="{{ $setting['value'] }}" target="_blank" class="text-blue-600 hover:underline">Podgląd</a>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <input type="file" 
                                           wire:model="uploadFiles.{{ $key }}"
                                           accept="{{ $setting['accept'] ?? '*' }}"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                @break
                        @endswitch
                        
                        <!-- Current value display -->
                        @if(!in_array($setting['type'], ['boolean', 'file']) && $setting['value'])
                        <div class="mt-2 text-xs text-gray-500">
                            Aktualna wartość: 
                            <span class="font-mono bg-gray-100 px-2 py-1 rounded">
                                @if($setting['type'] === 'password')
                                    ********
                                @else
                                    {{ is_array($setting['value']) ? json_encode($setting['value']) : $setting['value'] }}
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <i class="fas fa-cog text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500">Brak ustawień w tej kategorii</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-hide messages after 5 seconds
    document.addEventListener('livewire:load', function () {
        Livewire.on('messageShown', () => {
            setTimeout(() => {
                Livewire.emit('resetMessages');
            }, 5000);
        });
    });
</script>
@endpush

@php
    // Helper method for category icons
    function getCategoryIcon($category) {
        return match($category) {
            'general' => 'fas fa-cogs',
            'security' => 'fas fa-shield-alt',
            'product' => 'fas fa-box',
            'email' => 'fas fa-envelope',
            'integration' => 'fas fa-plug',
            'backup' => 'fas fa-database',
            'maintenance' => 'fas fa-wrench',
            'ui' => 'fas fa-palette',
            default => 'fas fa-cog'
        };
    }
@endphp