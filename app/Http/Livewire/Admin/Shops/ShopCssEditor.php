<?php

namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopCssFetcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ShopCssEditor - CSS File Editor for PrestaShop shops.
 *
 * ETAP_07h FAZA 8: Allows direct editing of PrestaShop CSS files
 * from PPM admin panel without FTP client.
 *
 * Features:
 * - Load CSS from PrestaShop via FTP
 * - Edit with syntax highlighting (CodeMirror)
 * - Automatic backup before save
 * - Restore from backup
 * - Lock mechanism for concurrent editing
 *
 * @package App\Http\Livewire\Admin\Shops
 */
class ShopCssEditor extends Component
{
    /**
     * Lock configuration.
     */
    public const LOCK_PREFIX = 'shop_css_editor_';
    public const LOCK_TIMEOUT_SECONDS = 300; // 5 minutes

    /**
     * Shop ID being edited.
     */
    public int $shopId;

    /**
     * Shop model.
     */
    public ?PrestaShopShop $shop = null;

    /**
     * Current CSS content in editor.
     */
    public string $cssContent = '';

    /**
     * Original CSS content (for change detection).
     */
    public string $originalContent = '';

    /**
     * CSS file path on server.
     */
    public ?string $filePath = null;

    /**
     * Last modification timestamp.
     */
    public ?string $lastModified = null;

    /**
     * Loading state.
     */
    public bool $isLoading = false;

    /**
     * Saving state.
     */
    public bool $isSaving = false;

    /**
     * Error message.
     */
    public ?string $errorMessage = null;

    /**
     * Success message.
     */
    public ?string $successMessage = null;

    /**
     * Available backups list.
     */
    public array $backups = [];

    /**
     * Editor lock info.
     */
    public ?array $lockInfo = null;

    /**
     * Mount component.
     */
    public function mount(int $shopId): void
    {
        $this->shopId = $shopId;
        $this->shop = PrestaShopShop::find($shopId);

        if (!$this->shop) {
            $this->errorMessage = 'Sklep o ID ' . $shopId . ' nie zostal znaleziony.';
            return;
        }

        // Check FTP configuration
        if (!$this->isFtpConfigured()) {
            $this->errorMessage = 'FTP nie jest skonfigurowane dla tego sklepu. Skonfiguruj FTP w ustawieniach sklepu.';
            return;
        }

        // Check/acquire lock
        if (!$this->acquireLock()) {
            return;
        }

        // Load CSS content
        $this->loadCss();
    }

    /**
     * Check if FTP is configured for shop.
     */
    public function isFtpConfigured(): bool
    {
        $ftpConfig = $this->shop->ftp_config ?? [];
        return !empty($ftpConfig['host']) && !empty($ftpConfig['user']);
    }

    /**
     * Acquire edit lock.
     */
    protected function acquireLock(): bool
    {
        $lockKey = self::LOCK_PREFIX . $this->shopId;

        // Check existing lock
        $existingLock = Cache::get($lockKey);
        if ($existingLock && $existingLock['user_id'] !== auth()->id()) {
            $this->lockInfo = $existingLock;
            $this->errorMessage = sprintf(
                'Plik jest edytowany przez innego uzytkownika (od %s). Sprobuj ponownie za kilka minut.',
                $existingLock['locked_at'] ?? 'unknown'
            );
            return false;
        }

        // Acquire lock
        Cache::put($lockKey, [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'Unknown',
            'locked_at' => now()->format('Y-m-d H:i:s'),
        ], self::LOCK_TIMEOUT_SECONDS);

        return true;
    }

    /**
     * Release edit lock.
     */
    protected function releaseLock(): void
    {
        $lockKey = self::LOCK_PREFIX . $this->shopId;
        $existingLock = Cache::get($lockKey);

        // Only release if we own the lock
        if ($existingLock && $existingLock['user_id'] === auth()->id()) {
            Cache::forget($lockKey);
        }
    }

