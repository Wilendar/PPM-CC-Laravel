<?php

namespace App\Services\VisualEditor\Blocks\Interactive;

use App\Services\VisualEditor\Blocks\BaseBlock;

/**
 * Raw HTML Block - Custom HTML/CSS/JS content.
 *
 * Allows advanced users to insert custom code that will be
 * rendered as-is in the output. Useful for:
 * - PrestaShop widget embeds
 * - Custom CSS styling
 * - JavaScript functionality
 * - Third-party integrations
 *
 * SECURITY: By default, script tags are sanitized.
 * Set sanitize=false only for trusted content.
 */
class RawHtmlBlock extends BaseBlock
{
    public string $type = 'raw-html';
    public string $name = 'Custom HTML';
    public string $icon = 'heroicons-code-bracket';
    public string $category = 'interactive';

    /**
     * ETAP_07h PP.3: Property Panel controls for RawHtmlBlock.
     * Note: Limited controls since content is user-defined HTML.
     */
    public array $propertyPanelControls = [
        'root' => ['box-model', 'background', 'border'],
    ];

    public array $defaultSettings = [
        'wrapper_class' => '',
        'wrapper_id' => '',
        'sanitize' => true,
        'custom_css' => '',
        'custom_js' => '',
        'js_position' => 'after', // before|after
    ];

    public function render(array $content, array $settings, array $children = []): string
    {
        $settings = $this->mergeSettings($settings);

        $html = $content['html'] ?? '';
        $customCss = trim($settings['custom_css'] ?? '');
        $customJs = trim($settings['custom_js'] ?? '');

        // Sanitize if enabled (remove scripts)
        if ($settings['sanitize']) {
            $html = $this->sanitizeHtml($html);
            $customJs = ''; // No JS in sanitized mode
        }

        // Build wrapper attributes
        $wrapperClass = trim('pd-block pd-raw-html ' . ($settings['wrapper_class'] ?? ''));
        $wrapperId = $settings['wrapper_id'] ?? '';
        $idAttr = $wrapperId ? " id=\"{$this->escape($wrapperId)}\"" : '';

        // Build output parts
        $output = '';

        // Custom CSS (inline style tag)
        if ($customCss) {
            $output .= "<style>\n{$customCss}\n</style>\n";
        }

        // JS before content
        if ($customJs && $settings['js_position'] === 'before') {
            $output .= "<script>\n{$customJs}\n</script>\n";
        }

        // Main HTML content
        $output .= "<div class=\"{$this->escape($wrapperClass)}\"{$idAttr}>\n{$html}\n</div>";

        // JS after content (default)
        if ($customJs && $settings['js_position'] === 'after') {
            $output .= "\n<script>\n{$customJs}\n</script>";
        }

        return $output;
    }

    public function getSchema(): array
    {
        return [
            'content' => [
                'html' => [
                    'type' => 'code',
                    'label' => 'Kod HTML',
                    'language' => 'html',
                    'required' => true,
                    'rows' => 15,
                    'placeholder' => '<div class="my-custom-block">\n  <!-- Your HTML here -->\n</div>',
                ],
            ],
            'settings' => [
                'wrapper_class' => [
                    'type' => 'text',
                    'label' => 'Klasy CSS kontenera',
                    'placeholder' => 'my-class another-class',
                    'default' => '',
                    'group' => 'wrapper',
                ],
                'wrapper_id' => [
                    'type' => 'text',
                    'label' => 'ID kontenera',
                    'placeholder' => 'my-unique-id',
                    'default' => '',
                    'group' => 'wrapper',
                ],
                'custom_css' => [
                    'type' => 'code',
                    'label' => 'Custom CSS',
                    'language' => 'css',
                    'rows' => 8,
                    'placeholder' => '.my-class {\n  color: #333;\n  padding: 1rem;\n}',
                    'default' => '',
                    'group' => 'advanced',
                ],
                'custom_js' => [
                    'type' => 'code',
                    'label' => 'Custom JavaScript',
                    'language' => 'javascript',
                    'rows' => 8,
                    'placeholder' => '// Your JavaScript code\nconsole.log("Block loaded");',
                    'default' => '',
                    'group' => 'advanced',
                ],
                'js_position' => [
                    'type' => 'select',
                    'label' => 'Pozycja JS',
                    'options' => [
                        'after' => 'Po HTML (domyslnie)',
                        'before' => 'Przed HTML',
                    ],
                    'default' => 'after',
                    'group' => 'advanced',
                    'condition' => ['custom_js' => ['!=', '']],
                ],
                'sanitize' => [
                    'type' => 'boolean',
                    'label' => 'Sanityzacja (usun skrypty)',
                    'default' => true,
                    'group' => 'advanced',
                    'help' => 'Wylacz tylko dla zaufanej tresci',
                ],
            ],
        ];
    }

    /**
     * Sanitize HTML by removing potentially dangerous elements.
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Remove event handlers (onclick, onload, etc.)
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Remove javascript: links
        $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $html);

        return $html;
    }

    /**
     * Override getPreview for better block palette display.
     */
    public function getPreview(): string
    {
        return <<<HTML
        <div class="block-preview block-preview--raw-html">
            <code>&lt;/&gt;</code>
            <span>Custom HTML</span>
        </div>
        HTML;
    }
}
