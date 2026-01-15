<div>
    {{-- Modal Wrapper --}}
    @if($showModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-data="{ showModal: @entangle('showModal') }"
        x-show="showModal"
        x-cloak
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/60"
            @click="$wire.close()"
        ></div>

        {{-- Modal Content --}}
        <div class="relative z-10 w-full max-w-4xl max-h-[90vh] overflow-hidden bg-slate-800 rounded-lg shadow-xl border border-slate-700">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700">
                <h2 class="text-xl font-semibold text-white">
                    Import z pliku CSV/Excel
                </h2>
                <button
                    wire:click="close"
                    class="p-2 text-slate-400 hover:text-white transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Progress Steps --}}
            <div class="px-6 py-4 border-b border-slate-700">
                <div class="flex items-center justify-center space-x-4">
                    {{-- Step 1: Upload --}}
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 'upload' ? 'bg-orange-500 text-white' : ($this->getStepIndex() > 1 ? 'bg-green-500 text-white' : 'bg-slate-600 text-slate-400') }}">
                            @if($this->getStepIndex() > 1)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                1
                            @endif
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep === 'upload' ? 'text-white font-medium' : 'text-slate-400' }}">Upload</span>
                    </div>

                    <div class="w-12 h-0.5 {{ $this->getStepIndex() > 1 ? 'bg-green-500' : 'bg-slate-600' }}"></div>

                    {{-- Step 2: Mapping --}}
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 'mapping' ? 'bg-orange-500 text-white' : ($this->getStepIndex() > 2 ? 'bg-green-500 text-white' : 'bg-slate-600 text-slate-400') }}">
                            @if($this->getStepIndex() > 2)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                2
                            @endif
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep === 'mapping' ? 'text-white font-medium' : 'text-slate-400' }}">Mapowanie</span>
                    </div>

                    <div class="w-12 h-0.5 {{ $this->getStepIndex() > 2 ? 'bg-green-500' : 'bg-slate-600' }}"></div>

                    {{-- Step 3: Preview --}}
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 'preview' ? 'bg-orange-500 text-white' : ($this->getStepIndex() > 3 ? 'bg-green-500 text-white' : 'bg-slate-600 text-slate-400') }}">
                            @if($this->getStepIndex() > 3)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                3
                            @endif
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep === 'preview' ? 'text-white font-medium' : 'text-slate-400' }}">Podglad</span>
                    </div>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                {{-- Errors --}}
                @if(!empty($errors))
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-500 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-red-400">Bledy:</h4>
                                <ul class="mt-1 text-sm text-red-300 list-disc list-inside">
                                    @foreach($errors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 1: Upload --}}
                @if($currentStep === 'upload')
                    @include('livewire.products.import.modals.partials.csv-upload-zone')
                @endif

                {{-- Step 2: Column Mapping --}}
                @if($currentStep === 'mapping')
                    @include('livewire.products.import.modals.partials.csv-column-mapping')
                @endif

                {{-- Step 3: Preview --}}
                @if($currentStep === 'preview')
                    @include('livewire.products.import.modals.partials.csv-preview-table')
                @endif

                {{-- Step 4: Result --}}
                @if($currentStep === 'result')
                    @include('livewire.products.import.modals.partials.csv-import-result')
                @endif
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-700 bg-slate-800/50">
                <div>
                    @if($currentStep !== 'upload' && $currentStep !== 'result')
                        <button
                            wire:click="goBack"
                            class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors"
                        >
                            Wstecz
                        </button>
                    @endif
                </div>

                <div class="flex items-center space-x-3">
                    @if($currentStep !== 'result')
                        <button
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors"
                        >
                            Anuluj
                        </button>
                    @endif

                    @if($currentStep === 'upload' && !empty($parsedData['headers']))
                        <button
                            wire:click="goToMapping"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors"
                        >
                            Dalej: Mapowanie kolumn
                        </button>
                    @endif

                    @if($currentStep === 'mapping')
                        <button
                            wire:click="goToPreview"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(!$this->isSkuMapped()) disabled @endif
                        >
                            Dalej: Podglad
                        </button>
                    @endif

                    @if($currentStep === 'preview')
                        <button
                            wire:click="import"
                            wire:loading.attr="disabled"
                            wire:target="import"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="import">
                                Importuj {{ count($this->getMappedRows()) }} produktow
                            </span>
                            <span wire:loading wire:target="import" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Importowanie...
                            </span>
                        </button>
                    @endif

                    @if($currentStep === 'result')
                        <button
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-colors"
                        >
                            Zamknij
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
