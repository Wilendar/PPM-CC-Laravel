{{-- MediaUploadWidget - Reusable Upload Component --}}
{{-- ETAP_07d Phase 5: Livewire Components --}}

<div class="media-upload-widget">
    {{-- Upload Zone --}}
    <div
        class="media-upload-zone {{ !$this->canUpload ? 'is-disabled' : '' }}"
        x-data="{
            isDragover: false,
            handleDrop(e) {
                if (!{{ $this->canUpload ? 'true' : 'false' }}) return;
                this.isDragover = false;
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    $wire.upload('photos', files);
                }
            }
        }"
        x-on:dragover.prevent="isDragover = true"
        x-on:dragleave.prevent="isDragover = false"
        x-on:drop.prevent="handleDrop($event)"
        :class="{ 'is-dragover': isDragover }"
        @click="$refs.fileInput.click()"
    >
        {{-- Icon --}}
        <svg class="media-upload-zone-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>

        {{-- Text --}}
        @if($this->canUpload)
            <p class="media-upload-zone-text">
                Przeciagnij zdjecia tutaj lub kliknij aby wybrac
            </p>
            <p class="media-upload-zone-hint">
                {{ $multiple ? 'Mozesz wybrac wiele plikow' : 'Wybierz jeden plik' }}
                | Max {{ $this->getRemainingSlots() }} zdjec | JPG, PNG, WebP, GIF | Max 10MB
            </p>
        @else
            <p class="media-upload-zone-text">
                Osiagnieto limit zdjec ({{ $maxFiles }})
            </p>
        @endif

        {{-- Hidden file input --}}
        <input
            type="file"
            x-ref="fileInput"
            class="media-upload-input"
            wire:model="photos"
            accept="{{ $this->acceptString }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ !$this->canUpload ? 'disabled' : '' }}
        />
    </div>

    {{-- Upload Progress --}}
    @if($isUploading)
        <div class="media-upload-progress">
            <div class="media-upload-progress-bar">
                <div class="media-upload-progress-fill" style="width: {{ $this->uploadPercentage }}%"></div>
            </div>
            <p class="media-upload-progress-text">
                Przesylanie... {{ $this->uploadPercentage }}%
            </p>
        </div>
    @endif

    {{-- Errors --}}
    @if($this->hasErrors)
        <div class="media-upload-errors">
            @foreach($uploadErrors as $error)
                <p class="media-upload-error">{{ $error }}</p>
            @endforeach
            <button type="button" wire:click="clearErrors" class="media-btn media-btn-sm media-btn-secondary mt-2">
                Zamknij
            </button>
        </div>
    @endif
</div>
