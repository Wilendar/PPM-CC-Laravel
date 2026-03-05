<div class="dashboard-widget" wire:poll.60s.visible="loadMessages" role="region" aria-label="Komunikaty systemowe">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <div class="flex items-center gap-2">
            <span class="dashboard-widget__title">Komunikaty systemowe</span>
            @if($unreadCount > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                    {{ $unreadCount }}
                </span>
            @endif
        </div>
        <div class="dashboard-widget__icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
    </div>

    {{-- Messages list --}}
    <div class="space-y-1">
        @forelse($messagesWithConfig as $msg)
            <div wire:key="msg-{{ $msg->id }}"
                 class="activity-item group relative {{ !$msg->is_read ? 'bg-gray-800/30 rounded-lg px-2 -mx-2' : '' }}">
                {{-- Priority icon --}}
                <div class="activity-item__icon {{ $msg->priorityConfig['bg'] }}">
                    <i class="{{ $msg->priorityConfig['icon'] }} {{ $msg->priorityConfig['text'] }}"></i>
                </div>

                {{-- Message content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-medium {{ !$msg->is_read ? 'text-white' : 'text-gray-300' }} truncate">
                            {{ $msg->title }}
                        </p>
                        @if(!$msg->is_read)
                            <button wire:click="markAsRead({{ $msg->id }})"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-xs text-gray-500 hover:text-gray-300 flex-shrink-0"
                                    title="Oznacz jako przeczytane">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endif
                    </div>
                    @if($msg->message)
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $msg->message }}</p>
                    @endif
                    <p class="activity-item__time mt-1">{{ $msg->created_at->diffForHumans() }}</p>
                </div>

                {{-- Unread dot --}}
                @if(!$msg->is_read)
                    <div class="w-2 h-2 rounded-full {{ $msg->priorityConfig['dot'] }} flex-shrink-0 mt-2"></div>
                @endif
            </div>
        @empty
            <div class="text-center py-6">
                <svg class="w-8 h-8 mx-auto text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-sm text-gray-500">Brak komunikatow</p>
            </div>
        @endforelse
    </div>
</div>
