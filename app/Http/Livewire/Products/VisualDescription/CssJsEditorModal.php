<?php

declare(strict_types=1);

namespace App\Http\Livewire\Products\VisualDescription;

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopAssetDiscovery;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * CSS/JS Editor Modal Component
 *
 * Displays all CSS/JS files from PrestaShop and allows editing custom.css/custom.js.
 *
 * ETAP_07f_P3: Visual Description Editor - CSS Integration
 */
class CssJsEditorModal extends Component
{
    /**
     * Shop ID to edit CSS for.
     */
    public ?int $shopId = null;

    /**
     * Whether modal is open.
     */
    public bool $isOpen = false;

    /**
     * Active tab: files, editor, analysis.
     */
    public string $activeTab = 'files';

    /**
     * Asset manifest from PrestaShop.
     */
    public array $assetManifest = [];

    /**
     * Selected assets for preview.
     */
    public array $selectedAssets = [];

    /**
     * Currently editing file type: 'css' or 'js'.
     */
    public string $editingType = 'css';

    /**
     * Currently editing file path (FTP path).
     * ETAP_07f_P3: Track specific file being edited.
     */
    public ?string $editingFilePath = null;

    /**
     * Currently editing file name (for display).
     */
    public ?string $editingFileName = null;

    /**
     * Editor content.
     */
    public string $editorContent = '';

    /**
     * Original content (for dirty check).
     */
    public string $originalContent = '';

    /**
     * Loading state.
     */
    public bool $isLoading = false;

    /**
     * Error message.
     */
    public ?string $errorMessage = null;

    /**
     * Success message.
     */
    public ?string $successMessage = null;

    /**
     * Open the modal.
     */
    #[On('openCssJsEditor')]
    public function open(int $shopId): void
    {
        $this->shopId = $shopId;
        $this->isOpen = true;
        $this->activeTab = 'files';
        $this->errorMessage = null;
        $this->successMessage = null;

        $this->loadAssetManifest();
    }

