<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription\Traits;

use App\Models\DescriptionTemplate;

/**
 * Trait EditorTemplates.
 *
 * Handles template loading, saving, and management.
 */
trait EditorTemplates
{
    /**
     * Show template modal.
     */
    public bool $showTemplateModal = false;

    /**
     * Template modal mode: 'load' or 'save'.
     */
    public string $templateModalMode = 'load';

    /**
     * New template name for saving.
     */
    public string $newTemplateName = '';

    /**
     * New template description.
     */
    public string $newTemplateDescription = '';

    /**
     * Search query for templates.
     */
    public string $templateSearch = '';

    /**
     * Open template modal in load mode.
     */
    public function openLoadTemplateModal(): void
    {
        $this->templateModalMode = 'load';
        $this->showTemplateModal = true;
        $this->templateSearch = '';
    }

    /**
     * Open template modal in save mode.
     */
    public function openSaveTemplateModal(): void
    {
        $this->templateModalMode = 'save';
        $this->newTemplateName = '';
        $this->newTemplateDescription = '';
        $this->showTemplateModal = true;
    }

    /**
     * Close template modal.
     */
    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
    }

    /**
     * Load blocks from template.
     */
    public function loadTemplate(int $templateId): void
    {
        $template = DescriptionTemplate::find($templateId);

        if (!$template) {
            $this->dispatch('notify', type: 'error', message: 'Szablon nie istnieje');
            return;
        }

        // Confirm if current blocks exist
        if (!empty($this->blocks)) {
            $this->dispatch('confirm-template-load', templateId: $templateId);
            return;
        }

        $this->applyTemplate($template);
    }

    /**
     * Confirm and apply template (replaces current blocks).
     */
    public function confirmLoadTemplate(int $templateId): void
    {
        $template = DescriptionTemplate::find($templateId);

        if (!$template) {
            return;
        }

        $this->applyTemplate($template);
    }

    /**
     * Apply template blocks.
     */
    protected function applyTemplate(DescriptionTemplate $template): void
    {
        $templateBlocks = $template->blocks_json ?? [];

        // Regenerate block IDs
        $this->blocks = array_map(function ($block) {
            $block['id'] = $this->generateBlockId();
            return $block;
        }, $templateBlocks);

        $this->selectedBlockIndex = null;
        $this->pushUndoState();
        $this->isDirty = true;
        $this->showTemplateModal = false;

        $this->dispatch('notify', type: 'success', message: "Zaladowano szablon: {$template->name}");
    }

    /**
     * Save current blocks as template.
     */
    public function saveAsTemplate(): void
    {
        $this->validate([
            'newTemplateName' => 'required|string|min:3|max:100',
        ], [
            'newTemplateName.required' => 'Nazwa szablonu jest wymagana',
            'newTemplateName.min' => 'Nazwa musi miec minimum 3 znaki',
        ]);

        if (empty($this->blocks)) {
            $this->dispatch('notify', type: 'error', message: 'Brak blokow do zapisania');
            return;
        }

        $template = DescriptionTemplate::create([
            'name' => $this->newTemplateName,
            'description' => $this->newTemplateDescription,
            'shop_id' => $this->shopId,
            'blocks_json' => $this->blocks,
            'preview_html' => $this->generatePreviewHtml(),
            'created_by' => auth()->id(),
        ]);

        $this->showTemplateModal = false;
        $this->newTemplateName = '';
        $this->newTemplateDescription = '';

        $this->dispatch('notify', type: 'success', message: "Zapisano szablon: {$template->name}");
    }

    /**
     * Get available templates.
     */
    public function getTemplatesProperty()
    {
        $query = DescriptionTemplate::query()
            ->where(function ($q) {
                $q->whereNull('shop_id')
                    ->orWhere('shop_id', $this->shopId);
            })
            ->orderBy('name');

        if ($this->templateSearch) {
            $query->where('name', 'like', "%{$this->templateSearch}%");
        }

        return $query->limit(20)->get();
    }

    /**
     * Delete template.
     */
    public function deleteTemplate(int $templateId): void
    {
        $template = DescriptionTemplate::find($templateId);

        if (!$template) {
            return;
        }

        // Check permissions - use 'created_by' from model
        $isOwner = $template->created_by === auth()->id();
        $isAdmin = auth()->user() && method_exists(auth()->user(), 'hasRole')
            ? auth()->user()->hasRole('admin')
            : false;

        if (!$isOwner && !$isAdmin) {
            $this->dispatch('notify', type: 'error', message: 'Brak uprawnien do usuniecia szablonu');
            return;
        }

        $name = $template->name;
        $template->delete();

        $this->dispatch('notify', type: 'success', message: "Usunieto szablon: {$name}");
    }
}
