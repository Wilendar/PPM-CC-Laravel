{{-- ETAP_05b PHASE 6: Variant Images Manager (PPM UI Standards Compliant) --}}
<div class="bg-gray-800 rounded-xl p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h4 class="text-lg font-medium text-white">
            <i class="fas fa-images text-purple-500 mr-2"></i>
            Zdjęcia Wariantów
        </h4>
    </div>

    @if($product && $product->variants && $product->variants->count() > 0)
        {{-- Upload Area --}}
        <div class="border-2 border-dashed border-gray-600 rounded-xl p-8 text-center hover:border-gray-500 transition-colors">
            <div class="space-y-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-700">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                </div>
                <div>
                    <p class="text-sm text-white mb-2">
                        Przeciągnij i upuść zdjęcia tutaj lub
                    </p>
                    <label class="btn-enterprise-primary px-6 py-2 cursor-pointer inline-block">
                        <i class="fas fa-folder-open mr-2"></i>
                        Wybierz Pliki
                        <input type="file"
                               wire:model="variantImages"
                               multiple
                               accept="image/*"
                               class="hidden">
                    </label>
                </div>
                <p class="text-xs text-gray-400">
                    Obsługiwane formaty: JPG, PNG, GIF. Maksymalny rozmiar: 5MB
                </p>
            </div>

            {{-- Upload Progress --}}
            <div wire:loading wire:target="variantImages" class="mt-4">
                <div class="flex items-center justify-center space-x-2 text-blue-400">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span class="text-sm">Przesyłanie zdjęć...</span>
                </div>
            </div>
        </div>

        {{-- Existing Images Grid --}}
        <div>
            <h5 class="text-md font-medium text-gray-300 mb-4">Istniejące Zdjęcia</h5>

            @if($product->images && $product->images->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($product->images as $image)
                        <div wire:key="variant-image-{{ $image->id }}"
                             class="relative group bg-gray-900 rounded-lg overflow-hidden border border-gray-700 hover:border-gray-600 transition-colors">
                            {{-- Image Thumbnail --}}
                            <div class="aspect-square bg-gray-900 flex items-center justify-center">
                                <img src="{{ $image->thumbnail_url ?? $image->url }}"
                                     alt="Variant image"
                                     class="w-full h-full object-cover">
                            </div>

                            {{-- Image Actions Overlay --}}
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                {{-- Assign to Variant Dropdown --}}
                                <select wire:model="imageVariantAssignments.{{ $image->id }}"
                                        class="text-xs px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white">
                                    <option value="">Przypisz do...</option>
                                    @foreach($product->variants as $variant)
                                        <option value="{{ $variant->id }}">{{ $variant->sku }}</option>
                                    @endforeach
                                </select>

                                {{-- Set as Cover Button --}}
                                <button wire:click="setImageAsCover({{ $image->id }})"
                                        class="px-2 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded text-xs transition-colors"
                                        title="Ustaw jako główne">
                                    <i class="fas fa-star"></i>
                                </button>

                                {{-- Delete Button --}}
                                <button wire:click="deleteImage({{ $image->id }})"
                                        wire:confirm="Czy na pewno usunąć to zdjęcie?"
                                        class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition-colors"
                                        title="Usuń">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            {{-- Cover Badge --}}
                            @if($image->is_cover)
                                <div class="absolute top-2 left-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-900/90 text-orange-200 border border-orange-700/50">
                                        <i class="fas fa-star text-xs mr-1"></i>
                                        Główne
                                    </span>
                                </div>
                            @endif

                            {{-- Variant Assignment Badge --}}
                            @if($image->variant_id)
                                <div class="absolute top-2 right-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-900/90 text-blue-200 border border-blue-700/50">
                                        {{ $image->variant->sku ?? 'N/A' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-gray-900 rounded-lg border border-gray-700">
                    <i class="fas fa-image text-4xl text-gray-600 mb-4"></i>
                    <p class="text-sm text-gray-400">
                        Brak zdjęć. Prześlij pierwsze zdjęcie powyżej.
                    </p>
                </div>
            @endif
        </div>

        {{-- Notes --}}
        <div class="bg-blue-900/20 border border-blue-700/50 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-400 mt-1"></i>
                <div class="text-sm text-blue-200">
                    <p class="font-medium mb-1">Wskazówki:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Przypisz zdjęcia do konkretnych wariantów używając dropdown</li>
                        <li>Ustaw główne zdjęcie produktu używając przycisku z gwiazdką</li>
                        <li>Zdjęcia nieprzypisane do wariantu będą pokazywane dla wszystkich wariantów</li>
                    </ul>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-images text-4xl text-gray-600 mb-4"></i>
            <p class="text-sm text-gray-400">
                Brak wariantów. Dodaj warianty produktu, aby zarządzać zdjęciami.
            </p>
        </div>
    @endif
</div>
