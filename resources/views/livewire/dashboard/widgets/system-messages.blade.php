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
        <div class="flex items-center gap-2">
            @if($isAdmin)
                <button wire:click="createMessage"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20 hover:bg-amber-500/25 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nowy
                </button>
            @endif
            <div class="dashboard-widget__icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Editor panel (admin only) --}}
    @if($showEditor)
        <div class="mb-4 p-3 rounded-lg bg-gray-800/60 border border-gray-600/50">
            <div class="space-y-3">
                {{-- Title --}}
                <div>
                    <input type="text" wire:model="editTitle" placeholder="Tytul komunikatu..."
                           class="w-full px-3 py-2 rounded-lg bg-gray-700 border border-gray-600 text-white text-sm placeholder-gray-500 focus:border-amber-500/50 focus:outline-none">
                    @error('editTitle') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Markdown toolbar --}}
                <div class="flex items-center gap-1"
                     x-data="{ insertMarkdown(syntax, wrap) {
                         const ta = $refs.msgArea;
                         const start = ta.selectionStart;
                         const end = ta.selectionEnd;
                         const text = ta.value;
                         const selected = text.substring(start, end);
                         let replacement;
                         if (wrap) {
                             replacement = syntax + selected + syntax;
                         } else {
                             replacement = syntax;
                         }
                         ta.value = text.substring(0, start) + replacement + text.substring(end);
                         ta.focus();
                         const newPos = wrap ? start + syntax.length + selected.length : start + replacement.length;
                         ta.setSelectionRange(newPos, newPos);
                         ta.dispatchEvent(new Event('input'));
                     }}">
                    <button type="button" @click="insertMarkdown('**', true)"
                            class="px-2 py-1 rounded text-xs font-bold text-gray-400 hover:text-white hover:bg-gray-600/50 transition-colors" title="Pogrubienie">B</button>
                    <button type="button" @click="insertMarkdown('*', true)"
                            class="px-2 py-1 rounded text-xs italic text-gray-400 hover:text-white hover:bg-gray-600/50 transition-colors" title="Kursywa">I</button>
                    <button type="button" @click="insertMarkdown('[tekst](url)', false)"
                            class="px-2 py-1 rounded text-xs text-gray-400 hover:text-white hover:bg-gray-600/50 transition-colors" title="Link">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    </button>
                    <button type="button" @click="insertMarkdown('\n- ', false)"
                            class="px-2 py-1 rounded text-xs text-gray-400 hover:text-white hover:bg-gray-600/50 transition-colors" title="Lista">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    </button>
                </div>

                {{-- Message textarea --}}
                <div>
                    <textarea wire:model="editMessage" x-ref="msgArea" rows="3"
                              placeholder="Tresc komunikatu (Markdown)..."
                              class="w-full px-3 py-2 rounded-lg bg-gray-700 border border-gray-600 text-white text-sm placeholder-gray-500 focus:border-amber-500/50 focus:outline-none resize-none"></textarea>
                    @error('editMessage') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Priority + Actions --}}
                <div class="flex items-center justify-between gap-3">
                    <select wire:model="editPriority"
                            class="px-3 py-1.5 rounded-lg bg-gray-700 border border-gray-600 text-white text-sm focus:border-amber-500/50 focus:outline-none">
                        <option value="low">Niski</option>
                        <option value="normal">Normalny</option>
                        <option value="high">Wysoki</option>
                        <option value="critical">Krytyczny</option>
                    </select>
                    <div class="flex items-center gap-2">
                        <button wire:click="cancelEdit" class="px-3 py-1.5 rounded-lg text-sm text-gray-400 hover:text-white transition-colors">Anuluj</button>
                        <button wire:click="saveMessage" class="px-3 py-1.5 rounded-lg text-sm font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/20 hover:bg-amber-500/25 transition-colors">Zapisz</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                        <div class="flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                            @if(!$msg->is_read)
                                <button wire:click="markAsRead({{ $msg->id }})"
                                        class="text-xs text-gray-500 hover:text-gray-300"
                                        title="Oznacz jako przeczytane">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endif
                            @if($isAdmin)
                                <button wire:click="startEdit({{ $msg->id }})"
                                        class="text-xs text-gray-500 hover:text-blue-400"
                                        title="Edytuj">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="deleteMessage({{ $msg->id }})"
                                        wire:confirm="Na pewno usunac ten komunikat?"
                                        class="text-xs text-gray-500 hover:text-red-400"
                                        title="Usun">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    @if($msg->renderedMessage)
                        <div x-data="{ expanded: false }" class="mt-0.5">
                            <div class="text-xs text-gray-500 dashboard-markdown"
                                 :class="{ 'line-clamp-2': !expanded }">{!! $msg->renderedMessage !!}</div>
                            <button @click="expanded = !expanded"
                                    class="text-xs text-amber-500/70 hover:text-amber-400 mt-0.5"
                                    x-text="expanded ? 'Zwiń' : 'Rozwiń'"></button>
                        </div>
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
