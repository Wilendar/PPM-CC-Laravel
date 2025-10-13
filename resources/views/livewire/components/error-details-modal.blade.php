{{-- Error Details Modal Component --}}
<template x-teleport="body">
    <div x-data="{ isOpen: @entangle('isOpen') }"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 overflow-y-auto"
         style="z-index: 999999;"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

    <!-- Background Overlay -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative" style="z-index: 10;">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative transform overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.98), rgba(17, 24, 39, 0.98)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-700/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-500/20 border border-red-500/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-semibold text-white" id="modal-title">
                                Szczegóły Błędów
                            </h3>
                            <p class="text-sm text-gray-400 mt-0.5">
                                Znaleziono {{ count($errors) }} {{ count($errors) === 1 ? 'błąd' : 'błędów' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Export CSV Button -->
                        <button wire:click="exportToCsv"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-200 hover:scale-105"
                                style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.15), rgba(209, 151, 90, 0.15)); border: 1px solid rgba(224, 172, 126, 0.3); color: #e0ac7e;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Eksportuj CSV
                        </button>

                        <!-- Close Button -->
                        <button @click="isOpen = false"
                                class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                            <svg class="w-6 h-6 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-6 max-h-[60vh] overflow-y-auto">
                @if(empty($errors))
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="mt-4 text-gray-400">Brak błędów do wyświetlenia</p>
                    </div>
                @else
                    <!-- Errors Table -->
                    <div class="overflow-hidden rounded-lg border border-gray-700/50">
                        <table class="min-w-full divide-y divide-gray-700/50">
                            <thead style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.1));">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: #e0ac7e;">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: #e0ac7e;">
                                        SKU
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: #e0ac7e;">
                                        Komunikat Błędu
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                @foreach($errors as $index => $error)
                                    <tr class="hover:bg-gray-700/20 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                            {{ $error['sku'] ?? 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300">
                                            <div class="max-w-2xl break-words">
                                                {{ $error['message'] ?? 'No message' }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-400">
                        Job ID: <span class="font-mono text-white">{{ $jobId }}</span>
                    </p>
                    <button @click="isOpen = false"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

{{-- JavaScript for CSV Download --}}
@script
<script>
$wire.on('download-csv', (event) => {
    const data = event.detail || event[0] || {};
    const filename = data.filename || 'errors.csv';
    const csvData = data.data || '';

    // Create blob and download
    const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
});
</script>
@endscript
