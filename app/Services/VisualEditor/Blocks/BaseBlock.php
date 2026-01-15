<?php

namespace App\Services\VisualEditor\Blocks;

use Illuminate\Support\Str;

/**
 * Abstract base class for all Visual Editor blocks.
 *
 * Each block type extends this class and implements:
 * - render() - generates HTML output
 * - getSchema() - defines content/settings structure
 */
abstract class BaseBlock
{
    /**
     * Unique block type identifier (e.g., 'hero-banner', 'two-column')
     */
    public string $type;

    /**
     * Human-readable block name
     */
    public string $name;

    /**
     * Icon identifier (heroicons format)
     */
    public string $icon = 'heroicons-cube';

    /**
     * Block category: layout, content, media, interactive
     */
    public string $category = 'content';

    /**
     * Short description for block palette tooltip
     */
    public string $description = '';

    /**
     * Default settings for this block type
     */
    public array $defaultSettings = [];

    /**
     * Whether this block can contain child blocks
     */
    public bool $supportsChildren = false;

    /**
     * ETAP_07h PP.1: Property Panel controls for this block type.
     * Maps element selectors to control arrays.
     *
     * Example:
     * [
     *   'root' => ['typography', 'background', 'layout-flex'],
     *   '.pd-intro__title' => ['typography', 'color-picker'],
     *   'img' => ['image-settings', 'border'],
     * ]
     *
     * If empty, PropertyPanelService falls back to hardcoded mappings.
     */
    public array $propertyPanelControls = [];

    /**
     * ETAP_07h PP.1: Custom controls specific to this block.
     * Control definitions not from standard registry.
     */
    public array $customControls = [];

    /**
     * Render the block to HTML.
     *
     * @param array $content Block content data
     * @param array $settings Block settings
     * @param array $children Nested child blocks (for layout blocks)
     * @return string Rendered HTML
     */
    abstract public function render(array $content, array $settings, array $children = []): string;

    /**
     * Get the block schema for editor UI.
     *
     * Schema structure:
     * [
     *   'content' => [
     *     'field_name' => ['type' => 'text|textarea|image|..', 'label' => '...', ...]
     *   ],
     *   'settings' => [
     *     'setting_name' => ['type' => 'text|select|boolean|...', 'label' => '...', 'default' => ...]
     *   ]
     * ]
     */
    abstract public function getSchema(): array;

    /**
     * Get a preview thumbnail or placeholder HTML for the block palette.
     */
    public function getPreview(): string
    {
        return <<<HTML
        <div class="block-preview block-preview--{$this->type}">
            <span class="block-preview__icon">{$this->icon}</span>
            <span class="block-preview__name">{$this->name}</span>
        </div>
        HTML;
    }

    /**
     * Validate block data against schema.
     *
     * @param array $data Block data to validate
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $data): array
    {
        $errors = [];
        $schema = $this->getSchema();

        // Validate required content fields
        if (isset($schema['content'])) {
            foreach ($schema['content'] as $field => $config) {
                if (($config['required'] ?? false) && empty($data['content'][$field] ?? null)) {
                    $errors["content.{$field}"] = "Pole {$config['label']} jest wymagane.";
                }
            }
        }

        // Validate setting constraints
        if (isset($schema['settings'])) {
            foreach ($schema['settings'] as $field => $config) {
                $value = $data['settings'][$field] ?? null;

                if ($value !== null) {
                    // Range validation
                    if (isset($config['min']) && $value < $config['min']) {
                        $errors["settings.{$field}"] = "Wartosc musi byc >= {$config['min']}.";
                    }
                    if (isset($config['max']) && $value > $config['max']) {
                        $errors["settings.{$field}"] = "Wartosc musi byc <= {$config['max']}.";
                    }

                    // Select options validation
                    if ($config['type'] === 'select' && isset($config['options'])) {
                        $validOptions = is_array($config['options'])
                            ? array_keys($config['options'])
                            : $config['options'];
                        if (!in_array($value, $validOptions)) {
                            $errors["settings.{$field}"] = "Nieprawidlowa opcja.";
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Merge provided settings with defaults.
     */
    public function mergeSettings(array $settings): array
    {
        return array_merge($this->defaultSettings, $settings);
    }

