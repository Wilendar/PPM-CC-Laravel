<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CSS Sync Orchestrator for Unified Visual Editor.
 *
 * ETAP_07h v2.0 CSS-FIRST ARCHITECTURE:
 * 1. Lock mechanism (Cache::lock) - prevent concurrent sync
 * 2. Backup existing CSS before sync
 * 3. FULL REPLACE strategy (not append) with markers
 * 4. Rollback on error
 *
 * CSS is delivered to: /themes/{theme}/css/uve-custom.css
 * Uses markers: @uve-styles-start / @uve-styles-end
 *
 * @package App\Services\VisualEditor
 */
class CssSyncOrchestrator
{
    /**
     * CSS markers for FULL REPLACE strategy.
     */
    public const CSS_MARKER_START = '/* @uve-styles-start */';
    public const CSS_MARKER_END = '/* @uve-styles-end */';
    public const CSS_SPECIFICITY_WRAPPER = '.uve-content';

    /**
     * Lock configuration.
     */
    public const LOCK_PREFIX = 'uve_css_sync_';
    public const LOCK_TIMEOUT_SECONDS = 60;

    /**
     * Sync status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_FETCHING = 'fetching';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_UPLOADING = 'uploading';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Dependencies.
     */
    private PrestaShopCssFetcher $cssFetcher;
    private CssPropertyMapper $propertyMapper;
    private CssRuleGenerator $ruleGenerator;

    /**
     * Current sync state.
     */
    private array $syncState = [
        'status' => self::STATUS_PENDING,
        'step' => '',
        'progress' => 0,
        'message' => '',
        'error' => null,
        'details' => [],
    ];

    public function __construct(
        ?PrestaShopCssFetcher $cssFetcher = null,
        ?CssPropertyMapper $propertyMapper = null,
        ?CssRuleGenerator $ruleGenerator = null
    ) {
        $this->cssFetcher = $cssFetcher ?? app(PrestaShopCssFetcher::class);
        $this->propertyMapper = $propertyMapper ?? new CssPropertyMapper();
        $this->ruleGenerator = $ruleGenerator ?? new CssRuleGenerator($this->propertyMapper);
    }

    /**
     * Synchronize CSS for a product description.
     *
     * ETAP_07h v2.0 CSS-FIRST Workflow:
     * 1. Acquire lock (prevent concurrent sync)
     * 2. Backup existing CSS
     * 3. Generate new CSS from cssRules
     * 4. FULL REPLACE within markers
     * 5. Upload to uve-custom.css
     * 6. Rollback on error
     *
     * @param ProductDescription $description Product description with css_rules
     * @param bool $forceFetch Force re-fetch CSS from server
     * @return array Sync result
     */
    public function syncProductDescription(ProductDescription $description, bool $forceFetch = false): array
    {
        $this->resetState();
        $shopId = $description->shop_id;
        $productId = $description->product_id;
        $lockKey = self::LOCK_PREFIX . $shopId;
        $backup = null;

        Log::info('CssSyncOrchestrator v2.0: Starting CSS-First sync', [
            'product_id' => $productId,
            'shop_id' => $shopId,
        ]);

        // Get shop configuration first
        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            return $this->failSync('Nie znaleziono sklepu o ID: ' . $shopId);
        }

        // Check if shop has CSS sync enabled (FTP required in v2.0)
        if (!$this->isCssSyncEnabled($shop)) {
            return $this->failSync('CSS-First v2.0: FTP nie jest skonfigurowane. CSS sync zablokowany.');
        }

        // v2.0: Acquire lock to prevent concurrent sync
        $lock = Cache::lock($lockKey, self::LOCK_TIMEOUT_SECONDS);

        if (!$lock->get()) {
            Log::warning('CssSyncOrchestrator: Lock not acquired, sync in progress', [
                'shop_id' => $shopId,
            ]);
            return $this->failSync('Synchronizacja CSS juz trwa dla tego sklepu. Sprobuj ponownie za chwile.');
        }

