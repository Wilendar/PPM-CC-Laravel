{{--
    Reusable toggle switch for notification preferences.
    Variables:
        $model - wire:model binding path (e.g. 'prefs.email_product_changes')
        $id    - unique HTML id for the toggle

    Uses single @entangle().live binding (Livewire 3.x + Alpine.js).
    NO wire:model.live on input (would double-toggle with entangle).
    NO $refs.cb.click() on div (would triple-toggle).
--}}
<label for="{{ $id }}" class="inline-flex items-center cursor-pointer">
    <div class="relative" x-data="{ on: @entangle($model).live }">
        <input type="checkbox"
               id="{{ $id }}"
               x-model="on"
               class="sr-only">
        <div @click="on = !on"
             :class="on ? 'bg-[#e0ac7e]' : 'bg-gray-600'"
             class="w-11 h-6 rounded-full transition-colors duration-200 cursor-pointer">
        </div>
        <div :class="on ? 'translate-x-5' : 'translate-x-0.5'"
             class="absolute top-0.5 left-0 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 pointer-events-none">
        </div>
    </div>
</label>
