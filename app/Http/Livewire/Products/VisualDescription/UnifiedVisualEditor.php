<?php

namespace App\Http\Livewire\Products\VisualDescription;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Models\DescriptionTemplate;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_BlockManagement;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_Preview;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_UndoRedo;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_ElementEditing;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_CssSync;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_PropertyPanel;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_ResponsiveStyles;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_CssClassGeneration;
use App\Http\Livewire\Products\VisualDescription\Traits\UVE_MediaPicker;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;

/**
 * Unified Visual Editor (UVE) - ETAP_07f_P5
 *
 * Polaczenie Visual Editora i Visual Block Buildera w jeden spojny system.
 * Bloki sa domyslnie zamrozone (locked), klik Edit -> odmrozenie i edycja elementow.
 */
class UnifiedVisualEditor extends Component
{
    use WithFileUploads;
    use UVE_BlockManagement;
    use UVE_Preview;
    use UVE_UndoRedo;
    use UVE_ElementEditing {
        UVE_ElementEditing::updateElementContent insteadof UVE_Preview;
        UVE_ElementEditing::updateElementStyle insteadof UVE_Preview;
    }
    use UVE_CssSync;
    use UVE_PropertyPanel;
    use UVE_ResponsiveStyles;
    use UVE_CssClassGeneration;
    use UVE_MediaPicker;

    // =====================
    // CONTEXT PROPERTIES
    // =====================

    /** @var int|null Product ID */
    public ?int $productId = null;

    /** @var int|null Shop ID */
    public ?int $shopId = null;

    /** @var ProductDescription|null Current description */
    public ?ProductDescription $description = null;

    // =====================
    // BLOCKS STATE
    // =====================

    /**
     * Array of blocks in UVE format:
     * [
     *   'id' => 'blk_xxx',
     *   'type' => 'pd-intro',
     *   'locked' => true,
     *   'document' => [...],
     *   'compiled_html' => '<div>...',
     *   'meta' => [...]
     * ]
     */
    public array $blocks = [];

    /** @var int|null Index of currently selected block */
    public ?int $selectedBlockIndex = null;

    /** @var int|null Index of block being edited (unfrozen) */
    public ?int $editingBlockIndex = null;

    // =====================
    // ELEMENT EDITING (when block is unfrozen)
    // =====================

    /** @var string|null ID of selected element within editing block */
    public ?string $selectedElementId = null;

    /** @var array|null Clipboard for copy/paste */
    public ?array $clipboard = null;

    // =====================
    // UI STATE
    // =====================

    /** @var string Current view mode: 'edit' | 'preview' | 'code' */
    public string $viewMode = 'edit';

    /** @var string Preview device: 'desktop' | 'tablet' | 'mobile' */
    public string $previewDevice = 'desktop';

    /** @var bool Whether there are unsaved changes */
    public bool $isDirty = false;

    /** @var bool Whether textarea was edited outside UVE */
    public bool $hasExternalEdits = false;

    /** @var bool Show block palette panel */
    public bool $showBlockPalette = true;

    /** @var bool Show properties panel */
    public bool $showPropertiesPanel = true;

    /** @var bool Show layers panel */
    public bool $showLayersPanel = false;

    /** @var string Active right panel tab: 'properties' | 'layers' */
    public string $activeRightPanel = 'properties';

    // =====================
    // MODALS
    // =====================

    /** @var bool Show import modal */
    public bool $showImportModal = false;

    /** @var string Import source: 'html' | 'prestashop' */
    public string $importSource = 'html';

    /** @var string HTML content for import */
    public string $importHtml = '';

    /** @var string Import mode: 'replace' | 'append' */
    public string $importMode = 'replace';

    /** @var bool Show template browser modal */
    public bool $showTemplateModal = false;

    /** @var string Template modal mode: 'load' | 'save' */
    public string $templateModalMode = 'load';

    // =====================
    // LIFECYCLE
    // =====================

    /**
     * Mount component with product and shop context
     */
    public function mount($product = null, $shop = null): void
    {
        // Resolve product
        if ($product instanceof Product) {
            $this->productId = $product->id;
        } elseif (is_numeric($product)) {
            $this->productId = (int) $product;
        }

        // Resolve shop
        if ($shop instanceof PrestaShopShop) {
            $this->shopId = $shop->id;
        } elseif (is_numeric($shop)) {
            $this->shopId = (int) $shop;
        }

        // Load existing description
        $this->loadDescription();

        // Detect if textarea was edited outside UVE
        $this->detectExternalEdits();

        // Initialize history
        $this->initHistory();
    }

