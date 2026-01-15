<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription;

use App\Http\Livewire\Products\VisualDescription\Traits\EditorBlockManagement;
use App\Http\Livewire\Products\VisualDescription\Traits\EditorMediaPicker;
use App\Http\Livewire\Products\VisualDescription\Traits\EditorPreview;
use App\Http\Livewire\Products\VisualDescription\Traits\EditorTemplates;
use App\Http\Livewire\Products\VisualDescription\Traits\EditorUndoRedo;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Services\VisualEditor\BlockRegistry;
use App\Services\VisualEditor\HtmlToBlocksParser;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Visual Description Editor Component.
 *
 * Elementor-style block editor for product descriptions.
 * Supports drag-and-drop, undo/redo, templates, and live preview.
 */
class VisualDescriptionEditor extends Component
{
    use EditorBlockManagement;
    use EditorMediaPicker;
    use EditorUndoRedo;
    use EditorTemplates;
    use EditorPreview;

    // Product context
    public ?int $productId = null;
    public ?int $shopId = null;

    // Block data
    public array $blocks = [];
    public ?int $selectedBlockIndex = null;

    // UI state
    public bool $showBlockPalette = true;
    public bool $isDirty = false;
    public string $viewMode = 'edit'; // 'edit', 'preview', 'code'

    // Code view HTML (for direct editing in HTML tab)
    public string $codeViewHtml = '';

    // Collapsed panels
    public bool $isPaletteCollapsed = false;
    public bool $isPropertiesCollapsed = false;
    public bool $isLayersCollapsed = false;

    // Import modal state
    public bool $showImportModal = false;
    public string $importSource = 'html'; // 'html', 'prestashop'
    public string $importHtml = '';
    public string $importMode = 'append'; // 'replace', 'append'

    // Variable picker modal state
    public bool $showVariableModal = false;

    // Block Generator modal state (ETAP_07f_P3 - Dedicated Blocks)
    public bool $showBlockGeneratorModal = false;
    public ?int $blockGeneratorSourceIndex = null;

    // CSS/JS Editor is handled by separate CssJsEditorModal component (ETAP_07f_P3)

    protected $listeners = [
        'block-selected' => 'selectBlock',
        'blocks-reordered' => 'reorderBlocks',
        'block-property-updated' => 'handlePropertyUpdate',
        'refresh-preview' => '$refresh',
        'confirm-template-load' => 'confirmLoadTemplate',
        'vbb-html-exported' => 'handleVbbHtmlExported',
    ];

    /**
     * Initialize the editor.
     *
     * Accepts either int IDs or model instances from route binding.
     *
     * @param Product|int|null $product Product model or ID
     * @param PrestaShopShop|int|null $shop Shop model or ID
     */
    public function mount(mixed $product = null, mixed $shop = null): void
    {
        // Handle model binding or direct ID
        $this->productId = $product instanceof Product ? $product->id : (int) $product;
        $this->shopId = $shop instanceof PrestaShopShop ? $shop->id : (int) $shop;

        if ($this->productId && $this->shopId) {
            $this->loadExistingDescription();
        }
    }

    /**
     * Load existing description from database.
     *
     * Priority:
     * 1. Load from ProductDescription (visual blocks) if exists AND has blocks
     * 2. Fallback: Parse product's long_description HTML from "Opisy i SEO" tab
     *
     * Note: If ProductDescription exists but blocks_json is empty array [],
     * we fall through to the fallback to re-import from product description.
     * This handles the case where user saved empty editor state.
     */
    protected function loadExistingDescription(): void
    {
        // 1. Try to load from ProductDescription (visual editor blocks)
        $description = ProductDescription::where('product_id', $this->productId)
            ->where('shop_id', $this->shopId)
            ->first();

        // Check if description exists AND has actual blocks (not empty array)
        // Note: blocks_json is cast to array, so "[]" becomes [] which is empty
        if ($description && is_array($description->blocks_json) && count($description->blocks_json) > 0) {
            // Ensure all blocks have unique IDs (required by block-canvas.blade.php)
            $this->blocks = $this->addBlockIds($description->blocks_json);
            $this->clearHistory();
            return;
        }

        // 2. Fallback: Parse existing HTML description from product
        // This runs when: no ProductDescription record OR blocks_json is empty
        $this->loadFromProductDescription();
        $this->clearHistory();
    }

