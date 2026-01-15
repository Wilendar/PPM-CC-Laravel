{{-- CSS/JS Editor Modal - ETAP_07f_P3 --}}
<div>
    @if($isOpen)
    <div
        class="fixed inset-0 z-50 overflow-y-auto"
        x-data="{ }"
        x-init="$el.scrollTop = 0"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/50 transition-opacity"
            wire:click="close"
        ></div>

        {{-- Modal --}}
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] flex flex-col border border-gray-700">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-100">
                            CSS/JS Editor - {{ $this->shop?->name ?? 'Sklep' }}
                        </h3>
                    </div>
                    <button
                        wire:click="close"
                        class="p-2 text-gray-400 hover:text-gray-200 rounded-lg hover:bg-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Tab Navigation --}}
                <div class="flex border-b border-gray-700 px-6">
                    <button
                        wire:click="setTab('files')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'files' ? 'border-cyan-400 text-cyan-400' : 'border-transparent text-gray-400 hover:text-gray-200' }}"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Pliki PrestaShop
                    </button>
                    <button
                        wire:click="setTab('editor')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'editor' ? 'border-cyan-400 text-cyan-400' : 'border-transparent text-gray-400 hover:text-gray-200' }}"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edytor
                        @if($this->isDirty)
                            <span class="ml-1 w-2 h-2 bg-orange-500 rounded-full inline-block"></span>
                        @endif
                    </button>
                    <button
                        wire:click="setTab('analysis')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'analysis' ? 'border-cyan-400 text-cyan-400' : 'border-transparent text-gray-400 hover:text-gray-200' }}"
                    >
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Analiza
                    </button>
                </div>

                {{-- Messages --}}
                @if($errorMessage)
                    <div class="mx-6 mt-4 p-3 bg-red-900/50 border border-red-700 rounded-lg text-red-300 text-sm">
                        {{ $errorMessage }}
                    </div>
                @endif
                @if($successMessage)
                    <div class="mx-6 mt-4 p-3 bg-green-900/50 border border-green-700 rounded-lg text-green-300 text-sm">
                        {{ $successMessage }}
                    </div>
                @endif

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto p-6">

                    {{-- FILES TAB - ETAP_07f_P3: Lista plikow ze skanowania FTP --}}
                    @if($activeTab === 'files')
                        <div class="space-y-6">
                            {{-- Stats --}}
                            <div class="flex gap-4 text-sm text-gray-400">
                                <span>CSS: <strong class="text-gray-200">{{ $this->totalCssCount }}</strong> plikow</span>
                                <span>JS: <strong class="text-gray-200">{{ $this->totalJsCount }}</strong> plikow</span>
                                @if($this->hasScannedFiles)
                                    <span class="text-green-400 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        FTP Scan
                                    </span>
                                @endif
                            </div>

                            @if($this->hasScannedFiles)
                                {{-- === CSS FILES === --}}
                                @php $cssCategories = $this->cssFilesByCategory; @endphp

                                {{-- THEME CSS --}}
                                @if(!empty($cssCategories['theme']))
                                    <div class="border border-gray-700 rounded-lg">
                                        <div class="px-4 py-3 bg-gray-700/50 border-b border-gray-700 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                            </svg>
                                            <span class="font-medium text-gray-200">THEME CSS</span>
                                            <span class="text-sm text-gray-400">({{ count($cssCategories['theme']) }} plikow)</span>
                                        </div>
                                        <div class="divide-y divide-gray-700 max-h-48 overflow-y-auto">
                                            @foreach($cssCategories['theme'] as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-cyan-400 hover:text-cyan-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- CUSTOM CSS/JS (EDYTOWALNE) --}}
                                @php
                                    $customCss = $cssCategories['custom'] ?? [];
                                    $customJs = $this->jsFilesByCategory['custom'] ?? [];
                                @endphp
                                @if(!empty($customCss) || !empty($customJs))
                                    <div class="border border-green-700/50 rounded-lg bg-green-900/20">
                                        <div class="px-4 py-3 bg-green-900/30 border-b border-green-700/50 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span class="font-medium text-gray-200">CUSTOM</span>
                                            <span class="text-sm text-green-400">(Edytowalne przez FTP)</span>
                                        </div>
                                        <div class="divide-y divide-green-700/30">
                                            @foreach($customCss as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-green-900/20">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                        <span class="text-xs px-2 py-0.5 bg-green-900/50 text-green-400 rounded">CSS</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                            class="text-xs text-cyan-400 hover:text-cyan-300"
                                                        >
                                                            Podglad
                                                        </button>
                                                        <button
                                                            wire:click="editFile('{{ $file['url'] ?? '' }}')"
                                                            class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                                                        >
                                                            Edytuj
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @foreach($customJs as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-green-900/20">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                        <span class="text-xs px-2 py-0.5 bg-yellow-900/50 text-yellow-400 rounded">JS</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                            class="text-xs text-cyan-400 hover:text-cyan-300"
                                                        >
                                                            Podglad
                                                        </button>
                                                        <button
                                                            wire:click="editFile('{{ $file['url'] ?? '' }}')"
                                                            class="px-3 py-1 text-xs bg-yellow-600 text-white rounded hover:bg-yellow-700"
                                                        >
                                                            Edytuj
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- MODULE CSS --}}
                                @if(!empty($cssCategories['module']))
                                    <div class="border border-gray-700 rounded-lg">
                                        <div class="px-4 py-3 bg-gray-700/50 border-b border-gray-700 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            <span class="font-medium text-gray-200">MODULES CSS</span>
                                            <span class="text-sm text-gray-400">({{ count($cssCategories['module']) }} plikow)</span>
                                        </div>
                                        <div class="divide-y divide-gray-700 max-h-60 overflow-y-auto">
                                            @foreach($cssCategories['module'] as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleAsset('{{ $file['url'] ?? '' }}')"
                                                            @checked(in_array($file['url'] ?? '', $selectedAssets))
                                                            class="rounded border-gray-600 bg-gray-700 text-cyan-500"
                                                        />
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-cyan-400 hover:text-cyan-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- === JS FILES === --}}
                                @php $jsCategories = $this->jsFilesByCategory; @endphp

                                {{-- THEME JS --}}
                                @if(!empty($jsCategories['theme']))
                                    <div class="border border-gray-700 rounded-lg">
                                        <div class="px-4 py-3 bg-gray-700/50 border-b border-gray-700 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                            </svg>
                                            <span class="font-medium text-gray-200">THEME JS</span>
                                            <span class="text-sm text-gray-400">({{ count($jsCategories['theme']) }} plikow)</span>
                                        </div>
                                        <div class="divide-y divide-gray-700 max-h-48 overflow-y-auto">
                                            @foreach($jsCategories['theme'] as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-cyan-400 hover:text-cyan-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- MODULE JS --}}
                                @if(!empty($jsCategories['module']))
                                    <div class="border border-gray-700 rounded-lg">
                                        <div class="px-4 py-3 bg-gray-700/50 border-b border-gray-700 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            <span class="font-medium text-gray-200">MODULES JS</span>
                                            <span class="text-sm text-gray-400">({{ count($jsCategories['module']) }} plikow)</span>
                                        </div>
                                        <div class="divide-y divide-gray-700 max-h-60 overflow-y-auto">
                                            @foreach($jsCategories['module'] as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleAsset('{{ $file['url'] ?? '' }}')"
                                                            @checked(in_array($file['url'] ?? '', $selectedAssets))
                                                            class="rounded border-gray-600 bg-gray-700 text-cyan-500"
                                                        />
                                                        <span class="text-sm text-gray-300">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-cyan-400 hover:text-cyan-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- OTHER FILES --}}
                                @php
                                    $otherCss = $cssCategories['other'] ?? [];
                                    $otherJs = $jsCategories['other'] ?? [];
                                @endphp
                                @if(!empty($otherCss) || !empty($otherJs))
                                    <div class="border border-gray-600 rounded-lg">
                                        <div class="px-4 py-3 bg-gray-700/30 border-b border-gray-600 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                            <span class="font-medium text-gray-300">INNE</span>
                                            <span class="text-sm text-gray-500">({{ count($otherCss) + count($otherJs) }} plikow)</span>
                                        </div>
                                        <div class="divide-y divide-gray-600 max-h-40 overflow-y-auto">
                                            @foreach($otherCss as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-sm text-gray-400">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                        <span class="text-xs px-1.5 py-0.5 bg-gray-700 text-gray-400 rounded">CSS</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-gray-400 hover:text-gray-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                            @foreach($otherJs as $file)
                                                <div class="px-4 py-2 flex items-center justify-between hover:bg-gray-700/30">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-sm text-gray-400">{{ $file['filename'] ?? basename($file['url'] ?? '') }}</span>
                                                        <span class="text-xs px-1.5 py-0.5 bg-gray-700 text-gray-400 rounded">JS</span>
                                                    </div>
                                                    <button
                                                        wire:click="viewFile('{{ $file['url'] ?? '' }}')"
                                                        class="text-xs text-gray-400 hover:text-gray-300"
                                                    >
                                                        Podglad
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Actions for scanned files --}}
                                <div class="flex gap-3 pt-4 border-t border-gray-700">
                                    <button
                                        wire:click="refreshAssetManifest"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshAssetManifest" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Odswiez liste
                                    </button>
                                    <button
                                        wire:click="fetchSelectedToPpm"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Pobierz do PPM cache
                                    </button>
                                    @if(count($selectedAssets) > 0)
                                        <button
                                            wire:click="saveSelectedAssets"
                                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Zapisz wybrane ({{ count($selectedAssets) }})
                                        </button>
                                    @endif
                                </div>

                            @else
                                {{-- Empty State - No Scanned Files --}}
                                <div class="p-8 text-center bg-gray-800/50 rounded-lg border border-gray-700">
                                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <h4 class="text-lg font-medium text-gray-300 mb-2">Brak zeskanowanych plikow CSS/JS</h4>
                                    <p class="text-gray-400 mb-4 max-w-md mx-auto">
                                        Aby wyswietlic liste plikow CSS/JS z PrestaShop, najpierw skonfiguruj polaczenie FTP
                                        i uruchom skanowanie w panelu konfiguracji sklepu.
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                        <a
                                            href="/admin/shops"
                                            class="px-4 py-2 text-sm bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 inline-flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Konfiguracja sklepow
                                        </a>
                                        <button
                                            wire:click="loadAssetManifest"
                                            class="px-4 py-2 text-sm border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 inline-flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Sprobuj HTTP discovery
                                        </button>
                                    </div>

                                    {{-- Help info --}}
                                    <div class="mt-6 p-4 bg-cyan-900/20 border border-cyan-700/30 rounded-lg text-left max-w-lg mx-auto">
                                        <p class="text-sm text-cyan-300 font-medium mb-2">Jak skonfigurowac skanowanie FTP:</p>
                                        <ol class="text-sm text-cyan-200/80 list-decimal list-inside space-y-1">
                                            <li>Przejdz do Admin > Sklepy</li>
                                            <li>Wybierz sklep i otworz konfiguracje</li>
                                            <li>Wypelnij dane FTP (host, user, password)</li>
                                            <li>Kliknij "Test polaczenia"</li>
                                            <li>Kliknij "Skanuj pliki CSS/JS"</li>
                                        </ol>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- EDITOR TAB --}}
                    @if($activeTab === 'editor')
                        <div class="space-y-4">
                            {{-- Editor Type Toggle --}}
                            <div class="flex gap-2">
                                <button
                                    wire:click="switchEditingType('css')"
                                    class="px-4 py-2 text-sm rounded-lg transition-colors {{ $editingType === 'css' ? 'bg-cyan-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                                >
                                    custom.css
                                </button>
                                <button
                                    wire:click="switchEditingType('js')"
                                    class="px-4 py-2 text-sm rounded-lg transition-colors {{ $editingType === 'js' ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}"
                                >
                                    custom.js
                                </button>
                            </div>

                            {{-- Code Editor --}}
                            <div class="border border-gray-700 rounded-lg overflow-hidden">
                                <div class="px-4 py-2 bg-gray-900 text-gray-300 text-xs font-mono flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="text-cyan-400">{{ $editingFileName ?? ($editingType === 'css' ? 'custom.css' : 'custom.js') }}</span>
                                        @if($editingFilePath)
                                            <span class="text-gray-500">{{ $editingFilePath }}</span>
                                        @endif
                                    </div>
                                    @if($this->isDirty)
                                        <span class="text-orange-400">* Niezapisane zmiany</span>
                                    @endif
                                </div>
                                <textarea
                                    wire:model.live.debounce.500ms="editorContent"
                                    class="w-full h-96 p-4 font-mono text-sm bg-gray-900 text-gray-100 focus:outline-none resize-none border-0"
                                    spellcheck="false"
                                    placeholder="// Zaladuj plik aby rozpoczac edycje..."
                                ></textarea>
                            </div>

                            {{-- Editor Actions --}}
                            <div class="flex gap-3">
                                <button
                                    wire:click="loadEditorContent"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Zaladuj z PrestaShop
                                </button>
                                <button
                                    wire:click="saveContent"
                                    wire:loading.attr="disabled"
                                    @disabled(!$this->isDirty)
                                    class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Zapisz na PrestaShop
                                </button>
                            </div>

                            {{-- Help --}}
                            <div class="p-4 bg-cyan-900/30 border border-cyan-700/50 rounded-lg text-sm text-cyan-200">
                                <p class="font-medium mb-2">Wskazowki:</p>
                                <ul class="list-disc list-inside space-y-1 text-cyan-300">
                                    <li>Zmiany w custom.css sa natychmiast widoczne na PrestaShop po zapisie</li>
                                    <li>Klasy <code class="px-1 bg-cyan-900/50 rounded">pd-*</code> sluza do stylowania opisow produktow</li>
                                    <li>Uzyj podgladu w edytorze wizualnym aby sprawdzic efekt</li>
                                </ul>
                            </div>
                        </div>
                    @endif

                    {{-- ANALYSIS TAB --}}
                    @if($activeTab === 'analysis')
                        <div class="space-y-4">
                            <div class="p-6 bg-gray-700/50 rounded-lg border border-gray-600 text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                                <h4 class="text-lg font-medium text-gray-200 mb-2">Analiza CSS</h4>
                                <p class="text-gray-400 mb-4">
                                    Przeanalizuj uzycie klas CSS w opisach produktow i wykryj brakujace definicje.
                                </p>
                                <p class="text-sm text-gray-500">
                                    Funkcja bedzie dostepna w nastepnej fazie implementacji.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="text-sm text-gray-400">
                        @if($this->shop)
                            <span>Sklep: {{ $this->shop->name }}</span>
                            <span class="mx-2">|</span>
                            <span>URL: {{ $this->shop->url }}</span>
                        @endif
                    </div>
                    <button
                        wire:click="close"
                        class="px-4 py-2 text-sm text-gray-300 border border-gray-600 rounded-lg hover:bg-gray-700"
                    >
                        Zamknij
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- Loading Overlay --}}
    @if($isLoading)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/30">
            <div class="bg-gray-800 rounded-lg p-6 shadow-xl border border-gray-700 flex items-center gap-3">
                <svg class="animate-spin h-6 w-6 text-cyan-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-200">Ladowanie...</span>
            </div>
        </div>
    @endif
</div>