    /**
     * Load existing product description
     */
    protected function loadDescription(): void
    {
        if (!$this->productId || !$this->shopId) {
            return;
        }

        $this->description = ProductDescription::where('product_id', $this->productId)
            ->where('shop_id', $this->shopId)
            ->first();

        if ($this->description && !empty($this->description->blocks)) {
            // Use accessor which auto-migrates legacy format
            $this->blocks = $this->description->blocks;

            // CSS-FIRST ARCHITECTURE (ETAP_07h): Load CSS rules from description
            if (method_exists($this, 'loadCssRulesFromDescription')) {
                $this->loadCssRulesFromDescription();
            }
        } else {
            // No blocks - try to import from Product.long_description
            $this->blocks = $this->tryImportFromProductDescription();
        }

        // CRITICAL: Recompile HTML to apply current renderStyles() logic
        // This ensures default CSS values (like text-decoration: none) are not hardcoded
        if (!empty($this->blocks)) {
            $this->compileAllBlocksHtml();
        }
    }

    /**
     * Detect if textarea (ProductShopData) was edited outside UVE
     */
    protected function detectExternalEdits(): void
    {
        $shopData = ProductShopData::where('product_id', $this->productId)
            ->where('shop_id', $this->shopId)
            ->first();

        if (!$shopData || !$this->description) {
            return;
        }

        $targetField = $this->description->target_field ?? 'description';
        $textareaContent = match ($targetField) {
            'description', 'both' => $shopData->long_description,
            'description_short' => $shopData->short_description,
            default => $shopData->long_description,
        };

        $uveContent = $this->description->rendered_html;

        if ($textareaContent && $uveContent && trim($textareaContent) !== trim($uveContent)) {
            $this->hasExternalEdits = true;
            $this->dispatch('notify', type: 'warning',
                message: 'Opis zostal zmodyfikowany poza edytorem wizualnym. Zapisanie nadpisze te zmiany wersja z edytora.');
        }
    }

    /**
     * Try to import HTML from ProductShopData.long_description (per-shop) when no blocks exist.
     * Falls back to Product.long_description (global) if no shop-specific description.
     */
    protected function tryImportFromProductDescription(): array
    {
        $htmlContent = null;
        $importSource = null;

        // First try to get per-shop description from ProductShopData
        if ($this->shopId) {
            $shopData = ProductShopData::where('product_id', $this->productId)
                ->where('shop_id', $this->shopId)
                ->first();

            if ($shopData && !empty($shopData->long_description)) {
                $htmlContent = $shopData->long_description;
                $importSource = "product_shop_data.long_description (shop_id: {$this->shopId})";
            }
        }

        // Fallback to global Product.long_description if no shop-specific description
        if (empty($htmlContent)) {
            $product = Product::find($this->productId);
            if ($product && !empty($product->long_description)) {
                $htmlContent = $product->long_description;
                $importSource = 'product.long_description (global)';
            }
        }

        if (empty($htmlContent)) {
            return [];
        }

        // Create a raw-html block with the description
        $importedBlock = [
            'id' => 'imported_' . uniqid(),
            'type' => 'raw-html',
            'content' => [
                'html' => $htmlContent,
            ],
            'settings' => [
                'wrapper_class' => '',
                'wrapper_id' => '',
                'sanitize' => false, // Trust product HTML
                'custom_css' => '',
                'custom_js' => '',
            ],
            'meta' => [
                'imported_from' => $importSource,
                'imported_at' => now()->toIso8601String(),
                'source_length' => strlen($htmlContent),
            ],
            'locked' => true,
            'compiled_html' => $htmlContent,
        ];

        // Dispatch notification about auto-import
        $this->dispatch('notify', type: 'info', message: 'Zaimportowano istniejacy opis produktu');

        return [$importedBlock];
    }