    /**
     * Load and parse product's long_description HTML into blocks.
     *
     * This imports the description from "Opisy i SEO" tab when opening visual editor
     * for the first time (no visual blocks saved yet).
     */
    protected function loadFromProductDescription(): void
    {
        $product = Product::find($this->productId);
        if (!$product) {
            return;
        }

        // Try to get shop-specific description first, then default
        $htmlDescription = $this->getShopDescription($product) ?? $product->long_description;

        if (empty($htmlDescription)) {
            Log::info('[VisualDescriptionEditor] No existing description to import', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ]);
            return;
        }

        try {
            $parser = new HtmlToBlocksParser();
            $importedBlocks = $parser->parse($htmlDescription);

            if (!empty($importedBlocks)) {
                $this->blocks = $this->addBlockIds($importedBlocks);
                $this->isDirty = true; // Mark as dirty so user can save the parsed blocks

                Log::info('[VisualDescriptionEditor] Imported description from "Opisy i SEO"', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                    'blocks_count' => count($this->blocks),
                    'source_length' => strlen($htmlDescription),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('[VisualDescriptionEditor] Failed to parse existing description', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get shop-specific description for the product.
     *
     * @param Product $product
     * @return string|null
     */
    protected function getShopDescription(Product $product): ?string
    {
        // Try ProductShopData first (shop-specific descriptions)
        $shopData = $product->dataForShop($this->shopId)->first();
        if ($shopData && !empty($shopData->long_description)) {
            return $shopData->long_description;
        }

        return null;
    }

    /**
     * Save description to database.
     */
    public function save(): void
    {
        if (!$this->productId || !$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak produktu lub sklepu');
            return;
        }

        $previewHtml = $this->generatePreviewHtml();

        ProductDescription::updateOrCreate(
            [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ],
            [
                'blocks_json' => $this->blocks,
                'compiled_html' => $previewHtml,
                'updated_by' => auth()->id(),
            ]
        );

        $this->isDirty = false;
        $this->dispatch('notify', type: 'success', message: 'Opis zapisany');
        $this->dispatch('description-saved');
    }

    /**
     * Handle property update from child component.
     */
    public function handlePropertyUpdate(int $index, string $key, mixed $value): void
    {
        $this->updateBlockProperty($index, $key, $value);
    }

    /**
     * Get available block types grouped by category.
     *
     * Includes both built-in blocks and shop-specific dynamic blocks.
     */
    public function getBlockPaletteProperty(): array
    {
        $registry = app(BlockRegistry::class);

        // Load dynamic blocks for current shop (ETAP_07f_P3)
        if ($this->shopId) {
            $registry->loadShopBlocks($this->shopId);
        }

        return $registry->groupedByCategory();
    }

    /**
     * Get currently selected shop.
     */
    public function getShopProperty(): ?PrestaShopShop
    {
        if (!$this->shopId) {
            return null;
        }

        return PrestaShopShop::find($this->shopId);
    }

    /**
     * Get product being edited.
     */
    public function getProductProperty(): ?Product
    {
        if (!$this->productId) {
            return null;
        }

        return Product::find($this->productId);
    }

    /**
     * Set view mode.
     */
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['edit', 'preview', 'code'])) {
            // Sync HTML when switching to code view
            if ($mode === 'code') {
                $this->codeViewHtml = $this->generatePreviewHtml();
            }
            $this->viewMode = $mode;
        }
    }

    /**
     * Apply HTML changes from code view.
     * Parses the edited HTML and converts back to blocks.
     */
    public function applyHtmlChanges(): void
    {
        if (empty(trim($this->codeViewHtml))) {
            $this->dispatch('notify', type: 'warning', message: 'Brak kodu HTML do zastosowania');
            return;
        }

        try {
            $parser = new HtmlToBlocksParser();
            $importedBlocks = $parser->parse($this->codeViewHtml);

            if (empty($importedBlocks)) {
                $this->dispatch('notify', type: 'warning', message: 'Nie znaleziono blokow w HTML');
                return;
            }

            // Add unique IDs to imported blocks
            $importedBlocks = $this->addBlockIds($importedBlocks);

            $this->pushUndoState();
            $this->blocks = $importedBlocks;
            $this->selectedBlockIndex = null;
            $this->isDirty = true;

            $count = count($importedBlocks);
            $this->dispatch('notify', type: 'success', message: "Zastosowano zmiany ({$count} blokow)");

        } catch (\Exception $e) {
            Log::error('Apply HTML changes failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad parsowania HTML: ' . $e->getMessage());
        }
    }

    /**
     * Reset code view HTML to current blocks state.
     */
    public function resetHtmlToBlocks(): void
    {
        $this->codeViewHtml = $this->generatePreviewHtml();
        $this->dispatch('notify', type: 'info', message: 'HTML zresetowany do aktualnego stanu blokow');
    }

    /**
     * Toggle palette collapsed state.
     */
    public function togglePalette(): void
    {
        $this->isPaletteCollapsed = !$this->isPaletteCollapsed;
    }

    /**
     * Toggle properties panel collapsed state.
     */
    public function toggleProperties(): void
    {
        $this->isPropertiesCollapsed = !$this->isPropertiesCollapsed;
    }

    /**
     * Toggle layers panel collapsed state.
     */
    public function toggleLayers(): void
    {
        $this->isLayersCollapsed = !$this->isLayersCollapsed;
    }

    /**
     * Get human-readable label for a block.
     *
     * Used by layer panel to display block names.
     *
     * @param int $index Block index
     * @return string Block label
     */
    public function getBlockLabel(int $index): string
    {
        if (!isset($this->blocks[$index])) {
            return 'Nieznany blok';
        }

        $block = $this->blocks[$index];
        $type = $block['type'] ?? 'unknown';
        $data = $block['data'] ?? [];

        // Try to get meaningful label from block data
        $content = $data['content']['text'] ?? $data['content']['html'] ?? $data['text'] ?? $data['html'] ?? null;

        if ($content) {
            // Strip HTML and truncate
            $plainText = strip_tags($content);
            if (strlen($plainText) > 25) {
                return mb_substr($plainText, 0, 22) . '...';
            }
            if (!empty($plainText)) {
                return $plainText;
            }
        }

        // Try heading/title
        $heading = $data['content']['heading'] ?? $data['heading'] ?? $data['title'] ?? null;
        if ($heading) {
            return mb_strlen($heading) > 25 ? mb_substr($heading, 0, 22) . '...' : $heading;
        }

        // Fallback to type name
        $typeLabels = [
            'prestashop-section' => 'Sekcja HTML',
            'hero' => 'Hero Banner',
            'hero-banner' => 'Hero Banner',
            'heading' => 'Naglowek',
            'pd-heading' => 'Naglowek PS',
            'text' => 'Tekst',
            'paragraph' => 'Paragraf',
            'image' => 'Obraz',
            'pd-cover' => 'Zdjecie glowne',
            'columns' => 'Kolumny',
            'pd-cols' => 'Kolumny PS',
            'list' => 'Lista',
            'pd-asset-list' => 'Lista parametrow',
            'separator' => 'Separator',
            'divider' => 'Linia',
            'video' => 'Wideo',
            'button' => 'Przycisk',
            'html' => 'HTML',
        ];

        return $typeLabels[$type] ?? ucfirst($type);
    }

    /**
     * Clear all blocks.
     */
    public function clearBlocks(): void
    {
        if (empty($this->blocks)) {
            return;
        }

        $this->pushUndoState();
        $this->blocks = [];
        $this->selectedBlockIndex = null;
        $this->isDirty = true;

        $this->dispatch('notify', type: 'info', message: 'Wyczyszczono wszystkie bloki');
    }

    // ========================================
    // Import Methods
    // ========================================

    /**
     * Open import modal.
     */
    public function openImportModal(string $source = 'html'): void
    {
        $this->importSource = $source;
        $this->importHtml = '';
        $this->importMode = 'append';
        $this->showImportModal = true;
    }

    /**
     * Close import modal.
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importHtml = '';
    }

    /**
     * Open variable picker modal.
     */
    public function openVariableModal(): void
    {
        $this->showVariableModal = true;
    }

    /**
     * Close variable picker modal.
     */
    public function closeVariableModal(): void
    {
        $this->showVariableModal = false;
    }

    /**
     * Import from pasted HTML.
     */
    public function importFromHtml(): void
    {
        if (empty(trim($this->importHtml))) {
            $this->dispatch('notify', type: 'error', message: 'Wklej kod HTML do zaimportowania');
            return;
        }

        try {
            $parser = new HtmlToBlocksParser();
            $importedBlocks = $parser->parse($this->importHtml);

            if (empty($importedBlocks)) {
                $this->dispatch('notify', type: 'warning', message: 'Nie znaleziono blokow w HTML');
                return;
            }

            // Add unique IDs to imported blocks (required by block-canvas.blade.php)
            $importedBlocks = $this->addBlockIds($importedBlocks);

            $this->pushUndoState();

            if ($this->importMode === 'replace') {
                $this->blocks = $importedBlocks;
            } else {
                $this->blocks = array_merge($this->blocks, $importedBlocks);
            }

            $this->isDirty = true;
            $this->closeImportModal();

            $count = count($importedBlocks);
            $this->dispatch('notify', type: 'success', message: "Zaimportowano {$count} blokow");

        } catch (\Exception $e) {
            Log::error('HTML Import failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad importu: ' . $e->getMessage());
        }
    }

    /**
     * Import description from PrestaShop.
     */
    public function importFromPrestaShop(): void
    {
        if (!$this->productId || !$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak produktu lub sklepu');
            return;
        }

        try {
            $product = Product::find($this->productId);
            $shop = PrestaShopShop::find($this->shopId);

            if (!$product || !$shop) {
                $this->dispatch('notify', type: 'error', message: 'Nie znaleziono produktu lub sklepu');
                return;
            }

            // Get PrestaShop product ID using Product's getPrestashopProductId method
            $psProductId = $product->getPrestashopProductId($shop);

            if (!$psProductId) {
                $this->dispatch('notify', type: 'error', message: 'Produkt nie jest zsynchronizowany z PrestaShop');
                return;
            }

            // Fetch from PrestaShop API
            $client = new PrestaShop8Client($shop);
            $psProductRaw = $client->getProduct($psProductId);

            // Unwrap 'product' key if present (API sometimes wraps response)
            $psProduct = $psProductRaw['product'] ?? $psProductRaw;

            // Extract description from multi-language array
            // PrestaShop returns: ['description' => [['id' => 1, 'value' => 'text PL'], ...]]
            $description = $this->extractMultilangDescription($psProduct, 'description');

            Log::info('importFromPrestaShop API response', [
                'psProductId' => $psProductId,
                'psProduct_exists' => !empty($psProduct),
                'has_product_wrapper' => isset($psProductRaw['product']),
                'description_field_type' => gettype($psProduct['description'] ?? null),
                'description_extracted_length' => strlen($description ?? ''),
                'description_preview' => $description ? substr($description, 0, 200) : 'NONE',
            ]);

            if (!$psProduct || empty($description)) {
                $this->dispatch('notify', type: 'warning', message: 'Brak opisu w PrestaShop');
                return;
            }

            // Parse the description
            $parser = new HtmlToBlocksParser();
            $importedBlocks = $parser->parse($description);

            Log::info('importFromPrestaShop parser result', [
                'blocks_count' => count($importedBlocks),
                'block_types' => array_column($importedBlocks, 'type'),
            ]);

            if (empty($importedBlocks)) {
                $this->dispatch('notify', type: 'warning', message: 'Nie znaleziono blokow w opisie PrestaShop');
                return;
            }

            // Add unique IDs to imported blocks (required by block-canvas.blade.php)
            $importedBlocks = $this->addBlockIds($importedBlocks);

            $this->pushUndoState();

            if ($this->importMode === 'replace') {
                $this->blocks = $importedBlocks;
            } else {
                $this->blocks = array_merge($this->blocks, $importedBlocks);
            }

            $this->isDirty = true;
            $this->closeImportModal();

            $count = count($importedBlocks);
            $this->dispatch('notify', type: 'success', message: "Zaimportowano {$count} blokow z PrestaShop");

        } catch (\Exception $e) {
            Log::error('PrestaShop Import failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad importu z PrestaShop: ' . $e->getMessage());
        }
    }

    /**
     * Execute import based on source.
     */
    public function executeImport(): void
    {
        if ($this->importSource === 'prestashop') {
            $this->importFromPrestaShop();
        } else {
            $this->importFromHtml();
        }
    }

    /**
     * Get block count.
     */
    public function getBlockCountProperty(): int
    {
        return count($this->blocks);
    }

    /**
     * Get CSS namespace for current shop.
     */
    public function getCssNamespaceProperty(): string
    {
        if (!$this->shopId) {
            return 'pd';
        }

        $shop = PrestaShopShop::find($this->shopId);
        if (!$shop) {
            return 'pd';
        }

        // Check shop styleset
        $styleset = $shop->activeStyleset;
        if ($styleset) {
            return $styleset->css_namespace ?? 'pd';
        }

        // Default based on shop type
        $shopName = strtolower($shop->name ?? '');
        if (str_contains($shopName, 'pitgang')) {
            return 'blok';
        }

        return 'pd';
    }

    /**
     * Add unique IDs to blocks that don't have them.
     *
     * @param array $blocks Blocks from parser
     * @return array Blocks with unique IDs
     */
    protected function addBlockIds(array $blocks): array
    {
        return array_map(function ($block) {
            if (!isset($block['id'])) {
                $block['id'] = uniqid('blk_', true);
            }
            return $block;
        }, $blocks);
    }

    /**
     * Extract value from PrestaShop multi-language field.
     *
     * PrestaShop API returns multi-lang fields as:
     * ['description' => [['id' => 1, 'value' => 'text PL'], ['id' => 2, 'value' => 'text EN']]]
     *
     * @param array $productData Product data from API
     * @param string $fieldName Field name to extract
     * @param int $languageId Language ID (default: 1 for Polish)
     * @return string|null
     */
    protected function extractMultilangDescription(array $productData, string $fieldName, int $languageId = 1): ?string
    {
        $field = $productData[$fieldName] ?? null;

        if ($field === null) {
            return null;
        }

        // If it's already a string, return it
        if (is_string($field)) {
            return $field;
        }

        // If it's a multi-language array
        if (is_array($field)) {
            // First try to find the requested language
            foreach ($field as $langData) {
                if (isset($langData['id']) && (int) $langData['id'] === $languageId) {
                    return $langData['value'] ?? null;
                }
            }

            // Fallback: return first available value
            if (!empty($field[0]['value'])) {
                return $field[0]['value'];
            }
        }

        return null;
    }

    // ========================================
    // Block Generator Methods (ETAP_07f_P3)
    // ========================================

    /**
     * Open block generator modal for a prestashop-section block.
     *
     * Allows converting raw HTML sections into reusable dedicated blocks.
     *
     * @param int $index Block index in $blocks array
     */
    public function openBlockGenerator(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            $this->dispatch('notify', type: 'error', message: 'Blok nie istnieje');
            return;
        }

        $block = $this->blocks[$index];

        if ($block['type'] !== 'prestashop-section') {
            $this->dispatch('notify', type: 'warning', message: 'Tylko bloki prestashop-section moga byc konwertowane');
            return;
        }

        if (!$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak wybranego sklepu');
            return;
        }

        $this->blockGeneratorSourceIndex = $index;
        $this->showBlockGeneratorModal = true;

        // Dispatch event to BlockGeneratorModal component with source HTML
        // Block structure may have HTML in 'data.content.html' or 'content.html'
        $sourceHtml = $block['data']['content']['html']
            ?? $block['content']['html']
            ?? '';

        $this->dispatch('openBlockGeneratorModal', shopId: $this->shopId, sourceHtml: $sourceHtml);
    }

