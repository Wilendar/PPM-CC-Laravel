<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Visual Description Editor - Product Description Model
 *
 * Opis wizualny produktu per sklep.
 * Jeden produkt moze miec rozne opisy dla roznych sklepow.
 *
 * @property int $id
 * @property int $product_id
 * @property int $shop_id
 * @property array $blocks_json
 * @property string|null $rendered_html
 * @property \Carbon\Carbon|null $last_rendered_at
 * @property int|null $template_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Product $product
 * @property-read PrestaShopShop $shop
 * @property-read DescriptionTemplate|null $template
 */
class ProductDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'shop_id',
        'blocks_json',
        'rendered_html',
        'last_rendered_at',
        'template_id',
        // Sync settings (ETAP_07f Faza 8.2)
        'sync_to_prestashop',
        'target_field',
        'include_inline_css',
        'last_synced_at',
        'sync_checksum',
        // UVE fields (ETAP_07f_P5)
        'blocks_v2',
        'format_version',
        // CSS-first architecture (ETAP_07h)
        'css_rules',
        'css_class_map',
        'css_mode',
        'css_migrated_at',
    ];

    protected $casts = [
        'blocks_json' => 'array',
        'blocks_v2' => 'array',
        'last_rendered_at' => 'datetime',
        'product_id' => 'integer',
        'shop_id' => 'integer',
        'template_id' => 'integer',
        // Sync settings (ETAP_07f Faza 8.2)
        'sync_to_prestashop' => 'boolean',
        'include_inline_css' => 'boolean',
        'last_synced_at' => 'datetime',
        // CSS-first architecture (ETAP_07h)
        'css_rules' => 'array',
        'css_class_map' => 'array',
        'css_migrated_at' => 'datetime',
    ];

    // =====================
    // BOOT
    // =====================

    protected static function booted(): void
    {
        // Auto-render HTML when blocks change
        static::saving(function (ProductDescription $description) {
            if ($description->isDirty('blocks_json')) {
                $description->markForRerender();
            }
        });
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Product this description belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Shop this description is for
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Template this description is based on (if any)
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DescriptionTemplate::class, 'template_id');
    }

    /**
     * Version history for this description
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProductDescriptionVersion::class, 'product_description_id')
            ->orderByDesc('version_number');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: Descriptions for specific product
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Descriptions for specific shop
     */
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Descriptions needing re-render
     */
    public function scopeNeedsRerender(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('rendered_html')
              ->orWhereNull('last_rendered_at')
              ->orWhereColumn('last_rendered_at', '<', 'updated_at');
        });
    }

    /**
     * Scope: Using specific template
     */
    public function scopeUsingTemplate(Builder $query, int $templateId): Builder
    {
        return $query->where('template_id', $templateId);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get blocks in UVE format (auto-migrate from legacy if needed)
     *
     * UVE format structure:
     * [
     *   'id' => 'blk_xxx',
     *   'type' => 'pd-intro',
     *   'locked' => true,
     *   'document' => ['root' => [...], 'variables' => [], 'cssClasses' => []],
     *   'compiled_html' => '<div>...</div>',
     *   'meta' => ['created_from' => 'import', 'source_shop_id' => 1]
     * ]
     */
    public function getBlocksAttribute(): array
    {
        // Use UVE format if available
        if ($this->format_version === '2.0' && !empty($this->blocks_v2)) {
            return $this->blocks_v2;
        }

        // Convert legacy format on-the-fly
        if (!empty($this->blocks_json)) {
            return $this->convertLegacyBlocksToUve($this->blocks_json);
        }

        return [];
    }

    /**
     * Set blocks (always save in UVE format)
     */
    public function setBlocksAttribute(array $blocks): void
    {
        $this->attributes['blocks_v2'] = json_encode($blocks);
        $this->attributes['format_version'] = '2.0';
    }

    /**
     * Convert legacy blocks_json format to UVE format
     *
     * Legacy format:
     * ['id' => '...', 'type' => '...', 'data' => ['content' => [...], 'settings' => [...]]]
     *
     * UVE format:
     * ['id' => '...', 'type' => '...', 'locked' => true, 'document' => [...], 'compiled_html' => '...']
     */
    protected function convertLegacyBlocksToUve(array $legacyBlocks): array
    {
        return array_map(function ($block) {
            // Already in UVE format
            if (isset($block['document'])) {
                return $block;
            }

            // Get HTML content from various possible locations
            $html = $block['data']['content']['html']
                ?? $block['content']['html']
                ?? $block['html']
                ?? '';

            // Build minimal document structure for legacy compatibility
            $document = [
                'version' => '1.0',
                'root' => [
                    'id' => 'el-' . ($block['id'] ?? uniqid()),
                    'type' => 'container',
                    'tag' => 'div',
                    'classes' => [],
                    'styles' => [],
                    'children' => [],
                    'visible' => true,
                    'locked' => false,
                ],
                'variables' => [],
                'cssClasses' => [],
            ];

            return [
                'id' => $block['id'] ?? 'blk_' . uniqid(),
                'type' => $block['type'] ?? 'prestashop-section',
                'locked' => true, // Legacy blocks start locked
                'document' => $document,
                'compiled_html' => $html,
                'meta' => [
                    'created_from' => 'migration',
                    'migrated_at' => now()->toIso8601String(),
                    'legacy_data' => $block['data'] ?? $block,
                ],
            ];
        }, $legacyBlocks);
    }

    /**
     * Check if using UVE format
     */
    public function getIsUveFormatAttribute(): bool
    {
        return $this->format_version === '2.0';
    }

    /**
     * Get block count
     */
    public function getBlockCountAttribute(): int
    {
        return count($this->blocks_json ?? []);
    }

    /**
     * Check if description needs re-rendering
     */
    public function getNeedsRenderingAttribute(): bool
    {
        if (empty($this->rendered_html)) {
            return true;
        }

        if (!$this->last_rendered_at) {
            return true;
        }

        return $this->last_rendered_at->lt($this->updated_at);
    }

    /**
     * Check if description is using a template
     */
    public function getIsFromTemplateAttribute(): bool
    {
        return $this->template_id !== null;
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Mark description for re-rendering (clears cached HTML)
     */
    public function markForRerender(): void
    {
        $this->rendered_html = null;
        $this->last_rendered_at = null;
    }

    /**
     * Parse and return blocks array
     *
     * Handles two block data formats:
     * - Parser format: ['type' => '...', 'data' => ['content' => [...], 'settings' => [...]]]
     * - Direct format: ['type' => '...', 'content' => [...], 'settings' => [...]]
     */
    public function parseBlocks(): array
    {
        $blocks = $this->blocks_json ?? [];

        // Ensure each block has required structure
        return array_map(function ($block) {
            // Handle parser format with nested 'data' key
            if (isset($block['data'])) {
                return [
                    'type' => $block['type'] ?? 'unknown',
                    'content' => $block['data']['content'] ?? [],
                    'settings' => $block['data']['settings'] ?? [],
                ];
            }

            // Handle direct format (legacy/manual)
            return [
                'type' => $block['type'] ?? 'unknown',
                'content' => $block['content'] ?? [],
                'settings' => $block['settings'] ?? [],
            ];
        }, $blocks);
    }

    /**
     * Render description to HTML
     * Note: Actual rendering logic will be in BlockRenderer service
     */
    public function render(): string
    {
        // If already rendered and up-to-date, return cached
        if (!$this->needs_rendering && $this->rendered_html) {
            return $this->rendered_html;
        }

        // Rendering will be handled by BlockRenderer service
        // This is just a placeholder that returns empty for now
        return $this->rendered_html ?? '';
    }

    /**
     * Update rendered HTML and timestamp
     */
    public function setRenderedHtml(string $html): void
    {
        $this->rendered_html = $html;
        $this->last_rendered_at = now();
        $this->saveQuietly(); // Don't trigger saving event again
    }

    /**
     * Apply template blocks to this description
     */
    public function applyTemplate(DescriptionTemplate $template): void
    {
        $this->blocks_json = $template->blocks_json;
        $this->template_id = $template->id;
        $this->markForRerender();
    }

    /**
     * Detach from template (keeps blocks but removes reference)
     */
    public function detachTemplate(): void
    {
        $this->template_id = null;
    }

    /**
     * Get or create description for product-shop pair
     */
    public static function getOrCreate(int $productId, int $shopId): self
    {
        return self::firstOrCreate(
            [
                'product_id' => $productId,
                'shop_id' => $shopId,
            ],
            [
                'blocks_json' => [],
            ]
        );
    }

    // =====================
    // PRESTASHOP SYNC (ETAP_07f Faza 8.2)
    // =====================

    /**
     * Check if description needs re-rendering for sync
     *
     * Compares current blocks_json checksum with stored sync_checksum
     * to determine if content has changed since last sync.
     *
     * @return bool True if re-rendering is needed
     */
    public function needsRerender(): bool
    {
        // If no rendered HTML, always needs render
        if (empty($this->rendered_html)) {
            return true;
        }

        // If no last_rendered_at, needs render
        if (!$this->last_rendered_at) {
            return true;
        }

        // If blocks were updated after last render
        if ($this->updated_at && $this->last_rendered_at->lt($this->updated_at)) {
            return true;
        }

        return false;
    }

    /**
     * Check if description needs sync to PrestaShop
     *
     * Compares current content checksum with last synced checksum.
     *
     * @return bool True if sync is needed
     */
    public function needsSync(): bool
    {
        // If sync is disabled, never needs sync
        if (!$this->sync_to_prestashop) {
            return false;
        }

        // If never synced, needs sync
        if (!$this->last_synced_at) {
            return true;
        }

        // Compare checksums
        $currentChecksum = $this->calculateChecksum();
        return $currentChecksum !== $this->sync_checksum;
    }

    /**
     * Calculate content checksum for change detection
     *
     * @return string MD5 hash of blocks_json
     */
    public function calculateChecksum(): string
    {
        $data = [
            'blocks' => $this->blocks_json ?? [],
            'target_field' => $this->target_field,
            'include_inline_css' => $this->include_inline_css,
        ];

        return md5(json_encode($data));
    }

    /**
     * Render blocks and cache the result
     *
     * Uses BlockRenderer service to generate HTML from blocks_json.
     * For UVE format (format_version 2.0), uses compiled_html directly.
     * Stores rendered HTML and updates last_rendered_at timestamp.
     *
     * @param bool $includeStyles Whether to include inline CSS
     * @return string Rendered HTML
     */
    public function renderAndCache(bool $includeStyles = null): string
    {
        // Use instance setting if not specified
        $includeStyles = $includeStyles ?? $this->include_inline_css;

        try {
            $html = '';

            // ETAP_07h: Check for UVE format (blocks_v2) - use compiled_html directly
            if ($this->format_version === '2.0' && !empty($this->blocks_v2)) {
                $html = $this->renderUveBlocks();
                \Illuminate\Support\Facades\Log::debug('[VISUAL DESC] Using UVE format compiled_html', [
                    'product_id' => $this->product_id,
                    'shop_id' => $this->shop_id,
                    'blocks_count' => count($this->blocks_v2),
                ]);
            } else {
                // Legacy format: use BlockRenderer
                /** @var \App\Services\VisualEditor\BlockRenderer $renderer */
                $renderer = app(\App\Services\VisualEditor\BlockRenderer::class);

                $html = $renderer->render($this->parseBlocks(), [
                    'shop_id' => $this->shop_id,
                    'include_styles' => $includeStyles,
                    'minify' => true,
                ]);
            }

            // ETAP_07h: Apply CSS class map (replace data-uve-id with generated CSS classes)
            $html = $this->applyCssClassMap($html);

            // Sanitize HTML for PrestaShop (no scripts, valid HTML5)
            $html = $this->sanitizeForPrestaShop($html);

            // Cache rendered HTML
            $this->setRenderedHtml($html);

            \Illuminate\Support\Facades\Log::info('[VISUAL DESC] Rendered and cached description', [
                'product_id' => $this->product_id,
                'shop_id' => $this->shop_id,
                'html_length' => strlen($html),
                'include_styles' => $includeStyles,
                'css_classes_applied' => !empty($this->css_class_map),
                'format' => $this->format_version === '2.0' ? 'UVE' : 'legacy',
            ]);

            return $html;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[VISUAL DESC] Render failed', [
                'product_id' => $this->product_id,
                'shop_id' => $this->shop_id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Render UVE format blocks by extracting compiled_html
     *
     * ETAP_07h: For UVE format blocks, the compiled_html is already
     * the final HTML with data-uve-id attributes. We just need to
     * concatenate all blocks and wrap in uve-content container.
     *
     * @return string Combined HTML from all UVE blocks
     */
    private function renderUveBlocks(): string
    {
        $blocks = $this->blocks_v2 ?? [];
        $htmlParts = [];

        foreach ($blocks as $block) {
            if (!empty($block['compiled_html'])) {
                $htmlParts[] = $block['compiled_html'];
            }
        }

        // Wrap in uve-content container for CSS scoping
        $html = implode("\n", $htmlParts);

        return '<div class="uve-content">' . $html . '</div>';
    }

    /**
     * Apply CSS class map to HTML
     *
     * ETAP_07h CSS-First Architecture:
     * Transforms elements with data-uve-id attributes by adding
     * corresponding CSS classes from css_class_map.
     *
     * Uses DOMDocument for proper HTML manipulation to avoid duplicate attributes.
     *
     * @param string $html Raw HTML with data-uve-id attributes
     * @return string HTML with CSS classes added
     */
    private function applyCssClassMap(string $html): string
    {
        $classMap = $this->css_class_map ?? [];

        if (empty($classMap) || empty($html)) {
            return $html;
        }

        // Use DOMDocument for proper HTML manipulation
        $doc = new \DOMDocument();
        $doc->encoding = 'UTF-8';

        // Suppress warnings for HTML5 tags and load HTML
        libxml_use_internal_errors(true);
        // Wrap in container to preserve structure
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__uve_wrapper__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // Process each element in the class map
        foreach ($classMap as $elementId => $cssClass) {
            if (empty($elementId) || empty($cssClass)) {
                continue;
            }

            // Find elements with this data-uve-id
            $elements = $xpath->query('//*[@data-uve-id="' . $elementId . '"]');

            foreach ($elements as $element) {
                $existingClass = $element->getAttribute('class');

                // Only add if not already present
                if (!str_contains($existingClass, $cssClass)) {
                    $newClass = trim($existingClass . ' ' . $cssClass);
                    $element->setAttribute('class', $newClass);
                }
            }
        }

        // Get inner HTML of wrapper
        $wrapper = $doc->getElementById('__uve_wrapper__');
        if ($wrapper) {
            $html = '';
            foreach ($wrapper->childNodes as $child) {
                $html .= $doc->saveHTML($child);
            }
        } else {
            // Fallback: get body content
            $body = $doc->getElementsByTagName('body')->item(0);
            if ($body) {
                $html = '';
                foreach ($body->childNodes as $child) {
                    $html .= $doc->saveHTML($child);
                }
            }
        }

        \Illuminate\Support\Facades\Log::debug('[VISUAL DESC] Applied CSS class map', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'class_map_count' => count($classMap),
        ]);

        return $html;
    }

    /**
     * Get HTML for PrestaShop sync
     *
     * Returns cached HTML or renders fresh if needed.
     * Handles both description and description_short targets.
     *
     * @return array ['description' => string, 'description_short' => string]
     */
    public function getHtmlForPrestaShop(): array
    {
        // Ensure HTML is rendered and up-to-date
        if ($this->needsRerender()) {
            $this->renderAndCache();
        }

        $html = $this->rendered_html ?? '';

        // Return based on target field setting
        return match ($this->target_field) {
            'description' => [
                'description' => $html,
                'description_short' => null, // Don't override
            ],
            'description_short' => [
                'description' => null, // Don't override
                'description_short' => $this->truncateForShortDescription($html),
            ],
            'both' => [
                'description' => $html,
                'description_short' => $this->truncateForShortDescription($html),
            ],
            default => [
                'description' => $html,
                'description_short' => null,
            ],
        };
    }

    /**
     * Mark description as synced to PrestaShop
     *
     * Updates last_synced_at and sync_checksum.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_checksum' => $this->calculateChecksum(),
        ]);

        \Illuminate\Support\Facades\Log::info('[VISUAL DESC] Marked as synced', [
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'sync_checksum' => $this->sync_checksum,
        ]);
    }

    /**
     * Sanitize HTML for PrestaShop
     *
     * Removes potentially dangerous elements:
     * - Script tags
     * - Event handlers (onclick, onload, etc.)
     * - External resources (iframes, objects)
     *
     * @param string $html Raw HTML
     * @return string Sanitized HTML
     */
    private function sanitizeForPrestaShop(string $html): string
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Remove event handlers
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Remove iframes
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);

        // Remove objects/embeds
        $html = preg_replace('/<(object|embed)\b[^>]*>.*?<\/\1>/is', '', $html);

        // Remove javascript: links
        $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $html);

        return trim($html);
    }

    /**
     * Truncate HTML for short description
     *
     * PrestaShop description_short has a character limit (typically 800 chars).
     * This creates a summary from the full HTML.
     *
     * @param string $html Full HTML
     * @param int $maxLength Maximum length (default: 400)
     * @return string Truncated text
     */
    private function truncateForShortDescription(string $html, int $maxLength = 400): string
    {
        // Strip HTML tags for short description
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Truncate if needed
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            // Cut at last word boundary
            $lastSpace = mb_strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
                $text = mb_substr($text, 0, $lastSpace);
            }
            $text .= '...';
        }

        // Wrap in paragraph for valid HTML
        return '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    // =====================
    // SCOPES (SYNC RELATED)
    // =====================

    /**
     * Scope: Descriptions enabled for PrestaShop sync
     */
    public function scopeSyncEnabled(Builder $query): Builder
    {
        return $query->where('sync_to_prestashop', true);
    }

    /**
     * Scope: Descriptions needing sync (checksum changed)
     */
    public function scopeNeedsSync(Builder $query): Builder
    {
        return $query->where('sync_to_prestashop', true)
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                  ->orWhereColumn('updated_at', '>', 'last_synced_at');
            });
    }

    // =====================
    // VERSION HISTORY (ETAP_07f Faza 6.1.4.3)
    // =====================

    /**
     * Create a version snapshot of current state
     *
     * @param string $changeType Type of change (created, updated, synced, etc.)
     * @param int|null $userId User who made the change
     * @param array $metadata Additional metadata
     * @return ProductDescriptionVersion
     */
    public function createVersion(
        string $changeType = ProductDescriptionVersion::CHANGE_UPDATED,
        ?int $userId = null,
        array $metadata = []
    ): ProductDescriptionVersion {
        return ProductDescriptionVersion::createVersion($this, $changeType, $userId, $metadata);
    }

    /**
     * Get latest version
     */
    public function getLatestVersion(): ?ProductDescriptionVersion
    {
        return $this->versions()->first();
    }

    /**
     * Get version count
     */
    public function getVersionCount(): int
    {
        return $this->versions()->count();
    }

    /**
     * Restore from a specific version
     */
    public function restoreFromVersion(ProductDescriptionVersion $version, ?int $userId = null): self
    {
        return $version->restore($userId);
    }
}
