<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExportProfile;
use App\Models\ExportProfileLog;
use App\Services\Export\FeedAlertService;
use App\Services\Export\FeedGeneratorFactory;
use App\Services\Export\ProductExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * FeedController - Public feed endpoint (token-based, no auth).
 *
 * Serves generated feed files for external consumers (Google Merchant, Ceneo, etc.).
 * Authentication is via unique token in URL, not session/cookie.
 *
 * Security layers:
 * - Rate limiting per-token (throttle:feed-access / feed-download)
 * - Token expiry validation
 * - IP whitelist per profile
 * - Mutex lock on on-the-fly generation (prevents DoS)
 * - Anomaly detection alerts (new IP, spikes, suspicious UA)
 *
 * @package App\Http\Controllers
 * @since ETAP_07f - Export & Feed System
 */
class FeedController extends Controller
{
    public function __construct(
        private ProductExportService $exportService,
        private FeedGeneratorFactory $generatorFactory,
        private FeedAlertService $alertService,
    ) {}

    /**
     * Show/serve feed content.
     *
     * If a fresh cached file exists, serve it directly.
     * Otherwise, generate on-the-fly (with mutex lock) and cache for future requests.
     */
    public function show(Request $request, string $token): BinaryFileResponse|Response
    {
        $startTime = microtime(true);

        $profile = $this->resolveProfile($token);
        $this->validateAccess($request, $profile);

        $botInfo = $this->detectBot($request->userAgent());

        // Run anomaly detection (async-safe, non-blocking)
        $this->alertService->checkAndAlert($profile, $request->ip(), $request->userAgent());

        // Serve cached file if it exists and is fresh
        if ($profile->file_path && file_exists($profile->file_path) && $profile->isFeedFresh()) {
            return $this->serveCachedFeed($request, $profile, $botInfo, $startTime);
        }

        // Generate on-the-fly with mutex lock (DoS protection)
        return $this->generateAndServe($request, $profile, $botInfo, $startTime);
    }

