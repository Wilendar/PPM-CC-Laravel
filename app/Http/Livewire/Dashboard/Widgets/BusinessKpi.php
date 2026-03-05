<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BusinessKpi extends Component
{
    public array $metrics = [];

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $userId = Auth::id() ?? 0;
        $cacheKey = "dashboard_business_kpi_{$userId}";

        $this->metrics = Cache::remember($cacheKey, 300, function () {
            return [
                'products_today' => Product::whereDate('created_at', today())->count(),
                'products_week' => Product::where('created_at', '>=', now()->startOfWeek())->count(),
                'products_month' => Product::where('created_at', '>=', now()->startOfMonth())->count(),
                'products_year' => Product::where('created_at', '>=', now()->startOfYear())->count(),
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
                'active_users' => User::where('is_active', true)->count(),
            ];
        });
    }

    public function refreshMetrics(): void
    {
        $userId = Auth::id() ?? 0;
        Cache::forget("dashboard_business_kpi_{$userId}");
        $this->loadMetrics();
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.business-kpi');
    }
}
