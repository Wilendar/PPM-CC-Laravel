{{-- Version History Modal - ETAP_07f Faza 6.1.4.4 --}}
@if($showVersionHistoryModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="version-history-modal" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 bg-gray-900/80 transition-opacity" aria-hidden="true" wire:click="closeVersionHistory"></div>

        {{-- Modal panel --}}
        <div class="inline-block align-bottom bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-700">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-blue-500/20">
                        <i class="fas fa-history text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Historia wersji</h3>
                        <p class="text-sm text-gray-400">
                            {{ count($versionHistoryList) }} zapisanych wersji
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="closeVersionHistory" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Content --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 max-h-[70vh]">
                {{-- Version List --}}
                <div class="border-r border-slate-700 overflow-y-auto max-h-[70vh]">
                    @if(empty($versionHistoryList))
                        <div class="p-8 text-center text-gray-400">
                            <i class="fas fa-clock text-4xl mb-3 opacity-50"></i>
                            <p>Brak zapisanych wersji</p>
                            <p class="text-sm mt-2">Wersje sa tworzone automatycznie podczas edycji</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-700/50">
                            @foreach($versionHistoryList as $version)
                                <div
                                    wire:click="previewVersion({{ $version['id'] }})"
                                    class="p-4 cursor-pointer transition-colors
                                        {{ $previewVersionId === $version['id'] ? 'bg-blue-500/20 border-l-2 border-blue-500' : 'hover:bg-slate-700/50' }}"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 rounded-lg {{ $previewVersionId === $version['id'] ? 'bg-blue-500/30' : 'bg-slate-700' }}">
                                                <i class="{{ $version['change_type_icon'] }} {{ $previewVersionId === $version['id'] ? 'text-blue-400' : 'text-gray-400' }}"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-white">Wersja #{{ $version['version_number'] }}</span>
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-700 text-gray-300">
                                                        {{ $version['change_type_label'] }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-400 mt-1">
                                                    <span>{{ $version['creator_name'] }}</span>
                                                    <span class="mx-1">|</span>
                                                    <span title="{{ $version['created_at'] }}">{{ $version['created_at_human'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs text-gray-500">{{ $version['block_count'] }} blokow</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Preview Panel --}}
                <div class="overflow-y-auto max-h-[70vh] bg-slate-900/50">
                    @if($previewVersionId)
                        {{-- Preview Header --}}
                        <div class="sticky top-0 bg-slate-800 border-b border-slate-700 px-4 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-400">Podglad wersji</span>
                            <button
                                type="button"
                                wire:click="restoreVersion({{ $previewVersionId }})"
                                wire:confirm="Czy na pewno chcesz przywrocic te wersje? Obecna wersja zostanie zapisana w historii."
                                class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                            >
                                <i class="fas fa-undo"></i>
                                Przywroc te wersje
                            </button>
                        </div>

                        {{-- Preview Content --}}
                        <div class="p-4">
                            <div class="bg-white rounded-lg p-4 text-gray-900 prose prose-sm max-w-none">
                                {!! $this->versionPreviewHtml !!}
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full p-8 text-center text-gray-400">
                            <div>
                                <i class="fas fa-eye text-4xl mb-3 opacity-50"></i>
                                <p>Wybierz wersje z listy</p>
                                <p class="text-sm mt-2">aby wyswietlic podglad</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-700 flex justify-end">
                <button
                    type="button"
                    wire:click="closeVersionHistory"
                    class="px-4 py-2 text-sm text-gray-300 hover:text-white bg-slate-700 hover:bg-slate-600 rounded-lg transition-colors"
                >
                    Zamknij
                </button>
            </div>
        </div>
    </div>
</div>
@endif
