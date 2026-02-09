<?php

namespace App\Http\Livewire\Admin\Features\Tabs;

use App\Jobs\Features\BulkAssignFeaturesJob;
use App\Models\FeatureTemplate;
use App\Models\JobProgress;
use App\Services\JobProgressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * FeatureTemplatesTab - Card Grid Template Management
 *
 * Displays templates as cards with inline Alpine.js preview (expand/collapse)
 * No server-side selection needed - preview handled by Alpine x-data
 *
 * ETAP_07e Features Panel Redesign
 */
class FeatureTemplatesTab extends Component
{
    // ========================================
    // STATE PROPERTIES
    // ========================================

    /**
     * Filter: 'all' | 'predefined' | 'custom'
     */
    public string $filter = 'all';

    // ========================================
    // TEMPLATE EDITOR MODAL
    // ========================================

    public bool $showTemplateModal = false;
    public ?int $editingTemplateId = null;
    public string $templateName = '';
    public array $templateFeatures = [];

    // ========================================
    // BULK ASSIGN MODAL
    // ========================================

    public bool $showBulkAssignModal = false;
    public ?int $bulkAssignTemplateId = null;
    public string $bulkAssignScope = 'all_vehicles';
    public ?int $bulkAssignCategoryId = null;
    public string $bulkAssignAction = 'add_features';
    public int $bulkAssignProductsCount = 0;

    // ========================================
    // JOB PROGRESS
    // ========================================

    public ?int $activeJobProgressId = null;
    public array $activeJobProgress = [];

    // ========================================
    // COMPUTED PROPERTIES
    // ========================================

