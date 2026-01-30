<div class="min-h-screen bg-gray-900">
    {{-- Header --}}
    <div class="border-b border-gray-700 bg-gray-800/50">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-white">Zarzadzanie dostawcami</h1>
                    <p class="text-sm text-gray-400 mt-1">Dostawcy, producenci i importerzy produktow</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-gray-700 bg-gray-800/30">
        <div class="px-4 sm:px-6 lg:px-8">
            <nav class="supplier-panel__tabs" aria-label="Tabs">
                @foreach($tabs as $tabKey => $tab)
                    <button wire:click="switchTab('{{ $tabKey }}')"
                            wire:key="tab-{{ $tabKey }}"
                            class="supplier-panel__tab {{ $activeTab === $tabKey ? 'supplier-panel__tab--active' : '' }}">
                        <div class="flex items-center gap-2">
                            @switch($tab['icon'] ?? $tabKey)
                                @case('truck')
                                    {{-- Dostawca --}}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                    </svg>
                                    @break
                                @case('building')
                                    {{-- Producent --}}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    @break
                                @case('globe')
                                    {{-- Importer --}}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    @break
                                @case('question-mark')
                                    {{-- Brak --}}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @break
                                @default
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                            @endswitch
                            <span>{{ $tab['label'] }}</span>
                            @if(isset($tab['count']) && $tab['count'] > 0)
                                <span class="supplier-panel__tab-badge">{{ $tab['count'] }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        @if($activeTab === 'brak')
            @include('livewire.admin.suppliers.partials.brak-tab')
        @else
            <div class="supplier-panel__layout">
                {{-- Sidebar - Entity List (25%) --}}
                <div class="supplier-panel__sidebar">
                    @include('livewire.admin.suppliers.partials.entity-list')
                </div>

                {{-- Main Content (75%) --}}
                <div class="supplier-panel__main">
                    @if($selectedEntityId && $this->selectedEntity)
                        @include('livewire.admin.suppliers.partials.entity-form')

                        <div class="mt-6">
                            @include('livewire.admin.suppliers.partials.product-table')
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="supplier-panel__empty-state">
                            <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                            </svg>
                            <p class="text-gray-400 text-sm">Wybierz podmiot z listy po lewej stronie</p>
                            <p class="text-gray-500 text-xs mt-1">lub dodaj nowy klikajac przycisk powyzej</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Create Modal --}}
    @include('livewire.admin.suppliers.partials.create-modal')

    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="supplier-panel__flash supplier-panel__flash--success">
            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="supplier-panel__flash supplier-panel__flash--error">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif
</div>
