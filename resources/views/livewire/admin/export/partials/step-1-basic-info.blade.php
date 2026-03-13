<div class="space-y-5">
    <h2 class="mb-4 text-lg font-semibold text-white">Informacje podstawowe</h2>

    {{-- Name --}}
    <div>
        <label for="profile-name" class="mb-1 block text-sm font-medium text-gray-300">Nazwa profilu *</label>
        <input wire:model="name" id="profile-name" type="text"
               class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
               placeholder="np. Feed Google Shopping">
        @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Format --}}
    <div>
        <label for="profile-format" class="mb-1 block text-sm font-medium text-gray-300">Format eksportu *</label>
        <select wire:model="format" id="profile-format"
                class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
            @foreach($this->getFormatOptions() as $key => $option)
                <option value="{{ $key }}">{{ $option['label'] }} - {{ $option['description'] }}</option>
            @endforeach
        </select>
        @error('format') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Schedule --}}
    <div>
        <label for="profile-schedule" class="mb-1 block text-sm font-medium text-gray-300">Harmonogram generowania</label>
        <select wire:model="schedule" id="profile-schedule"
                class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
            <option value="manual">Reczny</option>
            <option value="1h">Co godzine</option>
            <option value="6h">Co 6 godzin</option>
            <option value="12h">Co 12 godzin</option>
            <option value="24h">Raz dziennie</option>
        </select>
        @error('schedule') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Collapsible Security Section --}}
    <div x-data="{ securityOpen: false }" class="mt-6 border-t border-gray-700 pt-4">
        <button @click="securityOpen = !securityOpen" type="button"
                class="flex w-full items-center justify-between text-sm font-medium text-gray-300 transition-colors hover:text-white">
            <span class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Ustawienia bezpieczenstwa (opcjonalne)
            </span>
            <svg class="h-4 w-4 transition-transform" :class="securityOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="securityOpen" x-collapse class="mt-4 space-y-4">
            {{-- Token Expiry --}}
            <div>
                <label for="token-expiry" class="mb-1 block text-sm font-medium text-gray-300">Waznosc tokena</label>
                <select wire:model="tokenExpiryDays" id="token-expiry"
                        class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                    <option value="">Bez wygasania</option>
                    <option value="7">7 dni</option>
                    <option value="30">30 dni</option>
                    <option value="90">90 dni</option>
                    <option value="180">180 dni</option>
                    <option value="365">1 rok</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Po wygasnieciu token przestanie dzialac. Mozna go pozniej zregenerowac.</p>
            </div>

            {{-- IP Whitelist --}}
            <div>
                <label for="allowed-ips" class="mb-1 block text-sm font-medium text-gray-300">IP Whitelist</label>
                <textarea wire:model="allowedIpsText" id="allowed-ips"
                          class="w-full rounded-lg border-gray-600 bg-gray-700 px-4 py-2 text-white placeholder-gray-400 focus:border-[#e0ac7e] focus:ring-[#e0ac7e]"
                          placeholder="Jedno IP na linie, np.&#10;192.168.1.1&#10;10.0.0.5"
                          rows="4"></textarea>
                <p class="mt-1 text-xs text-gray-500">Pozostaw puste = dostep z kazdego IP. Tylko poprawne adresy IP beda zapisane.</p>
            </div>
        </div>
    </div>
</div>