        try {
            // Step 1: Fetch existing CSS (for backup and merge)
            // CRITICAL FIX: ALWAYS force fetch from FTP to get current file state!
            // Using cached content caused data loss when user manually uploaded CSS.
            // The cache might not reflect the actual file on server.
            $this->updateState(self::STATUS_FETCHING, 'Pobieranie CSS z serwera...', 10);
            $fetchResult = $this->fetchExistingCssWithValidation($shop, true);

            if (!$fetchResult['success']) {
                $lock->release();
                return $this->failSync($fetchResult['error']);
            }

            $existingCss = $fetchResult['content'];

            // v2.2: CRITICAL DEBUG - Log fetched CSS characteristics to detect stale cache
            $hasRootVar = str_contains($existingCss, ':root');
            $hasBtnPrimary = str_contains($existingCss, '.btn-primary');
            $hasMarkers = str_contains($existingCss, self::CSS_MARKER_START);
            $first100Chars = substr(trim($existingCss), 0, 100);

            Log::info('CssSyncOrchestrator: Fetched existing CSS analysis', [
                'shop_id' => $shopId,
                'size' => strlen($existingCss),
                'has_root_vars' => $hasRootVar,
                'has_btn_primary' => $hasBtnPrimary,
                'has_uve_markers' => $hasMarkers,
                'first_100_chars' => $first100Chars,
            ]);

            // v2.0: Create backup before modification
            $backup = $existingCss;
            Log::debug('CssSyncOrchestrator: Backup created', [
                'shop_id' => $shopId,
                'backup_size' => strlen($backup),
            ]);

            // Step 2: Generate CSS from css_rules (v2.0 format)
            $this->updateState(self::STATUS_GENERATING, 'Generowanie CSS z reguł...', 40);
            $generatedCss = $this->generateCssFromRulesV2($description);

            // v2.2: Validate generated CSS for leaks
            $leakValidation = $this->validateCssForLeaks($generatedCss);
            if (!$leakValidation['valid']) {
                Log::warning('CssSyncOrchestrator: CSS leak detected in generated styles', [
                    'product_id' => $productId,
                    'shop_id' => $shopId,
                    'leaks' => $leakValidation['leaks'],
                ]);
                // Don't fail, but log warning - leaks are scoped anyway
            }

            if (empty($generatedCss)) {
                $lock->release();
                return $this->skipSync('Brak reguł CSS do synchronizacji');
            }

            // Step 3: FULL REPLACE within markers (v2.0 strategy)
            $this->updateState(self::STATUS_GENERATING, 'Zastepowanie sekcji CSS...', 60);
            $mergedCss = $this->fullReplaceWithMarkers($existingCss, $generatedCss);

            // v2.2: CRITICAL DEBUG - Log merged CSS characteristics
            $mergedHasRootVar = str_contains($mergedCss, ':root');
            $mergedHasBtnPrimary = str_contains($mergedCss, '.btn-primary');
            $mergedFirst100Chars = substr(trim($mergedCss), 0, 100);

            Log::info('CssSyncOrchestrator: Merged CSS analysis', [
                'shop_id' => $shopId,
                'existing_size' => strlen($existingCss),
                'generated_size' => strlen($generatedCss),
                'merged_size' => strlen($mergedCss),
                'merged_has_root_vars' => $mergedHasRootVar,
                'merged_has_btn_primary' => $mergedHasBtnPrimary,
                'merged_first_100_chars' => $mergedFirst100Chars,
            ]);

            // v2.2: Safeguard - check for potential data loss
            $dataLossCheck = $this->validateNoDataLoss($existingCss, $mergedCss);
            if (!$dataLossCheck['safe']) {
                Log::error('CssSyncOrchestrator: CRITICAL - Potential data loss detected', [
                    'product_id' => $productId,
                    'shop_id' => $shopId,
                    'existing_size' => strlen($existingCss),
                    'merged_size' => strlen($mergedCss),
                    'reason' => $dataLossCheck['reason'],
                ]);
                $lock->release();
                return $this->failSync($dataLossCheck['reason']);
            }

            // Step 4: Upload CSS
            $this->updateState(self::STATUS_UPLOADING, 'Wysylanie CSS na serwer...', 80);
            $uploadResult = $this->uploadCss($shop, $mergedCss);

            if (!$uploadResult['success']) {
                // v2.0: Rollback on error
                $this->rollbackCss($shop, $backup);
                $lock->release();
                return $this->failSync($uploadResult['error'] ?? 'Blad wysylania CSS');
            }

            // Step 5: Update description css_mode
            $description->update([
                'css_mode' => 'external',
                'css_synced_at' => now(),
            ]);

            // v2.2: CRITICAL FIX - Update cache with the uploaded CSS
            // This ensures next operations won't use stale cache
            $shop->update([
                'cached_custom_css' => $mergedCss,
                'css_last_fetched_at' => now(),
            ]);
            Log::debug('CssSyncOrchestrator: Cache updated after successful upload', [
                'shop_id' => $shopId,
                'cache_size' => strlen($mergedCss),
            ]);

            $lock->release();

            // Success
            return $this->successSync([
                'generated_size' => strlen($generatedCss),
                'merged_size' => strlen($mergedCss),
                'upload_result' => $uploadResult,
                'css_mode' => 'external',
            ]);

        } catch (\Throwable $e) {
            // v2.0: Rollback on exception
            if ($backup !== null && $shop) {
                $this->rollbackCss($shop, $backup);
            }

            $lock->release();

            Log::error('CssSyncOrchestrator v2.0: Exception during sync', [
                'product_id' => $productId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failSync($e->getMessage());
        }
    }

    /**
     * Generate CSS from description's css_rules (v2.0 format).
     *
     * Uses per-style hash naming and wraps in .uve-content for specificity.
     * Smart filter: only skips FIXED PIXEL values, keeps percentage/viewport units.
     *
     * @param ProductDescription $description
     * @return string Generated CSS
     */
    protected function generateCssFromRulesV2(ProductDescription $description): string
    {
        $cssRules = $description->css_rules ?? [];

        if (empty($cssRules)) {
            return '';
        }

        // ETAP_07h FIX: Global layout fix for themes that override .tab-pane display
        // This ensures full-width layout on all PrestaShop themes (warehouse, kayo, etc.)
        $css = $this->getLayoutFixCss();

        $css .= "/* UVE CSS - Product {$description->product_id} Shop {$description->shop_id} */\n";
        $css .= "/* Generated: " . now()->toIso8601String() . " */\n\n";

        foreach ($cssRules as $selector => $properties) {
            if (empty($properties)) {
                continue;
            }

            // Wrap selector with specificity wrapper
            $wrappedSelector = self::CSS_SPECIFICITY_WRAPPER . ' ' . $selector;

            $css .= "{$wrappedSelector} {\n";
            foreach ($properties as $prop => $value) {
                // Skip layout-breaking properties with FIXED PIXEL values
                if ($this->shouldSkipCssProperty($prop, $value)) {
                    continue;
                }
                $css .= "  {$prop}: {$value};\n";
            }
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Check if a CSS property should be skipped from output.
     *
     * Smart filter: only skip FIXED PIXEL values for dimension/position properties.
     * KEEPS: percentage (100%), viewport (vw/vh), auto, fit-content, max-content values.
     *
     * @param string $prop CSS property name
     * @param string $value CSS value
     * @return bool True if property should be skipped
     */
    protected function shouldSkipCssProperty(string $prop, string $value): bool
    {
        // Properties that are layout-breaking only when using FIXED pixel values
        $dimensionProperties = [
            'width', 'height', 'min-width', 'max-width', 'min-height', 'max-height',
        ];

        // Properties that should ALWAYS be skipped (computed values)
        $alwaysSkipProperties = [
            'position', 'top', 'right', 'bottom', 'left',
            'background-position', 'background-size',
        ];

        // Always skip certain properties
        if (in_array($prop, $alwaysSkipProperties)) {
            return true;
        }

        // For dimension properties, only skip FIXED PIXEL values
        if (in_array($prop, $dimensionProperties)) {
            // KEEP: percentage, viewport units, auto, fit-content, max-content, min-content
            $keepPatterns = ['%', 'vw', 'vh', 'vmin', 'vmax', 'auto', 'fit-content', 'max-content', 'min-content'];
            foreach ($keepPatterns as $pattern) {
                if (stripos($value, $pattern) !== false) {
                    return false; // KEEP this value
                }
            }
            // SKIP: fixed pixel values like "131px", "39px"
            if (preg_match('/^\d+(\.\d+)?px$/', $value)) {
                return true;
            }
        }

        // For display/flex properties - skip computed defaults, keep intentional values
        if ($prop === 'display') {
            // Skip only 'block' (default) - keep flex, grid, inline-block, etc.
            return $value === 'block';
        }

        if (in_array($prop, ['flex-direction', 'gap'])) {
            // These are usually intentional - keep them
            return false;
        }

        return false;
    }

    /**
     * Validate CSS for potential "leaks" - selectors that could affect theme elements.
     *
     * ETAP_07h v2.2: CSS ISOLATION - zabezpieczenie przed przeciekami.
     * Wykrywa selektory które mogą wpływać na elementy motywu poza opisem produktu.
     *
     * @param string $css CSS content to validate
     * @return array ['valid' => bool, 'leaks' => array of problematic selectors]
     */
    protected function validateCssForLeaks(string $css): array
    {
        $leaks = [];

        // Safe selector prefixes - selectors starting with these are OK
        $safePrefixes = [
            '.uve-content',
            '.uve-e',                    // UVE element classes
            '.uve-s',                    // UVE style classes
            '.product-description',
            '#product-description',
            '#product .tabs',            // Scoped to product page tabs
            '.tab-pane.active > .product-description',
            '.tab-pane.active > * > .product-description',
            '/* ',                       // Comments
        ];

        // Dangerous global selectors that MUST NOT appear unscoped
        $dangerousPatterns = [
            '/^:root\s*\{/',              // Global :root variables
            '/^\.container\s*\{/',        // Unscoped .container
            '/^\.btn/',                   // Any button class
            '/^\.nav/',                   // Navigation elements
            '/^\.breadcrumb/',            // Breadcrumbs
            '/^\.header/',                // Header elements
            '/^\.footer/',                // Footer elements
            '/^\.menu/',                  // Menu elements
            '/^body\s*\{/',               // Body styles
            '/^html\s*\{/',               // HTML styles
            '/^\*\s*\{/',                 // Universal selector
            '/^\.tab-content\s*>/',       // Unscoped tab-content
            '/^\.tab-pane\s*[,\{]/',      // Unscoped tab-pane
            '/^#product\s+\.container/',  // Product page container (too broad)
        ];

        // Extract all selectors from CSS
        preg_match_all('/([^\{\}]+)\s*\{[^\}]*\}/s', $css, $matches);

        foreach ($matches[1] as $selector) {
            $selector = trim($selector);

            // Skip empty or comment lines
            if (empty($selector) || str_starts_with($selector, '/*')) {
                continue;
            }

            // Check if selector is safe
            $isSafe = false;
            foreach ($safePrefixes as $prefix) {
                if (str_starts_with($selector, $prefix)) {
                    $isSafe = true;
                    break;
                }
            }

            if (!$isSafe) {
                // Check against dangerous patterns
                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $selector)) {
                        $leaks[] = [
                            'selector' => substr($selector, 0, 100),
                            'reason' => 'Matches dangerous pattern: ' . $pattern,
                        ];
                        break;
                    }
                }
            }
        }

        // Log if leaks found
        if (!empty($leaks)) {
            Log::warning('CssSyncOrchestrator: CSS leak validation found issues', [
                'leak_count' => count($leaks),
                'leaks' => array_slice($leaks, 0, 5), // Log first 5
            ]);
        }

        return [
            'valid' => empty($leaks),
            'leaks' => $leaks,
        ];
    }

