<?php

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

/**
 * UVE Responsive Styles Trait - ETAP_07f_P5 FAZA PP.4
 *
 * Zarzadzanie stylami responsywnymi dla roznych breakpointow.
 * Obsluguje desktop, tablet i mobile z dziedziczeniem stylów.
 */
trait UVE_ResponsiveStyles
{
    // =====================
    // RESPONSIVE STATE
    // =====================

    /** @var string Current device being edited: 'desktop' | 'tablet' | 'mobile' */
    public string $currentDevice = 'desktop';

    /** @var array Responsive styles per breakpoint per element */
    public array $responsiveStyles = [];

    // =====================
    // BREAKPOINT CONFIGURATION
    // =====================

    /**
     * Get breakpoint configuration
     *
     * @return array Breakpoint definitions
     */
    public function getBreakpoints(): array
    {
        return [
            'desktop' => [
                'name' => 'Desktop',
                'minWidth' => 1024,
                'maxWidth' => null,
                'mediaQuery' => '@media (min-width: 1024px)',
                'iframeWidth' => '100%',
            ],
            'tablet' => [
                'name' => 'Tablet',
                'minWidth' => 768,
                'maxWidth' => 1023,
                'mediaQuery' => '@media (min-width: 768px) and (max-width: 1023px)',
                'iframeWidth' => '768px',
            ],
            'mobile' => [
                'name' => 'Mobile',
                'minWidth' => 0,
                'maxWidth' => 767,
                'mediaQuery' => '@media (max-width: 767px)',
                'iframeWidth' => '375px',
            ],
        ];
    }

    // =====================
    // DEVICE SWITCHING
    // =====================

    /**
     * Switch current editing device
     */
    public function switchDevice(string $device): void
    {
        if (!in_array($device, ['desktop', 'tablet', 'mobile'])) {
            return;
        }

        $this->currentDevice = $device;

        // Update preview device if method exists (from UVE_Preview trait)
        if (method_exists($this, 'setPreviewDevice')) {
            $this->setPreviewDevice($device);
        }

        // Dispatch device change event for iframe resize
        $breakpoints = $this->getBreakpoints();
        $this->dispatch('device-changed', [
            'device' => $device,
            'width' => $breakpoints[$device]['iframeWidth'],
            'mediaQuery' => $breakpoints[$device]['mediaQuery'],
        ]);

        Log::debug('[UVE_ResponsiveStyles] Device switched', [
            'device' => $device,
            'iframeWidth' => $breakpoints[$device]['iframeWidth'],
        ]);
    }

    // =====================
    // STYLE MANAGEMENT
    // =====================

    /**
     * Get styles for specific device for current element
     *
     * @param string $device Device name
     * @return array Styles for device
     */
    public function getStylesForDevice(string $device): array
    {
        if (!$this->selectedElementId) {
            return [];
        }

        $elementId = $this->selectedElementId;

        // Get device-specific styles
        $deviceStyles = $this->responsiveStyles[$elementId][$device] ?? [];

        // If not desktop and no specific styles, inherit from desktop
        if ($device !== 'desktop' && empty($deviceStyles)) {
            return $this->responsiveStyles[$elementId]['desktop'] ?? [];
        }

        return $deviceStyles;
    }

    /**
     * Set style for specific device
     *
     * @param string $device Device name
     * @param string $property CSS property (camelCase)
     * @param string|null $value CSS value
     */
    public function setStyleForDevice(string $device, string $property, ?string $value): void
    {
        if (!$this->selectedElementId) {
            return;
        }

        $elementId = $this->selectedElementId;

        // Initialize if needed
        if (!isset($this->responsiveStyles[$elementId])) {
            $this->responsiveStyles[$elementId] = [
                'desktop' => [],
                'tablet' => [],
                'mobile' => [],
            ];
        }

        // Set or remove value
        if ($value === null || $value === '') {
            unset($this->responsiveStyles[$elementId][$device][$property]);
        } else {
            $this->responsiveStyles[$elementId][$device][$property] = $value;
        }

        // Update element in document
        $this->updateElementResponsiveStyles($elementId);

        $this->isDirty = true;
    }

