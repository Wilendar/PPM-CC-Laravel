<div class="notification-center">
    <!-- Notification Bell Button -->
    <div class="relative">
        <button @click="$wire.toggleDropdown()" 
                class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-5 5-5-5h5V7a3 3 0 016 0v10z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <!-- Dropdown -->
        @if($showDropdown)
            <div class="absolute right-0 mt-2 w-96 bg-gray-800 border border-gray-200 rounded-lg shadow-xl z-50"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">Powiadomienia</h3>
                        @if($unreadCount > 0)
                            <button wire:click="markAllAsRead" 
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                Oznacz wszystkie jako przeczytane
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Nieprzeczytane: <strong>{{ $unreadCount }}</strong></span>
                        <span class="text-gray-600">Krytyczne: <strong>{{ $criticalNotifications->count() }}</strong></span>
                    </div>
                </div>

                <!-- Recent Notifications List -->
                <div class="max-h-64 overflow-y-auto">
                    @forelse($recentNotifications as $notification)
                        <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer
                                    {{ !$notification->is_read ? 'bg-blue-50' : '' }}"
                             wire:click="markAsRead({{ $notification->id }})">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <i class="{{ $notification->type_icon }} {{ $notification->priority_color }} text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">
                                        {{ $notification->title }}
                                    </p>
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        {{ $notification->message }}
                                    </p>
                                    <div class="flex items-center mt-1 space-x-2">
                                        <span class="text-xs text-gray-500">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        @if(!$notification->is_read)
                                            <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-bell-slash text-2xl mb-2"></i>
                            <p>Brak powiadomień</p>
                        </div>
                    @endforelse
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 border-t border-gray-200">
                    <a href="{{ route('admin.notifications.index') }}" 
                       class="block w-full text-center text-sm text-blue-600 hover:text-blue-800">
                        Zobacz wszystkie powiadomienia
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Full Notification Management Page -->
    @if(request()->routeIs('admin.notifications.*'))
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Centrum powiadomień</h1>
                    <p class="text-gray-600">Zarządzaj powiadomieniami systemowymi</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($unreadCount > 0)
                        <button wire:click="markAllAsRead" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-check-double mr-2"></i>
                            Oznacz wszystkie jako przeczytane
                        </button>
                    @endif
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-bell text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Wszystkie</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['total'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <i class="fas fa-envelope text-orange-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Nieprzeczytane</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['unread'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Krytyczne</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['critical'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-shield-alt text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Bezpieczeństwo</p>
                            <p class="text-2xl font-bold text-white">{{ $statistics['by_type']['security'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Tabs -->
            <div class="bg-gray-800 rounded-lg shadow">
                <div class="border-b border-gray-200">
                    <!-- Tabs -->
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button wire:click="setActiveTab('all')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Wszystkie
                        </button>
                        <button wire:click="setActiveTab('unread')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'unread' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Nieprzeczytane ({{ $statistics['unread'] ?? 0 }})
                        </button>
                        <button wire:click="setActiveTab('critical')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'critical' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Krytyczne
                        </button>
                        <button wire:click="setActiveTab('system')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'system' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            System
                        </button>
                        <button wire:click="setActiveTab('security')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Bezpieczeństwo
                        </button>
                        <button wire:click="setActiveTab('integration')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'integration' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Integracje
                        </button>
                    </nav>
                </div>

                <!-- Filters -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Type Filter -->
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Typ:</label>
                            <select wire:model="selectedType" class="border-gray-300 rounded-md text-sm">
                                <option value="">Wszystkie typy</option>
                                @foreach($this->getNotificationTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Priority Filter -->
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Priorytet:</label>
                            <select wire:model="selectedPriority" class="border-gray-300 rounded-md text-sm">
                                <option value="">Wszystkie priorytety</option>
                                @foreach($this->getPriorities() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Unread Filter -->
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="showOnlyUnread" class="rounded border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Tylko nieprzeczytane</span>
                        </label>

                        <!-- Clear Filters -->
                        @if($selectedType || $selectedPriority || $showOnlyUnread)
                            <button wire:click="clearFilters" 
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                Wyczyść filtry
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="divide-y divide-gray-200">
                    @forelse($notifications as $notification)
                        <div class="p-6 hover:bg-gray-50 {{ !$notification->is_read ? 'bg-blue-50' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0">
                                        <i class="{{ $notification->type_icon }} {{ $notification->priority_color }} text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-lg font-medium text-white">
                                                {{ $notification->title }}
                                            </h3>
                                            @if(!$notification->is_read)
                                                <span class="inline-block w-2 h-2 bg-blue-500 rounded-full"></span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-gray-600">{{ $notification->message }}</p>
                                        
                                        <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500">
                                            <span>
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $notification->created_at->format('d.m.Y H:i') }}
                                            </span>
                                            <span class="capitalize">
                                                <i class="fas fa-flag mr-1"></i>
                                                {{ $notification->priority }}
                                            </span>
                                            <span class="capitalize">
                                                <i class="fas fa-tag mr-1"></i>
                                                {{ str_replace('_', ' ', $notification->type) }}
                                            </span>
                                            @if($notification->creator)
                                                <span>
                                                    <i class="fas fa-user mr-1"></i>
                                                    {{ $notification->creator->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 ml-4">
                                    @if(!$notification->is_read)
                                        <button wire:click="markAsRead({{ $notification->id }})" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    
                                    @if($notification->priority === 'critical' && !$notification->is_acknowledged)
                                        <button wire:click="acknowledge({{ $notification->id }})" 
                                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                            Potwierdź
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            @if($notification->metadata)
                                <div class="mt-4 p-3 bg-gray-100 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Dodatkowe informacje:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                        @foreach($notification->metadata as $key => $value)
                                            <div>
                                                <span class="font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                <span class="text-gray-800">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-12 text-center text-gray-500">
                            <i class="fas fa-bell-slash text-4xl mb-4"></i>
                            <p class="text-lg">Brak powiadomień spełniających kryteria</p>
                            @if($selectedType || $selectedPriority || $showOnlyUnread)
                                <button wire:click="clearFilters" 
                                        class="mt-2 text-blue-600 hover:text-blue-800">
                                    Wyczyść filtry aby zobaczyć wszystkie powiadomienia
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($notifications->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Handle real-time notifications
    window.addEventListener('showBrowserNotification', event => {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(event.detail.title, {
                body: event.detail.body,
                icon: event.detail.icon
            });
        }
    });

    // Handle toast notifications
    window.addEventListener('showToast', event => {
        // This would integrate with your toast notification system
        console.log('Toast notification:', event.detail);
    });

    // Request notification permission on page load
    document.addEventListener('DOMContentLoaded', function() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    });
</script>
@endpush