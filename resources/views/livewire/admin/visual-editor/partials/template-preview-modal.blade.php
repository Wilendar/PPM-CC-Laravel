{{-- Template Preview Modal - ETAP_07f FAZA 5 --}}
@teleport('body')
<div
    x-data="{
        show: true,
        device: 'desktop',
        cid: '{{ $this->getId() }}'
    }"
    x-show="show"
    x-cloak
    @keydown.escape.window="Livewire.find(cid).call('closePreviewModal')"
    class="fixed inset-0 z-50"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/80 backdrop-blur-sm"
        wire:click="closePreviewModal"
    ></div>

    {{-- Modal Content --}}
    <div class="relative z-10 h-full flex items-center justify-center p-4">
        <div
            class="bg-gray-800 rounded-xl shadow-2xl w-full border border-gray-700 flex flex-col"
            :class="{
                'max-w-6xl max-h-[90vh]': device === 'desktop',
                'max-w-3xl max-h-[90vh]': device === 'tablet',
                'max-w-sm max-h-[90vh]': device === 'mobile'
            }"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-100">
                        {{ $previewTemplate->name ?? 'Podglad szablonu' }}
                    </h2>
                    @if($previewTemplate->category)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30 rounded-full">
                            {{ $previewTemplate->category }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    {{-- Device Toggle --}}
                    <div class="flex items-center bg-gray-900 rounded-lg p-1 border border-gray-700">
                        {{-- Desktop --}}
                        <button
                            @click="device = 'desktop'"
                            class="p-2 rounded-md transition"
                            :class="device === 'desktop' ? 'bg-gray-700 text-gray-100' : 'text-gray-500 hover:text-gray-300'"
                            title="Widok Desktop"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        {{-- Tablet --}}
                        <button
                            @click="device = 'tablet'"
                            class="p-2 rounded-md transition"
                            :class="device === 'tablet' ? 'bg-gray-700 text-gray-100' : 'text-gray-500 hover:text-gray-300'"
                            title="Widok Tablet"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        {{-- Mobile --}}
                        <button
                            @click="device = 'mobile'"
                            class="p-2 rounded-md transition"
                            :class="device === 'mobile' ? 'bg-gray-700 text-gray-100' : 'text-gray-500 hover:text-gray-300'"
                            title="Widok Mobile"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Close Button --}}
                    <button
                        wire:click="closePreviewModal"
                        class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Preview Body --}}
            <div class="flex-1 overflow-hidden p-6">
                <div
                    class="h-full bg-white rounded-lg overflow-auto shadow-inner mx-auto transition-all duration-300"
                    :class="{
                        'w-full': device === 'desktop',
                        'w-[768px] max-w-full': device === 'tablet',
                        'w-[375px] max-w-full': device === 'mobile'
                    }"
                >
                    {{-- Rendered HTML Preview --}}
                    <div class="p-4 md:p-6">
                        @if($previewHtml)
                            {!! $previewHtml !!}
                        @elseif($previewTemplate && !empty($previewTemplate->blocks_json))
                            {{-- Fallback: render blocks manually --}}
                            <div class="prose max-w-none">
                                @foreach($previewTemplate->blocks_json as $block)
                                    @include('livewire.admin.visual-editor.partials.block-preview-item', ['block' => $block])
                                @endforeach
                            </div>
                        @else
                            {{-- Empty State --}}
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                </svg>
                                <p>Ten szablon nie zawiera blokow do wyswietlenia</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Template Info Bar --}}
            <div class="px-6 py-3 bg-gray-900/50 border-t border-gray-700 flex-shrink-0">
                <div class="flex flex-wrap items-center justify-between gap-4 text-xs text-gray-500">
                    <div class="flex items-center gap-4">
                        {{-- Blocks Count --}}
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            {{ count($previewTemplate->blocks_json ?? []) }} blokow
                        </span>

                        {{-- Shop --}}
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            {{ $previewTemplate->shop?->name ?? 'Globalny' }}
                        </span>

                        {{-- Usage --}}
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            {{ $previewTemplate->usage_count ?? 0 }} uzyc
                        </span>

                        {{-- Created --}}
                        @if($previewTemplate->created_at)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $previewTemplate->created_at->format('d.m.Y') }}
                            </span>
                        @endif
                    </div>

                    {{-- Creator --}}
                    @if($previewTemplate->creator)
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ $previewTemplate->creator->name ?? 'Nieznany' }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-2">
                    {{-- Edit Button --}}
                    <button
                        wire:click="openEditModal({{ $previewTemplate->id }})"
                        class="btn-enterprise-ghost flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edytuj
                    </button>

                    {{-- Duplicate Button --}}
                    <button
                        wire:click="duplicateTemplate({{ $previewTemplate->id }})"
                        class="btn-enterprise-ghost flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Duplikuj
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        wire:click="closePreviewModal"
                        class="btn-enterprise-secondary"
                    >
                        Zamknij
                    </button>
                    <button
                        wire:click="useTemplate({{ $previewTemplate->id }})"
                        class="btn-enterprise-primary"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Uzyj szablonu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endteleport
