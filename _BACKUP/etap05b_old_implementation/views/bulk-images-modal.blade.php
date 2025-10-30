<div x-data="{ show: @entangle('showModal') }"
     x-show="show"
     class="modal-overlay"
     style="display: none;"
     x-cloak>

    <div class="modal-overlay-bg" @click="show = false"></div>

    <div class="modal-content bulk-images-modal">
        {{-- Modal Header --}}
        <div class="modal-header">
            <h3 class="text-h3">Masowe przypisanie zdjęć</h3>
            <button type="button" @click="show = false" class="modal-close-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="modal-body">
            <form wire:submit.prevent="assign">
                {{-- File Upload Section --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        Wybierz zdjęcia (max 10)
                    </label>

                    <div class="upload-dropzone">
                        <input type="file"
                               wire:model="uploadedImages"
                               multiple
                               accept="image/*"
                               max="10"
                               id="bulk-images-input"
                               class="upload-input">

                        <label for="bulk-images-input" class="upload-label">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <span class="text-gray-300 font-medium">Kliknij lub przeciągnij pliki</span>
                            <span class="text-gray-400 text-sm mt-1">Obsługiwane formaty: JPG, PNG, GIF, SVG (max 5MB/plik)</span>
                        </label>
                    </div>

                    @error('uploadedImages')
                        <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                    @enderror

                    @error('uploadedImages.*')
                        <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span>
                    @enderror

                    {{-- Upload Progress --}}
                    <div wire:loading wire:target="uploadedImages" class="mt-3 bg-blue-900/20 border border-blue-500/30 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <svg class="animate-spin h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-blue-400">Przesyłanie zdjęć...</span>
                        </div>
                    </div>
                </div>

                {{-- Preview Thumbnails --}}
                @if(!empty($uploadedImages))
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            Wybrane zdjęcia ({{ $this->totalImages }})
                        </label>

                        <div class="grid grid-cols-4 gap-4">
                            @foreach($uploadedImages as $index => $image)
                                <div class="image-preview-item" wire:key="image-{{ $index }}">
                                    @if(method_exists($image, 'temporaryUrl'))
                                        <img src="{{ $image->temporaryUrl() }}" class="image-preview-thumb" alt="Preview">
                                    @endif
                                    <button type="button"
                                            wire:click="removeImage({{ $index }})"
                                            class="image-preview-remove">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Assignment Type Selection --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-3">Sposób przypisania</label>

                    <div class="space-y-3">
                        <label class="assignment-type-option" wire:key="type-add">
                            <input type="radio" wire:model.live="assignmentType" value="add" class="form-radio">
                            <div>
                                <span class="font-medium">Dodaj do istniejących</span>
                                <p class="text-xs text-gray-400">Zachowaj stare zdjęcia, dodaj nowe</p>
                            </div>
                        </label>

                        <label class="assignment-type-option" wire:key="type-replace">
                            <input type="radio" wire:model.live="assignmentType" value="replace" class="form-radio">
                            <div>
                                <span class="font-medium">Zastąp istniejące</span>
                                <p class="text-xs text-gray-400">Usuń stare zdjęcia, dodaj nowe (pierwsze = główne)</p>
                            </div>
                        </label>

                        <label class="assignment-type-option" wire:key="type-set-main">
                            <input type="radio" wire:model.live="assignmentType" value="set_main" class="form-radio">
                            <div>
                                <span class="font-medium">Ustaw jako główne</span>
                                <p class="text-xs text-gray-400">Dodaj nowe jako główne zdjęcie, reszta jako dodatkowe</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Variant Summary --}}
                <div class="mb-6 bg-blue-900/20 border border-blue-500/30 rounded-lg p-4">
                    <p class="text-gray-300">
                        Przypiszesz <strong class="text-blue-400">{{ count($uploadedImages) }} zdjęć</strong>
                        do <strong class="text-blue-400">{{ count($selectedVariantIds) }} wariantów</strong>
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end space-x-3">
                    <button type="button" @click="show = false" class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button type="submit"
                            class="btn-enterprise-success"
                            {{ empty($uploadedImages) ? 'disabled' : '' }}>
                        Przypisz zdjęcia
                    </button>
                </div>

                @error('assign')
                    <div class="mt-4 bg-red-900/20 border border-red-500/30 rounded-lg p-4">
                        <p class="text-red-400">{{ $message }}</p>
                    </div>
                @enderror
            </form>
        </div>
    </div>
</div>
