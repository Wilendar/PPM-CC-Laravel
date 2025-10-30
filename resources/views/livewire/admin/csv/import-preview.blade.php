{{-- CSV Import Preview Component --}}
<div>

        {{-- Step Indicator --}}
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $step === 'upload' ? 'bg-[#e0ac7e]' : 'bg-green-500' }} text-white font-bold">
                        @if($step === 'upload')
                            1
                        @else
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </div>
                    <span class="ml-3 text-white font-medium">Upload pliku</span>
                </div>

                <div class="w-16 h-1 {{ in_array($step, ['preview', 'processing', 'complete']) ? 'bg-[#e0ac7e]' : 'bg-gray-600' }}"></div>

                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ in_array($step, ['preview']) ? 'bg-[#e0ac7e]' : (in_array($step, ['processing', 'complete']) ? 'bg-green-500' : 'bg-gray-600') }} text-white font-bold">
                        @if($step === 'preview')
                            2
                        @elseif(in_array($step, ['processing', 'complete']))
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            2
                        @endif
                    </div>
                    <span class="ml-3 text-white font-medium">Podgląd i walidacja</span>
                </div>

                <div class="w-16 h-1 {{ in_array($step, ['processing', 'complete']) ? 'bg-[#e0ac7e]' : 'bg-gray-600' }}"></div>

                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $step === 'processing' ? 'bg-[#e0ac7e]' : ($step === 'complete' ? 'bg-green-500' : 'bg-gray-600') }} text-white font-bold">
                        @if($step === 'complete')
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            3
                        @endif
                    </div>
                    <span class="ml-3 text-white font-medium">Import</span>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session()->has('success'))
            <div class="mb-6 bg-green-900 bg-opacity-20 border border-green-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mb-6 bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-300">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- STEP 1: Upload Section --}}
        @if($step === 'upload')
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-8 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);"
             x-data="{ dragging: false }">

            <div class="text-center">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    Upload pliku CSV/XLSX
                </h3>

                {{-- Dropzone Area --}}
                <div class="mt-6"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }))">

                    <label for="csvFile"
                           class="block cursor-pointer"
                           :class="dragging ? 'bg-[#e0ac7e] bg-opacity-20' : 'bg-gray-800 bg-opacity-40'">
                        <div class="border-2 border-dashed rounded-lg p-12 transition-all duration-200"
                             :class="dragging ? 'border-[#e0ac7e]' : 'border-gray-600 hover:border-[#e0ac7e]'">

                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>

                            <div class="mt-4 flex text-sm leading-6 text-gray-400 justify-center">
                                <span class="relative cursor-pointer rounded-md font-semibold text-[#e0ac7e] hover:text-[#d1975a]">
                                    Wybierz plik
                                </span>
                                <p class="pl-2">lub przeciągnij i upuść</p>
                            </div>
                            <p class="text-xs leading-5 text-gray-400 mt-2">CSV, XLSX do 10MB</p>

                            <input id="csvFile"
                                   type="file"
                                   wire:model="csvFile"
                                   accept=".csv,.xlsx"
                                   class="sr-only"
                                   x-ref="fileInput">
                        </div>
                    </label>
                </div>

                {{-- Upload Progress --}}
                <div wire:loading wire:target="csvFile" class="mt-6">
                    <div class="flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-[#e0ac7e]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-white font-medium">Przetwarzanie pliku...</span>
                    </div>
                </div>

                {{-- Template Downloads --}}
                <div class="mt-8 pt-6 border-t border-gray-600">
                    <h4 class="text-sm font-medium text-gray-300 mb-4">Pobierz szablon CSV:</h4>
                    <div class="flex justify-center space-x-4">
                        <a href="{{ route('admin.csv.template', 'variants') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Warianty
                        </a>
                        <a href="{{ route('admin.csv.template', 'features') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Cechy
                        </a>
                        <a href="{{ route('admin.csv.template', 'compatibility') }}"
                           class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Dopasowania
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 2: Preview & Validation Section --}}
        @if($step === 'preview')
        <div class="space-y-6">

            {{-- Statistics Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Całkowite wiersze</p>
                            <p class="text-2xl font-bold text-white">{{ $totalRows }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Poprawne</p>
                            <p class="text-2xl font-bold text-white">{{ $validRows }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Błędy</p>
                            <p class="text-2xl font-bold text-white">{{ $errorRows }}</p>
                        </div>
                    </div>
                </div>

                <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                     style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Konflikty</p>
                            <p class="text-2xl font-bold text-white">{{ $conflictRows }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column Mapping Preview --}}
            <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Mapowanie kolumn
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-600">
                        <thead class="bg-gray-800 bg-opacity-40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Kolumna CSV</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Wykryte pole</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Przykładowa wartość</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($columnMappings as $csvColumn => $fieldName)
                            <tr class="hover:bg-gray-700 hover:bg-opacity-30 transition-colors duration-150" wire:key="mapping-{{ $loop->index }}">
                                <td class="px-4 py-3 text-sm font-medium text-white">{{ $csvColumn }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#e0ac7e] bg-opacity-20 text-[#e0ac7e]">
                                        {{ $fieldName }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400 truncate max-w-xs">
                                    @if(isset($previewRows[0][$csvColumn]))
                                        {{ $previewRows[0][$csvColumn] }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Preview Data Table --}}
            <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Podgląd danych (pierwsze 10 wierszy)
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-600">
                        <thead class="bg-gray-800 bg-opacity-40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">#</th>
                                @foreach($headerRow as $header)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">{{ $header }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($previewRows as $index => $row)
                            <tr class="hover:bg-gray-700 hover:bg-opacity-30 transition-colors duration-150" wire:key="preview-{{ $index }}">
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $index + 2 }}</td>
                                @foreach($headerRow as $header)
                                <td class="px-4 py-3 text-sm text-white truncate max-w-xs">{{ $row[$header] ?? '-' }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-sm">
                                    @if(isset($validationErrors[$index]))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Błąd
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            OK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if(count($validationErrors) > 0)
            <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Błędy walidacji ({{ count($validationErrors) }})
                    </h3>

                    @if(session()->has('error_report_path'))
                    <a href="{{ Storage::url(session('error_report_path')) }}"
                       download
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Pobierz raport błędów
                    </a>
                    @endif
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($validationErrors as $rowIndex => $errors)
                        <div class="bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-red-300 mb-2">Wiersz {{ $rowIndex + 2 }}:</h4>
                            <ul class="list-disc list-inside space-y-1 text-sm text-red-200">
                                @foreach($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Conflict Resolution --}}
            @if(count($conflicts) > 0)
            <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    Rozwiązywanie konfliktów ({{ count($conflicts) }})
                </h3>

                <div class="mb-4 p-4 bg-yellow-900 bg-opacity-20 border border-yellow-500 border-opacity-30 rounded-lg">
                    <p class="text-sm text-yellow-300">
                        Wykryto rekordy które już istnieją w bazie danych. Wybierz akcję dla wszystkich konfliktów:
                    </p>
                </div>

                <div class="space-y-3">
                    <label class="flex items-center p-3 bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg hover:bg-opacity-60 cursor-pointer transition-colors duration-200">
                        <input type="radio"
                               wire:model="conflictResolution"
                               value="skip"
                               class="text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="ml-3 text-white">
                            <span class="font-medium">Pomiń</span> - Nie importuj duplikatów
                        </span>
                    </label>

                    <label class="flex items-center p-3 bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg hover:bg-opacity-60 cursor-pointer transition-colors duration-200">
                        <input type="radio"
                               wire:model="conflictResolution"
                               value="overwrite"
                               class="text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="ml-3 text-white">
                            <span class="font-medium">Nadpisz</span> - Zastąp istniejące dane nowymi wartościami
                        </span>
                    </label>

                    <label class="flex items-center p-3 bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg hover:bg-opacity-60 cursor-pointer transition-colors duration-200">
                        <input type="radio"
                               wire:model="conflictResolution"
                               value="update"
                               class="text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="ml-3 text-white">
                            <span class="font-medium">Aktualizuj zmiany</span> - Aktualizuj tylko pola które się różnią
                        </span>
                    </label>
                </div>

                <div class="mt-4 space-y-2 max-h-64 overflow-y-auto">
                    @foreach($conflicts as $conflict)
                        <div class="text-sm text-gray-300 p-2 bg-gray-700 bg-opacity-40 rounded">
                            {{ $conflict }}
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex items-center justify-between pt-6">
                <button wire:click="resetImport"
                        class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                    Anuluj
                </button>

                <div class="flex space-x-3">
                    <button wire:click="processImport"
                            wire:loading.attr="disabled"
                            :disabled="{{ $errorRows > 0 ? 'true' : 'false' }}"
                            class="relative px-6 py-3 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background: linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8)); border: 1px solid rgba(34, 197, 94, 0.5);">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span wire:loading.remove wire:target="processImport">Wykonaj import ({{ $totalRows }} wierszy)</span>
                        <span wire:loading wire:target="processImport">Importowanie...</span>
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 3: Processing Section --}}
        @if($step === 'processing')
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <div class="text-center">
                <div class="mx-auto w-16 h-16 mb-6">
                    <svg class="animate-spin text-[#e0ac7e]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-white mb-4">Import w trakcie...</h3>
                <p class="text-gray-400 mb-8">Przetwarzanie {{ $totalRows }} wierszy. Proszę czekać...</p>

                {{-- Progress Bar --}}
                <div class="max-w-md mx-auto">
                    <div class="w-full bg-gray-700 rounded-full h-3">
                        <div class="bg-[#e0ac7e] h-3 rounded-full transition-all duration-500"
                             style="width: 50%"></div>
                    </div>
                    <p class="text-sm text-gray-400 mt-2">Przetwarzanie w partiach po 100 wierszy...</p>
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 4: Complete Section --}}
        @if($step === 'complete')
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-white mb-4">Import zakończony pomyślnie!</h3>
                <p class="text-gray-400 mb-8">Zaimportowano {{ $validRows }} wierszy z {{ $totalRows }} całkowitych.</p>

                {{-- Results Summary --}}
                <div class="max-w-md mx-auto mb-8">
                    <div class="bg-gray-800 bg-opacity-40 rounded-lg p-6 space-y-3 text-left">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Pomyślne:</span>
                            <span class="text-green-400 font-medium">{{ $validRows }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Błędy:</span>
                            <span class="text-red-400 font-medium">{{ $errorRows }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Całkowite:</span>
                            <span class="text-white font-medium">{{ $totalRows }}</span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-center space-x-4">
                    <button wire:click="resetImport"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Importuj kolejny plik
                    </button>
                    <a href="{{ route('admin.dashboard') }}"
                       class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Powrót do panelu
                    </a>
                </div>
            </div>
        </div>
        @endif
</div>