    /**
     * Save description to database
     */
    public function save(): void
    {
        if (!$this->productId || !$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak kontekstu produktu lub sklepu');
            return;
        }

        try {
            // Merge elementStylesCache to blocks before save
            $this->mergeElementStylesCacheToBlocks();

            // Freeze any editing block before save
            if ($this->editingBlockIndex !== null) {
                $this->freezeBlock($this->editingBlockIndex, save: true);
            }

            // Compile all blocks HTML
            $this->compileAllBlocksHtml();

            // Determine CSS mode before saving
            $cssMode = method_exists($this, 'determineCssMode')
                ? $this->determineCssMode()
                : 'inline_style_block';

            // Create or update description
            $this->description = ProductDescription::updateOrCreate(
                [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                ],
                [
                    'blocks_v2' => $this->blocks,
                    'format_version' => '2.0',
                    'rendered_html' => $this->generateRenderedHtml(),
                    'last_rendered_at' => now(),
                    // CSS-FIRST ARCHITECTURE (ETAP_07h)
                    'css_rules' => $this->cssRules ?? [],
                    'css_class_map' => $this->cssClassMap ?? [],
                    'css_mode' => $cssMode,
                    // CRITICAL: Enable sync to PrestaShop
                    'sync_to_prestashop' => true,
                    'target_field' => 'description', // Send to long description field
                ]
            );

            // AUTO-SYNC: Write rendered HTML to ProductShopData (single source of truth)
            $renderedHtml = $this->description->rendered_html;
            $shopData = ProductShopData::firstOrNew([
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ]);

            $targetField = $this->description->target_field ?? 'description';

            if (in_array($targetField, ['description', 'both'])) {
                $shopData->long_description = $renderedHtml;
            }
            if (in_array($targetField, ['description_short', 'both'])) {
                $shortHtml = strip_tags($renderedHtml);
                $shopData->short_description = \Str::limit($shortHtml, 800, '');
            }

            $shopData->save();

            Log::info('[UVE] Auto-synced rendered HTML to ProductShopData', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'target_field' => $targetField,
                'html_length' => strlen($renderedHtml),
            ]);

            // Reset CSS dirty flag
            $this->cssDirty = false;

            $this->isDirty = false;
            $this->dispatch('notify', type: 'success', message: 'Opis zapisany');
            $this->dispatch('description-saved');

            // Trigger CSS sync if enabled (from UVE_CssSync trait)
            $this->afterSaveCssSync();

            // Dispatch sync to PrestaShop
            $this->syncToPrestaShop();

        } catch (\Exception $e) {
            Log::error('UVE save failed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Sync description to PrestaShop after save.
     */
    protected function syncToPrestaShop(): void
    {
        try {
            $product = Product::find($this->productId);
            $shop = PrestaShopShop::find($this->shopId);

            if (!$product || !$shop) {
                Log::warning('UVE syncToPrestaShop: Missing product or shop', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                ]);
                return;
            }

            // Check if product is connected to this shop
            $shopData = ProductShopData::where('product_id', $this->productId)
                ->where('shop_id', $this->shopId)
                ->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::info('UVE syncToPrestaShop: Product not connected to shop, skipping sync', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                ]);
                $this->dispatch('notify', type: 'info', message: 'Opis zapisany lokalnie (produkt nie zsynchronizowany ze sklepem)');
                return;
            }

            // Dispatch sync job
            SyncProductToPrestaShop::dispatch($product, $shop, auth()->id());

            Log::info('UVE syncToPrestaShop: Job dispatched', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'prestashop_product_id' => $shopData->prestashop_product_id,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Synchronizacja opisu do PrestaShop uruchomiona');

        } catch (\Exception $e) {
            Log::error('UVE syncToPrestaShop failed', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', type: 'warning', message: 'Opis zapisany, ale sync do PrestaShop nie powiodl sie');
        }
    }

    /**
     * Revert all unsaved changes - reload description from database
     */
    public function revertChanges(): void
    {
        // Clear any editing state
        $this->editingBlockIndex = null;
        $this->selectedBlockIndex = null;
        $this->selectedElementId = null;

        // Clear element styles cache
        $this->elementStylesCache = [];

        // Reload description from database
        $this->loadDescription();

        // Clear undo/redo history
        $this->initHistory();

        // Mark as clean
        $this->isDirty = false;

        // Notify user
        $this->dispatch('notify', type: 'info', message: 'Zmiany zostaly cofniete');

        // Refresh iframe
        $this->dispatch('refreshPreview');
    }

    // =====================
    // VIEW MODE
    // =====================

    /**
     * Set view mode
     */
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['edit', 'preview', 'code'])) {
            $this->viewMode = $mode;
        }
    }

    /**
     * Toggle preview mode
     */
    public function togglePreview(): void
    {
        $this->viewMode = $this->viewMode === 'preview' ? 'edit' : 'preview';
    }

    // =====================
    // PANEL TOGGLES
    // =====================

    public function toggleBlockPalette(): void
    {
        $this->showBlockPalette = !$this->showBlockPalette;
    }

    public function togglePropertiesPanel(): void
    {
        $this->showPropertiesPanel = !$this->showPropertiesPanel;
    }

    public function toggleLayersPanel(): void
    {
        $this->showLayersPanel = !$this->showLayersPanel;
    }

    public function setActiveRightPanel(string $panel): void
    {
        if (in_array($panel, ['properties', 'layers'])) {
            $this->activeRightPanel = $panel;
        }
    }

    // =====================
    // IMPORT MODAL
    // =====================

    public function openImportModal(string $source = 'html'): void
    {
        $this->importSource = $source;
        $this->importHtml = '';
        $this->importMode = 'replace';
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importHtml = '';
    }

    // =====================
    // COMPUTED PROPERTIES
    // =====================

    #[Computed]
    public function product(): ?Product
    {
        return $this->productId ? Product::find($this->productId) : null;
    }

    #[Computed]
    public function shop(): ?PrestaShopShop
    {
        return $this->shopId ? PrestaShopShop::find($this->shopId) : null;
    }

    /**
     * Get all available shops for this product (multi-store support)
     *
     * @return array Array of shops with id, name, has_description
     */
    #[Computed]
    public function availableShops(): array
    {
        if (!$this->productId) {
            return [];
        }

        $product = Product::with(['shopData.shop'])->find($this->productId);
        if (!$product) {
            return [];
        }

        return $product->shopData
            ->filter(fn($sd) => $sd->shop !== null)
            ->map(function ($shopData) {
                $hasDescription = ProductDescription::where('product_id', $this->productId)
                    ->where('shop_id', $shopData->shop_id)
                    ->whereNotNull('blocks_v2')
                    ->exists();

                return [
                    'id' => $shopData->shop_id,
                    'name' => $shopData->shop->name,
                    'is_active' => $shopData->shop_id === $this->shopId,
                    'has_description' => $hasDescription,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Switch to different shop's description
     *
     * Redirects to new URL with different shop ID to ensure proper routing.
     *
     * @param int $shopId
     */
    public function switchShop(int $shopId): void
    {
        if ($shopId === $this->shopId) {
            return;
        }

        // Check if shop is valid for this product
        $availableShopIds = collect($this->availableShops)->pluck('id')->toArray();
        if (!in_array($shopId, $availableShopIds)) {
            $this->dispatch('notify', type: 'error', message: 'Nieprawidlowy sklep');
            return;
        }

        // Save current if dirty before switching
        if ($this->isDirty) {
            $this->save();
        }

        Log::info('[UVE] Switching shop via redirect', [
            'product_id' => $this->productId,
            'from_shop_id' => $this->shopId,
            'to_shop_id' => $shopId,
        ]);

        // Redirect to new URL with different shop ID
        // This ensures URL reflects the current shop context
        // Using full page redirect (not SPA navigation) to ensure clean state
        $url = "/admin/visual-editor/uve/{$this->productId}/shop/{$shopId}";
        $this->redirect($url);
    }

    #[Computed]
    public function blockCount(): int
    {
        return count($this->blocks);
    }

    #[Computed]
    public function selectedBlock(): ?array
    {
        if ($this->selectedBlockIndex === null) {
            return null;
        }
        return $this->blocks[$this->selectedBlockIndex] ?? null;
    }

    #[Computed]
    public function editingBlock(): ?array
    {
        if ($this->editingBlockIndex === null) {
            return null;
        }
        return $this->blocks[$this->editingBlockIndex] ?? null;
    }

    #[Computed]
    public function isEditingBlock(): bool
    {
        return $this->editingBlockIndex !== null;
    }

    #[Computed]
    public function selectedElement(): ?array
    {
        if (!$this->isEditingBlock || !$this->selectedElementId) {
            return null;
        }

        $block = $this->editingBlock;
        if (!$block || !isset($block['document']['root'])) {
            return null;
        }

        return $this->findElementById($block['document']['root'], $this->selectedElementId);
    }

    /**
     * Recursively find element by ID in document tree
     */
    protected function findElementById(array $element, string $id): ?array
    {
        if (($element['id'] ?? '') === $id) {
            return $element;
        }

        if (!empty($element['children'])) {
            foreach ($element['children'] as $child) {
                $found = $this->findElementById($child, $id);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    // =====================
    // LIVEWIRE EVENTS
    // =====================

    #[On('block-selected')]
    public function handleBlockSelected(int $index): void
    {
        $this->selectBlock($index);
    }

    #[On('blocks-reordered')]
    public function handleBlocksReordered(array $newOrder): void
    {
        $this->reorderBlocks($newOrder);
    }

    #[On('element-selected')]
    public function handleElementSelected(string $elementId): void
    {
        $this->selectElement($elementId);
    }

    /**
     * Add element to currently editing block (used by element palette)
     */
    public function addElementToBlock(string $type): void
    {
        if ($this->editingBlockIndex === null) {
            $this->dispatch('notify', type: 'warning', message: 'Najpierw odmroz blok do edycji');
            return;
        }

        $this->addElement($type);
    }

    /**
     * Delete element (alias for removeElement)
     */
    public function deleteElement(string $elementId): void
    {
        $this->removeElement($elementId);
    }

    #[On('refresh-preview')]
    public function refreshPreview(): void
    {
        // Just trigger re-render
    }

    // =====================
    // IFRAME SYNCHRONIZATION (FAZA 4.5.2)
    // =====================

    /**
     * Parse data-uve-id to extract Livewire block index
     * Format: "block-0", "block-1", etc.
     *
     * NOTE: DOM block indices may not match Livewire block indices!
     * When a single "raw-html" block is rendered, it may contain multiple
     * DOM elements with pd-* classes, each getting their own block-X ID.
     * In this case, ALL DOM blocks belong to Livewire block[0].
     *
     * @param string $uveId The data-uve-id attribute value
     * @return int|null Livewire block index or null if invalid
     */
    protected function parseBlockIndexFromUveId(string $uveId): ?int
    {
        // Match both "block-X" and "block-X-element-Y" formats
        // Examples: "block-0", "block-1", "block-0-heading-0", "block-2-text-1"
        if (!preg_match('/^block-(\d+)/', $uveId, $matches)) {
            return null;
        }

        $domIndex = (int) $matches[1];

        // Direct match - DOM index exists in Livewire blocks
        if (isset($this->blocks[$domIndex])) {
            return $domIndex;
        }

        // Fallback: If only 1 block exists (raw-html case), all DOM blocks belong to it
        // This handles imported HTML that contains multiple pd-* sections
        if (count($this->blocks) === 1 && isset($this->blocks[0])) {
            Log::debug('[UVE] Mapping DOM block to raw-html block[0]', [
                'domIndex' => $domIndex,
                'livewireIndex' => 0,
                'blockType' => $this->blocks[0]['type'] ?? 'unknown',
            ]);
            return 0;
        }

        return null;
    }

    /**
     * Handle element selection from iframe postMessage
     * Called by Alpine.js when user clicks element in iframe
     *
     * @param string $uveId The data-uve-id from iframe
     * @param string|null $elementType Element type (block, heading, text, etc.)
     */
    public function selectElementFromIframe(string $uveId, ?string $elementType = null): void
    {
        $blockIndex = $this->parseBlockIndexFromUveId($uveId);

        if ($blockIndex !== null) {
            // It's a block-level selection
            $this->selectedBlockIndex = $blockIndex;
            $this->selectedElementId = $uveId;

            Log::debug('[UVE] Block selected from iframe', [
                'uveId' => $uveId,
                'blockIndex' => $blockIndex,
                'elementType' => $elementType,
            ]);
        } else {
            // It might be an element within a block (future: "block-0-el-1" format)
            $this->selectedElementId = $uveId;

            Log::debug('[UVE] Element selected from iframe', [
                'uveId' => $uveId,
                'elementType' => $elementType,
            ]);
        }

        $this->isDirty = false; // Selection doesn't dirty the state
    }

    /**
     * Handle content update from iframe inline editing
     * Called when user finishes editing text in iframe
     *
     * @param string $uveId The data-uve-id of edited element (e.g., "block-0" or "block-0-heading-0")
     * @param string $content New HTML content
     */
    public function updateElementContentFromIframe(string $uveId, string $content): void
    {
        $blockIndex = $this->parseBlockIndexFromUveId($uveId);

        if ($blockIndex === null) {
            Log::warning('[UVE] Cannot update content - invalid uveId', ['uveId' => $uveId]);
            return;
        }

        // Check if this is a child element update (contains dash after block-X)
        // e.g., "block-0-heading-0", "block-0-text-1" are child elements
        $isChildElement = preg_match('/^block-\d+-.+/', $uveId);

        if ($isChildElement) {
            // Child element: update only that element within the full HTML
            $this->updateChildElementContent($blockIndex, $uveId, $content);
        } else {
            // Block-level: replace entire compiled_html (original behavior)
            $this->captureState();

            if (isset($this->blocks[$blockIndex]['compiled_html'])) {
                $this->blocks[$blockIndex]['compiled_html'] = $content;
                $this->blocks[$blockIndex]['meta']['last_edited_at'] = now()->toIso8601String();
                $this->blocks[$blockIndex]['meta']['edited_in_iframe'] = true;
            }

            if (($this->blocks[$blockIndex]['type'] ?? '') === 'raw-html') {
                $this->blocks[$blockIndex]['content']['html'] = $content;
            }
        }

        $this->isDirty = true;

        Log::info('[UVE] Block content updated from iframe', [
            'uveId' => $uveId,
            'blockIndex' => $blockIndex,
            'contentLength' => strlen($content),
        ]);

        $this->dispatch('notify', type: 'info', message: 'Zawartosc zaktualizowana');
    }

    /**
     * Handle block deletion request from iframe
     * Called when user requests delete via keyboard or action button
     *
     * @param string $uveId The data-uve-id of block to delete
     */
    public function removeBlockFromIframe(string $uveId): void
    {
        $blockIndex = $this->parseBlockIndexFromUveId($uveId);

        if ($blockIndex === null) {
            Log::warning('[UVE] Cannot delete - invalid uveId', ['uveId' => $uveId]);
            return;
        }

        // Use existing removeBlock method from UVE_BlockManagement trait
        $this->removeBlock($blockIndex);

        Log::info('[UVE] Block removed from iframe', [
            'uveId' => $uveId,
            'blockIndex' => $blockIndex,
        ]);
    }

    /**
     * Update only a specific child element within a block's HTML
     *
     * NOTE: For raw-html blocks, child element editing is not yet supported because
     * the source HTML doesn't have data-uve-id markers (they're only added in preview).
     * Full implementation would require structural element matching.
     *
     * For now, we log the edit but DON'T corrupt the full HTML.
     *
     * @param int $blockIndex The Livewire block index
     * @param string $uveId The data-uve-id of the child element (e.g., "block-0-heading-0")
     * @param string $content The new innerHTML for that element
     */
    protected function updateChildElementContent(int $blockIndex, string $uveId, string $content): void
    {
        // TODO: Implement structural element matching for raw-html blocks
        // For now, log the edit without modifying data to prevent data corruption

        Log::info('[UVE] Child element edit received (NOT SAVED - raw-html limitation)', [
            'uveId' => $uveId,
            'blockIndex' => $blockIndex,
            'contentLength' => strlen($content),
            'contentPreview' => substr($content, 0, 100),
        ]);

        // Dispatch notification to user
        $this->dispatch('notify', type: 'warning', message: 'Edycja elementow wewnetrznych w raw-html nie jest jeszcze wspierana. Uzyj panelu bocznego.');
    }

    /**
     * Handle block duplication request from iframe
     *
     * @param string $uveId The data-uve-id of block to duplicate
     */
    public function duplicateBlockFromIframe(string $uveId): void
    {
        $blockIndex = $this->parseBlockIndexFromUveId($uveId);

        if ($blockIndex === null) {
            Log::warning('[UVE] Cannot duplicate - invalid uveId', ['uveId' => $uveId]);
            return;
        }

        // Use existing duplicateBlock method from UVE_BlockManagement trait
        $this->duplicateBlock($blockIndex);

        Log::info('[UVE] Block duplicated from iframe', [
            'uveId' => $uveId,
            'blockIndex' => $blockIndex,
        ]);
    }

    /**
     * Clear selection from iframe (deselect all)
     */
    public function clearIframeSelection(): void
    {
        $this->selectedBlockIndex = null;
        $this->selectedElementId = null;

        Log::debug('[UVE] Selection cleared from iframe');
    }

    // =====================
    // RENDER
    // =====================

    public function render()
    {
        return view('livewire.products.visual-description.unified-visual-editor');
    }
}
