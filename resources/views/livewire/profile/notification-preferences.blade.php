<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">
            Powiadomienia
        </h1>
        <p class="mt-1 text-sm text-gray-400">
            Przegladaj powiadomienia i zarzadzaj preferencjami dostarczania
        </p>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8">
            {{-- Inbox Tab --}}
            <button
                wire:click="$set('activeTab', 'inbox')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                       @if($activeTab === 'inbox')
                           border-[#e0ac7e] text-[#e0ac7e]
                       @else
                           border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-500
                       @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                Skrzynka
                @if($this->unreadCount > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-[#e0ac7e] text-gray-900">
                        {{ $this->unreadCount }}
                    </span>
                @endif
            </button>

            {{-- Settings Tab --}}
            <button
                wire:click="$set('activeTab', 'settings')"
                class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                       @if($activeTab === 'settings')
                           border-[#e0ac7e] text-[#e0ac7e]
                       @else
                           border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-500
                       @endif"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Ustawienia
            </button>
        </nav>
    </div>

    {{-- ============================================ --}}
    {{-- TAB: INBOX --}}
    {{-- ============================================ --}}
    @if($activeTab === 'inbox')
        <div class="space-y-4">

            {{-- Toolbar --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                {{-- Unread filter toggle --}}
                <label class="inline-flex items-center cursor-pointer select-none">
                    <div class="relative" x-data="{ checked: @entangle('showUnreadOnly').live }">
                        <input type="checkbox"
                               x-model="checked"
                               class="sr-only">
                        <div @click="checked = !checked"
                             :class="checked ? 'bg-[#e0ac7e]' : 'bg-gray-600'"
                             class="w-10 h-5 rounded-full transition-colors duration-200 cursor-pointer">
                        </div>
                        <div :class="checked ? 'translate-x-5' : 'translate-x-0'"
                             class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200 pointer-events-none">
                        </div>
                    </div>
                    <span class="ml-3 text-sm text-gray-300">Tylko nieprzeczytane</span>
                </label>

                {{-- Mark all as read --}}
                @if($this->unreadCount > 0)
                    <button wire:click="markAllAsRead"
                            wire:loading.attr="disabled"
                            wire:target="markAllAsRead"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg
                                   bg-gray-700 text-gray-300 border border-gray-600
                                   hover:bg-gray-600 hover:text-white
                                   transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span wire:loading.remove wire:target="markAllAsRead">Oznacz wszystkie jako przeczytane</span>
                        <span wire:loading wire:target="markAllAsRead">Oznaczanie...</span>
                    </button>
                @endif
            </div>

            {{-- Notification List --}}
            @if($this->notifications->count() > 0)
                <div class="space-y-3">
                    @foreach($this->notifications as $notification)
                        <div wire:key="notif-{{ $notification->id }}"
                             class="bg-gray-800/50 rounded-xl border transition-colors duration-200
                                    @if($notification->read_at === null)
                                        border-l-4 border-l-[#e0ac7e] border-t-gray-700 border-r-gray-700 border-b-gray-700 bg-gray-800/80
                                    @else
                                        border-gray-700
                                    @endif">
                            <div class="p-5 flex items-start gap-4">

                                {{-- Icon --}}
                                <div class="flex-shrink-0 mt-0.5">
                                    @php $iconType = $this->getNotificationIcon($notification); @endphp
                                    @switch($iconType)
                                        @case('sync')
                                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </div>
                                            @break
                                        @case('product')
                                            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            @break
                                        @case('security')
                                            <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            </div>
                                            @break
                                        @case('import')
                                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                </svg>
                                            </div>
                                            @break
                                        @case('stock')
                                            <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                            </div>
                                            @break
                                        @case('system')
                                            <div class="w-10 h-10 rounded-lg bg-gray-500/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            @break
                                        @default
                                            <div class="w-10 h-10 rounded-lg bg-[#e0ac7e]/20 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                                </svg>
                                            </div>
                                    @endswitch
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold
                                                       @if($notification->read_at === null) text-white @else text-gray-300 @endif">
                                                {{ $this->getNotificationTitle($notification) }}
                                            </h3>
                                            @if($this->getNotificationMessage($notification))
                                                <p class="mt-1 text-sm text-gray-400 line-clamp-2">
                                                    {{ $this->getNotificationMessage($notification) }}
                                                </p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-500">
                                                {{ $notification->created_at->diffForHumans() }}
                                                @if($notification->read_at)
                                                    &middot; przeczytane {{ $notification->read_at->diffForHumans() }}
                                                @endif
                                            </p>
                                        </div>

                                        {{-- Action buttons --}}
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            @if($notification->read_at === null)
                                                <button wire:click="markAsRead('{{ $notification->id }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="markAsRead('{{ $notification->id }}')"
                                                        class="p-2 rounded-lg text-gray-400 hover:text-[#e0ac7e] hover:bg-gray-700
                                                               transition-colors duration-200"
                                                        title="Oznacz jako przeczytane">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            <button wire:click="deleteNotification('{{ $notification->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteNotification('{{ $notification->id }}')"
                                                    wire:confirm="Czy na pewno chcesz usunac to powiadomienie?"
                                                    class="p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-gray-700
                                                           transition-colors duration-200"
                                                    title="Usun powiadomienie">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $this->notifications->links() }}
                </div>

            @else
                {{-- Empty State --}}
                <div class="bg-gray-800/50 rounded-xl border border-gray-700 p-12 text-center">
                    <svg class="mx-auto w-16 h-16 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-300 mb-1">Brak powiadomien</h3>
                    <p class="text-sm text-gray-500">
                        @if($showUnreadOnly)
                            Nie masz nieprzeczytanych powiadomien.
                        @else
                            Twoja skrzynka powiadomien jest pusta.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================================ --}}
    {{-- TAB: SETTINGS --}}
    {{-- ============================================ --}}
    @if($activeTab === 'settings')
        <div class="space-y-6"
             x-data="{
                 browserPermission: typeof Notification !== 'undefined' ? Notification.permission : 'unsupported',
                 get browserEnabled() { return this.browserPermission === 'granted'; },
                 requestPermission() {
                     if (typeof Notification === 'undefined') return;
                     Notification.requestPermission().then(permission => {
                         this.browserPermission = permission;
                     });
                 }
             }">

            {{-- Preferences Grid --}}
            <div class="bg-gray-800/50 rounded-xl border border-gray-700 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white">Kanaly dostarczania</h3>
                    <p class="mt-1 text-sm text-gray-400">
                        Wybierz, ktore powiadomienia chcesz otrzymywac i jakim kanalem
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-300">Kategoria</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-300">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        Email
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-300">
                                    <div class="flex items-center justify-center gap-2"
                                         :class="{ 'opacity-40': !browserEnabled }">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                        Przegladarka
                                        <template x-if="!browserEnabled">
                                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        </template>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            @php $lastGroup = null; @endphp
                            @foreach($this->visibleCategories as $category)
                                {{-- Group header for sub-categories (e.g. Import) --}}
                                @if($category['group'] && $category['group'] !== $lastGroup)
                                    <tr class="bg-gray-800/30">
                                        <td colspan="3" class="px-6 py-2">
                                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                                {{ $category['group'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @php $lastGroup = $category['group']; @endphp
                                @elseif(!$category['group'])
                                    @php $lastGroup = null; @endphp
                                @endif

                                @php $iconClasses = $this->getCategoryIconClasses($category['icon_color']); @endphp

                                <tr wire:key="pref-row-{{ $category['key'] }}" class="hover:bg-gray-700/30 transition-colors duration-150">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg {{ $iconClasses['bg'] }} flex items-center justify-center flex-shrink-0">
                                                @switch($category['icon'])
                                                    @case('product')
                                                        <svg class="w-4 h-4 {{ $iconClasses['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                        </svg>
                                                        @break
                                                    @case('import')
                                                        <svg class="w-4 h-4 {{ $iconClasses['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                        </svg>
                                                        @break
                                                    @case('sync')
                                                        <svg class="w-4 h-4 {{ $iconClasses['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                        </svg>
                                                        @break
                                                    @case('security')
                                                        <svg class="w-4 h-4 {{ $iconClasses['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                        </svg>
                                                        @break
                                                    @default
                                                        <svg class="w-4 h-4 {{ $iconClasses['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                        </svg>
                                                @endswitch
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-white">{{ $category['label'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $category['description'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        @include('livewire.profile.partials._toggle-switch', [
                                            'model' => 'prefs.email_' . $category['key'],
                                            'id' => 'email_' . $category['key'],
                                        ])
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div :class="{ 'opacity-40 pointer-events-none': !browserEnabled }"
                                             :title="!browserEnabled ? 'Wlacz powiadomienia przegladarki ponizej' : ''">
                                            @include('livewire.profile.partials._toggle-switch', [
                                                'model' => 'prefs.browser_' . $category['key'],
                                                'id' => 'browser_' . $category['key'],
                                            ])
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Browser Permission Section (uses browserPermission from parent x-data) --}}
            <div class="bg-gray-800/50 rounded-xl border border-gray-700 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-[#e0ac7e]/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-white">Powiadomienia przegladarki</h4>
                        <p class="mt-1 text-sm text-gray-400">
                            Aby otrzymywac powiadomienia w przegladarce, musisz zezwolic na ich wyswietlanie.
                        </p>

                        <div class="mt-4">
                            {{-- Permission: granted --}}
                            <template x-if="browserPermission === 'granted'">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Powiadomienia przegladarki wlaczone
                                </div>
                            </template>

                            {{-- Permission: denied --}}
                            <template x-if="browserPermission === 'denied'">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-500/20 text-red-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Powiadomienia zablokowane - zmien uprawnienia w ustawieniach przegladarki
                                </div>
                            </template>

                            {{-- Permission: default (not yet asked) --}}
                            <template x-if="browserPermission === 'default'">
                                <button @click="requestPermission()"
                                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold
                                               bg-[#e0ac7e] text-gray-900 hover:bg-[#d1975a]
                                               transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    Wlacz powiadomienia przegladarki
                                </button>
                            </template>

                            {{-- Permission: unsupported --}}
                            <template x-if="browserPermission === 'unsupported'">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-500/20 text-gray-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Ta przegladarka nie obsluguje powiadomien
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Auto-save toast (replaces manual Save button) --}}
            <div x-data="{ show: false }"
                 x-on:preferences-saved.window="show = true; setTimeout(() => show = false, 2000)"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-4 py-3 rounded-lg bg-emerald-500/90 text-white text-sm font-medium shadow-lg backdrop-blur-sm"
                 style="display: none;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Zapisano automatycznie
            </div>
        </div>
    @endif
</div>
