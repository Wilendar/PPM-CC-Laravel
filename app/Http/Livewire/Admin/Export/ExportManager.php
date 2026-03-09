<?php

namespace App\Http\Livewire\Admin\Export;

use App\Models\ExportProfile;
use App\Models\ExportProfileLog;
use App\Services\Export\ExportProfileService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ExportManager Livewire Component
 *
 * Admin panel for managing export profiles and public feeds.
 * Features: filtering, sorting, generation, toggle active/public,
 * duplicate, delete, feed URL copy.
 *
 * @package App\Http\Livewire\Admin\Export
 */
#[Layout('layouts.admin')]
#[Title('Eksport & Feedy - Admin PPM')]
class ExportManager extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Tabs
    public string $activeTab = 'profiles';

    // Filters (profiles)
    public string $search = '';
    public string $filterFormat = '';
    public string $filterSchedule = '';

    // Sorting
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal state
    public bool $showDeleteModal = false;
    public ?int $deletingProfileId = null;
    public string $deletingProfileName = '';

    // Log filters
    public string $logFilterProfile = '';
    public string $logFilterAction = '';
    public string $logFilterDateFrom = '';
    public string $logFilterDateTo = '';
    public string $logFilterBotType = ''; // 'bots', 'humans', '' (all)

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        if (Auth::check()) {
            Gate::authorize('export.read');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterFormat(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSchedule(): void
    {
        $this->resetPage();
    }

    public function updatingLogFilterProfile(): void
    {
        $this->resetPage(pageName: 'logsPage');
    }

    public function updatingLogFilterAction(): void
    {
        $this->resetPage(pageName: 'logsPage');
    }

    public function updatingLogFilterDateFrom(): void
    {
        $this->resetPage(pageName: 'logsPage');
    }

    public function updatingLogFilterDateTo(): void
    {
        $this->resetPage(pageName: 'logsPage');
    }

    public function updatingLogFilterBotType(): void
    {
        $this->resetPage(pageName: 'logsPage');
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated and filtered profiles.
     */
    public function getProfilesProperty()
    {
        $query = ExportProfile::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('slug', 'like', "%{$this->search}%")
                  ->orWhere('format', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterFormat) {
            $query->where('format', $this->filterFormat);
        }

        if ($this->filterSchedule) {
            $query->where('schedule', $this->filterSchedule);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(10);
    }

    /**
     * Get paginated and filtered logs.
     */
    public function getLogsProperty()
    {
        if (Auth::check() && !Auth::user()->can('export.view_logs')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'pageName' => 'logsPage',
            ]);
        }

        $query = ExportProfileLog::with(['profile', 'user'])
            ->orderBy('created_at', 'desc');

        if ($this->logFilterProfile) {
            $query->where('export_profile_id', $this->logFilterProfile);
        }
        if ($this->logFilterAction) {
            $query->where('action', $this->logFilterAction);
        }
        if ($this->logFilterDateFrom) {
            $query->whereDate('created_at', '>=', $this->logFilterDateFrom);
        }
        if ($this->logFilterDateTo) {
            $query->whereDate('created_at', '<=', $this->logFilterDateTo);
        }
        if ($this->logFilterBotType === 'bots') {
            $query->bots();
        } elseif ($this->logFilterBotType === 'humans') {
            $query->humans();
        }

        return $query->paginate(15, ['*'], 'logsPage');
    }

    /**
     * Get summary statistics.
     */
    public function getStatsProperty(): array
    {
        $totalSize = ExportProfile::whereNotNull('file_size')->sum('file_size');

        return [
            'total' => ExportProfile::count(),
            'active' => ExportProfile::active()->count(),
            'public' => ExportProfile::active()->where('is_public', true)->count(),
            'total_products' => number_format(ExportProfile::whereNotNull('product_count')->max('product_count') ?? 0),
            'total_size' => $this->formatFileSize((int) $totalSize),
            'downloads' => ExportProfileLog::where('action', 'downloaded')->count(),
        ];
    }

    /**
     * Get aggregated feed stats per profile for mini-stats display.
     */
    public function getFeedStatsProperty(): array
    {
        return ExportProfileLog::selectRaw("
            export_profile_id,
            COUNT(*) as total_requests,
            COUNT(DISTINCT ip_address) as unique_ips,
            SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_requests,
            SUM(CASE WHEN is_bot = 0 THEN 1 ELSE 0 END) as human_requests,
            AVG(response_time_ms) as avg_response_ms,
            SUM(CASE WHEN served_from = 'cache' THEN 1 ELSE 0 END) as cache_hits,
            SUM(CASE WHEN served_from IS NOT NULL AND served_from != 'cache' THEN 1 ELSE 0 END) as cache_misses,
            MAX(created_at) as last_access
        ")
        ->whereIn('action', ['accessed', 'downloaded'])
        ->groupBy('export_profile_id')
        ->get()
        ->keyBy('export_profile_id')
        ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Switch between profiles and logs tabs.
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        if ($tab === 'logs') {
            $this->resetPage(pageName: 'logsPage');
        }
    }

    /**
     * Sort by given field (toggle direction if same field).
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Generate feed now for a profile.
     */
    public function generateNow(int $profileId): void
    {
        if (Auth::check()) {
            Gate::authorize('export.read');
        }

        $profile = ExportProfile::findOrFail($profileId);

        try {
            $factory = app(\App\Services\Export\FeedGeneratorFactory::class);
            $productService = app(\App\Services\Export\ProductExportService::class);

            $startTime = microtime(true);
            $products = $productService->getProducts($profile);

            $generator = $factory->make($profile->format);
            $absolutePath = $generator->generate($products, $profile);

            $duration = (int) round((microtime(true) - $startTime) * 1000);
            $fileSize = file_exists($absolutePath) ? filesize($absolutePath) : null;

            // Store relative path from storage/app for model
            $relativePath = str_starts_with($absolutePath, storage_path('app/'))
                ? substr($absolutePath, strlen(storage_path('app/')))
                : $absolutePath;

            $profile->update([
                'file_path' => $relativePath,
                'file_size' => $fileSize,
                'product_count' => count($products),
                'generation_duration' => $duration,
                'last_generated_at' => now(),
                'next_generation_at' => $this->calculateNextGeneration($profile),
            ]);

            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action' => 'generated',
                'user_id' => Auth::id(),
                'product_count' => count($products),
                'file_size' => $fileSize,
                'duration' => $duration,
            ]);

            Log::info('Export profile generated', [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'format' => $profile->format,
                'product_count' => count($products),
                'duration_ms' => $duration,
            ]);

            session()->flash('success', "Feed \"{$profile->name}\" wygenerowany pomyslnie ({$duration}ms, " . count($products) . " produktow).");
        } catch (\Exception $e) {
            ExportProfileLog::create([
                'export_profile_id' => $profile->id,
                'action' => 'error',
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Export profile generation failed', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', "Blad generowania: {$e->getMessage()}");
        }
    }

    /**
     * Toggle profile active status.
     */
    public function toggleActive(int $profileId): void
    {
        if (Auth::check()) {
            Gate::authorize('export.update');
        }

        $profile = ExportProfile::findOrFail($profileId);
        $profile->update(['is_active' => !$profile->is_active]);

        $status = $profile->is_active ? 'aktywowany' : 'dezaktywowany';
        session()->flash('success', "Profil \"{$profile->name}\" {$status}.");
    }

    /**
     * Toggle profile public/feed status.
     */
    public function togglePublic(int $profileId): void
    {
        if (Auth::check()) {
            Gate::authorize('export.manage_feeds');
        }

        $profile = ExportProfile::findOrFail($profileId);
        $profile->update(['is_public' => !$profile->is_public]);

        $status = $profile->is_public ? 'upubliczniony' : 'ukryty';
        session()->flash('success', "Feed \"{$profile->name}\" {$status}.");
    }

    /**
     * Duplicate an export profile.
     */
    public function duplicateProfile(int $profileId): void
    {
        if (Auth::check()) {
            Gate::authorize('export.create');
        }

        $profile = ExportProfile::findOrFail($profileId);
        $service = app(ExportProfileService::class);
        $newProfile = $service->duplicate($profile, Auth::id() ?? 0);

        Log::info('Export profile duplicated', [
            'source_id' => $profile->id,
            'new_id' => $newProfile->id,
        ]);

        session()->flash('success', "Profil zduplikowany jako \"{$newProfile->name}\".");
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $profileId): void
    {
        $profile = ExportProfile::findOrFail($profileId);
        $this->deletingProfileId = $profileId;
        $this->deletingProfileName = $profile->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the profile after confirmation.
     */
    public function deleteProfile(): void
    {
        if (Auth::check()) {
            Gate::authorize('export.delete');
        }

        if (!$this->deletingProfileId) {
            return;
        }

        $profile = ExportProfile::findOrFail($this->deletingProfileId);
        $profileName = $profile->name;

        $service = app(ExportProfileService::class);
        $service->delete($profile);

        Log::info('Export profile deleted', ['profile_name' => $profileName]);

        $this->showDeleteModal = false;
        $this->deletingProfileId = null;
        $this->deletingProfileName = '';

        session()->flash('success', "Profil \"{$profileName}\" zostal usuniety.");
    }

    /**
     * Regenerate token for a profile (invalidates old URL).
     */
    public function regenerateToken(int $profileId): void
    {
        if (Auth::check()) {
            Gate::authorize('export.manage_feeds');
        }

        $profile = ExportProfile::findOrFail($profileId);
        $oldToken = substr($profile->token, 0, 8) . '...';
        $profile->regenerateToken();

        ExportProfileLog::create([
            'export_profile_id' => $profile->id,
            'action'            => 'token_rotated',
            'user_id'           => Auth::id(),
            'error_message'     => "Token rotated (old prefix: {$oldToken})",
        ]);

        Log::info('Feed token regenerated', [
            'profile_id'   => $profile->id,
            'profile_name' => $profile->name,
            'user_id'      => Auth::id(),
        ]);

        session()->flash('success', "Token dla \"{$profile->name}\" zostal zregenerowany. Stary URL przestanie dzialac.");
    }

    /**
     * Dispatch browser event to copy feed URL.
     */
    public function copyFeedUrl(int $profileId): void
    {
        $profile = ExportProfile::findOrFail($profileId);
        $this->dispatch('copy-to-clipboard', url: $profile->getFeedUrl());
    }

    /**
     * Clear all profile filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterFormat = '';
        $this->filterSchedule = '';
        $this->resetPage();
    }

    /**
     * Clear all log filters.
     */
    public function clearLogFilters(): void
    {
        $this->logFilterProfile = '';
        $this->logFilterAction = '';
        $this->logFilterDateFrom = '';
        $this->logFilterDateTo = '';
        $this->logFilterBotType = '';
        $this->resetPage(pageName: 'logsPage');
    }

    /*
    |--------------------------------------------------------------------------
    | SECURITY ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get rate limit status for a profile token.
     */
    public function getRateLimitStatus(int $profileId): array
    {
        $profile = ExportProfile::find($profileId);
        if (!$profile || !$profile->token) {
            return ['attempts' => 0, 'remaining' => 30, 'blocked' => false];
        }

        $limiter = app(RateLimiter::class);
        $key = 'feed:' . $profile->token;
        $attempts = $limiter->attempts($key);
        $remaining = max(0, 30 - $attempts);

        return [
            'attempts' => $attempts,
            'remaining' => $remaining,
            'blocked' => $remaining === 0,
        ];
    }

    /**
     * Check if generation lock (mutex) is active for a profile.
     */
    public function isGenerationLocked(int $profileId): bool
    {
        return Cache::has('feed-generate:' . $profileId);
    }

    /**
     * Manually clear rate limit for a profile.
     */
    public function clearRateLimit(int $profileId): void
    {
        Gate::authorize('export.manage_feeds');

        $profile = ExportProfile::findOrFail($profileId);
        $limiter = app(RateLimiter::class);

        $limiter->clear('feed:' . $profile->token);
        $limiter->clear('feed-dl:' . $profile->token);

        Log::info('Rate limit cleared manually', [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', "Rate limit dla \"{$profile->name}\" wyczyszczony.");
    }

    /**
     * Manually release generation lock for a profile.
     */
    public function releaseGenerationLock(int $profileId): void
    {
        Gate::authorize('export.manage_feeds');

        $profile = ExportProfile::findOrFail($profileId);

        Cache::forget('feed-generate:' . $profile->id);

        Log::info('Generation lock released manually', [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', "Lock generacji dla \"{$profile->name}\" zwolniony.");
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get format display labels.
     */
    public function getFormatLabels(): array
    {
        return [
            'csv' => 'CSV',
            'xlsx' => 'XLSX',
            'json' => 'JSON',
            'xml_google' => 'XML Google',
            'xml_ceneo' => 'XML Ceneo',
            'xml_prestashop' => 'XML PrestaShop',
        ];
    }

    /**
     * Get schedule display labels.
     */
    public function getScheduleLabels(): array
    {
        return [
            'manual' => 'Reczny',
            '1h' => 'Co godzine',
            '6h' => 'Co 6 godzin',
            '12h' => 'Co 12 godzin',
            '24h' => 'Co 24 godziny',
        ];
    }

    /**
     * Format file size for display.
     */
    public function formatFileSize(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1) . ' ' . $units[$i];
    }

    /**
     * Format response time for display (ms or seconds).
     */
    public function formatResponseTime(?int $ms): string
    {
        if ($ms === null) {
            return '-';
        }

        if ($ms >= 1000) {
            return round($ms / 1000, 1) . 's';
        }

        return $ms . 'ms';
    }

    /**
     * Extract domain from referer URL.
     */
    public function formatReferer(?string $referer): string
    {
        if (!$referer) {
            return '-';
        }

        $host = parse_url($referer, PHP_URL_HOST);

        return $host ?: $referer;
    }

    /**
     * Calculate next generation time based on schedule.
     */
    private function calculateNextGeneration(ExportProfile $profile): ?\Carbon\Carbon
    {
        $minutes = ExportProfile::SCHEDULE_MINUTES[$profile->schedule] ?? null;

        return $minutes ? now()->addMinutes($minutes) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.export.export-manager', [
            'formatLabels' => $this->getFormatLabels(),
            'scheduleLabels' => $this->getScheduleLabels(),
            'feedStats' => $this->feedStats,
        ]);
    }
}