    /**
     * Get default data for creating a new block instance.
     * Extracts default values from schema and defaultSettings.
     */
    public function getDefaultData(): array
    {
        $schema = $this->getSchema();

        // Extract default content values
        $content = [];
        if (isset($schema['content'])) {
            foreach ($schema['content'] as $field => $config) {
                $content[$field] = $config['default'] ?? '';
            }
        }

        // Use defaultSettings or extract from schema
        $settings = $this->defaultSettings;
        if (empty($settings) && isset($schema['settings'])) {
            foreach ($schema['settings'] as $field => $config) {
                $settings[$field] = $config['default'] ?? null;
            }
        }

        return [
            'content' => $content,
            'settings' => $settings,
        ];
    }

    /**
     * Get block info for registration/display.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'icon' => $this->icon,
            'category' => $this->category,
            'description' => $this->description,
            'supportsChildren' => $this->supportsChildren,
            'defaultSettings' => $this->defaultSettings,
            'schema' => $this->getSchema(),
            'propertyPanelControls' => $this->propertyPanelControls,
        ];
    }

    /**
     * ETAP_07h PP.1: Get Property Panel configuration for this block.
     *
     * Returns control configuration for Property Panel based on element selector.
     * Allows block to define which controls appear for each element type.
     *
     * @param string $elementSelector CSS selector or 'root' for main block element
     * @return array<string> Control type identifiers
     */
    public function getPropertyPanelConfig(string $elementSelector = 'root'): array
    {
        // Return specific selector controls if defined
        if (isset($this->propertyPanelControls[$elementSelector])) {
            return $this->propertyPanelControls[$elementSelector];
        }

        // Check for tag-based selector (e.g., 'img', 'h1', 'p')
        foreach ($this->propertyPanelControls as $selector => $controls) {
            // Match tag selectors
            if (preg_match('/^[a-z][a-z0-9]*$/i', $selector) && strtolower($selector) === strtolower($elementSelector)) {
                return $controls;
            }
        }

        // Return root controls as fallback if no specific match
        return $this->propertyPanelControls['root'] ?? [];
    }

    /**
     * ETAP_07h PP.1: Check if block has custom Property Panel configuration.
     *
     * @return bool True if block defines its own controls
     */
    public function hasPropertyPanelConfig(): bool
    {
        return !empty($this->propertyPanelControls);
    }

    /**
     * Escape HTML content safely.
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSS class string from array.
     */
    protected function classNames(array $classes): string
    {
        return implode(' ', array_filter($classes));
    }

    /**
     * Generate inline style string from array.
     */
    protected function inlineStyles(array $styles): string
    {
        $result = [];
        foreach ($styles as $property => $value) {
            if ($value !== null && $value !== '') {
                $result[] = "{$property}: {$value}";
            }
        }
        return implode('; ', $result);
    }

    /**
     * Convert text alignment to CSS class.
     */
    protected function alignmentClass(string $alignment): string
    {
        return match ($alignment) {
            'left' => 'text-left',
            'center' => 'text-center',
            'right' => 'text-right',
            'justify' => 'text-justify',
            default => '',
        };
    }

    /**
     * Generate responsive image srcset attribute.
     */
    protected function generateSrcset(string $baseUrl, array $widths = [400, 800, 1200]): string
    {
        $srcset = [];
        foreach ($widths as $width) {
            // Assuming CDN supports width parameter
            $url = Str::contains($baseUrl, '?')
                ? "{$baseUrl}&w={$width}"
                : "{$baseUrl}?w={$width}";
            $srcset[] = "{$url} {$width}w";
        }
        return implode(', ', $srcset);
    }

    /**
     * Wrap content in a block container with standard attributes.
     */
    protected function wrapBlock(string $content, array $extraClasses = [], array $extraStyles = []): string
    {
        $classes = $this->classNames(array_merge(
            ['pd-block', "pd-block--{$this->type}"],
            $extraClasses
        ));

        $style = !empty($extraStyles) ? ' style="' . $this->inlineStyles($extraStyles) . '"' : '';

        return "<div class=\"{$classes}\"{$style}>{$content}</div>";
    }
}
