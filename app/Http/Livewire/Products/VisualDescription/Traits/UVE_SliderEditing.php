<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use Illuminate\Support\Facades\Log;

/**
 * UVE Slider Editing Trait - ETAP_07f_P5 FAZA PP.3
 *
 * Logika edycji slidera Splide.js:
 * - Konfiguracja type, perPage, autoplay, arrows, pagination
 * - Breakpoints dla responsywnosci
 * - Generowanie JSON config dla Splide.js
 */
trait UVE_SliderEditing
{
    /**
     * Current slider configuration
     */
    public array $sliderConfig = [];

    /**
     * Default Splide.js configuration
     */
    protected array $sliderDefaults = [
        'type' => 'loop',
        'perPage' => 3,
        'autoplay' => true,
        'interval' => 3000,
        'pauseOnHover' => true,
        'pauseOnFocus' => true,
        'arrows' => true,
        'pagination' => true,
        'speed' => 400,
        'gap' => '16px',
        'breakpoints' => [
            1024 => ['perPage' => 2],
            768 => ['perPage' => 1],
        ],
    ];

    /**
     * Initialize slider configuration with defaults
     */
    public function initSliderConfig(?array $config = null): void
    {
        $this->sliderConfig = array_merge($this->sliderDefaults, $config ?? []);
    }

    /**
     * Update a single slider setting
     */
    public function updateSliderSetting(string $key, mixed $value): void
    {
        // Handle nested keys (e.g., 'breakpoints.1024.perPage')
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $ref = &$this->sliderConfig;

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
            $this->sliderConfig[$key] = $value;
        }

        // Validate the config after update
        $this->validateSliderConfig();

