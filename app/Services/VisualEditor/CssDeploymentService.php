<?php

declare(strict_types=1);

namespace App\Services\VisualEditor;

use App\Models\PrestaShopShop;
use App\Models\ShopStyleset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CSS Deployment Service for Visual Editor.
 *
 * Manages deployment of shop stylesets to PrestaShop themes:
 * - Generate CSS file from ShopStyleset
 * - Store locally for reference
 * - Upload to PrestaShop server via FTP (if configured)
 * - Version management for cache invalidation
 *
 * @package App\Services\VisualEditor
 * @since ETAP_07f Faza 8.4 - CSS Deployment
 */
class CssDeploymentService
{
    /**
     * CSS storage disk name.
     */
    private const STORAGE_DISK = 'public';

    /**
     * CSS files directory.
     */
    private const CSS_DIRECTORY = 'visual-editor/css';

    /**
     * Generated CSS cache duration in minutes.
     */
    private const CACHE_DURATION = 60;

    public function __construct(
        private StylesetManager $stylesetManager
    ) {}

    /**
     * Generate and store CSS for a shop styleset.
     *
     * Creates a versioned CSS file with all variables and custom styles.
     *
     * @param ShopStyleset $styleset Styleset to generate CSS for
     * @param bool $minify Whether to minify output
     * @return array ['path' => string, 'url' => string, 'version' => string]
     */
    public function generateCssFile(ShopStyleset $styleset, bool $minify = true): array
    {
        $css = $styleset->compileCss();

        if ($minify) {
            $css = $styleset->minifyCss();
        }

        // Generate filename with version hash
        $version = $this->generateVersion($css);
        $filename = $this->getCssFilename($styleset, $version);

        // Store to disk
        $path = self::CSS_DIRECTORY . '/' . $filename;
        Storage::disk(self::STORAGE_DISK)->put($path, $css);

        Log::info('CssDeploymentService: wygenerowano CSS', [
            'styleset_id' => $styleset->id,
            'shop_id' => $styleset->shop_id,
            'filename' => $filename,
            'size' => strlen($css),
            'minified' => $minify,
        ]);

        return [
            'path' => $path,
            'url' => Storage::disk(self::STORAGE_DISK)->url($path),
            'version' => $version,
            'size' => strlen($css),
        ];
    }

    /**
     * Deploy CSS to PrestaShop server.
     *
     * Uploads generated CSS file to the PrestaShop theme directory
     * using FTP or API (based on shop configuration).
     *
     * @param ShopStyleset $styleset Styleset to deploy
     * @param PrestaShopShop $shop Target shop
     * @return array Deployment result
     */
    public function deployToPrestaShop(ShopStyleset $styleset, PrestaShopShop $shop): array
    {
        // First, generate CSS file
        $generated = $this->generateCssFile($styleset, minify: true);

        // Check if shop has FTP deployment configured
        $deploymentConfig = $this->getDeploymentConfig($shop);

        if (!$deploymentConfig) {
            Log::warning('CssDeploymentService: brak konfiguracji deployment dla sklepu', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
            ]);

            return [
                'success' => false,
                'local' => $generated,
                'error' => 'Brak konfiguracji FTP dla sklepu. CSS wygenerowano lokalnie.',
            ];
        }

