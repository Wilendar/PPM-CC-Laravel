<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Asset Discovery Service
 *
 * Discovers and fetches CSS/JS files from PrestaShop shops.
 * Parses product pages to find all stylesheet and script references.
 *
 * ETAP_07f_P3: Visual Description Editor - CSS Integration
 */
class PrestaShopAssetDiscovery
{
    /**
     * Cache TTL for asset manifest (24 hours)
     */
    private const CACHE_TTL_HOURS = 24;

    /**
     * HTTP timeout in seconds
     */
    private const HTTP_TIMEOUT = 15;

    /**
     * Discover all CSS/JS assets from a PrestaShop product page.
     *
     * @param PrestaShopShop $shop Shop to discover assets from
     * @param int|null $productId Optional product ID for specific page
     * @param bool $forceRefresh Force refresh even if cached
     * @return array{css: array, js: array, discovered_at: string}
     */
    public function discoverAssets(PrestaShopShop $shop, ?int $productId = null, bool $forceRefresh = false): array
    {
        $cacheKey = "prestashop_assets_{$shop->id}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Build URL to fetch
            $url = $this->buildDiscoveryUrl($shop, $productId);

            Log::info('PrestaShopAssetDiscovery: Fetching page', [
                'shop_id' => $shop->id,
                'url' => $url,
            ]);

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('PrestaShopAssetDiscovery: Failed to fetch page', [
                    'shop_id' => $shop->id,
                    'status' => $response->status(),
                ]);

                return $this->getEmptyManifest();
            }

            $html = $response->body();
            $assets = $this->parseAssetsFromHtml($html, $shop);

            // Cache the results
            Cache::put($cacheKey, $assets, now()->addHours(self::CACHE_TTL_HOURS));

            Log::info('PrestaShopAssetDiscovery: Assets discovered', [
                'shop_id' => $shop->id,
                'css_count' => count($assets['css']),
                'js_count' => count($assets['js']),
            ]);