    /**
     * Set multiple styles for device at once
     *
     * @param string $device Device name
     * @param array $styles Key-value pairs of CSS properties
     */
    public function setStylesForDevice(string $device, array $styles): void
    {
        if (!$this->selectedElementId) {
            return;
        }

        $elementId = $this->selectedElementId;

        // Initialize if needed
        if (!isset($this->responsiveStyles[$elementId])) {
            $this->responsiveStyles[$elementId] = [
                'desktop' => [],
                'tablet' => [],
                'mobile' => [],
            ];
        }

        // Merge styles
        $this->responsiveStyles[$elementId][$device] = array_merge(
            $this->responsiveStyles[$elementId][$device],
            $styles
        );

        // Remove empty values
        $this->responsiveStyles[$elementId][$device] = array_filter(
            $this->responsiveStyles[$elementId][$device],
            fn($v) => $v !== '' && $v !== null
        );

        // Update element in document
        $this->updateElementResponsiveStyles($elementId);

        $this->isDirty = true;
    }

    /**
     * Inherit styles from desktop to other device
     *
     * @param string $targetDevice Device to copy styles to
     */
    public function inheritFromDesktop(string $targetDevice): void
    {
        if (!$this->selectedElementId || $targetDevice === 'desktop') {
            return;
        }

        $elementId = $this->selectedElementId;
        $desktopStyles = $this->responsiveStyles[$elementId]['desktop'] ?? [];

        if (empty($desktopStyles)) {
            $this->dispatch('notify', type: 'warning', message: 'Brak stylów desktop do skopiowania');
            return;
        }

        $this->responsiveStyles[$elementId][$targetDevice] = $desktopStyles;
        $this->updateElementResponsiveStyles($elementId);

        $this->isDirty = true;
        $this->dispatch('notify', type: 'success', message: "Style skopiowane z desktop do {$targetDevice}");
    }

    /**
     * Clear responsive styles for device
     *
     * @param string $device Device name
     */
    public function clearDeviceStyles(string $device): void
    {
        if (!$this->selectedElementId) {
            return;
        }

        $elementId = $this->selectedElementId;

        if (isset($this->responsiveStyles[$elementId][$device])) {
            $this->responsiveStyles[$elementId][$device] = [];
            $this->updateElementResponsiveStyles($elementId);
            $this->isDirty = true;
        }
    }

    /**
     * Update element responsive styles in document tree
     */
    protected function updateElementResponsiveStyles(string $elementId): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $responsiveData = $this->responsiveStyles[$elementId] ?? [];

        $this->updateElementInTree($elementId, function ($element) use ($responsiveData) {
            $element['responsiveStyles'] = $responsiveData;
            return $element;
        });

