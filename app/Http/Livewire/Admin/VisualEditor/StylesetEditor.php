<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin\VisualEditor;

use App\Models\PrestaShopShop;
use App\Models\ShopStyleset;
use App\Services\VisualEditor\Styleset\StylesetCompiler;
use App\Services\VisualEditor\Styleset\StylesetFactory;
use App\Services\VisualEditor\Styleset\StylesetValidator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Styleset Editor Component.
 *
 * Admin panel for editing shop-specific CSS stylesets.
 * Allows modifying CSS variables, custom CSS, and previewing changes.
 */
class StylesetEditor extends Component
{
    // Selected shop and styleset
    public ?int $selectedShopId = null;
    public ?int $stylesetId = null;

    // Styleset data
    public string $stylesetName = '';
    public string $cssNamespace = 'pd';
    public array $variables = [];
    public string $customCss = '';

    // UI state
    public bool $showPreview = true;
    public bool $showCustomCss = false;
    public string $activeGroup = 'Kolory';
    public array $validationErrors = [];
    public array $validationWarnings = [];
    public bool $isDirty = false;

    // Import/Export state
    public bool $showImportModal = false;
    public string $importJson = '';

    // Editor groups structure
    public array $editorGroups = [];

    protected $listeners = [
        'shop-changed' => 'loadShopStyleset',
        'refresh' => '$refresh',
    ];

    public function mount(?int $shopId = null): void
    {
        if ($shopId) {
            $this->selectedShopId = $shopId;
            $this->loadShopStyleset($shopId);
        } else {
            $this->loadDefaultEditorGroups();
        }
    }

    public function loadShopStyleset(int $shopId): void
    {
        $this->selectedShopId = $shopId;

        $styleset = ShopStyleset::forShop($shopId)->active()->first();

        if ($styleset) {
            $this->stylesetId = $styleset->id;
            $this->stylesetName = $styleset->name;
            $this->cssNamespace = $styleset->css_namespace ?? 'pd';
            $this->variables = $styleset->variables_json ?? [];
            $this->customCss = $styleset->css_content ?? '';
        } else {
            // Load defaults from factory
            $factory = app(StylesetFactory::class);
            $definition = $factory->getForShop($shopId);

            $this->stylesetId = null;
            $this->stylesetName = $definition->getName();
            $this->cssNamespace = $definition->getNamespace();
            $this->variables = $definition->getDefaultVariables();
            $this->customCss = '';
            $this->editorGroups = $definition->getEditorGroups();
        }

        $this->loadEditorGroupsForShop();
        $this->isDirty = false;
        $this->validationErrors = [];
        $this->validationWarnings = [];
    }

    public function updateVariable(string $name, string $value): void
    {
        $this->variables[$name] = $value;
        $this->isDirty = true;

        // Validate the variable
        $validator = app(StylesetValidator::class);
        $result = $validator->validateVariable($name, $value);

        if (!$result['valid']) {
            $this->validationErrors[$name] = $result['error'];
        } else {
            unset($this->validationErrors[$name]);
        }
    }

    public function updateCustomCss(string $css): void
    {
        $this->customCss = $css;
        $this->isDirty = true;

        // Validate CSS
        $validator = app(StylesetValidator::class);
        $result = $validator->validate($css);

        $this->validationErrors = array_merge(
            array_filter($this->validationErrors, fn($k) => !str_starts_with($k, 'css_'), ARRAY_FILTER_USE_KEY),
            array_combine(
                array_map(fn($i) => "css_{$i}", array_keys($result['errors'])),
                $result['errors']
            )
        );
        $this->validationWarnings = $result['warnings'];
    }

    public function save(): void
    {
        if (!$this->selectedShopId) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz sklep');
            return;
        }

        if (!empty($this->validationErrors)) {
            $this->dispatch('notify', type: 'error', message: 'Popraw bledy walidacji');
            return;
        }

        $data = [
            'shop_id' => $this->selectedShopId,
            'name' => $this->stylesetName,
            'css_namespace' => $this->cssNamespace,
            'variables_json' => $this->variables,
            'css_content' => $this->customCss,
            'is_active' => true,
        ];

        if ($this->stylesetId) {
            $styleset = ShopStyleset::find($this->stylesetId);
            $styleset->update($data);
        } else {
            $styleset = ShopStyleset::create($data);
            $this->stylesetId = $styleset->id;
        }

        // Clear cache
        $compiler = app(StylesetCompiler::class);
        $compiler->clearCache($this->selectedShopId);

