<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

/**
 * AttributeColorPicker Component
 *
 * Production-ready color picker for AttributeValue management in ETAP_05b variant system.
 * Uses vanilla-colorful Web Component for color selection with Alpine.js integration.
 *
 * CRITICAL: Outputs #RRGGBB format (uppercase) for PrestaShop attribute sync compatibility.
 *
 * @property string|null $color - Hex color value (#RRGGBB format)
 * @property string|null $label - Optional label for color input
 * @property bool $required - Whether color input is required
 *
 * @example Usage in AttributeValueManager:
 * <livewire:components.attribute-color-picker
 *     wire:model="formData.color"
 *     label="Attribute Color"
 *     :required="true"
 * />
 *
 * @package App\Http\Livewire\Components
 * @since ETAP_05b Phase 3
 * @version 1.0.0
 */
class AttributeColorPicker extends Component
{
    /**
     * Hex color value (#RRGGBB format)
     *
     * NULLABLE to avoid Livewire 3.x DI conflict (see _ISSUES_FIXES/LIVEWIRE_DEPENDENCY_INJECTION_ISSUE.md)
     * Default: null (initialized to #000000 in mount)
     *
     * @var string|null
     */
    public ?string $color = null;

    /**
     * Label text for color input (optional)
     *
     * @var string|null
     */
    public ?string $label = null;

    /**
     * Whether color input is required
     *
     * @var bool
     */
    public bool $required = false;

    /**
     * Validation error message (if any)
     *
     * @var string|null
     */
    public ?string $validationError = null;

    /**
     * Mount component - Initialize color with default
     *
     * @param string|null $color - Initial color value (defaults to #000000 if null)
     * @param string|null $label - Label text
     * @param bool $required - Whether field is required
     * @return void
     */
    public function mount(?string $color = null, ?string $label = null, bool $required = false): void
    {
        // Initialize with default black color if null
        $this->color = $color ?? '#000000';
        $this->label = $label;
        $this->required = $required;

        // Validate initial color format
        try {
            $this->validateAndNormalizeColor($this->color);
        } catch (\Exception $e) {
            // If initial color invalid, fallback to default
            $this->color = '#000000';
            $this->validationError = 'Initial color invalid, using default #000000';
        }
    }

    /**
     * Livewire lifecycle hook - Called when $color property is updated
     *
     * Validates and normalizes color format on every change.
     * Sets validation error if format invalid.
     *
     * @param string|null $value - New color value
     * @return void
     */
    public function updatedColor(?string $value): void
    {
        // Clear previous validation error
        $this->validationError = null;

        // Handle null/empty values
        if (empty($value)) {
            if ($this->required) {
                $this->validationError = 'Color is required';
            } else {
                // Set to default if not required
                $this->color = '#000000';
            }
            return;
        }

        // Validate and normalize format
        try {
            $this->validateAndNormalizeColor($value);
            $this->color = $value; // Normalized value (uppercase, with #)

            // Emit event to parent component (Livewire 3.x)
            $this->dispatch('color-updated', color: $this->color);
        } catch (\Exception $e) {
            $this->validationError = $e->getMessage();
            // Keep invalid value to allow user correction
        }
    }

    /**
     * Validate and normalize hex color format
     *
     * Ensures color is in #RRGGBB format (6 hex digits after #, uppercase).
     * PrestaShop API requires this exact format for attribute colors.
     *
     * Rules:
     * - Must be exactly 6 hex characters (with or without #)
     * - Automatically adds # prefix if missing
     * - Converts to uppercase for consistency
     *
     * @param string &$color - Color value (passed by reference, will be normalized)
     * @return void
     * @throws \Exception if format invalid
     */
    private function validateAndNormalizeColor(string &$color): void
    {
        // Trim whitespace
        $color = trim($color);

        // Remove # prefix for validation
        $hexValue = ltrim($color, '#');

        // Validate: exactly 6 hex characters
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hexValue)) {
            throw new \Exception('Invalid color format. Expected #RRGGBB (e.g., #FF5733, #000000)');
        }

        // Normalize: add # prefix + uppercase
        $color = '#' . strtoupper($hexValue);
    }

    /**
     * Get validation rules for Livewire validation
     *
     * Used by parent components when calling $this->validate()
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'color' => [
                $this->required ? 'required' : 'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
                'max:7'
            ]
        ];
    }

    /**
     * Render component view
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.components.attribute-color-picker');
    }
}
