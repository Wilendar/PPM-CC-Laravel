<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 rounded-lg border border-green-700 bg-green-900/30 px-4 py-3 text-sm text-green-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
            {{ session('error') }}
        </div>
    @endif
    @error('general')
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/30 px-4 py-3 text-sm text-red-300">
            {{ $message }}
        </div>
    @enderror

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">
            {{ $profileId ? 'Edytuj profil eksportu' : 'Nowy profil eksportu' }}
        </h1>
        <a href="{{ route('admin.export.index') }}" wire:navigate
           class="inline-flex items-center gap-2 text-sm text-gray-400 transition-colors hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Powrot do listy
        </a>
    </div>

    {{-- Stepper / Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center" wire:key="step-indicator-{{ $i }}">
                    <button wire:click="goToStep({{ $i }})"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors
                                   {{ $i <= $currentStep ? 'bg-[#e0ac7e] text-gray-900' : 'bg-gray-700 text-gray-400' }}
                                   {{ $i <= $currentStep ? 'cursor-pointer hover:bg-[#c9956a]' : 'cursor-default' }}">
                        @if($i < $currentStep)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $i }}
                        @endif
                    </button>
                    <span class="ml-2 hidden text-sm sm:inline {{ $i <= $currentStep ? 'text-white' : 'text-gray-500' }}">
                        {{ $this->getStepTitle($i) }}
                    </span>
                </div>
                @if($i < $totalSteps)
                    <div class="mx-2 h-px flex-1 {{ $i < $currentStep ? 'bg-[#e0ac7e]' : 'bg-gray-700' }}"></div>
                @endif
            @endfor
        </div>
    </div>

    {{-- Step Content --}}
    <div class="rounded-xl border border-gray-700 bg-gray-800/50 p-6">

        {{-- STEP 1: Basic Info --}}
        @if($currentStep === 1)
            @include('livewire.admin.export.partials.step-1-basic-info')
        @endif

        {{-- STEP 2: Field Selection --}}
        @if($currentStep === 2)
            @include('livewire.admin.export.partials.step-2-fields')
        @endif

        {{-- STEP 3: Filters --}}
        @if($currentStep === 3)
            @include('livewire.admin.export.partials.step-3-filters')
        @endif

        {{-- STEP 4: Price Groups & Warehouses --}}
        @if($currentStep === 4)
            @include('livewire.admin.export.partials.step-4-prices-warehouses')
        @endif

        {{-- STEP 5: Preview --}}
        @if($currentStep === 5)
            @include('livewire.admin.export.partials.step-5-preview')
        @endif
    </div>

    {{-- Navigation Buttons --}}
    <div class="mt-6 flex items-center justify-between">
        <div>
            @if($currentStep > 1)
                <button wire:click="previousStep"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-600 px-4 py-2 text-sm text-gray-300 transition-colors hover:bg-gray-700 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Poprzedni krok
                </button>
            @endif
        </div>
        <div class="flex gap-3">
            @if($currentStep < $totalSteps)
                <button wire:click="nextStep"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#e0ac7e] px-6 py-2 text-sm font-semibold text-gray-900 transition-colors hover:bg-[#c9956a]">
                    Nastepny krok
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <button wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-700 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="save" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ $profileId ? 'Zapisz zmiany' : 'Utworz profil' }}
                </button>
            @endif
        </div>
    </div>
</div>
