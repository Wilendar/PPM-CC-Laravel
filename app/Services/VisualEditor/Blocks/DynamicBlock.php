<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Blocks;

use App\Models\BlockDefinition;

/**
 * DynamicBlock - runtime block from database BlockDefinition.
 *
 * Bridges the block registry with shop-specific custom blocks stored in database.
 * Allows admins to create reusable blocks from prestashop-section HTML.
 *
 * ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System
 */
class DynamicBlock extends BaseBlock
{
    /**
     * The database definition model.
     */
    protected BlockDefinition $definition;

    /**
     * Create a new DynamicBlock from BlockDefinition.
     */
    public function __construct(BlockDefinition $definition)
    {
        $this->definition = $definition;

        // Set block properties from definition
        $this->type = $definition->type;
        $this->name = $definition->name;
        $this->icon = $definition->icon ?? 'heroicons-cube';
        $this->category = $definition->category ?? 'shop-custom';
        $this->description = $definition->description ?? '';
        $this->supportsChildren = false;

        // Extract default settings from schema
        $this->defaultSettings = $this->extractDefaultSettings();
    }

    /**
     * Get the BlockDefinition model.
     */
    public function getDefinition(): BlockDefinition
    {
        return $this->definition;
    }

    /**
     * Render the block using database template.
     */
    public function render(array $content, array $settings, array $children = []): string
    {
        $mergedSettings = $this->mergeSettings($settings);

        // Increment usage count (async to avoid query overhead)
        defer(fn () => $this->definition->incrementUsage());

        return $this->definition->render($content, $mergedSettings);
    }

    /**
     * Get block schema from database definition.
     */
    public function getSchema(): array
    {
        return $this->definition->schema ?? BlockDefinition::getDefaultSchema();
    }

    /**
     * Get CSS classes required by this block.
     */
    public function getCssClasses(): array
    {
        return $this->definition->css_classes ?? [];
    }

    /**
     * Get sample HTML (original source).
     */
    public function getSampleHtml(): ?string
    {
        return $this->definition->sample_html;
    }

    /**
     * Get shop ID this block belongs to.
     */
    public function getShopId(): int
    {
        return $this->definition->shop_id;
    }

    /**
     * Check if this is an active block.
     */
    public function isActive(): bool
    {
        return $this->definition->is_active;
    }

    /**
     * Get render template (for editing).
     */
    public function getRenderTemplate(): string
    {
        return $this->definition->render_template ?? '';
    }

    /**
     * Extract default settings from schema.
     */
    protected function extractDefaultSettings(): array
    {
        $defaults = [];
        $schema = $this->definition->schema ?? [];

        if (isset($schema['settings']) && is_array($schema['settings'])) {
            foreach ($schema['settings'] as $setting) {
                if (isset($setting['name'])) {
                    $defaults[$setting['name']] = $setting['default'] ?? null;
                }
            }
        }

        return $defaults;
    }

    /**
     * Override toArray to include additional info.
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'definitionId' => $this->definition->id,
            'shopId' => $this->definition->shop_id,
            'cssClasses' => $this->getCssClasses(),
            'usageCount' => $this->definition->usage_count,
            'isDynamic' => true,
        ]);
    }

    /**
     * Create DynamicBlock from BlockDefinition ID.
     */
    public static function fromDefinitionId(int $definitionId): ?static
    {
        $definition = BlockDefinition::find($definitionId);

        if (!$definition || !$definition->is_active) {
            return null;
        }

        return new static($definition);
    }

    /**
     * Get all active DynamicBlocks for a shop.
     */
    public static function forShop(int $shopId): array
    {
        $definitions = BlockDefinition::forShop($shopId)
            ->active()
            ->ordered()
            ->get();

        return $definitions->map(fn ($def) => new static($def))->all();
    }
}
