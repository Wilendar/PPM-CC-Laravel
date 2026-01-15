{{-- Upload Zone Partial --}}
<div class="csv-upload-zone">
    {{-- Drag & Drop Zone --}}
    <div
        x-data="{
            isDragging: false,
            handleDrop(e) {
                this.isDragging = false;
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    @this.uploadMultiple('uploadedFile', files);
                }
            }
        }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
        :class="{ 'border-orange-500 bg-orange-500/10': isDragging }"
        class="border-2 border-dashed border-slate-600 rounded-lg p-8 text-center transition-all hover:border-slate-500"
    >
        {{-- Upload Icon --}}
        <div class="flex justify-center mb-4">
            <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
        </div>

        <p class="text-lg text-slate-300 mb-2">Przeciagnij plik CSV/Excel tutaj</p>
        <p class="text-sm text-slate-500 mb-4">lub</p>

        <label for="csv-file-upload" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg cursor-pointer transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Wybierz plik
        </label>

        <input
            id="csv-file-upload"
            type="file"
            wire:model="uploadedFile"
            accept=".csv,.xlsx,.xls,.txt"
            class="hidden"
        >

        <p class="text-xs text-slate-500 mt-4">Maksymalny rozmiar: 50MB | Formaty: CSV, XLSX, XLS</p>
    </div>

    {{-- Upload Progress --}}
    @if($isUploading)
        <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-slate-400">Wgrywanie pliku...</span>
                <span class="text-sm text-slate-400">{{ $uploadProgress }}%</span>
            </div>
            <div class="w-full bg-slate-700 rounded-full h-2">
                <div class="bg-orange-500 h-2 rounded-full transition-all" style="width: {{ $uploadProgress }}%"></div>
            </div>
        </div>
    @endif

    {{-- Livewire upload progress --}}
    <div wire:loading wire:target="uploadedFile" class="mt-4">
        <div class="flex items-center justify-center space-x-2 text-slate-400">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm">Przetwarzanie pliku...</span>
        </div>
    </div>

    {{-- Parse Success --}}
    @if(!empty($parsedData['headers']) && !$isUploading)
        <div class="mt-6 p-4 bg-green-500/20 border border-green-500 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-green-400">Plik wczytany pomyslnie</h4>
                    <div class="mt-2 text-sm text-green-300">
                        <p>{{ $this->getUploadedFileName() }}</p>
                        <p class="mt-1">
                            <span class="font-medium">{{ $parsedData['total_rows'] }}</span> wierszy |
                            <span class="font-medium">{{ count($parsedData['headers']) }}</span> kolumn
                        </p>

                        @if($fileType === 'csv')
                            <p class="mt-1 text-xs text-green-400/70">
                                Separator: <code class="px-1 py-0.5 bg-slate-700 rounded">{{ $parsedData['detected_delimiter'] === "\t" ? 'TAB' : $parsedData['detected_delimiter'] }}</code> |
                                Kodowanie: <code class="px-1 py-0.5 bg-slate-700 rounded">{{ $parsedData['detected_encoding'] }}</code>
                            </p>
                        @else
                            <p class="mt-1 text-xs text-green-400/70">
                                Arkusz: <code class="px-1 py-0.5 bg-slate-700 rounded">{{ $parsedData['sheet_name'] }}</code>
                            </p>
                        @endif
                    </div>

                    {{-- Clear file button --}}
                    <button
                        wire:click="clearUploadedFile"
                        class="mt-3 text-xs text-slate-400 hover:text-white transition-colors"
                    >
                        Wgraj inny plik
                    </button>
                </div>
            </div>
        </div>

        {{-- Preview of detected columns --}}
        <div class="mt-4">
            <h4 class="text-sm font-medium text-slate-300 mb-2">Wykryte kolumny:</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($parsedData['headers'] as $header)
                    <span class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded">
                        {{ $header }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