    /**
     * Close block generator modal.
     */
    public function closeBlockGenerator(): void
    {
        $this->showBlockGeneratorModal = false;
        $this->blockGeneratorSourceIndex = null;
    }

    /**
     * Open block in Visual Block Builder (VBB) for visual editing.
     *
     * Extracts the HTML content from the block and opens VBB with it.
     * This allows in-place visual editing of any block in the editor.
     *
     * @param int $index Block index in $blocks array
     */
    public function openBlockInVBB(int $index): void
    {
        if (!isset($this->blocks[$index])) {
            $this->dispatch('notify', type: 'error', message: 'Blok nie istnieje');
            return;
        }

        if (!$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak wybranego sklepu');
            return;
        }

        $block = $this->blocks[$index];

        // Extract HTML from block - check various possible locations
        // prestashop-section: data.content.html or content.html
        // Other blocks: rendered via renderBlockPreview()
        $sourceHtml = $block['data']['content']['html']
            ?? $block['content']['html']
            ?? $block['data']['html']
            ?? $this->renderBlockPreview($index);

        // Store the editing block index for updating when VBB saves
        $this->selectedBlockIndex = $index;

        // Dispatch event to open VBB with the block's HTML
        $this->dispatch('openBlockBuilder', shopId: $this->shopId, sourceHtml: $sourceHtml);
    }