    /**
     * Validate that CSS merge doesn't cause significant data loss.
     *
     * ETAP_07h v2.2: Safeguard against accidental CSS overwrite.
     * Detects scenarios where original CSS content would be lost.
     *
     * @param string $existingCss Original CSS from server
     * @param string $mergedCss CSS after merge
     * @return array{safe: bool, reason: ?string}
     */
    protected function validateNoDataLoss(string $existingCss, string $mergedCss): array
    {
        // Skip validation if existing CSS was empty (new file)
        if (empty(trim($existingCss))) {
            return ['safe' => true, 'reason' => null];
        }

        // Check 1: Merged CSS shouldn't be significantly smaller than original
        // (unless we're removing UVE section which could be large)
        $existingSize = strlen($existingCss);
        $mergedSize = strlen($mergedCss);

        // Calculate non-UVE content size in existing CSS
        $nonUveSize = $existingSize;
        $startPos = strpos($existingCss, self::CSS_MARKER_START);
        $endPos = strpos($existingCss, self::CSS_MARKER_END);
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $uveBlockSize = $endPos + strlen(self::CSS_MARKER_END) - $startPos;
            $nonUveSize = $existingSize - $uveBlockSize;
        }

        // If non-UVE content is substantial (>500 bytes) but merged is tiny, something is wrong
        if ($nonUveSize > 500 && $mergedSize < $nonUveSize * 0.5) {
            return [
                'safe' => false,
                'reason' => "Wykryto potencjalną utratę danych CSS. " .
                           "Oryginalne reguły spoza UVE ({$nonUveSize} B) byłyby utracone. " .
                           "Sprawdź zawartość pliku CSS na serwerze.",
            ];
        }

