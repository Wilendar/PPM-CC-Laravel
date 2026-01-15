{{-- MediaGalleryGrid - Reusable Gallery Display Component --}}
{{-- ETAP_07d Phase 5: Livewire Components --}}

<div class="media-gallery">
    {{-- Header --}}
    <div class="media-gallery-header">
        <div>
            <span class="media-gallery-title">Galeria</span>
            <span class="media-gallery-count">({{ $this->mediaCount }} zdjec)</span>
        </div>

        {{-- Controls --}}
        <div class="media-gallery-controls">
            @if($this->mediaCount > 0)
                <button type="button" wire:click="toggleSelectMode"
                        class="media-btn media-btn-secondary media-btn-sm">
                    {{ $selectMode ? 'Anuluj zaznaczanie' : 'Zaznacz wiele' }}
                </button>

                @if($selectMode && $this->hasSelection)
                    <button type="button" wire:click="bulkDelete('ppm')"
                            class="media-btn media-btn-danger media-btn-sm">
                        Usun zaznaczone ({{ $this->selectionCount }})
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Gallery Grid --}}
    @if($media->count() > 0)
        <div class="media-gallery-grid"
             x-data="{
                 dragging: null,
                 dragOver: null,
                 handleDragStart(e, id) {
                     this.dragging = id;
                     e.dataTransfer.effectAllowed = 'move';
                 },
                 handleDragOver(e, id) {
                     e.preventDefault();
                     this.dragOver = id;
                 },
                 handleDrop(e, targetId) {
                     e.preventDefault();
                     if (this.dragging && this.dragging !== targetId) {
                         $wire.updateOrder([this.dragging, targetId]);
                     }
                     this.dragging = null;
                     this.dragOver = null;
                 }
             }">
            @foreach($media as $item)
                <div class="media-gallery-item {{ $item->is_primary ? 'is-primary' : '' }} {{ in_array($item->id, $selectedIds) ? 'is-selected' : '' }}"
                     draggable="true"
                     x-on:dragstart="handleDragStart($event, {{ $item->id }})"
                     x-on:dragover="handleDragOver($event, {{ $item->id }})"
                     x-on:drop="handleDrop($event, {{ $item->id }})"
                     :class="{ 'opacity-50': dragOver === {{ $item->id }} }">

                    {{-- Image --}}
                    <img src="{{ $item->thumbnailUrl ?? $item->url }}"
                         alt="{{ $item->original_name }}"
                         class="media-gallery-item-image"
                         loading="lazy" />

                    {{-- Primary Badge --}}
                    @if($item->is_primary)
                        <span class="media-gallery-item-badge">Glowne</span>
                    @endif

                    {{-- Selection Checkbox --}}
                    @if($selectMode)
                        <input type="checkbox"
                               class="media-gallery-item-checkbox"
                               wire:click="toggleSelection({{ $item->id }})"
                               {{ in_array($item->id, $selectedIds) ? 'checked' : '' }} />
                    @endif

                    {{-- Overlay with actions --}}
                    <div class="media-gallery-item-overlay"></div>

                    {{-- Actions --}}
                    <div class="media-gallery-item-actions">
                        @if(!$item->is_primary)
                            <button type="button" wire:click="setPrimary({{ $item->id }})"
                                    class="media-btn media-btn-primary media-btn-sm"
                                    title="Ustaw jako glowne">
                                <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                        @endif

                        <button type="button" wire:click="confirmDelete({{ $item->id }}, 'ppm')"
                                class="media-btn media-btn-danger media-btn-sm"
                                title="Usun">
                            <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="media-gallery-empty">
            <p>Brak zdjec w galerii</p>
            <p class="text-sm mt-2">Uzyj widgetu powyzej aby dodac zdjecia</p>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($confirmDeleteId)
        <div class="media-delete-modal" wire:click.self="cancelDelete">
            <div class="media-delete-modal-content">
                <h3 class="media-delete-modal-title">Potwierdz usuniecie</h3>

                <div class="media-delete-modal-options">
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="deleteScope" value="ppm" />
                        <span>Usun tylko z PPM</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="deleteScope" value="prestashop" />
                        <span>Usun tylko z PrestaShop</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" wire:model="deleteScope" value="both" />
                        <span>Usun z obu systemow</span>
                    </label>
                </div>

                <div class="media-delete-modal-actions">
                    <button type="button" wire:click="cancelDelete"
                            class="media-btn media-btn-secondary">
                        Anuluj
                    </button>
                    <button type="button" wire:click="executeDelete"
                            class="media-btn media-btn-danger">
                        Usun
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