        try {
            $result = match ($deploymentConfig['method']) {
                'ftp' => $this->deployViaFtp($generated, $deploymentConfig),
                'sftp' => $this->deployViaSftp($generated, $deploymentConfig),
                'api' => $this->deployViaApi($styleset, $shop),
                default => throw new \RuntimeException("Nieobslugiwana metoda: {$deploymentConfig['method']}"),
            };

            Log::info('CssDeploymentService: deployment zakonczony', [
                'styleset_id' => $styleset->id,
                'shop_id' => $shop->id,
                'method' => $deploymentConfig['method'],
                'success' => $result['success'],
            ]);

            return array_merge($result, ['local' => $generated]);

        } catch (\Throwable $e) {
            Log::error('CssDeploymentService: blad deployment', [
                'styleset_id' => $styleset->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'local' => $generated,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate CSS snippet for inline embedding.
     *
     * Returns minified CSS ready to embed in <style> tags.
     *
     * @param ShopStyleset $styleset
     * @return string Minified CSS
     */
    public function getInlineCss(ShopStyleset $styleset): string
    {
        return $styleset->minifyCss();
    }

    /**
     * Get CSS URL for a shop (generates if needed).
     *
     * @param int $shopId Shop ID
     * @return string|null CSS URL or null if no styleset
     */
    public function getCssUrlForShop(int $shopId): ?string
    {
        $styleset = $this->stylesetManager->getForShop($shopId);

        if (!$styleset) {
            return null;
        }

        // Check if CSS file exists and is current
        $version = $this->generateVersion($styleset->compileCss());
        $filename = $this->getCssFilename($styleset, $version);
        $path = self::CSS_DIRECTORY . '/' . $filename;

        if (!Storage::disk(self::STORAGE_DISK)->exists($path)) {
            $this->generateCssFile($styleset);
        }

        return Storage::disk(self::STORAGE_DISK)->url($path);
    }

    /**
     * Clean old CSS versions for a styleset.
     *
     * Keeps only the latest version, deletes older files.
     *
     * @param ShopStyleset $styleset
     * @return int Number of files deleted
     */
    public function cleanOldVersions(ShopStyleset $styleset): int
    {
        $prefix = "styleset-{$styleset->id}-";
        $files = Storage::disk(self::STORAGE_DISK)->files(self::CSS_DIRECTORY);

        $deleted = 0;
        $currentVersion = $this->generateVersion($styleset->compileCss());
        $currentFilename = $this->getCssFilename($styleset, $currentVersion);

        foreach ($files as $file) {
            $filename = basename($file);
            if (str_starts_with($filename, $prefix) && $filename !== $currentFilename) {
                Storage::disk(self::STORAGE_DISK)->delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            Log::info('CssDeploymentService: wyczyszczono stare wersje', [
                'styleset_id' => $styleset->id,
                'deleted_count' => $deleted,
            ]);
        }

        return $deleted;
    }

    /**
     * Get all deployed CSS files.
     *
     * @return array List of deployed CSS files with metadata
     */
    public function listDeployedFiles(): array
    {
        $files = Storage::disk(self::STORAGE_DISK)->files(self::CSS_DIRECTORY);
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'path' => $file,
                'filename' => basename($file),
                'url' => Storage::disk(self::STORAGE_DISK)->url($file),
                'size' => Storage::disk(self::STORAGE_DISK)->size($file),
                'modified' => Storage::disk(self::STORAGE_DISK)->lastModified($file),
            ];
        }

        return $result;
    }

    /**
     * Generate version hash from CSS content.
     *
     * @param string $css CSS content
     * @return string Version hash (8 characters)
     */
    private function generateVersion(string $css): string
    {
        return substr(md5($css), 0, 8);
    }

    /**
     * Get CSS filename for styleset.
     *
     * @param ShopStyleset $styleset
     * @param string $version Version hash
     * @return string Filename
     */
    private function getCssFilename(ShopStyleset $styleset, string $version): string
    {
        $namespace = $styleset->css_namespace ?? 'pd';
        return "styleset-{$styleset->id}-{$namespace}-{$version}.css";
    }

    /**
     * Get deployment configuration for a shop.
     *
     * @param PrestaShopShop $shop
     * @return array|null Configuration or null if not configured
     */
    private function getDeploymentConfig(PrestaShopShop $shop): ?array
    {
        // Check if shop has deployment settings in config
        $settings = $shop->settings ?? [];

        if (empty($settings['css_deployment'])) {
            return null;
        }

        return $settings['css_deployment'];
    }

    /**
     * Deploy CSS via FTP.
     *
     * @param array $generated Generated CSS info
     * @param array $config FTP configuration
     * @return array Result
     */
    private function deployViaFtp(array $generated, array $config): array
    {
        $host = $config['host'] ?? null;
        $user = $config['user'] ?? null;
        $pass = $config['password'] ?? null;
        $remotePath = $config['remote_path'] ?? '/themes/default/css/';

        if (!$host || !$user || !$pass) {
            throw new \RuntimeException('Niepelna konfiguracja FTP');
        }

        // Get local file content
        $css = Storage::disk(self::STORAGE_DISK)->get($generated['path']);
        $remoteFilename = basename($generated['path']);

        // Connect and upload via FTP
        $ftp = ftp_connect($host);
        if (!$ftp) {
            throw new \RuntimeException("Nie mozna polaczyc z FTP: {$host}");
        }

        try {
            if (!ftp_login($ftp, $user, $pass)) {
                throw new \RuntimeException('Blad logowania FTP');
            }

            ftp_pasv($ftp, true);

            // Create temp file for upload
            $tempFile = tmpfile();
            fwrite($tempFile, $css);
            rewind($tempFile);

            $remoteFull = rtrim($remotePath, '/') . '/' . $remoteFilename;

            if (!ftp_fput($ftp, $remoteFull, $tempFile, FTP_BINARY)) {
                throw new \RuntimeException("Blad uploadu pliku: {$remoteFull}");
            }

            fclose($tempFile);

            return [
                'success' => true,
                'method' => 'ftp',
                'remote_path' => $remoteFull,
            ];

        } finally {
            ftp_close($ftp);
        }
    }

    /**
     * Deploy CSS via SFTP.
     *
     * @param array $generated Generated CSS info
     * @param array $config SFTP configuration
     * @return array Result
     */
    private function deployViaSftp(array $generated, array $config): array
    {
        // SFTP deployment requires phpseclib package
        // For now, return not implemented
        Log::warning('CssDeploymentService: SFTP deployment not yet implemented');

        return [
            'success' => false,
            'method' => 'sftp',
            'error' => 'SFTP deployment nie jest jeszcze zaimplementowany',
        ];
    }

    /**
     * Deploy CSS via PrestaShop API.
     *
     * Uses PrestaShop Web Service to upload CSS file.
     *
     * @param ShopStyleset $styleset
     * @param PrestaShopShop $shop
     * @return array Result
     */
    private function deployViaApi(ShopStyleset $styleset, PrestaShopShop $shop): array
    {
        // PrestaShop standard API doesn't support file upload to themes
        // This would require a custom module on PrestaShop side
        Log::warning('CssDeploymentService: API deployment requires custom PS module');

        return [
            'success' => false,
            'method' => 'api',
            'error' => 'API deployment wymaga dedykowanego modulu PrestaShop',
        ];
    }

    /**
     * Generate CSS link tag for shop.
     *
     * @param int $shopId Shop ID
     * @return string HTML link tag or empty string
     */
    public function getCssLinkTag(int $shopId): string
    {
        $url = $this->getCssUrlForShop($shopId);

        if (!$url) {
            return '';
        }

        return sprintf(
            '<link rel="stylesheet" href="%s" type="text/css">',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get deployment status for shop.
     *
     * @param PrestaShopShop $shop
     * @return array Status information
     */
    public function getDeploymentStatus(PrestaShopShop $shop): array
    {
        $styleset = $this->stylesetManager->getForShop($shop->id);

        if (!$styleset) {
            return [
                'has_styleset' => false,
                'deployed' => false,
                'message' => 'Brak stylesetu dla sklepu',
            ];
        }

        $version = $this->generateVersion($styleset->compileCss());
        $filename = $this->getCssFilename($styleset, $version);
        $path = self::CSS_DIRECTORY . '/' . $filename;

        $deployed = Storage::disk(self::STORAGE_DISK)->exists($path);

        return [
            'has_styleset' => true,
            'styleset_id' => $styleset->id,
            'styleset_name' => $styleset->name,
            'deployed' => $deployed,
            'local_url' => $deployed ? Storage::disk(self::STORAGE_DISK)->url($path) : null,
            'version' => $version,
            'deployment_config' => $this->getDeploymentConfig($shop) !== null,
        ];
    }
}