        // Check 2: Important theme selectors should be preserved
        $importantSelectors = ['.btn-primary', '.btn-secondary', '.btn-link'];
        $preservedSelectors = [];
        $lostSelectors = [];

        foreach ($importantSelectors as $selector) {
            $inExisting = str_contains($existingCss, $selector);
            $inMerged = str_contains($mergedCss, $selector);

            if ($inExisting && !$inMerged) {
                $lostSelectors[] = $selector;
            } elseif ($inExisting && $inMerged) {
                $preservedSelectors[] = $selector;
            }
        }

        if (!empty($lostSelectors)) {
            Log::warning('CssSyncOrchestrator: Important selectors would be lost', [
                'lost' => $lostSelectors,
                'preserved' => $preservedSelectors,
            ]);
            return [
                'safe' => false,
                'reason' => "Ważne selektory CSS byłyby utracone: " . implode(', ', $lostSelectors) . ". " .
                           "To może wskazywać na błąd w merge. " .
                           "Sprawdź zawartość pliku CSS na serwerze.",
            ];
        }

        return ['safe' => true, 'reason' => null];
    }

    /**
     * Get global layout fix CSS for PrestaShop themes.
     *
     * ETAP_07h FIX: Some themes (warehouse, etc.) override .tab-pane with display:block
     * which breaks the CSS grid layout. This CSS ensures full-width layout works.
     *
     * Also includes BASE PRODUCT DESCRIPTION STYLES from production theme (kayo)
     * that may be missing in other themes (warehouse, etc.)
     *
     * @return string Layout fix CSS
     */
    protected function getLayoutFixCss(): string
    {
        // ETAP_07h v2.2: CSS ISOLATION - zabezpieczenie przed "przeciekami" do innych elementów theme
        // WSZYSTKIE style MUSZĄ być scoped do selektorów opisu produktu!
        // ZAKAZ globalnych selektorów jak :root, .container, .tab-pane bez scopingu!
        return <<<CSS
/* === UVE CSS ISOLATION LAYER v2.2 === */
/* CRITICAL: All styles are SCOPED to product description to prevent theme leaks */
/* WARNING: DO NOT add unscoped selectors - they will affect breadcrumbs, menus, buttons! */

/* === SCOPED UVE VARIABLES (NOT global :root!) === */
/* Variables are scoped to .uve-content to avoid conflicts with theme variables */
.uve-content,
.product-description .rte-content {
  --uve-brand-color: #ef8248;
  --uve-brand-color-rgb: 239, 130, 72;
  --uve-inline-padding: 1rem;
  --uve-max-content-width: 1300px;
  --uve-block-breakout: 270px;
  --uve-max-text-width: 760px;
}

/* === SCOPED LAYOUT FIX === */
/* Container fix - ONLY for containers INSIDE product description */
.product-description .container,
.uve-content .container,
#product-description .container {
  max-width: 100% !important;
}