            return $assets;

        } catch (\Throwable $e) {
            Log::error('PrestaShopAssetDiscovery: Exception during discovery', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyManifest();
        }
    }

    /**
     * Get categorized assets (theme, custom, modules).
     *
     * @param PrestaShopShop $shop
     * @return array{theme: array, custom: array, modules: array}
     */
    public function getCategorizedAssets(PrestaShopShop $shop): array
    {
        $assets = $this->discoverAssets($shop);

        return [
            'theme' => [
                'css' => $this->filterByCategory($assets['css'], 'theme'),
                'js' => $this->filterByCategory($assets['js'], 'theme'),
            ],
            'custom' => [
                'css' => $this->filterByCategory($assets['css'], 'custom'),
                'js' => $this->filterByCategory($assets['js'], 'custom'),
            ],
            'modules' => [
                'css' => $this->filterByCategory($assets['css'], 'module'),
                'js' => $this->filterByCategory($assets['js'], 'module'),
            ],
            'other' => [
                'css' => $this->filterByCategory($assets['css'], 'other'),
                'js' => $this->filterByCategory($assets['js'], 'other'),
            ],
        ];
    }

    /**
     * Fetch content of a specific CSS file.
     *
     * @param string $url Full URL to CSS file
     * @return string|null CSS content or null on failure
     */
    public function fetchCssContent(string $url): ?string
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/css,*/*;q=0.1',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('PrestaShopAssetDiscovery: Failed to fetch CSS', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('PrestaShopAssetDiscovery: Exception fetching CSS', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch theme.css from PrestaShop.
     *
     * @param PrestaShopShop $shop
     * @return string|null
     */
    public function fetchThemeCss(PrestaShopShop $shop): ?string
    {
        $categorized = $this->getCategorizedAssets($shop);
        $themeCssFiles = $categorized['theme']['css'] ?? [];

        // Find main theme.css
        foreach ($themeCssFiles as $asset) {
            if (str_contains($asset['url'], 'theme.css') || str_contains($asset['url'], 'theme.min.css')) {
                return $this->fetchCssContent($asset['url']);
            }
        }

        // Fallback: fetch first theme CSS file
        if (!empty($themeCssFiles)) {
            return $this->fetchCssContent($themeCssFiles[0]['url']);
        }

        return null;
    }

    /**
     * Fetch custom.css from PrestaShop.
     *
     * @param PrestaShopShop $shop
     * @return string|null
     */
    public function fetchCustomCss(PrestaShopShop $shop): ?string
    {
        $categorized = $this->getCategorizedAssets($shop);
        $customCssFiles = $categorized['custom']['css'] ?? [];

        foreach ($customCssFiles as $asset) {
            if (str_contains($asset['url'], 'custom.css')) {
                return $this->fetchCssContent($asset['url']);
            }
        }

        return null;
    }

    /**
     * Fetch all CSS content for preview (theme + custom + selected modules).
     *
     * @param PrestaShopShop $shop
     * @param array $includeModules Module CSS URLs to include
     * @return string Combined CSS content
     */
    public function fetchAllCssForPreview(PrestaShopShop $shop, array $includeModules = []): string
    {
        $cssParts = [];

        // 1. Theme CSS
        $themeCss = $this->fetchThemeCss($shop);
        if ($themeCss) {
            $cssParts[] = "/* ========== THEME CSS ========== */\n" . $themeCss;
        }

        // 2. Custom CSS
        $customCss = $this->fetchCustomCss($shop);
        if ($customCss) {
            $cssParts[] = "/* ========== CUSTOM CSS ========== */\n" . $customCss;
        }

        // 3. Selected module CSS
        foreach ($includeModules as $moduleUrl) {
            $moduleCss = $this->fetchCssContent($moduleUrl);
            if ($moduleCss) {
                $moduleName = basename(dirname($moduleUrl));
                $cssParts[] = "/* ========== MODULE: {$moduleName} ========== */\n" . $moduleCss;
            }
        }

        return implode("\n\n", $cssParts);
    }

    /**
     * Get asset manifest for shop (cached).
     *
     * @param PrestaShopShop $shop
     * @return array
     */
    public function getAssetManifest(PrestaShopShop $shop): array
    {
        return $this->discoverAssets($shop);
    }

    /**
     * Clear cached assets for shop.
     *
     * @param PrestaShopShop $shop
     */
    public function clearCache(PrestaShopShop $shop): void
    {
        Cache::forget("prestashop_assets_{$shop->id}");
    }

    /**
     * Build URL for asset discovery.
     */
    private function buildDiscoveryUrl(PrestaShopShop $shop, ?int $productId): string
    {
        $baseUrl = rtrim($shop->url, '/');

        // If we have a product ID, try to build product URL
        if ($productId) {
            // Try common PrestaShop URL patterns
            return "{$baseUrl}/index.php?id_product={$productId}&controller=product";
        }

        // Fallback to homepage
        return $baseUrl;
    }

    /**
     * Parse CSS and JS assets from HTML.
     */
    private function parseAssetsFromHtml(string $html, PrestaShopShop $shop): array
    {
        $baseUrl = rtrim($shop->url, '/');
        $cssAssets = [];
        $jsAssets = [];

        // Parse CSS <link> tags
        preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $linkMatches);

        foreach ($linkMatches[0] as $index => $fullTag) {
            $href = $linkMatches[1][$index];

            // Check if it's a stylesheet
            if (str_contains($fullTag, 'stylesheet') || str_ends_with($href, '.css')) {
                $fullUrl = $this->resolveUrl($href, $baseUrl);
                $cssAssets[] = $this->createAssetEntry($fullUrl, $fullTag);
            }
        }

        // Parse JS <script> tags
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $scriptMatches);

        foreach ($scriptMatches[1] as $src) {
            if (str_ends_with($src, '.js')) {
                $fullUrl = $this->resolveUrl($src, $baseUrl);
                $jsAssets[] = $this->createAssetEntry($fullUrl);
            }
        }

        // Remove duplicates
        $cssAssets = $this->uniqueByUrl($cssAssets);
        $jsAssets = $this->uniqueByUrl($jsAssets);

        return [
            'css' => $cssAssets,
            'js' => $jsAssets,
            'discovered_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create asset entry with metadata.
     */
    private function createAssetEntry(string $url, string $fullTag = ''): array
    {
        return [
            'url' => $url,
            'filename' => basename(parse_url($url, PHP_URL_PATH) ?? $url),
            'category' => $this->categorizeAsset($url),
            'size' => null, // Will be populated on fetch
            'media' => $this->extractMediaAttribute($fullTag),
        ];
    }

    /**
     * Categorize asset by URL pattern.
     */
    private function categorizeAsset(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // Custom CSS/JS
        if (str_contains($path, 'custom.css') || str_contains($path, 'custom.js')) {
            return 'custom';
        }

        // Theme assets
        if (str_contains($path, '/themes/') && !str_contains($path, '/modules/')) {
            return 'theme';
        }

        // Module assets
        if (str_contains($path, '/modules/')) {
            return 'module';
        }

        // CDN/External
        if (!str_contains($url, parse_url($url, PHP_URL_HOST) ?? '')) {
            return 'external';
        }

        return 'other';
    }

    /**
     * Extract media attribute from link tag.
     */
    private function extractMediaAttribute(string $tag): string
    {
        if (preg_match('/media=["\']([^"\']+)["\']/i', $tag, $matches)) {
            return $matches[1];
        }

        return 'all';
    }

    /**
     * Resolve relative URL to absolute.
     */
    private function resolveUrl(string $url, string $baseUrl): string
    {
        // Already absolute
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Protocol-relative
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        // Absolute path
        if (str_starts_with($url, '/')) {
            $parsed = parse_url($baseUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? '';

            return "{$scheme}://{$host}{$url}";
        }

        // Relative path
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Filter assets by category.
     */
    private function filterByCategory(array $assets, string $category): array
    {
        return array_values(array_filter($assets, fn($a) => $a['category'] === $category));
    }

    /**
     * Remove duplicate assets by URL.
     */
    private function uniqueByUrl(array $assets): array
    {
        $seen = [];
        $unique = [];

        foreach ($assets as $asset) {
            $url = $asset['url'];
            if (!isset($seen[$url])) {
                $seen[$url] = true;
                $unique[] = $asset;
            }
        }

        return $unique;
    }

    /**
     * Get empty manifest structure.
     */
    private function getEmptyManifest(): array
    {
        return [
            'css' => [],
            'js' => [],
            'discovered_at' => now()->toIso8601String(),
        ];
    }
}