    /**
     * Close the modal.
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['assetManifest', 'selectedAssets', 'editorContent', 'originalContent', 'editingFilePath', 'editingFileName']);
    }

    /**
     * Set active tab.
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'editor' && empty($this->editorContent)) {
            $this->loadEditorContent();
        }
    }

    /**
     * Load asset manifest from PrestaShop.
     */
    public function loadAssetManifest(): void
    {
        if (!$this->shopId) {
            $this->errorMessage = 'Brak ID sklepu';
            return;
        }

        // Get computed shop and check for null
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Nie znaleziono sklepu o ID: ' . $this->shopId;
            \Log::warning('CssJsEditorModal: Shop not found', ['shop_id' => $this->shopId]);
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $discovery = app(PrestaShopAssetDiscovery::class);
            $this->assetManifest = $discovery->getCategorizedAssets($shop);

            // Load previously selected modules from shop config
            $this->selectedAssets = $shop->selected_css_modules ?? [];

            $this->successMessage = 'Lista plikow zaladowana';

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad ladowania listy plikow: ' . $e->getMessage();
            \Log::error('CssJsEditorModal: loadAssetManifest failed', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Refresh asset manifest (force fetch from PrestaShop).
     */
    public function refreshAssetManifest(): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        $this->isLoading = true;

        try {
            $discovery = app(PrestaShopAssetDiscovery::class);
            $discovery->clearCache($shop);
            $this->assetManifest = $discovery->getCategorizedAssets($shop);

            $this->successMessage = 'Lista plikow odswiezona z PrestaShop';

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad odswiezania: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Load editor content (custom.css or custom.js).
     * ETAP_07f_P3: Uses dynamic file path from scanned files.
     */
    public function loadEditorContent(): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        $this->isLoading = true;

        try {
            $fetcher = app(PrestaShopCssFetcher::class);

            if ($this->editingType === 'css') {
                $result = $fetcher->getCustomCss($shop, $this->editingFilePath);
            } else {
                $result = $fetcher->getCustomJs($shop, $this->editingFilePath);
            }

            if ($result['success']) {
                $this->editorContent = $result['content'] ?? '';
                $this->originalContent = $this->editorContent;
                // Store the resolved file path
                $this->editingFilePath = $result['filePath'] ?? $this->editingFilePath;
                $this->editingFileName = $this->editingFilePath ? basename($this->editingFilePath) : null;
                $this->successMessage = 'Plik zaladowany: ' . ($this->editingFileName ?? 'custom.' . $this->editingType);
            } else {
                $this->errorMessage = $result['error'] ?? 'Nie udalo sie zaladowac pliku';
            }

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad ladowania: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Switch editing type (CSS/JS).
     * ETAP_07f_P3: Resets file path when switching type.
     */
    public function switchEditingType(string $type): void
    {
        if ($type === $this->editingType) {
            return;
        }

        // Check for unsaved changes
        if ($this->editorContent !== $this->originalContent) {
            $this->dispatch('confirm-switch', type: $type);
            return;
        }

        $this->editingType = $type;
        // Reset file path to let system find default for new type
        $this->editingFilePath = null;
        $this->editingFileName = null;
        $this->loadEditorContent();
    }

    /**
     * Save editor content to PrestaShop.
     * ETAP_07f_P3: Uses dynamic file path from scanned files.
     */
    public function saveContent(): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $fetcher = app(PrestaShopCssFetcher::class);

            if ($this->editingType === 'css') {
                $result = $fetcher->saveCustomCss($shop, $this->editorContent, $this->editingFilePath);
            } else {
                $result = $fetcher->saveCustomJs($shop, $this->editorContent, $this->editingFilePath);
            }

            if ($result['success']) {
                $this->originalContent = $this->editorContent;
                $this->successMessage = 'Plik zapisany na PrestaShop: ' . ($this->editingFileName ?? 'custom.' . $this->editingType);

                // Dispatch event to refresh preview
                $this->dispatch('cssUpdated');
            } else {
                $this->errorMessage = $result['error'] ?? 'Nie udalo sie zapisac pliku';
            }

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad zapisu: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Toggle asset selection.
     */
    public function toggleAsset(string $url): void
    {
        if (in_array($url, $this->selectedAssets)) {
            $this->selectedAssets = array_values(array_filter(
                $this->selectedAssets,
                fn($u) => $u !== $url
            ));
        } else {
            $this->selectedAssets[] = $url;
        }
    }

    /**
     * Save selected assets to shop config.
     */
    public function saveSelectedAssets(): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        try {
            $shop->update([
                'selected_css_modules' => $this->selectedAssets,
            ]);

            $this->successMessage = 'Wybrane moduly zapisane';

            // Dispatch event to refresh preview
            $this->dispatch('cssUpdated');

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad zapisu: ' . $e->getMessage();
        }
    }

    /**
     * Fetch selected CSS to PPM cache.
     */
    public function fetchSelectedToPpm(): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        $this->isLoading = true;

        try {
            $fetcher = app(PrestaShopCssFetcher::class);

            // Fetch theme CSS
            $fetcher->fetchThemeCss($shop, forceRefresh: true);

            // Fetch custom CSS
            $fetcher->fetchCssFromUrl($shop, forceRefresh: true);

            $this->successMessage = 'CSS pobrane do PPM cache';

            // Dispatch event to refresh preview
            $this->dispatch('cssUpdated');

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad pobierania: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * View specific CSS/JS file content.
     * ETAP_07f_P3: Sets file path for FTP editing.
     */
    public function viewFile(string $url): void
    {
        $shop = $this->shop;
        if (!$shop) {
            $this->errorMessage = 'Brak sklepu';
            return;
        }

        $this->isLoading = true;

        try {
            // Convert URL to FTP path
            $fetcher = app(PrestaShopCssFetcher::class);
            $ftpPath = $fetcher->urlToFtpPath($url, $shop->url);

            // Determine file type
            $isJs = str_ends_with(strtolower($url), '.js');
            $this->editingType = $isJs ? 'js' : 'css';
            $this->editingFilePath = $ftpPath;
            $this->editingFileName = basename($url);

            // Load via FTP for editable content
            if ($isJs) {
                $result = $fetcher->getCustomJs($shop, $ftpPath);
            } else {
                $result = $fetcher->getCustomCss($shop, $ftpPath);
            }

            if ($result['success']) {
                $this->editorContent = $result['content'] ?? '';
                $this->originalContent = $this->editorContent;
                $this->activeTab = 'editor';
                $this->successMessage = 'Plik zaladowany: ' . $this->editingFileName;
            } else {
                // Fallback: try HTTP fetch (read-only)
                $discovery = app(PrestaShopAssetDiscovery::class);
                $content = $discovery->fetchCssContent($url);

                if ($content) {
                    $this->editorContent = $content;
                    $this->originalContent = $content;
                    $this->activeTab = 'editor';
                    $this->successMessage = 'Plik zaladowany (tylko odczyt): ' . $this->editingFileName;
                } else {
                    $this->errorMessage = $result['error'] ?? 'Nie udalo sie pobrac pliku';
                }
            }

        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get shop instance.
     */
    #[Computed]
    public function shop(): ?PrestaShopShop
    {
        if (!$this->shopId) {
            return null;
        }

        return PrestaShopShop::find($this->shopId);
    }

    /**
     * Check if content has unsaved changes.
     */
    #[Computed]
    public function isDirty(): bool
    {
        return $this->editorContent !== $this->originalContent;
    }

    /**
     * Get scanned CSS files from shop model.
     * ETAP_07f_P3: Files from FTP scan.
     */
    #[Computed]
    public function scannedCssFiles(): array
    {
        $shop = $this->shop;
        if (!$shop) {
            return [];
        }

        return $shop->css_files ?? [];
    }

    /**
     * Get scanned JS files from shop model.
     * ETAP_07f_P3: Files from FTP scan.
     */
    #[Computed]
    public function scannedJsFiles(): array
    {
        $shop = $this->shop;
        if (!$shop) {
            return [];
        }

        return $shop->js_files ?? [];
    }

    /**
     * Check if shop has scanned files.
     */
    #[Computed]
    public function hasScannedFiles(): bool
    {
        return !empty($this->scannedCssFiles) || !empty($this->scannedJsFiles);
    }

    /**
     * Get CSS files grouped by category.
     * ETAP_07f_P3: Groups scanned files for UI display.
     */
    #[Computed]
    public function cssFilesByCategory(): array
    {
        $grouped = [
            'theme' => [],
            'custom' => [],
            'module' => [],
            'other' => [],
        ];

        foreach ($this->scannedCssFiles as $file) {
            // 'type' is used in AddShop, 'category' is fallback
            $category = $file['type'] ?? $file['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $file;
        }

        return $grouped;
    }

    /**
     * Get JS files grouped by category.
     * ETAP_07f_P3: Groups scanned files for UI display.
     */
    #[Computed]
    public function jsFilesByCategory(): array
    {
        $grouped = [
            'theme' => [],
            'custom' => [],
            'module' => [],
            'other' => [],
        ];

        foreach ($this->scannedJsFiles as $file) {
            // 'type' is used in AddShop, 'category' is fallback
            $category = $file['type'] ?? $file['category'] ?? 'other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $file;
        }

        return $grouped;
    }

    /**
     * Get total CSS files count.
     */
    #[Computed]
    public function totalCssCount(): int
    {
        // Priority: scanned files > asset manifest
        if ($this->hasScannedFiles) {
            return count($this->scannedCssFiles);
        }

        $count = 0;
        foreach ($this->assetManifest as $category => $types) {
            $count += count($types['css'] ?? []);
        }
        return $count;
    }

    /**
     * Get total JS files count.
     */
    #[Computed]
    public function totalJsCount(): int
    {
        // Priority: scanned files > asset manifest
        if ($this->hasScannedFiles) {
            return count($this->scannedJsFiles);
        }

        $count = 0;
        foreach ($this->assetManifest as $category => $types) {
            $count += count($types['js'] ?? []);
        }
        return $count;
    }

    /**
     * Edit a specific file by URL.
     * ETAP_07f_P3: Quick access from files list.
     */
    public function editFile(string $url): void
    {
        $this->viewFile($url);
    }

    public function render(): View
    {
        return view('livewire.products.visual-description.partials.css-js-editor-modal');
    }
}
