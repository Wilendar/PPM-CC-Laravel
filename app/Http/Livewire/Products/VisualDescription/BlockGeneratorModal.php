<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription;

use App\Models\BlockDefinition;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\BlockGenerator\BlockAutoGenerator;
use App\Services\VisualEditor\BlockGenerator\BlockAnalysisResult;
use App\Services\VisualEditor\BlockRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * BlockGeneratorModal - creates dedicated blocks from prestashop-section HTML.
 *
 * Allows admins to convert raw HTML sections into reusable, parameterized blocks.
 * Part of ETAP_07f_P3: Visual Description Editor - Dedicated Blocks System.
 */
class BlockGeneratorModal extends Component
{
    // Modal state
    public bool $show = false;
    public int $shopId = 0;
    public string $sourceHtml = '';

    // Block configuration
    public string $blockName = '';
    public string $blockType = '';
    public string $blockIcon = 'heroicons-cube';
    public string $blockDescription = '';

    // Analysis state
    public bool $isAnalyzed = false;
    public array $analysisResult = [];
    public array $analysisErrors = [];

    // Template editing
    public string $renderTemplate = '';
    public array $contentFields = [];
    public array $settingsFields = [];

    // Preview
    public string $previewHtml = '';

    // Step wizard
    public int $currentStep = 1; // 1: Analyze, 2: Configure, 3: Preview, 4: Save

    protected $listeners = [
        'openBlockGeneratorModal' => 'open',
    ];

    /**
     * Open the modal with source HTML.
     */
    public function open(int $shopId, string $sourceHtml): void
    {
        $this->reset();
        $this->shopId = $shopId;
        $this->sourceHtml = $sourceHtml;
        $this->show = true;
        $this->currentStep = 1;

        // Auto-analyze on open
        $this->analyze();
    }

    /**
     * Close the modal.
     */
    public function close(): void
    {
        $this->show = false;
        $this->reset();
    }