        $this->isDirty = false;
        $this->dispatch('notify', type: 'success', message: 'Styleset zapisany');
    }

    public function resetToDefaults(): void
    {
        if (!$this->selectedShopId) {
            return;
        }

        $factory = app(StylesetFactory::class);
        $definition = $factory->getForShop($this->selectedShopId);

        $this->variables = $definition->getDefaultVariables();
        $this->cssNamespace = $definition->getNamespace();
        $this->customCss = '';
        $this->isDirty = true;
        $this->validationErrors = [];

        $this->dispatch('notify', type: 'info', message: 'Przywrocono wartosci domyslne');
    }

    public function getPreviewCssProperty(): string
    {
        $compiler = app(StylesetCompiler::class);

        // Create temporary styleset for preview
        $tempStyleset = new ShopStyleset([
            'name' => $this->stylesetName,
            'css_namespace' => $this->cssNamespace,
            'variables_json' => $this->variables,
            'css_content' => $this->customCss,
        ]);

        return $compiler->compile($tempStyleset, ['minify' => false, 'include_base' => true]);
    }

    public function getShopsProperty(): \Illuminate\Support\Collection
    {
        return PrestaShopShop::orderBy('name')->get();
    }

    public function setActiveGroup(string $group): void
    {
        $this->activeGroup = $group;
    }

    // =====================
    // IMPORT / EXPORT
    // =====================

    /**
     * Export styleset as JSON.
     */
    public function exportStyleset(): void
    {
        if (!$this->selectedShopId) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz sklep');
            return;
        }

        $exportData = [
            'name' => $this->stylesetName,
            'css_namespace' => $this->cssNamespace,
            'variables' => $this->variables,
            'custom_css' => $this->customCss,
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
        ];

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = "styleset-{$this->cssNamespace}-" . now()->format('Y-m-d') . ".json";

        $this->dispatch('download-json', filename: $filename, content: $json);
        $this->dispatch('notify', type: 'success', message: 'Styleset wyeksportowany');
    }

    /**
     * Open import modal.
     */
    public function openImportModal(): void
    {
        $this->importJson = '';
        $this->showImportModal = true;
    }

    /**
     * Close import modal.
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importJson = '';
    }

    /**
     * Import styleset from JSON.
     */
    public function importStyleset(): void
    {
        if (!$this->selectedShopId) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz sklep');
            return;
        }

        if (empty($this->importJson)) {
            $this->dispatch('notify', type: 'error', message: 'Wklej dane JSON');
            return;
        }

        $data = json_decode($this->importJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->dispatch('notify', type: 'error', message: 'Nieprawidlowy format JSON');
            return;
        }

        // Validate required fields
        if (!isset($data['variables']) || !is_array($data['variables'])) {
            $this->dispatch('notify', type: 'error', message: 'Brakujace pole "variables" w JSON');
            return;
        }

        // Apply imported data
        if (isset($data['name'])) {
            $this->stylesetName = $data['name'];
        }
        if (isset($data['css_namespace'])) {
            $this->cssNamespace = $data['css_namespace'];
        }
        $this->variables = $data['variables'];
        if (isset($data['custom_css'])) {
            $this->customCss = $data['custom_css'];
        }

        $this->isDirty = true;
        $this->closeImportModal();
        $this->dispatch('notify', type: 'success', message: 'Styleset zaimportowany - zapisz zmiany');
    }

    public function render(): View
    {
        return view('livewire.admin.visual-editor.styleset-editor')
            ->layout('layouts.admin');
    }

    private function loadEditorGroupsForShop(): void
    {
        if (!$this->selectedShopId) {
            $this->loadDefaultEditorGroups();
            return;
        }

        $factory = app(StylesetFactory::class);
        $definition = $factory->getForShop($this->selectedShopId);
        $this->editorGroups = $definition->getEditorGroups();
    }

    private function loadDefaultEditorGroups(): void
    {
        $this->editorGroups = [
            'Kolory' => [
                ['name' => 'primary-color', 'label' => 'Kolor podstawowy', 'type' => 'color'],
                ['name' => 'secondary-color', 'label' => 'Kolor dodatkowy', 'type' => 'color'],
                ['name' => 'accent-color', 'label' => 'Kolor akcentu', 'type' => 'color'],
                ['name' => 'text-color', 'label' => 'Kolor tekstu', 'type' => 'color'],
                ['name' => 'background-color', 'label' => 'Kolor tla', 'type' => 'color'],
            ],
            'Typografia' => [
                ['name' => 'font-family', 'label' => 'Czcionka glowna', 'type' => 'font'],
                ['name' => 'heading-font', 'label' => 'Czcionka naglowkow', 'type' => 'font'],
                ['name' => 'font-size-base', 'label' => 'Rozmiar bazowy', 'type' => 'size'],
                ['name' => 'line-height', 'label' => 'Wysokosc linii', 'type' => 'number'],
            ],
            'Odstepy' => [
                ['name' => 'spacing-unit', 'label' => 'Jednostka odstepow', 'type' => 'size'],
                ['name' => 'spacing-sm', 'label' => 'Odstep maly', 'type' => 'size'],
                ['name' => 'spacing-md', 'label' => 'Odstep sredni', 'type' => 'size'],
                ['name' => 'spacing-lg', 'label' => 'Odstep duzy', 'type' => 'size'],
            ],
            'Obramowania' => [
                ['name' => 'border-color', 'label' => 'Kolor obramowania', 'type' => 'color'],
                ['name' => 'border-radius', 'label' => 'Zaokraglenie', 'type' => 'size'],
            ],
        ];
    }
}
