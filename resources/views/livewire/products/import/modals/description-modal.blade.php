{{-- ETAP_06 FAZA 6.5.4: DescriptionModal - Opisy produktu (short/long description) --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="description-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 id="description-modal-title" class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Opisy produktu
                        </h3>
                        @if($pendingProduct)
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $pendingProduct->sku }} - {{ $pendingProduct->name ?? '(brak nazwy)' }}
                        </p>
                        @endif
                    </div>
                    <button wire:click="closeModal"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-6">

                    {{-- Skip descriptions option --}}
                    <div class="p-4 rounded-lg transition-colors
                                {{ $skipDescriptions ? 'bg-red-900/30 border border-red-700' : 'bg-gray-700/30' }}">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="skipDescriptions"
                                   class="form-checkbox-dark">
                            <span class="ml-3">
                                <span class="text-white text-sm font-medium">
                                    Publikuj bez opisow
                                </span>
                                <span class="block text-xs text-gray-400 mt-0.5">
                                    Produkt zostanie opublikowany bez opisu krotkiego i dlugiego
                                </span>
                            </span>
                        </label>

                        @if($skipDescriptions)
                        <div class="mt-3 p-2 bg-red-800/30 rounded text-xs text-red-300 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Opisy nie zostana dodane przy publikacji</span>
                        </div>
                        @endif
                    </div>

                    {{-- Short description --}}
                    <div class="{{ $skipDescriptions ? 'opacity-50 pointer-events-none' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <label for="short_description" class="text-sm font-medium text-gray-300">
                                Krotki opis
                                <span class="text-gray-500 font-normal ml-1">(summary)</span>
                            </label>
                            <span class="text-xs {{ $this->shortCount > 500 ? 'text-amber-400' : 'text-gray-500' }}">
                                {{ $this->shortCount }} / 500 znakow
                            </span>
                        </div>
                        <textarea id="short_description"
                                  wire:model.blur="shortDescription"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Krotki opis produktu wyswietlany w listingu..."
                                  class="form-textarea-dark w-full resize-none"></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            Wyswietlany na listach produktow i w podsumowaniach
                        </p>
                    </div>

                    {{-- Long description --}}
                    <div class="{{ $skipDescriptions ? 'opacity-50 pointer-events-none' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <label for="long_description" class="text-sm font-medium text-gray-300">
                                Pelny opis
                                <span class="text-gray-500 font-normal ml-1">(HTML)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <span class="text-xs {{ $this->longCount > 5000 ? 'text-amber-400' : 'text-gray-500' }}">
                                    {{ $this->longCount }} znakow
                                </span>
                                <button type="button"
                                        wire:click="copyShortToLong"
                                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                    Kopiuj krotki opis
                                </button>
                            </div>
                        </div>
                        <textarea id="long_description"
                                  wire:model.blur="longDescription"
                                  rows="8"
                                  placeholder="Pelny opis produktu z formatowaniem HTML...

Mozesz uzywac tagow HTML:
<p>Akapity</p>
<ul><li>Listy</li></ul>
<strong>Pogrubienie</strong>
<em>Kursywa</em>"
                                  class="form-textarea-dark w-full resize-y font-mono text-sm"></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            Pelny opis widoczny na stronie produktu. Obsługuje HTML.
                        </p>
                    </div>

                    {{-- Quick actions --}}
                    @if(!$skipDescriptions && ($shortDescription || $longDescription))
                    <div class="flex items-center gap-3 pt-2 border-t border-gray-700">
                        <button type="button"
                                wire:click="clearDescriptions"
                                wire:confirm="Wyczyścić oba opisy?"
                                class="text-xs text-red-400 hover:text-red-300 transition-colors">
                            Wyczysc opisy
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                            Anuluj
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Status indicator --}}
                        @if($skipDescriptions)
                        <span class="text-xs text-red-400 mr-2">
                            Brak opisow
                        </span>
                        @elseif($shortDescription || $longDescription)
                        <span class="text-xs text-green-400 mr-2">
                            {{ ($shortDescription ? '1' : '0') }}/2 opisow
                        </span>
                        @endif

                        <button type="button"
                                wire:click="saveDescriptions"
                                @disabled($isProcessing)
                                class="px-6 py-2 rounded-lg transition-colors font-medium
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       {{ $skipDescriptions
                                           ? 'bg-red-600 hover:bg-red-700 text-white'
                                           : 'bg-green-600 hover:bg-green-700 text-white' }}">
                            @if($isProcessing)
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Zapisywanie...
                            @else
                                {{ $skipDescriptions ? 'Zapisz (bez opisow)' : 'Zapisz opisy' }}
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
