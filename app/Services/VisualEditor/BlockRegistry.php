<?php

namespace App\Services\VisualEditor;

use App\Models\BlockDefinition;
use App\Services\VisualEditor\Blocks\BaseBlock;
use App\Services\VisualEditor\Blocks\DynamicBlock;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Registry for all available Visual Editor blocks.
 *
 * Provides methods to register, retrieve, and discover block types.
 * Registered as singleton in AppServiceProvider.
 */
class BlockRegistry
{
    /**
     * Registered block instances indexed by type.
     *
     * @var array<string, BaseBlock>
     */
    private array $blocks = [];

    /**
     * Block categories with labels.
     */
    private array $categories = [
        'layout' => 'Uklad',
        'content' => 'Tresc',
        'media' => 'Media',
        'interactive' => 'Interaktywne',
        'prestashop' => 'PrestaShop',
        'shop-custom' => 'Dedykowane bloki',
    ];

    /**
     * Currently loaded shop ID for dynamic blocks.
     */
    private ?int $loadedShopId = null;

    /**
     * Register a block instance.
     *
     * @param BaseBlock $block Block instance to register
     * @throws InvalidArgumentException If block type already registered
     */
    public function register(BaseBlock $block): void
    {
        if ($this->has($block->type)) {
            throw new InvalidArgumentException(
                "Block type '{$block->type}' is already registered."
            );
        }

        $this->blocks[$block->type] = $block;
    }

    /**
     * Register multiple blocks at once.
     *
     * @param array<BaseBlock> $blocks
     */
    public function registerMany(array $blocks): void
    {
        foreach ($blocks as $block) {
            $this->register($block);
        }
    }

    /**
     * Get a block by type.
     *
     * @param string $type Block type identifier
     * @return BaseBlock|null
     */
    public function get(string $type): ?BaseBlock
    {
        return $this->blocks[$type] ?? null;
    }

    /**
     * Get a block by type or throw exception.
     *
     * @param string $type Block type identifier
     * @return BaseBlock
     * @throws InvalidArgumentException If block not found
     */
    public function getOrFail(string $type): BaseBlock
    {
        $block = $this->get($type);

        if (!$block) {
            throw new InvalidArgumentException("Block type '{$type}' not found.");
        }

        return $block;
    }