    /**
     * Get all templates (predefined + custom)
     */
    #[Computed]
    public function templates(): Collection
    {
        $query = FeatureTemplate::query()->active();

        if ($this->filter === 'predefined') {
            $query->predefined();
        } elseif ($this->filter === 'custom') {
            $query->custom();
        }

        return $query->orderBy('is_predefined', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function (FeatureTemplate $template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'is_predefined' => $template->is_predefined,
                    'features' => $template->features ?? [],
                    'features_count' => count($template->features ?? []),
                    'usage_count' => $template->usage_count ?? 0,
                    'icon' => $this->getTemplateIcon($template),
                    'category' => $this->getTemplateCategory($template),
                ];
            });
    }

    /**
     * Get template icon based on name
     */
    private function getTemplateIcon(FeatureTemplate $template): string
    {
        $name = strtolower($template->name);

        if (str_contains($name, 'elektr')) {
            return '⚡';
        }
        if (str_contains($name, 'spalin')) {
            return '⛽';
        }
        if (str_contains($name, 'pit bike')) {
            return '🏍';
        }
        if (str_contains($name, 'quad')) {
            return '🛵';
        }
        if (str_contains($name, 'buggy')) {
            return '🚙';
        }

        return '📋';
    }

    /**
     * Get template category
     */
    private function getTemplateCategory(FeatureTemplate $template): string
    {
        $name = strtolower($template->name);

        if (str_contains($name, 'elektr')) {
            return 'elektryczne';
        }
        if (str_contains($name, 'spalin')) {
            return 'spalinowe';
        }

        return 'uniwersalne';
    }

    // ========================================
    // TEMPLATE CRUD METHODS
    // ========================================

    /**
     * Open modal to create new template
     */
    public function openTemplateModal(): void
    {
        $this->resetTemplateForm();
        $this->showTemplateModal = true;
    }

    /**
     * Open modal to edit existing template
     */
    public function editTemplate(int $templateId): void
    {
        $template = FeatureTemplate::find($templateId);

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Szablon nie zostal znaleziony.');
            return;
        }

        $this->editingTemplateId = $template->id;
        $this->templateName = $template->name;
        $this->templateFeatures = $template->features ?? [];
        $this->showTemplateModal = true;
    }

    /**
     * Save template (create or update)
     */
    public function saveTemplate(): void
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateFeatures' => 'required|array|min:1',
        ]);

        try {
            DB::transaction(function () {
                if ($this->editingTemplateId) {
                    $template = FeatureTemplate::findOrFail($this->editingTemplateId);

                    if ($template->is_predefined) {
                        throw new \Exception('Nie mozna edytowac predefiniowanych szablonow');
                    }

                    $template->update([
                        'name' => $this->templateName,
                        'features' => $this->templateFeatures,
                    ]);
                    Log::info('Template updated', ['id' => $template->id]);
                } else {
                    FeatureTemplate::create([
                        'name' => $this->templateName,
                        'features' => $this->templateFeatures,
                        'is_predefined' => false,
                        'is_active' => true,
                    ]);
                    Log::info('Template created', ['name' => $this->templateName]);
                }
            });

            $this->closeTemplateModal();
            $this->dispatch('notify', type: 'success', message: 'Szablon zapisany pomyslnie.');

        } catch (\Exception $e) {
            Log::error('Template save failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad zapisu: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate template
     */
    public function duplicateTemplate(int $templateId): void
    {
        try {
            $original = FeatureTemplate::findOrFail($templateId);

            FeatureTemplate::create([
                'name' => $original->name . ' (kopia)',
                'features' => $original->features,
                'is_predefined' => false,
                'is_active' => true,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Szablon skopiowany.');

        } catch (\Exception $e) {
            Log::error('Template duplicate failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad kopiowania: ' . $e->getMessage());
        }
    }

    /**
     * Delete template
     */
    public function deleteTemplate(int $templateId): void
    {
        try {
            $template = FeatureTemplate::findOrFail($templateId);

            if ($template->is_predefined) {
                $this->dispatch('notify', type: 'error', message: 'Nie mozna usunac predefiniowanych szablonow.');
                return;
            }

            $template->delete();

            $this->dispatch('notify', type: 'success', message: 'Szablon usuniety.');

        } catch (\Exception $e) {
            Log::error('Template delete failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', type: 'error', message: 'Blad usuwania: ' . $e->getMessage());
        }
    }

    /**
     * Close template modal
     */
    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
        $this->resetTemplateForm();
    }

    /**
     * Reset template form
     */
    private function resetTemplateForm(): void
    {
        $this->editingTemplateId = null;
        $this->templateName = '';
        $this->templateFeatures = [];
    }

    /**
     * Add feature row to template
     */
    public function addFeatureRow(): void
    {
        $this->templateFeatures[] = [
            'name' => '',
            'type' => 'text',
            'required' => false,
            'default' => '',
        ];
    }

    /**
     * Remove feature from template
     */
    public function removeFeature(int $index): void
    {
        if (isset($this->templateFeatures[$index])) {
            unset($this->templateFeatures[$index]);
            $this->templateFeatures = array_values($this->templateFeatures);
        }
    }

    /**
     * Reorder template features (drag & drop callback from wire:sortable)
     */
    public function reorderTemplateFeatures(array $newOrder): void
    {
        $reordered = [];
        foreach ($newOrder as $item) {
            $oldIndex = (int) $item['value'];
            if (isset($this->templateFeatures[$oldIndex])) {
                $reordered[] = $this->templateFeatures[$oldIndex];
            }
        }
        if (count($reordered) === count($this->templateFeatures)) {
            $this->templateFeatures = $reordered;
        }
    }

    // ========================================
    // BULK ASSIGN METHODS
    // ========================================

    /**
     * Open bulk assign modal
     *
     * @param int $templateId Template ID is REQUIRED (from card button click)
     */
    public function openBulkAssignModal(int $templateId): void
    {
        $this->bulkAssignTemplateId = $templateId;
        $this->bulkAssignScope = 'all_vehicles';
        $this->bulkAssignCategoryId = null;
        $this->bulkAssignAction = 'add_features';
        $this->calculateProductsCount();
        $this->showBulkAssignModal = true;
    }

    /**
     * Close bulk assign modal
     */
    public function closeBulkAssignModal(): void
    {
        $this->showBulkAssignModal = false;
    }

    /**
     * Calculate products count for bulk assign
     */
    public function calculateProductsCount(): void
    {
        $query = \App\Models\Product::query();

        if ($this->bulkAssignScope === 'by_category' && $this->bulkAssignCategoryId) {
            $query->where('category_id', $this->bulkAssignCategoryId);
        }

        $this->bulkAssignProductsCount = $query->count();
    }

    public function updatedBulkAssignScope(): void
    {
        $this->calculateProductsCount();
    }

    public function updatedBulkAssignCategoryId(): void
    {
        $this->calculateProductsCount();
    }

    /**
     * Execute bulk assign
     */
    public function bulkAssign(): void
    {
        $this->validate([
            'bulkAssignTemplateId' => 'required',
            'bulkAssignScope' => 'required|in:all_vehicles,by_category',
            'bulkAssignAction' => 'required|in:add_features,replace_features',
        ]);

        try {
            $jobId = Str::uuid()->toString();
            $progressService = app(JobProgressService::class);
            $progressId = $progressService->createJobProgress(
                $jobId,
                null,
                'bulk_assign_features',
                $this->bulkAssignProductsCount
            );

            $this->activeJobProgressId = $progressId;

            BulkAssignFeaturesJob::dispatch(
                $this->bulkAssignTemplateId,
                $this->bulkAssignScope,
                $this->bulkAssignCategoryId,
                $this->bulkAssignAction,
                $jobId,
                auth()->id()
            );

            $this->closeBulkAssignModal();
            $this->dispatch('notify', type: 'success', message: "Rozpoczeto przypisywanie szablonu do {$this->bulkAssignProductsCount} produktow...");

        } catch (\Exception $e) {
            Log::error('BulkAssign dispatch failed', ['error' => $e->getMessage()]);
            $this->addError('general', 'Blad: ' . $e->getMessage());
        }
    }

    /**
     * Refresh job progress
     */
    public function refreshJobProgress(): void
    {
        if (!$this->activeJobProgressId) {
            return;
        }

        $progress = JobProgress::find($this->activeJobProgressId);

        if (!$progress) {
            $this->activeJobProgressId = null;
            $this->activeJobProgress = [];
            return;
        }

        $this->activeJobProgress = [
            'status' => $progress->status,
            'current' => $progress->current_count,
            'total' => $progress->total_count,
            'percentage' => $progress->progress_percentage,
            'errors' => $progress->error_count,
        ];

        if (in_array($progress->status, ['completed', 'failed'])) {
            if ($progress->status === 'completed') {
                $this->dispatch('notify', type: 'success', message: "Ukonczone! Szablon zastosowany do {$progress->current_count} produktow.");
            }
        }
    }

    /**
     * Dismiss progress
     */
    public function dismissProgress(): void
    {
        $this->activeJobProgressId = null;
        $this->activeJobProgress = [];
    }

    // ========================================
    // RENDER
    // ========================================

    public function render()
    {
        return view('livewire.admin.features.tabs.feature-templates-tab');
    }
}
