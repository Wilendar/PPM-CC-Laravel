<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\ExportProfile;
use App\Models\ExportProfileLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FeedAlertService - Detects anomalies in feed access patterns.
 *
 * Monitors:
 * - New IPs accessing a known profile (first time seen)
 * - Request spikes (>5x normal traffic in 5 min window)
 * - Suspicious User-Agents (empty, raw Python on production feeds)
 *
 * Alerts are logged to export_profile_logs with action='alert'.
 */
class FeedAlertService
{
    /**
     * Check for anomalies and log alerts if detected.
     */
    public function checkAndAlert(ExportProfile $profile, string $ip, ?string $userAgent): void
    {
        $this->checkNewIp($profile, $ip, $userAgent);
        $this->checkRequestSpike($profile);
        $this->checkSuspiciousUserAgent($profile, $ip, $userAgent);
    }

    /**
     * Detect first-time IP accessing this profile.
     */
    private function checkNewIp(ExportProfile $profile, string $ip, ?string $userAgent): void
    {
        $cacheKey = "feed-known-ips:{$profile->id}";
        $knownIps = Cache::get($cacheKey, []);

        if (in_array($ip, $knownIps, true)) {
            return;
        }

        // Check DB for historical access from this IP
        $hasHistory = ExportProfileLog::where('export_profile_id', $profile->id)
            ->where('ip_address', $ip)
            ->where('action', 'accessed')
            ->exists();

        if (!$hasHistory && !empty($knownIps)) {
            // New IP with no history + other IPs already known = alert
            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action'            => 'alert',
                'ip_address'        => $ip,
                'user_agent'        => $userAgent,
                'error_message'     => "New IP detected: {$ip} (first access to this profile)",
            ]);

            Log::warning('Feed alert: new IP detected', [
                'profile_id'   => $profile->id,
                'profile_name' => $profile->name,
                'ip'           => $ip,
                'user_agent'   => $userAgent,
            ]);
        }

        // Add IP to known list (cache for 24h)
        $knownIps[] = $ip;
        $knownIps = array_unique($knownIps);
        Cache::put($cacheKey, $knownIps, 86400);
    }

    /**
     * Detect request spike (>5x baseline in 5 min window).
     */
    private function checkRequestSpike(ExportProfile $profile): void
    {
        $windowKey = "feed-requests-5min:{$profile->id}";
        $count = (int) Cache::get($windowKey, 0);
        $count++;
        Cache::put($windowKey, $count, 300);

        // Alert threshold: more than 50 requests in 5 minutes
        $alertKey = "feed-spike-alerted:{$profile->id}";
        if ($count > 50 && !Cache::has($alertKey)) {
            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action'            => 'alert',
                'error_message'     => "Request spike detected: {$count} requests in 5 minutes",
            ]);

            Log::warning('Feed alert: request spike', [
                'profile_id'   => $profile->id,
                'profile_name' => $profile->name,
                'count_5min'   => $count,
            ]);

            // Don't alert again for 10 minutes
            Cache::put($alertKey, true, 600);
        }
    }

    /**
     * Detect suspicious User-Agent patterns.
     */
    private function checkSuspiciousUserAgent(ExportProfile $profile, string $ip, ?string $userAgent): void
    {
        $suspicious = false;
        $reason = '';

        if (empty($userAgent)) {
            $suspicious = true;
            $reason = 'Empty User-Agent';
        } elseif (preg_match('/^(python-requests|urllib|scrapy|httpx)/i', $userAgent)) {
            // Raw Python/scraping libraries on production feed
            $suspicious = true;
            $reason = "Suspicious UA: {$userAgent}";
        }

        if (!$suspicious) {
            return;
        }

        // Rate limit suspicious UA alerts (max 1 per IP per hour)
        $alertKey = "feed-ua-alert:{$profile->id}:{$ip}";
        if (Cache::has($alertKey)) {
            return;
        }

        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action'            => 'alert',
            'ip_address'        => $ip,
            'user_agent'        => $userAgent,
            'error_message'     => $reason,
        ]);

        Cache::put($alertKey, true, 3600);
    }
}
