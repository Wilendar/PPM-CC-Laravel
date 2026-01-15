<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Illuminate\Support\Facades\Log;

/**
 * UVE Parallax Editing Trait - ETAP_07f_P5 FAZA PP.3
 *
 * Logika edycji efektu parallax:
 * - Konfiguracja height, speed, overlay
 * - Obliczanie overlay RGBA
 * - Obsluga pd-pseudo-parallax, pd-pseudo-parallax__overlay
 */
trait UVE_ParallaxEditing
{
    /**
     * Current parallax configuration
     */
    public array $parallaxConfig = [];

    /**
     * Default parallax configuration
     */
    protected array $parallaxDefaults = [
        'height' => '500px',
        'speed' => 0.5,
        'backgroundSize' => 'cover',
        'backgroundPosition' => 'center center',
        'textPosition' => 'center',
        'fixedBackground' => false,
        'centerContent' => true,
        'overlay' => [
            'enabled' => true,
            'color' => '#000000',
            'opacity' => 0.5,
        ],
    ];

    /**
     * Initialize parallax configuration with defaults
     */
    public function initParallaxConfig(?array $config = null): void
    {
        $this->parallaxConfig = array_merge_recursive($this->parallaxDefaults, $config ?? []);
    }

    /**
     * Update a single parallax setting
     */
    public function updateParallaxSetting(string $key, mixed $value): void
    {
        // Handle nested keys (e.g., 'overlay.color')
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $ref = &$this->parallaxConfig;

            foreach ($keys as $i => $k) {
                if ($i === count($keys) - 1) {
                    $ref[$k] = $value;
                } else {
                    if (!isset($ref[$k]) || !is_array($ref[$k])) {
                        $ref[$k] = [];
                    }
                    $ref = &$ref[$k];
                }
            }
        } else {
            $this->parallaxConfig[$key] = $value;
        }

