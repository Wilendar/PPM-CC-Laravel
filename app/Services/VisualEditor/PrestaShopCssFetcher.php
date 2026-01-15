<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

/**
 * PrestaShop CSS Fetcher Service
 *
 * Manages fetching and caching of custom.css/custom.js from PrestaShop shops.
 * Supports both URL fetch and FTP/SFTP operations.
 *
 * ETAP_07f: Visual Description Editor - CSS Integration
 *
 * @package App\Services\VisualEditor
 */
class PrestaShopCssFetcher
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION_MINUTES = 60;

    /**
     * FTP connection timeout in seconds
     */
    private const FTP_TIMEOUT = 30;

    /**
     * Fetch CSS from PrestaShop via URL (read-only).
     *
     * @param PrestaShopShop $shop Shop to fetch CSS from
     * @param bool $forceRefresh Force refresh even if cache is valid
     * @return string|null CSS content or null on failure
     */
    public function fetchCssFromUrl(PrestaShopShop $shop, bool $forceRefresh = false): ?string
    {
        if (!$shop->custom_css_url) {
            return null;
        }

        // Check cache validity
        if (!$forceRefresh && $this->isCacheValid($shop)) {
            return $shop->cached_custom_css;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'text/css'])
                ->get($shop->custom_css_url);

            if (!$response->successful()) {
                Log::warning('PrestaShopCssFetcher: Failed to fetch CSS', [
                    'shop_id' => $shop->id,
                    'url' => $shop->custom_css_url,
                    'status' => $response->status(),
                ]);
                return $shop->cached_custom_css; // Return cached version as fallback
            }

            $css = $response->body();

            // Update cache
            $shop->update([
                'cached_custom_css' => $css,
                'css_last_fetched_at' => now(),
            ]);

            Log::info('PrestaShopCssFetcher: CSS fetched successfully', [
                'shop_id' => $shop->id,
                'size' => strlen($css),
            ]);

            return $css;

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: Exception during CSS fetch', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
            return $shop->cached_custom_css;
        }
    }

    /**
     * Fetch CSS from PrestaShop via FTP/SFTP.
     *
     * @param PrestaShopShop $shop Shop to fetch CSS from
     * @return array{success: bool, css: ?string, error: ?string}
     */
    public function fetchCssFromFtp(PrestaShopShop $shop): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'css' => null,
                'error' => 'FTP not configured for this shop',
            ];
        }

        try {
            $css = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpRead($config),
                default => $this->ftpRead($config),
            };

            if ($css !== null) {
                // Update cache
                $shop->update([
                    'cached_custom_css' => $css,
                    'css_last_fetched_at' => now(),
                ]);
            }

            return [
                'success' => $css !== null,
                'css' => $css,
                'error' => $css === null ? 'Failed to read file' : null,
            ];

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: FTP read failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'css' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload CSS to PrestaShop via FTP/SFTP.
     *
     * @param PrestaShopShop $shop Target shop
     * @param string $css CSS content to upload
     * @param bool $backup Create backup before upload
     * @return array{success: bool, error: ?string, backup_path: ?string}
     */
    public function uploadCssToFtp(PrestaShopShop $shop, string $css, bool $backup = true): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'error' => 'FTP not configured for this shop',
                'backup_path' => null,
            ];
        }

        try {
            $backupPath = null;

            // Create backup if requested
            if ($backup) {
                $backupPath = $this->createRemoteBackup($config);
            }

            $result = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpWrite($config, $css),
                default => $this->ftpWrite($config, $css),
            };

            if ($result) {
                $shop->update([
                    'css_last_deployed_at' => now(),
                    'css_deploy_status' => 'success',
                    'css_deploy_message' => 'CSS deployed successfully',
                ]);

                Log::info('PrestaShopCssFetcher: CSS uploaded successfully', [
                    'shop_id' => $shop->id,
                    'size' => strlen($css),
                    'backup' => $backupPath,
                ]);
            }

            return [
                'success' => $result,
                'error' => $result ? null : 'Failed to write file',
                'backup_path' => $backupPath,
            ];

        } catch (\Throwable $e) {
            $shop->update([
                'css_deploy_status' => 'failed',
                'css_deploy_message' => $e->getMessage(),
            ]);

            Log::error('PrestaShopCssFetcher: FTP upload failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'backup_path' => null,
            ];
        }
    }

    /**
     * Test FTP connection.
     *
     * @param array $config FTP configuration
     * @return array{success: bool, error: ?string, server_info: ?array}
     */
    public function testFtpConnection(array $config): array
    {
        try {
            $protocol = $config['protocol'] ?? 'ftp';

            if ($protocol === 'sftp') {
                return $this->testSftpConnection($config);
            }

            $ftp = @ftp_connect($config['host'], (int) ($config['port'] ?? 21), self::FTP_TIMEOUT);

            if (!$ftp) {
                return [
                    'success' => false,
                    'error' => "Cannot connect to FTP server: {$config['host']}",
                    'server_info' => null,
                ];
            }

            $password = $this->decryptPassword($config['password'] ?? '');
            $loginResult = @ftp_login($ftp, $config['user'] ?? '', $password);

            if (!$loginResult) {
                ftp_close($ftp);
                return [
                    'success' => false,
                    'error' => 'FTP login failed - check credentials',
                    'server_info' => null,
                ];
            }

            ftp_pasv($ftp, true);

            // Get server info
            $sysType = @ftp_systype($ftp);
            $pwd = @ftp_pwd($ftp);

            // Check if CSS path exists
            $cssPath = $config['css_path'] ?? '';
            $cssPathExists = false;
            if ($cssPath) {
                $cssPathExists = @ftp_size($ftp, $cssPath) !== -1;
            }

            ftp_close($ftp);

            return [
                'success' => true,
                'error' => null,
                'server_info' => [
                    'system_type' => $sysType,
                    'current_dir' => $pwd,
                    'css_path_exists' => $cssPathExists,
                ],
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'server_info' => null,
            ];
        }
    }

    /**
     * Scan CSS/JS files via FTP (fallback when HTTP fails).
     *
     * ETAP_07f_P3.5: FTP fallback for asset discovery when HTTP returns 503
     *
     * @param array $config FTP configuration with 'host', 'user', 'password', 'css_path'
     * @param string $shopUrl Shop base URL for constructing file URLs
     * @return array{css: array, js: array, discovered_at: string, source: string}
     */
    public function scanFilesViaFtp(array $config, string $shopUrl): array
    {
        $cssFiles = [];
        $jsFiles = [];

        try {
            $ftp = @ftp_connect($config['host'], (int) ($config['port'] ?? 21), self::FTP_TIMEOUT);
            if (!$ftp) {
                Log::warning('PrestaShopCssFetcher: FTP scan - cannot connect', [
                    'host' => $config['host'],
                ]);
                return $this->getEmptyFtpScanResult();
            }

            $password = $this->decryptPassword($config['password'] ?? '');
            if (!@ftp_login($ftp, $config['user'] ?? '', $password)) {
                ftp_close($ftp);
                Log::warning('PrestaShopCssFetcher: FTP scan - login failed');
                return $this->getEmptyFtpScanResult();
            }

            ftp_pasv($ftp, true);

            // Get theme directory from css_path
            $cssPath = $config['css_path'] ?? '';
            $themeCssDir = '';
            $themeJsDir = '';

            if ($cssPath) {
                // Extract theme dir from path like /themes/warehouse/assets/css/custom.css
                if (preg_match('#(/themes/[^/]+/)#', $cssPath, $matches)) {
                    $themeBase = $matches[1];
                    $themeCssDir = $themeBase . 'assets/css';
                    $themeJsDir = $themeBase . 'assets/js';
                } elseif (str_contains($cssPath, '/css/')) {
                    // Direct CSS directory path
                    $themeCssDir = dirname($cssPath);
                    $themeJsDir = str_replace('/css', '/js', $themeCssDir);
                }
            }

            // Scan CSS directory
            if ($themeCssDir) {
                $cssFiles = $this->listFtpDirectory($ftp, $themeCssDir, 'css', $shopUrl);
            }

            // Scan JS directory
            if ($themeJsDir) {
                $jsFiles = $this->listFtpDirectory($ftp, $themeJsDir, 'js', $shopUrl);
            }

            ftp_close($ftp);

            Log::info('PrestaShopCssFetcher: FTP scan completed', [
                'css_dir' => $themeCssDir,
                'js_dir' => $themeJsDir,
                'css_count' => count($cssFiles),
                'js_count' => count($jsFiles),
            ]);

            return [
                'css' => $cssFiles,
                'js' => $jsFiles,
                'discovered_at' => now()->toIso8601String(),
                'source' => 'ftp',
            ];

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: FTP scan failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->getEmptyFtpScanResult();
        }
    }

    /**
     * List files in FTP directory with specific extension.
     */
    private function listFtpDirectory($ftp, string $dir, string $extension, string $shopUrl): array
    {
        $files = [];

        // Try to list directory
        $listing = @ftp_nlist($ftp, $dir);
        if ($listing === false) {
            return [];
        }

        foreach ($listing as $path) {
            $filename = basename($path);

            // Skip directories (usually they don't have extension)
            if (!str_contains($filename, '.')) {
                continue;
            }

            // Filter by extension
            if (!str_ends_with(strtolower($filename), '.' . $extension)) {
                continue;
            }

            // Skip minified files
            if (str_contains(strtolower($filename), '.min.')) {
                continue;
            }

            // Construct URL from path
            $relativePath = $path;
            if (str_starts_with($path, '/')) {
                $relativePath = $path;
            }

            // Build full URL
            $url = rtrim($shopUrl, '/') . $relativePath;

            $files[] = [
                'url' => $url,
                'filename' => $filename,
                'category' => $this->categorizeFileByPath($path),
            ];
        }

        return $files;
    }

    /**
     * Categorize file by its FTP path.
     */
    private function categorizeFileByPath(string $path): string
    {
        if (str_contains($path, 'custom.css') || str_contains($path, 'custom.js')) {
            return 'custom';
        }
        if (str_contains($path, '/themes/')) {
            return 'theme';
        }
        if (str_contains($path, '/modules/')) {
            return 'module';
        }
        return 'other';
    }

    /**
     * Get empty FTP scan result structure.
     */
    private function getEmptyFtpScanResult(): array
    {
        return [
            'css' => [],
            'js' => [],
            'discovered_at' => now()->toIso8601String(),
            'source' => 'ftp',
        ];
    }

    /**
     * Get CSS for preview (from cache or fetch).
     *
     * ETAP_07f_P3.5: Multi-file CSS support
     * Priority: css_files array > cached_custom_css > URL fetch > FTP fetch
     *
     * @param PrestaShopShop $shop
     * @return string|null
     */
    public function getCssForPreview(PrestaShopShop $shop): ?string
    {
        // Priority 1: Multi-file CSS from css_files array (ETAP_07f_P3.5)
        if ($shop->hasScannedFiles() && $shop->getEnabledCssFilesCount() > 0) {
            $combinedCss = $this->fetchAllFromCssFiles($shop);
            if (!empty($combinedCss)) {
                return $combinedCss;
            }
        }

        // Priority 2: Legacy single-file cache
        if ($shop->cached_custom_css) {
            return $shop->cached_custom_css;
        }

        // Priority 3: Legacy URL fetch
        if ($shop->custom_css_url) {
            return $this->fetchCssFromUrl($shop);
        }

        // Priority 4: FTP fetch
        if ($shop->ftp_config) {
            $result = $this->fetchCssFromFtp($shop);
            return $result['css'];
        }

        return null;
    }

    /**
     * Fetch and combine all enabled CSS files from css_files array.
     *
     * ETAP_07f_P3.5: Multi-file CSS support
     *
     * @param PrestaShopShop $shop
     * @param bool $forceRefresh Force re-fetch even if cached
     * @return string Combined CSS content
     */
    public function fetchAllFromCssFiles(PrestaShopShop $shop, bool $forceRefresh = false): string
    {
        $cssFiles = $shop->css_files ?? [];
        $updated = false;
        $cssParts = [];

        foreach ($cssFiles as $index => $file) {
            // Skip disabled files
            if (!($file['enabled'] ?? false)) {
                continue;
            }

            $url = $file['url'] ?? '';
            if (empty($url)) {
                continue;
            }

            // Check if we need to fetch (no cache or force refresh)
            $needsFetch = $forceRefresh
                || empty($file['cached_content'])
                || empty($file['last_fetched_at'])
                || now()->diffInMinutes($file['last_fetched_at']) > self::CACHE_DURATION_MINUTES;

            if ($needsFetch) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders(['Accept' => 'text/css'])
                        ->get($url);

                    if ($response->successful()) {
                        $cssFiles[$index]['cached_content'] = $response->body();
                        $cssFiles[$index]['last_fetched_at'] = now()->toIso8601String();
                        $updated = true;

                        Log::debug('PrestaShopCssFetcher: CSS file fetched', [
                            'shop_id' => $shop->id,
                            'url' => $url,
                            'size' => strlen($response->body()),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('PrestaShopCssFetcher: Failed to fetch CSS file', [
                        'shop_id' => $shop->id,
                        'url' => $url,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Add to combined output
            $content = $cssFiles[$index]['cached_content'] ?? '';
            if (!empty($content)) {
                $name = $file['filename'] ?? basename($url);
                $category = $file['category'] ?? 'other';
                $cssParts[] = "/* ========== [{$category}] {$name} ========== */\n{$content}";
            }
        }

        // Save updated cache if any files were fetched
        if ($updated) {
            $shop->update(['css_files' => $cssFiles]);
        }

        return implode("\n\n", $cssParts);
    }

    /**
     * Get custom CSS content for editor.
     *
     * ETAP_07f_P3: Now supports dynamic file path from scanned files.
     *
     * @param PrestaShopShop $shop
     * @param string|null $filePath Optional FTP path (derived from scanned files if null)
     * @return array{success: bool, content: ?string, error: ?string, filePath: ?string}
     */
    public function getCustomCss(PrestaShopShop $shop, ?string $filePath = null): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'content' => null,
                'error' => 'FTP nie jest skonfigurowane dla tego sklepu',
                'filePath' => null,
            ];
        }

        // Derive file path from scanned files if not provided
        if (!$filePath) {
            $filePath = $this->getDefaultCssPath($shop);
            if (!$filePath) {
                return [
                    'success' => false,
                    'content' => null,
                    'error' => 'Brak zeskanowanych plikow CSS. Najpierw uruchom skanowanie plikow w konfiguracji sklepu.',
                    'filePath' => null,
                ];
            }
        }

        // Merge file path into config
        $configWithPath = array_merge($config, ['css_path' => $filePath]);

        try {
            $content = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpRead($configWithPath),
                default => $this->ftpRead($configWithPath),
            };

            return [
                'success' => $content !== null,
                'content' => $content,
                'error' => $content === null ? 'Nie udalo sie odczytac pliku CSS' : null,
                'filePath' => $filePath,
            ];

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: getCustomCss failed', [
                'shop_id' => $shop->id,
                'filePath' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'error' => $e->getMessage(),
                'filePath' => $filePath,
            ];
        }
    }

    /**
     * Get custom JS content for editor.
     *
     * ETAP_07f_P3: Now supports dynamic file path from scanned files.
     *
     * @param PrestaShopShop $shop
     * @param string|null $filePath Optional FTP path (derived from scanned files if null)
     * @return array{success: bool, content: ?string, error: ?string, filePath: ?string}
     */
    public function getCustomJs(PrestaShopShop $shop, ?string $filePath = null): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'content' => null,
                'error' => 'FTP nie jest skonfigurowane dla tego sklepu',
                'filePath' => null,
            ];
        }

        // Derive file path from scanned files if not provided
        if (!$filePath) {
            $filePath = $this->getDefaultJsPath($shop);
            if (!$filePath) {
                return [
                    'success' => false,
                    'content' => null,
                    'error' => 'Brak zeskanowanych plikow JS. Najpierw uruchom skanowanie plikow w konfiguracji sklepu.',
                    'filePath' => null,
                ];
            }
        }

        // Merge file path into config
        $jsConfig = array_merge($config, ['css_path' => $filePath]);

        try {
            $content = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpRead($jsConfig),
                default => $this->ftpRead($jsConfig),
            };

            return [
                'success' => $content !== null,
                'content' => $content,
                'error' => $content === null ? 'Nie udalo sie odczytac pliku JS' : null,
                'filePath' => $filePath,
            ];

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: getCustomJs failed', [
                'shop_id' => $shop->id,
                'filePath' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'error' => $e->getMessage(),
                'filePath' => $filePath,
            ];
        }
    }

    /**
     * Save custom CSS content to PrestaShop.
     *
     * ETAP_07f_P3: Now supports dynamic file path from scanned files.
     *
     * @param PrestaShopShop $shop
     * @param string $content CSS content
     * @param string|null $filePath Optional FTP path (derived from scanned files if null)
     * @return array{success: bool, error: ?string}
     */
    public function saveCustomCss(PrestaShopShop $shop, string $content, ?string $filePath = null): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'error' => 'FTP nie jest skonfigurowane dla tego sklepu',
            ];
        }

        // Derive file path from scanned files if not provided
        if (!$filePath) {
            $filePath = $this->getDefaultCssPath($shop);
            if (!$filePath) {
                return [
                    'success' => false,
                    'error' => 'Brak zeskanowanych plikow CSS. Najpierw uruchom skanowanie plikow w konfiguracji sklepu.',
                ];
            }
        }

        // Merge file path into config
        $configWithPath = array_merge($config, ['css_path' => $filePath]);

        try {
            // Create backup
            $this->createRemoteBackup($configWithPath);

            $result = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpWrite($configWithPath, $content),
                default => $this->ftpWrite($configWithPath, $content),
            };

            if ($result) {
                // ETAP_07h v2.1: Update shop deploy status
                $shop->update([
                    'css_last_deployed_at' => now(),
                    'css_deploy_status' => 'success',
                    'css_deploy_message' => 'CSS deployed successfully to ' . basename($filePath),
                ]);

                Log::info('PrestaShopCssFetcher: CSS uploaded successfully', [
                    'shop_id' => $shop->id,
                    'filePath' => $filePath,
                    'size' => strlen($content),
                ]);
            } else {
                $shop->update([
                    'css_deploy_status' => 'failed',
                    'css_deploy_message' => 'Failed to write CSS file',
                ]);
            }

            return [
                'success' => $result,
                'error' => $result ? null : 'Nie udalo sie zapisac pliku CSS',
            ];

        } catch (\Throwable $e) {
            $shop->update([
                'css_deploy_status' => 'failed',
                'css_deploy_message' => $e->getMessage(),
            ]);

            Log::error('PrestaShopCssFetcher: saveCustomCss failed', [
                'shop_id' => $shop->id,
                'filePath' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save custom JS content to PrestaShop.
     *
     * ETAP_07f_P3: Now supports dynamic file path from scanned files.
     *
     * @param PrestaShopShop $shop
     * @param string $content JS content
     * @param string|null $filePath Optional FTP path (derived from scanned files if null)
     * @return array{success: bool, error: ?string}
     */
    public function saveCustomJs(PrestaShopShop $shop, string $content, ?string $filePath = null): array
    {
        $config = $shop->ftp_config;

        if (!$config || empty($config['host'])) {
            return [
                'success' => false,
                'error' => 'FTP nie jest skonfigurowane dla tego sklepu',
            ];
        }

        // Derive file path from scanned files if not provided
        if (!$filePath) {
            $filePath = $this->getDefaultJsPath($shop);
            if (!$filePath) {
                return [
                    'success' => false,
                    'error' => 'Brak zeskanowanych plikow JS. Najpierw uruchom skanowanie plikow w konfiguracji sklepu.',
                ];
            }
        }

        // Merge file path into config
        $jsConfig = array_merge($config, ['css_path' => $filePath]);

        try {
            // Create backup
            $this->createRemoteBackup($jsConfig);

            $result = match ($config['protocol'] ?? 'ftp') {
                'sftp' => $this->sftpWrite($jsConfig, $content),
                default => $this->ftpWrite($jsConfig, $content),
            };

            if ($result) {
                Log::info('PrestaShopCssFetcher: JS uploaded successfully', [
                    'shop_id' => $shop->id,
                    'filePath' => $filePath,
                    'size' => strlen($content),
                ]);
            }

            return [
                'success' => $result,
                'error' => $result ? null : 'Nie udalo sie zapisac pliku JS',
            ];

        } catch (\Throwable $e) {
            Log::error('PrestaShopCssFetcher: saveCustomJs failed', [
                'shop_id' => $shop->id,
                'filePath' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get JS path from CSS path configuration.
     * Assumes JS file is in same directory as CSS: custom.css -> custom.js
     */
    private function getJsPath(array $config): ?string
    {
        $cssPath = $config['css_path'] ?? $config['js_path'] ?? '';

        if (empty($cssPath)) {
            return null;
        }

        // If js_path is explicitly set, use it
        if (!empty($config['js_path'])) {
            return $config['js_path'];
        }

        // Derive from CSS path: /path/to/custom.css -> /path/to/custom.js
        if (str_ends_with($cssPath, '.css')) {
            return substr($cssPath, 0, -4) . '.js';
        }

        // Fallback: append .js
        return $cssPath . '.js';
    }

    /**
     * Get default CSS path from scanned files.
     * Prioritizes THEME custom.css over module custom.css.
     *
     * ETAP_07f_P3: Dynamic path resolution from scanned files.
     * ETAP_07h v2.0: Fixed to prefer theme custom.css over module custom.css
     * ETAP_07h v2.1: Added /public_html prefix for shared hosting FTP
     *
     * @param PrestaShopShop $shop
     * @return string|null FTP path or null if no suitable file found
     */
    private function getDefaultCssPath(PrestaShopShop $shop): ?string
    {
        $cssFiles = $shop->css_files ?? [];

        if (empty($cssFiles)) {
            return null;
        }

        $path = null;

        // Priority 1: Look for THEME custom.css (contains /themes/ in URL)
        // Note: Field is 'name' not 'filename' in css_files array
        foreach ($cssFiles as $file) {
            $filename = strtolower($file['name'] ?? $file['filename'] ?? '');
            $url = $file['url'] ?? '';

            if ($filename === 'custom.css' && str_contains($url, '/themes/')) {
                $path = $this->urlToFtpPath($url, $shop->url);
                break;
            }
        }

        // Priority 2: Look for any custom.css (module custom.css as fallback)
        if (!$path) {
            foreach ($cssFiles as $file) {
                $filename = strtolower($file['name'] ?? $file['filename'] ?? '');
                $url = $file['url'] ?? '';
                $type = $file['type'] ?? $file['category'] ?? '';

                if ($type === 'custom' || $filename === 'custom.css') {
                    $path = $this->urlToFtpPath($url, $shop->url);
                    break;
                }
            }
        }

        // Priority 3: Return first CSS file (usually theme.css)
        if (!$path) {
            $firstFile = reset($cssFiles);
            if (!empty($firstFile['url'])) {
                $path = $this->urlToFtpPath($firstFile['url'], $shop->url);
            }
        }

        // ETAP_07h v2.1: Add /public_html prefix for shared hosting
        // Shared hosting FTP root is user home, web files are in /public_html/
        if ($path && !str_starts_with($path, '/public_html')) {
            $path = '/public_html' . $path;
        }

        return $path;
    }

    /**
     * Get default JS path from scanned files.
     * Prioritizes custom.js files.
     *
     * ETAP_07f_P3: Dynamic path resolution from scanned files.
     *
     * @param PrestaShopShop $shop
     * @return string|null FTP path or null if no suitable file found
     */
    private function getDefaultJsPath(PrestaShopShop $shop): ?string
    {
        $jsFiles = $shop->js_files ?? [];

        if (empty($jsFiles)) {
            // Fallback: derive from CSS path
            $cssPath = $this->getDefaultCssPath($shop);
            if ($cssPath && str_ends_with($cssPath, '.css')) {
                return substr($cssPath, 0, -4) . '.js';
            }
            return null;
        }

        // Priority 1: Look for custom.js (editable file)
        foreach ($jsFiles as $file) {
            $filename = $file['filename'] ?? '';
            $url = $file['url'] ?? '';
            $category = $file['category'] ?? '';

            if ($category === 'custom' || str_contains(strtolower($filename), 'custom.js')) {
                return $this->urlToFtpPath($url, $shop->url);
            }
        }

        // Priority 2: Return first JS file
        $firstFile = reset($jsFiles);
        if (!empty($firstFile['url'])) {
            return $this->urlToFtpPath($firstFile['url'], $shop->url);
        }

        return null;
    }

    /**
     * Convert asset URL to FTP path.
     *
     * Example:
     *   URL: https://test.kayomoto.pl/themes/warehouse/assets/css/custom.css
     *   Shop URL: https://test.kayomoto.pl
     *   Result: /themes/warehouse/assets/css/custom.css
     *
     * ETAP_07f_P3: URL to FTP path conversion for dynamic file editing.
     *
     * @param string $url Full asset URL
     * @param string $shopUrl Shop base URL
     * @return string FTP path (relative to shop root)
     */
    public function urlToFtpPath(string $url, string $shopUrl): string
    {
        // Normalize URLs
        $url = trim($url);
        $shopUrl = rtrim(trim($shopUrl), '/');

        // Remove protocol and domain
        $path = $url;

        // If URL starts with shop URL, remove it
        if (str_starts_with($url, $shopUrl)) {
            $path = substr($url, strlen($shopUrl));
        } elseif (preg_match('#^https?://[^/]+(.*)$#', $url, $matches)) {
            // Remove any domain, keep only path
            $path = $matches[1];
        }

        // Ensure path starts with /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * Get file path for specific asset URL.
     *
     * @param PrestaShopShop $shop
     * @param string $url Asset URL
     * @return string|null FTP path
     */
    public function getFilePathFromUrl(PrestaShopShop $shop, string $url): ?string
    {
        return $this->urlToFtpPath($url, $shop->url);
    }

    /**
     * Refresh CSS cache for shop.
     *
     * @param PrestaShopShop $shop
     * @return bool
     */
    public function refreshCache(PrestaShopShop $shop): bool
    {
        // Clear last fetched timestamp to force refresh
        $shop->update(['css_last_fetched_at' => null]);

        if ($shop->custom_css_url) {
            return $this->fetchCssFromUrl($shop, true) !== null;
        }

        if ($shop->ftp_config) {
            $result = $this->fetchCssFromFtp($shop);
            return $result['success'];
        }

        return false;
    }

    /**
     * Check if cache is still valid.
     */
    private function isCacheValid(PrestaShopShop $shop): bool
    {
        if (!$shop->css_last_fetched_at) {
            return false;
        }

        return $shop->css_last_fetched_at->diffInMinutes(now()) < self::CACHE_DURATION_MINUTES;
    }

    /**
     * Read file via FTP.
     */
    private function ftpRead(array $config): ?string
    {
        $ftp = ftp_connect($config['host'], (int) ($config['port'] ?? 21), self::FTP_TIMEOUT);
        if (!$ftp) {
            throw new \RuntimeException("Cannot connect to FTP: {$config['host']}");
        }

        try {
            $password = $this->decryptPassword($config['password'] ?? '');
            if (!ftp_login($ftp, $config['user'] ?? '', $password)) {
                throw new \RuntimeException('FTP login failed');
            }

            ftp_pasv($ftp, true);

            $tempFile = tmpfile();
            $tempPath = stream_get_meta_data($tempFile)['uri'];

            $cssPath = $config['css_path'] ?? '';
            if (!$cssPath) {
                throw new \RuntimeException('CSS path not configured');
            }

            if (!ftp_get($ftp, $tempPath, $cssPath, FTP_BINARY)) {
                throw new \RuntimeException("Cannot read file: {$cssPath}");
            }

            $content = file_get_contents($tempPath);
            fclose($tempFile);

            return $content;

        } finally {
            ftp_close($ftp);
        }
    }

    /**
     * Write file via FTP.
     *
     * ETAP_07h v2.0: Enhanced to create directories and files if they don't exist.
     */
    private function ftpWrite(array $config, string $content): bool
    {
        $ftp = ftp_connect($config['host'], (int) ($config['port'] ?? 21), self::FTP_TIMEOUT);
        if (!$ftp) {
            throw new \RuntimeException("Cannot connect to FTP: {$config['host']}");
        }

        try {
            $password = $this->decryptPassword($config['password'] ?? '');
            if (!ftp_login($ftp, $config['user'] ?? '', $password)) {
                throw new \RuntimeException('FTP login failed');
            }

            ftp_pasv($ftp, true);

            $cssPath = $config['css_path'] ?? '';
            if (!$cssPath) {
                throw new \RuntimeException('CSS path not configured');
            }

            // ETAP_07h v2.0: Ensure directory exists before writing
            $this->ftpEnsureDirectoryExists($ftp, dirname($cssPath));

            // Write content to temp file
            $tempFile = tmpfile();
            fwrite($tempFile, $content);
            rewind($tempFile);

            // Try ftp_fput first (works if file exists or can be created)
            $result = @ftp_fput($ftp, $cssPath, $tempFile, FTP_BINARY);

            if (!$result) {
                // Fallback: use ftp_put with temp file path
                $tempPath = stream_get_meta_data($tempFile)['uri'];
                $result = @ftp_put($ftp, $cssPath, $tempPath, FTP_BINARY);
            }

            fclose($tempFile);

            if (!$result) {
                throw new \RuntimeException("ftp_fput(): {$cssPath}: No such file or directory");
            }

            // ETAP_07h v2.0: Set proper file permissions (644 = owner rw, group/other r)
            @ftp_chmod($ftp, 0644, $cssPath);

            return $result;

        } finally {
            ftp_close($ftp);
        }
    }

    /**
     * Ensure directory exists on FTP server (create recursively if needed).
     *
     * ETAP_07h v2.0: Helper for creating directories before file upload.
     *
     * @param resource $ftp FTP connection
     * @param string $directory Directory path (e.g., /themes/warehouse/assets/css)
     */
    private function ftpEnsureDirectoryExists($ftp, string $directory): void
    {
        if (empty($directory) || $directory === '/' || $directory === '.') {
            return;
        }

        // Check if directory exists by trying to change to it
        $currentDir = @ftp_pwd($ftp);
        if (@ftp_chdir($ftp, $directory)) {
            // Directory exists, go back to original
            @ftp_chdir($ftp, $currentDir);
            return;
        }

        // Directory doesn't exist - create it recursively
        $parts = array_filter(explode('/', $directory));
        $path = '';

        foreach ($parts as $part) {
            $path .= '/' . $part;

            // Try to change to this directory
            if (!@ftp_chdir($ftp, $path)) {
                // Directory doesn't exist, try to create it
                @ftp_mkdir($ftp, $path);
                Log::debug('PrestaShopCssFetcher: Created FTP directory', [
                    'path' => $path,
                ]);
            }
        }

        // Return to original directory
        @ftp_chdir($ftp, $currentDir);
    }

    /**
     * Read file via SFTP (requires phpseclib).
     */
    private function sftpRead(array $config): ?string
    {
        // SFTP requires phpseclib package
        // For now, return error - can be implemented if needed
        throw new \RuntimeException('SFTP not yet implemented. Please use FTP or install phpseclib.');
    }

    /**
     * Write file via SFTP.
     */
    private function sftpWrite(array $config, string $content): bool
    {
        throw new \RuntimeException('SFTP not yet implemented. Please use FTP or install phpseclib.');
    }

    /**
     * Test SFTP connection.
     */
    private function testSftpConnection(array $config): array
    {
        return [
            'success' => false,
            'error' => 'SFTP not yet implemented. Please use FTP protocol.',
            'server_info' => null,
        ];
    }

    /**
     * Create backup of remote CSS file.
     */
    private function createRemoteBackup(array $config): ?string
    {
        try {
            $cssPath = $config['css_path'] ?? '';
            if (!$cssPath) {
                return null;
            }

            $backupPath = $cssPath . '.backup.' . date('Y-m-d_His');

            $ftp = ftp_connect($config['host'], (int) ($config['port'] ?? 21), self::FTP_TIMEOUT);
            if (!$ftp) {
                return null;
            }

            try {
                $password = $this->decryptPassword($config['password'] ?? '');
                if (!ftp_login($ftp, $config['user'] ?? '', $password)) {
                    return null;
                }

                ftp_pasv($ftp, true);

                // Download current file
                $tempFile = tmpfile();
                $tempPath = stream_get_meta_data($tempFile)['uri'];

                if (ftp_get($ftp, $tempPath, $cssPath, FTP_BINARY)) {
                    // Upload as backup
                    rewind($tempFile);
                    ftp_fput($ftp, $backupPath, $tempFile, FTP_BINARY);
                }

                fclose($tempFile);

                return $backupPath;

            } finally {
                ftp_close($ftp);
            }

        } catch (\Throwable $e) {
            Log::warning('PrestaShopCssFetcher: Backup failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Decrypt FTP password.
     */
    private function decryptPassword(string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            // Password might not be encrypted (legacy)
            return $encrypted;
        }
    }

    /**
     * Encrypt FTP password for storage.
     */
    public static function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    /**
     * Fetch theme.css from PrestaShop via asset discovery.
     *
     * @param PrestaShopShop $shop
     * @param bool $forceRefresh
     * @return string|null
     */
    public function fetchThemeCss(PrestaShopShop $shop, bool $forceRefresh = false): ?string
    {
        // Check cache first
        if (!$forceRefresh && $shop->cached_theme_css) {
            return $shop->cached_theme_css;
        }

        $discovery = app(PrestaShopAssetDiscovery::class);
        $themeCss = $discovery->fetchThemeCss($shop);

        if ($themeCss) {
            $shop->update([
                'cached_theme_css' => $themeCss,
                'theme_css_fetched_at' => now(),
            ]);

            Log::info('PrestaShopCssFetcher: Theme CSS fetched', [
                'shop_id' => $shop->id,
                'size' => strlen($themeCss),
            ]);
        }

        return $themeCss;
    }

    /**
     * Fetch all CSS for preview (theme + custom + modules).
     *
     * @param PrestaShopShop $shop
     * @param array $includeModules Module URLs to include
     * @param bool $forceRefresh
     * @return string Combined CSS
     */
    public function fetchAllCss(PrestaShopShop $shop, array $includeModules = [], bool $forceRefresh = false): string
    {
        $cssParts = [];

        // 1. Theme CSS
        $themeCss = $this->fetchThemeCss($shop, $forceRefresh);
        if ($themeCss) {
            $cssParts[] = "/* ========== THEME CSS ========== */\n" . $themeCss;
        }

        // 2. Custom CSS
        $customCss = $this->fetchCssFromUrl($shop, $forceRefresh);
        if ($customCss) {
            $cssParts[] = "/* ========== CUSTOM CSS ========== */\n" . $customCss;
        }

        // 3. Module CSS
        if (!empty($includeModules)) {
            $discovery = app(PrestaShopAssetDiscovery::class);
            foreach ($includeModules as $moduleUrl) {
                $moduleCss = $discovery->fetchCssContent($moduleUrl);
                if ($moduleCss) {
                    $moduleName = basename(dirname($moduleUrl));
                    $cssParts[] = "/* ========== MODULE: {$moduleName} ========== */\n" . $moduleCss;
                }
            }
        }

        return implode("\n\n", $cssParts);
    }

    /**
     * Get full CSS for IFRAME preview (with fallbacks).
     *
     * ETAP_07f_P3.5: Multi-file CSS support
     * Priority: css_files array > legacy single-file cache > manifest
     *
     * @param PrestaShopShop $shop
     * @return string
     */
    public function getFullCssForPreview(PrestaShopShop $shop): string
    {
        // Priority 1: Use multi-file CSS from css_files array (ETAP_07f_P3.5)
        if ($shop->hasScannedFiles() && $shop->getEnabledCssFilesCount() > 0) {
            $combinedCss = $this->fetchAllFromCssFiles($shop);
            if (!empty($combinedCss)) {
                return $combinedCss;
            }
        }

        // Priority 2: Legacy single-file system (fallback)
        $cssParts = [];

        // Cached theme CSS
        if ($shop->cached_theme_css) {
            $cssParts[] = "/* === THEME CSS === */\n" . $shop->cached_theme_css;
        }

        // Cached custom CSS
        if ($shop->cached_custom_css) {
            $cssParts[] = "/* === CUSTOM CSS === */\n" . $shop->cached_custom_css;
        }

        // Selected modules CSS from manifest
        $manifest = $shop->css_asset_manifest ?? [];
        $selectedModules = $manifest['selected_modules'] ?? [];
        $modulesCss = $manifest['modules_css_cache'] ?? [];

        foreach ($selectedModules as $moduleUrl) {
            if (isset($modulesCss[$moduleUrl])) {
                $moduleName = basename(dirname($moduleUrl));
                $cssParts[] = "/* === MODULE: {$moduleName} === */\n" . $modulesCss[$moduleUrl];
            }
        }

        return implode("\n\n", $cssParts);
    }

    /**
     * Update asset manifest for shop.
     *
     * @param PrestaShopShop $shop
     * @param array $manifest
     */
    public function updateAssetManifest(PrestaShopShop $shop, array $manifest): void
    {
        $shop->update([
            'css_asset_manifest' => $manifest,
            'css_manifest_fetched_at' => now(),
        ]);
    }

    /**
     * Get asset manifest from shop.
     *
     * @param PrestaShopShop $shop
     * @return array
     */
    public function getAssetManifest(PrestaShopShop $shop): array
    {
        return $shop->css_asset_manifest ?? [];
    }
}