/* Grid layout ONLY for product description tab */
#product-description.tab-pane.active,
.tab-pane.active > .product-description,
.tab-pane.active > * > .product-description,
.product-description.active {
  display: grid !important;
  grid-template-columns:
    [row-start] minmax(var(--uve-inline-padding, 1rem), 1fr)
    [block-start] minmax(0, var(--uve-block-breakout, 270px))
    [text-start] min(var(--uve-max-text-width, 760px), 100% - 2 * var(--uve-inline-padding, 1rem))
    [text-end] minmax(0, var(--uve-block-breakout, 270px))
    [block-end] minmax(var(--uve-inline-padding, 1rem), 1fr)
    [row-end];
}

/* Full width for UVE content */
.product-description .uve-content,
.product-description .rte-content,
.product-description .rte-content .pd-base-grid {
  grid-column: 1 / -1;
  width: 100%;
}

/* === END SCOPED LAYOUT FIX === */

/* === SCOPED PRODUCT DESCRIPTION STYLES === */
/* All pd-* classes are scoped to .uve-content or .product-description */

/* Brand background - SCOPED to prevent affecting theme elements */
.uve-content .bg-brand,
.product-description .bg-brand {
  background-color: var(--uve-brand-color);
  grid-column: 1 / -1;
}

/* Product intro - SCOPED */
.uve-content .pd-intro,
.product-description .pd-intro {
  display: grid;
  grid-column: 1 / -1;
  justify-content: center;
}

/* Product intro heading - SCOPED */
.uve-content .pd-intro__heading,
.uve-content .pd-model,
.product-description .pd-intro__heading,
.product-description .pd-model {
  display: grid;
  grid-column: text;
  font-size: 56px;
  font-weight: 700;
  gap: 16px;
  margin-bottom: 8px;
  padding: 96px 0 128px;
}

/* Product intro text - SCOPED */
.uve-content .pd-intro__text,
.product-description .pd-intro__text {
  grid-column: text;
  margin-bottom: 12.8px;
}

/* Product model type - SCOPED */
.uve-content .pd-model__type,
.product-description .pd-model__type {
  display: block;
  grid-column: 2 / -1;
  font-size: 39px;
  font-weight: 400;
}

/* Product model name - SCOPED */
.uve-content .pd-model__name,
.product-description .pd-model__name {
  display: block;
  grid-column: 1 / -1;
  font-size: 56px;
  font-weight: 800;
}

/* Product cover - SCOPED */
.uve-content .pd-cover,
.product-description .pd-cover {
  grid-column: 1 / -1;
}

/* Product cover picture with gradient - SCOPED */
.uve-content .pd-cover__picture,
.product-description .pd-cover__picture {
  display: block;
  background: linear-gradient(rgb(246, 246, 246) 70%, var(--uve-brand-color) 70%);
}

.uve-content .pd-cover__picture img,
.product-description .pd-cover__picture img {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 0 auto;
}

/* Asset list - SCOPED */
.uve-content .pd-asset-list,
.product-description .pd-asset-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 48px;
  padding: 96px 32px;
  margin: 0 auto;
  max-width: 1300px;
  list-style: none;
  color: #fff;
}

.uve-content .pd-asset-list li,
.product-description .pd-asset-list li {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 9px;
  font-size: 18px;
}

.uve-content .pd-asset-list li b,
.product-description .pd-asset-list li b {
  font-size: 32px;
  font-weight: 700;
}

/* === END SCOPED PRODUCT DESCRIPTION STYLES === */

/* === SCOPED NAV TABS FIX === */
/* This fix is INTENTIONALLY scoped to #product page tabs only */
/* It fixes padding formula in warehouse theme without affecting other pages */
#product .tabs .nav-tabs {
  padding-inline: max(var(--inline-padding, 1rem), (100% - var(--max-content-width, 1300px)) / 2) !important;
}

/* === END SCOPED NAV TABS FIX === */