        // Mark as dirty for save
        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        Log::debug('Parallax setting updated', [
            'key' => $key,
            'value' => $value,
            'config' => $this->parallaxConfig,
        ]);
    }

    /**
     * Update multiple parallax settings at once
     */
    public function updateParallaxSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->updateParallaxSetting($key, $value);
        }
    }

    /**
     * Apply default parallax configuration
     */
    public function applyParallaxDefaults(): void
    {
        $this->parallaxConfig = $this->parallaxDefaults;

        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        $this->dispatch('notify', type: 'info', message: 'Domyslna konfiguracja parallax przywrocona');
    }

    /**
     * Calculate overlay RGBA from hex color and opacity
     */
    public function calculateOverlayRgba(?string $color = null, ?float $opacity = null): string
    {
        $color = $color ?? $this->parallaxConfig['overlay']['color'] ?? '#000000';
        $opacity = $opacity ?? $this->parallaxConfig['overlay']['opacity'] ?? 0.5;

        // Ensure color starts with #
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        // Parse hex color
        $hex = ltrim($color, '#');

        // Handle short hex (e.g., #fff)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Clamp opacity
        $opacity = max(0, min(1, $opacity));

        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, number_format($opacity, 2));
    }

    /**
     * Generate inline styles for parallax container
     */
    public function generateParallaxContainerStyles(): string
    {
        $styles = [];

        // Height
        $height = $this->parallaxConfig['height'] ?? '500px';
        $styles[] = "min-height: {$height}";

        // Background
        $bgSize = $this->parallaxConfig['backgroundSize'] ?? 'cover';
        $styles[] = "background-size: {$bgSize}";

        $bgPosition = $this->parallaxConfig['backgroundPosition'] ?? 'center center';
        $styles[] = "background-position: {$bgPosition}";

        // Fixed background (parallax effect)
        if ($this->parallaxConfig['fixedBackground'] ?? false) {
            $styles[] = 'background-attachment: fixed';
        }

        return implode('; ', $styles);
    }

    /**
     * Generate inline styles for parallax overlay
     */
    public function generateParallaxOverlayStyles(): string
    {
        if (!($this->parallaxConfig['overlay']['enabled'] ?? true)) {
            return 'display: none';
        }

        $rgba = $this->calculateOverlayRgba();

        return "background-color: {$rgba}";
    }

    /**
     * Generate inline styles for parallax content
     */
    public function generateParallaxContentStyles(): string
    {
        $styles = [];

        // Text position
        $textPosition = $this->parallaxConfig['textPosition'] ?? 'center';
        switch ($textPosition) {
            case 'left':
                $styles[] = 'text-align: left';
                $styles[] = 'justify-content: flex-start';
                break;
            case 'right':
                $styles[] = 'text-align: right';
                $styles[] = 'justify-content: flex-end';
                break;
            default:
                $styles[] = 'text-align: center';
                $styles[] = 'justify-content: center';
        }

        // Center content vertically
        if ($this->parallaxConfig['centerContent'] ?? true) {
            $styles[] = 'align-items: center';
            $styles[] = 'display: flex';
        }

        return implode('; ', $styles);
    }

    /**
     * Generate data attribute for parallax speed
     */
    public function generateParallaxDataAttribute(): string
    {
        $speed = $this->parallaxConfig['speed'] ?? 0.5;

        return htmlspecialchars(json_encode([
            'speed' => floatval($speed),
        ]), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Parse parallax configuration from existing block
     */
    public function parseParallaxConfigFromBlock(array $block): array
    {
        $config = $this->parallaxDefaults;
        $data = $block['data'] ?? [];

        // Check for stored config
        if (isset($data['parallaxConfig'])) {
            $config = array_merge_recursive($config, $data['parallaxConfig']);
        }

        // Try to parse from inline styles if present
        if (isset($data['style'])) {
            $style = $data['style'];

            // Parse min-height
            if (preg_match('/min-height:\s*([^;]+)/', $style, $matches)) {
                $config['height'] = trim($matches[1]);
            }

            // Parse background-size
            if (preg_match('/background-size:\s*([^;]+)/', $style, $matches)) {
                $config['backgroundSize'] = trim($matches[1]);
            }

            // Parse background-attachment
            if (str_contains($style, 'background-attachment: fixed')) {
                $config['fixedBackground'] = true;
            }
        }

        return $config;
    }

    /**
     * Apply parallax configuration to a block
     */
    public function applyParallaxConfigToBlock(int $blockIndex): void
    {
        if (!isset($this->blocks[$blockIndex])) {
            return;
        }

        $this->blocks[$blockIndex]['data']['parallaxConfig'] = $this->parallaxConfig;
        $this->blocks[$blockIndex]['data']['parallaxStyles'] = [
            'container' => $this->generateParallaxContainerStyles(),
            'overlay' => $this->generateParallaxOverlayStyles(),
            'content' => $this->generateParallaxContentStyles(),
        ];
        $this->blocks[$blockIndex]['data']['dataParallax'] = $this->generateParallaxDataAttribute();

        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        Log::info('Parallax config applied to block', [
            'block_index' => $blockIndex,
            'config' => $this->parallaxConfig,
        ]);
    }

    /**
     * Generate CSS class list for parallax section
     */
    public function generateParallaxClasses(): array
    {
        $classes = ['pd-pseudo-parallax'];

        // Text position modifier
        $textPosition = $this->parallaxConfig['textPosition'] ?? 'center';
        $classes[] = "pd-pseudo-parallax--{$textPosition}";

        // Center content modifier
        if ($this->parallaxConfig['centerContent'] ?? true) {
            $classes[] = 'pd-pseudo-parallax--centered';
        }

        // Fixed background modifier
        if ($this->parallaxConfig['fixedBackground'] ?? false) {
            $classes[] = 'pd-pseudo-parallax--fixed';
        }

        return $classes;
    }

    /**
     * Get text position options
     */
    public function getParallaxTextPositionsProperty(): array
    {
        return [
            'left' => [
                'label' => 'Lewo',
                'icon' => 'align-left',
            ],
            'center' => [
                'label' => 'Srodek',
                'icon' => 'align-center',
            ],
            'right' => [
                'label' => 'Prawo',
                'icon' => 'align-right',
            ],
        ];
    }

    /**
     * Get background size options
     */
    public function getParallaxBackgroundSizesProperty(): array
    {
        return [
            'cover' => [
                'label' => 'Cover',
                'description' => 'Wypelnia caly kontener',
            ],
            'contain' => [
                'label' => 'Contain',
                'description' => 'Dopasowuje do kontenera',
            ],
            'auto' => [
                'label' => 'Auto',
                'description' => 'Oryginalny rozmiar',
            ],
        ];
    }

    /**
     * Get overlay color presets
     */
    public function getParallaxOverlayPresetsProperty(): array
    {
        return [
            '#000000' => 'Czarny',
            '#1a1a1a' => 'Ciemny szary',
            '#0f172a' => 'Slate',
            '#1e3a5f' => 'Navy',
            '#e0ac7e' => 'Brand Gold',
            '#ef8248' => 'Brand Orange',
        ];
    }
}
