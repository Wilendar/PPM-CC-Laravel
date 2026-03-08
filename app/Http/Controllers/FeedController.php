<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExportProfile;
use App\Models\ExportProfileLog;
use App\Services\Export\ProductExportService;
use App\Services\Export\FeedGeneratorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * FeedController - Public feed endpoint (token-based, no auth).
 *
 * Serves generated feed files for external consumers (Google Merchant, Ceneo, etc.).
 * Authentication is via unique token in URL, not session/cookie.
 *
 * @package App\Http\Controllers
 * @since ETAP_07f - Export & Feed System
 */
class FeedController extends Controller
{
    public function __construct(
        private ProductExportService $exportService,
        private FeedGeneratorFactory $generatorFactory,
    ) {}

    /**
     * Show/serve feed content.
     *
     * If a fresh cached file exists, serve it directly.
     * Otherwise, generate on-the-fly and cache for future requests.
     */
    public function show(Request $request, string $token): BinaryFileResponse
    {
        $profile = ExportProfile::where('token', $token)
            ->where('is_active', true)
            ->where('is_public', true)
            ->firstOrFail();

        // Log access
        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action' => 'accessed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Serve cached file if it exists and is fresh
        if ($profile->file_path && file_exists($profile->file_path) && $profile->isFeedFresh()) {
            $generator = $this->generatorFactory->make($profile->format);

            return response()->file($profile->file_path, [
                'Content-Type' => $generator->getContentType(),
                'X-Feed-Generated' => $profile->last_generated_at?->toIso8601String(),
                'X-Product-Count' => (string) ($profile->product_count ?? 0),
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        // Generate on-the-fly
        $startTime = microtime(true);
        $products = $this->exportService->getProducts($profile);
        $generator = $this->generatorFactory->make($profile->format);
        $filePath = $generator->generate($products, $profile);
        $duration = (int) (microtime(true) - $startTime);

        // Update profile stats
        $profile->update([
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'product_count' => count($products),
            'generation_duration' => $duration,
            'last_generated_at' => now(),
        ]);

        return response()->file($filePath, [
            'Content-Type' => $generator->getContentType(),
            'X-Feed-Generated' => now()->toIso8601String(),
            'X-Product-Count' => (string) count($products),
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Force download feed file.
     *
     * Generates the file if it does not exist yet,
     * then serves it as a download attachment.
     */
    public function download(Request $request, string $token): BinaryFileResponse
    {
        $profile = ExportProfile::where('token', $token)
            ->where('is_active', true)
            ->where('is_public', true)
            ->firstOrFail();

        // Log download
        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action' => 'downloaded',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Generate if needed
        if (!$profile->file_path || !file_exists($profile->file_path)) {
            $startTime = microtime(true);
            $products = $this->exportService->getProducts($profile);
            $generator = $this->generatorFactory->make($profile->format);
            $filePath = $generator->generate($products, $profile);
            $duration = (int) (microtime(true) - $startTime);

            $profile->update([
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'product_count' => count($products),
                'generation_duration' => $duration,
                'last_generated_at' => now(),
            ]);
        }

        $generator = $this->generatorFactory->make($profile->format);
        $filename = $profile->slug . '.' . $generator->getFileExtension();

        return response()->download($profile->file_path, $filename, [
            'Content-Type' => $generator->getContentType(),
        ]);
    }
}