CSS;
    }

    /**
     * FULL REPLACE strategy: replace content between markers.
     *
     * If markers don't exist, append at end.
     *
     * @param string $existingCss Existing CSS content
     * @param string $newCss New CSS to inject
     * @return string Merged CSS
     */
    protected function fullReplaceWithMarkers(string $existingCss, string $newCss): string
    {
        $startMarker = self::CSS_MARKER_START;
        $endMarker = self::CSS_MARKER_END;

        // Wrap new CSS with markers
        $markedCss = "\n{$startMarker}\n{$newCss}{$endMarker}\n";

        // Check if markers exist
        $startPos = strpos($existingCss, $startMarker);
        $endPos = strpos($existingCss, $endMarker);

        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            // FULL REPLACE: Remove everything between markers (inclusive) and insert new
            $before = substr($existingCss, 0, $startPos);
            $after = substr($existingCss, $endPos + strlen($endMarker));

            return $before . $markedCss . $after;
        }

        // No markers found - append at end
        return $existingCss . "\n" . $markedCss;
    }

    /**
     * Rollback CSS to backup state.
     *
     * @param PrestaShopShop $shop
     * @param string $backupCss
     */
    protected function rollbackCss(PrestaShopShop $shop, string $backupCss): void
    {
        try {
            Log::warning('CssSyncOrchestrator: Rolling back CSS', [
                'shop_id' => $shop->id,
                'backup_size' => strlen($backupCss),
            ]);

            $this->uploadCss($shop, $backupCss);

            Log::info('CssSyncOrchestrator: Rollback completed', [
                'shop_id' => $shop->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('CssSyncOrchestrator: Rollback FAILED', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Synchronize CSS for multiple products (bulk sync).
     *
     * @param array $descriptions Array of ProductDescription
     * @param PrestaShopShop $shop Target shop
     * @return array Bulk sync results
     */
    public function syncBulk(array $descriptions, PrestaShopShop $shop): array
    {
        $results = [];
        $allGeneratedCss = [];

        Log::info('CssSyncOrchestrator: Starting bulk sync', [
            'shop_id' => $shop->id,
            'count' => count($descriptions),
        ]);

        // Step 1: Fetch existing CSS once (ALWAYS force fetch!)
        // CRITICAL FIX: Must force fetch to get current file state from server
        $fetchResult = $this->fetchExistingCssWithValidation($shop, true);
        if (!$fetchResult['success']) {
            return [
                'success' => false,
                'status' => self::STATUS_FAILED,
                'error' => $fetchResult['error'],
            ];
        }
        $existingCss = $fetchResult['content'];

        // Step 2: Generate CSS for all products
        foreach ($descriptions as $description) {
            $blocks = $description->blocks_v2 ?? $description->blocks ?? [];
            $productCss = $this->generateCssFromBlocks($blocks, $shop->id, $description->product_id);

            if (!empty($productCss)) {
                $allGeneratedCss[] = $productCss;
            }
        }

        if (empty($allGeneratedCss)) {
            return [
                'success' => true,
                'status' => self::STATUS_SKIPPED,
                'message' => 'Brak styli do synchronizacji',
            ];
        }

        // Step 3: Combine all generated CSS
        $combinedGeneratedCss = implode("\n\n", $allGeneratedCss);

        // Step 4: FULL REPLACE with markers (consistent with syncProductDescription)
        // CRITICAL FIX: Previously used mergeCss() which had different marker system
        $mergedCss = $this->fullReplaceWithMarkers($existingCss, $combinedGeneratedCss);

        // Step 5: Upload
        $uploadResult = $this->uploadCss($shop, $mergedCss);

        // v2.2: CRITICAL FIX - Update cache after successful upload
        if ($uploadResult['success']) {
            $shop->update([
                'cached_custom_css' => $mergedCss,
                'css_last_fetched_at' => now(),
            ]);
            Log::debug('CssSyncOrchestrator: Bulk sync cache updated', [
                'shop_id' => $shop->id,
                'cache_size' => strlen($mergedCss),
            ]);
        }

        return [
            'success' => $uploadResult['success'],
            'status' => $uploadResult['success'] ? self::STATUS_SUCCESS : self::STATUS_FAILED,
            'products_processed' => count($descriptions),
            'css_rules_generated' => count($allGeneratedCss),
            'merged_size' => strlen($mergedCss),
            'upload_result' => $uploadResult,
        ];
    }

    /**
     * Preview generated CSS without uploading.
     *
     * @param ProductDescription $description
     * @return array Preview result
     */
    public function previewCss(ProductDescription $description): array
    {
        $blocks = $description->blocks_v2 ?? $description->blocks ?? [];
        $generatedCss = $this->generateCssFromBlocks($blocks, $description->shop_id, $description->product_id);

        // Parse into rules for display
        $rules = $this->ruleGenerator->parseRules($generatedCss);

        return [
            'css' => $generatedCss,
            'rules_count' => count($rules),
            'rules' => $rules,
            'size' => strlen($generatedCss),
        ];
    }

    /**
     * Remove product CSS from shop.
     *
     * @param ProductDescription $description
     * @return array Result
     */
    public function removeCss(ProductDescription $description): array
    {
        $shop = PrestaShopShop::find($description->shop_id);
        if (!$shop) {
            return ['success' => false, 'error' => 'Shop not found'];
        }

        try {
            // Fetch existing CSS
            $existingCss = $this->fetchExistingCss($shop, true);

            // Remove UVE section for this product
            $cleanedCss = $this->ruleGenerator->removeUveSection($existingCss);

            // Upload cleaned CSS
            $uploadResult = $this->uploadCss($shop, $cleanedCss);

            return [
                'success' => $uploadResult['success'],
                'message' => $uploadResult['success'] ? 'CSS usuniete' : $uploadResult['error'],
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if CSS sync is enabled for shop.
     */
    private function isCssSyncEnabled(PrestaShopShop $shop): bool
    {
        // Check FTP configuration
        if (!$shop->ftp_config || empty($shop->ftp_config['host'])) {
            return false;
        }

        // Check if CSS sync is explicitly disabled
        $settings = $shop->settings ?? [];
        if (isset($settings['css_sync_enabled']) && !$settings['css_sync_enabled']) {
            return false;
        }

        return true;
    }

    /**
     * Fetch existing CSS from shop with validation.
     *
     * ETAP_07h v2.2: Returns error instead of empty string on failure
     * to prevent accidental overwrite of existing CSS.
     *
     * @param PrestaShopShop $shop
     * @param bool $forceFetch
     * @return array{success: bool, content: string, error: ?string}
     */
    private function fetchExistingCssWithValidation(PrestaShopShop $shop, bool $forceFetch = false): array
    {
        // Try to get from cache first (if not forcing refresh)
        if (!$forceFetch && $shop->cached_custom_css) {
            Log::debug('CssSyncOrchestrator: Using cached CSS', [
                'shop_id' => $shop->id,
                'size' => strlen($shop->cached_custom_css),
            ]);
            return [
                'success' => true,
                'content' => $shop->cached_custom_css,
                'error' => null,
            ];
        }

        // Fetch via FTP
        $result = $this->cssFetcher->getCustomCss($shop);

        if ($result['success']) {
            $content = $result['content'] ?? '';

            Log::info('CssSyncOrchestrator: Fetched CSS from server', [
                'shop_id' => $shop->id,
                'size' => strlen($content),
            ]);

            return [
                'success' => true,
                'content' => $content,
                'error' => null,
            ];
        }

        // v2.2: CRITICAL FIX - DO NOT return empty string on failure!
        // This was causing loss of existing CSS when FTP fetch failed.
        Log::error('CssSyncOrchestrator: CRITICAL - Failed to fetch existing CSS, sync blocked', [
            'shop_id' => $shop->id,
            'error' => $result['error'] ?? 'Unknown error',
        ]);

        return [
            'success' => false,
            'content' => '',
            'error' => 'Nie można pobrać istniejącego pliku CSS z serwera. ' .
                       'Synchronizacja zablokowana aby uniknąć utraty danych. ' .
                       'Sprawdź połączenie FTP. Błąd: ' . ($result['error'] ?? 'Unknown'),
        ];
    }

    /**
     * Fetch existing CSS from shop (legacy method for backward compatibility).
     *
     * @deprecated Use fetchExistingCssWithValidation() instead
     */
    private function fetchExistingCss(PrestaShopShop $shop, bool $forceFetch = false): string
    {
        $result = $this->fetchExistingCssWithValidation($shop, $forceFetch);
        return $result['success'] ? $result['content'] : '';
    }

    /**
     * Generate CSS from UVE blocks.
     */
    private function generateCssFromBlocks(array $blocks, int $shopId, int $productId): string
    {
        if (empty($blocks)) {
            return '';
        }

        return $this->ruleGenerator->generateFromBlocks($blocks, $shopId, $productId);
    }

    /**
     * Merge generated CSS with existing.
     */
    private function mergeCss(string $existingCss, string $generatedCss): string
    {
        if (empty($generatedCss)) {
            return $existingCss;
        }

        return $this->ruleGenerator->injectIntoExisting($existingCss, $generatedCss);
    }

    /**
     * Upload CSS to shop.
     *
     * ETAP_07h v2.0: Always upload to /themes/{theme}/css/uve-custom.css
     */
    private function uploadCss(PrestaShopShop $shop, string $css): array
    {
        // Determine UVE custom CSS path
        $uveCssPath = $this->getUveCssPath($shop);

        if (!$uveCssPath) {
            return [
                'success' => false,
                'error' => 'Nie można określić ścieżki do pliku UVE CSS. Sprawdź konfigurację theme.',
            ];
        }

        Log::debug('CssSyncOrchestrator: Uploading to UVE CSS path', [
            'shop_id' => $shop->id,
            'path' => $uveCssPath,
            'css_size' => strlen($css),
        ]);

        return $this->cssFetcher->saveCustomCss($shop, $css, $uveCssPath);
    }

    /**
     * Get UVE custom CSS file path.
     *
     * ETAP_07h v2.0: Uses existing custom.css file (already has correct permissions)
     * UVE styles are injected with markers (@uve-styles-start / @uve-styles-end)
     * This avoids permission issues with creating new files on shared hosting.
     *
     * CRITICAL: Most shared hosting FTP has root at user home, not web root.
     * Web files are in /public_html/, so we need /public_html/themes/... NOT /themes/...
     */
    private function getUveCssPath(PrestaShopShop $shop): ?string
    {
        // Try to find THEME custom.css in scanned files (not module custom.css!)
        $cssFiles = $shop->css_files ?? [];
        $themeCssPath = null;

        foreach ($cssFiles as $file) {
            $filename = strtolower($file['filename'] ?? $file['name'] ?? '');
            $url = $file['url'] ?? '';

            // Look for theme custom.css (contains /themes/ in URL)
            if ($filename === 'custom.css' && str_contains($url, '/themes/')) {
                $themeCssPath = $this->cssFetcher->urlToFtpPath($url, $shop->url);
                break;
            }
        }

        // If not found by name, extract theme from any css_file
        if (!$themeCssPath) {
            foreach ($cssFiles as $file) {
                $url = $file['url'] ?? '';
                if (preg_match('#/themes/([^/]+)/#', $url, $matches)) {
                    $themeName = $matches[1];
                    $themeCssPath = "/themes/{$themeName}/assets/css/custom.css";
                    break;
                }
            }
        }

        // Fallback: try from ftp_config
        if (!$themeCssPath) {
            $ftpConfig = $shop->ftp_config ?? [];
            if (!empty($ftpConfig['theme_name'])) {
                $themeCssPath = "/themes/{$ftpConfig['theme_name']}/assets/css/custom.css";
            }
        }

        // Last resort fallback
        if (!$themeCssPath) {
            $themeCssPath = '/themes/default/assets/css/custom.css';
        }

        // CRITICAL FIX: Prepend /public_html if path doesn't already have it
        // Shared hosting FTP root is user home, web files are in /public_html/
        if ($themeCssPath && !str_starts_with($themeCssPath, '/public_html')) {
            $themeCssPath = '/public_html' . $themeCssPath;
        }

        Log::debug('CssSyncOrchestrator: Resolved CSS path', [
            'shop_id' => $shop->id,
            'path' => $themeCssPath,
        ]);

        return $themeCssPath;
    }

    /**
     * Reset sync state.
     */
    private function resetState(): void
    {
        $this->syncState = [
            'status' => self::STATUS_PENDING,
            'step' => '',
            'progress' => 0,
            'message' => '',
            'error' => null,
            'details' => [],
        ];
    }

    /**
     * Update sync state.
     */
    private function updateState(string $status, string $message, int $progress): void
    {
        $this->syncState['status'] = $status;
        $this->syncState['message'] = $message;
        $this->syncState['progress'] = $progress;

        Log::debug('CssSyncOrchestrator: State update', $this->syncState);
    }

    /**
     * Return success result.
     */
    private function successSync(array $details = []): array
    {
        $this->syncState['status'] = self::STATUS_SUCCESS;
        $this->syncState['progress'] = 100;
        $this->syncState['message'] = 'Synchronizacja zakonczona pomyslnie';
        $this->syncState['details'] = $details;

        Log::info('CssSyncOrchestrator: Sync completed successfully', $details);

        return $this->syncState;
    }

    /**
     * Return failed result.
     */
    private function failSync(string $error): array
    {
        $this->syncState['status'] = self::STATUS_FAILED;
        $this->syncState['message'] = 'Blad synchronizacji';
        $this->syncState['error'] = $error;

        Log::warning('CssSyncOrchestrator: Sync failed', ['error' => $error]);

        return $this->syncState;
    }

    /**
     * Return skipped result.
     */
    private function skipSync(string $reason): array
    {
        $this->syncState['status'] = self::STATUS_SKIPPED;
        $this->syncState['message'] = $reason;
        $this->syncState['progress'] = 100;

        Log::info('CssSyncOrchestrator: Sync skipped', ['reason' => $reason]);

        return $this->syncState;
    }

    /**
     * Get current sync state.
     */
    public function getState(): array
    {
        return $this->syncState;
    }

    /**
     * Validate shop CSS configuration.
     */
    public function validateShopConfig(PrestaShopShop $shop): array
    {
        $issues = [];

        // Check FTP config
        if (!$shop->ftp_config) {
            $issues[] = 'Brak konfiguracji FTP';
        } else {
            if (empty($shop->ftp_config['host'])) {
                $issues[] = 'Brak adresu hosta FTP';
            }
            if (empty($shop->ftp_config['user'])) {
                $issues[] = 'Brak uzytkownika FTP';
            }
        }

        // Check CSS files
        if (!$shop->hasScannedFiles()) {
            $issues[] = 'Brak zeskanowanych plikow CSS - uruchom skanowanie w konfiguracji sklepu';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'has_ftp' => (bool) ($shop->ftp_config['host'] ?? false),
            'has_css_files' => $shop->hasScannedFiles(),
        ];
    }

    /**
     * Test CSS sync connection.
     */
    public function testConnection(PrestaShopShop $shop): array
    {
        // Test FTP connection
        $ftpResult = $this->cssFetcher->testFtpConnection($shop->ftp_config ?? []);

        if (!$ftpResult['success']) {
            return [
                'success' => false,
                'error' => $ftpResult['error'],
                'step' => 'ftp_connection',
            ];
        }

        // Try to read CSS file
        $readResult = $this->cssFetcher->getCustomCss($shop);

        return [
            'success' => $readResult['success'],
            'error' => $readResult['error'] ?? null,
            'step' => 'css_read',
            'server_info' => $ftpResult['server_info'],
            'css_path' => $readResult['filePath'] ?? null,
            'css_size' => strlen($readResult['content'] ?? ''),
        ];
    }

    /**
     * Get CSS sync statistics for shop.
     */
    public function getStats(PrestaShopShop $shop): array
    {
        $descriptions = ProductDescription::where('shop_id', $shop->id)
            ->whereNotNull('blocks_v2')
            ->get();

        $totalRules = 0;
        $totalSize = 0;

        foreach ($descriptions as $desc) {
            $preview = $this->previewCss($desc);
            $totalRules += $preview['rules_count'];
            $totalSize += $preview['size'];
        }

        return [
            'shop_id' => $shop->id,
            'products_with_uve' => $descriptions->count(),
            'total_css_rules' => $totalRules,
            'total_css_size' => $totalSize,
            'last_sync' => $shop->css_last_deployed_at ?? null,
        ];
    }
}
