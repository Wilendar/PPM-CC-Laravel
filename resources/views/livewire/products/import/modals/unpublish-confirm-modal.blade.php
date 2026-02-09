{{-- PPM Confirmation Modal: Unpublish Product --}}
{{-- Pattern: Livewire $confirmUnpublishId controls visibility --}}
<div>
@if($confirmUnpublishId)
    @php
        $unpubProduct = \App\Models\PendingProduct::find($confirmUnpublishId);
    @endphp
    <div class="fixed inset-0 overflow-y-auto import-unpublish-modal-overlay"
         style="z-index: 100;"
         @keydown.escape.window="$wire.cancelUnpublish()">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity"
             @click="$wire.cancelUnpublish()"></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-gray-800 rounded-xl shadow-2xl border border-red-900/50 transform transition-all"
                 @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700 flex items-center gap-3">
                    <div class="p-2 bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Cofniecie publikacji</h3>
                        <p class="text-sm text-gray-400">Operacja nieodwracalna</p>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-4 space-y-4">
                    <div class="text-sm text-gray-300">
                        Czy na pewno chcesz cofnac publikacje produktu
                        <strong class="text-white">{{ $unpubProduct?->sku ?? 'N/A' }}</strong>?
                    </div>

                    <div class="bg-red-900/20 border border-red-800/50 rounded-lg p-3 text-xs text-red-300 space-y-1">
                        <div class="flex items-center gap-2 font-medium text-red-400">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Ta operacja spowoduje:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 ml-6">
                            <li>Usuniecie produktu z PPM (tabela products)</li>
                            <li>Usuniecie powiazanych cen, danych sklepowych i mediow</li>
                            <li>Przywrocenie statusu "draft" w imporcie</li>
                            <li>Zewnetrzne systemy (PrestaShop/ERP) wymagaja recznego usuniecia</li>
                        </ul>
                    </div>

                    <div class="text-xs text-gray-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Operacja zostanie zalogowana (uzytkownik: {{ auth()->user()?->name ?? 'N/A' }},
                        data: {{ now()->format('d.m.Y H:i') }})
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-end gap-3">
                    <button type="button"
                            wire:click="cancelUnpublish"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors">
                        Anuluj
                    </button>
                    <button type="button"
                            wire:click="confirmUnpublish"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 hover:bg-red-700 text-white inline-flex items-center gap-2 transition-colors">
                        <span wire:loading.remove wire:target="confirmUnpublish">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="confirmUnpublish">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        Potwierdz cofniecie
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
