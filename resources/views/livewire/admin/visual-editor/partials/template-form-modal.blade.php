{{-- Template Form Modal (Create/Edit) - ETAP_07f FAZA 5 --}}
@teleport('body')
<div
    x-data="{
        show: true,
        cid: '{{ $this->getId() }}'
    }"
    x-show="show"
    x-cloak
    @keydown.escape.window="Livewire.find(cid).call('closeFormModal')"
    class="fixed inset-0 z-50"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/70 backdrop-blur-sm"
        wire:click="closeFormModal"
    ></div>

    {{-- Modal Content --}}
    <div class="relative z-10 h-full flex items-center justify-center p-4">
        <div
            class="bg-gray-800 rounded-xl shadow-2xl max-w-xl w-full border border-gray-700"
            @click.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-gray-100">
                    @if($editingTemplateId)
                        Edytuj szablon
                    @else
                        Nowy szablon
                    @endif
                </h2>
                <button
                    wire:click="closeFormModal"
                    class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded-lg transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form Body --}}
            <form wire:submit.prevent="saveTemplate" class="p-6 space-y-5">
                {{-- Name Field --}}
                <div>
                    <label for="templateName" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Nazwa szablonu <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="text"
                        id="templateName"
                        wire:model="form.name"
                        class="w-full px-4 py-2.5 bg-gray-900 border rounded-lg text-gray-100 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('form.name') ? 'border-red-500' : 'border-gray-700' }}"
                        placeholder="Np. Opis produktu motocyklowego"
                        autofocus
                    >
                    @error('form.name')
                        <p class="mt-1.5 text-xs text-red-400 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Description Field --}}
                <div>
                    <label for="templateDescription" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Opis
                        <span class="text-gray-500 font-normal">(opcjonalny)</span>
                    </label>
                    <textarea
                        id="templateDescription"
                        wire:model="form.description"
                        rows="3"
                        class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                        placeholder="Krotki opis szablonu i jego przeznaczenia..."
                    ></textarea>
                    @error('form.description')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Shop Field --}}
                <div>
                    <label for="templateShop" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Przypisany sklep
                    </label>
                    <select
                        id="templateShop"
                        wire:model="form.shop_id"
                        class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                        <option value="">Globalny (wszystkie sklepy)</option>
                        @foreach($shops ?? [] as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Szablony globalne sa dostepne we wszystkich sklepach. Szablony przypisane do sklepu beda dostepne tylko dla tego sklepu.
                    </p>
                    @error('form.shop_id')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category Field --}}
                <div>
                    <label for="templateCategory" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Kategoria szablonu
                    </label>
                    <select
                        id="templateCategory"
                        wire:model="form.category"
                        class="w-full px-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                        <option value="">Bez kategorii</option>
                        @foreach($templateCategories ?? [] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('form.category')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    {{-- Custom Category Input --}}
                    <div class="mt-2">
                        <button
                            type="button"
                            x-data="{ showCustom: false }"
                            @click="showCustom = !showCustom"
                            class="text-xs text-blue-400 hover:text-blue-300 transition"
                        >
                            + Dodaj nowa kategorie
                        </button>
                        <div
                            x-data="{ showCustom: false }"
                            x-show="showCustom"
                            x-cloak
                            class="mt-2"
                        >
                            <input
                                type="text"
                                wire:model="form.custom_category"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 text-sm"
                                placeholder="Nazwa nowej kategorii..."
                            >
                        </div>
                    </div>
                </div>

                {{-- Is Public Checkbox --}}
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            type="checkbox"
                            id="templateIsPublic"
                            wire:model="form.is_public"
                            class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500 focus:ring-offset-gray-800"
                        >
                    </div>
                    <div class="ml-3">
                        <label for="templateIsPublic" class="text-sm font-medium text-gray-300">
                            Szablon publiczny
                        </label>
                        <p class="text-xs text-gray-500">
                            Publiczne szablony sa widoczne dla wszystkich uzytkownikow. Prywatne szablony sa widoczne tylko dla autora i adminow.
                        </p>
                    </div>
                </div>

                {{-- Blocks Info (Edit Mode) --}}
                @if($editingTemplateId && $editingTemplate)
                    <div class="p-4 bg-gray-900/50 border border-gray-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-400">
                                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                <span>{{ count($editingTemplate->blocks_json ?? []) }} blokow w szablonie</span>
                            </div>
                            <a
                                href="{{ route('admin.visual-editor.edit', $editingTemplateId) }}"
                                class="text-xs text-blue-400 hover:text-blue-300 transition flex items-center"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edytuj bloki
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Error Summary --}}
                @if($errors->any())
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-red-400">Popraw bledy przed zapisaniem</h4>
                                <ul class="mt-1 text-xs text-red-300 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700">
                <button
                    wire:click="closeFormModal"
                    class="btn-enterprise-secondary"
                >
                    Anuluj
                </button>
                <button
                    wire:click="saveTemplate"
                    wire:loading.attr="disabled"
                    wire:target="saveTemplate"
                    class="btn-enterprise-primary relative"
                >
                    <span wire:loading.remove wire:target="saveTemplate" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        @if($editingTemplateId)
                            Zapisz zmiany
                        @else
                            Utworz szablon
                        @endif
                    </span>
                    <span wire:loading wire:target="saveTemplate" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Zapisywanie...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endteleport