    /**
     * Force download feed file.
     *
     * Generates the file if it does not exist yet,
     * then serves it as a download attachment.
     */
    public function download(Request $request, string $token): BinaryFileResponse|Response
    {
        $startTime = microtime(true);

        $profile = $this->resolveProfile($token);
        $this->validateAccess($request, $profile);

        $botInfo = $this->detectBot($request->userAgent());
        $this->alertService->checkAndAlert($profile, $request->ip(), $request->userAgent());

        $servedFrom = 'cache';

        // Generate if needed (with mutex lock)
        if (!$profile->file_path || !file_exists($profile->file_path)) {
            $lock = Cache::lock('feed-generate:' . $profile->id, 30);

            if (!$lock->get()) {
                return response('Feed generation in progress. Please retry.', 503)
                    ->header('Retry-After', '10');
            }

            try {
                $genStart = microtime(true);
                $products = $this->exportService->getProducts($profile);
                $generator = $this->generatorFactory->make($profile->format);
                $filePath = $generator->generate($products, $profile);
                $duration = (int) (microtime(true) - $genStart);

                $profile->update([
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'product_count' => count($products),
                    'generation_duration' => $duration,
                    'last_generated_at' => now(),
                ]);

                $servedFrom = 'on_the_fly';
            } finally {
                $lock->release();
            }
        }

        $generator = $this->generatorFactory->make($profile->format);
        $contentType = $generator->getContentType();
        $filename = $profile->slug . '.' . $generator->getFileExtension();
        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action'            => 'downloaded',
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'response_time_ms'  => $responseTimeMs,
            'served_from'       => $servedFrom,
            'http_status'       => 200,
            'content_type'      => $contentType,
            'referer'           => $request->header('Referer'),
            'is_bot'            => $botInfo['is_bot'],
            'bot_name'          => $botInfo['bot_name'],
        ]);

        return response()->download($profile->file_path, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Resolve profile by token (active + public + not expired).
     */
    private function resolveProfile(string $token): ExportProfile
    {
        return ExportProfile::where('token', $token)
            ->where('is_active', true)
            ->where('is_public', true)
            ->where(function ($q) {
                $q->whereNull('token_expires_at')
                  ->orWhere('token_expires_at', '>', now());
            })
            ->firstOrFail();
    }

    /**
     * Validate IP whitelist access.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function validateAccess(Request $request, ExportProfile $profile): void
    {
        if (!$profile->isIpAllowed($request->ip())) {
            Log::warning('Feed access denied: IP not whitelisted', [
                'profile_id' => $profile->id,
                'ip'         => $request->ip(),
            ]);

            abort(403, 'IP not whitelisted');
        }
    }

    /**
     * Serve feed from cached file.
     */
    private function serveCachedFeed(
        Request $request,
        ExportProfile $profile,
        array $botInfo,
        float $startTime,
    ): BinaryFileResponse {
        $generator = $this->generatorFactory->make($profile->format);
        $contentType = $generator->getContentType();
        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action'            => 'accessed',
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'response_time_ms'  => $responseTimeMs,
            'served_from'       => 'cache',
            'http_status'       => 200,
            'content_type'      => $contentType,
            'referer'           => $request->header('Referer'),
            'is_bot'            => $botInfo['is_bot'],
            'bot_name'          => $botInfo['bot_name'],
        ]);

        return response()->file($profile->file_path, [
            'Content-Type' => $contentType,
            'X-Feed-Generated' => $profile->last_generated_at?->toIso8601String(),
            'X-Product-Count' => (string) ($profile->product_count ?? 0),
            'X-Served-From' => 'cache',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Generate feed on-the-fly with mutex lock (DoS protection).
     */
    private function generateAndServe(
        Request $request,
        ExportProfile $profile,
        array $botInfo,
        float $startTime,
    ): BinaryFileResponse|Response {
        // Mutex: only one generation per profile at a time
        $lock = Cache::lock('feed-generate:' . $profile->id, 30);

        if (!$lock->get()) {
            return response('Feed generation in progress. Please retry.', 503)
                ->header('Retry-After', '10');
        }

        try {
            $genStart = microtime(true);
            $products = $this->exportService->getProducts($profile);
            $generator = $this->generatorFactory->make($profile->format);
            $filePath = $generator->generate($products, $profile);
            $duration = (int) (microtime(true) - $genStart);
            $contentType = $generator->getContentType();
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $profile->update([
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'product_count' => count($products),
                'generation_duration' => $duration,
                'last_generated_at' => now(),
            ]);

            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action'            => 'accessed',
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
                'product_count'     => count($products),
                'file_size'         => filesize($filePath),
                'duration'          => $duration,
                'response_time_ms'  => $responseTimeMs,
                'served_from'       => 'on_the_fly',
                'http_status'       => 200,
                'content_type'      => $contentType,
                'referer'           => $request->header('Referer'),
                'is_bot'            => $botInfo['is_bot'],
                'bot_name'          => $botInfo['bot_name'],
            ]);

            return response()->file($filePath, [
                'Content-Type' => $contentType,
                'X-Feed-Generated' => now()->toIso8601String(),
                'X-Product-Count' => (string) count($products),
                'X-Served-From' => 'on_the_fly',
                'Cache-Control' => 'public, max-age=300',
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Detect if request comes from a known bot.
     *
     * @return array{is_bot: bool, bot_name: string|null}
     */
    private function detectBot(?string $ua): array
    {
        if (!$ua) {
            return ['is_bot' => false, 'bot_name' => null];
        }

        $bots = [
            'Googlebot'   => '/googlebot/i',
            'Bingbot'     => '/bingbot/i',
            'CeneoBot'    => '/ceneo/i',
            'FacebookBot' => '/facebookexternalhit/i',
            'YandexBot'   => '/yandexbot/i',
            'AhrefsBot'   => '/ahrefsbot/i',
            'SemrushBot'  => '/semrushbot/i',
            'Curl'        => '/curl\//i',
            'Wget'        => '/wget\//i',
            'Python'      => '/python-requests|urllib/i',
        ];

        foreach ($bots as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return ['is_bot' => true, 'bot_name' => $name];
            }
        }

        return ['is_bot' => false, 'bot_name' => null];
    }
}