        // Recompile block
        $this->compileBlockHtml($this->editingBlockIndex);
    }

    // =====================
    // CSS GENERATION
    // =====================

    /**
     * Generate responsive CSS for element
     *
     * @param string|null $elementId Element ID (defaults to selected)
     * @return string Generated CSS with media queries
     */
    public function generateResponsiveCss(?string $elementId = null): string
    {
        $elementId = $elementId ?? $this->selectedElementId;
        if (!$elementId) {
            return '';
        }

        $styles = $this->responsiveStyles[$elementId] ?? [];
        if (empty($styles)) {
            return '';
        }

        $breakpoints = $this->getBreakpoints();
        $css = '';
        $selector = "[data-uve-id=\"{$elementId}\"]";

        // Desktop first (no media query needed for base styles)
        if (!empty($styles['desktop'])) {
            $css .= $selector . " {\n";
            foreach ($styles['desktop'] as $prop => $value) {
                $css .= "    " . $this->toKebabCase($prop) . ": {$value};\n";
            }
            $css .= "}\n\n";
        }

        // Tablet styles
        if (!empty($styles['tablet'])) {
            $css .= $breakpoints['tablet']['mediaQuery'] . " {\n";
            $css .= "    " . $selector . " {\n";
            foreach ($styles['tablet'] as $prop => $value) {
                $css .= "        " . $this->toKebabCase($prop) . ": {$value};\n";
            }
            $css .= "    }\n";
            $css .= "}\n\n";
        }

        // Mobile styles
        if (!empty($styles['mobile'])) {
            $css .= $breakpoints['mobile']['mediaQuery'] . " {\n";
            $css .= "    " . $selector . " {\n";
            foreach ($styles['mobile'] as $prop => $value) {
                $css .= "        " . $this->toKebabCase($prop) . ": {$value};\n";
            }
            $css .= "    }\n";
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Generate responsive CSS for all elements in current block
     *
     * @return string Complete responsive CSS
     */
    public function generateAllResponsiveCss(): string
    {
        $css = "/* UVE Responsive Styles */\n\n";

        foreach (array_keys($this->responsiveStyles) as $elementId) {
            $elementCss = $this->generateResponsiveCss($elementId);
            if (!empty($elementCss)) {
                $css .= "/* Element: {$elementId} */\n";
                $css .= $elementCss . "\n";
            }
        }

        return $css;
    }

    /**
     * Convert camelCase to kebab-case
     */
    protected function toKebabCase(string $property): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $property));
    }

    // =====================
    // COMPUTED PROPERTIES
    // =====================

    /**
     * Get styles for current device
     */
    #[Computed]
    public function currentDeviceStyles(): array
    {
        return $this->getStylesForDevice($this->currentDevice);
    }

    /**
     * Check if current device has custom styles (not inherited)
     */
    #[Computed]
    public function hasCustomDeviceStyles(): bool
    {
        if (!$this->selectedElementId || $this->currentDevice === 'desktop') {
            return true;
        }

        $elementId = $this->selectedElementId;
        return !empty($this->responsiveStyles[$elementId][$this->currentDevice] ?? []);
    }

    /**
     * Get preview width for current device
     */
    #[Computed]
    public function devicePreviewWidth(): string
    {
        $breakpoints = $this->getBreakpoints();
        return $breakpoints[$this->currentDevice]['iframeWidth'];
    }

    // =====================
    // EVENT HANDLERS
    // =====================

    /**
     * Handle device changed event
     */
    #[On('device-changed')]
    public function onDeviceChanged(array $data): void
    {
        $device = $data['device'] ?? 'desktop';
        $this->currentDevice = $device;

        Log::debug('[UVE_ResponsiveStyles] Device changed via event', [
            'device' => $device,
        ]);
    }

    // =====================
    // DATA PERSISTENCE
    // =====================

    /**
     * Load responsive styles from block document
     */
    public function loadResponsiveStylesFromBlock(): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        $block = $this->blocks[$this->editingBlockIndex] ?? null;
        if (!$block || !isset($block['document']['root'])) {
            return;
        }

        // Extract responsive styles from all elements
        $this->responsiveStyles = [];
        $this->extractResponsiveStylesFromElement($block['document']['root']);

        Log::debug('[UVE_ResponsiveStyles] Loaded responsive styles from block', [
            'blockIndex' => $this->editingBlockIndex,
            'elementsWithStyles' => count($this->responsiveStyles),
        ]);
    }

    /**
     * Recursively extract responsive styles from element tree
     */
    protected function extractResponsiveStylesFromElement(array $element): void
    {
        $elementId = $element['id'] ?? null;

        if ($elementId && !empty($element['responsiveStyles'])) {
            $this->responsiveStyles[$elementId] = $element['responsiveStyles'];
        }

        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                $this->extractResponsiveStylesFromElement($child);
            }
        }
    }

    /**
     * Save responsive styles to block document
     */
    public function saveResponsiveStylesToBlock(): void
    {
        if ($this->editingBlockIndex === null) {
            return;
        }

        // Styles are already saved via updateElementResponsiveStyles()
        // This method is for explicit save operations

        Log::debug('[UVE_ResponsiveStyles] Responsive styles saved to block', [
            'blockIndex' => $this->editingBlockIndex,
            'elementsWithStyles' => count($this->responsiveStyles),
        ]);
    }
}
