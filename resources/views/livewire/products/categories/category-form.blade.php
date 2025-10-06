{{-- CategoryForm Component - Advanced Category Create/Edit Form --}}
{{-- CSS loaded via admin layout --}}

<div class="category-form-container">
<div class="w-full py-4">
    {{-- Header Section --}}
    <div class="mb-6 px-4 xl:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark-primary mb-2">
                    @if($mode === 'create')
                        <i class="fas fa-plus-circle text-green-400 mr-2"></i>
                        Nowa kategoria
                    @else
                        <i class="fas fa-edit text-mpp-orange mr-2"></i>
                        Edytuj kategorię
                    @endif
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb-dark flex items-center space-x-2 text-sm">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="hover:text-mpp-orange">
                                <i class="fas fa-home"></i> Panel administracyjny
                            </a>
                        </li>
                        <li class="text-dark-muted">></li>
                        <li>
                            <a href="{{ route('admin.products.categories.index') }}" class="hover:text-mpp-orange">
                                <i class="fas fa-sitemap"></i> Kategorie
                            </a>
                        </li>
                        <li class="text-dark-muted">></li>
                        <li class="text-dark-secondary">
                            {{ $mode === 'create' ? 'Nowa kategoria' : 'Edycja' }}
                        </li>
                    </ol>
                </nav>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('admin.products.categories.index') }}"
                   class="btn-enterprise-secondary">
                    <i class="fas fa-times"></i>
                    Anuluj
                </a>
                <button wire:click="save"
                        class="btn-enterprise-primary relative"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-save"></i>
                        {{ $mode === 'create' ? 'Utwórz kategorię' : 'Zapisz zmiany' }}
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="fas fa-spinner fa-spin"></i>
                        Zapisywanie...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    @if (session()->has('message'))
        <div class="alert-dark-success flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert-dark-error flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Main Form --}}
    <form wire:submit.prevent="save">
        <div class="category-form-main-container">
            {{-- Left Column - Form Content --}}
            <div class="category-form-left-column">
                <div class="enterprise-card p-8">
                    {{-- Enterprise Tab Navigation --}}
                    <div class="tabs-enterprise">
                        @foreach($tabs as $tabKey => $tabLabel)
                            <button class="tab-enterprise {{ $activeTab === $tabKey ? 'active' : '' }}"
                                    type="button"
                                    wire:click="setActiveTab('{{ $tabKey }}')">
                                @switch($tabKey)
                                    @case('basic')
                                        <i class="fas fa-edit icon"></i>
                                        @break
                                    @case('seo')
                                        <i class="fas fa-search icon"></i>
                                        @break
                                    @case('visual')
                                        <i class="fas fa-palette icon"></i>
                                        @break
                                    @case('visibility')
                                        <i class="fas fa-eye icon"></i>
                                        @break
                                    @case('defaults')
                                        <i class="fas fa-cog icon"></i>
                                        @break
                                @endswitch
                                <span>{{ $tabLabel }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Tab Content --}}
                    <div class="pt-8">
                        {{-- Basic Information Tab --}}
                        @if($activeTab === 'basic')
                            <div class="space-y-8">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    {{-- Category Name --}}
                                    <div class="lg:col-span-2">
                                        <div class="form-group-enterprise">
                                            <label for="category-name" class="form-label-enterprise">
                                                <i class="fas fa-tag icon"></i>
                                                Nazwa kategorii *
                                            </label>
                                            <input type="text"
                                                   id="category-name"
                                                   class="form-input-enterprise text-lg @error('form.name') border-red-500 @enderror"
                                                   wire:model="form.name"
                                                   placeholder="Wprowadź nazwę kategorii..."
                                                   maxlength="300">
                                            @error('form.name')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Parent Category --}}
                                    <div>
                                        <div class="form-group-enterprise">
                                            <label for="parent-category" class="form-label-enterprise">
                                                <i class="fas fa-sitemap icon"></i>
                                                Kategoria nadrzędna
                                            </label>
                                            <select id="parent-category"
                                                    class="form-input-enterprise @error('form.parent_id') border-red-500 @enderror"
                                                    wire:model="form.parent_id">
                                                <option value="">-- Kategoria główna --</option>
                                                @foreach($parentOptions as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('form.parent_id')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                            <button type="button"
                                                    class="btn-enterprise-secondary text-sm mt-2"
                                                    wire:click="toggleParentTree">
                                                <i class="fas fa-tree mr-2"></i>
                                                {{ $showParentTree ? 'Ukryj drzewo' : 'Pokaż drzewo kategorii' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Slug Generation --}}
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <div class="lg:col-span-2">
                                        <div class="form-group-enterprise">
                                            <label for="category-slug" class="form-label-enterprise">
                                                <i class="fas fa-link icon"></i>
                                                Adres URL (slug)
                                            </label>
                                            <div class="flex">
                                                <input type="text"
                                                       id="category-slug"
                                                       class="form-input-enterprise flex-1 rounded-r-none @error('form.slug') border-red-500 @enderror"
                                                       wire:model="form.slug"
                                                       placeholder="adres-url-kategorii"
                                                       maxlength="300">
                                                <button type="button"
                                                        class="btn-enterprise-secondary px-3 rounded-l-none border-l-0"
                                                        wire:click="regenerateSlug"
                                                        wire:loading.attr="disabled"
                                                        wire:target="regenerateSlug">
                                                    <span wire:loading.remove wire:target="regenerateSlug">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </span>
                                                    <span wire:loading wire:target="regenerateSlug">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                </button>
                                            </div>
                                            @error('form.slug')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                            <div class="flex items-center mt-3">
                                                <input type="checkbox"
                                                       id="auto-slug"
                                                       class="w-4 h-4 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                       wire:model="autoSlug">
                                                <label class="text-dark-secondary text-sm ml-2" for="auto-slug">
                                                    Automatyczne generowanie z nazwy
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Sort Order --}}
                                    <div>
                                        <div class="form-group-enterprise">
                                            <label for="sort-order" class="form-label-enterprise">
                                                <i class="fas fa-sort-numeric-down icon"></i>
                                                Kolejność wyświetlania
                                            </label>
                                            <input type="number"
                                                   id="sort-order"
                                                   class="form-input-enterprise @error('form.sort_order') border-red-500 @enderror"
                                                   wire:model="form.sort_order"
                                                   min="0"
                                                   max="9999"
                                                   placeholder="0">
                                            @error('form.sort_order')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Short Description --}}
                                <div class="form-group-enterprise">
                                    <label for="short-description" class="form-label-enterprise">
                                        <i class="fas fa-align-left icon"></i>
                                        Krótki opis
                                        <span class="text-dark-muted text-sm ml-1">(wyświetlany w listach kategorii)</span>
                                    </label>
                                    <textarea id="short-description"
                                              class="form-input-enterprise @error('form.short_description') border-red-500 @enderror"
                                              wire:model="form.short_description"
                                              rows="3"
                                              maxlength="500"
                                              placeholder="Krótki opis kategorii..."></textarea>
                                    <div class="text-dark-muted text-sm">
                                        {{ strlen($form['short_description'] ?? '') }}/500 znaków
                                    </div>
                                    @error('form.short_description')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Full Description --}}
                                <div class="form-group-enterprise">
                                    <label for="description" class="form-label-enterprise">
                                        <i class="fas fa-align-justify icon"></i>
                                        Pełny opis kategorii
                                    </label>
                                    <textarea id="description"
                                              class="form-input-enterprise @error('form.description') border-red-500 @enderror"
                                              wire:model="form.description"
                                              rows="8"
                                              maxlength="5000"
                                              placeholder="Szczegółowy opis kategorii, informacje o produktach, wskazówki dla klientów..."></textarea>
                                    <div class="flex items-center justify-between text-sm mt-2">
                                        <span class="text-dark-muted">{{ strlen($form['description'] ?? '') }}/5000 znaków</span>
                                        <span class="text-dark-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Obsługuje HTML i markdown
                                        </span>
                                    </div>
                                    @error('form.description')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Status Switches --}}
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   id="is-active"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="form.is_active">
                                            <label class="form-label-enterprise ml-3 cursor-pointer" for="is-active">
                                                <i class="fas fa-toggle-on icon"></i>
                                                Kategoria aktywna
                                            </label>
                                        </div>
                                        <div class="text-dark-muted text-sm ml-8">
                                            Nieaktywne kategorie nie są widoczne w sklepie
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   id="is-featured"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="form.is_featured">
                                            <label class="form-label-enterprise ml-3 cursor-pointer" for="is-featured">
                                                <i class="fas fa-star icon"></i>
                                                Kategoria polecana
                                            </label>
                                        </div>
                                        <div class="text-dark-muted text-sm ml-8">
                                            Kategorie polecane są wyróżnione w menu
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- SEO & Meta Tab --}}
                        @if($activeTab === 'seo')
                            <div class="space-y-8">
                                <h3 class="text-xl font-bold text-dark-primary flex items-center mb-6">
                                    <i class="fas fa-search text-mpp-orange mr-3"></i>
                                    Optymalizacja SEO
                                </h3>

                                {{-- Meta Title --}}
                                <div class="form-group-enterprise">
                                    <label for="meta-title" class="form-label-enterprise">
                                        <i class="fas fa-heading icon"></i>
                                        Meta tytuł
                                    </label>
                                    <input type="text"
                                           id="meta-title"
                                           class="form-input-enterprise @error('seoForm.meta_title') border-red-500 @enderror"
                                           wire:model="seoForm.meta_title"
                                           maxlength="300"
                                           placeholder="Tytuł kategorii w wyszukiwarkach...">
                                    <div class="text-dark-muted text-sm mt-2">
                                        <span>{{ strlen($seoForm['meta_title'] ?? '') }}/300 znaków</span>
                                        <span class="ml-4">
                                            Pozostaw puste aby użyć nazwy kategorii
                                        </span>
                                    </div>
                                    @error('seoForm.meta_title')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Meta Description --}}
                                <div class="form-group-enterprise">
                                    <label for="meta-description" class="form-label-enterprise">
                                        <i class="fas fa-paragraph icon"></i>
                                        Meta opis
                                    </label>
                                    <textarea id="meta-description"
                                              class="form-input-enterprise @error('seoForm.meta_description') border-red-500 @enderror"
                                              wire:model="seoForm.meta_description"
                                              rows="3"
                                              maxlength="300"
                                              placeholder="Opis kategorii wyświetlany w wynikach wyszukiwania..."></textarea>
                                    <div class="text-dark-muted text-sm mt-2">
                                        <span>{{ strlen($seoForm['meta_description'] ?? '') }}/300 znaków</span>
                                        <span class="ml-4">
                                            Optymalnie 150-160 znaków
                                        </span>
                                    </div>
                                    @error('seoForm.meta_description')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Meta Keywords --}}
                                <div class="form-group-enterprise">
                                    <label for="meta-keywords" class="form-label-enterprise">
                                        <i class="fas fa-tags icon"></i>
                                        Słowa kluczowe
                                    </label>
                                    <input type="text"
                                           id="meta-keywords"
                                           class="form-input-enterprise @error('seoForm.meta_keywords') border-red-500 @enderror"
                                           wire:model="seoForm.meta_keywords"
                                           maxlength="500"
                                           placeholder="słowo1, słowo2, fraza kluczowa...">
                                    <div class="text-dark-muted text-sm mt-2">
                                        Oddziel słowa kluczowe przecinkami
                                    </div>
                                    @error('seoForm.meta_keywords')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Canonical URL --}}
                                <div class="form-group-enterprise">
                                    <label for="canonical-url" class="form-label-enterprise">
                                        <i class="fas fa-link icon"></i>
                                        Kanoniczny URL
                                    </label>
                                    <input type="url"
                                           id="canonical-url"
                                           class="form-input-enterprise @error('seoForm.canonical_url') border-red-500 @enderror"
                                           wire:model="seoForm.canonical_url"
                                           placeholder="https://example.com/canonical-url">
                                    <div class="text-dark-muted text-sm mt-2">
                                        Pozostaw puste jeśli nie potrzebujesz przekierowania
                                    </div>
                                    @error('seoForm.canonical_url')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="border-t border-gray-600 pt-6 mt-8">
                                    <h4 class="text-lg font-semibold text-dark-primary flex items-center mb-6">
                                        <i class="fab fa-facebook text-blue-400 mr-3"></i>
                                        Open Graph (Facebook, LinkedIn)
                                    </h4>

                                    {{-- OG Title --}}
                                    <div class="form-group-enterprise">
                                        <label for="og-title" class="form-label-enterprise">
                                            <i class="fab fa-facebook-f icon"></i>
                                            OG Tytuł
                                        </label>
                                    <input type="text"
                                           id="og-title"
                                           class="form-input-enterprise @error('seoForm.og_title') border-red-500 @enderror"
                                           wire:model="seoForm.og_title"
                                           maxlength="300"
                                           placeholder="Tytuł przy udostępnieniu w social media...">
                                    @error('seoForm.og_title')
                                        <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                    {{-- OG Description --}}
                                    <div class="form-group-enterprise">
                                        <label for="og-description" class="form-label-enterprise">
                                            <i class="fab fa-facebook-f icon"></i>
                                            OG Opis
                                        </label>
                                        <textarea id="og-description"
                                                  class="form-input-enterprise @error('seoForm.og_description') border-red-500 @enderror"
                                              wire:model="seoForm.og_description"
                                              rows="2"
                                              maxlength="300"
                                              placeholder="Opis przy udostępnieniu w social media..."></textarea>
                                        @error('seoForm.og_description')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Visual Settings Tab --}}
                        @if($activeTab === 'visual')
                            <div class="space-y-8">
                                <h3 class="text-xl font-bold text-dark-primary flex items-center mb-6">
                                    <i class="fas fa-palette text-mpp-orange mr-3"></i>
                                    Ustawienia wizualne
                                </h3>

                                {{-- Icon Selection --}}
                                <div class="space-y-8">
                                    <div>
                                        <h4 class="text-lg font-semibold text-dark-secondary mb-4 flex items-center">
                                            <i class="fas fa-icons text-mpp-orange mr-2"></i>
                                            Ikona kategorii
                                        </h4>

                                        {{-- Icon Type Selection --}}
                                        <div class="flex flex-wrap gap-3 mb-6">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="icon-type"
                                                       value="none"
                                                       wire:model="visualForm.icon_type"
                                                       class="sr-only">
                                                <span class="btn-enterprise-secondary px-4 py-2 {{ $visualForm['icon_type'] === 'none' ? 'btn-enterprise-primary' : '' }}">
                                                    <i class="fas fa-ban mr-2"></i>
                                                    Brak ikony
                                                </span>
                                            </label>

                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="icon-type"
                                                       value="font_awesome"
                                                       wire:model="visualForm.icon_type"
                                                       class="sr-only">
                                                <span class="btn-enterprise-secondary px-4 py-2 {{ $visualForm['icon_type'] === 'font_awesome' ? 'btn-enterprise-primary' : '' }}">
                                                    <i class="fab fa-font-awesome mr-2"></i>
                                                    Font Awesome
                                                </span>
                                            </label>

                                            <label class="flex items-center cursor-pointer">
                                                <input type="radio"
                                                       name="icon-type"
                                                       value="custom_upload"
                                                       wire:model="visualForm.icon_type"
                                                       class="sr-only">
                                                <span class="btn-enterprise-secondary px-4 py-2 {{ $visualForm['icon_type'] === 'custom_upload' ? 'btn-enterprise-primary' : '' }}">
                                                    <i class="fas fa-upload mr-2"></i>
                                                    Prześlij plik
                                                </span>
                                            </label>
                                        </div>

                                        {{-- Font Awesome Icon Selection --}}
                                        @if($visualForm['icon_type'] === 'font_awesome')
                                            <div class="form-group-enterprise">
                                                <label for="fontawesome-icon" class="form-label-enterprise">
                                                    <i class="fab fa-font-awesome icon"></i>
                                                    Klasa Font Awesome
                                                </label>
                                                <input type="text"
                                                       id="fontawesome-icon"
                                                       class="form-input-enterprise @error('visualForm.icon') border-red-500 @enderror"
                                                       wire:model="visualForm.icon"
                                                       placeholder="fas fa-shopping-cart">
                                                <div class="text-dark-muted text-sm mt-2">
                                                    Przykłady: fas fa-car, fas fa-tools, fas fa-home
                                                </div>
                                                @error('visualForm.icon')
                                                    <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif

                                        {{-- Icon Upload --}}
                                        @if($visualForm['icon_type'] === 'custom_upload')
                                            <div class="form-group-enterprise">
                                                <label for="icon-upload-file" class="form-label-enterprise">
                                                    <i class="fas fa-upload icon"></i>
                                                    Plik ikony
                                                </label>
                                                <input type="file"
                                                       id="icon-upload-file"
                                                       class="form-input-enterprise @error('iconUpload') border-red-500 @enderror"
                                                       wire:model="iconUpload"
                                                       accept=".jpg,.jpeg,.png,.webp">
                                                <div class="text-dark-muted text-sm mt-2">
                                                    Obsługiwane formaty: JPG, PNG, WebP. Maksymalny rozmiar: 2MB.
                                                </div>
                                                @error('iconUpload')
                                                    <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                                @enderror

                                                {{-- Upload Progress --}}
                                                <div wire:loading wire:target="iconUpload" class="mt-3">
                                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                                        <div class="bg-gradient-to-r from-mpp-orange to-yellow-400 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                                                    </div>
                                                    <p class="text-mpp-orange text-sm mt-1 animate-pulse">Przesyłanie ikony...</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Icon Preview --}}
                                    <div class="mt-6">
                                        <div class="enterprise-card p-6 text-center max-w-sm">
                                            <h5 class="text-sm font-semibold text-dark-secondary mb-4">Podgląd</h5>
                                            <div class="flex justify-center items-center h-24">
                                                @if($visualForm['icon_type'] === 'font_awesome' && !empty($visualForm['icon']))
                                                    <i class="{{ $visualForm['icon'] }} text-5xl"
                                                       style="color: {{ $visualForm['color_primary'] ?? '#e0ac7e' }}"></i>
                                                @elseif($iconPreview)
                                                    <img src="{{ $iconPreview }}"
                                                         alt="Podgląd ikony"
                                                         class="max-w-16 max-h-16 rounded">
                                                @else
                                                    <i class="fas fa-image text-5xl text-gray-500"></i>
                                                @endif
                                            </div>
                                            <p class="text-dark-muted text-xs mt-3">
                                                @if($visualForm['icon_type'] === 'font_awesome')
                                                    Font Awesome
                                                @elseif($iconPreview)
                                                    Ikona custom
                                                @else
                                                    Brak ikony
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Color Scheme --}}
                                <div class="space-y-6">
                                    <div class="form-group-enterprise">
                                        <label for="color-primary" class="form-label-enterprise">
                                            <i class="fas fa-paint-brush icon"></i>
                                            Kolor główny
                                        </label>
                                        <div class="flex">
                                            <input type="color"
                                                   id="color-primary"
                                                   class="w-16 h-12 border-gray-600 rounded-l-lg cursor-pointer @error('visualForm.color_primary') border-red-500 @enderror"
                                                   wire:model="visualForm.color_primary">
                                            <input type="text"
                                                   class="form-input-enterprise flex-1 rounded-l-none border-l-0"
                                                   wire:model="visualForm.color_primary"
                                                   placeholder="#e0ac7e">
                                        </div>
                                        @error('visualForm.color_primary')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-enterprise">
                                        <label for="color-secondary" class="form-label-enterprise">
                                            <i class="fas fa-palette icon"></i>
                                            Kolor drugorzędny
                                        </label>
                                        <div class="flex">
                                            <input type="color"
                                                   id="color-secondary"
                                                   class="w-16 h-12 border-gray-600 rounded-l-lg cursor-pointer @error('visualForm.color_secondary') border-red-500 @enderror"
                                                   wire:model="visualForm.color_secondary">
                                            <input type="text"
                                                   class="form-input-enterprise flex-1 rounded-l-none border-l-0"
                                                   wire:model="visualForm.color_secondary"
                                                   placeholder="#374151">
                                        </div>
                                        @error('visualForm.color_secondary')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Display Style --}}
                                <div class="space-y-6">
                                    <div class="form-group-enterprise">
                                        <label for="display-style" class="form-label-enterprise">
                                            <i class="fas fa-th-large icon"></i>
                                            Styl wyświetlania
                                        </label>
                                        <select id="display-style"
                                                class="form-input-enterprise @error('visualForm.display_style') border-red-500 @enderror"
                                                wire:model="visualForm.display_style">
                                            <option value="default">Domyślny</option>
                                            <option value="card">Karta</option>
                                            <option value="minimal">Minimalistyczny</option>
                                            <option value="featured">Polecany</option>
                                        </select>
                                        @error('visualForm.display_style')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-enterprise">
                                        <label for="banner-position" class="form-label-enterprise">
                                            <i class="fas fa-image icon"></i>
                                            Pozycja bannera
                                        </label>
                                        <select id="banner-position"
                                                class="form-input-enterprise @error('visualForm.banner_position') border-red-500 @enderror"
                                                wire:model="visualForm.banner_position">
                                            <option value="top">Góra</option>
                                            <option value="side">Bok</option>
                                            <option value="background">Tło</option>
                                        </select>
                                        @error('visualForm.banner_position')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Banner Upload --}}
                                <div class="space-y-6">
                                    <h4 class="text-lg font-semibold text-dark-secondary mb-4 flex items-center">
                                        <i class="fas fa-image text-mpp-orange mr-2"></i>
                                        Banner kategorii
                                    </h4>

                                    <div class="form-group-enterprise">
                                                <label for="banner-upload" class="form-label-enterprise">
                                                    <i class="fas fa-upload icon"></i>
                                                    Wybierz plik bannera
                                                </label>
                                                <input type="file"
                                                       id="banner-upload"
                                                       class="form-input-enterprise @error('bannerUpload') border-red-500 @enderror"
                                                       wire:model="bannerUpload"
                                                       accept=".jpg,.jpeg,.png,.webp">
                                                <div class="text-dark-muted text-sm mt-2">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    JPG, PNG, WebP • Maks. 5MB • Zalecane: 1200x400px
                                                </div>
                                                @error('bannerUpload')
                                                    <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                                @enderror

                                                {{-- Upload Progress --}}
                                                <div wire:loading wire:target="bannerUpload" class="mt-3">
                                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                                        <div class="bg-gradient-to-r from-mpp-orange to-yellow-400 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                                                    </div>
                                                    <p class="text-mpp-orange text-sm mt-1 animate-pulse">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i>
                                                        Przesyłanie bannera...
                                                    </p>
                                                </div>
                                            </div>

                                        <div class="mt-6">
                                            @if($bannerPreview)
                                                <div class="enterprise-card p-4 text-center max-w-sm">
                                                    <img src="{{ $bannerPreview }}"
                                                         alt="Podgląd bannera"
                                                         class="w-full rounded-lg shadow-lg">
                                                    <p class="text-dark-muted text-xs mt-2">Podgląd bannera</p>
                                                </div>
                                            @else
                                                <div class="enterprise-card p-4 text-center bg-gray-800">
                                                    <div class="flex justify-center items-center h-24">
                                                        <i class="fas fa-image text-4xl text-gray-500"></i>
                                                    </div>
                                                    <p class="text-dark-muted text-xs mt-2">Brak bannera</p>
                                                </div>
                                            @endif
                                        </div>
                                </div>
                            </div>
                        @endif

                        {{-- Visibility Settings Tab --}}
                        @if($activeTab === 'visibility')
                            <div class="space-y-8">
                                <h3 class="text-xl font-bold text-dark-primary flex items-center mb-6">
                                    <i class="fas fa-eye text-mpp-orange mr-3"></i>
                                    Ustawienia widoczności
                                </h3>

                                {{-- Basic Visibility --}}
                                <div class="space-y-6">
                                    <h4 class="text-lg font-semibold text-dark-secondary mb-4">Podstawowe ustawienia</h4>
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox"
                                                   id="is-visible"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="visibilityForm.is_visible">
                                            <label class="form-label-enterprise cursor-pointer" for="is-visible">
                                                <i class="fas fa-eye icon"></i>
                                                Kategoria widoczna
                                            </label>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox"
                                                   id="show-in-menu"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="visibilityForm.show_in_menu">
                                            <label class="form-label-enterprise cursor-pointer" for="show-in-menu">
                                                <i class="fas fa-bars icon"></i>
                                                Pokaż w menu
                                            </label>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox"
                                                   id="show-in-filter"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="visibilityForm.show_in_filter">
                                            <label class="form-label-enterprise cursor-pointer" for="show-in-filter">
                                                <i class="fas fa-filter icon"></i>
                                                Pokaż w filtrach
                                            </label>
                                        </div>

                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox"
                                                   id="show-product-count"
                                                   class="w-5 h-5 text-mpp-orange bg-gray-700 border-gray-600 rounded focus:ring-mpp-orange focus:ring-2"
                                                   wire:model="visibilityForm.show_product_count">
                                            <label class="form-label-enterprise cursor-pointer" for="show-product-count">
                                                <i class="fas fa-hashtag icon"></i>
                                                Pokaż liczbę produktów
                                            </label>
                                        </div>
                                </div>

                                {{-- Advanced Settings --}}
                                <div class="space-y-6">
                                    <h4 class="text-lg font-semibold text-dark-secondary mb-4">Ustawienia zaawansowane</h4>
                                        <div class="form-group-enterprise">
                                            <label for="min-products" class="form-label-enterprise">
                                                <i class="fas fa-box icon"></i>
                                                Minimalna liczba produktów do wyświetlenia
                                            </label>
                                            <input type="number"
                                                   id="min-products"
                                                   class="form-input-enterprise @error('visibilityForm.min_products_to_show') border-red-500 @enderror"
                                                   wire:model="visibilityForm.min_products_to_show"
                                                   min="0"
                                                   max="1000"
                                                   placeholder="0">
                                            <div class="text-dark-muted text-sm mt-2">
                                                Kategoria będzie ukryta jeśli ma mniej produktów
                                            </div>
                                            @error('visibilityForm.min_products_to_show')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                </div>

                                {{-- Availability Schedule --}}
                                <div class="border-t border-gray-600 pt-8">
                                    <h4 class="text-lg font-semibold text-dark-secondary mb-6 flex items-center">
                                        <i class="fas fa-calendar-alt text-blue-400 mr-3"></i>
                                        Harmonogram dostępności
                                    </h4>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="form-group-enterprise">
                                            <label for="available-from" class="form-label-enterprise">
                                                <i class="fas fa-calendar-plus icon"></i>
                                                Dostępna od
                                            </label>
                                            <input type="datetime-local"
                                                   id="available-from"
                                                   class="form-input-enterprise @error('visibilityForm.available_from') border-red-500 @enderror"
                                                   wire:model="visibilityForm.available_from">
                                            @error('visibilityForm.available_from')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group-enterprise">
                                            <label for="available-to" class="form-label-enterprise">
                                                <i class="fas fa-calendar-minus icon"></i>
                                                Dostępna do
                                            </label>
                                            <input type="datetime-local"
                                                   id="available-to"
                                                   class="form-input-enterprise @error('visibilityForm.available_to') border-red-500 @enderror"
                                                   wire:model="visibilityForm.available_to">
                                            @error('visibilityForm.available_to')
                                                <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Default Values Tab --}}
                        @if($activeTab === 'defaults')
                            <div class="space-y-8">
                                <h3 class="text-xl font-bold text-dark-primary flex items-center mb-6">
                                    <i class="fas fa-cog text-mpp-orange mr-3"></i>
                                    Domyślne wartości dla produktów
                                </h3>

                                <div class="alert-dark-success flex items-center">
                                    <i class="fas fa-info-circle mr-3"></i>
                                    <span>Te wartości będą automatycznie ustawiane dla nowych produktów w tej kategorii</span>
                                </div>

                                {{-- Default Tax Rate --}}
                                <div class="space-y-6">
                                    <div class="form-group-enterprise">
                                        <label for="default-tax" class="form-label-enterprise">
                                            <i class="fas fa-percentage icon"></i>
                                            Domyślna stawka VAT (%)
                                        </label>
                                        <input type="number"
                                               id="default-tax"
                                               class="form-input-enterprise @error('defaultsForm.default_tax_rate') border-red-500 @enderror"
                                               wire:model="defaultsForm.default_tax_rate"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               placeholder="23.00">
                                        @error('defaultsForm.default_tax_rate')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-enterprise">
                                        <label for="default-weight" class="form-label-enterprise">
                                            <i class="fas fa-weight icon"></i>
                                            Domyślna waga (kg)
                                        </label>
                                        <input type="number"
                                               id="default-weight"
                                               class="form-input-enterprise @error('defaultsForm.default_weight') border-red-500 @enderror"
                                               wire:model="defaultsForm.default_weight"
                                               step="0.001"
                                               min="0"
                                               placeholder="0.000">
                                        @error('defaultsForm.default_weight')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Default Dimensions --}}
                                <h4 class="text-lg font-semibold text-dark-secondary mb-4 flex items-center">
                                    <i class="fas fa-ruler text-blue-400 mr-2"></i>
                                    Domyślne wymiary (cm)
                                </h4>
                                <div class="space-y-6">
                                    <div class="form-group-enterprise">
                                        <label for="default-height" class="form-label-enterprise">
                                            <i class="fas fa-arrows-alt-v icon"></i>
                                            Wysokość
                                        </label>
                                        <input type="number"
                                               id="default-height"
                                               class="form-input-enterprise @error('defaultsForm.default_dimensions.height') border-red-500 @enderror"
                                               wire:model="defaultsForm.default_dimensions.height"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                        @error('defaultsForm.default_dimensions.height')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-enterprise">
                                        <label for="default-width" class="form-label-enterprise">
                                            <i class="fas fa-arrows-alt-h icon"></i>
                                            Szerokość
                                        </label>
                                        <input type="number"
                                               id="default-width"
                                               class="form-input-enterprise @error('defaultsForm.default_dimensions.width') border-red-500 @enderror"
                                               wire:model="defaultsForm.default_dimensions.width"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                        @error('defaultsForm.default_dimensions.width')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group-enterprise">
                                        <label for="default-length" class="form-label-enterprise">
                                            <i class="fas fa-ruler-horizontal icon"></i>
                                            Długość
                                        </label>
                                        <input type="number"
                                               id="default-length"
                                               class="form-input-enterprise @error('defaultsForm.default_dimensions.length') border-red-500 @enderror"
                                               wire:model="defaultsForm.default_dimensions.length"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                        @error('defaultsForm.default_dimensions.length')
                                            <div class="text-red-400 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Column - Quick Actions & Info --}}
            <div class="category-form-right-column">
                {{-- Quick Actions Panel --}}
                <div class="enterprise-card p-6">
                    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
                        <i class="fas fa-bolt text-mpp-orange mr-2"></i>
                        Szybkie akcje
                    </h4>
                    <div class="space-y-4">
                        {{-- Save Button --}}
                        <button wire:click="save"
                                class="btn-enterprise-primary w-full py-3 text-lg"
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save mr-3"></i>
                                {{ $mode === 'create' ? 'Utwórz kategorię' : 'Zapisz zmiany' }}
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin mr-3"></i>
                                Zapisywanie...
                            </span>
                        </button>

                        {{-- Cancel Button --}}
                        <a href="{{ route('admin.products.categories.index') }}"
                           class="btn-enterprise-secondary w-full py-3">
                            <i class="fas fa-times mr-2"></i>
                            Anuluj i wróć
                        </a>

                        {{-- Preview Button (Edit Mode) --}}
                        @if($mode === 'edit' && $category)
                            <a href="#" class="btn-enterprise-secondary w-full py-3">
                                <i class="fas fa-eye mr-2"></i>
                                Podgląd kategorii
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Category Info (Edit Mode) --}}
                @if($mode === 'edit' && $category)
                    <div class="enterprise-card p-6">
                        <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
                            <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                            Informacje o kategorii
                        </h4>
                        <div class="space-y-3 text-sm">
                            @if($category)
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted font-medium">ID:</span>
                                <span class="text-dark-primary">{{ $category->id }}</span>
                            </div>
                            @endif
                            @if($category && $category->created_at)
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted font-medium">Utworzona:</span>
                                <span class="text-dark-primary">{{ $category->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted font-medium">Ostatnia aktualizacja:</span>
                                <span class="text-dark-primary">{{ $category->updated_at->format('d.m.Y H:i') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                <span class="text-dark-muted font-medium">Poziom:</span>
                                <span class="text-dark-primary">{{ $category->level ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-dark-muted font-medium">Produktów:</span>
                                <span class="bg-gradient-to-r from-mpp-orange to-yellow-400 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">{{ $category->products_count ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Tips Panel --}}
                <div class="enterprise-card p-6">
                    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
                        Wskazówki
                    </h4>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-check text-green-400 mt-1 flex-shrink-0"></i>
                            <span class="text-dark-secondary text-sm">Używaj opisowych nazw kategorii</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-check text-green-400 mt-1 flex-shrink-0"></i>
                            <span class="text-dark-secondary text-sm">Wypełnij meta opis dla lepszego SEO</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-check text-green-400 mt-1 flex-shrink-0"></i>
                            <span class="text-dark-secondary text-sm">Ikony pomagają w nawigacji</span>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-check text-green-400 mt-1 flex-shrink-0"></i>
                            <span class="text-dark-secondary text-sm">Domyślne wartości oszczędzają czas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div> {{-- Close bootstrap-container --}}

{{-- Enterprise animations and micro-interactions - moved to CSS files --}}

@push('scripts')
<script>
document.addEventListener('livewire:init', function () {
    // Enhanced form validation feedback
    Livewire.on('validationFailed', () => {
        // Scroll to first error with enterprise animation
        const firstError = document.querySelector('.border-red-500');
        if (firstError) {
            firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // Add pulse animation to error field
            firstError.classList.add('animate-pulse');
            setTimeout(() => {
                firstError.classList.remove('animate-pulse');
            }, 2000);
        }
    });

    // Enterprise tab switching animations
    Livewire.on('tabChanged', () => {
        const tabContent = document.querySelector('.enterprise-card');
        if (tabContent) {
            tabContent.style.animation = 'none';
            tabContent.offsetHeight; // Trigger reflow
            tabContent.style.animation = 'fadeInUp 0.5s cubic-bezier(0.23, 1, 0.32, 1)';
        }
    });
});
</script>
@endpush