    /**
     * Analyze the source HTML.
     */
    public function analyze(): void
    {
        if (empty($this->sourceHtml)) {
            $this->analysisErrors = ['Brak zrodlowego HTML do analizy'];
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                $this->analysisErrors = ['Nie znaleziono sklepu'];
                return;
            }

            $generator = new BlockAutoGenerator();
            $result = $generator->generateFromHtml(
                $this->sourceHtml,
                $shop,
                'Nowy blok dedykowany'
            );

            if (!$result->isValid()) {
                $this->analysisErrors = $result->errors;
                return;
            }

            // Store analysis result
            $this->analysisResult = $result->toArray();
            $this->isAnalyzed = true;
            $this->analysisErrors = [];

            // Pre-fill form fields
            $this->blockName = $result->suggestedName;
            $this->blockType = $result->suggestedType;
            $this->renderTemplate = $result->renderTemplate;
            $this->contentFields = $result->contentFields;
            $this->previewHtml = $this->sourceHtml;

            // Move to configure step
            $this->currentStep = 2;

            Log::info('BlockGeneratorModal: Analysis completed', [
                'shop_id' => $this->shopId,
                'element_count' => $result->structure['elementCount'] ?? 0,
                'content_fields' => count($result->contentFields),
                'css_classes' => count($result->cssClasses),
            ]);

        } catch (\Exception $e) {
            Log::error('BlockGeneratorModal: Analysis failed', [
                'error' => $e->getMessage(),
            ]);
            $this->analysisErrors = ['Blad analizy: ' . $e->getMessage()];
        }
    }

    /**
     * Go to next step.
     */
    public function nextStep(): void
    {
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    /**
     * Go to previous step.
     */
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Update preview based on current template.
     */
    public function updatePreview(): void
    {
        // Generate preview with template
        $this->previewHtml = $this->renderTemplate;
    }

    /**
     * Save the block definition.
     */
    public function save(): void
    {
        // Validate required fields
        if (empty($this->blockName)) {
            $this->dispatch('notify', type: 'error', message: 'Nazwa bloku jest wymagana');
            return;
        }

        try {
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                $this->dispatch('notify', type: 'error', message: 'Nie znaleziono sklepu');
                return;
            }

            // Generate type slug if not set
            if (empty($this->blockType)) {
                $this->blockType = BlockDefinition::generateTypeSlug($this->blockName, $this->shopId);
            }

            // Build schema
            $schema = [
                'content' => [],
                'settings' => [
                    [
                        'name' => 'cssClass',
                        'type' => 'text',
                        'label' => 'Dodatkowe klasy CSS',
                        'default' => '',
                    ],
                ],
            ];

            // Add detected content fields to schema
            foreach ($this->contentFields as $field) {
                $schema['content'][$field['name']] = [
                    'type' => $field['type'],
                    'label' => $field['label'],
                    'default' => $field['currentValue'] ?? '',
                ];
            }

            // Create block definition
            $definition = BlockDefinition::create([
                'shop_id' => $this->shopId,
                'type' => $this->blockType,
                'name' => $this->blockName,
                'category' => 'shop-custom',
                'icon' => $this->blockIcon,
                'description' => $this->blockDescription ?: 'Wygenerowany z prestashop-section',
                'schema' => $schema,
                'render_template' => $this->renderTemplate,
                'css_classes' => $this->analysisResult['cssClasses'] ?? [],
                'sample_html' => $this->sourceHtml,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            // Reload block registry with new definition
            $registry = app(BlockRegistry::class);
            $registry->unloadShopBlocks();
            $registry->loadShopBlocks($this->shopId);

            Log::info('BlockGeneratorModal: Block definition created', [
                'definition_id' => $definition->id,
                'type' => $definition->type,
                'shop_id' => $this->shopId,
            ]);

            $this->dispatch('notify', type: 'success', message: "Blok '{$this->blockName}' zostal utworzony");
            $this->dispatch('block-definition-created', definitionId: $definition->id);
            $this->close();

        } catch (\Exception $e) {
            Log::error('BlockGeneratorModal: Save failed', [
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Get shop name.
     */
    #[Computed]
    public function shop(): ?PrestaShopShop
    {
        return $this->shopId ? PrestaShopShop::find($this->shopId) : null;
    }

    /**
     * Get analysis summary.
     */
    #[Computed]
    public function analysisSummary(): array
    {
        if (empty($this->analysisResult)) {
            return [];
        }

        return [
            'rootTag' => $this->analysisResult['structure']['rootTag'] ?? 'unknown',
            'elementCount' => $this->analysisResult['structure']['elementCount'] ?? 0,
            'depth' => $this->analysisResult['structure']['depth'] ?? 0,
            'cssClassCount' => count($this->analysisResult['cssClasses'] ?? []),
            'contentFieldCount' => count($this->analysisResult['contentFields'] ?? []),
            'repeaterCount' => count($this->analysisResult['repeaters'] ?? []),
            'hasImages' => $this->analysisResult['structure']['hasImages'] ?? false,
            'hasLinks' => $this->analysisResult['structure']['hasLinks'] ?? false,
        ];
    }

    /**
     * Get available icons.
     */
    #[Computed]
    public function availableIcons(): array
    {
        return [
            'heroicons-cube' => 'Szescian',
            'heroicons-square-3-stack-3d' => 'Karty',
            'heroicons-rectangle-group' => 'Banner',
            'heroicons-photo' => 'Zdjecie',
            'heroicons-list-bullet' => 'Lista',
            'heroicons-squares-2x2' => 'Grid',
            'heroicons-check-badge' => 'Badge',
            'heroicons-star' => 'Gwiazdka',
            'heroicons-document-text' => 'Tekst',
            'heroicons-link' => 'Link',
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.products.visual-description.partials.block-generator-modal');
    }
}
