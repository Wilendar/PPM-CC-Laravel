<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin\VisualEditor;

use App\Models\DescriptionTemplate;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\TemplateCategoryService;
use App\Services\VisualEditor\TemplateVariableService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Template Manager Component.
 *
 * Admin panel for managing visual description templates.
 * Supports CRUD operations, filtering, preview, and import/export.
 */
class TemplateManager extends Component
{
    use WithPagination;

    // =====================
    // PUBLIC PROPERTIES
    // =====================

    /** @var string Search query for filtering templates */
    public string $search = '';

    /** @var int|null Filter by shop ID (null = all shops) */
    public ?int $shopFilter = null;

    /** @var string|null Filter by category */
    public ?string $categoryFilter = null;

    /** @var int|null Currently selected template for operations */
    public ?int $selectedTemplateId = null;

    // Modal states
    public bool $showCreateModal = false;
    public bool $showPreviewModal = false;
    public bool $showImportModal = false;

    // Create/Edit form data
    public string $formName = '';
    public string $formDescription = '';
    public ?int $formShopId = null;
    public string $formCategory = 'other';

    // Import data
    public string $importJson = '';

    // =====================
    // LISTENERS
    // =====================

    protected $listeners = [
        'template-created' => '$refresh',
        'template-deleted' => '$refresh',
        'refresh' => '$refresh',
    ];

    // =====================
    // LIFECYCLE
    // =====================

