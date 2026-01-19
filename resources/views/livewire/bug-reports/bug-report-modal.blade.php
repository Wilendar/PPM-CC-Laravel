{{-- Bug Report Modal Component (NO x-teleport - use high z-index instead) --}}
<div>
    <div x-data="{
        isOpen: @entangle('isOpen'),
        collectDiagnostics() {
            if (window.ppmDiagnostics) {
                const data = window.ppmDiagnostics.getData();
                @this.set('contextUrl', data.url);
                @this.set('browserInfo', data.browser);
                @this.set('consoleErrors', data.consoleErrors);
                @this.set('userActions', data.actions);

                // Parse OS from user agent
                const ua = navigator.userAgent;
                let os = 'Unknown';
                if (ua.includes('Windows')) os = 'Windows';
                else if (ua.includes('Mac')) os = 'macOS';
                else if (ua.includes('Linux')) os = 'Linux';
                else if (ua.includes('Android')) os = 'Android';
                else if (ua.includes('iOS')) os = 'iOS';
                @this.set('osInfo', os);
            }
        }
    }"
         x-show="isOpen"
         x-on:open-bug-report-modal.window="isOpen = true; $nextTick(() => collectDiagnostics())"
         x-cloak
         class="fixed inset-0 overflow-y-auto"
         style="z-index: 999999;"
         aria-labelledby="bug-report-modal-title"
         role="dialog"
         aria-modal="true">

        {{-- Background Overlay --}}
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="isOpen = false; @this.call('close')"
             class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

        {{-- Modal Container --}}
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative" style="z-index: 10;">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.stop
                 class="relative transform overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl enterprise-card">

                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="icon-chip">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <h3 class="text-lg font-semibold text-white" id="bug-report-modal-title">
                                    Zglos problem lub pomysl
                                </h3>
                                <p class="text-sm text-gray-400 mt-0.5">
                                    Twoje zgloszenie pomoze nam ulepszyc system
                                </p>
                            </div>
                        </div>

                        {{-- Close Button --}}
                        <button wire:click="close"
                                class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                            <svg class="w-6 h-6 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <form wire:submit="submit">
                    <div class="px-6 py-6 max-h-[65vh] overflow-y-auto space-y-5">

                        {{-- Type & Severity Row --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Type --}}
                            <div>
                                <label for="bug-type" class="block text-sm font-medium text-gray-300 mb-2">
                                    Typ zgloszenia <span class="text-red-400">*</span>
                                </label>
                                <select id="bug-type"
                                        wire:model="type"
                                        class="form-input-enterprise w-full">
                                    @foreach($this->types as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Severity --}}
                            <div>
                                <label for="bug-severity" class="block text-sm font-medium text-gray-300 mb-2">
                                    Priorytet <span class="text-red-400">*</span>
                                </label>
                                <select id="bug-severity"
                                        wire:model="severity"
                                        class="form-input-enterprise w-full">
                                    @foreach($this->severities as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('severity')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Title --}}
                        <div>
                            <label for="bug-title" class="block text-sm font-medium text-gray-300 mb-2">
                                Tytul <span class="text-red-400">*</span>
                            </label>
                            <input type="text"
                                   id="bug-title"
                                   wire:model="title"
                                   class="form-input-enterprise w-full"
                                   placeholder="Krotki opis problemu lub pomyslu..."
                                   maxlength="255">
                            @error('title')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="bug-description" class="block text-sm font-medium text-gray-300 mb-2">
                                Opis <span class="text-red-400">*</span>
                            </label>
                            <textarea id="bug-description"
                                      wire:model="description"
                                      rows="4"
                                      class="form-input-enterprise w-full resize-y"
                                      placeholder="Opisz szczegolowo co sie stalo lub co chcialby widziec..."
                                      maxlength="5000"></textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Steps to Reproduce (for bugs) --}}
                        <div x-show="$wire.type === 'bug'" x-cloak>
                            <label for="bug-steps" class="block text-sm font-medium text-gray-300 mb-2">
                                Kroki do odtworzenia (opcjonalne)
                            </label>
                            <textarea id="bug-steps"
                                      wire:model="stepsToReproduce"
                                      rows="3"
                                      class="form-input-enterprise w-full resize-y"
                                      placeholder="1. Kliknij w...
2. Otworz...
3. Widze blad..."
                                      maxlength="3000"></textarea>
                            @error('stepsToReproduce')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Screenshot Upload --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Zrzut ekranu (opcjonalne)
                            </label>

                            @if($screenshot)
                                {{-- Preview --}}
                                <div class="relative inline-block">
                                    <img src="{{ $screenshot->temporaryUrl() }}"
                                         alt="Screenshot preview"
                                         class="max-h-32 rounded-lg border border-gray-600">
                                    <button type="button"
                                            wire:click="removeScreenshot"
                                            class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @else
                                {{-- Upload Area --}}
                                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-600 rounded-lg cursor-pointer hover:border-gray-500 hover:bg-gray-800/30 transition-colors">
                                    <div class="flex flex-col items-center justify-center pt-2 pb-2">
                                        <svg class="w-8 h-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-400">Kliknij lub przeciagnij obrazek</p>
                                        <p class="text-xs text-gray-500">PNG, JPG do 5MB</p>
                                    </div>
                                    <input type="file"
                                           wire:model="screenshot"
                                           class="hidden"
                                           accept="image/png,image/jpeg,image/jpg,image/gif">
                                </label>
                            @endif
                            @error('screenshot')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror

                            {{-- Upload Progress --}}
                            <div wire:loading wire:target="screenshot" class="mt-2">
                                <div class="flex items-center gap-2 text-sm text-gray-400">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Przesylanie...
                                </div>
                            </div>
                        </div>

                        {{-- Diagnostics Info (collapsed) --}}
                        <details class="bg-gray-800/30 rounded-lg border border-gray-700/50">
                            <summary class="px-4 py-3 cursor-pointer text-sm text-gray-400 hover:text-gray-300 transition-colors">
                                <span class="ml-2">Informacje diagnostyczne (automatyczne)</span>
                            </summary>
                            <div class="px-4 pb-4 space-y-2 text-xs font-mono text-gray-500">
                                <p><span class="text-gray-400">URL:</span> {{ $contextUrl ?? 'N/A' }}</p>
                                <p><span class="text-gray-400">Przegladarka:</span> {{ Str::limit($browserInfo ?? 'N/A', 60) }}</p>
                                <p><span class="text-gray-400">System:</span> {{ $osInfo ?? 'N/A' }}</p>
                                @if($consoleErrors && count($consoleErrors) > 0)
                                    <p><span class="text-gray-400">Bledy konsoli:</span> {{ count($consoleErrors) }}</p>
                                @endif
                                @if($userActions && count($userActions) > 0)
                                    <p><span class="text-gray-400">Ostatnie akcje:</span> {{ count($userActions) }}</p>
                                @endif
                            </div>
                        </details>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="close"
                                    class="btn-enterprise-secondary">
                                Anuluj
                            </button>
                            <button type="submit"
                                    class="btn-enterprise-primary"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="submit"
                                    @if($isSubmitting) disabled @endif>
                                <span wire:loading.remove wire:target="submit">
                                    Wyslij zgloszenie
                                </span>
                                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Wysylanie...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