        // Mark as dirty for save
        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        Log::debug('Slider setting updated', [
            'key' => $key,
            'value' => $value,
            'config' => $this->sliderConfig,
        ]);
    }

    /**
     * Update multiple slider settings at once
     */
    public function updateSliderSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->updateSliderSetting($key, $value);
        }
    }

    /**
     * Apply default slider configuration
     */
    public function applySliderDefaults(): void
    {
        $this->sliderConfig = $this->sliderDefaults;

        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        $this->dispatch('notify', type: 'info', message: 'Domyslna konfiguracja slidera przywrocona');
    }

    /**
     * Validate and fix slider configuration
     */
    public function validateSliderConfig(): bool
    {
        $isValid = true;

        // Validate type
        $validTypes = ['slide', 'loop', 'fade'];
        if (!in_array($this->sliderConfig['type'] ?? '', $validTypes)) {
            $this->sliderConfig['type'] = 'loop';
            $isValid = false;
        }

        // Fade type only supports perPage = 1
        if (($this->sliderConfig['type'] ?? '') === 'fade') {
            $this->sliderConfig['perPage'] = 1;
        }

        // Validate perPage (1-6)
        $perPage = intval($this->sliderConfig['perPage'] ?? 3);
        if ($perPage < 1 || $perPage > 6) {
            $this->sliderConfig['perPage'] = max(1, min(6, $perPage));
            $isValid = false;
        }

        // Validate interval (1000-10000ms)
        $interval = intval($this->sliderConfig['interval'] ?? 3000);
        if ($interval < 1000 || $interval > 10000) {
            $this->sliderConfig['interval'] = max(1000, min(10000, $interval));
            $isValid = false;
        }

        // Validate speed (100-2000ms)
        $speed = intval($this->sliderConfig['speed'] ?? 400);
        if ($speed < 100 || $speed > 2000) {
            $this->sliderConfig['speed'] = max(100, min(2000, $speed));
            $isValid = false;
        }

        // Ensure boolean values
        $booleans = ['autoplay', 'pauseOnHover', 'pauseOnFocus', 'arrows', 'pagination'];
        foreach ($booleans as $key) {
            if (isset($this->sliderConfig[$key])) {
                $this->sliderConfig[$key] = (bool) $this->sliderConfig[$key];
            }
        }

        // Validate breakpoints
        if (isset($this->sliderConfig['breakpoints'])) {
            foreach ($this->sliderConfig['breakpoints'] as $breakpoint => $config) {
                if (isset($config['perPage'])) {
                    $bpPerPage = intval($config['perPage']);
                    $this->sliderConfig['breakpoints'][$breakpoint]['perPage'] = max(1, min(6, $bpPerPage));
                }
            }
        }

        return $isValid;
    }

    /**
     * Generate Splide.js JSON configuration
     */
    public function generateSliderJson(): string
    {
        $this->validateSliderConfig();

        $config = [
            'type' => $this->sliderConfig['type'] ?? 'loop',
            'perPage' => intval($this->sliderConfig['perPage'] ?? 3),
            'autoplay' => $this->sliderConfig['autoplay'] ?? true,
            'interval' => intval($this->sliderConfig['interval'] ?? 3000),
            'pauseOnHover' => $this->sliderConfig['pauseOnHover'] ?? true,
            'pauseOnFocus' => $this->sliderConfig['pauseOnFocus'] ?? true,
            'arrows' => $this->sliderConfig['arrows'] ?? true,
            'pagination' => $this->sliderConfig['pagination'] ?? true,
            'speed' => intval($this->sliderConfig['speed'] ?? 400),
            'gap' => $this->sliderConfig['gap'] ?? '16px',
            'breakpoints' => $this->sliderConfig['breakpoints'] ?? [],
        ];

        // Only include autoplay settings if autoplay is enabled
        if (!$config['autoplay']) {
            unset($config['interval'], $config['pauseOnHover'], $config['pauseOnFocus']);
        }

        return json_encode($config, JSON_PRETTY_PRINT);
    }

    /**
     * Generate inline data attribute for Splide initialization
     */
    public function generateSliderDataAttribute(): string
    {
        $this->validateSliderConfig();

        $config = [
            'type' => $this->sliderConfig['type'] ?? 'loop',
            'perPage' => intval($this->sliderConfig['perPage'] ?? 3),
            'gap' => $this->sliderConfig['gap'] ?? '16px',
            'speed' => intval($this->sliderConfig['speed'] ?? 400),
        ];

        if ($this->sliderConfig['autoplay'] ?? true) {
            $config['autoplay'] = true;
            $config['interval'] = intval($this->sliderConfig['interval'] ?? 3000);
            $config['pauseOnHover'] = $this->sliderConfig['pauseOnHover'] ?? true;
        }

        $config['arrows'] = $this->sliderConfig['arrows'] ?? true;
        $config['pagination'] = $this->sliderConfig['pagination'] ?? true;

        if (!empty($this->sliderConfig['breakpoints'])) {
            $config['breakpoints'] = $this->sliderConfig['breakpoints'];
        }

        return htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Parse slider configuration from existing block
     */
    public function parseSliderConfigFromBlock(array $block): array
    {
        $config = $this->sliderDefaults;

        // Check for pd-slider class element
        $data = $block['data'] ?? [];

        if (isset($data['sliderConfig'])) {
            $config = array_merge($config, $data['sliderConfig']);
        }

        // Try to parse from data-splide attribute if present
        if (isset($data['dataSplide'])) {
            $parsed = json_decode($data['dataSplide'], true);
            if (is_array($parsed)) {
                $config = array_merge($config, $parsed);
            }
        }

        return $config;
    }

    /**
     * Apply slider configuration to a block
     */
    public function applySliderConfigToBlock(int $blockIndex): void
    {
        if (!isset($this->blocks[$blockIndex])) {
            return;
        }

        $this->blocks[$blockIndex]['data']['sliderConfig'] = $this->sliderConfig;
        $this->blocks[$blockIndex]['data']['dataSplide'] = $this->generateSliderDataAttribute();

        if (property_exists($this, 'isDirty')) {
            $this->isDirty = true;
        }

        Log::info('Slider config applied to block', [
            'block_index' => $blockIndex,
            'config' => $this->sliderConfig,
        ]);
    }

    /**
     * Get available slider types
     */
    public function getSliderTypesProperty(): array
    {
        return [
            'slide' => [
                'label' => 'Slide',
                'description' => 'Standardowy przesuwany slider',
            ],
            'loop' => [
                'label' => 'Loop',
                'description' => 'Nieskonczona petla (klonowane slajdy)',
            ],
            'fade' => [
                'label' => 'Fade',
                'description' => 'Przenikanie (tylko 1 slajd)',
            ],
        ];
    }

    /**
     * Get speed presets
     */
    public function getSliderSpeedPresetsProperty(): array
    {
        return [
            300 => '0.3s (szybko)',
            400 => '0.4s (domyslnie)',
            500 => '0.5s',
            600 => '0.6s',
            800 => '0.8s',
            1000 => '1s (wolno)',
        ];
    }
}