    /**
     * Handle HTML exported from Visual Block Builder.
     *
     * Updates the currently selected block with the new HTML from VBB.
     * Called when user clicks "Apply to Editor" in VBB.
     *
     * @param string $html Exported HTML from VBB
     */
    public function handleVbbHtmlExported(string $html): void
    {
        if ($this->selectedBlockIndex === null || !isset($this->blocks[$this->selectedBlockIndex])) {
            $this->dispatch('notify', type: 'warning', message: 'Brak wybranego bloku do aktualizacji');
            return;
        }

        // Push current state to undo stack
        $this->pushUndoState();

        // Update block with new HTML
        // Convert to prestashop-section format which displays raw HTML
        $this->blocks[$this->selectedBlockIndex] = [
            'id' => $this->blocks[$this->selectedBlockIndex]['id'] ?? uniqid('blk_', true),
            'type' => 'prestashop-section',
            'data' => [
                'content' => [
                    'html' => $html,
                ],
            ],
            'content' => [
                'html' => $html,
            ],
        ];

        $this->isDirty = true;

        Log::info('[VisualDescriptionEditor] Block updated from VBB', [
            'block_index' => $this->selectedBlockIndex,
            'html_length' => strlen($html),
        ]);
    }

    /**
     * Get source block HTML for generator.
     */
    public function getBlockGeneratorSourceHtmlProperty(): string
    {
        if ($this->blockGeneratorSourceIndex === null || !isset($this->blocks[$this->blockGeneratorSourceIndex])) {
            return '';
        }

        $block = $this->blocks[$this->blockGeneratorSourceIndex];
        return $block['content']['html'] ?? '';
    }

    // ========================================
    // CSS/JS Editor Methods
    // ========================================

    /**
     * Open CSS/JS editor modal (dispatches event to CssJsEditorModal component).
     *
     * ETAP_07f_P3: Uses new CssJsEditorModal component that shows ALL CSS/JS files
     * from PrestaShop (theme.css, custom.css, modules, etc.)
     */
    public function openCssJsEditor(): void
    {
        if (!$this->shopId) {
            $this->dispatch('notify', type: 'error', message: 'Brak wybranego sklepu');
            return;
        }

        // Dispatch event to open CssJsEditorModal component
        $this->dispatch('openCssJsEditor', shopId: $this->shopId);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.products.visual-description.visual-description-editor')
            ->layout('layouts.admin');
    }
}
