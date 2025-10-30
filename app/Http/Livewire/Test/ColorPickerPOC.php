<?php

namespace App\Http\Livewire\Test;

use Livewire\Component;

class ColorPickerPOC extends Component
{
    /**
     * Color value in hex format (#ffffff)
     * CRITICAL: Must always be #RRGGBB format for PrestaShop compatibility
     */
    public string $colorValue = '#ff5733';

    /**
     * Display name for the color (optional, for UI feedback)
     */
    public string $colorName = 'Coral Red';

    /**
     * Test colors for quick selection
     */
    public array $testColors = [
        '#FF0000' => 'Red',
        '#00FF00' => 'Green',
        '#0000FF' => 'Blue',
        '#FFFF00' => 'Yellow',
        '#FF6600' => 'Orange',
        '#FF00FF' => 'Magenta',
        '#00FFFF' => 'Cyan',
        '#FFFFFF' => 'White',
        '#000000' => 'Black',
    ];

    /**
     * Lifecycle hook - Mount component
     */
    public function mount(): void
    {
        // Initialize with a valid hex color
        $this->validateHexFormat($this->colorValue);
    }

    /**
     * Update color value from picker
     * Called by Alpine.js x-data @color-changed event
     *
     * @param string $color
     * @return void
     */
    public function updateColor(string $color): void
    {
        $this->validateHexFormat($color);
        $this->colorValue = $color;
    }

    /**
     * Validate and format hex color
     * Ensures #RRGGBB format (6 hex digits after #)
     *
     * @param string $color
     * @return void
     * @throws \Exception if invalid format
     */
    private function validateHexFormat(string &$color): void
    {
        // Normalize: remove # if present
        $color = ltrim($color, '#');

        // Check if exactly 6 hex characters
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $color)) {
            throw new \Exception("Invalid hex format: $color. Expected RRGGBB format");
        }

        // Add # prefix and uppercase
        $color = '#' . strtoupper($color);
    }

    /**
     * Set color from quick selection
     */
    public function setColor(string $colorHex): void
    {
        $this->updateColor($colorHex);
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.test.color-picker-poc');
    }
}
