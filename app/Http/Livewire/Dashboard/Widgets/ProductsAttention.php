<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProductsAttention extends Component
{
    public array $metrics = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $userId = Auth::id() ?? 0;
        $cacheKey = "dashboard_products_attention_{$userId}";

        $this->metrics = Cache::remember($cacheKey, 300, function () {
            return [
                'no_images' => Product::doesntHave('media')->count(),
                'no_prices' => Product::doesntHave('validPrices')->count(),
                'empty_categories' => Category::doesntHave('products')->count(),
                'sync_failed' => SyncJob::failed()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];
        });
    }

    public function refreshMetrics(): void
    {
        $userId = Auth::id() ?? 0;
        Cache::forget("dashboard_products_attention_{$userId}");
        $this->loadMetrics();
    }

    /**
     * Get severity CSS class based on count.
     * Red >10, Yellow >0, Green =0
     */
    public function getSeverityClass(int $count): string
    {
        if ($count > 10) {
            return 'text-red-400';
        }

        if ($count > 0) {
            return 'text-amber-400';
        }

        return 'text-emerald-400';
    }

    /**
     * Get severity dot class for the health indicator.
     */
    public function getSeverityDot(int $count): string
    {
        if ($count > 10) {
            return 'health-dot--error';
        }

        if ($count > 0) {
            return 'health-dot--warning';
        }

        return 'health-dot--healthy';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.products-attention');
    }
}
