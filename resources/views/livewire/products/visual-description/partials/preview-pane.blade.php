{{-- Preview Pane with IFRAME Isolation --}}
{{-- IFRAME ensures complete CSS isolation - PrestaShop CSS cannot leak into PPM admin UI --}}
@php
    $fullWidth = $fullWidth ?? false;
@endphp

<div class="ve-preview-pane flex-1 flex flex-col bg-gray-900 {{ $fullWidth ? '' : 'border-l border-gray-700' }}">
    {{-- Preview Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
        <span class="font-medium text-gray-200">Podglad (1:1 ze sklepem)</span>

        {{-- Device Toggle --}}
        <div class="flex items-center gap-1 bg-gray-800 rounded-lg p-0.5">
            <button
                wire:click="setPreviewMode('desktop')"
                class="p-2 rounded transition {{ $previewMode === 'desktop' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                title="Desktop"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </button>
            <button
                wire:click="setPreviewMode('tablet')"
                class="p-2 rounded transition {{ $previewMode === 'tablet' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                title="Tablet"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </button>
            <button
                wire:click="setPreviewMode('mobile')"
                class="p-2 rounded transition {{ $previewMode === 'mobile' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                title="Mobile"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-1">
            {{-- Refresh Preview Button --}}
            <button
                wire:click="$refresh"
                class="p-2 rounded text-gray-400 hover:text-gray-200 hover:bg-gray-700 transition"
                title="Odswiez podglad"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            {{-- Fullscreen Preview Button --}}
            <button
                x-data
                x-on:click="
                    const iframe = document.querySelector('iframe[id^=preview-iframe]');
                    if (iframe) {
                        const newWindow = window.open('', '_blank', 'width=1200,height=800');
                        newWindow.document.write(iframe.srcdoc);
                        newWindow.document.close();
                    }
                "
                class="p-2 rounded text-gray-400 hover:text-gray-200 hover:bg-gray-700 transition"
                title="Otworz na pelnym ekranie"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Preview Frame with IFRAME Isolation --}}
    {{-- IMPORTANT: Iframe has FIXED viewport height to correctly simulate browser 100vh behavior --}}
    {{-- If iframe auto-resizes to content height, 100vh inside iframe = content height (wrong!) --}}
    {{-- Fixed height ensures 100vh = viewport height (correct, like real browser) --}}
    <div class="flex-1 overflow-hidden p-4 bg-gray-950">
        <div
            class="{{ $fullWidth ? 'w-full' : 'mx-auto' }} bg-white rounded-lg shadow-2xl overflow-hidden transition-all duration-300 h-full"
            style="{{ $fullWidth && $previewMode === 'desktop' ? '' : 'max-width: ' . $this->previewWidth . ';' }}"
        >
            {{-- IFRAME for Complete CSS Isolation --}}
            {{-- Fixed height viewport - content scrolls INSIDE iframe (like real browser) --}}
            {{-- This ensures 100vh CSS inside iframe = iframe viewport, not content height --}}
            <iframe
                id="preview-iframe-{{ $this->getId() }}"
                class="w-full border-0 h-full"
                style="min-height: 600px;"
                srcdoc="{{ $this->getIframeContent() }}"
                sandbox="allow-same-origin allow-scripts"
            ></iframe>
        </div>
    </div>

    {{-- Preview Footer --}}
    <div class="flex items-center justify-between px-4 py-2 border-t border-gray-700 text-xs text-gray-500">
        <span>{{ $this->blockCount }} {{ trans_choice('blok|bloki|blokow', $this->blockCount) }}</span>
        <div class="flex items-center gap-2">
            <span class="text-green-400">
                <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Izolowany CSS
            </span>
            <span>{{ $this->shop?->name ?? 'Brak sklepu' }}</span>
        </div>
    </div>
</div>