    /**
     * Check if a block type is registered.
     */
    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }

    /**
     * Get all registered blocks.
     *
     * @return array<string, BaseBlock>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Get all blocks as array (for JSON serialization).
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->blocks as $type => $block) {
            $result[$type] = $block->toArray();
        }
        return $result;
    }

    /**
     * Get blocks filtered by category.
     *
     * @param string $category Category identifier
     * @return array<string, BaseBlock>
     */
    public function byCategory(string $category): array
    {
        return array_filter(
            $this->blocks,
            fn(BaseBlock $block) => $block->category === $category
        );
    }

    /**
     * Get all blocks grouped by category.
     *
     * @return array<string, array<string, BaseBlock>>
     */
    public function groupedByCategory(): array
    {
        $grouped = [];

        foreach ($this->categories as $category => $label) {
            $categoryBlocks = $this->byCategory($category);
            if (!empty($categoryBlocks)) {
                $grouped[$category] = [
                    'label' => $label,
                    'blocks' => $categoryBlocks,
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get available categories.
     *
     * @return array<string, string> Category ID => Label
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * Get count of registered blocks.
     */
    public function count(): int
    {
        return count($this->blocks);
    }

    /**
     * Auto-discover and register block classes from the Blocks directory.
     *
     * Scans subdirectories: Layout, Content, Media, Interactive
     */
    public function discoverBlocks(): void
    {
        $basePath = app_path('Services/VisualEditor/Blocks');
        $baseNamespace = 'App\\Services\\VisualEditor\\Blocks';

        $subdirectories = ['Layout', 'Content', 'Media', 'Interactive', 'PrestaShop'];

        foreach ($subdirectories as $subdir) {
            $path = $basePath . DIRECTORY_SEPARATOR . $subdir;

            if (!File::isDirectory($path)) {
                continue;
            }

            $files = File::files($path);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $file->getFilenameWithoutExtension();

                // Skip base/abstract classes
                if (str_starts_with($className, 'Base') || str_starts_with($className, 'Abstract')) {
                    continue;
                }

                $fullClassName = "{$baseNamespace}\\{$subdir}\\{$className}";

                if (!class_exists($fullClassName)) {
                    continue;
                }

                // Verify it's a concrete block class
                $reflection = new \ReflectionClass($fullClassName);
                if ($reflection->isAbstract() || !$reflection->isSubclassOf(BaseBlock::class)) {
                    continue;
                }

                // Register instance
                try {
                    $instance = new $fullClassName();
                    $this->register($instance);
                } catch (\Throwable $e) {
                    // Log but don't fail - allows partial loading
                    \Log::warning("Failed to register block: {$fullClassName}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Validate block data against its schema.
     *
     * @param string $type Block type
     * @param array $data Block data (content + settings)
     * @return array Validation errors (empty if valid)
     */
    public function validateBlockData(string $type, array $data): array
    {
        $block = $this->get($type);

        if (!$block) {
            return ['type' => "Nieznany typ bloku: {$type}"];
        }

        return $block->validate($data);
    }

    /**
     * Create a new block data structure with defaults.
     *
     * @param string $type Block type
     * @return array|null Block data structure or null if type not found
     */
    public function createBlockData(string $type): ?array
    {
        $block = $this->get($type);

        if (!$block) {
            return null;
        }

        $schema = $block->getSchema();

        // Initialize content with empty values
        $content = [];
        if (isset($schema['content'])) {
            foreach ($schema['content'] as $field => $config) {
                $content[$field] = $config['default'] ?? '';
            }
        }

        return [
            'type' => $type,
            'content' => $content,
            'settings' => $block->defaultSettings,
            'children' => [],
        ];
    }

    /**
     * Get block types that support child blocks.
     *
     * @return array<string> Block types with children support
     */
    public function getContainerTypes(): array
    {
        return array_keys(array_filter(
            $this->blocks,
            fn(BaseBlock $block) => $block->supportsChildren
        ));
    }

    // =====================================================
    // DYNAMIC BLOCKS (from database per shop)
    // =====================================================

    /**
     * Load dynamic blocks for a specific shop.
     *
     * Queries BlockDefinition table and registers DynamicBlock instances.
     * Call this when entering shop-specific context (e.g., editing product description).
     *
     * @param int $shopId PrestaShop shop ID
     * @return void
     */
    public function loadShopBlocks(int $shopId): void
    {
        // Skip if already loaded for this shop
        if ($this->loadedShopId === $shopId) {
            return;
        }

        // Unload previous shop blocks first
        if ($this->loadedShopId !== null) {
            $this->unloadShopBlocks();
        }

        $definitions = BlockDefinition::forShop($shopId)
            ->active()
            ->ordered()
            ->get();

        foreach ($definitions as $definition) {
            try {
                $dynamicBlock = new DynamicBlock($definition);

                // Don't override built-in blocks
                if (!$this->has($dynamicBlock->type)) {
                    $this->blocks[$dynamicBlock->type] = $dynamicBlock;
                }
            } catch (\Throwable $e) {
                \Log::warning('BlockRegistry: Failed to load dynamic block', [
                    'definition_id' => $definition->id,
                    'type' => $definition->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->loadedShopId = $shopId;
    }

    /**
     * Unload dynamic blocks from current registry.
     *
     * Removes all DynamicBlock instances, keeping only built-in blocks.
     */
    public function unloadShopBlocks(): void
    {
        $this->blocks = array_filter(
            $this->blocks,
            fn(BaseBlock $block) => !($block instanceof DynamicBlock)
        );

        $this->loadedShopId = null;
    }

    /**
     * Get all blocks available for a specific shop.
     *
     * Returns both built-in blocks and shop-specific dynamic blocks.
     *
     * @param int $shopId PrestaShop shop ID
     * @return array<string, BaseBlock>
     */
    public function getBlocksForShop(int $shopId): array
    {
        $this->loadShopBlocks($shopId);
        return $this->blocks;
    }

    /**
     * Get only dynamic blocks for a shop.
     *
     * @param int $shopId PrestaShop shop ID
     * @return array<string, DynamicBlock>
     */
    public function getDynamicBlocksForShop(int $shopId): array
    {
        $this->loadShopBlocks($shopId);

        return array_filter(
            $this->blocks,
            fn(BaseBlock $block) => $block instanceof DynamicBlock
        );
    }

    /**
     * Get currently loaded shop ID.
     */
    public function getLoadedShopId(): ?int
    {
        return $this->loadedShopId;
    }

    /**
     * Check if a block is dynamic (from database).
     */
    public function isDynamicBlock(string $type): bool
    {
        $block = $this->get($type);
        return $block instanceof DynamicBlock;
    }

    /**
     * Get BlockDefinition model for a block type.
     *
     * @return BlockDefinition|null
     */
    public function getBlockDefinition(string $type): ?BlockDefinition
    {
        $block = $this->get($type);

        if ($block instanceof DynamicBlock) {
            return $block->getDefinition();
        }

        return null;
    }

    /**
     * Reload dynamic blocks (e.g., after creating/editing a definition).
     */
    public function reloadShopBlocks(): void
    {
        if ($this->loadedShopId !== null) {
            $shopId = $this->loadedShopId;
            $this->unloadShopBlocks();
            $this->loadShopBlocks($shopId);
        }
    }
}
