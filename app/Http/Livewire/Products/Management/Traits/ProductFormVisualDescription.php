<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductDescription;
use App\Models\ProductDescriptionVersion;
use App\Models\DescriptionTemplate;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\BlockRenderer;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use App\Services\JobProgressService;
use App\Jobs\VisualEditor\SyncDescriptionToPrestaShopJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * ProductFormVisualDescription Trait
 *
 * ETAP_07f Faza 6: Visual Description Editor Integration
 *
 * Handles visual description management within ProductForm:
 * - Loading visual description for current shop
 * - Preview of rendered HTML
 * - Sync to standard description
 * - Toggle between editors
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since ETAP_07f - Visual Description Editor
 */
trait ProductFormVisualDescription
{
    /**
     * Toggle between standard and visual editor
     */
    public bool $useVisualEditor = false;

    /**
     * Current visual description ID
     */
    public ?int $visualDescriptionId = null;

    /**
     * Preview HTML cache (avoid repeated rendering)
     */
    public ?string $visualPreviewHtml = null;

    /**
     * Selected template ID for applying to visual description
     */
    public ?int $selectedTemplateId = null;

    /**
     * Initialize visual description state
     */
    protected function initializeVisualDescription(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->visualDescriptionId = null;
            $this->visualPreviewHtml = null;
            return;
        }

        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            return;
        }

        $description = $this->getVisualDescriptionForShop($shopId);
        if ($description) {
            $this->visualDescriptionId = $description->id;
            $this->loadVisualPreview($description);
        }
    }

    /**
     * Get visual description for specific shop
     *
     * @param int $shopId
     * @return ProductDescription|null
     */
    protected function getVisualDescriptionForShop(int $shopId): ?ProductDescription
    {
        if (!$this->product || !$this->product->id) {
            return null;
        }

        return ProductDescription::where('product_id', $this->product->id)
            ->where('shop_id', $shopId)
            ->first();
    }

    /**
     * Get visual description for current context (computed property)
     */
    #[Computed]
    public function visualDescription(): ?ProductDescription
    {
        if (!$this->product || !$this->product->id) {
            return null;
        }

        // Use activeShopId or first exported shop
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            return null;
        }

        return $this->getVisualDescriptionForShop($shopId);
    }

    /**
     * Check if visual description exists for current shop
     */
    #[Computed]
    public function hasVisualDescription(): bool
    {
        return $this->visualDescription !== null
            && !empty($this->visualDescription->blocks_json);
    }

    /**
     * Get block count for current visual description
     */
    #[Computed]
    public function visualBlockCount(): int
    {
        $description = $this->visualDescription;
        return $description ? $description->block_count : 0;
    }

    /**
     * Get first exported shop ID (fallback for context)
     */
    protected function getFirstExportedShopId(): ?int
    {
        if (empty($this->exportedShops)) {
            return null;
        }

        return (int) reset($this->exportedShops);
    }

    /**
     * Load visual description preview HTML
     *
     * @param ProductDescription|null $description
     */
    protected function loadVisualPreview(?ProductDescription $description = null): void
    {
        $description = $description ?? $this->visualDescription;

        if (!$description || empty($description->blocks_json)) {
            $this->visualPreviewHtml = null;
            return;
        }

        try {
            $renderer = app(BlockRenderer::class);
            $this->visualPreviewHtml = $renderer->generateHtml($description, includeStyles: false);
        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Preview render failed', [
                'description_id' => $description->id,
                'error' => $e->getMessage(),
            ]);
            $this->visualPreviewHtml = null;
        }
    }

    /**
     * Refresh visual description preview
     */
    public function refreshVisualPreview(): void
    {
        $this->loadVisualPreview();
        $this->dispatch('visual-preview-refreshed');
    }

    /**
     * Open visual editor in full-screen mode
     *
     * Redirects to Unified Visual Editor (UVE) route with product and shop IDs
     * Route: /admin/visual-editor/uve/{product}/shop/{shop}
     */
    public function openVisualEditor(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->addError('visual_editor', 'Najpierw zapisz produkt');
            return;
        }

        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            $this->addError('visual_editor', 'Wybierz sklep, aby edytowac opis wizualny');
            return;
        }

        // Redirect to UVE (Unified Visual Editor) - ETAP_07f_P5
        $url = "/admin/visual-editor/uve/{$this->product->id}/shop/{$shopId}";
        $this->redirect($url, navigate: true);
    }

    /**
     * Create new visual description for current shop
     */
    public function createVisualDescription(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->addError('visual_description', 'Najpierw zapisz produkt');
            return;
        }

        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            $this->addError('visual_description', 'Wybierz sklep, aby utworzyc opis wizualny');
            return;
        }

        try {
            $description = ProductDescription::getOrCreate(
                $this->product->id,
                $shopId
            );

            $this->visualDescriptionId = $description->id;

            Log::info('[ProductFormVisualDescription] Created visual description', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'description_id' => $description->id,
            ]);

            session()->flash('message', 'Utworzono opis wizualny. Kliknij "Edytuj" aby dodac bloki.');

        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Create failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('visual_description', 'Nie udalo sie utworzyc opisu wizualnego');
        }
    }

    /**
     * Sync visual description to standard description field
     *
     * Renders blocks to HTML and updates product.long_description
     */
    public function syncVisualToStandard(): void
    {
        $description = $this->visualDescription;

        if (!$description || empty($description->blocks_json)) {
            $this->addError('visual_sync', 'Brak opisu wizualnego do synchronizacji');
            return;
        }

        try {
            $renderer = app(BlockRenderer::class);
            $html = $renderer->generateCleanHtml($description);

            // Update standard description based on context
            if ($this->activeShopId) {
                // Update shop-specific description (in-memory)
                $this->shopData[$this->activeShopId]['long_description'] = $html;

                // Persist to database (not just in-memory)
                $psd = \App\Models\ProductShopData::firstOrNew([
                    'product_id' => $this->product->id,
                    'shop_id' => $this->activeShopId,
                ]);
                $psd->long_description = $html;
                $psd->save();

                // Invalidate ProductStatusAggregator cache so ProductList shows updated status
                app(\App\Services\Product\ProductStatusAggregator::class)->invalidateCache($this->product->id);
            } else {
                // Update default long description
                $this->long_description = $html;
            }

            $this->useVisualEditor = true;

            Log::info('[ProductFormVisualDescription] Synced to standard', [
                'product_id' => $this->product->id,
                'shop_id' => $this->activeShopId,
                'html_length' => strlen($html),
            ]);

            session()->flash('message', 'Opis wizualny zsynchronizowany z opisem standardowym');

        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Sync to standard failed', [
                'description_id' => $description->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->addError('visual_sync', 'Blad podczas synchronizacji opisu');
        }
    }

    /**
     * Toggle visual editor preference
     */
    public function toggleVisualEditor(): void
    {
        $this->useVisualEditor = !$this->useVisualEditor;

        Log::info('[ProductFormVisualDescription] Toggled visual editor', [
            'product_id' => $this->product->id ?? null,
            'use_visual' => $this->useVisualEditor,
        ]);
    }

    /**
     * Apply template to visual description
     *
     * @param int $templateId
     */
    public function applyTemplateToVisual(int $templateId): void
    {
        if (!$this->product || !$this->product->id) {
            $this->addError('visual_template', 'Najpierw zapisz produkt');
            return;
        }

        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            $this->addError('visual_template', 'Wybierz sklep');
            return;
        }

        $template = DescriptionTemplate::find($templateId);
        if (!$template) {
            $this->addError('visual_template', 'Szablon nie istnieje');
            return;
        }

        try {
            $description = ProductDescription::getOrCreate(
                $this->product->id,
                $shopId
            );

            $description->applyTemplate($template);
            $description->save();

            $this->visualDescriptionId = $description->id;
            $this->loadVisualPreview($description);
            $this->selectedTemplateId = null;

            Log::info('[ProductFormVisualDescription] Applied template', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'template_id' => $templateId,
            ]);

            session()->flash('message', "Szablon '{$template->name}' zostal zastosowany");

        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Apply template failed', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('visual_template', 'Blad podczas stosowania szablonu');
        }
    }

    /**
     * Get available description templates
     *
     * Returns templates available for current shop context
     */
    #[Computed]
    public function availableTemplates(): array
    {
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();

        return DescriptionTemplate::forShop($shopId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->toArray();
    }

    /**
     * Get visual description info for display
     */
    #[Computed]
    public function visualDescriptionInfo(): array
    {
        $description = $this->visualDescription;

        if (!$description) {
            return [
                'exists' => false,
                'block_count' => 0,
                'last_modified' => null,
                'template_name' => null,
            ];
        }

        return [
            'exists' => true,
            'block_count' => $description->block_count,
            'last_modified' => $description->updated_at?->diffForHumans(),
            'template_name' => $description->template?->name,
            'needs_rendering' => $description->needs_rendering,
        ];
    }

    /**
     * Get CSS content for current shop (for preview styling)
     *
     * Uses PrestaShopCssFetcher to get CSS from shop's custom_css_url or cache.
     * Returns null if no CSS configured or fetch fails.
     *
     * @return string|null CSS content for preview iframe
     */
    #[Computed]
    public function shopCssContent(): ?string
    {
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            return null;
        }

        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            return null;
        }

        // Check if CSS is configured
        if (!$shop->custom_css_url && !$shop->ftp_config) {
            return null;
        }

        try {
            $fetcher = app(PrestaShopCssFetcher::class);
            return $fetcher->getCssForPreview($shop);
        } catch (\Exception $e) {
            Log::warning('[ProductFormVisualDescription] Failed to get CSS for preview', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if shop has CSS configured for preview
     */
    #[Computed]
    public function hasShopCss(): bool
    {
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            return false;
        }

        $shop = PrestaShopShop::find($shopId);
        return $shop && ($shop->custom_css_url || $shop->ftp_config);
    }

    /**
     * Get shop CSS URL for display/linking
     */
    #[Computed]
    public function shopCssUrl(): ?string
    {
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            return null;
        }

        $shop = PrestaShopShop::find($shopId);
        return $shop?->custom_css_url;
    }

    /**
     * Refresh CSS cache from PrestaShop
     */
    public function refreshShopCss(): void
    {
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            $this->addError('css_refresh', 'Nie wybrano sklepu');
            return;
        }

        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            $this->addError('css_refresh', 'Sklep nie istnieje');
            return;
        }

        try {
            $fetcher = app(PrestaShopCssFetcher::class);
            $success = $fetcher->refreshCache($shop);

            if ($success) {
                session()->flash('message', 'CSS sklepu odswiezony');
                $this->dispatch('shop-css-refreshed');
            } else {
                $this->addError('css_refresh', 'Nie udalo sie pobrac CSS');
            }
        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] CSS refresh failed', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('css_refresh', 'Blad podczas odswiezania CSS');
        }
    }

    /**
     * Refresh descriptions from DB for current shop
     *
     * Called when returning from UVE or switching shop tabs to ensure
     * textarea shows up-to-date data (UVE may have changed it externally).
     */
    public function refreshDescriptionsFromDb(): void
    {
        if (!$this->activeShopId || !$this->product || !$this->product->id) {
            return;
        }

        $freshData = \App\Models\ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->activeShopId)
            ->first();

        if ($freshData) {
            $this->shopData[$this->activeShopId]['long_description'] = $freshData->long_description ?? '';
            $this->shopData[$this->activeShopId]['short_description'] = $freshData->short_description ?? '';
            $this->long_description = $freshData->long_description ?? '';
            $this->short_description = $freshData->short_description ?? '';
        }
    }

    /**
     * Called when shop tab changes - refresh visual description context
     */
    public function onShopTabChangedForVisual(): void
    {
        // Reset visual description cache
        $this->visualDescriptionId = null;
        $this->visualPreviewHtml = null;

        // Reinitialize for new shop context
        $this->initializeVisualDescription();
    }

    // =========================================================================
    // PRESTASHOP SYNC METHODS (ETAP_07f 6.1.4)
    // =========================================================================

    /**
     * Toggle sync_to_prestashop setting
     */
    public function togglePrestaShopSync(): void
    {
        $description = $this->visualDescription;
        if (!$description) {
            $this->addError('visual_sync', 'Najpierw utworz opis wizualny');
            return;
        }

        $description->sync_to_prestashop = !$description->sync_to_prestashop;
        $description->save();

        Log::info('[ProductFormVisualDescription] Toggled PrestaShop sync', [
            'description_id' => $description->id,
            'sync_enabled' => $description->sync_to_prestashop,
        ]);

        $status = $description->sync_to_prestashop ? 'wlaczona' : 'wylaczona';
        session()->flash('message', "Synchronizacja z PrestaShop {$status}");
    }

    /**
     * Set target field for PrestaShop sync
     *
     * @param string $field description|description_short|both
     */
    public function setPrestaShopTargetField(string $field): void
    {
        $allowedFields = ['description', 'description_short', 'both'];
        if (!in_array($field, $allowedFields)) {
            $this->addError('visual_sync', 'Nieprawidlowe pole docelowe');
            return;
        }

        $description = $this->visualDescription;
        if (!$description) {
            $this->addError('visual_sync', 'Najpierw utworz opis wizualny');
            return;
        }

        $description->target_field = $field;
        $description->save();

        Log::info('[ProductFormVisualDescription] Set target field', [
            'description_id' => $description->id,
            'target_field' => $field,
        ]);
    }

    /**
     * Toggle include_inline_css setting
     */
    public function toggleInlineCss(): void
    {
        $description = $this->visualDescription;
        if (!$description) {
            return;
        }

        $description->include_inline_css = !$description->include_inline_css;
        $description->save();

        Log::info('[ProductFormVisualDescription] Toggled inline CSS', [
            'description_id' => $description->id,
            'include_css' => $description->include_inline_css,
        ]);
    }

    /**
     * Sync visual description to PrestaShop NOW
     *
     * Dispatches SyncDescriptionToPrestaShopJob
     */
    public function syncToPrestaShopNow(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->addError('visual_sync', 'Najpierw zapisz produkt');
            return;
        }

        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        if (!$shopId) {
            $this->addError('visual_sync', 'Wybierz sklep');
            return;
        }

        $description = $this->visualDescription;
        if (!$description || empty($description->blocks_json)) {
            $this->addError('visual_sync', 'Brak opisu wizualnego do synchronizacji');
            return;
        }

        // Check if product is synced to PrestaShop
        $shopData = $this->product->getShopData($shopId);
        if (!$shopData || !$shopData->prestashop_id) {
            $this->addError('visual_sync', 'Produkt nie jest zsynchronizowany z tym sklepem PrestaShop');
            return;
        }

        try {
            // Create progress tracking
            $progressService = app(JobProgressService::class);
            $progress = $progressService->create(
                'sync_visual_description',
                1,
                Auth::id(),
                ['product_id' => $this->product->id, 'shop_id' => $shopId]
            );

            // Dispatch sync job
            SyncDescriptionToPrestaShopJob::dispatch(
                $this->product->id,
                $shopId,
                Auth::id(),
                $progress->id
            );

            Log::info('[ProductFormVisualDescription] Dispatched sync job', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'progress_id' => $progress->id,
            ]);

            session()->flash('message', 'Synchronizacja opisu z PrestaShop rozpoczeta');
            $this->dispatch('sync-job-started', progressId: $progress->id);

        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Sync dispatch failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('visual_sync', 'Blad podczas uruchamiania synchronizacji');
        }
    }

    /**
     * Get sync status info for display
     */
    #[Computed]
    public function visualSyncInfo(): array
    {
        $description = $this->visualDescription;

        if (!$description) {
            return [
                'sync_enabled' => false,
                'target_field' => 'description',
                'include_css' => true,
                'last_synced' => null,
                'needs_sync' => false,
                'can_sync' => false,
            ];
        }

        // Check if product is synced to PrestaShop
        $shopId = $this->activeShopId ?? $this->getFirstExportedShopId();
        $canSync = false;
        if ($shopId && $this->product) {
            $shopData = $this->product->dataForShop($shopId)->first();
            $canSync = $shopData && $shopData->prestashop_id;
        }

        return [
            'sync_enabled' => (bool) $description->sync_to_prestashop,
            'target_field' => $description->target_field ?? 'description',
            'include_css' => (bool) $description->include_inline_css,
            'last_synced' => $description->last_synced_at?->diffForHumans(),
            'last_synced_full' => $description->last_synced_at?->format('Y-m-d H:i:s'),
            'needs_sync' => $description->needsSync(),
            'can_sync' => $canSync,
        ];
    }

    // =====================
    // VERSION HISTORY (ETAP_07f Faza 6.1.4.4)
    // =====================

    /**
     * Version history modal state
     */
    public bool $showVersionHistoryModal = false;

    /**
     * Selected version for preview
     */
    public ?int $previewVersionId = null;

    /**
     * Version history list (cached)
     */
    public array $versionHistoryList = [];

    /**
     * Open version history modal
     */
    public function openVersionHistory(): void
    {
        $description = $this->visualDescription;
        if (!$description) {
            $this->addError('version_history', 'Brak opisu wizualnego');
            return;
        }

        $this->loadVersionHistory();
        $this->showVersionHistoryModal = true;

        Log::info('[ProductFormVisualDescription] Opened version history', [
            'description_id' => $description->id,
            'version_count' => count($this->versionHistoryList),
        ]);
    }

    /**
     * Close version history modal
     */
    public function closeVersionHistory(): void
    {
        $this->showVersionHistoryModal = false;
        $this->previewVersionId = null;
    }

    /**
     * Load version history for current description
     */
    protected function loadVersionHistory(): void
    {
        $description = $this->visualDescription;
        if (!$description) {
            $this->versionHistoryList = [];
            return;
        }

        $this->versionHistoryList = $description->versions()
            ->with('creator:id,name')
            ->take(20) // Last 20 versions
            ->get()
            ->map(function (ProductDescriptionVersion $version) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'change_type' => $version->change_type,
                    'change_type_label' => $version->change_type_label,
                    'change_type_icon' => $version->change_type_icon,
                    'creator_name' => $version->creator?->name ?? 'System',
                    'created_at' => $version->created_at->format('Y-m-d H:i'),
                    'created_at_human' => $version->created_at->diffForHumans(),
                    'block_count' => $version->block_count,
                ];
            })
            ->toArray();
    }

    /**
     * Preview specific version
     */
    public function previewVersion(int $versionId): void
    {
        $version = ProductDescriptionVersion::find($versionId);
        if (!$version) {
            $this->addError('version_history', 'Wersja nie istnieje');
            return;
        }

        $this->previewVersionId = $versionId;

        Log::info('[ProductFormVisualDescription] Previewing version', [
            'version_id' => $versionId,
            'version_number' => $version->version_number,
        ]);
    }

    /**
     * Get preview HTML for selected version
     */
    #[Computed]
    public function versionPreviewHtml(): ?string
    {
        if (!$this->previewVersionId) {
            return null;
        }

        $version = ProductDescriptionVersion::find($this->previewVersionId);
        if (!$version) {
            return null;
        }

        // Return cached rendered HTML if available
        if ($version->rendered_html) {
            return $version->rendered_html;
        }

        // Otherwise try to render from blocks_json
        if (!empty($version->blocks_json)) {
            try {
                $renderer = app(BlockRenderer::class);
                return $renderer->render($version->blocks_json, [
                    'shop_id' => $this->visualDescription?->shop_id,
                    'include_styles' => true,
                ]);
            } catch (\Exception $e) {
                Log::error('[ProductFormVisualDescription] Version render failed', [
                    'version_id' => $version->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return '<p class="text-gray-400">Brak podgladu dla tej wersji</p>';
    }

    /**
     * Restore from specific version
     */
    public function restoreVersion(int $versionId): void
    {
        $version = ProductDescriptionVersion::find($versionId);
        if (!$version) {
            $this->addError('version_history', 'Wersja nie istnieje');
            return;
        }

        $description = $this->visualDescription;
        if (!$description) {
            $this->addError('version_history', 'Brak opisu wizualnego');
            return;
        }

        // Verify version belongs to this description
        if ($version->product_description_id !== $description->id) {
            $this->addError('version_history', 'Wersja nie nalezy do tego opisu');
            return;
        }

        try {
            $version->restore(Auth::id());

            // Reload preview
            $this->loadVisualPreview($description->fresh());
            $this->loadVersionHistory();
            $this->previewVersionId = null;

            Log::info('[ProductFormVisualDescription] Restored from version', [
                'description_id' => $description->id,
                'version_id' => $versionId,
                'version_number' => $version->version_number,
            ]);

            session()->flash('message', "Przywrocono wersje #{$version->version_number}");

        } catch (\Exception $e) {
            Log::error('[ProductFormVisualDescription] Version restore failed', [
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('version_history', 'Blad podczas przywracania wersji');
        }
    }

    /**
     * Get version history info for display
     */
    #[Computed]
    public function versionHistoryInfo(): array
    {
        $description = $this->visualDescription;
        if (!$description) {
            return [
                'has_versions' => false,
                'version_count' => 0,
                'latest_version' => null,
            ];
        }

        $latestVersion = $description->getLatestVersion();

        return [
            'has_versions' => $description->getVersionCount() > 0,
            'version_count' => $description->getVersionCount(),
            'latest_version' => $latestVersion ? [
                'number' => $latestVersion->version_number,
                'date' => $latestVersion->created_at->diffForHumans(),
                'type' => $latestVersion->change_type_label,
            ] : null,
        ];
    }
}
