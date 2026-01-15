{{-- Import Result Partial --}}
<div class="csv-import-result">
    {{-- Success Header --}}
    @if($importResult['created'] > 0)
        <div class="text-center mb-6">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-semibold text-white">Import zakonczony!</h3>
            <p class="text-sm text-slate-400 mt-2">Produkty zostaly dodane do listy oczekujacych</p>
        </div>
    @else
        <div class="text-center mb-6">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 rounded-full bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-semibold text-white">Import zakonczony z ostrzezeniami</h3>
            <p class="text-sm text-slate-400 mt-2">Nie udalo sie zaimportowac wszystkich produktow</p>
        </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        {{-- Created --}}
        <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-lg text-center">
            <div class="text-3xl font-bold text-green-400">{{ $importResult['created'] }}</div>
            <div class="text-sm text-green-400/70 mt-1">Utworzonych</div>
        </div>

        {{-- Skipped --}}
        <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-lg text-center">
            <div class="text-3xl font-bold text-amber-400">{{ $importResult['skipped'] }}</div>
            <div class="text-sm text-amber-400/70 mt-1">Pominietych</div>
        </div>

        {{-- Errors --}}
        <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-lg text-center">
            <div class="text-3xl font-bold text-red-400">{{ count($importResult['errors']) }}</div>
            <div class="text-sm text-red-400/70 mt-1">Bledow</div>
        </div>
    </div>

    {{-- Error list --}}
    @if(!empty($importResult['errors']))
        <div class="mt-6">
            <h4 class="text-sm font-medium text-slate-300 mb-3">
                Szczegoly bledow ({{ count($importResult['errors']) }}):
            </h4>

            <div class="max-h-48 overflow-y-auto border border-slate-700 rounded-lg">
                <table class="w-full">
                    <thead class="sticky top-0 bg-slate-700">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase">Wiersz</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase">SKU</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase">Blad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @foreach(array_slice($importResult['errors'], 0, 50) as $error)
                            <tr class="hover:bg-slate-700/30">
                                <td class="px-3 py-2 text-sm text-slate-400">
                                    {{ $error['row'] ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-sm text-orange-400 font-mono">
                                    {{ $error['sku'] ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-sm text-red-300">
                                    {{ $error['message'] ?? 'Nieznany blad' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($importResult['errors']) > 50)
                <div class="mt-2 text-xs text-slate-500 text-center">
                    Pokazano 50 z {{ count($importResult['errors']) }} bledow
                </div>
            @endif
        </div>
    @endif

    {{-- Next steps --}}
    <div class="mt-6 p-4 bg-slate-700/30 rounded-lg border border-slate-600">
        <h4 class="text-sm font-medium text-white mb-2">Nastepne kroki:</h4>
        <ul class="text-sm text-slate-400 space-y-1">
            <li class="flex items-start">
                <svg class="w-4 h-4 text-orange-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                </svg>
                Przejdz do zakladki "Oczekujace" aby przejrzec zaimportowane produkty
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 text-orange-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                </svg>
                Zweryfikuj dane i zatwierdz produkty do glownej bazy
            </li>
            <li class="flex items-start">
                <svg class="w-4 h-4 text-orange-400 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                </svg>
                Produkty mozesz eksportowac do sklepow PrestaShop
            </li>
        </ul>
    </div>
</div>
