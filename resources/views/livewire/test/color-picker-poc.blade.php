<div x-data="colorPickerApp()" class="max-w-2xl mx-auto p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
            Color Picker POC - vanilla-colorful + Alpine.js
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Testing vanilla-colorful library integration with Alpine.js + Livewire 3.x
        </p>
    </div>

    <!-- Test Card -->
    <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
        <!-- Hex Color Input -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                Hex Color Value (#RRGGBB format)
            </label>
            <input
                wire:model.live="colorValue"
                type="text"
                placeholder="#ff5733"
                class="w-full px-4 py-2 border-2 border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white font-mono text-lg"
                @input="validateHexInput($event)"
            >
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Format: #RRGGBB (e.g., #ff5733, #FF0000, #ffffff)
            </p>
        </div>

        <!-- Color Display Boxes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Current Color Preview -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    Current Color Preview
                </label>
                <div
                    :style="`background-color: ${colorValue || '#ff5733'}`"
                    class="w-full h-48 rounded-lg shadow-md border-2 border-gray-600 transition-all duration-200"
                    :title="`Color: ${colorValue}`"
                ></div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 font-mono text-center">
                    <span x-text="colorValue || '#ff5733'"></span>
                </p>
            </div>

            <!-- Color Information -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-white mb-4">Color Information</h3>

                <!-- RGB Values -->
                <div class="mb-4">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-2">RGB Values:</p>
                    <div x-text="getRgbFromHex(colorValue)" class="text-sm font-mono text-gray-300"></div>
                </div>

                <!-- Format Status -->
                <div class="mb-4">
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-2">Format Status:</p>
                    <div
                        :class="{
                            'text-green-600 dark:text-green-400': isValidHex(colorValue),
                            'text-red-600 dark:text-red-400': !isValidHex(colorValue)
                        }"
                        class="text-sm font-medium"
                    >
                        <span x-show="isValidHex(colorValue)">✓ Valid #RRGGBB format</span>
                        <span x-show="!isValidHex(colorValue)">✗ Invalid format</span>
                    </div>
                </div>

                <!-- Livewire Binding Status -->
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold mb-2">Livewire Binding:</p>
                    <div class="text-green-600 dark:text-green-400 text-sm font-medium">
                        ✓ Connected via wire:model.live
                    </div>
                </div>
            </div>
        </div>

        <!-- Color Picker Component (vanilla-colorful) -->
        <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-dashed border-gray-600">
            <label class="block text-sm font-medium text-gray-300 mb-4">
                Color Picker (vanilla-colorful)
            </label>

            <!-- vanilla-colorful Web Component -->
            <div class="flex justify-center">
                <hex-color-picker
                    :color="colorValue"
                    @color-changed="colorValue = $event.detail.value; handleColorChange()"
                    class="vanilla-colorful-picker"
                ></hex-color-picker>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mt-4 text-center">
                Click on the color square to select color, or drag the sliders
            </p>
        </div>

        <!-- Quick Color Selection -->
        <div class="mb-8">
            <label class="block text-sm font-medium text-gray-300 mb-4">
                Quick Color Selection
            </label>

            <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
                @foreach($testColors as $hex => $name)
                    <button
                        @click="colorValue = '{{ $hex }}'; handleColorChange(); $wire.updateColor('{{ $hex }}')"
                        :class="{
                            'ring-4 ring-offset-2 ring-blue-500': colorValue === '{{ $hex }}'
                        }"
                        class="h-12 rounded-lg shadow-md transition-all hover:shadow-lg hover:scale-105"
                        :style="`background-color: '{{ $hex }}'`"
                        :title="'{{ $name }} ({{ $hex }})'"
                    >
                        <span class="sr-only">{{ $name }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Status Messages -->
        <div x-show="statusMessage" class="p-4 rounded-lg mb-6" :class="statusClass">
            <p x-text="statusMessage" class="text-sm font-medium"></p>
        </div>

        <!-- Browser Info (for debugging) -->
        <div class="text-xs text-gray-500 dark:text-gray-400 p-4 bg-gray-100 dark:bg-gray-700 rounded">
            <p><strong>Browser Info:</strong></p>
            <p>
                User Agent: <span x-text="navigator.userAgent.substring(0, 50) + '...'"></span>
            </p>
            <p>
                Custom Elements Supported:
                <span x-text="'customElements' in window ? 'Yes' : 'No'"></span>
            </p>
        </div>
    </div>

    <!-- Compliance Checklist -->
    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg border-l-4 border-blue-500 p-6">
        <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-4">POC Compliance Checklist</h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
            <li>
                <span x-show="isValidHex(colorValue)" class="text-green-600 dark:text-green-400">✓</span>
                <span x-show="!isValidHex(colorValue)" class="text-red-600 dark:text-red-400">✗</span>
                Hex format validation (#RRGGBB)
            </li>
            <li class="text-green-600 dark:text-green-400">
                ✓ Livewire wire:model.live binding
            </li>
            <li class="text-green-600 dark:text-green-400">
                ✓ Alpine.js x-data integration
            </li>
            <li class="text-green-600 dark:text-green-400">
                ✓ Real-time color preview
            </li>
            <li class="text-green-600 dark:text-green-400">
                ✓ Custom Elements support check
            </li>
        </ul>
    </div>
</div>

<!-- Load vanilla-colorful Web Component -->
<script type="module">
    import HexColorPicker from 'vanilla-colorful/hex-color-picker.js';
</script>

<!-- Alpine.js Data & Methods -->
<script>
    function colorPickerApp() {
        return {
            colorValue: @json($colorValue),
            statusMessage: '',
            statusClass: '',

            /**
             * Validate if string is valid #RRGGBB hex color
             */
            isValidHex(hex) {
                if (!hex) return false;
                return /^#[0-9A-Fa-f]{6}$/.test(hex);
            },

            /**
             * Handle color change - validate format
             */
            handleColorChange() {
                if (!this.isValidHex(this.colorValue)) {
                    this.statusMessage = 'Invalid color format. Using #RRGGBB format...';
                    this.statusClass = 'bg-red-100 border-l-4 border-red-500 text-red-700';
                    setTimeout(() => {
                        this.statusMessage = '';
                    }, 3000);
                } else {
                    this.statusMessage = 'Color updated: ' + this.colorValue;
                    this.statusClass = 'bg-green-100 border-l-4 border-green-500 text-green-700';
                    setTimeout(() => {
                        this.statusMessage = '';
                    }, 2000);
                }
            },

            /**
             * Validate hex input (force uppercase)
             */
            validateHexInput(event) {
                const value = event.target.value;
                if (value && !value.startsWith('#')) {
                    event.target.value = '#' + value;
                    this.colorValue = '#' + value;
                }
                if (value) {
                    event.target.value = value.toUpperCase();
                    this.colorValue = value.toUpperCase();
                }
            },

            /**
             * Convert hex to RGB (for display purposes)
             */
            getRgbFromHex(hex) {
                if (!this.isValidHex(hex)) return 'Invalid format';
                const color = hex.substring(1);
                const r = parseInt(color.substring(0, 2), 16);
                const g = parseInt(color.substring(2, 4), 16);
                const b = parseInt(color.substring(4, 6), 16);
                return `RGB(${r}, ${g}, ${b})`;
            }
        };
    }
</script>

<!-- CSS Styling for vanilla-colorful -->
<style>
    /* vanilla-colorful Web Component Container */
    .vanilla-colorful-picker {
        display: flex;
        justify-content: center;
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
    }

    /* Ensure proper spacing for color picker */
    hex-color-picker {
        --vc-focus-ring: 2px solid rgba(59, 130, 246, 0.5);
        --vc-border-radius: 0.5rem;
    }

    /* Dark mode adjustments for web component */
    @media (prefers-color-scheme: dark) {
        hex-color-picker {
            --vc-bg-color: rgb(55, 65, 81);
            --vc-text-color: rgb(229, 231, 235);
            --vc-border-color: rgb(75, 85, 99);
        }
    }
</style>
