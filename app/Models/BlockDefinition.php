<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * BlockDefinition Model
 *
 * Represents a shop-specific block definition created from prestashop-section.
 * Admin can edit the render_template directly for full customization.
 *
 * ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System
 *
 * @property int $id
 * @property int $shop_id
 * @property string $type
 * @property string $name
 * @property string $category
 * @property string|null $icon
 * @property string|null $description
 * @property array $schema
 * @property string $render_template
 * @property array|null $css_classes
 * @property string|null $sample_html
 * @property bool $is_active
 * @property int $usage_count
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class BlockDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'type',
        'name',
        'category',
        'icon',
        'description',
        'schema',
        'render_template',
        'builder_document',
        'builder_version',
        'css_classes',
        'sample_html',
        'is_active',
        'usage_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schema' => 'array',
        'builder_document' => 'array',
        'css_classes' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Default schema structure.
     */
    public static function getDefaultSchema(): array
    {
        return [
            'content' => [],
            'settings' => [],
        ];
    }

    // ========== RELATIONSHIPS ==========

    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========== SCOPES ==========

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('name');
    }

    // ========== ACCESSORS ==========

    /**
     * Get content fields from schema.
     */
    public function getContentFieldsAttribute(): array
    {
        return $this->schema['content'] ?? [];
    }

    /**
     * Get settings fields from schema.
     */
    public function getSettingsFieldsAttribute(): array
    {
        return $this->schema['settings'] ?? [];
    }

    /**
     * Get CSS classes as string for display.
     */
    public function getCssClassesStringAttribute(): string
    {
        return implode(', ', $this->css_classes ?? []);
    }

    // ========== METHODS ==========

    /**
     * Render block with given content and settings.
     *
     * Uses Blade-like template syntax for rendering.
     *
     * @param array $content Block content data
     * @param array $settings Block settings
     * @return string Rendered HTML
     */
    public function render(array $content, array $settings): string
    {
        $template = $this->render_template;

        if (empty($template)) {
            return '<!-- Empty block template -->';
        }

        try {
            // Replace simple placeholders
            // Format: {{ $variable }} or {{ $content.field }}
            $html = preg_replace_callback(
                '/\{\{\s*\$(\w+(?:\.\w+)*)\s*\}\}/',
                function ($matches) use ($content, $settings) {
                    return $this->resolvePlaceholder($matches[1], $content, $settings);
                },
                $template
            );

            // Process @foreach loops
            $html = $this->processForEachLoops($html, $content, $settings);

            // Process @if conditions
            $html = $this->processIfConditions($html, $content, $settings);

            return $html;

        } catch (\Throwable $e) {
            \Log::error('BlockDefinition: Render failed', [
                'block_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return "<!-- Block render error: {$e->getMessage()} -->";
        }
    }

    /**
     * Resolve placeholder value from content or settings.
     */
    protected function resolvePlaceholder(string $path, array $content, array $settings): string
    {
        $parts = explode('.', $path);
        $root = array_shift($parts);

        $data = match ($root) {
            'content' => $content,
            'settings' => $settings,
            default => array_merge($content, $settings),
        };

        $value = $data;
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        return is_string($value) ? htmlspecialchars($value) : (string) $value;
    }

    /**
     * Process @foreach loops in template.
     */
    protected function processForEachLoops(string $template, array $content, array $settings): string
    {
        // Pattern: @foreach($items as $item) ... @endforeach
        $pattern = '/@foreach\s*\(\s*\$(\w+)\s+as\s+\$(\w+)\s*\)(.*?)@endforeach/s';

        return preg_replace_callback($pattern, function ($matches) use ($content, $settings) {
            $arrayName = $matches[1];
            $itemName = $matches[2];
            $loopContent = $matches[3];

            $items = $content[$arrayName] ?? $settings[$arrayName] ?? [];
            $result = '';

            foreach ($items as $index => $item) {
                $itemHtml = $loopContent;

                // Replace item placeholders
                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        $itemHtml = str_replace(
                            ["\${{$itemName}.{$key}}", "{{ \${$itemName}.{$key} }}"],
                            htmlspecialchars((string) $value),
                            $itemHtml
                        );
                    }
                }

                // Replace $loop.index
                $itemHtml = str_replace(['$loop.index', '{{ $loop.index }}'], (string) $index, $itemHtml);

                $result .= $itemHtml;
            }

            return $result;
        }, $template);
    }

    /**
     * Process @if conditions in template.
     */
    protected function processIfConditions(string $template, array $content, array $settings): string
    {
        // Pattern: @if($condition) ... @endif
        $pattern = '/@if\s*\(\s*\$(\w+(?:\.\w+)*)\s*\)(.*?)@endif/s';

        return preg_replace_callback($pattern, function ($matches) use ($content, $settings) {
            $condition = $matches[1];
            $ifContent = $matches[2];

            $value = $this->resolvePlaceholder($condition, $content, $settings);

            if ($value && $value !== '0' && $value !== 'false') {
                return $ifContent;
            }

            return '';
        }, $template);
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Generate unique type slug for shop.
     */
    public static function generateTypeSlug(string $name, int $shopId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('shop_id', $shopId)->where('type', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Validate render template syntax.
     */
    public function validateTemplate(): array
    {
        $errors = [];
        $template = $this->render_template;

        // Check for unclosed @foreach
        $foreachCount = preg_match_all('/@foreach/', $template);
        $endforeachCount = preg_match_all('/@endforeach/', $template);
        if ($foreachCount !== $endforeachCount) {
            $errors[] = 'Niezamkniety @foreach - sprawdz czy wszystkie @endforeach sa obecne';
        }

        // Check for unclosed @if
        $ifCount = preg_match_all('/@if/', $template);
        $endifCount = preg_match_all('/@endif/', $template);
        if ($ifCount !== $endifCount) {
            $errors[] = 'Niezamkniety @if - sprawdz czy wszystkie @endif sa obecne';
        }

        // Check for unclosed HTML tags (basic check)
        $openDivs = preg_match_all('/<div[^>]*>/', $template);
        $closeDivs = preg_match_all('/<\/div>/', $template);
        if ($openDivs !== $closeDivs) {
            $errors[] = "Niezbalansowane tagi <div>: {$openDivs} otwartych, {$closeDivs} zamknietych";
        }

        return $errors;
    }

    /**
     * Export block definition as JSON.
     */
    public function exportAsJson(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'category' => $this->category,
            'icon' => $this->icon,
            'description' => $this->description,
            'schema' => $this->schema,
            'render_template' => $this->render_template,
            'css_classes' => $this->css_classes,
            'sample_html' => $this->sample_html,
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Import block definition from JSON.
     */
    public static function importFromJson(array $data, int $shopId, ?int $userId = null): static
    {
        $type = static::generateTypeSlug($data['name'] ?? 'imported-block', $shopId);

        return static::create([
            'shop_id' => $shopId,
            'type' => $type,
            'name' => $data['name'] ?? 'Imported Block',
            'category' => $data['category'] ?? 'shop-custom',
            'icon' => $data['icon'] ?? 'heroicons-cube',
            'description' => $data['description'] ?? null,
            'schema' => $data['schema'] ?? static::getDefaultSchema(),
            'render_template' => $data['render_template'] ?? '',
            'css_classes' => $data['css_classes'] ?? [],
            'sample_html' => $data['sample_html'] ?? null,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }
}