    public function mount(): void
    {
        // Initialize with first available category
        $categoryService = app(TemplateCategoryService::class);
        $this->formCategory = array_key_first($categoryService->getCategories()) ?? 'other';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShopFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    // =====================
    // COMPUTED PROPERTIES
    // =====================

    /**
     * Get filtered templates with pagination.
     */
    #[Computed]
    public function filteredTemplates(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = DescriptionTemplate::query()
            ->with(['shop:id,name', 'creator:id,name']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Shop filter
        if ($this->shopFilter !== null) {
            if ($this->shopFilter === 0) {
                // Global templates only
                $query->whereNull('shop_id');
            } else {
                // Specific shop (includes global)
                $query->forShop($this->shopFilter);
            }
        }

        // Category filter
        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        return $query->orderByDesc('updated_at')->paginate(12);
    }

    /**
     * Get template categories for filtering.
     */
    #[Computed]
    public function templateCategories(): array
    {
        $service = app(TemplateCategoryService::class);
        return $service->getCategories();
    }

    /**
     * Get usage statistics for templates.
     */
    #[Computed]
    public function usageStats(): array
    {
        return [
            'total' => DescriptionTemplate::count(),
            'global' => DescriptionTemplate::global()->count(),
            'shop_specific' => DescriptionTemplate::whereNotNull('shop_id')->count(),
            'most_used' => DescriptionTemplate::withCount('productDescriptions')
                ->orderByDesc('product_descriptions_count')
                ->first()?->name ?? '-',
        ];
    }

    /**
     * Get available shops for selection.
     */
    #[Computed]
    public function shops(): Collection
    {
        return PrestaShopShop::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get selected template for preview.
     */
    #[Computed]
    public function selectedTemplate(): ?DescriptionTemplate
    {
        if (!$this->selectedTemplateId) {
            return null;
        }

        return DescriptionTemplate::with(['shop', 'creator'])->find($this->selectedTemplateId);
    }

    // =====================
    // TEMPLATE CRUD
    // =====================

    /**
     * Open create modal.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    /**
     * Create new template.
     */
    public function createTemplate(): void
    {
        $this->validate([
            'formName' => 'required|string|min:3|max:100',
            'formDescription' => 'nullable|string|max:500',
            'formShopId' => 'nullable|exists:prestashop_shops,id',
            'formCategory' => 'required|string|max:50',
        ], [
            'formName.required' => 'Nazwa szablonu jest wymagana',
            'formName.min' => 'Nazwa musi miec minimum 3 znaki',
            'formName.max' => 'Nazwa moze miec maksymalnie 100 znakow',
        ]);

        $template = DescriptionTemplate::create([
            'name' => $this->formName,
            'description' => $this->formDescription ?: null,
            'shop_id' => $this->formShopId,
            'category' => $this->formCategory,
            'blocks_json' => [],
            'is_default' => false,
            'created_by' => auth()->id(),
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', type: 'success', message: "Utworzono szablon: {$template->name}");
        $this->dispatch('template-created', templateId: $template->id);
    }

    /**
     * Duplicate existing template.
     */
    public function duplicateTemplate(int $id): void
    {
        $template = DescriptionTemplate::find($id);

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Szablon nie istnieje');
            return;
        }

        $newName = $template->name . ' (kopia)';
        $duplicate = $template->duplicate($newName);

        $this->dispatch('notify', type: 'success', message: "Utworzono kopie: {$duplicate->name}");
    }

    /**
     * Delete template.
     */
    public function deleteTemplate(int $id): void
    {
        $template = DescriptionTemplate::find($id);

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Szablon nie istnieje');
            return;
        }

        // Check permissions
        if (!$this->canDeleteTemplate($template)) {
            $this->dispatch('notify', type: 'error', message: 'Brak uprawnien do usuniecia szablonu');
            return;
        }

        // Check if template is in use
        $usageCount = $template->getUsageCount();
        if ($usageCount > 0) {
            $this->dispatch('notify', type: 'warning', message: "Szablon jest uzywany w {$usageCount} opisach. Usun najpierw przypisania.");
            return;
        }

        $name = $template->name;
        $template->delete();

        $this->selectedTemplateId = null;
        $this->dispatch('notify', type: 'success', message: "Usunieto szablon: {$name}");
        $this->dispatch('template-deleted');
    }

    // =====================
    // PREVIEW
    // =====================

    /**
     * Open preview modal for template.
     */
    public function previewTemplate(int $id): void
    {
        $this->selectedTemplateId = $id;
        $this->showPreviewModal = true;
    }

    /**
     * Close preview modal.
     */
    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->selectedTemplateId = null;
    }

    // =====================
    // IMPORT / EXPORT
    // =====================

    /**
     * Export template as JSON.
     */
    public function exportTemplate(int $id): void
    {
        $template = DescriptionTemplate::find($id);

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Szablon nie istnieje');
            return;
        }

        $exportData = $template->export();
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->dispatch('download-json', filename: "template-{$template->id}.json", content: $json);
        $this->dispatch('notify', type: 'success', message: 'Szablon wyeksportowany');
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
     * Import template from JSON.
     */
    public function importTemplate(): void
    {
        $this->validate([
            'importJson' => 'required|string',
        ], [
            'importJson.required' => 'Wklej dane JSON szablonu',
        ]);

        $data = json_decode($this->importJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->dispatch('notify', type: 'error', message: 'Nieprawidlowy format JSON');
            return;
        }

        if (!isset($data['name']) || !isset($data['blocks'])) {
            $this->dispatch('notify', type: 'error', message: 'Brakujace wymagane pola (name, blocks)');
            return;
        }

        try {
            $template = DescriptionTemplate::import($data, $this->formShopId, auth()->id());
            $this->closeImportModal();
            $this->dispatch('notify', type: 'success', message: "Zaimportowano szablon: {$template->name}");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Blad importu: ' . $e->getMessage());
        }
    }

    // =====================
    // HELPERS
    // =====================

    /**
     * Check if user can delete template.
     */
    private function canDeleteTemplate(DescriptionTemplate $template): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Owner can delete
        if ($template->created_by === $user->id) {
            return true;
        }

        // Admin can delete any
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->formName = '';
        $this->formDescription = '';
        $this->formShopId = null;
        $this->formCategory = 'other';
    }

    /**
     * Get category label for display.
     */
    public function getCategoryLabel(string $key): string
    {
        $service = app(TemplateCategoryService::class);
        return $service->getCategoryLabel($key);
    }

    /**
     * Get category icon.
     */
    public function getCategoryIcon(string $key): string
    {
        $service = app(TemplateCategoryService::class);
        return $service->getCategoryIcon($key);
    }

    // =====================
    // RENDER
    // =====================

    public function render(): View
    {
        return view('livewire.admin.visual-editor.template-manager')
            ->layout('layouts.admin');
    }
}