    /**
     * Load CSS from server.
     */
    public function loadCss(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $cssFetcher = app(PrestaShopCssFetcher::class);
            $result = $cssFetcher->getCustomCss($this->shop);

            if ($result['success']) {
                $this->cssContent = $result['content'] ?? '';
                $this->originalContent = $this->cssContent;
                $this->filePath = $result['filePath'] ?? null;
                $this->lastModified = now()->format('Y-m-d H:i:s');

                Log::info('ShopCssEditor: CSS loaded successfully', [
                    'shop_id' => $this->shopId,
                    'file_path' => $this->filePath,
                    'size' => strlen($this->cssContent),
                ]);

                // Notify JS to update CodeMirror editor
                $this->dispatch('css-loaded');
            } else {
                $this->errorMessage = $result['error'] ?? 'Nie udalo sie zaladowac pliku CSS.';
                Log::error('ShopCssEditor: Failed to load CSS', [
                    'shop_id' => $this->shopId,
                    'error' => $result['error'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad ladowania CSS: ' . $e->getMessage();
            Log::error('ShopCssEditor: Exception loading CSS', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Save CSS to server.
     */
    public function saveCss(): void
    {
        $this->isSaving = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            // Validate CSS syntax (basic check)
            $syntaxError = $this->validateCssSyntax($this->cssContent);
            if ($syntaxError) {
                $this->errorMessage = 'Blad skladni CSS: ' . $syntaxError;
                return;
            }

            // Refresh lock
            if (!$this->acquireLock()) {
                return;
            }

            $cssFetcher = app(PrestaShopCssFetcher::class);

            // Save with backup (PrestaShopCssFetcher creates backup automatically)
            $result = $cssFetcher->saveCustomCss($this->shop, $this->cssContent, $this->filePath);

            if ($result['success']) {
                $this->originalContent = $this->cssContent;
                $this->lastModified = now()->format('Y-m-d H:i:s');
                $this->successMessage = 'CSS zapisany pomyslnie!';

                // Refresh shop data
                $this->shop->refresh();

                Log::info('ShopCssEditor: CSS saved successfully', [
                    'shop_id' => $this->shopId,
                    'file_path' => $this->filePath,
                    'size' => strlen($this->cssContent),
                ]);

                $this->dispatch('css-saved');
            } else {
                $this->errorMessage = $result['error'] ?? 'Nie udalo sie zapisac pliku CSS.';
                Log::error('ShopCssEditor: Failed to save CSS', [
                    'shop_id' => $this->shopId,
                    'error' => $result['error'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Blad zapisywania CSS: ' . $e->getMessage();
            Log::error('ShopCssEditor: Exception saving CSS', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Basic CSS syntax validation.
     */
    protected function validateCssSyntax(string $css): ?string
    {
        // Check for balanced braces
        $openBraces = substr_count($css, '{');
        $closeBraces = substr_count($css, '}');

        if ($openBraces !== $closeBraces) {
            return "Niezbalansowane nawiasy klamrowe: { = {$openBraces}, } = {$closeBraces}";
        }

        // Check for unclosed comments
        $openComments = substr_count($css, '/*');
        $closeComments = substr_count($css, '*/');

        if ($openComments !== $closeComments) {
            return "Niezamkniete komentarze: /* = {$openComments}, */ = {$closeComments}";
        }

        return null;
    }

    /**
     * Refresh CSS from server (discard changes).
     */
    public function refreshCss(): void
    {
        if ($this->hasUnsavedChanges()) {
            // Dispatch confirmation event
            $this->dispatch('confirm-refresh');
            return;
        }

        $this->loadCss();
    }

    /**
     * Confirm refresh (discard changes).
     */
    #[On('confirm-refresh-accepted')]
    public function confirmRefresh(): void
    {
        $this->loadCss();
    }

    /**
     * Check if there are unsaved changes.
     */
    #[Computed]
    public function hasUnsavedChanges(): bool
    {
        return $this->cssContent !== $this->originalContent;
    }

    /**
     * Get CSS content size in KB.
     */
    #[Computed]
    public function cssSize(): string
    {
        $bytes = strlen($this->cssContent);
        return number_format($bytes / 1024, 2) . ' KB';
    }

    /**
     * Get line count.
     */
    #[Computed]
    public function lineCount(): int
    {
        return substr_count($this->cssContent, "\n") + 1;
    }

    /**
     * Cleanup on component destroy.
     */
    public function dehydrate(): void
    {
        // Note: Don't release lock on every request, only on explicit close
    }

    /**
     * Release lock and go back.
     */
    public function closeEditor(): void
    {
        $this->releaseLock();
        $this->redirect(route('admin.shops.add', ['edit' => $this->shopId]));
    }

    /**
     * Render component.
     */
    public function render()
    {
        return view('livewire.admin.shops.shop-css-editor')
            ->layout('layouts.admin', [
                'title' => 'Edytor CSS - ' . ($this->shop->name ?? 'Sklep'),
                'breadcrumb' => 'Edytor CSS',
            ]);
    }
}
